<?php
/**
 * Schreibt einen Eintrag in die crm_log Tabelle.
 * Angepasst für Antigravity DB Layer.
 *
 * @param array $params Assoziatives Array mit Log-Daten.
 * @return array Resultat der db_insert Operation.
 */
function crm_log_add($params = array()) {
    // 1. Standardwerte definieren
    $default_params = array(
        'app_name'           => '',
        'my_role'            => '', 
        'action_type'        => 'info',
        'table_name'         => '',
        'record_id'          => '',
        'changed_field_name' => '',
        'field_old_value'    => '',
        'field_new_value'    => '',
        'full_old_data'      => array(),
        'full_new_data'      => array(),
        'description'        => '',
        'ip_address'         => $_SERVER['REMOTE_ADDR'] ?? '',
        'created_by'         => $_SESSION['usr_login'] ?? '' 
    );

    $log = array_merge($default_params, $params);

    // 2. JSON und Arrays aufbereiten
    // Falls JSON-Strings statt Arrays übergeben wurden, diese erst decodieren
    if (is_string($log['full_old_data'])) {
        $log['full_old_data'] = json_decode($log['full_old_data'], true) ?: array();
    }
    if (is_string($log['full_new_data'])) {
        $log['full_new_data'] = json_decode($log['full_new_data'], true) ?: array();
    }

    // Feld-Updates ins JSON mergen (wie in Scriptcase-Vorlage gewünscht)
    if (!empty($log['changed_field_name'])) {
        $field = $log['changed_field_name'];
        $log['full_old_data'][$field] = $log['field_old_value'];
        $log['full_new_data'][$field] = $log['field_new_value'];
    }

    // In diesem AG Projekt wandelt der DB-Layer (db_insert) Arrays automatisch in JSON um,
    // falls die Spalte vom Typ JSON/Text ist. Wir übergeben daher die Arrays direkt.
    
    // 3. Daten für db_insert vorbereiten
    $insert_data = array(
        'app_name'           => $log['app_name'],
        'my_role'            => $log['my_role'],
        'action_type'        => $log['action_type'],
        'table_name'         => $log['table_name'],
        'record_id'          => (string)$log['record_id'],
        'changed_field_name' => $log['changed_field_name'],
        'field_old_value'    => (string)$log['field_old_value'],
        'field_new_value'    => (string)$log['field_new_value'],
        'full_old_data'      => !empty($log['full_old_data']) ? json_encode($log['full_old_data'], JSON_UNESCAPED_UNICODE) : null,
        'full_new_data'      => !empty($log['full_new_data']) ? json_encode($log['full_new_data'], JSON_UNESCAPED_UNICODE) : null,
        'description'        => $log['description'],
        'ip_address'         => $log['ip_address'],
        'created_by'         => $log['created_by']
    );

    // 4. Datenbank-Operation via AG DB Layer
    // Die Funktion db_insert kümmert sich um Escaping und Prepared Statements.
    return db_insert('crm_log', $insert_data);
}

/**
 * Erstellt die crm_log Tabelle, falls sie nicht existiert.
 */
function crm_log_init_table() {
    $sql = "CREATE TABLE IF NOT EXISTS `crm_log` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `app_name` varchar(100) DEFAULT NULL,
              `my_role` varchar(50) DEFAULT NULL,
              `action_type` varchar(50) NOT NULL COMMENT 'insert, update, soft_delete, restore, error, login, undo, redo',
              `table_name` varchar(100) DEFAULT NULL,
              `record_id` varchar(100) DEFAULT NULL,
              `changed_field_name` varchar(100) DEFAULT NULL,
              `field_old_value` text DEFAULT NULL,
              `field_new_value` text DEFAULT NULL,
              `full_old_data` longtext DEFAULT NULL,
              `full_new_data` longtext DEFAULT NULL,
              `description` text DEFAULT NULL,
              `ip_address` varchar(50) DEFAULT NULL,
              `is_deleted_yn` int(11) NOT NULL DEFAULT 0,
              `created_by` varchar(50) DEFAULT NULL,
              `created` datetime NOT NULL DEFAULT current_timestamp(),
              `updated_by` varchar(50) DEFAULT NULL,
              `updated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `idx_action_type` (`action_type`),
              KEY `idx_table_record` (`table_name`,`record_id`),
              KEY `idx_changed_field` (`changed_field_name`),
              KEY `idx_created` (`created`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
            
    return db_query($sql);
}
