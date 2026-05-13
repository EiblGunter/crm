<?php
/**
 * Anti-Gravity Database Layer Implementation
 * Procedural wrapper for PDO with fixed result schema.
 * 
 * IMPORTANT: NO [] ARRAY SYNTAX ALLOWED!
 */

$ag_db_connection = null;

function db_connect($config, $connection_name = 'default') {
    global $ag_db_connection;
    
    $host = isset($config['host']) ? $config['host'] : 'localhost';
    $port = isset($config['port']) ? $config['port'] : '3306';
    $db   = isset($config['db']) ? $config['db'] : '';
    $user = isset($config['user']) ? $config['user'] : 'root';
    $pass = isset($config['pass']) ? $config['pass'] : '';
    $charset = isset($config['charset']) ? $config['charset'] : 'utf8mb4';

    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
    $options = array(
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES    => false,
    );

    try {
        $ag_db_connection = new PDO($dsn, $user, $pass, $options);
        return array(
            'success'       => true,
            'data'          => array(),
            'error'         => '',
            'affected_rows' => 0,
            'last_id'       => null
        );
    } catch (PDOException $e) {
        return array(
            'success'       => false,
            'data'          => array(),
            'error'         => $e->getMessage(),
            'affected_rows' => 0,
            'last_id'       => null
        );
    }
}

function db_query($sql, $params = array()) {
    global $ag_db_connection;
    
    if (!$ag_db_connection) {
        return array(
            'success'       => false,
            'data'          => array(),
            'error'         => 'No database connection established.',
            'affected_rows' => 0,
            'last_id'       => null
        );
    }

    try {
        $stmt = $ag_db_connection->prepare($sql);
        $stmt->execute($params);
        
        $data = array();
        if (stripos($sql, 'SELECT') === 0 || stripos($sql, 'SHOW') === 0 || stripos($sql, 'DESCRIBE') === 0) {
            $data = $stmt->fetchAll();
        }

        return array(
            'success'       => true,
            'data'          => $data,
            'error'         => '',
            'affected_rows' => $stmt->rowCount(),
            'last_id'       => $ag_db_connection->lastInsertId()
        );
    } catch (PDOException $e) {
        return array(
            'success'       => false,
            'data'          => array(),
            'error'         => $e->getMessage(),
            'affected_rows' => 0,
            'last_id'       => null
        );
    }
}

function db_select($table, $filters = array(), $options = array()) {
    $sql = "SELECT * FROM `$table`";
    $params = array();
    
    if (!empty($filters)) {
        $sql .= " WHERE ";
        $where_parts = array();
        foreach ($filters as $key => $value) {
            $where_parts[] = "`$key` = :$key";
            $params[":$key"] = $value;
        }
        $sql .= implode(' AND ', $where_parts);
    }
    
    if (isset($options['order_by'])) {
        $sql .= " ORDER BY " . $options['order_by'];
    }
    
    if (isset($options['limit'])) {
        $sql .= " LIMIT " . intval($options['limit']);
    }

    return db_query($sql, $params);
}

function db_insert($table, $data = array()) {
    $keys = array_keys($data);
    $fields = "`" . implode("`, `", $keys) . "`";
    $placeholders = ":" . implode(", :", $keys);
    
    $sql = "INSERT INTO `$table` ($fields) VALUES ($placeholders)";
    
    $params = array();
    foreach ($data as $key => $value) {
        $params[":$key"] = $value;
    }
    
    return db_query($sql, $params);
}

function db_update($table, $data = array(), $filters = array()) {
    $set_parts = array();
    $params = array();
    
    foreach ($data as $key => $value) {
        $set_parts[] = "`$key` = :data_$key";
        $params[":data_$key"] = $value;
    }
    
    $sql = "UPDATE `$table` SET " . implode(", ", $set_parts);
    
    if (!empty($filters)) {
        $sql .= " WHERE ";
        $where_parts = array();
        foreach ($filters as $key => $value) {
            $where_parts[] = "`$key` = :filter_$key";
            $params[":filter_$key"] = $value;
        }
        $sql .= implode(' AND ', $where_parts);
    }
    
    return db_query($sql, $params);
}

function db_delete($table, $filters = array()) {
    $sql = "DELETE FROM `$table`";
    $params = array();
    
    if (!empty($filters)) {
        $sql .= " WHERE ";
        $where_parts = array();
        foreach ($filters as $key => $value) {
            $where_parts[] = "`$key` = :$key";
            $params[":$key"] = $value;
        }
        $sql .= implode(' AND ', $where_parts);
    }
    
    return db_query($sql, $params);
}

function db_transaction_start() {
    global $ag_db_connection;
    return $ag_db_connection->beginTransaction();
}

function db_commit() {
    global $ag_db_connection;
    return $ag_db_connection->commit();
}

function db_rollback() {
    global $ag_db_connection;
    return $ag_db_connection->rollBack();
}
