<?php
/**
 * Project: ge_grid_edit
 * Author: Gunter Eibl
 * Version: 1.0.5
 * Date: 2023-10-27
 */

session_start();

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

// 1. START ERROR/OUTPUT BUFFERING
ob_start();

$tableName = isset($_GET['table_name']) ? $_GET['table_name'] : 'adresse';
$gridName = isset($_GET['grid_name']) ? $_GET['grid_name'] : 'adresse_multiline';
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'design';

$_SESSION['form_multiline_grid_control_table'] = $tableName;
$_SESSION['form_multiline_grid_name'] = $gridName;

if (!isset($_SESSION['asdw_user']))
    $_SESSION['asdw_user'] = 'gunter';
$currentUser = $_SESSION['asdw_user'];

require_once $_SERVER['DOCUMENT_ROOT'] . '/tools/design_templates/ag_library.php';
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ultimate Grid Final 1.0.5</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php ag_inject_css_variables([]); ?>

    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/trumbowyg.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/theme/dracula.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/addon/hint/show-hint.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/addon/edit/matchbrackets.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/addon/hint/show-hint.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/xml/xml.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/css/css.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/clike/clike.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/php/php.min.js"></script>

    <style>
        /* Mobile Modes CSS */
        @media (max-width: 768px) {
            .mobile-hidden { display: none !important; }
            thead th { position: sticky; top: 0; z-index: 100; background: var(--ag-color-surface, #fff); box-shadow: 0 1px 0 var(--ag-color-border, #ccc); }
            
            .mobile-mode-cards table, .mobile-mode-cards thead, .mobile-mode-cards tbody, .mobile-mode-cards th, .mobile-mode-cards td, .mobile-mode-cards tr {
                display: block; width: 100% !important; min-width: 100% !important;
            }
            .mobile-mode-cards thead { display: none; }
            .mobile-mode-cards tr { margin-bottom: 2rem; border: 1px solid var(--ag-color-border, #ccc); border-radius: 8px; padding: 10px; background: var(--ag-color-surface, #fff); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            .mobile-mode-cards td { border: none !important; padding-left: 5px !important; padding-right: 5px !important; position: relative; margin-top: 25px; padding-bottom: 10px; }
            .mobile-mode-cards td::before {
                content: attr(data-field);
                position: absolute;
                top: -20px;
                left: 5px;
                background: transparent;
                padding: 0;
                font-size: 0.75rem;
                font-weight: bold;
                color: var(--ag-color-primary, #6c757d);
                text-transform: uppercase;
            }
            .mobile-mode-cards .cell-edit-input { width: 100% !important; }
            .mobile-mode-cards td[class="text-center"]::before { display: none; }
            .mobile-mode-cards td[class="text-center"] { margin-top: 10px; border-top: 1px solid #eee !important; padding-top: 15px !important; }
        }
        body, html {
            background-color: var(--ag-color-background, #f8fafc) !important;
            color: var(--ag-color-text-main, #0f172a) !important;
            transition: all 0.4s ease;
        }

        .sc-custom-grid-wrapper {
            font-family: "Segoe UI", Arial, sans-serif;
            background-color: transparent !important;
            padding: 15px;
            border-radius: 4px;
        }

        .sc-grid-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #fff;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .sc-grid-container {
            overflow-x: auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            min-height: 400px;
            position: relative;
        }

        .sc-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            min-width: 1000px;
            table-layout: fixed;
        }

        .sc-table th,
        .sc-table td {
            border-bottom: 1px solid #dee2e6;
            padding: 8px 10px;
            text-align: left;
            vertical-align: middle;
            border-right: 1px solid #f8f9fa;
        }

        .sc-table th {
            background-color: #e9ecef;
            color: #495057;
            font-weight: 600;
            white-space: nowrap;
            position: relative;
            border-top: none;
            user-select: none;
            cursor: pointer;
        }

        .sc-table th:hover {
            background-color: #dbe2e8;
        }

        .col-resizer {
            position: absolute;
            right: 0;
            top: 0;
            width: 10px;
            height: 100%;
            cursor: col-resize;
            z-index: 10;
            opacity: 0;
        }

        .col-resizer:hover {
            background: rgba(0, 123, 255, 0.3);
            opacity: 1;
        }

        th.ui-sortable-handle {
            cursor: grab;
        }

        .header-search {
            width: 100%;
            font-size: 0.8rem;
            padding: 3px;
            border: 1px solid #ced4da;
            border-radius: 3px;
            margin-top: 5px;
            cursor: text;
        }

        .cell-edit-input {
            width: 100%;
            border: 1px solid transparent;
            background: transparent;
            padding: 4px;
            border-radius: 3px;
            transition: 0.2s;
            min-height: 30px;
        }

        .cell-edit-input:focus {
            border-color: #80bdff;
            background: #fff;
            outline: none;
        }

        .changed-cell {
            background-color: #ffeeba !important;
            transition: background 0.5s;
        }

        .required-field {
            border-left: 3px solid #007bff !important;
        }

        .img-thumbnail-custom {
            height: 40px;
            width: auto;
            max-width: 80px;
            object-fit: contain;
            cursor: zoom-in;
            border: 1px solid #dee2e6;
            background: #fff;
            padding: 2px;
            border-radius: 4px;
        }

        .html-preview-box {
            max-height: 50px;
            overflow: hidden;
            font-size: 0.8rem;
            border: 1px solid #eee;
            padding: 4px;
            cursor: pointer;
            position: relative;
            background: #fff;
        }

        .btn-icon {
            cursor: pointer;
            color: #6c757d;
            margin-left: 5px;
            font-size: 14px;
        }

        .btn-icon:hover {
            color: #007bff;
        }

        mark {
            background-color: #ffeeba;
            padding: 0;
            color: #000;
        }

        .input-match {
            border: 2px solid #ffc107 !important;
            background-color: #fffbf0;
        }

        #map {
            height: 350px;
            width: 100%;
            margin-top: 10px;
            border: 1px solid #ccc;
        }

        .modal-body {
            max-height: 80vh;
            overflow-y: auto;
        }

        .trumbowyg-box {
            margin: 0;
        }

        #signature-pad {
            touch-action: none;
            background: #fff;
            border: 1px solid #ccc;
        }

        .nav-tabs .nav-link.active {
            font-weight: bold;
            border-bottom: 3px solid #007bff;
        }

        .sort-indicator {
            margin-left: 5px;
            color: #007bff;
        }

        .lookup-row {
            display: flex;
            gap: 5px;
            margin-bottom: 5px;
        }

        .lookup-key {
            width: 40%;
        }

        .lookup-val {
            width: 60%;
        }

        .CodeMirror { 
            font-family: monospace; 
            font-size: 13px !important; 
            border-radius: 4px; 
            border: 1px solid #ced4da; 
            margin-bottom: 5px; 
        } 
        .CodeMirror-hints { 
            z-index: 10000 !important; 
            font-family: monospace; 
            font-size: 13px; 
        }

        /* Preview Modal Close Button */
        #imagePreviewModal .close {
            opacity: 1;
            text-shadow: 0 1px 2px #000;
        }

        #imagePreviewModal .close:hover {
            color: #ddd;
        }

        /* AG DESIGN SYSTEM CONFIG-SIDEBAR OVERRIDES */
        #config-sidebar, #config-main-view, #config-detail-view, .bg-white {
            background-color: var(--ag-color-surface, #fff) !important;
            color: var(--ag-color-text-main, #212529) !important;
            border-color: var(--ag-color-border, #dee2e6) !important;
        }
        #config-sidebar .modal-header, #config-sidebar .nav-tabs .nav-link, .bg-light {
            background-color: var(--ag-color-toolbar_bg, #f8f9fa) !important;
            border-color: var(--ag-color-border, #dee2e6) !important;
            color: var(--ag-color-text-main, #495057) !important;
        }
        #config-sidebar .nav-tabs .nav-link.active {
            background-color: var(--ag-color-surface, #fff) !important;
            border-bottom-color: transparent !important;
            font-weight: bold;
        }
        .form-control, .modal-content, .card {
            background-color: var(--ag-color-surface, #ffffff) !important;
            color: var(--ag-color-text-main, #212529) !important;
            border-color: var(--ag-color-border, #dee2e6) !important;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.2rem var(--ag-color-brand, rgba(0, 123, 255, 0.25)) !important;
            background-color: var(--ag-design-focus_bg, #ffffff) !important;
        }

        /* Resize Handles */
        #config-sidebar .ui-resizable-handle { opacity: 0; transition: opacity 0.2s; }
        #config-sidebar:hover .ui-resizable-handle { opacity: 1; }
        #config-sidebar .ui-resizable-w { cursor: ew-resize; width: 6px; left: -3px; }
        #config-sidebar .ui-resizable-w::after { content: ''; position: absolute; left: 2px; top: 50%; transform: translateY(-50%); height: 30px; width: 4px; background: #ccc; border-radius: 2px; }
    </style>
</head>

<body class="min-h-screen flex flex-col">

    <?php ag_render_header('Multiline Grid Designer', 'MODUL'); ?>

    <?php if (!empty($sys_debug_log)): ?>
        <div class="px-4 mt-4 w-full">
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 shadow-sm rounded-r">
                <h3 class="font-bold text-sm mb-2">System Debug / Uncaught Output:</h3>
                <pre class="text-xs overflow-auto whitespace-pre-wrap"><?= htmlspecialchars($sys_debug_log) ?></pre>
            </div>
        </div>
    <?php endif; ?>
    
    <main class="flex-grow w-full px-4 my-4 d-flex" style="gap: 15px; align-items: flex-start;">
        <div id="form-wrapper" style="flex: 1; min-width: 0;">
            <div id="my-grid-instance-1"></div>
        </div>

        
        <div id="config-sidebar" style="display:none; width: 550px; flex-shrink: 0; background: white; border: 1px solid #dee2e6; border-radius: 4px; box-shadow: 0 0 15px rgba(0,0,0,0.05); overflow: hidden;">
            <div class="modal-header pb-0 border-bottom-0 bg-light flex-shrink-0">
                <ul class="nav nav-tabs border-bottom-0" id="cfgTabs" role="tablist" style="width:100%">
                    <li class="nav-item"><a class="nav-link active" id="tab-gen" data-toggle="tab" href="#content-gen">Allgemein</a></li>
                    <li class="nav-item"><a class="nav-link" id="tab-fld" data-toggle="tab" href="#content-fld">Felder</a></li>
                    <li class="nav-item"><a class="nav-link" id="tab-form-events" data-toggle="tab" href="#content-form-events">Ereignisse</a></li>
                    <li class="nav-item"><a class="nav-link" id="tab-json" data-toggle="tab" href="#content-json">JSON</a></li>
                    <button type="button" class="close ml-auto mb-2" onclick="window.closeConfigSidebar()">×</button>
                </ul>
            </div>
            <div class="modal-body bg-light pt-3 p-0 m-0" style="flex: 1; overflow-y: auto;">
                <div id="config-main-view" class="tab-content bg-white border rounded p-3 shadow-sm m-3" style="min-height: 500px;">
                    <div class="tab-pane fade show active" id="content-gen">
                        <form>
                            <div class="form-group row">
                                <label class="col-4 cfg-label">Name</label>
                                <div class="col-8"><input class="form-control" id="cfg_table" readonly></div>
                            </div>
                            <div class="form-group row">
                                <label class="col-4 cfg-label">Limit / Seite</label>
                                <div class="col-8"><input type="number" class="form-control" id="cfg_numberPerPage"></div>
                            </div>
                            <div class="form-group row">
                                <label class="col-4 cfg-label">Sortierung</label>
                                <div class="col-4"><input class="form-control" id="cfg_sortField" readonly></div>
                                <div class="col-4"><input class="form-control" id="cfg_sortOrder" readonly></div>
                            </div>
                            <hr>
                            <div class="form-group row">
                                <label class="col-4 cfg-label">Ungerade Zeile</label>
                                <div class="col-8"><input type="color" class="form-control" id="cfg_row_color_odd"></div>
                            </div>
                            <div class="form-group row">
                                <label class="col-4 cfg-label">Gerade Zeile</label>
                                <div class="col-8"><input type="color" class="form-control" id="cfg_row_color_even"></div>
                            </div>
                            <hr>
                                                        <div class="form-group row">
                                <label class="col-4 cfg-label">Mobile Ansicht</label>
                                <div class="col-8">
                                    <select class="form-control" id="cfg_mobile_mode">
                                        <option value="standard">Standard (Scrollen)</option>
                                        <option value="cards">Kachel-Ansicht (Cards)</option>
                                        <option value="modal">Detail (Verstecken + Modal)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-4 cfg-label">Zugeordnetes Form_Simple <br><small class="text-muted">(Fallback)</small></label>
                                <div class="col-8">
                                    <select class="form-control" id="cfg_form_simple_grid">
                                        <option value="">- Automatisch generieren -</option>
                                        <?php
                                        $resGrids = db_query("SELECT grid_name FROM grid_definition WHERE (type_of_form = 'simple' OR type_of_form IS NULL OR type_of_form = '') AND (is_deleted_yn = 0 OR is_deleted_yn IS NULL) ORDER BY grid_name ASC");
                                        if ($resGrids['success'] && !empty($resGrids['data'])) {
                                            foreach ($resGrids['data'] as $rGrid) {
                                                $g = htmlspecialchars($rGrid['grid_name']);
                                                echo "<option value=\"{$g}\">{$g}</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                    <small class="form-text text-muted">Auswählen, welches Simple-Form als Detail-Modal genutzt werden soll.</small>
                                </div>
                            </div>
                            <hr>
                            <h6 class="text-primary mt-3"><i class="fas fa-robot"></i> KI-Schnittstellen</h6>
                            <p class="small text-muted mb-3">Hinterlegen Sie hier Ihre API-Schlüssel.</p>

                            <div class="form-group row">
                                <label class="col-4 cfg-label">Google Gemini <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-primary ml-1" title="API Key erstellen (Google AI Studio)"><i class="fas fa-external-link-alt"></i></a></label>
                                <div class="col-8">
                                    <div class="input-group">
                                        <input id="cfg_api_gemini" type="password" class="form-control"
                                            placeholder="AIzaSy..." autocomplete="new-password">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-pw" type="button" onclick="var i=$(this).parent().prev('input');if(i.attr('type')=='password'){i.attr('type','text');$(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');}else{i.attr('type','password');$(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');}"><i class="fas fa-eye"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-4 cfg-label">OpenAI ChatGPT <a href="https://platform.openai.com/api-keys" target="_blank" class="text-primary ml-1" title="API Key erstellen (OpenAI Platform)"><i class="fas fa-external-link-alt"></i></a></label>
                                <div class="col-8">
                                    <div class="input-group">
                                        <input id="cfg_api_chatgpt" type="password" class="form-control"
                                            placeholder="sk-proj-..." autocomplete="new-password">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-pw" type="button" onclick="var i=$(this).parent().prev('input');if(i.attr('type')=='password'){i.attr('type','text');$(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');}else{i.attr('type','password');$(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');}"><i class="fas fa-eye"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-4 cfg-label">Anthropic Claude <a href="https://console.anthropic.com/settings/keys" target="_blank" class="text-primary ml-1" title="API Key erstellen (Anthropic Console)"><i class="fas fa-external-link-alt"></i></a></label>
                                <div class="col-8">
                                    <div class="input-group">
                                        <input id="cfg_api_anthropic" type="password" class="form-control"
                                            placeholder="sk-ant-..." autocomplete="new-password">
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary toggle-pw" type="button" onclick="var i=$(this).parent().prev('input');if(i.attr('type')=='password'){i.attr('type','text');$(this).find('i').removeClass('fa-eye').addClass('fa-eye-slash');}else{i.attr('type','password');$(this).find('i').removeClass('fa-eye-slash').addClass('fa-eye');}"><i class="fas fa-eye"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="tab-pane fade" id="content-fld"></div>
                    <div class="tab-pane fade" id="content-form-events">
                        <h6 class="border-bottom pb-2 text-primary"><i class="fas fa-bolt"></i> Formular-Referenz: <br><small class="text-muted">Zugriff auf Zeilendaten über <code>$data['feldname']</code></small></h6>
                        <div class="form-group"><label>onLoad <small>(Nach dem Laden)</small></label><textarea id="cfg_evt_onload" class="form-control" rows="3" style="font-family:monospace;font-size:12px;"></textarea></div>
                        <div class="form-group"><label>onBeforeSave <small>(Vor dem Speichern)</small></label><textarea id="cfg_evt_onbeforesave" class="form-control" rows="3" style="font-family:monospace;font-size:12px;"></textarea></div>
                        <div class="form-group"><label>onAfterSave <small>(Nach dem Speichern)</small></label><textarea id="cfg_evt_onaftersave" class="form-control" rows="3" style="font-family:monospace;font-size:12px;"></textarea></div>
                        <div class="form-group"><label>Libraries / Globale Funktionen (PHP)</label><textarea id="cfg_evt_libraries" class="form-control" rows="5" placeholder="function myCustomCalc($val) { return $val * 2; }" style="font-family:monospace;font-size:12px;"></textarea></div>
                    </div>
                    <div class="tab-pane fade" id="content-json">
                        <textarea id="config-json-editor" class="form-control" rows="20" style="font-family:monospace; font-size:12px;"></textarea>
                    </div>
                </div>

                <div id="config-detail-view" class="bg-white border rounded p-3 shadow-sm m-3" style="min-height: 500px; display:none;">
<div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="m-0">Feld: <span id="detail-field-name" class="text-primary"></span></h5>
                            <button class="btn btn-sm btn-secondary" id="btn-detail-back"><i
                                    class="fas fa-arrow-left"></i> Zurück</button>
                        </div>
                        
                        <!-- Live Preview -->
                        <div class="p-3 mb-3 border rounded shadow-sm" style="background-color: #f1f3f5; border-left: 4px solid #6c757d !important;" id="field-preview-container">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="badge badge-secondary" style="font-size: 10px; opacity: 0.8;"><i class="fas fa-eye"></i> Live Vorschau</span>
                            </div>
                            <div class="form-group mb-0" style="position: relative;" id="preview-wrapper">
                                <label id="preview-label" style="font-size: 14px; font-weight: normal; color: #333;">Beispiel-Label</label>
                                <div id="preview-input-container">
                                    <input type="text" id="preview-input" class="form-control" value="Muster 1234,56" style="background-color: #fff; color: #495057; text-align: left; font-weight: normal; font-style: normal;">
                                </div>
                            </div>
                        </div>

                        <hr>
                        
                        <!-- Nav Tabs -->
                        <ul class="nav nav-tabs mb-3" id="fieldDetailTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active py-2 px-3 small font-weight-bold" id="dt-basis-tab" data-toggle="tab" href="#dt-basis" role="tab"><i class="fas fa-cog"></i> Basis</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2 px-3 small font-weight-bold" id="dt-design-tab" data-toggle="tab" href="#dt-design" role="tab"><i class="fas fa-paint-brush"></i> Design</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2 px-3 small font-weight-bold" id="dt-behavior-tab" data-toggle="tab" href="#dt-behavior" role="tab"><i class="fas fa-sliders-h"></i> Verhalten</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2 px-3 small font-weight-bold" id="dt-lookup-tab" data-toggle="tab" href="#dt-lookup" role="tab"><i class="fas fa-database"></i> Lookup</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-2 px-3 small font-weight-bold" id="dt-events-tab" data-toggle="tab" href="#dt-events" role="tab"><i class="fas fa-bolt text-warning"></i> Action</a>
                            </li>
                        </ul>

                        <!-- Tab Content -->
                        <div class="tab-content" id="fieldDetailTabsContent">
                            <!-- TAB 1: BASIS & VALIDIERUNG -->
                            <div class="tab-pane fade show active" id="dt-basis" role="tabpanel">
                                <div class="mb-4 pb-3 border-bottom">
                                    <h6 class="text-primary mt-2">Allgemein</h6>
                                    <div class="form-group"><label>Typ</label><select id="det_type"
                                            class="form-control form-control-sm"
                                            onchange="app.checkHeightEnabled()"></select></div>
                                    <div class="form-group"><label>Label</label><input id="det_label"
                                            class="form-control form-control-sm"></div>
                                    <div class="form-row">
                                        <div class="form-group col-6"><label>Breite</label><input id="det_width"
                                                type="number" class="form-control form-control-sm"></div>
                                        <div class="form-group col-6"><label>Höhe</label><input id="det_height"
                                                type="number" class="form-control form-control-sm" placeholder="Auto"></div>
                                    </div>
                                    <div class="custom-control custom-checkbox mb-3"><input type="checkbox" class="custom-control-input" id="det_hide_mobile"><label class="custom-control-label" for="det_hide_mobile">Auf dem Handy ausblenden (Variante Modal)</label></div><div class="form-group"><label>Hilfetext (HTML)</label><textarea id="det_help"
                                            class="form-control form-control-sm" rows="3"></textarea></div>
                                </div>
                                <div class="pb-2" id="det_val_section">
                                    <h6 class="text-primary mt-2">Validierung</h6>
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input type="checkbox" class="custom-control-input" id="det_val_req">
                                        <label class="custom-control-label" for="det_val_req">Pflichtfeld (Required)</label>
                                    </div>
                                    <div class="form-row">
                                        <div class="form-group col-6"><label>Min. Länge</label><input id="det_val_min"
                                                type="number" class="form-control form-control-sm"></div>
                                        <div class="form-group col-6"><label>Max. Länge</label><input id="det_val_max"
                                                type="number" class="form-control form-control-sm"></div>
                                    </div>
                                    <div class="form-group">
                                        <label>RegEx Muster</label>
                                        <div class="input-group input-group-sm">
                                            <input id="det_val_regex" class="form-control" placeholder="z.B. ^[0-9]+$">
                                            <div class="input-group-append">
                                                <button class="btn btn-info text-white" type="button"
                                                    onclick="app.openRegexBuilder()" title="RegEx KI-Assistent & Tester"><i
                                                        class="fas fa-magic"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group"><label>Eigene Fehlermeldung</label><input id="det_val_regex_msg"
                                            class="form-control form-control-sm"
                                            placeholder="z.B. Bitte geben Sie eine gültige IBAN ein."></div>
                                </div>
                            </div>
                            
                            <!-- TAB 2: DESIGN -->
                            <div class="tab-pane fade" id="dt-design" role="tabpanel">
                                <h6 class="text-primary mt-2 border-bottom pb-2">Styling Eingabefeld (Input)</h6>
                                <div class="form-row">
                                    <div class="form-group col-4">
                                        <label>Ausrichtung</label>
                                        <select id="det_style_align" class="form-control form-control-sm" onchange="app.updateFieldPreview()">
                                            <option value="">Standard</option>
                                            <option value="center">Zentriert</option>
                                            <option value="right">Rechtsbündig</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-4">
                                        <label>Schriftschnitt</label>
                                        <select id="det_style_weight" class="form-control form-control-sm" onchange="app.updateFieldPreview()">
                                            <option value="">Standard</option>
                                            <option value="bold">Fett</option>
                                            <option value="italic">Kursiv</option>
                                            <option value="monospace">Code</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-4">
                                        <label>Schriftgröße</label>
                                        <select id="det_style_size" class="form-control form-control-sm" onchange="app.updateFieldPreview()">
                                            <option value="">Standard</option>
                                            <option value="0.8rem">Klein (Sm)</option>
                                            <option value="1.2rem">Groß (Lg)</option>
                                            <option value="1.5rem">Extra Groß</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row border-bottom pb-2 mb-3">
                                    <div class="form-group col-6">
                                        <label>Textfarbe (<input type="checkbox" id="det_style_color_en" onchange="app.updateFieldPreview()"> Aktiv)</label>
                                        <input type="color" id="det_style_color" class="form-control form-control-sm" value="#000000" onchange="app.updateFieldPreview()" oninput="$('#det_style_color_en').prop('checked', true); app.updateFieldPreview();">
                                    </div>
                                    <div class="form-group col-6">
                                        <label>Hintergrundfarbe (<input type="checkbox" id="det_style_bg_en" onchange="app.updateFieldPreview()"> Aktiv)</label>
                                        <input type="color" id="det_style_bg" class="form-control form-control-sm" value="#ffffff" onchange="app.updateFieldPreview()" oninput="$('#det_style_bg_en').prop('checked', true); app.updateFieldPreview();">
                                    </div>
                                </div>

                                <h6 class="text-primary mt-2 border-bottom pb-2">Styling Beschriftung (Label)</h6>
                                <div class="form-row">
                                    <div class="form-group col-4">
                                        <label>Ausrichtung</label>
                                        <select id="det_lbl_align" class="form-control form-control-sm" onchange="app.updateFieldPreview()">
                                            <option value="">Standard</option>
                                            <option value="center">Zentriert</option>
                                            <option value="right">Rechts</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-4">
                                        <label>Schriftschnitt</label>
                                        <select id="det_lbl_weight" class="form-control form-control-sm" onchange="app.updateFieldPreview()">
                                            <option value="">Standard</option>
                                            <option value="bold">Fett</option>
                                            <option value="italic">Kursiv</option>
                                        </select>
                                    </div>
                                    <div class="form-group col-4">
                                        <label>Schriftgröße</label>
                                        <select id="det_lbl_size" class="form-control form-control-sm" onchange="app.updateFieldPreview()">
                                            <option value="">Standard</option>
                                            <option value="0.8rem">Klein (Sm)</option>
                                            <option value="1.2rem">Groß (Lg)</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-row">
                                    <div class="form-group col-6">
                                        <label>Textfarbe (<input type="checkbox" id="det_lbl_color_en" onchange="app.updateFieldPreview()"> Aktiv)</label>
                                        <input type="color" id="det_lbl_color" class="form-control form-control-sm" value="#000000" onchange="app.updateFieldPreview()" oninput="$('#det_lbl_color_en').prop('checked', true); app.updateFieldPreview();">
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 3: VERHALTEN -->
                            <div class="tab-pane fade" id="dt-behavior" role="tabpanel">
                                <h6 class="text-primary mt-2 border-bottom pb-2">Texteingabe & Hilfen <small class="text-muted">(Hauptsächlich Text/Zahlen)</small></h6>
                                <div class="form-group">
                                    <label>Platzhalter-Text (Placeholder)</label>
                                    <input type="text" id="det_beh_placeholder" class="form-control form-control-sm" placeholder="Beispiel: Bitte geben Sie Ihren Namen ein..." onkeyup="app.updateFieldPreview()">
                                </div>
                                <div class="form-row border-bottom pb-2 mb-3">
                                    <div class="form-group col-6">
                                        <label>Präfix (Text oder Icon)</label>
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" tabindex="-1"><i class="fas fa-icons"></i></button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="#" onclick="$('#det_beh_prefix').val('fas fa-user'); app.updateFieldPreview(); return false;"><i class="fas fa-user fa-fw"></i> User</a>
                                                    <a class="dropdown-item" href="#" onclick="$('#det_beh_prefix').val('fas fa-envelope'); app.updateFieldPreview(); return false;"><i class="fas fa-envelope fa-fw"></i> Mail</a>
                                                    <a class="dropdown-item" href="#" onclick="$('#det_beh_prefix').val('fas fa-phone'); app.updateFieldPreview(); return false;"><i class="fas fa-phone fa-fw"></i> Phone</a>
                                                    <a class="dropdown-item" href="#" onclick="$('#det_beh_prefix').val('fas fa-lock'); app.updateFieldPreview(); return false;"><i class="fas fa-lock fa-fw"></i> Lock</a>
                                                    <a class="dropdown-item" href="#" onclick="$('#det_beh_prefix').val('fas fa-calendar-alt'); app.updateFieldPreview(); return false;"><i class="fas fa-calendar-alt fa-fw"></i> Calendar</a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item" href="#" onclick="$('#det_beh_prefix').val('€'); app.updateFieldPreview(); return false;">€ Symbol</a>
                                                    <a class="dropdown-item" href="#" onclick="$('#det_beh_prefix').val('$'); app.updateFieldPreview(); return false;">$ Symbol</a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-primary" href="https://fontawesome.com/search?o=r&m=free" target="_blank"><i class="fas fa-external-link-alt fa-fw"></i> Mehr Icons suchen...</a>
                                                </div>
                                            </div>
                                            <input type="text" id="det_beh_prefix" class="form-control" placeholder="z.B. € oder fas fa-envelope" onkeyup="app.updateFieldPreview()">
                                        </div>
                                    </div>
                                    <div class="form-group col-6">
                                        <label>Suffix (Text oder Icon)</label>
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" tabindex="-1"><i class="fas fa-icons"></i></button>
                                                <div class="dropdown-menu">
                                                    <a class="dropdown-item" href="#" onclick="$('#det_beh_suffix').val('%'); app.updateFieldPreview(); return false;">% Prozent</a>
                                                    <a class="dropdown-item" href="#" onclick="$('#det_beh_suffix').val('kg'); app.updateFieldPreview(); return false;">kg</a>
                                                    <a class="dropdown-item" href="#" onclick="$('#det_beh_suffix').val('Stk'); app.updateFieldPreview(); return false;">Stück</a>
                                                    <a class="dropdown-item" href="#" onclick="$('#det_beh_suffix').val('€'); app.updateFieldPreview(); return false;">€ Symbol</a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item" href="#" onclick="$('#det_beh_suffix').val('fas fa-check'); app.updateFieldPreview(); return false;"><i class="fas fa-check fa-fw"></i> Check</a>
                                                    <a class="dropdown-item" href="#" onclick="$('#det_beh_suffix').val('fas fa-times'); app.updateFieldPreview(); return false;"><i class="fas fa-times fa-fw"></i> Times</a>
                                                    <a class="dropdown-item" href="#" onclick="$('#det_beh_suffix').val('fas fa-search'); app.updateFieldPreview(); return false;"><i class="fas fa-search fa-fw"></i> Search</a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-primary" href="https://fontawesome.com/search?o=r&m=free" target="_blank"><i class="fas fa-external-link-alt fa-fw"></i> Mehr Icons suchen...</a>
                                                </div>
                                            </div>
                                            <input type="text" id="det_beh_suffix" class="form-control" placeholder="z.B. kg" onkeyup="app.updateFieldPreview()">
                                        </div>
                                    </div>
                                </div>

                                <div id="beh_row_numbers">
                                    <h6 class="text-primary mt-2 border-bottom pb-2">Zahlen-Formatierung <small class="text-muted">(für Dezimal/Integer)</small></h6>
                                    <div class="form-row border-bottom pb-2 mb-3">
                                        <div class="form-group col-6">
                                            <label>Tausendertrennzeichen</label>
                                            <select id="det_beh_thousands" class="form-control form-control-sm" onchange="app.updateFieldPreview()">
                                                <option value="">Nein</option>
                                                <option value=".">Punkt (1.000)</option>
                                                <option value=",">Komma (1,000)</option>
                                                <option value=" ">Leerzeichen (1 000)</option>
                                            </select>
                                        </div>
                                        <div class="form-group col-6">
                                            <label>Dezimalstellen (Erzwingen)</label>
                                            <input type="number" id="det_beh_decimals" min="0" max="6" class="form-control form-control-sm" placeholder="Standard" onchange="app.updateFieldPreview()">
                                        </div>
                                    </div>
                                </div>

                                <h6 class="text-primary mt-2 border-bottom pb-2">Spezial-Funktionen</h6>
                                <div class="form-group" id="beh_row_counter">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" class="custom-control-input" id="det_beh_counter" onchange="app.updateFieldPreview()">
                                        <label class="custom-control-label" for="det_beh_counter">Zeichenzähler anzeigen <small>(benötigt Max. Länge im Reiter Basis)</small></label>
                                    </div>
                                </div>
                                <div id="beh_row_button" style="display:none;">
                                    <h6 class="text-primary mt-3 border-bottom pb-2"><i class="fas fa-bullseye"></i> Button-Einstellungen</h6>
                                    <div class="form-group mb-2">
                                        <label>Button Design (Variant)</label>
                                        <select id="det_beh_btnStyle" class="form-control form-control-sm" onchange="app.updateFieldPreview()">
                                            <option value="primary">Primary (Blau)</option>
                                            <option value="secondary">Secondary (Grau)</option>
                                            <option value="success">Success (Grün)</option>
                                            <option value="danger">Danger (Rot)</option>
                                            <option value="outline">Outline (Rahmen)</option>
                                            <option value="custom">Benutzerdefiniert (Style-Reiter)</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label>Aktion: URL aufrufen <small class="text-muted">(Optional, ansonsten onClick-Event)</small></label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" id="det_beh_btnUrl" class="form-control form-control-sm" placeholder="z.B. https://...?id={id}">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-info" type="button" title="URL-Assist" onclick="app.openAiCodeModal('det_beh_btnUrl'); $('#ea_prompt').attr('placeholder', 'Erstelle eine URL, die die Felder id und name kombiniert...');">
                                                    <i class="fas fa-magic"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Nutze {feldname} als Platzhalter-Variable.</small>
                                    </div>
                                    <div class="form-group mb-2">
                                        <label>Ziel-Fenster</label>
                                        <select id="det_beh_btnTarget" class="form-control form-control-sm" onchange="app.updateFieldPreview()">
                                            <option value="_self">Gleiches Fenster (_self)</option>
                                            <option value="_blank">Neues Fenster (_blank)</option>
                                            <option value="_parent">Eltern-Fenster (_parent)</option>
                                        </select>
                                    </div>
                                    <div class="form-group mb-2 mt-3 pt-2 border-top">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="det_beh_btn3d" onchange="app.updateFieldPreview()">
                                            <label class="custom-control-label" for="det_beh_btn3d">3D-Effekt aktivieren <small>(Klick-Animation)</small></label>
                                        </div>
                                    </div>
                                </div>

                            </div>

                            <!-- TAB 4: LOOKUP -->
                            <div class="tab-pane fade" id="dt-lookup" role="tabpanel">
                                <h6 class="text-primary mt-2 border-bottom pb-2">Datenquelle (Lookup) <small class="text-muted">(für Dropdowns & Multiselects)</small></h6>
                                <div class="form-group">
                                    <label>Quelle für Werte</label>
                                    <select id="det_lookup_type" class="form-control form-control-sm" onchange="app.toggleLookupType(this.value)">
                                        <option value="">- Keine Vorgabe -</option>
                                        <option value="manual">Manuelle Liste (Statisch)</option>
                                        <option value="sql">Datenbank-Abfrage (SQL)</option>
                                    </select>
                                </div>
                                <div id="det_lookup_manual_area" style="display:none;" class="mt-3">
                                    <label class="font-weight-bold"><i class="fas fa-list"></i> Listen-Einträge</label>
                                    <div class="bg-light p-2 rounded border mb-2">
                                        <div class="row align-items-center mb-1 text-muted small px-2">
                                            <div class="col-4">Wert (Key)</div>
                                            <div class="col-7 border-left border-gray-300">Anzeige (Label)</div>
                                            <div class="col-1"></div>
                                        </div>
                                        <div id="det_manual_rows" style="max-height: 250px; overflow-y:auto; overflow-x:hidden;"></div>
                                    </div>
                                    <button class="btn btn-sm border bg-white" style="color:var(--ag-color-brand)" onclick="app.addLookupRow()">
                                        <i class="fas fa-plus"></i> Zeile anfügen
                                    </button>
                                </div>
                                <div id="det_lookup_sql_area" style="display:none;" class="mt-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <label class="font-weight-bold mb-0"><i class="fas fa-database"></i> SQL Abfrage</label>
                                        <div>
                                            <button class="btn btn-sm btn-outline-secondary mr-2" type="button" title="SQL Testen" onclick="app.testSql()"><i class="fas fa-play"></i> Prüfen</button>
                                            <button class="btn btn-sm btn-link text-primary ai-code-btn" type="button" style="display:none;" title="KI Code-Assistent" onclick="app.openAiCodeModal('det_sql_query')"><i class="fas fa-magic"></i></button>
                                        </div>
                                    </div>
                                    <p class="small text-muted mb-2">Gibt mindestens 2 Spalten zurück: <code>id</code> (Wert) und <code>name</code> (Anzeige). (Bitte "key" NICHT als Spalten-Alias verwenden!)</p>
                                    <textarea id="det_sql_query" class="form-control" rows="5" placeholder="SELECT id, name FROM tab_name"></textarea>
                                    <div class="form-group mt-3" id="beh_row_select2" style="display:none;">
                                        <div class="custom-control custom-checkbox">
                                            <input type="checkbox" class="custom-control-input" id="det_beh_select2">
                                            <label class="custom-control-label" for="det_beh_select2">Suchfeld-Funktion aktivieren (Select2) <small>(nur für Dropdowns)</small></label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- TAB 5: EVENTS -->
                            <div class="tab-pane fade" id="dt-events" role="tabpanel">
                                <h6 class="text-primary mt-2 border-bottom pb-2">PHP Server Events <small class="text-muted">(Interaktive Logik)</small></h6>
                                <div class="alert alert-info py-1 px-2 small mb-2">
                                    <i class="fas fa-info-circle"></i> <strong>Tipp:</strong> Nutze <code>$data['feldname']</code> zum Lesen/Schreiben von Feldwerten. Für Datenbankaufrufe nutze <code>db_query()</code> etc.
                                </div>
                                
                                <div class="accordion" id="evtAccordion">
                                    <div class="card border-0 mb-1">
                                        <div class="card-header p-0 bg-white shadow-sm" id="headingEvtChg">
                                            <button class="btn btn-sm btn-light btn-block text-left text-dark font-weight-bold p-2 border" type="button" onclick="$('.evt-col').hide(); $('#colEvtChg').show(); app.refreshEditors();">
                                                <i class="fas fa-bolt text-warning"></i> onChange <small class="text-muted font-weight-normal float-right mt-1">Beim Ändern</small>
                                            </button>
                                        </div>
                                        <div id="colEvtChg" class="evt-col" style="display:block;">
                                            <div class="card-body p-2 border border-top-0 rounded-bottom bg-light">
                                                <div class="form-group mb-0"><label class="mb-1">onChange Code:</label><textarea id="det_evt_onchange" class="form-control" rows="3" placeholder="\$data['feld'] = ...;"></textarea></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card border-0 mb-1">
                                        <div class="card-header p-0 bg-white shadow-sm" id="headingEvtClick">
                                            <button class="btn btn-sm btn-light btn-block text-left text-dark font-weight-bold p-2 border" type="button" onclick="$('.evt-col').hide(); $('#colEvtClick').show(); app.refreshEditors();">
                                                <i class="fas fa-mouse-pointer text-primary"></i> onClick <small class="text-muted font-weight-normal float-right mt-1">Beim Klicken</small>
                                            </button>
                                        </div>
                                        <div id="colEvtClick" class="evt-col" style="display:none;">
                                            <div class="card-body p-2 border border-top-0 rounded-bottom bg-light">
                                                <div class="form-group mb-0"><label class="mb-1">onClick Code:</label><textarea id="det_evt_onclick" class="form-control" rows="3"></textarea></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card border-0 mb-1">
                                        <div class="card-header p-0 bg-white shadow-sm" id="headingEvtBlur">
                                            <button class="btn btn-sm btn-light btn-block text-left text-dark font-weight-bold p-2 border" type="button" onclick="$('.evt-col').hide(); $('#colEvtBlur').show(); app.refreshEditors();">
                                                <i class="fas fa-sign-out-alt text-secondary"></i> onBlur <small class="text-muted font-weight-normal float-right mt-1">Beim Verlassen</small>
                                            </button>
                                        </div>
                                        <div id="colEvtBlur" class="evt-col" style="display:none;">
                                            <div class="card-body p-2 border border-top-0 rounded-bottom bg-light">
                                                <div class="form-group mb-0"><label class="mb-1">onBlur Code:</label><textarea id="det_evt_onblur" class="form-control" rows="3"></textarea></div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card border-0 mb-1">
                                        <div class="card-header p-0 bg-white shadow-sm" id="headingEvtFocus">
                                            <button class="btn btn-sm btn-light btn-block text-left text-dark font-weight-bold p-2 border" type="button" onclick="$('.evt-col').hide(); $('#colEvtFocus').show(); app.refreshEditors();">
                                                <i class="fas fa-crosshairs text-success"></i> onFocus <small class="text-muted font-weight-normal float-right mt-1">Beim Fokussieren</small>
                                            </button>
                                        </div>
                                        <div id="colEvtFocus" class="evt-col" style="display:none;">
                                            <div class="card-body p-2 border border-top-0 rounded-bottom bg-light">
                                                <div class="form-group mb-0"><label class="mb-1">onFocus Code:</label><textarea id="det_evt_onfocus" class="form-control" rows="3"></textarea></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card border-0 mb-1">
                                        <div class="card-header p-0 bg-white shadow-sm" id="headingEvtKey">
                                            <button class="btn btn-sm btn-light btn-block text-left text-dark font-weight-bold p-2 border" type="button" onclick="$('.evt-col').hide(); $('#colEvtKey').show(); app.refreshEditors();">
                                                <i class="fas fa-keyboard text-info"></i> onKeyPress <small class="text-muted font-weight-normal float-right mt-1">Beim Tippen</small>
                                            </button>
                                        </div>
                                        <div id="colEvtKey" class="evt-col" style="display:none;">
                                            <div class="card-body p-2 border border-top-0 rounded-bottom bg-light">
                                                <div class="form-group mb-0"><label class="mb-1">onKeyPress Code:</label><textarea id="det_evt_onkeypress" class="form-control" rows="3"></textarea></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        
                </div> <!-- /config-detail-view -->
            </div> <!-- /modal-body -->
            <div class="modal-footer pt-2 pb-2 bg-white d-flex justify-content-between flex-shrink-0">
                <button type="button" class="btn btn-secondary" onclick="window.closeConfigSidebar()">Abbrechen</button>
                <button type="button" class="btn btn-success" id="btn-save-config-json" onclick="window.getGrid('#my-grid-instance-1').saveConfigFull()"><i class="fas fa-save"></i> Speichern</button>
            </div>
        </div>
    </main>

    <?php ag_render_footer(); ?>

    <div class="modal fade" id="schemaSyncModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sync</h5><button type="button" class="close" data-dismiss="modal">×</button>
                </div>
                <div class="modal-body">
                    <div id="schema-sync-list"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-primary"
                        id="btn-apply-sync">Hinzufügen</button></div>
            </div>
        </div>
    </div>

    <div id="dynamic-modals-container"></div>

    <!-- AI Code Assistant Modal -->
    <div class="modal fade" id="eventCodeAiModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-robot"></i> KI Code-Assistent</h5>
                    <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-2">Beschreibe, was passieren soll. Die KI generiert reinen PHP-Code für das <strong id="ea_target_name"></strong> Event.</p>
                    <div class="input-group mb-3">
                        <textarea id="ea_prompt" class="form-control" rows="3" placeholder="Z. B. 'Multipliziere Menge mit Einzelpreis und trage es in Gesamtpreis ein...'"></textarea>
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="ea_mic_btn" title="Spracheingabe (Chrome)" onclick="window.app.startSprachEingabeCode()"><i class="fas fa-microphone"></i></button>
                        </div>
                    </div>
                    <input type="hidden" id="ea_target_id">
                    <button class="btn btn-primary btn-block mb-3" onclick="window.app.generateEventCode()"><i class="fas fa-magic"></i> Code generieren</button>
                    <div id="ea_loading" class="text-center" style="display:none;"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br>KI denkt nach...</div>
                    <div class="mt-3">
                        <label>Generierter Code (Vorschau)</label>
                        <textarea id="ea_result" class="form-control" rows="4" style="font-family:monospace; font-size:12px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
                    <button type="button" class="btn btn-success" onclick="window.app.applyEventCode()">Code einfügen</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="imagePreviewModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content bg-transparent border-0">
                <div class="modal-body p-0 text-center position-relative"><button type="button"
                        class="close text-white position-absolute"
                        style="right:0; top:-30px; font-size:2rem; opacity:1;" data-dismiss="modal">×</button><img
                        id="previewImageFull" src=""
                        style="max-width:100%; max-height:85vh; border-radius:4px; box-shadow: 0 10px 30px rgba(0,0,0,0.5);">
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/trumbowyg.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        var AJAX_URL = "form_multiline_ajax.php";

        // --- MAP MANAGER ---
        var mapManager = {
            map: null, marker: null, currentGridId: null, currentId: null, currentField: null,
            openMap: function (gridId, id, f, val) {
                this.currentGridId = gridId; this.currentId = id; this.currentField = f;
                $('#dynamic-modals-container').html('<div class="modal fade" id="dynMap"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h6 class="modal-title">Karte</h6><button type="button" class="close" data-dismiss="modal">×</button></div><div class="modal-body"><div class="input-group mb-2"><input id="map-addr-preview" class="form-control" value="' + val + '"><div class="input-group-append"><button id="map-s" class="btn btn-primary">Suchen</button></div></div><div id="map-cnt" style="height:350px;border:1px solid #ccc"></div></div><div class="modal-footer"><button class="btn btn-primary btn-save">Übernehmen</button></div></div></div></div>');
                $('#dynMap').modal('show');
                setTimeout(function () {
                    if (mapManager.map) mapManager.map.remove();
                    mapManager.map = L.map('map-cnt').setView([52.52, 13.405], 13);
                    var o = String.fromCharCode(123), c = String.fromCharCode(125);
                    var url = 'https://' + o + 's' + c + '.tile.openstreetmap.org/' + o + 'z' + c + '/' + o + 'x' + c + '/' + o + 'y' + c + '.png';
                    L.tileLayer(url, { attribution: 'OSM' }).addTo(mapManager.map);
                    mapManager.map.on('click', function (e) { mapManager.setMarker(e.latlng); });
                    if (val) mapManager.searchLocation(val);
                    $('#map-s').off('click').click(function () { mapManager.searchLocation($('#map-addr-preview').val()); });
                    $('#dynMap .btn-save').off('click').click(function () {
                        var newVal = $('#map-addr-preview').val();
                        var grid = window.getGrid(mapManager.currentGridId);
                        var oldVal = $(grid.container).find('tr[data-id="' + mapManager.currentId + '"] td[data-field="' + mapManager.currentField + '"] input').val();
                        grid.commitChange(mapManager.currentId, mapManager.currentField, newVal, oldVal);
                        $('#dynMap').modal('hide');
                    });
                }, 500);
            },
            searchLocation: function (q) {
                if (!q) return;
                $.get('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q), function (r) {
                    if (r && r.length) { mapManager.map.setView([r[0].lat, r[0].lon], 16); mapManager.setMarker({ lat: r[0].lat, lng: r[0].lon }); }
                });
            },
            setMarker: function (ll) {
                if (this.marker) this.map.removeLayer(this.marker);
                this.marker = L.marker(ll, { draggable: true }).addTo(this.map);
                this.marker.on('dragend', function (e) {
                    var p = e.target.getLatLng();
                    $.get('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + p.lat + '&lon=' + p.lng, function (r) { $('#map-addr-preview').val(r.display_name || (p.lat + ', ' + p.lng)); });
                });
            }
        };

        // --- GRID APP ---
        function GridApp(containerId, gridName, tableName) {
            this.containerId = containerId; this.container = $(containerId); this.gridName = gridName;
            this.tableName = tableName || gridName;
            this.config = null; this.fields = []; this.lookups = {}; this.data = [];
            this.currentPage = 1; this.limit = 10; this.filters = {};
            this.sortField = ''; this.sortOrder = '';
            this.history = []; this.historyStep = -1;
            this.renderStructure(); this.init();
        }

        GridApp.prototype.renderStructure = function () {
            var html = '';
            var envMode = '<?php echo $mode; ?>';
            
            html += '<div class="sc-custom-grid-wrapper"><div class="sc-grid-toolbar"><div class="d-flex align-items-center"><h5 class="m-0 mr-3 text-dark grid-title">Lade...</h5><span class="status-msg badge badge-light border">Init</span></div><div class="d-flex align-items-center">';
            
            if (envMode !== 'run') {
                html += '<div class="custom-control custom-switch mr-3"><input type="checkbox" class="custom-control-input" id="designSwitch" onchange="if(this.checked){ window.getGrid(\'' + this.containerId + '\').openConfigEditor(); } else { window.getGrid(\'' + this.containerId + '\').closeConfigSidebar(); }"><label class="custom-control-label font-weight-bold" for="designSwitch">Design</label></div>';
                html += '<button class="btn btn-sm btn-info btn-sync-schema mr-1" title="DB Sync"><i class="fas fa-database"></i></button>';
                html += '<button class="btn btn-sm btn-dark btn-edit-config mr-3" title="Config"><i class="fas fa-cog"></i></button>';
            }
            
            html += '<button class="btn btn-sm btn-success btn-add-record mr-2" title="Neu"><i class="fas fa-plus"></i> Neu</button>';
            html += '<button class="btn btn-sm btn-outline-secondary btn-undo" disabled title="Undo"><i class="fas fa-undo"></i></button>';
            html += '<button class="btn btn-sm btn-outline-secondary btn-redo" disabled title="Redo"><i class="fas fa-redo"></i></button>';
            html += '<button class="btn btn-sm btn-warning ml-2 btn-reset" title="Reset"><i class="fas fa-filter"></i></button>';
            html += '<button class="btn btn-sm btn-primary ml-1 btn-reload" title="Reload"><i class="fas fa-sync"></i></button>';
            html += '</div></div><div class="sc-grid-container"><table class="sc-table"><thead><tr class="grid-headers"></tr><tr class="grid-filters"></tr></thead><tbody class="grid-body"></tbody></table></div>';
            html += '<div class="d-flex justify-content-center align-items-center mt-3"><button class="btn btn-sm btn-light border btn-prev"><i class="fas fa-chevron-left"></i></button><span class="mx-3 small">Seite <span class="curr-page">1</span></span><button class="btn btn-sm btn-light border btn-next"><i class="fas fa-chevron-right"></i></button></div></div>';
            this.container.html(html);

            var self = this;
            this.container.find('.btn-reload').click(function () { self.reload(); });
            this.container.find('.btn-reset').click(function () { self.resetFilters(); });
            this.container.find('.btn-prev').click(function () { self.prevPage(); });
            this.container.find('.btn-next').click(function () { self.nextPage(); });
            this.container.find('.btn-undo').click(function () { self.undo(); });
            this.container.find('.btn-redo').click(function () { self.redo(); });
            this.container.find('.btn-add-record').click(function () { self.addRecord(); });
            this.container.find('.btn-edit-config').click(function () { self.openConfigEditor(); });
            this.container.find('.btn-sync-schema').click(function () { self.checkSchema(); });
        };

        GridApp.prototype.init = function () {
            if ($('#trumbowyg-icons').length === 0) { $.get('https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/icons.svg', function (d) { $('body').prepend('<div id="trumbowyg-icons" style="display:none">' + new XMLSerializer().serializeToString(d.documentElement) + '</div>'); }); }
            this.loadConfig();
        };

        GridApp.prototype.loadConfig = function () {
            var self = this; this.setStatus('Lade Config...');
            $.post(AJAX_URL, { action: 'load_config', gridName: this.gridName, tableName: this.tableName }, function (res) {
                if (res.status === 'ok') {
                    self.config = res.baseConfig; 
                    if (self.config.tableName) { self.tableName = self.config.tableName; }
                    self.lookups = res.lookups; 
                    self.fields = self.config.fields; 
                    self.limit = self.config.numberPerPage || 10;
                    self.sortField = self.config.sortField || ''; 
                    self.sortOrder = self.config.sortOrder || '';
                    self.container.find('.grid-title').text(self.gridName);
                    
                    self.container.removeClass('mobile-mode-cards mobile-mode-modal');
                    if (self.config.mobile_mode === 'cards') {
                        self.container.addClass('mobile-mode-cards');
                    } else if (self.config.mobile_mode === 'modal') {
                        self.container.addClass('mobile-mode-modal');
                    }
                    
                    self.applyStyles(); self.renderHeaders(); self.loadData();
                } else if (res.status === 'config_missing') {
                    if (res.table_exists) {
                        if (confirm('Config fehlt für Grid "' + self.gridName + '". Jetzt aus Tabelle "' + self.tableName + '" erstellen?')) {
                            self.createInitialConfig(self.tableName);
                        } else {
                            self.setStatus('Abgebrochen', 'warning');
                        }
                    } else {
                        alert('Fehler: Die verknüpfte Tabelle "' + self.tableName + '" existiert nicht!');
                        self.setStatus('Fehlt: ' + self.tableName, 'danger');
                    }
                }
            }, 'json');
        };

        GridApp.prototype.createInitialConfig = function (tbl) { var self = this; $.post(AJAX_URL, { action: 'create_initial_config', gridName: this.gridName, tableName: tbl }, function (res) { if (res.status == 'ok') self.checkSchema(true); }, 'json'); };

        GridApp.prototype.applyStyles = function () {
            self.container = this.container;
            self.container.removeClass('mobile-mode-cards mobile-mode-modal');
            if (this.config.mobile_mode === 'cards') self.container.addClass('mobile-mode-cards');
            else if (this.config.mobile_mode === 'modal') self.container.addClass('mobile-mode-modal');
            var odd = this.config.row_color_odd || '#ffffff'; var even = this.config.row_color_even || '#f9f9f9';
            var sid = 'style-' + this.containerId.replace('#', ''); $('#' + sid).remove();
            $('head').append('<style id="' + sid + '">' + this.containerId + ' .grid-body tr:nth-of-type(odd) { background-color: ' + odd + '; } ' + this.containerId + ' .grid-body tr:nth-of-type(even) { background-color: ' + even + '; }</style>');
        };

        GridApp.prototype.saveConfigFull = function() {
            if ($('#config-detail-view').is(':visible')) {
                var f = this.config.fields[this.currentEditFieldIdx];
                this.scrapeDetailView(f);
                $('#config-detail-view').hide(); 
                $('#config-main-view').show(); 
            }
            if ($('#tab-json').hasClass('active')) {
                try { this.config = JSON.parse($('#config-json-editor').val()); } catch (e) { alert('JSON Invalid'); return; }
            } else { 
                this.scrapeConfigFromUI(); 
            } 
            this.config.sortField = this.sortField; 
            this.config.sortOrder = this.sortOrder; 
            this.saveConfig(); 
            this.renderHeaders(); 
            this.loadData(); 
            this.applyStyles(); 
            this.closeConfigSidebar();
        };

        GridApp.prototype.closeConfigSidebar = function() {
            $('#config-sidebar').hide();
            $('#designSwitch').prop('checked', false);
        };

        GridApp.prototype.openConfigEditor = function () {
            var self = this; this.currentEditField = null;
            $('#config-json-editor').val(JSON.stringify(this.config, null, 4));
            
            $('#cfg_table').val(this.tableName);
            $('#cfg_numberPerPage').val(this.config.numberPerPage || 10);
            $('#cfg_sortField').val(this.sortField || '-');
            $('#cfg_sortOrder').val(this.sortOrder || '-');
            $('#cfg_row_color_odd').val(this.config.row_color_odd || '#ffffff');
            $('#cfg_row_color_even').val(this.config.row_color_even || '#f9f9f9');
            $('#cfg_mobile_mode').val(this.config.mobile_mode || 'standard');
            $('#cfg_form_simple_grid').val(this.config.form_simple_grid || '');

            if(window.app && window.app.setEditorValue) {
                window.app.setEditorValue('cfg_evt_onload', this.config.evt_onload || '');
                window.app.setEditorValue('cfg_evt_onbeforesave', this.config.evt_onbeforesave || '');
                window.app.setEditorValue('cfg_evt_onaftersave', this.config.evt_onaftersave || '');
                window.app.setEditorValue('cfg_evt_libraries', this.config.evt_libraries || '');
            } else {
                $('#cfg_evt_onload').val(this.config.evt_onload || '');
                $('#cfg_evt_onbeforesave').val(this.config.evt_onbeforesave || '');
                $('#cfg_evt_onaftersave').val(this.config.evt_onaftersave || '');
                $('#cfg_evt_libraries').val(this.config.evt_libraries || '');
            }
            
            $('#cfg_api_gemini').val(this.config.apiKeys ? this.config.apiKeys.gemini : '');
            $('#cfg_api_chatgpt').val(this.config.apiKeys ? this.config.apiKeys.chatgpt : '');
            $('#cfg_api_anthropic').val(this.config.apiKeys ? this.config.apiKeys.anthropic : '');

            $('#content-fld').html(this.buildFieldsTable());
            
            $('#config-main-view').show(); $('#config-detail-view').hide();
            $('#cfgTabs a').off('shown.bs.tab').on('shown.bs.tab', function (e) {
                if (e.relatedTarget && $(e.relatedTarget).attr('id') === 'tab-json') {
                    try { self.config = JSON.parse($('#config-json-editor').val()); } catch (ex) { }
                    $('#cfg_numberPerPage').val(self.config.numberPerPage || 10);
                    $('#cfg_row_color_even').val(self.config.row_color_even || '#ffffff');
                    $('#cfg_row_color_odd').val(self.config.row_color_odd || '#f9f9f9');
                    $('#content-fld').html(self.buildFieldsTable()); 
                } 
            });
            $('#btn-detail-back').off('click').click(function () { 
                if (self.currentEditField) { 
                    self.scrapeDetailView(self.currentEditField); 
                    $('#content-fld').html(self.buildFieldsTable()); 
                } 
                $('#config-detail-view').hide(); $('#config-main-view').show(); 
            });
            
            // Add lookup logic from simple view
            if (!window.lookupLogicBound) {
                $('#det_lookup_manual_area button').off('click').click(function () { 
                    $('#det_manual_rows').append('<div class="lookup-row d-flex mb-1"><input class="form-control form-control-sm lookup-key mr-1" placeholder="DB Wert"><input class="form-control form-control-sm lookup-val mr-1" placeholder="Anzeige"><button class="btn btn-sm btn-danger btn-del-row">&times;</button></div>'); 
                    $('.btn-del-row').off('click').click(function () { $(this).parent().remove(); }); 
                });
                $('#det_lookup_type').off('change').change(function () { 
                    var v = $(this).val(); 
                    if (v == 'manual') { $('#det_lookup_manual_area').show(); $('#det_lookup_sql_area').hide(); } 
                    else if (v == 'sql') { $('#det_lookup_manual_area').hide(); $('#det_lookup_sql_area').show(); } 
                    else { $('#det_lookup_manual_area').hide(); $('#det_lookup_sql_area').hide(); }
                });
                window.lookupLogicBound = true;
            }
            
            // Initialize Resizable sidebar
            if (!$('#config-sidebar').hasClass('ui-resizable')) {
                $('#config-sidebar').resizable({
                    handles: 'w',
                    minWidth: 350,
                    maxWidth: 800
                });
            }
            
            $('#config-sidebar').show();
            // Re-bind getGrid for safe access if it was lost
            window.gridInstances[this.containerId] = this;
        };

        GridApp.prototype.editFieldSettings = function (idx) {
            this.scrapeConfigFromUI(); var f = this.config.fields[idx]; this.currentEditFieldIdx = idx; this.currentEditField = f;
            $('#detail-field-name').text(f.fieldName);
            
            // Re-populate type select options
            var types = ['string', 'integer', 'decimal', 'currency', 'date', 'date_time', 'multiple_line_text', 'email', 'url', 'htmlEditor', 'image', 'youTube', 'GoogleMaps', 'signature', 'select', 'radio', 'checkbox', 'double_select', 'button', 'custom'];
            var opts = ''; types.forEach(function (t) { opts += '<option value="' + t + '" ' + (f.fieldTyp == t ? 'selected' : '') + '>' + t + '</option>'; });
            $('#det_type').html(opts); 
            
            $('#det_label').val(f.label || f.fieldName); 
            $('#det_width').val(f.width || 100);

            var lType = ''; if (f.lookup) { if (f.lookup.manual) lType = 'manual'; else if (f.lookup.type == 'sql') lType = 'sql'; }
            $('#det_lookup_type').val(lType).trigger('change');
            $('#det_manual_rows').html('');
            if (f.lookup && f.lookup.manual) { 
                for (var k in f.lookup.manual) { 
                    $('#det_manual_rows').append('<div class="lookup-row d-flex mb-1"><input class="form-control form-control-sm lookup-key mr-1" value="' + k + '"><input class="form-control form-control-sm lookup-val mr-1" value="' + f.lookup.manual[k] + '"><button class="btn btn-sm btn-danger btn-del-row">&times;</button></div>'); 
                } 
                $('.btn-del-row').off('click').click(function () { $(this).parent().remove(); }); 
            }
            if(window.app && window.app.setEditorValue) {
                window.app.setEditorValue('det_sql_query', (f.lookup && f.lookup.type == 'sql') ? f.lookup.source : '');
            } else {
                $('#det_sql_query').val((f.lookup && f.lookup.type == 'sql') ? f.lookup.source : '');
            }

            // New stuff from form_simple
            $('#det_val_req').prop('checked', f.val_req || false); 
            $('#det_val_min').val(f.val_min || ''); 
            $('#det_val_max').val(f.val_max || ''); 
            $('#det_val_regex').val(f.val_regex || ''); 
            $('#det_val_regex_msg').val(f.val_regex_msg || '');

            var inputStyle = f.style || {};
            $('#det_style_align').val(inputStyle.textAlign || ''); $('#det_style_weight').val(inputStyle.weight || ''); $('#det_style_size').val(inputStyle.size || '');
            if (inputStyle.color) { $('#det_style_color').val(inputStyle.color); $('#det_style_color_en').prop('checked', true); } else { $('#det_style_color_en').prop('checked', false); }
            if (inputStyle.backgroundColor) { $('#det_style_bg').val(inputStyle.backgroundColor); $('#det_style_bg_en').prop('checked', true); } else { $('#det_style_bg_en').prop('checked', false); }

            var lblStyle = f.lblStyle || {};
            $('#det_lbl_align').val(lblStyle.textAlign || ''); $('#det_lbl_weight').val(lblStyle.weight || ''); $('#det_lbl_size').val(lblStyle.size || '');
            if (lblStyle.color) { $('#det_lbl_color').val(lblStyle.color); $('#det_lbl_color_en').prop('checked', true); } else { $('#det_lbl_color_en').prop('checked', false); }

            var behavior = f.behavior || {};
            $('#det_beh_placeholder').val(behavior.placeholder || ''); $('#det_beh_prefix').val(behavior.prefix || ''); $('#det_beh_suffix').val(behavior.suffix || ''); $('#det_beh_thousands').val(behavior.thousands || ''); $('#det_beh_decimals').val(behavior.decimals !== undefined ? behavior.decimals : ''); $('#det_beh_counter').prop('checked', behavior.counter === true); $('#det_beh_select2').prop('checked', behavior.searchSelect === true); $('#det_beh_btnStyle').val(behavior.btnStyle || 'primary'); $('#det_beh_btnUrl').val(behavior.btnUrl || ''); $('#det_beh_btnTarget').val(behavior.btnTarget || '_self'); $('#det_beh_btn3d').prop('checked', behavior.btn3d === true);

            if (!f.events) f.events = {};
            if(window.app && window.app.setEditorValue) {
                window.app.setEditorValue('det_evt_onchange', f.events.onChange || '');
                window.app.setEditorValue('det_evt_onclick', f.events.onClick || '');
                window.app.setEditorValue('det_evt_onfocus', f.events.onFocus || '');
                window.app.setEditorValue('det_evt_onblur', f.events.onBlur || '');
                window.app.setEditorValue('det_evt_onkeypress', f.events.onKeyPress || '');
            } else {
                $('#det_evt_onchange').val(f.events.onChange || '');
                $('#det_evt_onclick').val(f.events.onClick || '');
                $('#det_evt_onfocus').val(f.events.onFocus || '');
                $('#det_evt_onblur').val(f.events.onBlur || '');
                $('#det_evt_onkeypress').val(f.events.onKeyPress || '');
            }

            $('#config-main-view').hide();  
            $('#config-detail-view').show();
            
            // Re-bind change events for live preview logic
            $('#det_type, #det_lookup_type').off('change').on('change', function() { window.app.updateBehaviorVisibility(); window.app.updateFieldPreview(); });
            $('#det_label, #det_val_max, #det_beh_placeholder').off('keyup').on('keyup', function() { window.app.updateFieldPreview(); });
            $('#det_required, #det_readonly').off('change').on('change', function() { window.app.updateFieldPreview(); });
            
            window.app.updateBehaviorVisibility();
            window.app.updateFieldPreview(); // Initial preview render!
        };

        GridApp.prototype.scrapeDetailView = function (f) {
            f.fieldTyp = $('#det_type').val(); 
            f.label = $('#det_label').val(); 
            f.width = parseInt($('#det_width').val()) || 100;
            
            var lType = $('#det_lookup_type').val();
            if (lType === '') { delete f.lookup; } else if (lType === 'sql') { f.lookup = { type: 'sql', source: window.app && window.app.getEditorValue ? window.app.getEditorValue('det_sql_query') : $('#det_sql_query').val() }; } else if (lType === 'manual') { var man = {}; $('#det_manual_rows .lookup-row').each(function () { var k = $(this).find('.lookup-key').val(); var v = $(this).find('.lookup-val').val(); if (k) man[k] = v; }); f.lookup = { manual: man }; }
            
            f.val_req = $('#det_val_req').is(':checked'); 
            f.val_min = parseInt($('#det_val_min').val()) || undefined; 
            f.val_max = parseInt($('#det_val_max').val()) || undefined; 
            f.val_regex = $('#det_val_regex').val(); 
            f.val_regex_msg = $('#det_val_regex_msg').val();

            f.style = {};
            var align = $('#det_style_align').val(); if (align) f.style.textAlign = align;
            var sw = $('#det_style_weight').val(); if (sw) f.style.weight = sw;
            var ss = $('#det_style_size').val(); if (ss) f.style.size = ss;
            if ($('#det_style_color_en').is(':checked')) f.style.color = $('#det_style_color').val();
            if ($('#det_style_bg_en').is(':checked')) f.style.backgroundColor = $('#det_style_bg').val();
            
            f.lblStyle = {};
            var lba = $('#det_lbl_align').val(); if (lba) f.lblStyle.textAlign = lba;
            var lw = $('#det_lbl_weight').val(); if (lw) f.lblStyle.weight = lw;
            var ls = $('#det_lbl_size').val(); if (ls) f.lblStyle.size = ls;
            if ($('#det_lbl_color_en').is(':checked')) f.lblStyle.color = $('#det_lbl_color').val();
            
            f.behavior = {};
            var placeholder = $('#det_beh_placeholder').val(); if (placeholder) f.behavior.placeholder = placeholder;
            var prefix = $('#det_beh_prefix').val(); if (prefix) f.behavior.prefix = prefix;
            var suffix = $('#det_beh_suffix').val(); if (suffix) f.behavior.suffix = suffix;
            var thousand = $('#det_beh_thousands').val(); if (thousand) f.behavior.thousands = thousand;
            var decimals = $('#det_beh_decimals').val(); if (decimals !== '') f.behavior.decimals = parseInt(decimals);
            f.behavior.counter = $('#det_beh_counter').is(':checked');
            f.behavior.searchSelect = $('#det_beh_select2').is(':checked');
            f.behavior.btnStyle = $('#det_beh_btnStyle').val();
            f.behavior.btnUrl = $('#det_beh_btnUrl').val();
            f.behavior.btnTarget = $('#det_beh_btnTarget').val();
            f.behavior.btn3d = $('#det_beh_btn3d').is(':checked');

            if (!f.events) f.events = {};
            if(window.app && window.app.getEditorValue) {
                f.events.onChange = window.app.getEditorValue('det_evt_onchange');
                f.events.onClick = window.app.getEditorValue('det_evt_onclick');
                f.events.onFocus = window.app.getEditorValue('det_evt_onfocus');
                f.events.onBlur = window.app.getEditorValue('det_evt_onblur');
                f.events.onKeyPress = window.app.getEditorValue('det_evt_onkeypress');
            } else {
                f.events.onChange = $('#det_evt_onchange').val();
                f.events.onClick = $('#det_evt_onclick').val();
                f.events.onFocus = $('#det_evt_onfocus').val();
                f.events.onBlur = $('#det_evt_onblur').val();
                f.events.onKeyPress = $('#det_evt_onkeypress').val();
            }
        };

        GridApp.prototype.buildGeneralForm = function () { return ''; }; // Deprecated since we use static form in sidebar

        GridApp.prototype.buildFieldsTable = function () {
    var self = this;
    var h = '<table class="table table-sm table-hover"><thead><tr><th>Feld</th><th>Label</th><th class="text-center">Sort</th><th class="text-center">Req</th><th class="text-center">RO</th><th class="text-center" title="Auf Handy anzeigen (Klick auf Icon umzuschalten)"><i class="fas fa-mobile-alt" style="cursor:pointer;" onclick="$(\'.cfg-f-mob\').each(function(){ $(this).prop(\'checked\', !$(this).prop(\'checked\')); }); window.getGrid(\'' + self.containerId + '\').scrapeConfigFromUI(); window.getGrid(\'' + self.containerId + '\').saveConfig();"></i></th><th></th></tr></thead><tbody>';
    this.config.fields.forEach(function (f, i) {
        var isSort = (self.sortField === f.fieldName);
        h += '<tr data-idx="' + i + '">';
        h += '<td><small class="font-weight-bold">' + f.fieldName + '</small></td>';
        h += '<td><input class="form-control form-control-sm cfg-f-label" value="' + (f.label || f.fieldName) + '"></td>';
        h += '<td class="text-center"><div class="d-flex justify-content-center align-items-center"><input type="radio" name="def_sort" class="cfg-f-sort mr-1" value="' + f.fieldName + '" ' + (isSort ? 'checked' : '') + '><input type="checkbox" class="cfg-f-desc" ' + (isSort && self.sortOrder == 'DESC' ? 'checked' : '') + '></div></td>';
        h += '<td class="text-center"><input type="checkbox" class="cfg-f-req" ' + (f.required ? 'checked' : '') + '></td>';
        h += '<td class="text-center"><input type="checkbox" class="cfg-f-ro" ' + (f.readonly ? 'checked' : '') + '></td>';
        h += '<td class="text-center" title="Auf Handy anzeigen"><input type="checkbox" class="cfg-f-mob" ' + (f.hide_on_mobile ? '' : 'checked') + '></td>';
        h += '<td class="text-right"><button class="btn btn-sm btn-light border" onclick="window.getGrid(\'' + self.containerId + '\').editFieldSettings(' + i + ')"><i class="fas fa-cog"></i></button></td>';
        h += '</tr>';
    });
    h += '</tbody></table>';
    h += '<div class="mt-3 text-center"><button class="btn btn-sm btn-outline-secondary" onclick="window.getGrid(\'' + self.containerId + '\').addCustomField()"><i class="fas fa-plus"></i> Benutzerdefiniertes Feld hinzufügen</button></div>';
    return h;
};

        GridApp.prototype.addCustomField = function() {
            var fName = prompt("Name des neuen Feldes (z.B. custom_btn_1):");
            if (!fName || fName.trim() === '') return;
            fName = fName.trim();
            if (this.config.fields.find(function(f) { return f.fieldName === fName; })) {
                alert("Ein Feld mit diesem Namen existiert bereits!");
                return;
            }
            this.config.fields.push({
                fieldName: fName,
                label: 'Neues Feld',
                fieldTyp: 'button',
                isCustom: true,
                width: 150
            });
            this.fields = this.config.fields;
            this.saveConfig();
            $('#content-fld').html(this.buildFieldsTable());
        };

        GridApp.prototype.scrapeConfigFromUI = function () {
            this.config.numberPerPage = parseInt($('#cfg_numberPerPage').val()); this.config.row_color_odd = $('#cfg_row_color_odd').val(); this.config.row_color_even = $('#cfg_row_color_even').val(); this.config.mobile_mode = $('#cfg_mobile_mode').val(); this.config.form_simple_grid = $('#cfg_form_simple_grid').val();
            this.config.evt_onload = window.app && window.app.getEditorValue ? window.app.getEditorValue('cfg_evt_onload') : $('#cfg_evt_onload').val();
            this.config.evt_onbeforesave = window.app && window.app.getEditorValue ? window.app.getEditorValue('cfg_evt_onbeforesave') : $('#cfg_evt_onbeforesave').val();
            this.config.evt_onaftersave = window.app && window.app.getEditorValue ? window.app.getEditorValue('cfg_evt_onaftersave') : $('#cfg_evt_onaftersave').val();
            this.config.evt_libraries = window.app && window.app.getEditorValue ? window.app.getEditorValue('cfg_evt_libraries') : $('#cfg_evt_libraries').val();
            
            if (!this.config.apiKeys) this.config.apiKeys = {};
            this.config.apiKeys.gemini = $('#cfg_api_gemini').val();
            this.config.apiKeys.chatgpt = $('#cfg_api_chatgpt').val();
            this.config.apiKeys.anthropic = $('#cfg_api_anthropic').val();            
            var self = this; $('#content-fld tbody tr').each(function () { var idx = $(this).data('idx'); var f = self.config.fields[idx]; f.label = $(this).find('.cfg-f-label').val(); f.required = $(this).find('.cfg-f-req').is(':checked'); f.readonly = $(this).find('.cfg-f-ro').is(':checked'); f.hide_on_mobile = !$(this).find('.cfg-f-mob').is(':checked'); if ($(this).find('.cfg-f-sort').is(':checked')) { self.sortField = f.fieldName; self.sortOrder = $(this).find('.cfg-f-desc').is(':checked') ? 'DESC' : 'ASC'; } });
        };

        GridApp.prototype.renderHeaders = function () {
            var self = this; var hHtml = ''; var fHtml = ''; var gid = this.containerId;
            this.fields.forEach(function (f) {
                var w = f.width ? f.width + 'px' : '150px';
                var sIcon = '';
                if (self.sortField === f.fieldName) sIcon = (self.sortOrder === 'ASC') ? ' <i class="fas fa-sort-up sort-indicator"></i>' : ' <i class="fas fa-sort-down sort-indicator"></i>';
                else sIcon = ' <i class="fas fa-sort text-muted small" style="opacity:0.3"></i>';
                
                var hIcon = f.helpText ? ' <i class="fas fa-info-circle text-info ms-1" title="' + f.helpText.replace(/"/g, '&quot;') + '"></i>' : '';
                
                                var mobHid = f.hide_on_mobile ? ' mobile-hidden' : '';
                hHtml += '<th class="' + mobHid + '" data-field="' + f.fieldName + '" style="width:' + w + '; min-width:' + w + '"><div class="d-flex justify-content-between align-items-center" style="cursor:pointer; width:100%; height:100%" onclick="window.getGrid(\'' + gid + '\').toggleSort(\'' + f.fieldName + '\')"><span>' + (f.label || f.fieldName) + hIcon + sIcon + '</span></div><div class="col-resizer" onmousedown="window.getGrid(\'' + gid + '\').startResize(event, \'' + f.fieldName + '\')"></div></th>';
                var inp = '';
                if (['image', 'signature', 'htmlEditor', 'youTube', 'button', 'custom'].indexOf(f.fieldTyp) !== -1) inp = '<input type="text" class="header-search bg-light" disabled>';
                else if (['select', 'radio', 'checkbox', 'double_select'].indexOf(f.fieldTyp) !== -1) { var opts = self.lookups[f.fieldName] || {}; inp = '<select class="header-search" data-field="' + f.fieldName + '" onchange="window.getGrid(\'' + gid + '\').handleFilter(this)"><option value="">Alle</option>'; for (var k in opts) inp += '<option value="' + k + '">' + opts[k] + '</option>'; inp += '</select>'; }
                else if (f.fieldTyp === 'date') inp = '<input type="date" class="header-search" data-field="' + f.fieldName + '" onchange="window.getGrid(\'' + gid + '\').handleFilter(this)">';
                else inp = '<input type="text" class="header-search" data-field="' + f.fieldName + '" onkeyup="window.getGrid(\'' + gid + '\').handleFilter(this)">';
                fHtml += '<th class="' + mobHid + '">' + inp + '</th>';
            });
            var infoHtml = (self.config.mobile_mode === 'modal') ? '<div class="d-md-none text-primary small mt-1" style="line-height:1;"><i class="fas fa-info-circle"></i></div>' : '';
            hHtml = '<th style="width:70px; position:sticky; left:0; z-index:100; background:var(--ag-color-surface, #fff); box-shadow: 1px 0 0 var(--ag-color-border, #ccc);">' + infoHtml + '</th>' + hHtml; 
            fHtml = '<th style="position:sticky; left:0; z-index:100; background:var(--ag-color-surface, #fff); box-shadow: 1px 0 0 var(--ag-color-border, #ccc);"></th>' + fHtml;
            var hEl = this.container.find('.grid-headers'); hEl.html(hHtml); this.container.find('.grid-filters').html(fHtml);
            hEl.sortable({ axis: "x", containment: "parent", tolerance: "pointer", stop: function (e, ui) { var newOrder = hEl.sortable("toArray", { attribute: "data-field" }); var newFields = []; newOrder.forEach(function (fn) { var obj = self.fields.find(function (o) { return o.fieldName === fn; }); if (obj) newFields.push(obj); }); self.fields = newFields; self.config.fields = self.fields; self.saveConfig(); self.renderHeaders(); self.renderBody(self.data); $('#content-fld').html(self.buildFieldsTable()); } }).disableSelection();
        };

        // FIX: Map Open Function Bridge
        GridApp.prototype.openMap = function (id, f, val) {
            mapManager.openMap(this.containerId, id, f, val);
        };

        GridApp.prototype.startResize = function (e, fieldName) { e.preventDefault(); e.stopPropagation(); var self = this; this.resizingField = fieldName; this.startX = e.pageX; var fieldObj = this.fields.find(function (f) { return f.fieldName == fieldName; }); this.startWidth = fieldObj.width || 150; $(document).on('mousemove.gridresize', function (em) { var diff = em.pageX - self.startX; self.container.find('th[data-field="' + fieldName + '"]').css('width', Math.max(50, self.startWidth + diff) + 'px'); }); $(document).on('mouseup.gridresize', function (em) { $(document).off('.gridresize'); var diff = em.pageX - self.startX; self.updateFieldWidth(fieldName, Math.max(50, self.startWidth + diff)); }); };
        GridApp.prototype.updateFieldWidth = function (fn, w) { var f = this.fields.find(function (o) { return o.fieldName == fn; }); if (f.width == w) return; f.width = w; this.saveConfig(); };
        GridApp.prototype.toggleSort = function (f) { this.sortField = f; this.sortOrder = (this.sortOrder === 'ASC') ? 'DESC' : 'ASC'; this.config.sortField = this.sortField; this.config.sortOrder = this.sortOrder; this.saveConfig(); this.renderHeaders(); this.loadData(); };
        GridApp.prototype.loadData = function () { var self = this; this.setStatus('Lade...'); $.post(AJAX_URL, { action: 'load_data', gridName: this.gridName, page: this.currentPage, limit: this.limit, filters: this.filters, sortField: this.sortField, sortOrder: this.sortOrder }, function (res) { if (res.rows) { self.data = res.rows; self.renderBody(res.rows); self.container.find('.curr-page').text(self.currentPage); self.setStatus('Bereit'); } }, 'json'); };
        GridApp.prototype.renderBody = function (rows) { var self = this; var html = ''; var gid = this.containerId; if (!rows || rows.length === 0) { html = '<tr><td colspan="' + (this.fields.length + 1) + '" class="text-center p-4">Keine Daten</td></tr>'; } else { rows.forEach(function (row) { html += '<tr data-id="' + row.id + '">'; var editBtn = (self.config.mobile_mode === 'modal') ? '<button class="btn btn-sm btn-outline-primary border-0 mr-1 d-md-none" onclick="window.getGrid(\'' + gid + '\').openRowModal(' + row.id + ')" title="Bearbeiten"><i class="fas fa-pencil-alt"></i></button>' : ''; html += '<td class="text-center" style="position:sticky; left:0; z-index:90; background:var(--ag-color-surface, #fff); box-shadow: 1px 0 0 var(--ag-color-border, #ccc);">' + editBtn + '<button class="btn btn-sm btn-outline-danger border-0" onclick="window.getGrid(\'' + gid + '\').deleteRecord(' + row.id + ')"><i class="fas fa-trash-alt"></i></button></td>'; self.fields.forEach(function (f) { var val = row[f.fieldName]; if (val === undefined || val === null) val = ''; if (val === '' && f.defaultValue) val = f.defaultValue; var filterVal = self.filters[f.fieldName]; var hl = (filterVal && val.toString().toLowerCase().indexOf(filterVal.toLowerCase()) !== -1) ? 'input-match' : ''; var mobHid = f.hide_on_mobile ? ' class="mobile-hidden"' : ''; html += '<td ' + mobHid + ' data-field="' + f.fieldName + '">' + self.getInputHtml(f, val, row, hl, filterVal) + '</td>'; }); html += '</tr>'; }); } this.container.find('.grid-body').html(html); };

        // FIX: openSignature pass val
                        GridApp.prototype.getInputHtml = function (f, value, row, hlClass, filterVal) {
            var self = this; var gid = this.containerId; var id = row.id;
            if (value === undefined || value === null) value = '';
            
            // Build inline styles & classes from field config
            var inlineStyle = '';
            var twClasses = 'cell-edit-input form-control form-control-sm ' + hlClass + (f.required ? ' required-field' : '');
            if (f.style) {
                if (f.style.color) inlineStyle += 'color: ' + f.style.color + ' !important; ';
                if (f.style.backgroundColor) inlineStyle += 'background-color: ' + f.style.backgroundColor + ' !important; ';
                if (f.style.textAlign) inlineStyle += 'text-align: ' + f.style.textAlign + '; ';
                if (f.style.weight) {
                    if (f.style.weight === 'bold') twClasses += ' font-bold';
                    else inlineStyle += 'font-weight: ' + f.style.weight + '; ';
                }
                if (f.style.size) inlineStyle += 'font-size: ' + f.style.size + '; ';
            }
            
            var ph = f.behavior && f.behavior.placeholder ? ' placeholder="' + f.behavior.placeholder + '"' : '';
            var maxLenAttr = (f.val_max && f.fieldTyp !== 'integer' && f.fieldTyp !== 'decimal') ? ' maxlength="' + f.val_max + '"' : '';

            // Event bindings
            var evtClick = f.events && f.events.onClick ? ' onclick="window.getGrid(\'' + gid + '\').triggerEvent(' + id + ', \'onClick\', \'' + f.fieldName + '\')"' : '';
            var evtFocus = ' onfocus="window.getGrid(\'' + gid + '\').triggerEvent(' + id + ', \'onFocus\', \'' + f.fieldName + '\')"';
            var evtKeyPress = f.events && f.events.onKeyPress ? ' onkeyup="window.getGrid(\'' + gid + '\').triggerEvent(' + id + ', \'onKeyPress\', \'' + f.fieldName + '\')"' : '';
            
            // For live counter update in grid cells
            var evtInput = '';
            if (f.behavior && f.behavior.counter && (f.fieldTyp !== 'integer' && f.fieldTyp !== 'decimal' && f.fieldTyp !== 'date' && f.fieldTyp !== 'button' && f.fieldTyp !== 'checkbox' && f.fieldTyp !== 'radio' && f.fieldTyp !== 'select' && f.fieldTyp !== 'double_select')) {
                var cMax = f.val_max || 'Max';
                evtInput = ' oninput="$(this).closest(\'div.position-relative\').find(\'div.text-muted\').text(($(this).val() || \'\').length + \' / ' + cMax + '\');" ';
            }

            var common = 'class="' + twClasses + '" style="' + inlineStyle + '" data-orig="' + value + '" data-field="' + f.fieldName + '"' + ph + maxLenAttr;
            common += ' onblur="window.getGrid(\'' + gid + '\').onBlur(this, \'' + f.fieldName + '\')"' + evtFocus + evtClick + evtKeyPress + evtInput;
            
            function hl(txt, s) { if (!s || !txt) return txt; var re = new RegExp("(" + s + ")", "gi"); return txt.toString().replace(re, '<mark>$1</mark>'); }
            
            // Button Type logic
            if (f.fieldTyp === 'button') {
                var btnStyle = (f.behavior && f.behavior.btnStyle) ? f.behavior.btnStyle : 'primary';
                var btnSize = 'btn-sm'; // default
                if (f.style && f.style.size) {
                    if (f.style.size.indexOf('18') !== -1 || f.style.size.indexOf('large') !== -1) btnSize = 'btn-lg';
                }
                var cssClasses = 'btn ' + btnSize + ' btn-' + btnStyle;
                if (f.behavior && f.behavior.btn3d) cssClasses += ' shadow-sm border-bottom border-dark'; // Simple 3D mock class
                var btnUrl = (f.behavior && f.behavior.btnUrl) ? f.behavior.btnUrl : '';
                var target = (f.behavior && f.behavior.btnTarget) ? f.behavior.btnTarget : '_self';
            
                var clk = "if('" + btnUrl + "') { window.open('" + btnUrl + "', '" + target + "'); } else { window.getGrid('" + gid + "').triggerEvent(" + id + ", 'onClick', '" + f.fieldName + "'); }";
                return '<button type="button" class="' + cssClasses + ' w-100" style="' + inlineStyle + '" onclick="' + clk + '" data-field="' + f.fieldName + '">' + (f.label || f.fieldName) + '</button>';
            }

            if (f.readonly === true || f.readonly === 'true') { 
                if (f.fieldTyp == 'image') { var ip = row[f.fieldName + '_preview']; return ip ? '<img src="' + ip + '" class="img-thumbnail-custom">' : '<span class="text-muted small">Leer</span>'; } 
                return '<div ' + common + ' style="' + inlineStyle + ' background-color:transparent!important; border:none!important;" readonly disabled>' + value + '</div>'; 
            }
            
            var retHtml = '';
            switch (f.fieldTyp) {
                case 'multiple_line_text': retHtml = '<textarea ' + common + ' rows="1" style="' + inlineStyle + ' resize:vertical">' + value + '</textarea>'; break;
                case 'email': retHtml = '<div class="input-group input-group-sm"><input type="email" value="' + value + '" ' + common + '><div class="input-group-append"><a href="mailto:' + value + '" class="btn btn-outline-secondary btn-icon" target="_blank"><i class="fas fa-envelope"></i></a></div></div>'; break;
                case 'url': var link = (value && value.indexOf('http') !== 0) ? 'https://' + value : value; retHtml = '<div class="input-group input-group-sm"><input type="url" value="' + value + '" ' + common + '><div class="input-group-append"><a href="' + link + '" class="btn btn-outline-secondary btn-icon" target="_blank"><i class="fas fa-external-link-alt"></i></a></div></div>'; break;
                case 'select': var opts = this.lookups[f.fieldName] || {}; var sel = '<select ' + common + ' onchange="window.getGrid(\'' + gid + '\').onChange(this, \'' + f.fieldName + '\')"><option value=""></option>'; for (var k in opts) sel += '<option value="' + k + '" ' + (String(k) === String(value) ? 'selected' : '') + '>' + opts[k] + '</option>'; retHtml = sel + '</select>'; break;
                case 'radio': var rOpts = this.lookups[f.fieldName] || {}; var rHtml = '<div class="btn-group btn-group-sm btn-group-toggle" data-toggle="buttons" data-orig="' + value + '">'; for (var k in rOpts) rHtml += '<label class="btn btn-outline-secondary ' + (k == value ? 'active' : '') + '" onclick="window.getGrid(\'' + gid + '\').handleRadio(this, ' + id + ', \'' + f.fieldName + '\', \'' + k + '\')"><input type="radio" ' + (k == value ? 'checked' : '') + '> ' + rOpts[k] + '</label>'; retHtml = rHtml + '</div>'; break;
                case 'checkbox': case 'double_select': var cnt = value ? value.split(',').length : 0; retHtml = '<button class="btn btn-sm btn-light border btn-block text-left" onclick="window.getGrid(\'' + gid + '\').openMultiSelectModal(' + id + ', \'' + f.fieldName + '\', \'' + value + '\')">' + cnt + ' gewählt <i class="fas fa-caret-down float-right text-muted"></i></button>'; break;
                case 'htmlEditor': var strip = value ? value.replace(/<[^>]*>?/gm, '').substring(0, 30) : 'Leer'; retHtml = '<div class="html-preview-box ' + hlClass + '" onclick="window.getGrid(\'' + gid + '\').openHtmlEditor(' + id + ', \'' + f.fieldName + '\')">' + hl(strip, filterVal) + ' <i class="fas fa-pen float-right text-muted"></i><div id="html_store_' + id + '_' + f.fieldName + '" style="display:none">' + value + '</div></div>'; break;
                case 'image': var hasImg = row[f.fieldName + '_has_image']; var imgP = row[f.fieldName + '_preview']; var disp = hasImg ? '<img src="' + imgP + '" class="img-thumbnail-custom" onclick="window.getGrid(\'' + gid + '\').loadAndShowPreview(' + id + ', \'' + f.fieldName + '\')" title="Zoom">' : '<span class="small text-muted">Leer</span>'; var btns = '<div class="d-flex flex-column ml-2 gap-1"><i class="fas fa-camera btn-icon" onclick="window.getGrid(\'' + gid + '\').openUpload(' + id + ', \'' + f.fieldName + '\')" title="Upload"></i>'; if (hasImg) btns += '<i class="fas fa-download btn-icon" onclick="window.getGrid(\'' + gid + '\').downloadFullImage(' + id + ', \'' + f.fieldName + '\')" title="Save"></i>'; btns += '</div>'; retHtml = '<div class="d-flex align-items-center">' + disp + btns + '</div>'; break;
                case 'signature': var sigP = row[f.fieldName + '_preview']; var sigImg = sigP ? '<img src="' + sigP + '" height="30" class="border">' : 'Leer'; retHtml = '<div class="d-flex align-items-center justify-content-between" style="cursor:pointer" onclick="window.getGrid(\'' + gid + '\').openSignature(' + id + ', \'' + f.fieldName + '\')">' + sigImg + ' <i class="fas fa-pen-nib text-muted"></i></div>'; break;
                case 'youTube': var vidId = ''; if (value) { var m = value.match(/v=([^&]+)/); if (m) vidId = m[1]; } var thumb = vidId ? '<img src="https://img.youtube.com/vi/' + vidId + '/default.jpg" height="30" class="mr-1">' : ''; var ytLink = vidId ? '<a href="' + value + '" target="_blank" class="ml-1 text-danger btn-icon"><i class="fab fa-youtube"></i></a>' : ''; retHtml = '<div class="d-flex align-items-center">' + thumb + '<input type="text" value="' + value + '" ' + common + ' placeholder="YouTube URL">' + ytLink + '</div>'; break;
                case 'GoogleMaps': retHtml = '<div class="input-group input-group-sm"><input type="text" value="' + value + '" ' + common + '><div class="input-group-append"><button class="btn btn-outline-success btn-icon" onclick="window.getGrid(\'' + gid + '\').openMap(' + id + ', \'' + f.fieldName + '\', \'' + value + '\')"><i class="fas fa-map-marker-alt"></i></button></div></div>'; break;
                case 'date': retHtml = '<input type="date" value="' + value + '" ' + common + ' onchange="window.getGrid(\'' + gid + '\').onChange(this, \'' + f.fieldName + '\')">'; break;
                case 'date_time': 
                    var dtVal = value ? value.replace(' ', 'T') : '';
                    retHtml = '<input type="datetime-local" value="' + dtVal + '" ' + common + ' onchange="window.getGrid(\'' + gid + '\').onChange(this, \'' + f.fieldName + '\')">'; break;
                case 'integer': retHtml = '<input type="number" step="1" value="' + value + '" ' + common + '>'; break;
                case 'decimal': case 'currency': 
                    var step = (f.behavior && f.behavior.decimals) ? Math.pow(10, -parseInt(f.behavior.decimals)) : '0.01';
                    if (f.behavior && f.behavior.decimals !== undefined && f.behavior.decimals !== '' && value !== '') {
                        var parsed = parseFloat((''+value).replace(',', '.'));
                        if (!isNaN(parsed)) value = parsed.toFixed(parseInt(f.behavior.decimals));
                    }
                    retHtml = '<input type="number" step="' + step + '" value="' + value + '" ' + common + '>'; 
                    break;
                default: 
                    if (f.behavior && (f.behavior.prefix || f.behavior.suffix)) {
                        var pfx = f.behavior.prefix ? '<div class="input-group-prepend"><span class="input-group-text bg-light text-muted px-2">' + f.behavior.prefix + '</span></div>' : '';
                        var sfx = f.behavior.suffix ? '<div class="input-group-append"><span class="input-group-text bg-light text-muted px-2">' + f.behavior.suffix + '</span></div>' : '';
                        retHtml = '<div class="input-group input-group-sm">' + pfx + '<input type="text" value="' + value + '" ' + common + '>' + sfx + '</div>';
                    } else {
                        retHtml = '<input type="text" value="' + value + '" ' + common + '>';
                    }
                    break;
            }

            // Append character counter wrapper if enabled
            if (f.behavior && f.behavior.counter && (f.fieldTyp !== 'integer' && f.fieldTyp !== 'decimal' && f.fieldTyp !== 'date' && f.fieldTyp !== 'button' && f.fieldTyp !== 'checkbox' && f.fieldTyp !== 'radio' && f.fieldTyp !== 'select' && f.fieldTyp !== 'double_select')) {
                var max = f.val_max || 'Max';
                var currentLen = (value || '').toString().length;
                retHtml = '<div class="position-relative">' + retHtml + '<div class="text-muted text-right" style="font-size:10px; line-height:1; margin-top:2px;">' + currentLen + ' / ' + max + '</div></div>';
            }

            return retHtml;
        };

        GridApp.prototype.triggerEvent = function(id, evtName, fName, isSystemTrigger) {
            if ($('#config-sidebar').is(':visible')) return; // Do not fire events in design mode
            var fConf = this.config.fields.find(function(c) { return c.fieldName == fName; });
            if (!fConf || !fConf.events || !fConf.events[evtName]) return;
            
            if (!this.eventCallStack) this.eventCallStack = [];
            var stackSizeLimit = 10;
            var eventSignature = evtName + '_' + id + '_' + fName;
            
            if (isSystemTrigger) {
                this.eventCallStack.push(eventSignature);
                if (this.eventCallStack.length > stackSizeLimit) {
                    console.error('Endlosschleife blockiert!', this.eventCallStack);
                    this.setStatus("Fehler: Endlosschleife ('" + fName + "') wegen zu großer Tiefe blockiert!", 'danger');
                    this.eventCallStack = []; 
                    return;
                }
            } else {
                this.eventCallStack = [eventSignature];
            }
            
            var self = this;
            var formData = {};
            // Gather form data ONLY from the row with this ID!
            var tr = $('tr[data-id="' + id + '"]');
            tr.find('[data-field]').each(function() {
                var fieldName = $(this).data('field');
                if (fieldName) {
                    var inputEl = $(this).find('input, select, textarea');
                    if (inputEl.length) {
                        formData[fieldName] = inputEl.val();
                    } else {
                        var divEl = $(this).find('div[readonly]');
                        if (divEl.length) formData[fieldName] = divEl.text();
                    }
                }
            });
            
            $.post(AJAX_URL, {
                action: 'execute_event',
                gridName: this.gridName,
                tableName: this.tableName,
                id: id,
                eventName: evtName,
                fieldName: fName,
                formData: JSON.stringify(formData)
            }, function(res) {
                if (res.status === 'ok' && res.data) {
                    for (var key in res.data) {
                        if (res.data.hasOwnProperty(key)) {
                            var td = tr.find('td[data-field="' + key + '"]');
                            var inputEl = td.find('input.cell-edit-input, select.cell-edit-input, textarea.cell-edit-input');
                            if (inputEl.length && inputEl.val() != res.data[key]) {
                                inputEl.val(res.data[key]);
                                self.saveCell(id, key, res.data[key]); // Actually persist to DB natively
                                
                                inputEl.addClass('bg-success text-white');
                                setTimeout((function(elem) { return function(){elem.removeClass('bg-success text-white');}; })(inputEl), 1000);
                                
                                // Cascade changes
                                self.triggerEvent(id, 'onChange', key, true);
                            } else if (!inputEl.length) {
                                var divEl = td.find('div[readonly]');
                                if (divEl.length && divEl.text() != res.data[key]) {
                                     divEl.text(res.data[key]);
                                     self.saveCell(id, key, res.data[key]);
                                     
                                     divEl.addClass('bg-success text-white');
                                     setTimeout((function(elem) { return function(){elem.removeClass('bg-success text-white');}; })(divEl), 1000);
                                     
                                     // Cascade changes
                                     self.triggerEvent(id, 'onChange', key, true);
                                }
                            }
                        }
                    }
                } else if (res.error) {
                    self.setStatus("Fehler in " + evtName + " ("+fName+"): " + res.error, 'danger');
                }
                self.eventCallStack.pop();
            }, 'json').fail(function() {
                self.setStatus("Server-Fehler bei Event " + evtName, 'danger');
                self.eventCallStack.pop();
            });
        };

        GridApp.prototype.formatDecimalField = function(el, decimals) {
            var valStr = $(el).val();
            if (!valStr) return;
            var parsed = parseFloat(valStr.replace(',', '.'));
            if (!isNaN(parsed)) {
                el.value = parsed.toFixed(decimals);
            }
        };

        GridApp.prototype.onBlur = function (el, f) { 
            var fConf = this.config.fields.find(function(c) { return c.fieldName == f; });
            if (fConf && (fConf.fieldTyp === 'decimal' || fConf.fieldTyp === 'currency') && fConf.behavior && fConf.behavior.decimals !== undefined && fConf.behavior.decimals !== '') {
                this.formatDecimalField(el, parseInt(fConf.behavior.decimals));
            }
            var id = $(el).closest('tr').data('id');
            this.commitChange(id, f, $(el).val(), $(el).data('orig')); 
            this.triggerEvent(id, 'onBlur', f);
        };
        GridApp.prototype.onChange = function (el, f) { 
            var id = $(el).closest('tr').data('id');
            this.commitChange(id, f, $(el).val(), $(el).data('orig')); 
        };
        GridApp.prototype.handleRadio = function (lbl, id, f, n) { var c = $(lbl).parent(); var o = c.data('orig'); if (String(n) !== String(o)) { this.commitChange(id, f, n, o); c.data('orig', n); } };
        GridApp.prototype.commitChange = function (id, f, n, o) { 
             if (String(n) === String(o)) return; 
             this.saveCell(id, f, n); 
             if (this.historyStep < this.history.length - 1) this.history = this.history.slice(0, this.historyStep + 1); 
             this.history.push({ id: id, field: f, oldVal: o, newVal: n }); 
             this.historyStep++; this.updateUndoButtons(); 
             this.updateUiCell(id, f, n); 
             this.triggerEvent(id, 'onChange', f);
        };

        GridApp.prototype.updateUiCell = function (id, f, val) {
            var td = $('tr[data-id="' + id + '"] td[data-field="' + f + '"]');
            var input = td.find('.cell-edit-input');
            if (input.length) { input.val(val).data('orig', val).addClass('changed-cell'); setTimeout(function () { input.removeClass('changed-cell'); }, 1000); }
            // HTML
            if (td.find('.html-preview-box').length) { var s = val ? val.replace(/<[^>]*>?/gm, '').substring(0, 30) : 'Leer'; td.find('.html-preview-box').html(s + ' <i class="fas fa-pen float-right text-muted"></i><div id="html_store_' + id + '_' + f + '" style="display:none">' + val + '</div>'); td.find('.html-preview-box').addClass('changed-cell'); setTimeout(function () { td.find('.html-preview-box').removeClass('changed-cell'); }, 1000); }
            // Radio
            if (td.find('.btn-group-toggle').length) { this.reload(); }
            // FIX: URL/Email Button Update
            var anchor = td.find('a.btn-icon');
            if (anchor.length) {
                var hrefVal = val;
                // Check context based on icon class or field type if possible (but checking string content is safer here)
                if (val && val.indexOf('http') !== 0 && val.indexOf('mailto') !== 0) {
                    if (anchor.find('.fa-envelope').length) hrefVal = 'mailto:' + val;
                    else if (anchor.find('.fa-external-link-alt').length) hrefVal = 'https://' + val;
                }
                anchor.attr('href', hrefVal);
            }
        };

        GridApp.prototype.saveCell = function (id, f, v) { $.post(AJAX_URL, { action: 'save_cell', tableName: this.tableName, id: id, field: f, value: v }); };
        GridApp.prototype.undo = function () { if (this.historyStep >= 0) { var a = this.history[this.historyStep]; this.revert(a.id, a.field, a.oldVal); this.historyStep--; this.updateUndoButtons(); } };
        GridApp.prototype.redo = function () { if (this.historyStep < this.history.length - 1) { this.historyStep++; var a = this.history[this.historyStep]; this.revert(a.id, a.field, a.newVal); this.updateUndoButtons(); } };
        GridApp.prototype.revert = function (id, f, val) { this.saveCell(id, f, val); var fieldObj = this.fields.find(function (fi) { return fi.fieldName == f; }); if (['radio', 'select', 'checkbox'].indexOf(fieldObj.fieldTyp) !== -1) this.reload(); else this.updateUiCell(id, f, val); };
        GridApp.prototype.updateUndoButtons = function () { this.container.find('.btn-undo').prop('disabled', this.historyStep < 0); this.container.find('.btn-redo').prop('disabled', this.historyStep >= this.history.length - 1); };
        GridApp.prototype.openHtmlEditor = function (id, f) { var self = this; var c = $('#html_store_' + id + '_' + f).html(); $('#dynamic-modals-container').html('<div class="modal fade" id="dynHtml"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h6 class="modal-title">HTML</h6><button type="button" class="close" data-dismiss="modal">×</button></div><div class="modal-body"><textarea id="tb"></textarea></div><div class="modal-footer"><button class="btn btn-primary btn-save">Speichern</button></div></div></div></div>'); $('#dynHtml').modal('show'); $('#tb').trumbowyg().trumbowyg('html', c); $('#dynHtml .btn-save').off('click').click(function () { var n = $('#tb').trumbowyg('html'); var o = $('#html_store_' + id + '_' + f).html(); self.commitChange(id, f, n, o); $('#dynHtml').modal('hide'); }); };
        GridApp.prototype.openMultiSelectModal = function (id, f, val) { var self = this; var o = this.lookups[f] || {}; var s = val ? val.split(',') : []; var h = '<div class="row">'; for (var k in o) h += '<div class="col-6"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input ms-chk" id="m_' + k + '" value="' + k + '" ' + (s.includes(k.toString()) ? 'checked' : '') + '><label class="custom-control-label" for="m_' + k + '">' + o[k] + '</label></div></div>'; $('#dynamic-modals-container').html('<div class="modal fade" id="dynMulti"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h6 class="modal-title">Auswahl</h6></div><div class="modal-body">' + h + '</div><div class="modal-footer"><button class="btn btn-primary btn-save">OK</button></div></div></div></div>'); $('#dynMulti').modal('show'); $('#dynMulti .btn-save').off('click').click(function () { var arr = []; $('#dynMulti .ms-chk:checked').each(function () { arr.push($(this).val()) }); self.commitChange(id, f, arr.join(','), val); $('#dynMulti').modal('hide'); }); };
        GridApp.prototype.openUpload = function (id, f) { var self = this; $('#dynamic-modals-container').html('<div class="modal fade" id="dynUp"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h6 class="modal-title">Upload</h6><button type="button" class="close" data-dismiss="modal">×</button></div><div class="modal-body"><div class="custom-file"><input type="file" class="custom-file-input" id="f-in"><label class="custom-file-label">Wählen...</label></div></div><div class="modal-footer"><button class="btn btn-primary btn-save">Upload</button></div></div></div></div>'); $('#dynUp').modal('show'); $('#f-in').on('change', function () { $(this).next().html($(this).val().split('\\').pop()); }); $('#dynUp .btn-save').off('click').click(function () { var file = $('#f-in').prop('files')[0]; if (!file) return; var fd = new FormData(); fd.append('action', 'upload_file'); fd.append('tableName', self.tableName); fd.append('id', id); fd.append('field', f); fd.append('file', file); $.ajax({ url: AJAX_URL, type: 'POST', data: fd, contentType: false, processData: false, success: function () { $('#dynUp').modal('hide'); self.reload(); } }); }); };

        // FIX: Signature with existing Image & Close Button
        GridApp.prototype.openSignature = function (id, f) {
            var self = this;
            // Fetch current value (Image URL)
            $.post(AJAX_URL, { action: 'load_full_image', tableName: this.tableName, id: id, field: f }, function (res) {
                var existingImg = (res.status == 'ok') ? res.data : null;

                $('#dynamic-modals-container').html('<div class="modal fade" id="dynSig"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h6 class="modal-title">Unterschrift</h6><button type="button" class="close" data-dismiss="modal">×</button></div><div class="modal-body text-center"><canvas id="sig-pad" width="400" height="200" class="border"></canvas><br><button class="btn btn-sm btn-light mt-2 btn-clear">Löschen</button></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button><button class="btn btn-primary btn-save">Speichern</button></div></div></div></div>');
                $('#dynSig').modal('show');

                var canvas = document.getElementById('sig-pad');
                var pad = new SignaturePad(canvas);

                // Draw existing
                if (existingImg) {
                    // SignaturePad needs FromDataURL
                    pad.fromDataURL(existingImg);
                }

                $('#dynSig .btn-clear').click(function () { pad.clear(); });
                $('#dynSig .btn-save').off('click').click(function () {
                    if (pad.isEmpty()) return;
                    fetch(pad.toDataURL()).then(function (r) { return r.blob() }).then(function (b) {
                        var fd = new FormData(); fd.append('action', 'upload_file'); fd.append('tableName', self.tableName); fd.append('id', id); fd.append('field', f); fd.append('file', b);
                        $.ajax({ url: AJAX_URL, type: 'POST', data: fd, contentType: false, processData: false, success: function () { $('#dynSig').modal('hide'); self.reload(); } });
                    });
                });
            }, 'json');
        };

        GridApp.prototype.loadAndShowPreview = function (id, f) { $.post(AJAX_URL, { action: 'load_full_image', tableName: this.tableName, id: id, field: f }, function (res) { if (res.status == 'ok') { $('#previewImageFull').attr('src', res.data); $('#imagePreviewModal').modal('show'); } }, 'json'); };
        GridApp.prototype.downloadFullImage = function (id, f) { $.post(AJAX_URL, { action: 'load_full_image', tableName: this.tableName, id: id, field: f }, function (res) { if (res.status == 'ok') { var a = document.createElement("a"); a.href = res.data; a.download = 'img_' + id + '.png'; document.body.appendChild(a); a.click(); document.body.removeChild(a); } }, 'json'); };
        GridApp.prototype.saveConfig = function () { $.post(AJAX_URL, { action: 'save_config', gridName: this.gridName, config: JSON.stringify(this.config) }); };
        GridApp.prototype.setStatus = function (msg, type) { this.container.find('.status-msg').text(msg).attr('class', 'status-msg badge badge-' + (type || 'light') + ' border'); };
        GridApp.prototype.checkSchema = function (auto) { var self = this; $.post(AJAX_URL, { action: 'check_schema', gridName: this.gridName, tableName: this.tableName, currentFields: JSON.stringify(this.fields) }, function (res) { if (res.newColumns && res.newColumns.length > 0) { if (auto) { self.applySchema(res.newColumns); } else { var h = ''; res.newColumns.forEach(function (c) { h += '<div><input type="checkbox" class="sync-col" value="' + c + '" checked> ' + c + '</div>'; }); $('#schema-sync-list').html(h); $('#btn-apply-sync').off('click').click(function () { var cols = []; $('.sync-col:checked').each(function () { cols.push($(this).val()) }); self.applySchema(cols); $('#schemaSyncModal').modal('hide'); }); $('#schemaSyncModal').modal('show'); } } else if (!auto) alert('Synchron.'); }, 'json'); };
        GridApp.prototype.applySchema = function (cols) { var self = this; $.post(AJAX_URL, { action: 'update_schema', gridName: this.gridName, tableName: this.tableName, cols: cols }, function () { self.loadConfig(); }, 'json'); };
        GridApp.prototype.handleFilter = function (el) { this.filters[$(el).data('field')] = $(el).val(); this.loadData(); };
        GridApp.prototype.toggleSort = function (f) { this.sortField = f; this.sortOrder = (this.sortOrder == 'ASC' ? 'DESC' : 'ASC'); this.renderHeaders(); this.loadData(); };
        GridApp.prototype.resetFilters = function () { this.filters = {}; this.container.find('.header-search').val(''); this.loadData(); };

        GridApp.prototype.openRowModal = function(id) {
            var targetGrid = this.config.form_simple_grid ? this.config.form_simple_grid : this.gridName.replace('_multiline', '_popup');
            var q = '?gridName=' + encodeURIComponent(targetGrid) + '&id=' + id + '&modal_mode=1';
            var url = '../form_simple/form_simple.php' + q;
            
            var mHtml = '<div class="modal fade" id="gridRowModal" tabindex="-1" role="dialog" style="padding:0 !important"><div class="modal-dialog modal-dialog-centered" style="max-width:100%; margin:0; height:100vh"><div class="modal-content" style="height:100%; border-radius:0"><div class="modal-body p-0" style="background:#f8fafc"><iframe src="' + url + '" style="width:100%; height:100%; border:none; min-height: 100vh;"></iframe></div></div></div></div>';
            
            $('#grid-modal-container').remove();
            $('body').append('<div id="grid-modal-container">' + mHtml + '</div>');
            $('#gridRowModal').modal('show');
            
            var self = this;
            $(window).off('message.gridRowModal').on('message.gridRowModal', function(e) {
                if (e.originalEvent.data === 'form_simple_saved') {
                    $('#gridRowModal').modal('hide');
                    self.reload();
                }
            });
        };
        GridApp.prototype.prevPage = function () { if (this.currentPage > 1) { this.currentPage--; this.loadData(); } };
        GridApp.prototype.nextPage = function () { this.currentPage++; this.loadData(); };
        GridApp.prototype.reload = function () { this.loadData(); };
        GridApp.prototype.addRecord = function () { var self = this; $.post(AJAX_URL, { action: 'add_record', tableName: this.tableName, gridName: this.gridName }, function () { self.reload(); }); };
        GridApp.prototype.deleteRecord = function (id) { if (confirm('Löschen?')) { var self = this; var m = this.config.delete_record || 'delete'; $.post(AJAX_URL, { action: 'delete_record', tableName: this.tableName, id: id, mode: m }, function () { self.reload(); }); } };

        window.gridInstances = {};
        window.getGrid = function (id) { return window.gridInstances[id]; };
        window.closeConfigSidebar = function() { 
            if (window.gridInstances['#my-grid-instance-1']) {
                window.gridInstances['#my-grid-instance-1'].closeConfigSidebar();
            } else {
                $("#config-sidebar").hide(); 
                $("#designSwitch").prop("checked", false);
            }
        };

        window.app = {
            codeEditors: {},
            setEditorValue: function(id, val) {
                $('#' + id).val(val);
                if (window.app.codeEditors && window.app.codeEditors[id]) {
                    window.app.codeEditors[id].setValue(val || '');
                }
            },
            getEditorValue: function(id) {
                return (window.app.codeEditors && window.app.codeEditors[id]) ? window.app.codeEditors[id].getValue() : $('#' + id).val();
            },
            isDarkTheme: function() {
                var bg = window.getComputedStyle(document.body).backgroundColor;
                var rgb = bg.match(/\d+/g);
                if (rgb && rgb.length >= 3) {
                    var luma = 0.2126 * parseInt(rgb[0]) + 0.7152 * parseInt(rgb[1]) + 0.0722 * parseInt(rgb[2]);
                    return luma < 128;
                }
                return false;
            },
            registerCodeMirrorHints: function() {
                CodeMirror.registerHelper("hint", "ag_php_data", function(editor, options) {
                    var cursor = editor.getCursor();
                    var line = editor.getLine(cursor.line);
                    var start = cursor.ch, end = cursor.ch;
                    var match = line.slice(0, start).match(/\$data\[['"]([^'"]*)$/);
                    if (!match) return null;
                    var currentWord = match[1];
                    var list = [];
                    var grid = window.gridInstances['#my-grid-instance-1'];
                    if (grid && grid.config && grid.config.fields) {
                        grid.config.fields.forEach(function(f) {
                            if (f.fieldName.toLowerCase().indexOf(currentWord.toLowerCase()) !== -1) {
                                list.push({
                                    text: f.fieldName + "']",
                                    displayText: f.fieldName + " (" + (f.label || 'unbenannt') + ")"
                                });
                            }
                        });
                    }
                    return {
                        list: list,
                        from: CodeMirror.Pos(cursor.line, start - currentWord.length),
                        to: CodeMirror.Pos(cursor.line, end)
                    };
                });
            },
            initCodeMirrorEditors: function() {
                var textareas = ['cfg_evt_onload', 'cfg_evt_onbeforesave', 'cfg_evt_onaftersave', 'cfg_evt_libraries', 'det_evt_onchange', 'det_evt_onclick', 'det_evt_onfocus', 'det_evt_onblur', 'det_evt_onkeypress', 'det_sql_query'];
                var theme = this.isDarkTheme() ? 'dracula' : 'default';
                
                textareas.forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el && !window.app.codeEditors[id]) {
                        var label = $(el).prev('label');
                        if (label.length && label.find('.ai-code-btn').length === 0) {
                            label.addClass('d-flex justify-content-between w-100');
                            label.append('<i class="fas fa-magic text-primary ai-code-btn" data-target="'+id+'" style="cursor:pointer;" title="KI Code-Assistent" onclick="window.app.openAiCodeModal(\''+id+'\')"></i>');
                        }

                        var mode = 'application/x-httpd-php-open';
                        
                        var cm = CodeMirror.fromTextArea(el, {
                            lineNumbers: true,
                            mode: mode,
                            theme: theme,
                            indentUnit: 4,
                            matchBrackets: true,
                            viewportMargin: Infinity,
                            extraKeys: {"Ctrl-Space": "autocomplete"}
                        });
                        if (id !== 'cfg_evt_libraries') {
                            cm.on("keyup", function (cm, event) {
                                if (!cm.state.completionActive && event.keyCode >= 65 && event.keyCode <= 90) {
                                    CodeMirror.commands.autocomplete(cm, null, {completeSingle: false});
                                }
                            });
                        }
                        cm.on('change', function(instance) { instance.save(); });
                        cm.on('keyup', function(cm, event) {
                            if (!cm.state.completionActive && event.key === "'" || event.key === '"') {
                                var cur = cm.getCursor();
                                var line = cm.getLine(cur.line).slice(0, cur.ch);
                                if (line.match(/\$data\[['"]$/)) {
                                    CodeMirror.showHint(cm, CodeMirror.hint.ag_php_data, {completeSingle: false});
                                }
                            }
                        });
                        cm.setSize(null, id === 'cfg_evt_libraries' ? "300px" : "150px");
                        window.app.codeEditors[id] = cm;
                    } else if (window.app.codeEditors[id]) {
                        window.app.codeEditors[id].setOption("theme", theme);
                        window.app.codeEditors[id].refresh();
                    }
                });
            },
            updateBehaviorVisibility: function() {
                var t = $('#det_type').val() || 'string';
                if (t === 'string' || t === 'multiple_line_text' || t === 'text') $('#beh_row_counter').show();
                else $('#beh_row_counter').hide();
                if (t === 'select') $('#beh_row_select2').show();
                else $('#beh_row_select2').hide();
                if (t === 'integer' || t === 'decimal') $('#beh_row_numbers').show();
                else $('#beh_row_numbers').hide();

                if (t === 'button') {
                    $('#beh_row_button').show();
                    $('#dt-lookup-tab').parent().hide();
                    $('#det_beh_placeholder').closest('.form-group').hide();
                    $('#det_def_val_container').hide();
                    $('#det_val_section').hide();
                    $('#det_evt_onchange').closest('.card').hide();
                    $('#det_evt_onblur').closest('.card').hide();
                    $('#det_evt_onfocus').closest('.card').hide();
                    $('#det_label').prev('label').text('Button-Text / Beschriftung');
                    if ($('#btn-hint-alert').length === 0) {
                        $('#det_type').closest('.form-group').after('<div id="btn-hint-alert" class="alert alert-info py-2 px-3 mt-3 small"><i class="fas fa-info-circle"></i> <b>Hinweis:</b> Bei Buttons bildet dieses Feld den eigentlichen Text im Button. Validierungen, Standardinhalte und einige Events sind deaktiviert. Präfix und Suffix verbleiben im Button als Text oder Icon.</div>');
                    }
                    $('#btn-hint-alert').show();
                } else {
                    $('#beh_row_button').hide();
                    $('#dt-lookup-tab').parent().show();
                    $('#det_beh_placeholder').closest('.form-group').show();
                    $('#det_def_val_container').show();
                    $('#det_val_section').show();
                    $('#det_evt_onchange').closest('.card').show();
                    $('#det_evt_onblur').closest('.card').show();
                    $('#det_evt_onfocus').closest('.card').show();
                    $('#det_label').prev('label').text('Label');
                    if ($('#btn-hint-alert').length) $('#btn-hint-alert').hide();
                }
            },
            updateFieldPreview: function() {
                var gid = Object.keys(window.gridInstances)[0];
                var grid = window.gridInstances[gid];
                if (!grid) return;
                
                var t = $('#det_type').val() || 'text';
                var fName = 'preview_field';
                
                var f = {
                    fieldName: fName,
                    label: $('#det_label').val() || 'Beispiel Label',
                    fieldTyp: t,
                    required: $('#det_required').is(':checked'),
                    readonly: $('#det_readonly').is(':checked'),
                    val_max: $('#det_val_max').val(),
                    behavior: {
                        placeholder: $('#det_beh_placeholder').val(),
                        prefix: $('#det_beh_prefix').val(),
                        suffix: $('#det_beh_suffix').val(),
                        btnStyle: $('#det_beh_btnStyle').val(),
                        btnUrl: $('#det_beh_btnUrl').val(),
                        btnTarget: $('#det_beh_btnTarget').val(),
                        counter: $('#det_beh_counter').is(':checked'),
                        btn3d: $('#det_beh_btn3d').is(':checked')
                    },
                    style: {
                        color: $('#det_style_color_en').is(':checked') ? $('#det_style_color').val() : '',
                        backgroundColor: $('#det_style_bg_en').is(':checked') ? $('#det_style_bg').val() : '',
                        textAlign: $('#det_style_align').val(),
                        weight: $('#det_style_weight').val(),
                        size: $('#det_style_size').val()
                    }
                };

                var valStr = 'Muster 1234,56';
                if (['checkbox', 'double_select'].indexOf(t) !== -1) valStr = 'Wert 1,Wert 2';
                else if (t === 'radio') valStr = '1';

                var rowData = { id: 0 };
                rowData[fName] = valStr;
                
                if (['select', 'radio'].indexOf(t) !== -1) {
                    grid.lookups[fName] = { '1': 'Option A', '2': 'Option B' };
                }

                var newHtml = grid.getInputHtml(f, valStr, rowData, '', '');
                
                $('#preview-input-container').html(newHtml);
            },
            openRegexBuilder: function() { alert('KI Assistent für RegEx (Form_Multiline) folgt.'); },
            openAiCodeModal: function(targetId) {
                $('#ea_target_id').val(targetId);
                var shortName = targetId.replace('cfg_evt_', '').replace('det_evt_', '');
                $('#ea_target_name').text(shortName.toUpperCase());
                $('#ea_prompt').val('');
                $('#ea_result').val('');
                $('#ea_loading').hide();
                $('#eventCodeAiModal').modal('show');
            },
            generateEventCode: function() {
                var p = $('#ea_prompt').val();
                if(!p) { alert("Bitte beschreibe, was der Code tun soll."); return; }
                
                var t = $('#ea_target_id').val();
                var contextData = [];
                var grid = window.gridInstances['#my-grid-instance-1'];
                if (grid && grid.config && grid.config.fields) {
                    grid.config.fields.forEach(function(f) {
                        contextData.push(f.fieldName + " (" + (f.label||'') + ", " + (f.fieldTyp||'string') + ")");
                    });
                }
                var ctx = "Verfügbare Felder in \$data-Array:\n" + contextData.join("\n");
                
                $('#ea_loading').show();
                
                var keys = (grid && grid.config && grid.config.apiKeys) ? grid.config.apiKeys : { gemini: '', chatgpt: '', anthropic: '' };
                
                $.post(AJAX_URL, {
                    action: 'generate_event_code',
                    prompt: p,
                    context: ctx,
                    keys: keys
                }, function(res) {
                    $('#ea_loading').hide();
                    if(res.code) {
                        $('#ea_result').val(res.code);
                    } else {
                        alert(res.error || "Fehler bei der Generierung.");
                    }
                }, 'json');
            },
            applyEventCode: function() {
                var c = $('#ea_result').val();
                var t = $('#ea_target_id').val();
                if(c && window.app.codeEditors[t]) {
                    var cm = window.app.codeEditors[t];
                    cm.replaceSelection(c + "\n");
                    cm.save();
                    $('#eventCodeAiModal').modal('hide');
                }
            },
            startSprachEingabeCode: function () {
                if (!('webkitSpeechRecognition' in window)) { alert("Spracheingabe wird in diesem Browser leider nicht unterstützt."); return; }
                var sr = new webkitSpeechRecognition();
                sr.lang = 'de-DE'; sr.continuous = false; sr.interimResults = false;
                sr.onstart = function () { $('#ea_mic_btn').removeClass('btn-outline-secondary').addClass('btn-danger'); };
                sr.onresult = function (e) {
                    var tr = e.results[0][0].transcript;
                    var c = $('#ea_prompt').val();
                    $('#ea_prompt').val(c ? c + ' ' + tr : tr);
                };
                sr.onerror = function (e) { console.error('Speech error', e); };
                sr.onend = function () { $('#ea_mic_btn').removeClass('btn-danger').addClass('btn-outline-secondary'); };
                sr.start();
            },
            refreshEditors: function() {
                for (var id in window.app.codeEditors) {
                    window.app.codeEditors[id].refresh();
                }
            },
            testSql: function() { alert('SQL Test in Multiline kommt im nächsten Update!'); },
            toggleLookupType: function(v) { /* Already handled by our own bindings */ },
            addLookupRow: function() { $('#det_manual_rows').append('<div class=\"lookup-row d-flex mb-1\"><input class=\"form-control form-control-sm lookup-key mr-1\" placeholder=\"DB Wert\"><input class=\"form-control form-control-sm lookup-val mr-1\" placeholder=\"Anzeige\"><button class=\"btn btn-sm btn-danger btn-del-row\">&times;</button></div>'); $('.btn-del-row').off('click').click(function () { $(this).parent().remove(); }); }
        };

        var GRID_NAME = "<?php echo $gridName; ?>";

        $(document).ready(function () {
            var myId = '#my-grid-instance-1';
            var gridName = '<?php echo $gridName; ?>';
            var tableName = '<?php echo $tableName; ?>';
            window.gridInstances[myId] = new GridApp(myId, gridName, tableName);

            window.app.registerCodeMirrorHints();
            window.app.initCodeMirrorEditors();
            
            // Fix Bootstrap tabs bug for CodeMirror redraw
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                window.app.refreshEditors();
            });
        });
    </script>
</body>

</html>
<?php
// 3. END ERROR/OUTPUT BUFFERING
$output = ob_get_clean();
echo $output;

// Detection of errors in the captured output (simple heuristic: if it contains '<b>Notice</b>' or similar, but here we just check if there's any unexpected output before the HTML starts, or if we want to log the whole thing if it's "dirty")
// Actually, in a page load, we usually just want to log if there were actual errors.
// Since we can't easily distinguish between "good" HTML and "bad" errors in the middle, 
// we rely on the fact that we started buffering at the very top.
?>













