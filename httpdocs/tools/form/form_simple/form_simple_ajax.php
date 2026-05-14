<?php
/**
* Project: ge_grid_edit
* Author: Gunter Eibl
* Version: 2.12.1 (Fix Gemini API Model Update auf 2.5-flash)
*/

error_reporting(E_ALL);
ini_set('display_errors', 1);
ob_start();
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

// --- HELPER FUNCTIONS ---
function callAI($prompt, $gemini, $chatgpt, $anthropic) {
$sysPrompt = "Du bist ein RegEx-Experte. Antworte AUSSCHLIESSLICH mit dem nackten, rohen RegEx-String für folgende
Anforderung: " . $prompt . ". WICHTIG: Keine Erklärungen, keine Slashes am Anfang/Ende, keine Markdown-Backticks.";
$url = ""; $headers = array(); $data = array();

if (!empty($gemini)) {
// HIER WURDE DAS MODELL AUF gemini-2.5-flash AKTUALISIERT
$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . trim($gemini);
$headers = array("Content-Type: application/json");
$data = array("contents" => array(array("parts" => array(array("text" => $sysPrompt)))));
} else if (!empty($chatgpt)) {
$url = "https://api.openai.com/v1/chat/completions";
$headers = array("Authorization: Bearer " . trim($chatgpt), "Content-Type: application/json");
$data = array("model" => "gpt-4o-mini", "messages" => array(array("role" => "user", "content" => $sysPrompt)));
} else if (!empty($anthropic)) {
$url = "https://api.anthropic.com/v1/messages";
$headers = array("x-api-key: " . trim($anthropic), "anthropic-version: 2023-06-01", "Content-Type: application/json");
$data = array("model" => "claude-3-haiku-20240307", "max_tokens" => 150, "messages" => array(array("role" => "user",
"content" => $sysPrompt)));
} else {
return array('error' => 'Kein API Key konfiguriert. Bitte in den Einstellungen unter "Allgemein" eintragen.');
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

if ($response === false) {
return array('error' => 'Server-Verbindungsfehler (cURL): ' . $curlError);
}

$res = json_decode($response, true);

// Detailliertes Error-Handling (Gibt die exakte Fehlermeldung der API zurück)
if (isset($res['error']['message'])) {
return array('error' => 'API-Fehler: ' . $res['error']['message']);
} else if (isset($res['error']) && is_string($res['error'])) {
return array('error' => 'API-Fehler: ' . $res['error']);
}

$finalRegex = "";

if (!empty($gemini) && isset($res['candidates'][0]['content']['parts'][0]['text'])) {
$finalRegex = $res['candidates'][0]['content']['parts'][0]['text'];
} else if (!empty($chatgpt) && isset($res['choices'][0]['message']['content'])) {
$finalRegex = $res['choices'][0]['message']['content'];
} else if (!empty($anthropic) && isset($res['content'][0]['text'])) {
$finalRegex = $res['content'][0]['text'];
} else {
return array('error' => 'Unerwartetes Antwortformat der KI.', 'raw' => $response);
}

// Bereinigung des KI-Outputs
$finalRegex = trim($finalRegex);
$finalRegex = str_replace(array('```regex', '```'), '', $finalRegex);
$finalRegex = trim($finalRegex);
if(substr($finalRegex, 0, 1) === '/' && substr($finalRegex, -1) === '/') {
$finalRegex = substr($finalRegex, 1, -1);
}

return array('regex' => $finalRegex);
}

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
if(is_array($configFields)) {
foreach ($configFields as $field) {
if (isset($field['lookup']) && !empty($field['lookup'])) {
$options = array();
if (isset($field['lookup']['type']) && $field['lookup']['type'] == 'sql') {
$resL = db_query($field['lookup']['source']);
if ($resL['success'] && !empty($resL['data'])) {
foreach ($resL['data'] as $rowL) {
$key = array_values($rowL)[0];
$val = isset(array_values($rowL)[1]) ? array_values($rowL)[1] : $key;
$options[$key] = $val;
}
}
} else if (isset($field['lookup']['manual'])) {
$options = $field['lookup']['manual'];
}
$lookups[$field['fieldName']] = $options;
}
}
}
return $lookups;
}

function sendJson($data) {
    $sys_debug_log = trim(ob_get_clean());
    if (is_array($data)) $data['sys_debug_log'] = $sys_debug_log;

    // Log errors if present
    if (!empty($sys_debug_log)) {
        crm_log_add(array(
            'app_name'    => 'form_simple_ajax.php',
            'action_type' => 'error',
            'description' => 'Uncaught Output: ' . $sys_debug_log
        ));
    }
    if (isset($data['error']) && !empty($data['error'])) {
        crm_log_add(array(
            'app_name'    => 'form_simple_ajax.php',
            'action_type' => 'error',
            'description' => 'Application Error: ' . $data['error']
        ));
    }

    header('Content-Type: application/json');
    $json = json_encode($data);
    if ($json === false) {
        $utf8ize = function($d) use (&$utf8ize) {
            if (is_array($d)) { foreach ($d as $k => $v) $d[$k] = $utf8ize($v); return $d; }
            if (is_string($d)) return mb_convert_encoding($d, 'UTF-8', 'UTF-8');
            return $d;
        };
        $data = $utf8ize($data);
        $json = json_encode($data);
    }
    echo $json; exit;
}

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$gridName = isset($_REQUEST['gridName']) ? $_REQUEST['gridName'] : '';

if(empty($gridName) && $action != 'upload_file' && $action != 'load_full_image' && $action != 'generate_regex') {
if(isset($_SESSION['form_simple_grid_control_table'])) $gridName = $_SESSION['form_simple_grid_control_table'];
else if(!empty($action)) sendJson(array('error' => 'GridName missing'));
}

// --- ACTIONS ---

if ($action == 'execute_event') {
    $eventName = isset($_REQUEST['eventName']) ? $_REQUEST['eventName'] : '';
    $fieldName = isset($_REQUEST['fieldName']) ? $_REQUEST['fieldName'] : '';
    $formDataJson = isset($_REQUEST['formData']) ? $_REQUEST['formData'] : '{}';
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
                if (isset($f['events']) && isset($f['events'][$eventName])) {
                    $eventCode = trim($f['events'][$eventName]);
                }
                break;
            }
        }
    } else {
        if (isset($config['events']) && isset($config['events'][$eventName])) {
            $eventCode = trim($config['events'][$eventName]);
        }
    }
    
    if (empty($eventCode)) {
        sendJson(array('status' => 'ok', 'data' => $data));
    }
    
    if (isset($config['events']) && !empty(trim($config['events']['libraries']))) {
        try {
            eval(trim($config['events']['libraries']));
        } catch (Throwable $e) {
            sendJson(array('error' => 'Library Error: ' . $e->getMessage()));
        }
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

if ($action == 'generate_regex') {
$prompt = isset($_REQUEST['prompt']) ? trim($_REQUEST['prompt']) : '';
$keys = isset($_REQUEST['keys']) ? $_REQUEST['keys'] : array();

if(empty($prompt)) sendJson(array('error' => 'Prompt ist leer.'));

$geminiKey = isset($keys['gemini']) ? $keys['gemini'] : '';
$chatgptKey = isset($keys['chatgpt']) ? $keys['chatgpt'] : '';
$anthropicKey = isset($keys['anthropic']) ? $keys['anthropic'] : '';

$result = callAI($prompt, $geminiKey, $chatgptKey, $anthropicKey);
sendJson($result);
}

if ($action == 'test_sql') {
    $sql = isset($_REQUEST['sql']) ? trim($_REQUEST['sql']) : '';
    // remove trailing semicolon
    $sql = rtrim($sql, '; ');
    if (empty($sql)) {
        sendJson(array('error' => 'Keine SQL Abfrage definiert.'));
    }
    // append LIMIT if not exists for security
    if (stripos($sql, 'LIMIT ') === false) {
        $sql .= " LIMIT 5";
    }
    $res = db_query($sql);
    if ($res['success']) {
        sendJson(array('status' => 'ok', 'data' => $res['data']));
    } else {
        sendJson(array('error' => $res['error']));
    }
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

if ($action == 'preview_sql_query') {
    $sql = isset($_REQUEST['sql']) ? trim(stripslashes($_REQUEST['sql'])) : '';
    if (empty($sql)) sendJson(array('status' => 'empty'));
    
    // Limits the query to 5 to protect performance in live preview
    $sql = preg_replace('/LIMIT\s+\d+/i', '', $sql);
    $sql .= " LIMIT 5";
    
    $res = db_query($sql);
    if ($res['success'] && !empty($res['data'])) {
        $opts = array();
        foreach ($res['data'] as $row) {
            $key = array_values($row)[0];
            $val = isset(array_values($row)[1]) ? array_values($row)[1] : $key;
            $opts[] = $val;
        }
        sendJson(array('status' => 'ok', 'data' => $opts));
    } else {
        if (!$res['success']) sendJson(array('error' => $res['error']));
        sendJson(array('status' => 'empty'));
    }
}

if ($action == 'load_single') {
$id = intval(isset($_REQUEST['id']) ? $_REQUEST['id'] : 0);
if($id === 0) $id = 1;

$resConf = db_select('grid_definition', array('grid_name' => $gridName));
if (!$resConf['success'] || empty($resConf['data'])) {
$tableName = preg_replace('/_(grid|popup)$/', '', $gridName);
$config = array('tableName'=>$tableName, 'fields'=>array(), 'canvas'=>array('width'=>1000,'height'=>800),
'apiKeys'=>array('gemini'=>'','chatgpt'=>'','anthropic'=>''));
$js = addslashes(json_encode($config));
$jsRaw = json_encode($config);
$resIns = db_insert('grid_definition', array('grid_name' => $gridName, 'config_json' => $jsRaw));
if ($resIns['success']) {
    crm_log_add(array(
        'app_name'    => 'form_simple_ajax.php',
        'action_type' => 'insert',
        'table_name'  => 'grid_definition',
        'record_id'   => $gridName,
        'description' => "Initial-Konfiguration für Grid '$gridName' erstellt"
    ));
}
} else {
$config = json_decode($resConf['data'][0]['config_json'], true);
if(!is_array($config)) $config = array('tableName'=>preg_replace('/_(grid|popup)$/','',$gridName), 'fields'=>array());
}

$tableName = isset($config['tableName']) ? $config['tableName'] : preg_replace('/_(grid|popup)$/','',$gridName);

if (empty($config['fields'])) {
$y = 20;

// 1. Fallback-Stufe: Prüfen, ob wir die Konfiguration aus einer bestehenden Multiline-Ansicht erben können
$multilineGridName = preg_replace('/_(grid|popup)$/', '_multiline', $gridName);
$resMulti = db_select('grid_definition', array('grid_name' => $multilineGridName));

if ($resMulti['success'] && !empty($resMulti['data'])) {
$multiConfig = json_decode($resMulti['data'][0]['config_json'], true);
if (is_array($multiConfig) && !empty($multiConfig['fields'])) {
$nf = array();
foreach ($multiConfig['fields'] as $mf) {
$f = $mf;
$f['tabIndex'] = count($nf) + 1;
// Physische Koordinaten anfügen, falls nicht im Multiline (als Listen-Renderer) vorhanden
$f['form'] = array('lbl'=>array('x'=>20,'y'=>$y+5), 'inp'=>array('x'=>150,'y'=>$y));
array_push($nf, $f);
$y += 45;
}
$config['fields'] = $nf;
$config['canvas']['height'] = $y + 100;
db_update('grid_definition', array('config_json' => json_encode($config)), array('grid_name' => $gridName));
}
}

// 2. Fallback-Stufe: Wenn Multiline nicht existiert (oder leer ist), baue rohes Schema aus der nackten Datenbank
if (empty($config['fields'])) {
$resMeta = db_query("SHOW COLUMNS FROM $tableName");
if ($resMeta['success'] && !empty($resMeta['data'])) {
$nf = array();
foreach ($resMeta['data'] as $rowMeta) {
$c = array_values($rowMeta)[0]; $t = array_values($rowMeta)[1];
$ft = 'text';
if(stripos($t,'int')!==false) $ft='integer';
if(stripos($t,'datetime')!==false || stripos($t,'timestamp')!==false) $ft='date_time';
elseif(stripos($t,'date')!==false) $ft='date';
if(stripos($t,'text')!==false) $ft='multiple_line_text';
if(stripos($t,'blob')!==false) $ft='image';
array_push($nf, array('fieldName'=>$c, 'label'=>ucfirst($c), 'fieldTyp'=>$ft, 'width'=>300, 'readonly'=>($c=='id'),
'tabIndex'=>(count($nf)+1), 'form'=>array('lbl'=>array('x'=>20,'y'=>$y+5), 'inp'=>array('x'=>150,'y'=>$y))));
$y+=45;
}
$config['fields'] = $nf;
$config['canvas']['height'] = $y+100;
db_update('grid_definition', array('config_json' => json_encode($config)), array('grid_name' => $gridName));
}
}
}

$resData = db_select($tableName, array('id' => $id));
$row = array();
if ($resData['success'] && !empty($resData['data'])) {
$row = $resData['data'][0];
} else {
$resMin = db_query("SELECT MIN(id) AS min_id FROM $tableName");
if($resMin['success'] && !empty($resMin['data']) && $resMin['data'][0]['min_id']) sendJson(array('id'=>$resMin['data'][0]['min_id'], 'retry'=>true));
}

    $lookups = getLookupData($config['fields']);

    foreach($config['fields'] as &$f) {
        $fn = $f['fieldName'];
        $isCustom = isset($f['isCustom']) && $f['isCustom'];
        $val = $isCustom ? (isset($f['defaultValue']) ? $f['defaultValue'] : '') : (isset($row[$fn]) ? $row[$fn] : '');

        if (($f['fieldTyp'] == 'image' || $f['fieldTyp'] == 'signature') && !empty($val)) {
            $reqWidth = (isset($f['width']) && intval($f['width']) > 0) ? intval($f['width']) : 100;
            if ($reqWidth > 1200) $reqWidth = 1200;
            $f['value'] = ''; 
            // Bei Custom-Feldern nehmen wir an, der defaultValue ist bereits eine gütige URL oder Data-URI Base64
            $f['preview'] = $isCustom ? $val : makeThumbnail($val, $reqWidth); 
            $f['has_image'] = true;
        } else {
            $f['value'] = $val;
        }
        if(!isset($f['form'])) $f['form'] = array('lbl'=>array('x'=>20,'y'=>20), 'inp'=>array('x'=>150,'y'=>20));
    }

    sendJson(array('id'=>$id, 'config'=>$config, 'lookups'=>$lookups));
    }

    if ($action == 'save_config') {
    $js = addslashes($_REQUEST['config']);
    $res = db_update('grid_definition', array('config_json' => stripslashes($js)), array('grid_name' => $gridName));
    if ($res['success']) {
        crm_log_add(array(
            'app_name'    => 'form_simple_ajax.php',
            'action_type' => 'update',
            'table_name'  => 'grid_definition',
            'record_id'   => $gridName,
            'description' => "Konfiguration für Grid '$gridName' gespeichert"
        ));
    }
    sendJson(array('status'=>'ok'));
    }

    if ($action == 'save_layout') {
    $chg = json_decode($_REQUEST['changes'], true);
    $resDs = db_select('grid_definition', array('grid_name' => $gridName));
    $conf = json_decode($resDs['data'][0]['config_json'], true);
    foreach($chg as $c) {
    foreach($conf['fields'] as &$f) {
    if($f['fieldName'] == $c['fieldName']) {
    $t = ($c['type']=='lbl')?'lbl':'inp';
    $f['form'][$t]['x'] = intval($c['newX']);
    $f['form'][$t]['y'] = intval($c['newY']);

    if(isset($c['w'])) {
    if($c['type'] == 'inp') {
    $f['width'] = intval($c['w']);
    if(isset($c['h'])) $f['height'] = intval($c['h']);
    }
    }
    }
    }
    }
    $js = addslashes(json_encode($conf));
    $res = db_update('grid_definition', array('config_json' => stripslashes($js)), array('grid_name' => $gridName));
    if ($res['success']) {
        crm_log_add(array(
            'app_name'    => 'form_simple_ajax.php',
            'action_type' => 'update',
            'table_name'  => 'grid_definition',
            'record_id'   => $gridName,
            'description' => "Layout für Grid '$gridName' aktualisiert"
        ));
    }
    sendJson(array('status'=>'ok'));
    }

    if ($action == 'upload_file') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $hex = bin2hex(file_get_contents($_FILES['file']['tmp_name']));
    $sql = "UPDATE ".$_REQUEST['tableName']." SET ".$_REQUEST['field']." = 0x$hex WHERE id = ".intval($_REQUEST['id']);
    $res = db_query($sql);
    if ($res['success']) {
        crm_log_add(array(
            'app_name'    => 'form_simple_ajax.php',
            'action_type' => 'update',
            'table_name'  => $_REQUEST['tableName'],
            'record_id'   => $_REQUEST['id'],
            'description' => "Datei/Bild in Feld '".$_REQUEST['field']."' hochgeladen"
        ));
    }
    sendJson(array('status'=>'uploaded'));
    } else sendJson(array('error'=>'File error'));
    }

    if ($action == 'load_full_image') {
    $sql = "SELECT ".$_REQUEST['field']." AS blob_data FROM ".$_REQUEST['tableName']." WHERE id = ".intval($_REQUEST['id']);
    $resImg = db_query($sql);
    if ($resImg['success'] && !empty($resImg['data'])) {
    $blob = $resImg['data'][0]['blob_data'];
    $res = !empty($blob) ? 'data:image/png;base64,'.base64_encode($blob) : null;
    sendJson(array('status'=>($res?'ok':'empty'), 'data'=>$res));
    } else sendJson(array('error'=>'Not found'));
    }

    if ($action == 'navigate') {
        $tbl = $_REQUEST['tableName']; $cur = intval($_REQUEST['currentId']); $dir = $_REQUEST['direction'];
        $sql = "";
        if ($dir == 'next') $sql = "SELECT MIN(id) AS new_id FROM $tbl WHERE id > $cur";
        else if ($dir == 'prev') $sql = "SELECT MAX(id) AS new_id FROM $tbl WHERE id < $cur";
        else if ($dir == 'first') $sql = "SELECT MIN(id) AS new_id FROM $tbl";
        else if ($dir == 'last') $sql = "SELECT MAX(id) AS new_id FROM $tbl";

        if (!empty($sql)) {
            $resNav = db_query($sql);
            $nid = ($resNav['success'] && !empty($resNav['data']) && $resNav['data'][0]['new_id']) ? $resNav['data'][0]['new_id'] : $cur;
            sendJson(array('newId'=>$nid));
        } else {
            sendJson(array('error'=>'Invalid direction'));
        }
    }

        if ($action == 'save_data') {
        $tbl = $_REQUEST['tableName']; $fld = $_REQUEST['field']; $val = $_REQUEST['value']; 
        // Convert T separator from datetime-local to space for MySQL
        if (is_string($val) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}/', $val)) {
            $val = str_replace('T', ' ', $val);
            if (strlen($val) == 16) $val .= ':00'; // Add seconds
        }
        $val = addslashes($val);
        $id = intval($_REQUEST['id']);
        if($tbl && $fld) {
        $oldRes = db_select($tbl, array('id' => $id));
        $oldVal = ($oldRes['success'] && !empty($oldRes['data'])) ? ($oldRes['data'][0][$fld] ?? '') : '';

        $res = db_update($tbl, array($fld => stripslashes($val)), array('id' => $id));
        if ($res['success']) {
            crm_log_add(array(
                'app_name'           => 'form_simple_ajax.php',
                'action_type'        => 'update',
                'table_name'         => $tbl,
                'record_id'          => $id,
                'changed_field_name' => $fld,
                'field_old_value'    => $oldVal,
                'field_new_value'    => stripslashes($val),
                'description'        => "Datensatz ID $id in Tabelle '$tbl' aktualisiert (Feld: $fld)"
            ));
        }
        sendJson(array('status'=>'ok'));
        } else sendJson(array('error'=>'Missing params'));
        }

        sendJson(array('status'=>'no_action'));
?>