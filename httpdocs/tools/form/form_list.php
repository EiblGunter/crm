<?php
session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/tools/db/db.php';

// Umgebungsvariablen laden, falls nicht vorhanden
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

$mysql_config = array(
    'driver'  => 'mysql',
    'host'    => getenv('MYSQL_HOST') ?: '127.0.0.1',
    'port'    => getenv('MYSQL_PORT') ?: '3307',
    'db'      => getenv('MYSQL_DATABASE') ?: 'crm_db',
    'user'    => getenv('MYSQL_USER') ?: 'root',
    'pass'    => getenv('MYSQL_PASSWORD') ?: 'Hotel111',
    'charset' => 'utf8mb4'
);
db_connect($mysql_config, 'default');

// Aktions-Routing
$action = filter_input(INPUT_GET, 'action') ?: filter_input(INPUT_POST, 'action') ?: 'list';

if ($action === 'create') {
    $tableName = filter_input(INPUT_POST, 'table_name');
    $formType = filter_input(INPUT_POST, 'form_type') ?: 'simple';
    if ($tableName) {
        $gridName = $tableName . ($formType === 'multiline' ? '_multiline' : '_grid'); 
        
        // Prüfen ob bereits eine Form mit diesem Namen existiert
        $check = db_select('grid_definition', array('grid_name' => $gridName));
        if (!$check['success'] || empty($check['data'])) {
            // Spalten der Tabelle ermitteln
            $resCols = db_query("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()", array($tableName));
            
            $fieldsArray = array();
            if ($resCols['success'] && !empty($resCols['data'])) {
                $tabIndex = 1; $posY = 25; $inpPosY = 20;
                foreach ($resCols['data'] as $rowCol) {
                    $colName = $rowCol['COLUMN_NAME'];
                    $colType = strtolower($rowCol['DATA_TYPE']);
                    $colLen = $rowCol['CHARACTER_MAXIMUM_LENGTH'];
                    
                    $mappedType = "text";
                    if (strpos($colType, "int") !== false) { $mappedType = "integer"; } 
                    elseif (strpos($colType, "dec") !== false || strpos($colType, "float") !== false || strpos($colType, "double") !== false) { $mappedType = "decimal"; } 
                    elseif (strpos($colType, "date") !== false || strpos($colType, "time") !== false) { $mappedType = "date"; } 
                    elseif (strpos($colType, "text") !== false) { $mappedType = "multiple_line_text"; }
                    
                    $fieldsArray[] = array(
                        "fieldName" => $colName,
                        "label" => ucfirst($colName),
                        "fieldTyp" => $mappedType,
                        "width" => 250,
                        "height" => null,
                        "readonly" => false,
                        "hidden" => false,
                        "tabIndex" => $tabIndex,
                        "form" => array("lbl" => array("x" => 20, "y" => $posY), "inp" => array("x" => 150, "y" => $inpPosY)),
                        "datenbankDefinition" => array("db_name" => $colName, "db_type" => $colType, "db_length" => $colLen)
                    );
                    $posY += 45; $inpPosY += 45; $tabIndex++;
                }
            }
            
            // Standard JSON Konfiguration
            $jsonObj = array("tableName" => $tableName, "canvas" => array("width" => 1000, "height" => 800), "fields" => $fieldsArray);
            
            // Speichern
            db_insert('grid_definition', array('grid_name' => $gridName, 'config_json' => json_encode($jsonObj), 'type_of_form' => $formType));
        }
        
        // Nach Erstellung direkt öffnen
        if ($formType === 'multiline') {
            header("Location: /tools/form/form_multiline/form_multiline.php?grid_name=" . urlencode($gridName));
        } else {
            $_SESSION['form_simple_grid_control_table'] = $gridName;
            header("Location: /tools/form/form_simple/form_simple.php?id=1");
        }
        exit;
    }
}

if ($action === 'open') {
    $gridName = filter_input(INPUT_GET, 'grid_name');
    $type = filter_input(INPUT_GET, 'type') ?: 'simple';
    if ($gridName) {
        if ($type === 'multiline') {
            header("Location: /tools/form/form_multiline/form_multiline.php?grid_name=" . urlencode($gridName));
        } else {
            $_SESSION['form_simple_grid_control_table'] = $gridName;
            header("Location: /tools/form/form_simple/form_simple.php?id=1");
        }
        exit;
    }
}

if ($action === 'delete') {
    $gridName = filter_input(INPUT_GET, 'grid_name');
    if ($gridName) {
        db_query("UPDATE grid_definition SET is_deleted_yn = 1 WHERE grid_name = ?", array($gridName));
        header("Location: form_list.php");
        exit;
    }
}

if ($action === 'update_metadata') {
    header('Content-Type: application/json');
    $oldGridName = filter_input(INPUT_POST, 'old_grid_name');
    $newGridName = filter_input(INPUT_POST, 'new_grid_name');
    $description = filter_input(INPUT_POST, 'description');
    
    if ($oldGridName && $newGridName) {
        // Prevent duplicate names
        if ($oldGridName !== $newGridName) {
            $check = db_select('grid_definition', array('grid_name' => $newGridName));
            if ($check['success'] && !empty($check['data'])) {
                echo json_encode(['success' => false, 'error' => 'Name existiert bereits']);
                exit;
            }
        }
        
        $res = db_query("UPDATE grid_definition SET grid_name = ?, description = ? WHERE grid_name = ?", array($newGridName, $description, $oldGridName));
        
        // Update session if needed, though they are stored in DB.
        
        echo json_encode(['success' => $res['success'], 'error' => $res['error'] ?? '']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Fehlende Parameter']);
    }
    exit;
}

// APIs
if ($action === 'api_table_schema') {
    $tbl = filter_input(INPUT_GET, 'table_name');
    $res = db_query("SELECT COLUMN_NAME as 'Feldname', DATA_TYPE as 'Datentyp', CHARACTER_MAXIMUM_LENGTH as 'Max Länge', IS_NULLABLE as 'Nullable' FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()", array($tbl));
    echo json_encode($res); exit;
}
if ($action === 'api_table_data') {
    $tbl = filter_input(INPUT_GET, 'table_name');
    if (preg_match('/^[a-zA-Z0-9_]+$/', $tbl)) {
        $res = db_query("SELECT * FROM `$tbl` LIMIT 10");
        if ($res['success'] && !empty($res['data'])) {
            foreach ($res['data'] as &$row) {
                foreach ($row as $k => &$v) {
                    if ($v !== null) {
                        // Bei reinen Binärdaten oder kaputter Kodierung: erzwinge UTF-8
                        if (!mb_check_encoding($v, 'UTF-8')) {
                            $v = mb_convert_encoding($v, 'UTF-8', 'ISO-8859-1');
                        }
                        // Zu lange Texte (wie Base64-Unterschriften) vor JSON-Übergabe abschneiden,
                        // um "Verbindungsfehler" (Abbruch des REST-Calls durch Speicherüberlauf) zu verhindern
                        if (mb_strlen($v, 'UTF-8') > 150) {
                            $v = mb_substr($v, 0, 150, 'UTF-8') . '... <span class="text-xs text-slate-400 italic">[abgeschnitten]</span>';
                        }
                    }
                }
            }
        }
        echo json_encode($res);
    } else {
        echo json_encode(['success' => false, 'error' => 'Ungültiger Tabellenname']);
    }
    exit;
}

// -----------------------------------------------------------------------------
// HTML & UI
// -----------------------------------------------------------------------------
require_once $_SERVER['DOCUMENT_ROOT'] . '/tools/design_templates/ag_library.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anti-Gravity Forms Controller</title>
    <!-- Tailwind CSS (via ag_library styles normally, added just in case) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <?php ag_inject_css_variables([]); ?>
    
    <style>
        body { 
            background-color: var(--ag-color-background, #f8fafc) !important; 
            color: var(--ag-color-text-main, #0f172a) !important; 
            transition: all 0.4s ease; 
        }
        .ag-page-card { 
            background-color: var(--ag-color-surface, #ffffff) !important; 
            border: 1px solid var(--ag-color-border, #e2e8f0) !important; 
            border-radius: var(--ag-spacing-border_radius, 1.5rem); 
            transition: all 0.4s ease; 
        }
    </style>
</head>
<body class="min-h-screen">

    <?php ag_render_header('Forms Dashboard', 'MODUL'); ?>

    <main class="max-w-[98%] mx-auto my-8">
        <div class="ag-page-card shadow-2xl p-8 flex flex-col md:flex-row gap-8">
            
            <!-- Linke Spalte: Existierende Forms -->
            <div class="flex-1">
                <h3 class="text-xl font-black mb-6 border-b pb-2 flex items-center gap-2" style="color: var(--ag-color-brand, #3b82f6);">
                    <i class="fas fa-list-alt"></i> Vorhandene Forms
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php
                    $resList = db_query("SELECT grid_name, config_json, type_of_form, description, is_deleted_yn FROM grid_definition WHERE is_deleted_yn = 0 OR is_deleted_yn IS NULL ORDER BY grid_name ASC");
                    if ($resList['success'] && !empty($resList['data'])) {
                        foreach ($resList['data'] as $row) {
                            $gName = htmlspecialchars($row['grid_name']);
                            $conf = json_decode($row['config_json']);
                            $tName = htmlspecialchars($conf->tableName ?? 'Unbekannt');
                            $fCount = isset($conf->fields) ? count($conf->fields) : 0;
                            
                            $typeOfForm = isset($row['type_of_form']) ? strtolower($row['type_of_form']) : 'simple';
                            if (empty($typeOfForm)) $typeOfForm = 'simple';
                            $formLabel = ($typeOfForm === 'multiline') ? 'Form mehrzeilig' : 'Form';
                            
                            $runtimeLink = ($typeOfForm === 'multiline') 
                                ? "/tools/form/form_multiline/form_multiline.php?grid_name={$gName}&mode=run"
                                : "/tools/form/form_simple/form_simple.php?gridName={$gName}&mode=run";
                            
                            $desc = htmlspecialchars($row['description'] ?? '');
                            $descSnippet = (strlen($desc) > 50) ? substr($desc, 0, 47) . '...' : ($desc ?: 'Keine Beschreibung');
                            
                            echo "
                            <div class='block p-4 rounded-xl border hover:shadow-lg transition-all relative group' style='background: var(--ag-color-surface); border-color: var(--ag-color-border);'>
                                <div class='flex justify-between items-start mb-2'>
                                    <h4 class='font-bold text-lg' style='color: var(--ag-color-text-main);'>{$gName}</h4>
                                    <div class='flex items-center gap-2'>
                                        <button class='text-slate-400 hover:text-blue-600 transition-colors p-1' onclick='openMetadataModal(\"{$gName}\", " . json_encode($desc) . ")' title='Metadaten bearbeiten'><i class='fas fa-edit text-sm'></i></button>
                                        <span class='bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded'>{$formLabel}</span>
                                        <button class='text-red-400 hover:text-red-600 transition-colors p-1' onclick='if(confirm(\"Möchten Sie die Definition {$gName} wirklich löschen?\")) { window.location.href=\"?action=delete&grid_name={$gName}\"; }' title='Definition löschen'><i class='fas fa-trash text-sm'></i></button>
                                    </div>
                                </div>
                                <p class='text-sm mb-1' style='color: var(--ag-color-text-main); opacity: 0.8;'>
                                    <i class='fas fa-database text-xs mr-1'></i> Tabelle: <strong>{$tName}</strong>
                                </p>
                                <p class='text-xs mb-1' style='color: var(--ag-color-text-main); opacity: 0.6;'>
                                    {$fCount} Felder konfiguriert
                                </p>
                                <p class='text-xs mb-3 italic' style='color: var(--ag-color-text-main); opacity: 0.6;' title='{$desc}'>
                                    {$descSnippet}
                                </p>
                                <div class='flex gap-2 pt-2 border-t' style='border-color: var(--ag-color-border);'>
                                    <a href='?action=open&grid_name={$gName}&type={$typeOfForm}' class='flex-1 text-center py-2 px-3 bg-blue-50 text-blue-600 hover:bg-blue-100 hover:text-blue-800 rounded-md transition-colors text-sm font-medium'>
                                        <i class='fas fa-pencil-alt mr-1'></i> Design
                                    </a>
                                    <a href='{$runtimeLink}' target='_blank' class='flex-1 text-center py-2 px-3 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:text-emerald-800 rounded-md transition-colors text-sm font-medium'>
                                        <i class='fas fa-play mr-1'></i> Runtime (Test)
                                    </a>
                                </div>
                            </div>";
                        }
                    } else {
                        echo "<p class='text-sm italic' style='color: var(--ag-color-text-main); opacity: 0.7;'>Es wurden noch keine Forms angelegt.</p>";
                    }
                    ?>
                </div>
            </div>
            
            <!-- Rechte Spalte: Neue Forms anlegen -->
            <div class="w-full md:w-1/3 bg-slate-50 p-6 rounded-2xl border" style="background-color: var(--ag-color-toolbar_bg); border-color: var(--ag-color-border);">
                <h3 class="text-xl font-black mb-6 border-b pb-2 flex items-center gap-2" style="color: var(--ag-color-brand, #3b82f6);">
                    <i class="fas fa-magic"></i> Neue Form generieren
                </h3>
                
                <p class="text-sm mb-4" style="color: var(--ag-color-text-main); opacity: 0.8;">
                    Wählen Sie eine Datenbank-Tabelle aus. Das System generiert automatisch eine passende Eingabemaske inklusive UI-Definition.
                </p>
                
                <div class="flex flex-col gap-2 max-h-[600px] overflow-y-auto pr-2 custom-scrollbar">
                    <?php
                    $resTbls = db_query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE IN ('BASE TABLE', 'VIEW') AND TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME ASC");
                    if ($resTbls['success'] && !empty($resTbls['data'])) {
                        foreach ($resTbls['data'] as $rowTbl) {
                            $tName = htmlspecialchars($rowTbl['TABLE_NAME']);
                            echo "
                            <div class='flex items-center w-full gap-2 mb-2 p-2 border rounded-lg hover:shadow-sm transition-all' style='background: var(--ag-color-surface); border-color: var(--ag-color-border); color: var(--ag-color-text-main);'>
                                <div class='flex-1 font-medium text-sm truncate' title='{$tName}'><i class='fas fa-table text-slate-400 mr-2'></i> {$tName}</div>
                                <form method='post' action='' class='m-0 p-0 flex gap-1 items-center'>
                                    <input type='hidden' name='action' value='create'>
                                    <input type='hidden' name='table_name' value='{$tName}'>
                                    <button type='submit' name='form_type' value='simple' class='px-3 py-1.5 text-xs font-semibold border rounded hover:bg-blue-50 hover:text-blue-600 transition-colors' title='Detail-Maske (Simple) generieren'><i class='fas fa-square mr-1'></i> Simple</button>
                                    <button type='submit' name='form_type' value='multiline' class='px-3 py-1.5 text-xs font-semibold border rounded hover:bg-emerald-50 hover:text-emerald-600 transition-colors' title='Listen-View (Multiline) generieren'><i class='fas fa-list mr-1'></i> Multiline</button>
                                </form>
                                <button type='button' onclick='showTablePreview(\"{$tName}\", \"schema\")' class='p-1.5 text-slate-400 hover:text-blue-500 transition-colors flex-shrink-0' title='Struktur (Felder, Typen)'><i class='fas fa-info-circle'></i></button>
                                <button type='button' onclick='showTablePreview(\"{$tName}\", \"data\")' class='p-1.5 text-slate-400 hover:text-green-500 transition-colors flex-shrink-0' title='Inhalte (Top 10 Datensätze)'><i class='fas fa-eye'></i></button>
                            </div>";
                        }
                    }
                    ?>
                </div>
            </div>

        </div>
    </main>

    <?php ag_render_footer(); ?>
    
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.2); }
    </style>

    <!-- Modal for Previews -->
    <div id="previewModal" class="fixed inset-0 bg-black bg-opacity-70 hidden z-50 flex items-center justify-center backdrop-blur-sm">
        <div class="rounded-2xl shadow-2xl p-6 w-11/12 max-w-5xl max-h-[85vh] flex flex-col" style="background-color: #ffffff !important; opacity: 1 !important; border: 1px solid var(--ag-color-border);">
            <div class="flex justify-between items-center mb-4 border-b pb-3" style="border-color: var(--ag-color-border);">
                <h3 class="text-xl font-bold flex items-center gap-3" id="previewTitle" style="color: var(--ag-color-text-main);">Vorschau</h3>
                <button onclick="document.getElementById('previewModal').classList.add('hidden')" class="text-slate-400 hover:text-red-500 transition-colors"><i class="fas fa-times text-xl"></i></button>
            </div>
            <div class="overflow-auto flex-1 custom-scrollbar rounded-lg" id="previewContent" style="color: var(--ag-color-text-main);">
                <div class="text-center py-8"><i class="fas fa-spinner fa-spin text-3xl text-blue-500"></i></div>
            </div>
        </div>
    </div>

    <!-- Metadata Edit Modal -->
    <div id="metadataModal" class="fixed inset-0 bg-black bg-opacity-70 hidden z-50 flex items-center justify-center backdrop-blur-sm">
        <div class="rounded-2xl shadow-2xl p-6 w-full max-w-lg flex flex-col bg-white" style="background-color: var(--ag-color-surface, #ffffff) !important; border: 1px solid var(--ag-color-border, #ccc);">
            <div class="flex justify-between items-center mb-4 border-b pb-3" style="border-color: var(--ag-color-border);">
                <h3 class="text-xl font-bold flex items-center gap-3" style="color: var(--ag-color-text-main);">Eigenschaften bearbeiten</h3>
                <button onclick="document.getElementById('metadataModal').classList.add('hidden')" class="text-slate-400 hover:text-red-500 transition-colors"><i class="fas fa-times text-lg"></i></button>
            </div>
            
            <div class="flex flex-col gap-4">
                <input type="hidden" id="editOldGridName">
                
                <div>
                    <label class="block text-sm font-semibold mb-1" style="color: var(--ag-color-text-main);">Name (Grid Name)</label>
                    <input type="text" id="editGridName" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white" style="background: var(--ag-color-background, #ffffff); border-color: var(--ag-color-border, #ccc); color: var(--ag-color-text-main, #000);">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold mb-1" style="color: var(--ag-color-text-main);">Beschreibung</label>
                    <textarea id="editDescription" rows="4" class="w-full border rounded-lg p-2 focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white" style="background: var(--ag-color-background, #ffffff); border-color: var(--ag-color-border, #ccc); color: var(--ag-color-text-main, #000); resize: vertical;"></textarea>
                </div>
                
                <div class="flex justify-end gap-2 mt-4">
                    <button onclick="document.getElementById('metadataModal').classList.add('hidden')" class="px-4 py-2 border rounded-lg hover:bg-slate-50 transition-colors" style="border-color: var(--ag-color-border); color: var(--ag-color-text-main);">Abbrechen</button>
                    <button onclick="saveMetadata()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"><i class="fas fa-save mr-1"></i> Speichern</button>
                </div>
            </div>
        </div>
    </div>

    <script>
    function showTablePreview(tableName, mode) {
        document.getElementById('previewModal').classList.remove('hidden');
        document.getElementById('previewTitle').innerHTML = (mode === 'schema' ? '<i class=\"fas fa-info-circle text-blue-500\"></i> Struktur: ' : '<i class=\"fas fa-eye text-green-500\"></i> Inhalte (Top 10): ') + '<strong>' + tableName + '</strong>';
        document.getElementById('previewContent').innerHTML = '<div class=\"text-center py-12\"><i class=\"fas fa-spinner fa-spin text-4xl text-blue-500 mb-4\"></i><br><span class=\"opacity-70\">Lade Daten aus der Datenbank...</span></div>';
        
        fetch('?action=' + (mode === 'schema' ? 'api_table_schema' : 'api_table_data') + '&table_name=' + encodeURIComponent(tableName))
        .then(r => r.json())
        .then(res => {
            if (!res.success || !res.data) {
                document.getElementById('previewContent').innerHTML = '<div class=\"text-red-500 p-6 bg-red-50 rounded-lg text-center\"><i class=\"fas fa-exclamation-triangle text-2xl mb-2\"></i><br>Fehler beim Laden der Daten.<br><small>' + (res.error || '') + '</small></div>';
                return;
            }
            if (res.data.length === 0) {
                document.getElementById('previewContent').innerHTML = '<div class=\"p-8 text-center opacity-60\"><i class=\"fas fa-box-open text-4xl mb-3\"></i><br>Die Tabelle hat keine Einträge.</div>';
                return;
            }
            
            let html = '<table class=\"w-full text-sm text-left whitespace-nowrap\"><thead class=\"text-xs uppercase sticky top-0 bg-slate-100 shadow-sm z-10\" style=\"background: var(--ag-color-toolbar_bg);\"><tr>';
            let headers = Object.keys(res.data[0]);
            headers.forEach(h => html += '<th class=\"px-4 py-3 font-bold opacity-80 border-b\" style=\"border-color: var(--ag-color-border);\">' + h + '</th>');
            html += '</tr></thead><tbody>';
            
            res.data.forEach(row => {
                html += '<tr class=\"border-b hover:bg-slate-50 transition-colors\" style=\"border-color: var(--ag-color-border);\">';
                headers.forEach(h => {
                    let val = row[h];
                    if (val === null) val = '<span class=\"text-red-400 italic text-xs\">NULL</span>';
                    else if (val.length > 80) val = val.substring(0, 80) + '...';
                    html += '<td class=\"px-4 py-3 opacity-90\">' + val + '</td>';
                });
                html += '</tr>';
            });
            html += '</tbody></table>';
            document.getElementById('previewContent').innerHTML = html;
        })
        .catch(err => {
            document.getElementById('previewContent').innerHTML = '<div class=\"text-red-500 p-6 bg-red-50 rounded-lg text-center\"><i class=\"fas fa-wifi text-2xl mb-2\"></i><br>Verbindungsfehler.</div>';
        });
    }
    
    function openMetadataModal(gridName, description) {
        document.getElementById('editOldGridName').value = gridName;
        document.getElementById('editGridName').value = gridName;
        document.getElementById('editDescription').value = description || '';
        document.getElementById('metadataModal').classList.remove('hidden');
    }
    
    function saveMetadata() {
        const oldGridName = document.getElementById('editOldGridName').value;
        const newGridName = document.getElementById('editGridName').value.trim();
        const description = document.getElementById('editDescription').value;
        
        if (!newGridName) {
            alert('Bitte geben Sie einen Namen ein.');
            return;
        }
        
        const fd = new FormData();
        fd.append('action', 'update_metadata');
        fd.append('old_grid_name', oldGridName);
        fd.append('new_grid_name', newGridName);
        fd.append('description', description);
        
        // Show loading state
        document.body.style.cursor = 'wait';
        
        fetch('form_list.php', {
            method: 'POST',
            body: fd
        })
        .then(r => r.json())
        .then(res => {
            document.body.style.cursor = '';
            if (res.success) {
                window.location.reload();
            } else {
                alert('Fehler beim Speichern: ' + (res.error || 'Unbekannter Fehler'));
            }
        })
        .catch(err => {
            document.body.style.cursor = '';
            alert('Verbindungsfehler beim Speichern.');
            console.error(err);
        });
    }
    
    // Close modal on Escape
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            document.getElementById('previewModal').classList.add('hidden');
        }
    });
    </script>
</body>
</html>
