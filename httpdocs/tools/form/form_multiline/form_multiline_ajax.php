<?php
/**
 * Project: ge_grid_edit
 * Author:  Gunter Eibl
 * Version: 1.0.5
 * Date:    2023-10-27
 */

ob_start();
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/tools/db/db.php';

if (!getenv('MYSQL_HOST')) {
    $envPath = $_SERVER['DOCUMENT_ROOT'] . '/../.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                putenv(sprintf('%s=%s', trim($name), trim($value)));
            }
        }
    }
}

if (!function_exists('db_connect')) {
    // Falls db.php nicht aktiv ist
} else {
    $mysql_config = array(
        'driver'  => 'mysql',
        'host'    => getenv('MYSQL_HOST') ?: '127.0.0.1',
        'port'    => getenv('MYSQL_PORT') ?: '3307',
        'db'      => getenv('MYSQL_DATABASE') ?: 'crm_db',
        'user'    => getenv('MYSQL_USER') ?: 'root',
        'pass'    => getenv('MYSQL_PASSWORD') ?: 'Hotel111',
        'charset' => 'utf8mb4'
    );
    @db_connect($mysql_config, 'default');
}
// --- HELPER ---
function makeThumbnail($blob, $width = 80) {
    if (empty($blob)) return null;
    try {
        $src = imagecreatefromstring($blob);
        if (!$src) return null;
        $oldW = imagesx($src); $oldH = imagesy($src);
        $height = floor($oldH * ($width / $oldW));
        $dst = imagecreatetruecolor($width, $height);
        imagealphablending($dst, false); imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $oldW, $oldH);
        ob_start(); imagepng($dst); $data = ob_get_contents(); ob_end_clean();
        imagedestroy($src); imagedestroy($dst);
        return 'data:image/png;base64,' . base64_encode($data);
    } catch (Exception $e) { return null; }
}

function getLookupData($configFields) {
    $lookups = array();
    foreach ($configFields as $field) {
        if (isset($field['lookup']) && !empty($field['lookup'])) {
            $options = array();
            if (isset($field['lookup']['type']) && $field['lookup']['type'] == 'sql') {
                $resL = db_query($field['lookup']['source']);
                if ($resL['success'] && !empty($resL['data'])) {
                    foreach ($resL['data'] as $row) {
                        $row_vals = array_values($row);
                        $key = $row_vals[0];
                        $val = isset($row_vals[1]) ? $row_vals[1] : $key;
                        $options[$key] = $val;
                    }
                }
            } else if (isset($field['lookup']['manual'])) {
                $options = $field['lookup']['manual'];
            }
            $lookups[$field['fieldName']] = $options;
        }
    }
    return $lookups;
}

function sendJson($data) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$gridName = isset($_POST['gridName']) ? $_POST['gridName'] : 'adresse_multiline';
$tableName = isset($_POST['tableName']) ? $_POST['tableName'] : 'adresse';

// 0. EVENTS 
if ($action == 'execute_event') {
    $eventName = isset($_POST['eventName']) ? $_POST['eventName'] : '';
    $fieldName = isset($_POST['fieldName']) ? $_POST['fieldName'] : '';
    $formDataJson = isset($_POST['formData']) ? $_POST['formData'] : '{}';
    $data = json_decode($formDataJson, true);
    if (!is_array($data)) $data = array();

    $resConf = db_select('grid_definition', array('grid_name' => $gridName));
    if (!$resConf['success'] || empty($resConf['data'])) {
        sendJson(array('error' => 'Config not found'));
    }
    $config = json_decode($resConf['data'][0]['config_json'], true);
    
    $eventCode = '';
    
    if (!empty($fieldName)) {
        foreach($config['fields'] as $f) {
            if ($f['fieldName'] == $fieldName) {
                if (isset($f['evt_' . $eventName])) {
                    $eventCode = trim($f['evt_' . $eventName]);
                } else if (isset($f['events']) && isset($f['events'][$eventName])) {
                    $eventCode = trim($f['events'][$eventName]);
                }
                break;
            }
        }
    } else {
        if (isset($config['evt_' . $eventName])) {
            $eventCode = trim($config['evt_' . $eventName]);
        } else if (isset($config['events']) && isset($config['events'][$eventName])) {
            $eventCode = trim($config['events'][$eventName]);
        }
    }
    
    if (empty($eventCode)) {
        sendJson(array('status' => 'ok', 'data' => $data));
    }
    
    if (isset($config['evt_libraries']) && !empty(trim($config['evt_libraries']))) {
        try { eval(trim($config['evt_libraries'])); } catch (Throwable $e) {}
    } else if (isset($config['events']) && !empty(trim($config['events']['libraries']))) {
        try { eval(trim($config['events']['libraries'])); } catch (Throwable $e) {}
    }
    
    try {
        set_error_handler(function($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) return;
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
        
        ob_start();
        eval($eventCode);
        $output = ob_get_clean();
        
        restore_error_handler();
        sendJson(array('status' => 'ok', 'data' => $data, 'debugOut' => $output));
    } catch (Throwable $e) {
        ob_end_clean();
        restore_error_handler();
        $msg = str_replace("eval()'d code", "Dein PHP-Code", $e->getMessage());
        sendJson(array('error' => 'Fehler: ' . $msg));
    }
}

// 1. CONFIG
if ($action == 'load_config') {
    $res = db_select('grid_definition', array('grid_name' => $gridName));
    if ($res['success'] && !empty($res['data'])) {
        $config = json_decode($res['data'][0]['config_json'], true);
        $lookups = getLookupData($config['fields']);
        sendJson(array('status' => 'ok', 'baseConfig' => $config, 'lookups' => $lookups));
    } else {
        $resCheck = db_query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . addslashes($tableName) . "'");
        $exists = ($resCheck['success'] && !empty($resCheck['data']));
        sendJson(array('status' => 'config_missing', 'table_exists' => $exists, 'gridName' => $gridName, 'tableName' => $tableName));
    }
}

// 2. CREATE INITIAL CONFIG
if ($action == 'create_initial_config') {
    $tableName = $_POST['tableName'];
    $json = json_encode(array(
        'tableName' => $tableName,
        'fields' => array(array('fieldName'=>'id', 'fieldTyp'=>'integer', 'label'=>'ID', 'readonly'=>true, 'width'=>50)),
        'storedLayout' => array(),
        'numberPerPage' => 10,
        'new_record' => 'top',
        'delete_record' => 'delete',
        'row_color_odd' => '#ffffff',
        'row_color_even' => '#f9f9f9'
    ));
    db_insert('grid_definition', array('grid_name' => $gridName, 'config_json' => $json));
    sendJson(array('status' => 'ok'));
}

// 3. SCHEMA SYNC CHECKS
if ($action == 'check_schema') {
    $tableName = $_POST['tableName'];
    $rawFields = isset($_POST['currentFields']) ? $_POST['currentFields'] : '[]';
    $currentFields = json_decode($rawFields, true);
    if (!is_array($currentFields)) $currentFields = array();
    
    $existingNames = array();
    foreach($currentFields as $f) {
        if (isset($f['fieldName'])) $existingNames[] = $f['fieldName'];
    }
    
    $gridName = isset($_POST['gridName']) ? $_POST['gridName'] : '';
    if ($gridName) {
        $resConfig = db_select('grid_definition', array('grid_name' => $gridName));
        if ($resConfig['success'] && !empty($resConfig['data'])) {
            $dbconfig = json_decode($resConfig['data'][0]['config_json'], true);
            if (isset($dbconfig['fields']) && is_array($dbconfig['fields'])) {
                foreach($dbconfig['fields'] as $fld) {
                    if (isset($fld['fieldName']) && !in_array($fld['fieldName'], $existingNames)) {
                        $existingNames[] = $fld['fieldName'];
                    }
                }
            }
        }
    }
    
    $resCols = db_query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . addslashes($tableName) . "'");
    
    $newColumns = array();
    if ($resCols['success']) {
        foreach ($resCols['data'] as $row) {
            $col = $row['COLUMN_NAME'];
            if (!in_array($col, $existingNames)) $newColumns[] = $col;
        }
    }
    sendJson(array('status' => 'ok', 'newColumns' => $newColumns));
}

// 4. UPDATE SCHEMA
if ($action == 'update_schema') {
    function getMeta($c, $t) {
        $resD = db_query("SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . addslashes($t) . "' AND COLUMN_NAME = '" . addslashes($c) . "'");
        if(!$resD['success'] || empty($resD['data'])) return array('fieldName'=>$c, 'fieldTyp'=>'string', 'width'=>150);
        $type = strtolower($resD['data'][0]['DATA_TYPE']);
        $f = array('fieldName'=>$c, 'label'=>ucfirst($c), 'width'=>150, 'required'=>false);
        if(strpos($type,'text')!==false) { $f['fieldTyp']='multiple_line_text'; $f['width']=300; }
        else if($type=='date') $f['fieldTyp']='date';
        else if(strpos($type,'int')!==false) $f['fieldTyp']='integer';
        else if(strpos($type,'decimal')!==false || strpos($type,'float')!==false) $f['fieldTyp']='decimal';
        else if(strpos($type,'blob')!==false) $f['fieldTyp']='image';
        else $f['fieldTyp']='string';
        return $f;
    }

    $tableName = $_POST['tableName'];
    $colsToAdd = isset($_POST['cols']) ? $_POST['cols'] : array();
    if (!is_array($colsToAdd)) {
        $colsToAdd = $colsToAdd ? array($colsToAdd) : array();
    }
    
    $resConfig = db_select('grid_definition', array('grid_name' => $gridName));
    if (!$resConfig['success'] || empty($resConfig['data'])) {
        sendJson(array('status' => 'error', 'msg' => 'Config not found'));
    }
    $config = json_decode($resConfig['data'][0]['config_json'], true);
    if (!is_array($config)) $config = array('fields' => array());
    if (!isset($config['fields']) || !is_array($config['fields'])) $config['fields'] = array();
    
    foreach ($colsToAdd as $col) {
        $exists = false;
        foreach ($config['fields'] as $fld) {
            if (isset($fld['fieldName']) && $fld['fieldName'] == $col) $exists = true;
        }
        if (!$exists) {
            $newField = getMeta($col, $tableName);
            if ($newField) $config['fields'][] = $newField;
        }
    }
    $json = json_encode($config);
    db_update('grid_definition', array('config_json' => $json), array('grid_name' => $gridName));
    sendJson(array('status' => 'ok'));
}

// 5. SAVE CONFIG
if ($action == 'save_config') {
    $json = $_POST['config'];
    $res = db_update('grid_definition', array('config_json' => $json), array('grid_name' => $gridName));
    if (!$res['success']) sendJson(array('error' => $res['error']));
    else sendJson(array('status' => 'ok'));
}

// 6. LOAD DATA
if ($action == 'load_data') {
    $resC = db_select('grid_definition', array('grid_name' => $gridName));
    if(!$resC['success'] || empty($resC['data'])) { sendJson(array('error'=>'Config DB Error')); }
    $config = json_decode($resC['data'][0]['config_json'], true);
    
    $tableName = $config['tableName'];
    $page = intval($_POST['page']);
    $limit = intval($_POST['limit']);
    $offset = ($page - 1) * $limit;

    $where = " WHERE 1=1 "; 
    if (isset($config['delete_record']) && $config['delete_record'] !== 'delete' && !empty($config['delete_record'])) {
        $delField = $config['delete_record'];
        $where .= " AND ($delField IS NULL OR $delField != 1) ";
    }

    if (isset($_POST['filters']) && is_array($_POST['filters'])) {
        foreach ($_POST['filters'] as $f => $v) {
            if ($v !== '' && $v !== null && $v !== 'null') {
                $where .= " AND $f LIKE '%".addslashes($v)."%' ";
            }
        }
    }

    $order = " ORDER BY id DESC ";
    if (!empty($_POST['sortField'])) {
        $order = " ORDER BY ".addslashes($_POST['sortField'])." ".($_POST['sortOrder']=='ASC'?'ASC':'DESC');
    }

    $sFields = array("id");
    foreach ($config['fields'] as $f) {
        if (!empty($f['isCustom'])) continue; // skip virtual UI-only fields
        $sFields[] = $f['fieldName'];
    }
    $sFields = array_unique($sFields);
    $selectFields = implode(", ", $sFields);

    $resCount = db_query("SELECT COUNT(*) as _cnt FROM $tableName $where");
    $total = ($resCount['success'] && !empty($resCount['data'])) ? $resCount['data'][0]['_cnt'] : 0;

    $res = db_query("SELECT $selectFields FROM $tableName $where $order LIMIT $limit OFFSET $offset");

    $rows = array();
    
    // Inject Grid Libraries once
    if (isset($config['evt_libraries']) && !empty(trim($config['evt_libraries']))) {
        try { eval(trim($config['evt_libraries'])); } catch (Throwable $e) {}
    }

    if ($res['success']) {
        foreach ($res['data'] as $dbRow) {
            $data = $dbRow;
            
            // Execute global onLoad event
            if (isset($config['evt_onload']) && !empty(trim($config['evt_onload']))) {
                try { eval(trim($config['evt_onload'])); } catch (Throwable $e) {}
            }

            $row = array();
            $row['id'] = isset($data['id']) ? $data['id'] : '';
            
            foreach ($config['fields'] as $f) {
                $fn = $f['fieldName'];
                $val = isset($data[$fn]) ? $data[$fn] : '';
                
                if (($f['fieldTyp'] == 'image' || $f['fieldTyp'] == 'signature') && !empty($val)) {
                    $row[$fn . '_preview'] = makeThumbnail($val, 80); 
                    $row[$fn . '_has_image'] = true;
                    $row[$fn] = ''; 
                } else {
                    $row[$fn] = $val;
                }
            }
            $rows[] = $row;
        }
    }
    sendJson(array('rows' => $rows, 'total' => $total));
}

// 7. ACTIONS
if ($action == 'add_record') {
    $tableName = $_POST['tableName'];
    $gridName = isset($_POST['gridName']) ? $_POST['gridName'] : '';
    $insertData = array();
    
    if ($gridName) {
        $resConf = db_select('grid_definition', array('grid_name' => $gridName));
        if ($resConf['success'] && !empty($resConf['data'])) {
            $config = json_decode($resConf['data'][0]['config_json'], true);
            foreach ($config['fields'] as $f) {
                if (isset($f['defaultValue']) && trim($f['defaultValue']) !== '') {
                    $insertData[$f['fieldName']] = $f['defaultValue'];
                }
            }
        }
    }
    if (empty($insertData)) {
        $insertData = array('name' => 'Neuer Eintrag');
    }
    
    $res = db_insert($tableName, $insertData);
    if (!$res['success']) {
        db_insert($tableName, array());
    }
    sendJson(array('status' => 'ok'));
}

if ($action == 'delete_record') {
    $tableName = $_POST['tableName']; $id = intval($_POST['id']); $mode = $_POST['mode'];
    if ($mode === 'delete') {
        $res = db_delete($tableName, array('id' => $id));
    } else {
        $res = db_update($tableName, array($mode => 1), array('id' => $id));
    }
    if (!$res['success']) sendJson(array('error' => $res['error']));
    else sendJson(array('status' => 'ok'));
}

if ($action == 'save_cell') {
    $id = intval($_POST['id']);
    $field = $_POST['field'];
    $value = $_POST['value'];
    $tableName = $_POST['tableName'];
    $gridName = isset($_POST['gridName']) ? $_POST['gridName'] : '';

    $config = null;
    if ($gridName) {
        $resC = db_select('grid_definition', array('grid_name' => $gridName));
        if ($resC['success'] && !empty($resC['data'])) {
            $config = json_decode($resC['data'][0]['config_json'], true);
        }
    }

    $data = array($field => $value);
    $fullRow = db_select($tableName, array('id' => $id));
    if ($fullRow['success'] && !empty($fullRow['data'])) {
        $data = array_merge($fullRow['data'][0], $data); 
    }

    if ($config && isset($config['evt_libraries']) && !empty(trim($config['evt_libraries']))) {
        try { eval(trim($config['evt_libraries'])); } catch(Throwable $e) {}
    }
    if ($config && isset($config['evt_onbeforesave']) && !empty(trim($config['evt_onbeforesave']))) {
        try { eval(trim($config['evt_onbeforesave'])); } catch(Throwable $e) {}
    }

    if (isset($fullRow['data'][0]) && !array_key_exists($field, $fullRow['data'][0])) { $res = array('success' => true); } else { $res = db_update($tableName, array($field => $data[$field]), array('id' => $id)); }
    if (!$res['success']) {
        sendJson(array('error' => $res['error']));
    } else {
        if ($config && isset($config['evt_onaftersave']) && !empty(trim($config['evt_onaftersave']))) {
            try { eval(trim($config['evt_onaftersave'])); } catch(Throwable $e) {}
        }
        sendJson(array('status' => 'ok', 'data' => $data));
    }
}

if ($action == 'upload_file') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $hex = bin2hex(file_get_contents($_FILES['file']['tmp_name']));
        $res = db_query("UPDATE " . $_POST['tableName'] . " SET " . $_POST['field'] . " = 0x$hex WHERE id = " . intval($_POST['id']));
        if (!$res['success']) sendJson(array('error' => $res['error']));
        else sendJson(array('status' => 'uploaded'));
    } else {
        sendJson(array('error' => 'File error'));
    }
}

if ($action == 'load_full_image') {
    $resImg = db_query("SELECT " . $_POST['field'] . " FROM " . $_POST['tableName'] . " WHERE id = " . intval($_POST['id']));
    if ($resImg['success'] && !empty($resImg['data'])) {
        $row_vals = array_values($resImg['data'][0]);
        $blob = $row_vals[0];
        $res = !empty($blob) ? 'data:image/png;base64,' . base64_encode($blob) : null;
        sendJson(array('status' => $res ? 'ok' : 'empty', 'data' => $res));
    } else sendJson(array('error' => 'Not found'));
}

if ($action == 'generate_event_code') {
    $prompt = isset($_REQUEST['prompt']) ? trim($_REQUEST['prompt']) : '';
    $eventName = isset($_REQUEST['eventName']) ? $_REQUEST['eventName'] : '';
    $fieldsJson = isset($_REQUEST['fields']) ? $_REQUEST['fields'] : '[]';
    $keys = isset($_REQUEST['keys']) ? $_REQUEST['keys'] : array();

    if(empty($prompt)) sendJson(array('error' => 'Prompt ist leer.'));
    
    $gemini = isset($keys['gemini']) ? trim($keys['gemini']) : '';
    $chatgpt = isset($keys['chatgpt']) ? trim($keys['chatgpt']) : '';
    $anthropic = isset($keys['anthropic']) ? trim($keys['anthropic']) : '';
    
    $fields = json_decode($fieldsJson, true);
    if (!is_array($fields)) $fields = array();
    
    $fieldListStr = "";
    foreach($fields as $f) {
        $fieldListStr .= "- " . $f['fieldName'] . " (Label: " . (isset($f['label'])?$f['label']:'-') . ")\n";
    }

    if ($eventName === 'DET_SQL_QUERY') {
        $sysPrompt = "Schreibe AUSSCHLIESSLICH reines SQL für eine Datenbankabfrage (Datenquelle) in der App.\n";
        $sysPrompt .= "Es ist äußerst wichtig, dass du exakt nur SQL lieferst. KEIN Markdown (keine Backticks wie ```sql), keine Beschreibungen!\n";
        $sysPrompt .= "WICHTIG: Verwende NIEMALS das Wort 'key' als Alias (z.B. 'as key'), da dies ein geschützter SQL-Ausdruck ist.\n";
        $sysPrompt .= "Die Abfrage muss mindestens zwei Spalten zurückgeben (die erste als interner Wert, die zweite für die textuelle Anzeige).\n";
        $sysPrompt .= "Beispiel: SELECT id, name as label FROM user\n\n";
        $sysPrompt .= "AUFGABE: " . $prompt;
    } else {
        $sysPrompt = "Schreibe AUSSCHLIESSLICH reinen PHP 8 Code für das Event '$eventName' in der Anti-Gravity ERP Low-Code Architektur.\n";
        $sysPrompt .= "Es ist äußerst wichtig, dass du exakt nur Code lieferst. KEIN Markdown (keine Backticks wie ```php), kein <?php, keine Beschreibungen!\n";
        $sysPrompt .= "Beispiel-Rückgabe: \$data['total'] = \$data['menge'] * \$data['preis'];\n\n";
        $sysPrompt .= "Formular-Zustand liegt im assoziativen Array \$data.\n";
        $sysPrompt .= "Vorhandene Felder:\n" . $fieldListStr . "\n\n";
        $sysPrompt .= "AUFGABE: " . $prompt;
    }

    $url = ""; $headers = array(); $data = array();

    if (!empty($gemini)) {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $gemini;
        $headers = array("Content-Type: application/json");
        $data = array("contents" => array(array("parts" => array(array("text" => $sysPrompt)))));
    } else if (!empty($chatgpt)) {
        $url = "https://api.openai.com/v1/chat/completions";
        $headers = array("Authorization: Bearer " . $chatgpt, "Content-Type: application/json");
        $data = array("model" => "gpt-4o", "messages" => array(array("role" => "user", "content" => $sysPrompt)));
    } else if (!empty($anthropic)) {
        $url = "https://api.anthropic.com/v1/messages";
        $headers = array("x-api-key: " . $anthropic, "anthropic-version: 2023-06-01", "Content-Type: application/json");
        $data = array("model" => "claude-3-haiku-20240307", "max_tokens" => 500, "messages" => array(array("role" => "user", "content" => $sysPrompt)));
    } else {
        sendJson(array('error' => 'Kein API Key konfiguriert.'));
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) sendJson(array('error' => 'cURL Fehler: ' . $curlError));
    $res = json_decode($response, true);
    if (isset($res['error']['message'])) sendJson(array('error' => 'API-Fehler: ' . $res['error']['message']));

    $code = "";
    if (!empty($gemini) && isset($res['candidates'][0]['content']['parts'][0]['text'])) {
        $code = $res['candidates'][0]['content']['parts'][0]['text'];
    } else if (!empty($chatgpt) && isset($res['choices'][0]['message']['content'])) {
        $code = $res['choices'][0]['message']['content'];
    } else if (!empty($anthropic) && isset($res['content'][0]['text'])) {
        $code = $res['content'][0]['text'];
    } else {
        sendJson(array('error' => 'Unerwartetes KI-Format.'));
    }

    $code = trim($code);
    $code = preg_replace('/^```(php|sql)\s*/i', '', $code);
    $code = preg_replace('/^```\s*/', '', $code);
    $code = preg_replace('/```$/', '', $code);
    $code = trim($code);
    if (strpos($code, '<?php') === 0) $code = trim(substr($code, 5));

    sendJson(array('status' => 'ok', 'code' => $code));
}

sendJson(array('status' => 'no_action'));
?>



