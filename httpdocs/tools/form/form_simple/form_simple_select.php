<?php
session_start();
ob_start();
// 1. Initialisierung und Action-Routing
$action = filter_input(INPUT_POST, 'action');
if (empty($action)) {
    $action = filter_input(INPUT_GET, 'action');
}
if (empty($action)) {
    $action = 'list';
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

$pageTitle = "Definitionen Übersicht";
if ($action == 'new') {
    $pageTitle = "Neue Definition anlegen";
}

// 2. Routing: Eine bestehende Definition in form_view.php laden
if ($action == 'load') {
    $loadGrid = filter_input(INPUT_GET, 'grid_name');
    if (!empty($loadGrid)) {
        $_SESSION['grid_control_table'] = $loadGrid;
        header("Location: form_simple.php?id=1");
        exit;
    }
}

// 3. Routing: Name einer Definition ändern
if ($action == 'rename') {
    $oldName = filter_input(INPUT_POST, 'old_grid_name');
    $newName = filter_input(INPUT_POST, 'new_grid_name');
    
    if (!empty($oldName) && !empty($newName)) {
        $res = db_update('grid_definition', array('grid_name' => $newName), array('grid_name' => $oldName));
        if ($res['success']) {
            crm_log_add(array(
                'app_name'    => 'form_simple_select.php',
                'action_type' => 'update',
                'table_name'  => 'grid_definition',
                'record_id'   => $newName,
                'description' => "Definition von '$oldName' nach '$newName' umbenannt"
            ));
        }
    }
    // FEHLERBEHEBUNG: Kein Redirect zur form_view, sondern zurück zur Liste in dieser App!
    $action = 'list';
}

// 4. Speicher-Logik: Neues JSON anlegen inkl. datenbankDefinition
if ($action == 'save') {
    $newTable = filter_input(INPUT_POST, 'def_table');
    $gridName = filter_input(INPUT_POST, 'def_name');

    $resCols = db_query("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()", array($newTable));
    
    $fieldsArray = array();
    
    if ($resCols['success'] && !empty($resCols['data'])) {
        $tabIndex = 1;
        $posY = 25;
        $inpPosY = 20;
        
        foreach ($resCols['data'] as $rowCol) {
            $colName = $rowCol['COLUMN_NAME'];
            $colType = strtolower($rowCol['DATA_TYPE']);
            $colLen = $rowCol['CHARACTER_MAXIMUM_LENGTH'];
            
            $mappedType = "text";
            if (strpos($colType, "int") !== false) { $mappedType = "integer"; } 
            elseif (strpos($colType, "dec") !== false || strpos($colType, "float") !== false || strpos($colType, "double") !== false) { $mappedType = "decimal"; } 
            elseif (strpos($colType, "datetime") !== false || strpos($colType, "timestamp") !== false) { $mappedType = "date_time"; } 
            elseif (strpos($colType, "date") !== false || strpos($colType, "time") !== false) { $mappedType = "date"; } 
            elseif (strpos($colType, "text") !== false) { $mappedType = "multiple_line_text"; }
            
            $dbDef = array(
                "db_name" => $colName,
                "db_type" => $colType,
                "db_length" => $colLen
            );
            
            $fieldDef = array(
                "fieldName" => $colName,
                "label" => ucfirst($colName),
                "fieldTyp" => $mappedType,
                "width" => 250,
                "height" => null,
                "readonly" => false,
                "hidden" => false,
                "tabIndex" => $tabIndex,
                "form" => array("lbl" => array("x" => 20, "y" => $posY), "inp" => array("x" => 150, "y" => $inpPosY)),
                "datenbankDefinition" => $dbDef
            );
            
            array_push($fieldsArray, $fieldDef);
            $posY = $posY + 45; $inpPosY = $inpPosY + 45; $tabIndex = $tabIndex + 1;
        }
    }

    $jsonObj = array("tableName" => $newTable, "canvas" => array("width" => 1000, "height" => 800), "apiKeys" => array("gemini" => "", "chatgpt" => "", "anthropic" => ""), "fields" => $fieldsArray);
    $jsonString = json_encode($jsonObj);

    $resIns = db_insert('grid_definition', array('grid_name' => $gridName, 'config_json' => $jsonString));
    
    if ($resIns['success']) {
        crm_log_add(array(
            'app_name'    => 'form_simple_select.php',
            'action_type' => 'insert',
            'table_name'  => 'grid_definition',
            'record_id'   => $gridName,
            'description' => "Neue Simple-Grid Definition '$gridName' für Tabelle '$newTable' erstellt"
        ));
        // HIER ist der einzige Ort, wo form_view aufgerufen werden soll (nach Neuanlage)
        $_SESSION['grid_control_table'] = $gridName;
        header("Location: form_simple.php?id=1");
        exit;
    }
}

// 5. Update-Logik: Sync in JSON übernehmen inkl. datenbankDefinition
if ($action == 'sync') {
    $syncGrid = filter_input(INPUT_POST, 'sync_grid_name');
    $missingStr = filter_input(INPUT_POST, 'sync_missing');
    $changedStr = filter_input(INPUT_POST, 'sync_changed');
    $newStr = filter_input(INPUT_POST, 'sync_new');

    $missingArr = explode(',', $missingStr);
    $changedArr = explode(',', $changedStr);
    $newArr = explode(',', $newStr);

    $resSync = db_select('grid_definition', array('grid_name' => $syncGrid));
    
    if ($resSync['success'] && !empty($resSync['data'])) {
        $currentJson = $resSync['data'][0]['config_json'];
        $configObj = json_decode($currentJson);
        $tableName = $configObj->tableName;
        
        $newFieldsArray = array();
        $maxTabIndex = 0;
        $maxY = 25;
        
        foreach ($configObj->fields as $fObj) {
            $fName = $fObj->fieldName;
            if ($fObj->tabIndex > $maxTabIndex) { $maxTabIndex = $fObj->tabIndex; }
            if ($fObj->form->lbl->y > $maxY) { $maxY = $fObj->form->lbl->y; }
            
            if (in_array($fName, $missingArr)) { continue; }
            
            if (in_array($fName, $changedArr)) {
                $resCt = db_query("SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ? AND TABLE_SCHEMA = DATABASE()", array($tableName, $fName));
                if ($resCt['success'] && !empty($resCt['data'])) {
                    $cType = strtolower($resCt['data'][0]['DATA_TYPE']);
                    $cLen = $resCt['data'][0]['CHARACTER_MAXIMUM_LENGTH'];
                    
                    $dbDef = new stdClass();
                    $dbDef->db_name = $fName;
                    $dbDef->db_type = $cType;
                    $dbDef->db_length = $cLen;
                    $fObj->datenbankDefinition = $dbDef;
                }
            }
            array_push($newFieldsArray, $fObj);
        }
        
        foreach ($newArr as $newFName) {
            if (empty($newFName)) { continue; }
            $resNt = db_query("SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND COLUMN_NAME = ? AND TABLE_SCHEMA = DATABASE()", array($tableName, $newFName));
            if ($resNt['success'] && !empty($resNt['data'])) {
                $cType = strtolower($resNt['data'][0]['DATA_TYPE']);
                $cLen = $resNt['data'][0]['CHARACTER_MAXIMUM_LENGTH'];
                
                $mappedType = "text";
                if (strpos($cType, "int") !== false) { $mappedType = "integer"; } 
                elseif (strpos($cType, "dec") !== false || strpos($cType, "float") !== false) { $mappedType = "decimal"; } 
                elseif (strpos($cType, "datetime") !== false || strpos($cType, "timestamp") !== false) { $mappedType = "date_time"; } 
                elseif (strpos($cType, "date") !== false || strpos($cType, "time") !== false) { $mappedType = "date"; } 
                elseif (strpos($cType, "text") !== false) { $mappedType = "multiple_line_text"; }
                
                $maxTabIndex = $maxTabIndex + 1;
                $maxY = $maxY + 45;
                
                $dbDef = array("db_name" => $newFName, "db_type" => $cType, "db_length" => $cLen);
                
                $fieldDef = array(
                    "fieldName" => $newFName, "label" => ucfirst($newFName), "fieldTyp" => $mappedType,
                    "width" => 250, "height" => null, "readonly" => false, "hidden" => false, "tabIndex" => $maxTabIndex,
                    "form" => array("lbl" => array("x" => 20, "y" => $maxY), "inp" => array("x" => 150, "y" => ($maxY - 5))),
                    "datenbankDefinition" => $dbDef
                );
                array_push($newFieldsArray, $fieldDef);
            }
        }
        
        $configObj->fields = $newFieldsArray;
        $updatedJsonStr = json_encode($configObj);
        $resUpd = db_update('grid_definition', array('config_json' => $updatedJsonStr), array('grid_name' => $syncGrid));
        if ($resUpd['success']) {
            crm_log_add(array(
                'app_name'    => 'form_simple_select.php',
                'action_type' => 'update',
                'table_name'  => 'grid_definition',
                'record_id'   => $syncGrid,
                'description' => "Struktur-Synchronisation für '$syncGrid' durchgeführt"
            ));
        }
    }
    
    // FEHLERBEHEBUNG: Kein Redirect, sondern Aktion wieder auf list setzen!
    $action = 'list';
}

// 6. HTML-Ausgabe
$sys_debug_log = trim(ob_get_clean());
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/tools/design_templates/ag_library.php';
    ag_inject_css_variables([]);
    ?>
    <style>
        body { background-color: var(--ag-color-background, #f8fafc) !important; color: var(--ag-color-text-main, #0f172a) !important; transition: all 0.4s ease; }
        .ag-page-card { background-color: var(--ag-color-surface, #ffffff) !important; border-color: var(--ag-color-border, #e2e8f0) !important; border-radius: var(--ag-spacing-border_radius, 1.5rem) !important; transition: all 0.4s ease; }
        #ag-header { background-color: var(--ag-color-toolbar_bg, #0d6efd) !important; border-bottom: 1px solid var(--ag-color-border, #e2e8f0); color: var(--ag-color-text-main, #fff); }
    </style>
</head>
<body class="bg-light w-100 min-h-screen">

    <main class="max-w-[98%] mx-auto my-8">
        <?php if (!empty($sys_debug_log)): ?>
            <div class="mb-4">
                <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 shadow-sm rounded-r">
                    <h3 class="font-bold text-sm mb-2">System Debug / Uncaught Output:</h3>
                    <pre class="text-xs overflow-auto whitespace-pre-wrap"><?= htmlspecialchars($sys_debug_log) ?></pre>
                </div>
            </div>
        <?php endif; ?>
        <div id="ag-header" class="d-flex justify-content-between align-items-center p-3 rounded-top shadow-sm">
            <h2 class="m-0 fs-4" style="color: inherit;"><?php echo $pageTitle; ?></h2>
            <div class="header-actions flex items-center gap-4 d-flex">
                <?php if ($action == 'list'): ?>
                    <a href="?action=new" class="btn btn-light text-primary fw-bold">Neu anlegen</a>
                <?php endif; ?>
            </div>
        </div>

        </div>

        <div class="ag-page-card bg-white border border-top-0 p-4 shadow-2xl">
            
            <?php if ($action == 'list'): ?>
                <p class="lead">Wähle eine bestehende Grid-Definition aus oder erstelle eine neue.</p>
                <div class="table-responsive">
                    <table class="table table-striped table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Grid Name</th>
                                <th>Verbundene Tabelle & Status</th>
                                <th class="text-end">Aktion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $resList = db_query("SELECT grid_name, config_json FROM grid_definition ORDER BY grid_name ASC");
                            
                            if (!$resList['success']) {
                                echo "<tr><td colspan='3' class='text-danger'>Fehler beim Laden der Definitionen.</td></tr>";
                            } else {
                                foreach ($resList['data'] as $rowList) {
                                    $rowGrid = $rowList['grid_name'];
                                    $rowJsonStr = $rowList['config_json'];
                                    $confObj = json_decode($rowJsonStr);
                                    $tName = $confObj->tableName;
                                    
                                    $dbColsMap = new stdClass();
                                    $resC = db_query("SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = DATABASE()", array($tName));
                                    if ($resC['success'] && !empty($resC['data'])) {
                                        foreach ($resC['data'] as $rowC) {
                                            $cName = $rowC['COLUMN_NAME'];
                                            $cInfo = new stdClass();
                                            $cInfo->type = strtolower($rowC['DATA_TYPE']);
                                            $cInfo->len = $rowC['CHARACTER_MAXIMUM_LENGTH']; 
                                            $dbColsMap->$cName = $cInfo;
                                        }
                                    }

                                    $missingArr = array();
                                    $changedArr = array();
                                    $newArr = array();
                                    $jsonColsMap = new stdClass();

                                    if (property_exists($confObj, 'fields') && is_array($confObj->fields)) {
                                        foreach ($confObj->fields as $f) {
                                            $fN = $f->fieldName;
                                            $jsonColsMap->$fN = true;

                                            if (!property_exists($dbColsMap, $fN)) {
                                                array_push($missingArr, $fN);
                                            } else {
                                                $dbInfo = $dbColsMap->$fN;
                                                
                                                if (!property_exists($f, 'datenbankDefinition')) {
                                                    array_push($changedArr, $fN);
                                                } else {
                                                    $savedType = $f->datenbankDefinition->db_type;
                                                    $savedLen = $f->datenbankDefinition->db_length;
                                                    if ($savedType != $dbInfo->type || $savedLen != $dbInfo->len) {
                                                        array_push($changedArr, $fN);
                                                    }
                                                }
                                            }
                                        }
                                    }

                                    foreach ($dbColsMap as $dbN => $dbInfo) {
                                        if (!property_exists($jsonColsMap, $dbN)) {
                                            array_push($newArr, $dbN);
                                        }
                                    }

                                    $diffCount = count($missingArr) + count($changedArr) + count($newArr);
                                    $link = "?action=load&grid_name=" . $rowGrid;
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo $rowGrid; ?></strong>
                                            <button class="btn btn-sm text-secondary ms-1 p-0 border-0" onclick="openRename('<?php echo $rowGrid; ?>')" title="Umbenennen">✏️</button>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span><?php echo $tName; ?></span>
                                                <?php if ($diffCount > 0): ?>
                                                    <div class="ms-3 d-flex flex-column" style="font-size: 0.65rem; line-height: 1.1;">
                                                        <?php if (count($missingArr) > 0): ?>
                                                            <div class="bg-danger text-white px-1 rounded mb-1 text-center" title="Anzahl fehlender Felder in Tabelle"><?php echo count($missingArr); ?></div>
                                                        <?php endif; ?>
                                                        <?php if (count($changedArr) > 0): ?>
                                                            <div class="bg-warning text-dark px-1 rounded mb-1 text-center" title="Anzahl geänderter Felder in Tabelle"><?php echo count($changedArr); ?></div>
                                                        <?php endif; ?>
                                                        <?php if (count($newArr) > 0): ?>
                                                            <div class="bg-success text-white px-1 rounded text-center" title="Anzahl neuer Felder in Tabelle"><?php echo count($newArr); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary ms-2 p-1" title="JSON anpassen" onclick="openSync('<?php echo $rowGrid; ?>')">⚙️</button>
                                                    
                                                    <div id="sync_data_<?php echo $rowGrid; ?>" class="d-none">
                                                        <?php foreach($missingArr as $m): ?>
                                                            <div class="form-check"><input class="form-check-input sync-chk-m sync-chk-item" type="checkbox" value="<?php echo $m; ?>" checked><label class="form-check-label text-danger">Löschen: <?php echo $m; ?></label></div>
                                                        <?php endforeach; ?>
                                                        <?php foreach($changedArr as $c): ?>
                                                            <div class="form-check"><input class="form-check-input sync-chk-c sync-chk-item" type="checkbox" value="<?php echo $c; ?>" checked><label class="form-check-label text-warning">DB-Metadaten updaten: <?php echo $c; ?></label></div>
                                                        <?php endforeach; ?>
                                                        <?php foreach($newArr as $n): ?>
                                                            <div class="form-check"><input class="form-check-input sync-chk-n sync-chk-item" type="checkbox" value="<?php echo $n; ?>" checked><label class="form-check-label text-success">Neu anlegen: <?php echo $n; ?></label></div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td class="text-end"><a href="<?php echo $link; ?>" class="btn btn-sm btn-outline-primary">Öffnen</a></td>
                                    </tr>
                                    <?php
                                }
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="modal fade" id="renameModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post" action="">
                                <input type="hidden" name="action" value="rename">
                                <input type="hidden" name="old_grid_name" id="old_grid_name" value="">
                                <div class="modal-header bg-primary text-white">
                                    <h5 class="modal-title">Definition umbenennen</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <label class="form-label">Neuer Name für die Definition</label>
                                    <input type="text" class="form-control" name="new_grid_name" id="new_grid_name" required>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                                    <button type="submit" class="btn btn-success">Speichern</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="modal fade" id="syncModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post" action="" id="formSync" onsubmit="prepareSyncData()">
                                <input type="hidden" name="action" value="sync">
                                <input type="hidden" name="sync_grid_name" id="sync_grid_name" value="">
                                <input type="hidden" name="sync_missing" id="val_sync_m" value="">
                                <input type="hidden" name="sync_changed" id="val_sync_c" value="">
                                <input type="hidden" name="sync_new" id="val_sync_n" value="">
                                
                                <div class="modal-header bg-warning">
                                    <h5 class="modal-title text-dark">Sicherheitsabfrage: JSON anpassen</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p>Strukturabweichungen zur Datenbank gefunden. Wählen Sie die Felder, die im JSON aktualisiert werden sollen:</p>
                                    
                                    <div class="form-check mb-3 pb-2 border-bottom">
                                        <input class="form-check-input" type="checkbox" id="selectAllSync" onchange="toggleAllSync(this)" checked>
                                        <label class="form-check-label fw-bold" for="selectAllSync">Alle auswählen / abwählen</label>
                                    </div>

                                    <div id="syncModalContent" class="bg-light p-3 border rounded">
                                        </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Abbrechen</button>
                                    <button type="submit" class="btn btn-success">Ausgewählte anpassen</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            <?php elseif ($action == 'new'): ?>
                <form method="post" action="" id="newDefForm">
                    <input type="hidden" name="action" value="save">
                    
                    <div class="mb-3">
                        <label for="def_name" class="form-label">Name der neuen Definition (z.B. kunden_grid)</label>
                        <input type="text" class="form-control" id="def_name" name="def_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="def_table" class="form-label">Basis-Tabelle / View anbinden</label>
                        <select class="form-select" id="def_table" name="def_table" required>
                            <option value="">-- Bitte wählen --</option>
                            <?php
                            $resTbls = db_query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE IN ('BASE TABLE', 'VIEW') AND TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME ASC");
                            if ($resTbls['success'] && !empty($resTbls['data'])) {
                                foreach ($resTbls['data'] as $rowTbl) {
                                    $tName = $rowTbl['TABLE_NAME'];
                                    echo "<option value='" . $tName . "'>" . $tName . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </form>
            <?php endif; ?>
            
        </div>

        </div>

        <div class="d-flex justify-content-end gap-2 bg-light p-3 border border-top-0 rounded-bottom shadow-sm">
            <?php if ($action == 'new'): ?>
                <a href="?action=list" class="btn btn-secondary">Zurück</a>
                <button type="submit" form="newDefForm" class="btn btn-success">Speichern</button>
            <?php endif; ?>
        </div>

    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openRename(oldName) {
            document.getElementById('old_grid_name').value = oldName;
            document.getElementById('new_grid_name').value = oldName;
            let modal = new bootstrap.Modal(document.getElementById('renameModal'));
            modal.show();
        }

        function openSync(gridName) {
            document.getElementById('sync_grid_name').value = gridName;
            let hiddenData = document.getElementById('sync_data_' + gridName).innerHTML;
            document.getElementById('syncModalContent').innerHTML = hiddenData;
            
            document.getElementById('selectAllSync').checked = true;
            
            let modal = new bootstrap.Modal(document.getElementById('syncModal'));
            modal.show();
        }

        function toggleAllSync(source) {
            let checkboxes = document.querySelectorAll('#syncModalContent .sync-chk-item');
            checkboxes.forEach(cb => {
                cb.checked = source.checked;
            });
        }

        function prepareSyncData() {
            let mVals = []; document.querySelectorAll('#syncModalContent .sync-chk-m:checked').forEach(el => mVals.push(el.value));
            let cVals = []; document.querySelectorAll('#syncModalContent .sync-chk-c:checked').forEach(el => cVals.push(el.value));
            let nVals = []; document.querySelectorAll('#syncModalContent .sync-chk-n:checked').forEach(el => nVals.push(el.value));
            
            document.getElementById('val_sync_m').value = mVals.join(',');
            document.getElementById('val_sync_c').value = cVals.join(',');
            document.getElementById('val_sync_n').value = nVals.join(',');
        }
    </script>
</body>
</html>
<?php
// END ERROR/OUTPUT BUFFERING
$sys_debug_log = trim(ob_get_clean());
if (!empty($sys_debug_log)) {
    crm_log_add(array(
        'app_name'    => 'form_simple_select.php',
        'action_type' => 'error',
        'description' => 'Page Load Uncaught Output: ' . $sys_debug_log
    ));
}
?>
