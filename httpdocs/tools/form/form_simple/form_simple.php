<?php
ob_start();
session_start();
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

/**
 * Project: ge_grid_edit
 * Author:  Gunter Eibl
 * Version: 2.21.0 Form (Fixes: Height-Lock, Popups in Design Mode, Password Autocomplete)
 */
if (isset($_GET['gridName'])) {
    $_SESSION['form_simple_grid_control_table'] = $_GET['gridName'];
} elseif (!isset($_SESSION['form_simple_grid_control_table'])) {
    $_SESSION['form_simple_grid_control_table'] = 'adresse_grid';
}
$gridName = $_SESSION['form_simple_grid_control_table'];
$startId = isset($_GET['id']) ? intval($_GET['id']) : 1;
$mode = isset($_GET['mode']) ? $_GET['mode'] : 'design';
$modal_mode = isset($_GET['modal_mode']) ? intval($_GET['modal_mode']) : 0;
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form View Designer 2.21</title>

    <link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/trumbowyg.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/plugins/colors/ui/trumbowyg.colors.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <?php
    require_once $_SERVER['DOCUMENT_ROOT'] . '/tools/design_templates/ag_library.php';
    ag_inject_css_variables([]);
    ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100vh;
            overflow: hidden;
            background-color: var(--ag-color-background, #f8fafc) !important;
            color: var(--ag-color-text-main, #0f172a) !important;
            transition: all 0.4s ease;
        }

        #ag-header {
            background-color: var(--ag-color-toolbar_bg, #fff) !important;
            border-bottom: 1px solid var(--ag-color-border, #dee2e6);
            padding: 10px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
        }

        #form-wrapper {
            background-color: var(--ag-color-surface, #ffffff) !important;
            transition: all 0.4s ease;
        }

        /* Theme Overrides for Bootstrap Components */
        .app-input.form-control, .app-input.form-control:focus, .html-preview-box,
        #config-sidebar .form-control, #config-sidebar select, #config-sidebar .custom-range, #config-json-editor {
            background-color: var(--ag-color-background, #fff) !important;
            color: var(--ag-color-text-main, #495057) !important;
            border-color: var(--ag-color-border, #ced4da) !important;
        }
        
        .app-input.form-control:focus, #config-sidebar .form-control:focus {
            box-shadow: 0 0 0 0.2rem var(--ag-color-brand, rgba(0, 123, 255, 0.25)) !important;
            background-color: var(--ag-design-focus_bg, #fff) !important;
        }

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

        #config-sidebar .list-group-item {
            background-color: var(--ag-color-background, #fff) !important;
            border-color: var(--ag-color-border, #dee2e6) !important;
            color: var(--ag-color-text-main, #495057) !important;
        }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <style>
        body {
            background-color: #f4f6f9;
            font-family: "Segoe UI", Arial, sans-serif;
            padding: 20px;
        }

        #toolbar {
            background: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 1000;
            height: 70px;
        }

        .toolbar-group {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        #form-wrapper {
            overflow: auto;
            background: #e9ecef;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 20px;
            min-height: 600px;
            display: flex;
            justify-content: center;
        }

        #form-canvas {
            background: white;
            border: 1px solid #ccc;
            position: relative;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: border 0.3s;
        }

        #form-canvas.design-mode {
            background-image: radial-gradient(#ccc 1px, transparent 1px);
            background-size: 20px 20px;
            border: 2px dashed #007bff;
        }

        .form-element {
            position: absolute;
            box-sizing: border-box;
        }

        .f-label {
            font-weight: 600;
            color: #495057;
            font-size: 14px;
            padding: 5px;
            white-space: nowrap;
            cursor: default;
            user-select: none;
            display: flex;
            align-items: center;
        }

        .f-input {
            padding: 2px;
            overflow: visible !important;
        }

        .form-element.is-hidden {
            display: none;
        }

        .design-mode .form-element.is-hidden {
            display: block;
            opacity: 0.4;
            border: 1px dotted red;
        }

        .app-input {
            width: 100%;
            height: 100%;
            font-size: 14px;
            box-sizing: border-box;
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

        .img-thumbnail-custom {
            height: 100%;
            width: auto;
            max-width: 100%;
            object-fit: contain;
            cursor: zoom-in;
            border: 1px solid #dee2e6;
            background: #fff;
            padding: 2px;
            border-radius: 4px;
            flex: 1;
            min-height: 20px;
        }

        .html-preview-box {
            height: 38px;
            overflow: hidden;
            font-size: 0.8rem;
            border: 1px solid #ced4da;
            padding: 4px;
            cursor: pointer;
            background: #fff;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .design-mode .form-element {
            cursor: move;
        }

        .design-mode .f-label:hover {
            border: 1px dashed #999;
            background: rgba(255, 255, 0, 0.1);
        }

        .design-mode .f-input:hover {
            border: 1px dashed #007bff;
            background: rgba(0, 123, 255, 0.05);
        }

        .design-mode input,
        .design-mode select,
        .design-mode textarea,
        .design-mode .btn,
        .design-mode img {
            pointer-events: none !important;
            opacity: 0.7;
        }

        .custom-range {
            width: 100px;
            cursor: pointer;
        }

        #loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.8);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }

        .is-valid {
            border-color: #28a745 !important;
            transition: border-color 0.5s;
        }

        .is-invalid {
            border-color: #dc3545 !important;
            background-color: #fff0f0;
        }

        .ui-autocomplete {
            z-index: 1060 !important;
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
            font-size: 14px;
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2);
            border: 1px solid #ccc;
            background: white;
        }

        .help-icon {
            cursor: help;
            margin-left: 5px;
            font-size: 14px;
            color: #17a2b8;
            position: relative;
        }

        .help-bubble {
            display: none;
            position: absolute;
            left: 20px;
            top: -5px;
            z-index: 9999;
            background: #fff;
            color: #333;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 13px;
            font-family: "Segoe UI", Arial, sans-serif;
            font-weight: normal;
            line-height: 1.4;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            width: 250px;
            white-space: normal;
            text-transform: none;
            text-align: left;
        }

        .help-bubble::before {
            content: '';
            position: absolute;
            left: -6px;
            top: 12px;
            width: 10px;
            height: 10px;
            background: #fff;
            transform: rotate(45deg);
            border-left: 1px solid #ccc;
            border-bottom: 1px solid #ccc;
        }

        .help-icon:hover .help-bubble {
            display: block;
        }

        .ui-resizable-handle {
            position: absolute;
            font-size: 0.1px;
            display: block;
            z-index: 900 !important;
            touch-action: none;
            opacity: 0;
        }

        .design-mode .ui-resizable-handle {
            opacity: 0.8;
            pointer-events: auto !important;
        }

        .design-mode .ui-resizable-handle:hover {
            opacity: 1;
            background-color: #0056b3;
        }

        .ui-resizable-e {
            cursor: e-resize;
            width: 14px;
            right: -7px;
            top: 0;
            height: 100%;
            background: transparent;
        }

        .design-mode .ui-resizable-e::after {
            content: '';
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 20px;
            background: #007bff;
            border-radius: 2px;
        }

        .ui-resizable-se {
            cursor: se-resize;
            width: 16px;
            height: 16px;
            right: -8px;
            bottom: -8px;
            background-color: #007bff;
            border: 2px solid #fff;
            border-radius: 50%;
            box-shadow: 0 0 3px rgba(0, 0, 0, 0.3);
        }

        .ui-resizable-w {
            cursor: ew-resize;
            width: 14px;
            left: -7px;
            top: 0;
            height: 100%;
            background: transparent;
            z-index: 1000 !important;
        }

        .design-mode .ui-resizable-w::after, #config-sidebar .ui-resizable-w::after {
            content: '';
            position: absolute;
            left: 4px;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 20px;
            background: #007bff;
            border-radius: 2px;
        }

        .ui-resizable-s {
            cursor: s-resize;
            height: 14px;
            width: 100%;
            bottom: -7px;
            left: 0;
            background: transparent;
        }

        .design-mode .ui-resizable-s::after {
            content: '';
            position: absolute;
            bottom: 4px;
            left: 50%;
            transform: translateX(-50%);
            width: 20px;
            height: 4px;
            background: #007bff;
            border-radius: 2px;
        }

        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            #toolbar {
                flex-wrap: wrap;
                height: auto;
                gap: 10px;
                padding: 10px;
                justify-content: center;
            }

            #form-wrapper {
                padding: 5px;
                border: none;
                background: transparent;
            }

            #form-canvas {
                width: 100% !important;
                height: auto !important;
                display: flex !important;
                flex-direction: column !important;
                padding: 15px;
                box-sizing: border-box;
                border: 1px solid #dee2e6;
                border-radius: 8px;
            }

            .form-element {
                position: relative !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                height: auto !important;
                margin-bottom: 5px;
            }

            .f-label {
                margin-top: 10px;
                margin-bottom: 2px;
                padding-left: 0;
                font-size: 13px;
            }

            .f-input {
                min-height: 38px;
            }

            #designSwitch {
                pointer-events: none;
                opacity: 0.5;
            }
        }

        /* Server-side Modal-Mode Equivalents */
        body.modal-mode { padding: 0 !important; overflow-y: auto !important; height: auto !important; background-color: #f4f6f9; display: block !important; }
        body.modal-mode .d-flex.w-100 { display: block !important; }
        body.modal-mode #form-wrapper { height: auto !important; min-height: 0 !important; padding: 5px; border: none; background: transparent; box-shadow: none; overflow: visible !important; margin: 0 !important; }
        body.modal-mode #form-canvas { width: 100% !important; height: auto !important; display: block !important; padding: 15px; box-sizing: border-box; border: 1px solid #dee2e6; border-radius: 8px; background: white; margin-bottom: 50px; }
        body.modal-mode .form-element { position: relative !important; left: auto !important; top: auto !important; width: 100% !important; height: auto !important; margin-bottom: 5px; display: block !important; }
        body.modal-mode .f-label { margin-top: 15px; margin-bottom: 5px; padding-left: 0; font-size: 14px; font-weight: bold; }
        body.modal-mode .f-input { min-height: 48px; border-radius: 4px; overflow: visible !important; margin-bottom: 15px; }
        body.modal-mode #ag-header { position: sticky; top: 0; z-index: 9999; box-shadow: 0 4px 6px rgba(0,0,0,0.1); border-bottom: none; }

        .design-edit-btn {
            display: none;
            position: absolute;
            top: -10px;
            right: -10px;
            width: 22px;
            height: 22px;
            padding: 0;
            background-color: #007bff;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 18px;
            font-size: 10px;
            cursor: pointer;
            z-index: 1050;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            border: 2px solid white;
            transition: transform 0.15s ease-in-out;
            outline: none;
        }
        .design-edit-btn:focus { outline: none; }
        
        .design-edit-btn:hover {
            transform: scale(1.15) rotate(10deg);
            background-color: #0056b3;
        }

        .design-mode .form-element:hover .design-edit-btn {
            display: block;
        }
    </style>
</head>

<body class="<?php echo $modal_mode ? 'modal-mode' : ''; ?>">

    <div id="loading">
        <div class="spinner-border text-primary"></div>
    </div>

    <?php if (!empty($sys_debug_log)): ?>
        <div class="p-4 bg-yellow-100 text-yellow-800 border-bottom border-yellow-500">
            <h3 class="font-bold text-sm mb-2">System Debug / Uncaught Output:</h3>
            <pre class="text-xs overflow-auto whitespace-pre-wrap m-0"><?= htmlspecialchars($sys_debug_log) ?></pre>
        </div>
    <?php endif; ?>

    <div id="ag-header">
        <div class="toolbar-group" style="flex: 1; display: flex; justify-content: flex-start;">
            <?php if (!$modal_mode): ?>
            <button class="btn btn-outline-secondary" id="btn-undo" onclick="app.handleUndo()" disabled title="Undo"><i
                    class="fas fa-undo"></i></button>
            <button class="btn btn-outline-secondary" id="btn-redo" onclick="app.handleRedo()" disabled title="Redo"><i
                    class="fas fa-redo"></i></button>
            <?php endif; ?>
        </div>
        <div class="toolbar-group" style="flex: 1; display: flex; justify-content: center;">
            <?php if (!$modal_mode): ?>
            <button class="btn btn-outline-primary" onclick="app.nav('first')" title="Erster Datensatz"><i class="fas fa-step-backward"></i></button>
            <button class="btn btn-outline-primary" onclick="app.nav('prev')" title="Vorheriger Datensatz"><i class="fas fa-chevron-left"></i></button>
            <?php endif; ?>
            <span class="font-weight-bold" id="record-indicator" style="min-width: 60px; text-align:center;">ID: ...</span>
            <?php if (!$modal_mode): ?>
            <button class="btn btn-outline-primary" onclick="app.nav('next')" title="Nächster Datensatz"><i class="fas fa-chevron-right"></i></button>
            <button class="btn btn-outline-primary" onclick="app.nav('last')" title="Letzter Datensatz"><i class="fas fa-step-forward"></i></button>
            <?php endif; ?>
        </div>
        <div class="toolbar-group flex items-center gap-4 d-flex align-items-center" style="flex: 1; justify-content: flex-end;">
            <?php if ($mode !== 'run' && !$modal_mode): ?>
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="designSwitch"
                        onchange="app.toggleDesign(this.checked)">
                    <label class="custom-control-label font-weight-bold" for="designSwitch">Design</label>
                </div>
            <?php endif; ?>
            <?php if ($modal_mode): ?>
                <button class="btn btn-success font-weight-bold ml-2" onclick="if(document.activeElement) document.activeElement.blur(); setTimeout(() => window.parent.postMessage('form_simple_saved', '*'), 250);"><i class="fas fa-check"></i> Speichern &amp; Schlie&szlig;en</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="d-flex w-100" style="align-items: flex-start; gap: 15px;">
        <div id="form-wrapper" style="flex: 1; min-width: 0;">
            <div id="form-canvas"></div>
        </div>

        <div id="config-sidebar" style="display:none; width: 550px; flex-shrink: 0; background: white; border: 1px solid #dee2e6; border-radius: 4px; box-shadow: 0 0 15px rgba(0,0,0,0.05); overflow: hidden;">
            <div class="modal-header pb-0 border-bottom-0 bg-light">
                <ul class="nav nav-tabs border-bottom-0" id="cfgTabs" role="tablist" style="width:100%">
                    <li class="nav-item"><a class="nav-link active" data-toggle="tab"
                                href="#content-gen">Allgemein</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#content-fld">Felder</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab"
                                href="#content-taborder">Tab-Reihenfolge</a></li>
                        <li class="nav-item"><a class="nav-link" data-toggle="tab" href="#content-form-events">Globale Events</a></li>
                        <li class="nav-item"><a class="nav-link" id="tab-json" data-toggle="tab"
                                href="#content-json">JSON</a></li>
                        <button type="button" class="close ml-auto mb-2" onclick="app.closeConfigSidebar()">×</button>
                    </ul>
                </div>
                <div class="modal-body bg-light pt-3" style="max-height: 80vh; overflow-y: auto;">
                    <div id="config-main-view" class="tab-content bg-white border rounded p-3 shadow-sm"
                        style="min-height: 500px;">

                        <div class="tab-pane fade show active" id="content-gen">
                            <form>
                                <div class="form-group row"><label class="col-3">Tabelle</label>
                                    <div class="col-9"><input id="cfg_table" class="form-control" readonly></div>
                                </div>
                                <div class="form-group row"><label class="col-3">Canvas BxH</label>
                                    <div class="col-4"><input id="cfg_cw" type="number" class="form-control"
                                            placeholder="Width"></div>
                                    <div class="col-5"><input id="cfg_ch" type="number" class="form-control"
                                            placeholder="Height"></div>
                                </div>

                                <div class="form-group row align-items-center">
                                    <label class="col-3"><i class="fas fa-magnet text-muted"></i> Raster (Snap)</label>
                                    <div class="col-5 d-flex align-items-center">
                                        <input type="range" class="custom-range flex-grow-1" min="10" max="50" step="5" value="10"
                                            oninput="$('#snapVal').text(this.value + 'px'); app.updateGrid(this.value);">
                                        <span id="snapVal" class="badge badge-light border ml-2">10px</span>
                                    </div>
                                    <div class="col-4 text-right">
                                        <button class="btn btn-warning btn-sm" type="button"
                                            onclick="app.resetLayout()" title="Alle Felder linear neu anordnen"><i class="fas fa-th-list"></i> Reset Layout</button>
                                    </div>
                                </div>

                                <div class="form-group row align-items-center mt-3">
                                    <label class="col-3"><i class="fas fa-paint-brush text-muted"></i> Design Vorlage</label>
                                    <div class="col-9" id="theme-injector-target">
                                    </div>
                                </div>

                                <h6 class="mt-4 border-bottom pb-2 text-primary"><i class="fas fa-key"></i> KI
                                    API-Schlüssel (für Assistenten & RegEx)</h6>
                                <p class="small text-muted mb-3">Hinterlegen Sie hier Ihre API-Schlüssel.</p>

                                <div class="form-group row">
                                    <label class="col-3">Google Gemini <a href="https://aistudio.google.com/app/apikey" target="_blank" class="text-primary ml-1" title="API Key erstellen (Google AI Studio)"><i class="fas fa-external-link-alt"></i></a></label>
                                    <div class="col-9">
                                        <div class="input-group">
                                            <input id="cfg_api_gemini" type="password" class="form-control"
                                                placeholder="AIzaSy..." autocomplete="new-password">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary toggle-pw" type="button"><i
                                                        class="fas fa-eye"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-3">OpenAI ChatGPT <a href="https://platform.openai.com/api-keys" target="_blank" class="text-primary ml-1" title="API Key erstellen (OpenAI Platform)"><i class="fas fa-external-link-alt"></i></a></label>
                                    <div class="col-9">
                                        <div class="input-group">
                                            <input id="cfg_api_chatgpt" type="password" class="form-control"
                                                placeholder="sk-proj-..." autocomplete="new-password">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary toggle-pw" type="button"><i
                                                        class="fas fa-eye"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-3">Anthropic Claude <a href="https://console.anthropic.com/settings/keys" target="_blank" class="text-primary ml-1" title="API Key erstellen (Anthropic Console)"><i class="fas fa-external-link-alt"></i></a></label>
                                    <div class="col-9">
                                        <div class="input-group">
                                            <input id="cfg_api_anthropic" type="password" class="form-control"
                                                placeholder="sk-ant-..." autocomplete="new-password">
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary toggle-pw" type="button"><i
                                                        class="fas fa-eye"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="content-fld"></div>
                        <div class="tab-pane fade" id="content-taborder">
                            <p class="text-muted small mt-2">Ziehe die Felder per Drag & Drop in die gewünschte
                                Reihenfolge für die Tabulator-Navigation.</p>
                            <ul id="sortable-tab-order" class="list-group shadow-sm"
                                style="max-height: 400px; overflow-y: auto;"></ul>
                        </div>
                        <div class="tab-pane fade" id="content-form-events">
                            <h6 class="border-bottom pb-2 text-primary"><i class="fas fa-bolt"></i> Formular-Referenz: <br><small class="text-muted">Zugriff auf das Daten-Array über <code>$data['feldname']</code></small></h6>
                            <div class="form-group"><label>onLoad <small>(Nach dem Laden)</small></label><textarea id="cfg_evt_onload" class="form-control" rows="3" style="font-family:monospace;font-size:12px;"></textarea></div>
                            <div class="form-group"><label>onBeforeSave <small>(Vor dem Speichern)</small></label><textarea id="cfg_evt_onbeforesave" class="form-control" rows="3" style="font-family:monospace;font-size:12px;"></textarea></div>
                            <div class="form-group"><label>onAfterSave <small>(Nach dem Speichern)</small></label><textarea id="cfg_evt_onaftersave" class="form-control" rows="3" style="font-family:monospace;font-size:12px;"></textarea></div>
                            <div class="form-group"><label>Libraries / Globale Funktionen (PHP)</label><textarea id="cfg_evt_libraries" class="form-control" rows="5" placeholder="function myCustomCalc($val) { return $val * 2; }" style="font-family:monospace;font-size:12px;"></textarea></div>
                        </div>
                        <div class="tab-pane fade" id="content-json">
                            <textarea id="config-json-editor" class="form-control" rows="20"
                                style="font-family:monospace; font-size:12px;"></textarea>
                        </div>
                    </div>

                    <div id="config-detail-view" class="bg-white border rounded p-3 shadow-sm"
                        style="min-height: 500px; display:none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="m-0">Feld: <span id="detail-field-name" class="text-primary"></span></h5>
                            <button class="btn btn-sm btn-secondary" onclick="app.closeFieldDetail()"><i
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
                                    <div class="form-group"><label>Hilfetext (HTML)</label><textarea id="det_help"
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
                        </div>
                    </div>
                </div>
                <div class="modal-footer pt-2 pb-2 bg-white">
                    <button type="button" class="btn btn-secondary" onclick="app.closeConfigSidebar()">Abbrechen</button>
                    <button type="button" class="btn btn-success" onclick="app.saveConfigFull()">Speichern</button>
                </div>
            </div>
        </div>
    </div>

    <div id="dynamic-modals-container"></div>

    <div class="modal fade" id="regexBuilderModal" tabindex="-1" style="z-index: 1060;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-magic"></i> RegEx Assistent & Tester</h5>
                    <button type="button" class="close text-white" data-dismiss="modal">×</button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6 border-right">
                            <label class="font-weight-bold text-primary"><i class="fas fa-robot"></i>
                                KI-Assistent</label>
                            <div class="input-group">
                                <input type="text" id="rb_ai_prompt" class="form-control"
                                    placeholder="z.B. Deutsche Postleitzahl oder IBAN">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" id="btn-ai-generate"
                                        onclick="app.generateRegexAI()">Generieren</button>
                                </div>
                            </div>
                            <small class="text-muted">Nutzt den API-Schlüssel aus 'Allgemein'.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="font-weight-bold text-secondary"><i class="fas fa-book"></i>
                                Vorlagen-Bibliothek</label>
                            <select id="rb_templates" class="form-control"
                                onchange="app.applyRegexTemplate(this.value)">
                                <option value="">-- Bitte wählen --</option>
                                <option value="^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}$">E-Mail Adresse</option>
                                <option value="^[0-9]{5}$">Deutsche Postleitzahl (5 Ziffern)</option>
                                <option value="^[A-Z]{2}[0-9]{2}[a-zA-Z0-9]{11,30}$">IBAN</option>
                                <option value="^[0-9]+$">Nur Zahlen (ohne Leerzeichen)</option>
                                <option value="^[a-zA-ZäöüÄÖÜß\s]+$">Nur Buchstaben & Leerzeichen</option>
                            </select>
                        </div>
                    </div>
                    <hr>
                    <div class="form-group bg-light p-3 rounded border">
                        <label class="font-weight-bold">Ihr RegEx Code (Maschinenraum)</label>
                        <input type="text" id="rb_regex_code" class="form-control font-weight-bold text-danger"
                            style="font-family: monospace; font-size:16px;">
                    </div>
                    <div class="form-group border border-info p-3 rounded">
                        <label class="font-weight-bold text-info"><i class="fas fa-flask"></i> Live-Tester</label>
                        <input type="text" id="rb_test_input" class="form-control form-control-lg"
                            placeholder="Geben Sie hier einen Test-Text ein...">
                        <div id="rb_test_result" class="mt-2 font-weight-bold" style="font-size: 1.2rem;"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
                    <button type="button" class="btn btn-success" onclick="app.applyRegex()">Übernehmen</button>
                </div>
            </div>
        </div>
    </div>

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
                            <button class="btn btn-outline-secondary" type="button" id="ea_mic_btn" title="Spracheingabe (Chrome)" onclick="app.startSprachEingabeCode()"><i class="fas fa-microphone"></i></button>
                        </div>
                    </div>
                    <input type="hidden" id="ea_target_id">
                    <button class="btn btn-primary btn-block mb-3" onclick="app.generateEventCode()"><i class="fas fa-magic"></i> Code generieren</button>
                    <div id="ea_loading" class="text-center" style="display:none;"><i class="fas fa-spinner fa-spin fa-2x text-primary"></i><br>KI denkt nach...</div>
                    <div class="mt-3">
                        <label>Generierter Code (Vorschau)</label>
                        <textarea id="ea_result" class="form-control" rows="4" style="font-family:monospace; font-size:12px;"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button>
                    <button type="button" class="btn btn-success" onclick="app.applyEventCode()">Code einfügen</button>
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

    <script src="/tools/ge_validator/ge_validator.js"></script>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/trumbowyg.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/plugins/colors/trumbowyg.colors.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/plugins/fontsize/trumbowyg.fontsize.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        var AJAX_URL = "form_simple_ajax.php";
        var GRID_NAME = "<?php echo $gridName; ?>";

        $(document).on('click', '.toggle-pw', function () {
            var $inp = $(this).closest('.input-group').find('input');
            var $icon = $(this).find('i');
            if ($inp.attr('type') === 'password') {
                $inp.attr('type', 'text');
                $icon.removeClass('fa-eye').addClass('fa-eye-slash');
            } else {
                $inp.attr('type', 'password');
                $icon.removeClass('fa-eye-slash').addClass('fa-eye');
            }
        });

        function setupAddressAutocomplete(selector, onSelectCallback) { $(selector).autocomplete({ source: function (request, response) { $.ajax({ url: "https://nominatim.openstreetmap.org/search", dataType: "json", data: { q: request.term, format: "json", addressdetails: 1, limit: 5 }, success: function (data) { response($.map(data, function (item) { return { label: item.display_name, value: item.display_name, lat: item.lat, lon: item.lon }; })); } }); }, minLength: 3, select: function (event, ui) { event.preventDefault(); $(this).val(ui.item.value); if (onSelectCallback) onSelectCallback.call(this, ui.item); return false; } }); }

        var mapManager = { map: null, marker: null, currentId: null, currentField: null, openMap: function (id, f) { this.currentId = id; this.currentField = f; var currentVal = $('#inp_' + f).find('input').val() || ''; $('#dynamic-modals-container').html('<div class="modal fade" id="dynMap"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h6 class="modal-title">Karte</h6><button type="button" class="close" data-dismiss="modal">×</button></div><div class="modal-body"><div class="input-group mb-2"><input id="map-addr-preview" class="form-control" placeholder="Adresse suchen..."><div class="input-group-append"><button id="map-s" class="btn btn-primary">Suchen</button></div></div><div id="map-cnt" style="height:350px;border:1px solid #ccc; background:#eee;"></div></div><div class="modal-footer"><button class="btn btn-primary btn-save">Übernehmen</button></div></div></div></div>'); var modal = $('#dynMap'); $('#map-addr-preview').val(currentVal); modal.modal('show'); modal.on('shown.bs.modal', function () { if (mapManager.map) { mapManager.map.remove(); mapManager.map = null; } mapManager.map = L.map('map-cnt').setView([52.52, 13.405], 13); var o = String.fromCharCode(123); var c = String.fromCharCode(125); L.tileLayer('https://' + o + 's' + c + '.tile.openstreetmap.org/' + o + 'z' + c + '/' + o + 'x' + c + '/' + o + 'y' + c + '.png', { attribution: 'OSM' }).addTo(mapManager.map); mapManager.map.invalidateSize(); mapManager.map.on('click', function (e) { mapManager.setMarker(e.latlng); }); if (currentVal) mapManager.searchLocation(currentVal); setupAddressAutocomplete('#map-addr-preview', function (item) { mapManager.map.setView([item.lat, item.lon], 16); mapManager.setMarker({ lat: item.lat, lng: item.lon }); }); $('#map-s').off('click').click(function () { mapManager.searchLocation($('#map-addr-preview').val()); }); modal.find('.btn-save').off('click').click(function () { var newVal = $('#map-addr-preview').val(); app.pushDataUndo(mapManager.currentField, newVal, $('#inp_' + mapManager.currentField).find('input').val()); app.saveDataDirect(mapManager.currentField, newVal); $('#inp_' + mapManager.currentField).find('input').val(newVal); modal.modal('hide'); }); }); }, searchLocation: function (q) { if (!q) return; $.get('https://nominatim.openstreetmap.org/search?format=json&q=' + encodeURIComponent(q), function (r) { if (r && r.length) { var lat = r[0].lat; var lon = r[0].lon; mapManager.map.setView([lat, lon], 16); mapManager.setMarker({ lat: lat, lng: lon }); } }); }, setMarker: function (ll) { if (this.marker) this.map.removeLayer(this.marker); this.marker = L.marker(ll, { draggable: true }).addTo(this.map); this.marker.on('dragend', function (e) { var p = e.target.getLatLng(); $.get('https://nominatim.openstreetmap.org/reverse?format=json&lat=' + p.lat + '&lon=' + p.lng, function (r) { $('#map-addr-preview').val(r.display_name || (p.lat + ', ' + p.lng)); }); }); } };

        var app = {
            currentId: <?php echo $startId; ?>, config: null, lookups: {}, isDesign: false, snapping: 10,
            layoutUndo: [], layoutRedo: [], dataUndo: [], dataRedo: [], tempVal: null, currentEditFieldIdx: null,

            init: function () {
                if ($('#trumbowyg-icons').length === 0) { $.get('https://cdnjs.cloudflare.com/ajax/libs/Trumbowyg/2.27.3/ui/icons.svg', function (d) { $('body').prepend('<div id="trumbowyg-icons" style="display:none">' + new XMLSerializer().serializeToString(d.documentElement) + '</div>'); }); }
                this.loadData(this.currentId);
                $('#rb_regex_code, #rb_test_input').on('input keyup', function () { app.testRegexLive(); });
                
                var self = this;
                var themeInitInterval = setInterval(function() {
                    var $sel = $('#ag-theme-selector');
                    if ($sel.length) {
                        $sel.removeClass('bg-slate-100 border-none text-[10px] uppercase font-black px-3 py-2 rounded-lg cursor-pointer hover:bg-white hover:shadow-sm transition-all focus:ring-2 focus:ring-blue-500 appearance-none text-slate-600 outline-none')
                            .addClass('form-control form-control-sm');
                        $('#theme-injector-target').append($sel);
                        
                        $sel.on('change', function() {
                            if(app.config) app.config.themeName = $(this).val();
                        });
                        
                        if (self.config && self.config.themeName) $sel.val(self.config.themeName);
                        clearInterval(themeInitInterval);
                    }
                }, 100);
            },

            loadData: function (id) {
                $('#loading').css('display', 'flex');
                var self = this;
                $.post(AJAX_URL, { action: 'load_single', gridName: GRID_NAME, id: id }, function (res) {
                    $('#loading').hide();
                    if (res.retry) { self.loadData(res.id); return; }
                    if (res.error) { alert(res.error); return; }
                    self.currentId = parseInt(res.id);
                    self.config = res.config;
                    self.lookups = res.lookups || {};
                    if (self.config.themeName && window.AgThemeEngine) {
                        var applyThemeInterval = setInterval(function() {
                            if ($('#ag-theme-selector').length) {
                                $('#ag-theme-selector').val(self.config.themeName);
                                window.AgThemeEngine.changeTheme(self.config.themeName);
                                clearInterval(applyThemeInterval);
                            }
                        }, 50);
                    }
                    self.dataUndo = []; self.dataRedo = []; self.updateButtons();
                    $('#record-indicator').text('ID: ' + self.currentId);
                    self.render();
                    self.triggerEvent('onLoad', '');
                }, 'json').fail(function () { $('#loading').hide(); alert("Ajax Fehler"); });
            },

            render: function () {
                var canvas = $('#form-canvas'); canvas.empty();
                var cW = (this.config.canvas && this.config.canvas.width) ? this.config.canvas.width : 1000;
                var cH = (this.config.canvas && this.config.canvas.height) ? this.config.canvas.height : 800;
                canvas.css({ width: cW + 'px', height: cH + 'px' });

                var self = this;
                if (this.config.fields) {
                    this.config.fields.forEach(function (f) {
                        var lx = (f.form && f.form.lbl) ? f.form.lbl.x : 0; var ly = (f.form && f.form.lbl) ? f.form.lbl.y : 0;
                        var ix = (f.form && f.form.inp) ? f.form.inp.x : 0; var iy = (f.form && f.form.inp) ? f.form.inp.y : 0;
                        var w = f.width ? f.width : 200;
                        var hStr = ''; if (f.height) hStr = 'height:' + f.height + 'px;';
                        var isHid = (f.hidden === true || f.hidden === 'true') ? 'is-hidden' : '';

                        var lbl = $('<div>').attr('id', 'lbl_' + f.fieldName).addClass('form-element f-label ' + isHid).css({ left: lx + 'px', top: ly + 'px' }).text(f.label || f.fieldName);
                        
                        if (f.lblStyle) {
                            if (f.lblStyle.textAlign) { lbl.css('display', 'block'); lbl.css('text-align', f.lblStyle.textAlign); }
                            if (f.lblStyle.weight === 'bold') lbl.css('font-weight', 'bold');
                            if (f.lblStyle.weight === 'italic') lbl.css('font-style', 'italic');
                            if (f.lblStyle.size) lbl.css('font-size', f.lblStyle.size);
                            if (f.lblStyle.color) lbl.css('color', f.lblStyle.color);
                        }

                        if (f.helpText) { var help = $('<i class="fas fa-info-circle help-icon"></i>'); var bubble = $('<div class="help-bubble"></div>').html(f.helpText); help.append(bubble); lbl.append(help); }
                        if (f.val_req) lbl.append('<span class="text-danger ml-1">*</span>');
                        if (f.fieldTyp === 'button') lbl.css('display', 'none');

                        var inp = $('<div>').attr('id', 'inp_' + f.fieldName).addClass('form-element f-input ' + isHid).attr('style', 'left:' + ix + 'px; top:' + iy + 'px; width:' + w + 'px; ' + hStr);
                        try {
                            inp.html(self.getInputHtml(f));
                        } catch (e) {
                            console.error("Renderer FEHLER für Feld", f.fieldName, e);
                            inp.html('<div style="color:red; font-size:11px; border:1px solid red; padding:2px;">Init-Fehler (Feld <b>' + f.fieldName + '</b>): ' + e.message + '</div>');
                        }

                        var orderBase = (f.tabIndex ? parseInt(f.tabIndex) : (1000 + fidx)) * 2;
                        lbl.css('order', orderBase);
                        inp.css('order', orderBase + 1);

                        var editBtn = $('<button class="design-edit-btn" type="button" title="Dieses Feld konfigurieren"><i class="fas fa-pencil-alt"></i></button>')
                            .attr('data-fname', f.fieldName)
                            .attr('onmousedown', 'app.openDesignerField(event, \'' + f.fieldName + '\')');
                        
                        // We append the edit btn to BOTH label and input wrappers to make it accessible everywhere!
                        lbl.append(editBtn.clone());
                        inp.append(editBtn.clone());

                        canvas.append(lbl).append(inp);
                    });
                }

                this.initMainFormAutocompletes();
                if (this.isDesign) { 
                    this.enableResize(); 
                    this.enableDrag(); 
                    $('.app-input').prop('disabled', true);
                    $('.design-only-ui').show();
                    $('#form-canvas').addClass('design-mode');
                } else {
                    setTimeout(function() {
                        if ($.fn.select2) {
                            $('[data-plugins="select2"]').select2({
                                width: '100%',
                                placeholder: "Suche...",
                                allowClear: true
                            }).on('select2:select', function(e) {
                                app.saveData(this); // Trigger autosave manually on select
                            });
                        }
                    }, 50);
                }
            },

            openDesignerField: function(e, fName) {
                if (e) {
                    e.stopPropagation();
                    e.preventDefault();
                }
                if (!fName) return;
                
                var idx = this.config.fields.findIndex(function(f) { return f.fieldName === fName; });
                if (idx !== -1) {
                    $('#config-sidebar').show();
                    $('#cfgTabs .nav-link').removeClass('active');
                    $('#cfgTabs a[href="#content-fld"]').addClass('active');
                    $('#config-main-view .tab-pane').removeClass('show active');
                    $('#content-fld').addClass('show active');
                    this.editFieldDetail(idx);
                }
            },

            initMainFormAutocompletes: function () {
                if (this.isDesign) return;
                var self = this;
                $('.app-input').each(function () {
                    var fName = $(this).data('field');
                    var fConf = self.config.fields.find(function (f) { return f.fieldName == fName; });
                    if (fConf && fConf.fieldTyp === 'GoogleMaps') { setupAddressAutocomplete(this, function (item) { app.saveData($(this)); }); }
                });
            },

            fieldRenderers: {
                'multiple_line_text': function(f, val, common, valStr, lookups, isDesign) { 
                    return '<textarea ' + common + '>' + val + '</textarea>'; 
                },
                'email': function(f, val, common, valStr, lookups, isDesign) {
                    return '<div class="flex h-full w-full"><input type="email" ' + common + ' ' + valStr + ' onkeyup="app.updateLink(this,\'mailto\')"><a href="mailto:' + val + '" class="flex items-center justify-center px-3 border border-l-0 border-gray-300 bg-gray-50 text-gray-500 rounded-r hover:bg-gray-100 transition-colors" target="_blank" tabindex="-1"><i class="fas fa-envelope"></i></a></div>';
                },
                'url': function(f, val, common, valStr, lookups, isDesign) {
                    var l = (val && val.indexOf('http') !== 0) ? 'https://' + val : val;
                    return '<div class="flex h-full w-full"><input type="url" ' + common + ' ' + valStr + ' onkeyup="app.updateLink(this,\'url\')"><a href="' + l + '" class="flex items-center justify-center px-3 border border-l-0 border-gray-300 bg-gray-50 text-gray-500 rounded-r hover:bg-gray-100 transition-colors" target="_blank" tabindex="-1"><i class="fas fa-external-link-alt"></i></a></div>';
                },
                'select': function(f, val, common, valStr, lookups, isDesign) {
                    var opts = lookups[f.fieldName] || {};
                    var sel = '<select ' + common + ' onchange="app.saveData(this)"><option value=""></option>';
                    for (var k in opts) sel += '<option value="' + k + '" ' + (k == val ? 'selected' : '') + '>' + opts[k] + '</option>';
                    return sel + '</select>';
                },
                'radio': function(f, val, common, valStr, lookups, isDesign) {
                    var rOpts = lookups[f.fieldName] || {};
                    var tabStr = f.tabIndex ? ' tabindex="' + f.tabIndex + '"' : '';
                    var rH = '<div class="flex flex-wrap gap-2 h-full items-center pl-1">';
                    for (var k in rOpts) {
                        var activeClass = (k == val) ? 'bg-blue-50 border-blue-500 text-blue-700 font-medium' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50';
                        rH += '<label class="cursor-pointer border rounded px-3 py-1 text-sm transition-colors m-0 ' + activeClass + '" onclick="app.manualSave(\'' + f.fieldName + '\', \'' + k + '\')"><input type="radio" class="hidden" ' + tabStr + ' ' + (k == val ? 'checked' : '') + '> ' + rOpts[k] + '</label>';
                    }
                    return rH + '</div>';
                },
                'checkbox': function(f, val, common, valStr, lookups, isDesign) {
                    var cnt = val ? val.split(',').length : 0;
                    var tabStr = f.tabIndex ? ' tabindex="' + f.tabIndex + '"' : '';
                    return '<button ' + tabStr + ' class="w-full h-full text-left px-3 py-1 bg-white border border-gray-300 rounded text-sm hover:bg-gray-50 transition-colors flex justify-between items-center outline-none focus:ring-2 focus:ring-blue-500" onclick="app.openMultiSelect(\'' + f.fieldName + '\', \'' + val + '\')"><span>' + cnt + ' gewählt</span> <i class="fas fa-caret-down text-gray-400"></i></button>';
                },
                'double_select': function(f, val, common, valStr, lookups, isDesign) {
                    return this.checkbox(f, val, common, valStr, lookups, isDesign);
                },
                'htmlEditor': function(f, val, common, valStr, lookups, isDesign) {
                    var strip = val ? String(val).replace(/<[^>]*>?/gm, '').substring(0, 30) : 'Leer';
                    var tabStrDiv = f.tabIndex ? ' tabindex="' + f.tabIndex + '"' : ' tabindex="0"';
                    return '<div ' + tabStrDiv + ' class="w-full h-full px-3 py-2 bg-white border border-gray-300 rounded text-sm text-gray-600 cursor-pointer hover:bg-gray-50 overflow-hidden flex items-center justify-between outline-none focus:ring-2 focus:ring-blue-500 shadow-sm" onclick="app.openHtmlEditor(\'' + f.fieldName + '\')"><span>' + strip + '...</span> <i class="fas fa-pen text-gray-400"></i><div id="html_store_' + f.fieldName + '" style="display:none">' + val + '</div></div>';
                },
                'image': function(f, val, common, valStr, lookups, isDesign) {
                    var tabStrDiv = f.tabIndex ? ' tabindex="' + f.tabIndex + '"' : ' tabindex="0"';
                    var hasImg = f.has_image; var imgP = f.preview;
                    var disp = hasImg ? '<img src="' + imgP + '" class="h-full w-auto object-contain cursor-zoom-in min-h-[20px] rounded" onclick="app.showPreview(\'' + f.fieldName + '\')">' : '<span class="text-xs text-gray-400">Leer</span>';
                    var btns = '<div class="flex flex-col ml-3 gap-2 justify-center"><button type="button" class="text-gray-400 hover:text-blue-500 transition-colors focus:outline-none" onclick="app.openUpload(\'' + f.fieldName + '\')"><i class="fas fa-camera"></i></button>';
                    if (hasImg) btns += '<button type="button" class="text-gray-400 hover:text-blue-500 transition-colors focus:outline-none" onclick="app.downloadImage(\'' + f.fieldName + '\')"><i class="fas fa-download"></i></button>';
                    btns += '</div>';
                    return '<div ' + tabStrDiv + ' class="flex items-center p-2 h-full w-full bg-white border border-gray-300 rounded shadow-sm focus:ring-2 focus:ring-blue-500 outline-none">' + disp + btns + '</div>';
                },
                'signature': function(f, val, common, valStr, lookups, isDesign) {
                    var tabStrDiv = f.tabIndex ? ' tabindex="' + f.tabIndex + '"' : ' tabindex="0"';
                    var sigP = f.preview;
                    var sigImg = sigP ? '<img src="' + sigP + '" class="max-h-full max-w-full object-contain flex-1">' : '<span class="text-xs text-gray-400 flex-1 flex items-center">Leer</span>';
                    return '<div ' + tabStrDiv + ' class="flex items-center justify-between p-2 h-full w-full bg-white border border-gray-300 rounded cursor-pointer hover:bg-gray-50 shadow-sm focus:ring-2 focus:ring-blue-500 outline-none" onclick="app.openSignature(\'' + f.fieldName + '\')">' + sigImg + ' <i class="fas fa-pen-nib text-gray-400 ml-2"></i></div>';
                },
                'GoogleMaps': function(f, val, common, valStr, lookups, isDesign) {
                    return '<div class="flex h-full w-full"><input type="text" ' + common + ' ' + valStr + '><button class="flex items-center justify-center px-3 border border-l-0 border-gray-300 bg-gray-50 text-green-600 rounded-r hover:bg-green-50 transition-colors shadow-sm" tabindex="-1" onclick="app.openMap(\'' + f.fieldName + '\')"><i class="fas fa-map-marker-alt"></i></button></div>';
                },
                'youTube': function(f, val, common, valStr, lookups, isDesign, app) {
                    return '<div class="flex h-full w-full"><input type="text" ' + common + ' ' + valStr + ' placeholder="Video URL"><button class="flex items-center justify-center px-3 border border-l-0 border-gray-300 bg-gray-50 text-red-600 rounded-r hover:bg-red-50 transition-colors shadow-sm" tabindex="-1" onclick="if(!app.isDesign) window.open($(this).closest(\'.flex\').find(\'input\').val(), \'_blank\')"><i class="fas fa-play"></i></button></div>';
                },
                'video': function(f, val, common, valStr, lookups, isDesign, app) {
                    return this.youTube(f, val, common, valStr, lookups, isDesign, app);
                },
                'date': function(f, val, common, valStr, lookups, isDesign) {
                    return '<input type="date" ' + common + ' ' + valStr + '>';
                },
                'integer': function(f, val, common, valStr, lookups, isDesign) {
                    return '<input type="number" step="1" ' + common + ' ' + valStr + '>';
                },
                'decimal': function(f, val, common, valStr, lookups, isDesign) {
                    var step = (f.behavior && f.behavior.decimals !== undefined && f.behavior.decimals !== '') ? Math.pow(10, -(f.behavior.decimals)) : '0.01';
                    return '<input type="number" step="' + step + '" ' + common + ' ' + valStr + '>';
                },
                'button': function(f, val, common, valStr, lookups, isDesign, app) {
                    var tabStr = f.tabIndex ? ' tabindex="' + f.tabIndex + '"' : ' tabindex="0"';                   
                    var text = f.defaultValue || f.label || 'Button';
                    var iconStr = '';
                    if (f.behavior && f.behavior.prefix) {
                        var pStr = f.behavior.prefix.trim();
                        iconStr = pStr.indexOf('fa-') > -1 ? '<i class="' + pStr + ' mx-1"></i>' : '<span class="mx-1">' + pStr + '</span>';
                    }
                    var sufStr = '';
                    if (f.behavior && f.behavior.suffix) {
                        var sStr = f.behavior.suffix.trim();
                        sufStr = sStr.indexOf('fa-') > -1 ? '<i class="' + sStr + ' mx-1"></i>' : '<span class="mx-1">' + sStr + '</span>';
                    }
                    var url = (f.behavior && f.behavior.btnUrl) ? f.behavior.btnUrl.replace(/'/g, "\\'") : '';
                    var target = (f.behavior && f.behavior.btnTarget) ? f.behavior.btnTarget : '_self';
                    var is3d = (f.behavior && f.behavior.btn3d) ? true : false;
                    
                    var typeClass = 'bg-blue-600 hover:bg-blue-700 text-white border-blue-600';
                    var bg3dClass = ' border-blue-800';
                    var styleAttr = 'height:100%; width:100%; transition:all 0.2s;';
                    if (f.style) {
                        if (f.style.textAlign) styleAttr += 'text-align:' + f.style.textAlign + '; ';
                        if (f.style.weight === 'bold') styleAttr += 'font-weight:bold; ';
                        if (f.style.weight === 'italic') styleAttr += 'font-style:italic; ';
                        if (f.style.size) styleAttr += 'font-size:' + f.style.size + '; ';
                    }

                    if (f.behavior && f.behavior.btnStyle) {
                        switch(f.behavior.btnStyle) {
                            case 'secondary': typeClass = 'bg-gray-500 hover:bg-gray-600 text-white border-gray-500'; bg3dClass=' border-gray-700'; break;
                            case 'danger': typeClass = 'bg-red-500 hover:bg-red-600 text-white border-red-500'; bg3dClass=' border-red-800'; break;
                            case 'success': typeClass = 'bg-green-500 hover:bg-green-600 text-white border-green-500'; bg3dClass=' border-green-800'; break;
                            case 'outline': typeClass = 'bg-transparent border-2 border-[var(--ag-color-brand,#3b82f6)] text-[var(--ag-color-brand,#3b82f6)] hover:bg-[var(--ag-color-brand,#3b82f6)] hover:text-white'; bg3dClass=' border-[var(--ag-text-dark,#1e3a8a)]'; break;
                            case 'custom': 
                                typeClass = '';
                                if (f.style && f.style.color) styleAttr += 'color:' + f.style.color + ' !important; ';
                                if (f.style && f.style.backgroundColor) styleAttr += 'background-color:' + f.style.backgroundColor + ' !important; border-color:' + f.style.backgroundColor + ';';
                                break;
                        }
                    }

                    if (is3d) {
                        typeClass += ' border-b-4 hover:border-b-[4px] hover:translate-y-[2px] active:border-b-0 active:translate-y-[4px] active:mt-[4px] transition-all' + bg3dClass;
                    }

                    var clk = "if(!app.isDesign) { if('" + url + "') { app.openBtnUrl('" + url + "', '" + target + "'); } else { app.triggerEvent('onClick', '" + f.fieldName + "'); } }";

                    return '<button ' + tabStr + ' type="button" class="w-full h-full px-4 py-2 font-medium rounded shadow-sm flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-offset-2 ' + typeClass + '" style="' + styleAttr + '" onclick="' + clk + '">' + iconStr + text + sufStr + '</button>';
                },
                'default': function(f, val, common, valStr, lookups, isDesign) {
                    return '<input type="text" ' + common + ' ' + valStr + '>';
                }
            },

            buildPrefixSuffix: function(behStr, isPrepend) {
                if (!behStr) return '';
                var content = behStr.indexOf('fa-') !== -1 ? '<i class="' + behStr + '"></i>' : behStr;
                var radiusClass = isPrepend ? 'rounded-l border-r-0' : 'rounded-r border-l-0';
                return '<span class="inline-flex items-center px-3 ' + radiusClass + ' border border-gray-300 bg-gray-50 text-gray-500 text-sm whitespace-nowrap">' + content + '</span>';
            },

            getInputHtml: function (f) {
                var val = (f.value !== undefined && f.value !== null) ? f.value : ''; var ro = f.readonly;
                var tabStr = f.tabIndex ? ' tabindex="' + f.tabIndex + '"' : ''; 
                var tabStrDiv = f.tabIndex ? ' tabindex="' + f.tabIndex + '"' : ' tabindex="0"';
                
                var inlineStyle = 'height:100%; ';
                if (f.style) {
                    if (f.style.textAlign) inlineStyle += 'text-align:' + f.style.textAlign + ' !important; ';
                    if (f.style.weight === 'bold') inlineStyle += 'font-weight:bold !important; ';
                    if (f.style.weight === 'italic') inlineStyle += 'font-style:italic !important; ';
                    if (f.style.weight === 'monospace') inlineStyle += 'font-family:monospace !important; ';
                    if (f.style.size) inlineStyle += 'font-size:' + f.style.size + ' !important; ';
                    if (f.style.color) inlineStyle += 'color:' + f.style.color + ' !important; ';
                    if (f.style.backgroundColor) inlineStyle += 'background-color:' + f.style.backgroundColor + ' !important; ';
                }
                
                var beh = f.behavior || {};
                var ph = beh.placeholder ? ' placeholder="' + String(beh.placeholder).replace(/"/g, '&quot;') + '"' : '';
                var maxLenAttr = (beh.counter && f.val_max) ? ' maxlength="' + f.val_max + '"' : '';
                
                var evts = ' onclick="app.triggerEvent(\'onClick\', \'' + f.fieldName + '\')" onkeyup="app.triggerEvent(\'onKeyPress\', \'' + f.fieldName + '\')" ';
                if (beh.counter && f.val_max) {
                    evts += ' oninput="app.updateCounter(this, ' + f.val_max + ')" ';
                }
                
                var onBlurStr = 'onblur="app.saveData(this); app.triggerEvent(\'onBlur\', \'' + f.fieldName + '\');';
                if (f.fieldTyp === 'decimal' && beh.decimals !== undefined && beh.decimals !== null && beh.decimals !== '') {
                    onBlurStr += ' app.formatDecimalField(this, ' + beh.decimals + ');';
                }
                onBlurStr += '"';

                // Tailwind base classes for proper appearance
                var twClasses = 'app-input w-full h-full px-3 py-2 bg-white border border-gray-300 rounded text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm transition-colors';
                
                var common = 'class="' + twClasses + '" style="' + inlineStyle + '" data-field="' + f.fieldName + '" ' + evts + ' onfocus="app.trackFocus(this); app.triggerEvent(\'onFocus\', \'' + f.fieldName + '\')" ' + onBlurStr + tabStr + ph + maxLenAttr;
                var valStr = ' value="' + val.toString().replace(/"/g, '&quot;') + '"';

                var htmlOut = '';
                if (ro) { 
                    if (f.fieldTyp == 'image') htmlOut = f.preview ? '<img src="' + f.preview + '" class="h-full w-auto object-contain min-h-[20px] rounded">' : '<span class="text-xs text-gray-400">Leer</span>'; 
                    else htmlOut = '<input type="text" class="' + twClasses + ' bg-gray-50 text-gray-500" style="' + inlineStyle + '" value="' + val + '" disabled>'; 
                } else {
                    var renderer = this.fieldRenderers[f.fieldTyp] || this.fieldRenderers['default'];
                    if (f.fieldTyp === 'button') {
                        htmlOut = renderer.call(this, f, val, '', valStr, this.lookups, this.isDesign, this);
                    } else {
                        var hasPrefixOrSuffix = (beh.prefix || beh.suffix) && ['text', 'integer', 'decimal', 'date', 'multiple_line_text'].indexOf(f.fieldTyp || 'text') !== -1;
                    
                    if (hasPrefixOrSuffix) {
                        // Remove rounded corners on the input where prefix/suffix attaches
                        var modifiedClasses = twClasses.replace('rounded', '');
                        if (beh.prefix && !beh.suffix) modifiedClasses += ' rounded-r';
                        else if (!beh.prefix && beh.suffix) modifiedClasses += ' rounded-l';
                        else if (!beh.prefix && !beh.suffix) modifiedClasses += ' rounded';
                        
                        var modifiedCommon = common.replace('class="' + twClasses + '"', 'class="' + modifiedClasses + '"');
                        htmlOut = renderer.call(this, f, val, modifiedCommon, valStr, this.lookups, this.isDesign, this);
                        
                        var preHtml = this.buildPrefixSuffix(beh.prefix, true);
                        var sufHtml = this.buildPrefixSuffix(beh.suffix, false);
                        htmlOut = '<div class="flex h-full w-full shadow-sm rounded">' + preHtml + htmlOut + sufHtml + '</div>';
                    } else {
                        htmlOut = renderer.call(this, f, val, common, valStr, this.lookups, this.isDesign, this);
                    }
                    }
                }
                
                // Select2 activation marker
                if (beh.searchSelect && f.fieldTyp === 'select' && !this.isDesign) {
                    htmlOut = htmlOut.replace('<select ', '<select data-plugins="select2" ');
                }
                
                if (beh.counter && f.val_max && (f.fieldTyp === 'text' || (!f.fieldTyp) || f.fieldTyp === 'multiple_line_text')) {
                    var curLen = val ? String(val).length : 0;
                    htmlOut += '<div class="text-gray-400 text-right mt-1" id="counter_' + f.fieldName + '" style="font-size:10px; line-height:1;">' + curLen + ' / ' + f.val_max + '</div>';
                    htmlOut = '<div class="relative h-full w-full flex flex-col">' + htmlOut + '</div>';
                }

                return htmlOut;
            },

            openBtnUrl: function(urlPattern, target) {
                var self = this;
                var finalUrl = urlPattern.replace(/\{([^}]+)\}/g, function(match, fieldName) {
                    var el = $('.app-input[data-field="' + fieldName + '"]');
                    if (el.length) return encodeURIComponent(el.val());
                    
                    var fConf = self.config.fields.find(function(c){return c.fieldName == fieldName});
                    if (fConf && fConf.value !== undefined && fConf.value !== null) {
                        return encodeURIComponent(fConf.value);
                    }
                    
                    return match;
                });
                window.open(finalUrl, target || '_blank');
            },

            saveData: function (el) {
                if (this.isDesign) return;
                var $el = $(el); var newVal = $el.val();
                if ($el.closest('button').length) return; // Ignore save for buttons
                var fName = $el.data('field');
                var fConf = this.config.fields.find(function (f) { return f.fieldName == fName; });

                if (fConf && typeof geValidator !== 'undefined') {
                    var rules = { required: fConf.val_req, minLength: fConf.val_min, maxLength: fConf.val_max, regex: fConf.val_regex, regexErrorMsg: fConf.val_regex_msg };
                    var validation = geValidator.validate(newVal, rules);
                    if (!validation.valid) { geValidator.showError($el, validation.msg); return; } else { geValidator.clearError($el); }
                }

                if ($(el).hasClass('ui-autocomplete-input') && $(el).autocomplete("widget").is(":visible")) return;
                if (String(this.tempVal) !== String(newVal)) { 
                    this.pushDataUndo(fName, newVal, this.tempVal); 
                    this.saveDataDirect(fName, newVal, el); 
                    this.triggerEvent('onChange', fName);
                }
            },

            updateCounter: function(el, max) {
                if (!max) return;
                var val = $(el).val() || '';
                var count = val.length;
                var $c = $(el).closest('.position-relative').find('.text-muted.text-right');
                if ($c.length) {
                    $c.text(count + ' / ' + max);
                }
            },
            
            formatDecimalField: function(el, decimals) {
                if (decimals === undefined || decimals === null || decimals === '') return;
                var valStr = $(el).val();
                if (!valStr) return;
                var parsed = parseFloat(valStr.replace(',', '.'));
                if (!isNaN(parsed)) {
                    el.value = parsed.toFixed(decimals);
                }
            },

            eventCallStack: [],
            eventDebounceTimers: {},

            triggerEvent: function(evtName, fName, isSystemTrigger) {
                if (this.isDesign) return;
                var fConf = this.config.fields.find(function(c) { return c.fieldName == fName; });
                if (!fConf || !fConf.events || !fConf.events[evtName]) return;
                
                var stackSizeLimit = 10;
                var eventSignature = evtName + '_' + fName;
                
                if (isSystemTrigger) {
                    this.eventCallStack.push(eventSignature);
                    if (this.eventCallStack.length > stackSizeLimit) {
                        console.error('Endlosschleife blockiert!', this.eventCallStack);
                        app.showErrorToast("System", "Endlosschleife ('" + fName + "') wegen zu großer Tiefe blockiert!");
                        this.eventCallStack = []; 
                        return;
                    }
                } else {
                    this.eventCallStack = [eventSignature];
                }
                
                var self = this;
                var executeServerEvent = function() {
                    var formData = {};
                    $('.app-input').each(function() {
                        var n = $(this).data('field');
                        if (n) {
                            if ($(this).is(':checkbox') || $(this).is(':radio')) {
                                formData[n] = $(this).val();
                            } else {
                                formData[n] = $(this).val();
                            }
                        }
                    });
                    
                    self.config.fields.forEach(function(fc) {
                        if (['checkbox', 'double_select'].indexOf(fc.fieldTyp) !== -1) {
                             // potential state reading logic
                        }
                    });
                    
                    $.post(AJAX_URL, {
                        action: 'execute_event',
                        gridName: GRID_NAME,
                        tableName: self.config.tableName,
                        id: self.currentId,
                        eventName: evtName,
                        fieldName: fName,
                        formData: JSON.stringify(formData)
                    }, function(res) {
                        if (res.status === 'ok' && res.data) {
                            for (var key in res.data) {
                                if (res.data.hasOwnProperty(key)) {
                                    var el = $('.app-input[data-field="'+key+'"]');
                                    if (el.length && el.val() != res.data[key]) {
                                        el.val(res.data[key]);
                                        
                                        var fc = self.config.fields.find(function(c){return c.fieldName == key;});
                                        if (fc && fc.fieldTyp === 'decimal' && fc.behavior && fc.behavior.decimals !== undefined && fc.behavior.decimals !== '') {
                                            self.formatDecimalField(el[0], parseInt(fc.behavior.decimals));
                                            res.data[key] = el.val(); // update res.data to the formatted string
                                        }

                                        self.saveDataDirect(key, res.data[key], null);
                                        el.addClass('ring-2 ring-green-500 bg-green-50 border-transparent transition-all duration-300');
                                        setTimeout((function(elem) { return function(){elem.removeClass('ring-2 ring-green-500 bg-green-50 border-transparent');}; })(el), 1200);
                                        // Cascade the changes (infinite loops caught by stack!)
                                        self.triggerEvent('onChange', key, true);
                                    }
                                }
                            }
                        } else if (res.error) {
                            var targetName = fName ? 'Feld: ' + fName : 'Globale Ebene';
                            self.showErrorToast("Laufzeitfehler in " + evtName + " ("+targetName+")", res.error);
                        }
                        self.eventCallStack.pop();
                    }, 'json').fail(function() {
                        self.showErrorToast("Server-Fehler", "Das Skript konnte auf dem Server nicht geladen oder ausgeführt werden (Fatal Error).");
                        self.eventCallStack.pop();
                    });
                };

                if (evtName === 'onKeyPress' || evtName === 'onKeyUp') {
                    if (this.eventDebounceTimers[eventSignature]) {
                        clearTimeout(this.eventDebounceTimers[eventSignature]);
                    }
                    this.eventDebounceTimers[eventSignature] = setTimeout(function() {
                        executeServerEvent();
                    }, 400); 
                } else {
                    executeServerEvent();
                }
            },

            showErrorToast: function(title, msg) {
                if ($('#error-toast-container').length === 0) {
                    $('body').append('<div id="error-toast-container" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;"></div>');
                }
                var id = 'toast_' + Math.random().toString(36).substr(2, 9);
                var html = '<div id="'+id+'" class="toast text-white align-items-center border-0 shadow" role="alert" aria-live="assertive" aria-atomic="true" data-delay="8000" style="background:#dc3545;">' +
                           '<div class="toast-header text-white border-0" style="background:#c82333;"><strong class="mr-auto"><i class="fas fa-exclamation-triangle"></i> ' + title + '</strong><button type="button" class="ml-2 mb-1 close text-white" data-dismiss="toast" style="outline:none;"><span>&times;</span></button></div>' +
                           '<div class="toast-body" style="font-family:monospace; font-size:12px;">' + msg + '</div></div>';
                $('#error-toast-container').append(html);
                $('#'+id).toast('show');
                $('#'+id).on('hidden.bs.toast', function () { $(this).remove(); });
            },

            trackFocus: function (el) { this.tempVal = $(el).val(); },
            manualSave: function (f, v) { if (this.isDesign) return; var self = this; this.pushDataUndo(f, v, null); this.saveDataDirect(f, v, null, function(){ self.loadData(self.currentId); }); },
            saveDataDirect: function (f, v, el, cb) { var fConf = this.config.fields.find(function (c) { return c.fieldName == f; }); if (fConf && fConf.isCustom) { if(el) { $(el).addClass('is-valid'); setTimeout(function () { $(el).removeClass('is-valid') }, 1000); } if(cb) cb(); return; } $.post(AJAX_URL, { action: 'save_data', gridName: GRID_NAME, tableName: this.config.tableName, id: this.currentId, field: f, value: v }, function (res) { if (res.status == 'ok' && el) { $(el).addClass('is-valid'); setTimeout(function () { $(el).removeClass('is-valid') }, 1000); } if(cb) cb(res); }, 'json'); },
            nav: function (d) { $.post(AJAX_URL, { action: 'navigate', gridName: GRID_NAME, tableName: this.config.tableName, currentId: this.currentId, direction: d }, function (r) { if (r.newId) app.loadData(r.newId); }, 'json'); },
            updateLink: function (el, type) { var val = $(el).val(); var btn = $(el).next().find('a'); if (type == 'mailto') btn.attr('href', 'mailto:' + val); else btn.attr('href', (val.indexOf('http') !== 0 ? 'https://' : '') + val); },
            openMap: function (f) { if (this.isDesign) return; mapManager.openMap(this.currentId, f); },

            openUpload: function (f) { if (this.isDesign) return; var fConf = this.config.fields.find(function(c){return c.fieldName==f;}); if(fConf && fConf.isCustom) { alert("Bilder für benutzerdefinierte Felder können nur im Config-Bereich über eine URL als Standardwert eingefügt werden."); return; } var self = this; $('#dynamic-modals-container').html('<div class="modal fade" id="dynUp"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h6 class="modal-title">Upload</h6><button type="button" class="close" data-dismiss="modal">×</button></div><div class="modal-body"><div class="custom-file"><input type="file" class="custom-file-input" id="f-in"><label class="custom-file-label">Wählen...</label></div></div><div class="modal-footer"><button class="btn btn-primary btn-save">Upload</button></div></div></div></div>'); $('#dynUp').modal('show'); $('#f-in').on('change', function () { $(this).next().html($(this).val().split('\\').pop()); }); $('#dynUp .btn-save').click(function () { var file = $('#f-in').prop('files')[0]; if (!file) return; var fd = new FormData(); fd.append('action', 'upload_file'); fd.append('tableName', self.config.tableName); fd.append('id', self.currentId); fd.append('field', f); fd.append('file', file); $.ajax({ url: AJAX_URL, type: 'POST', data: fd, contentType: false, processData: false, success: function () { $('#dynUp').modal('hide'); self.loadData(self.currentId); } }); }); },
            openSignature: function (f) { if (this.isDesign) return; var fConf = this.config.fields.find(function(c){return c.fieldName==f;}); if(fConf && fConf.isCustom) { alert("Nicht verfügbar für benutzerdefinierte Felder."); return; } var self = this; $.post(AJAX_URL, { action: 'load_full_image', tableName: this.config.tableName, id: this.currentId, field: f }, function (res) { $('#dynamic-modals-container').html('<div class="modal fade" id="dynSig"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h6 class="modal-title">Unterschrift</h6><button type="button" class="close" data-dismiss="modal">×</button></div><div class="modal-body text-center"><canvas id="sig-pad" width="400" height="200" class="border"></canvas><br><button class="btn btn-sm btn-light mt-2 btn-clear">Löschen</button></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Abbrechen</button><button class="btn btn-primary btn-save">Speichern</button></div></div></div></div>'); $('#dynSig').modal('show'); var pad = new SignaturePad(document.getElementById('sig-pad')); if (res.data) pad.fromDataURL(res.data); $('#dynSig .btn-clear').click(function () { pad.clear(); }); $('#dynSig .btn-save').click(function () { if (pad.isEmpty()) return; fetch(pad.toDataURL()).then(r => r.blob()).then(b => { var fd = new FormData(); fd.append('action', 'upload_file'); fd.append('tableName', self.config.tableName); fd.append('id', self.currentId); fd.append('field', f); fd.append('file', b); $.ajax({ url: AJAX_URL, type: 'POST', data: fd, contentType: false, processData: false, success: function () { $('#dynSig').modal('hide'); self.loadData(self.currentId); } }); }); }); }, 'json'); },
            openHtmlEditor: function (f) { if (this.isDesign) return; var self = this; var c = $('#html_store_' + f).html(); $('#dynamic-modals-container').html('<div class="modal fade" id="dynHtml"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h6 class="modal-title">HTML Editor</h6><button type="button" class="close" data-dismiss="modal">×</button></div><div class="modal-body"><textarea id="tb"></textarea></div><div class="modal-footer"><button class="btn btn-primary btn-save">Speichern</button></div></div></div></div>'); $('#dynHtml').modal('show'); $('#tb').trumbowyg({ btns: [ ['viewHTML'], ['undo', 'redo'], ['formatting'], ['strong', 'em', 'underline', 'del'], ['superscript', 'subscript'], ['fontsize'], ['foreColor', 'backColor'], ['link'], ['insertImage'], ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'], ['unorderedList', 'orderedList'], ['horizontalRule'], ['removeformat'], ['fullscreen'] ] }).trumbowyg('html', c); $('#dynHtml .btn-save').off('click').click(function () { var n = $('#tb').trumbowyg('html'); self.pushDataUndo(f, n, c); var strip = n ? String(n).replace(/<[^>]*>?/gm, '').substring(0, 30) : 'Leer'; $('#inp_' + f + ' .html-preview-box').html(strip + '... <i class="fas fa-pen text-muted"></i><div id="html_store_' + f + '" style="display:none">' + n + '</div>'); $('#dynHtml').modal('hide'); self.saveDataDirect(f, n, null, function(){ self.loadData(self.currentId); }); }); },
            openMultiSelect: function (f, val) { if (this.isDesign) return; var self = this; var o = this.lookups[f] || {}; var s = val ? val.split(',') : []; var h = '<div class="row">'; for (var k in o) h += '<div class="col-6"><div class="custom-control custom-checkbox"><input type="checkbox" class="custom-control-input ms-chk" id="m_' + k + '" value="' + k + '" ' + (s.includes(k.toString()) ? 'checked' : '') + '><label class="custom-control-label" for="m_' + k + '">' + o[k] + '</label></div></div>'; $('#dynamic-modals-container').html('<div class="modal fade" id="dynMulti"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h6 class="modal-title">Auswahl</h6></div><div class="modal-body">' + h + '</div><div class="modal-footer"><button class="btn btn-primary btn-save">OK</button></div></div></div></div>'); $('#dynMulti').modal('show'); $('#dynMulti .btn-save').click(function () { var arr = []; $('#dynMulti .ms-chk:checked').each(function () { arr.push($(this).val()) }); var nv = arr.join(','); self.pushDataUndo(f, nv, val); $('#dynMulti').modal('hide'); self.saveDataDirect(f, nv, null, function(){ self.loadData(self.currentId); }); }); },
            showPreview: function (f) { if (this.isDesign) return; $.post(AJAX_URL, { action: 'load_full_image', tableName: this.config.tableName, id: this.currentId, field: f }, function (res) { if (res.status == 'ok') { $('#previewImageFull').attr('src', res.data); $('#imagePreviewModal').modal('show'); } }, 'json'); },
            downloadImage: function (f) { if (this.isDesign) return; $.post(AJAX_URL, { action: 'load_full_image', tableName: this.config.tableName, id: this.currentId, field: f }, function (res) { if (res.status == 'ok') { var a = document.createElement("a"); a.href = res.data; a.download = 'img.png'; document.body.appendChild(a); a.click(); document.body.removeChild(a); } }, 'json'); },

            codeEditors: {},
            setEditorValue: function(id, val) {
                $('#' + id).val(val);
                if (this.codeEditors && this.codeEditors[id]) {
                    this.codeEditors[id].setValue(val);
                }
            },
            refreshEditors: function() {
                setTimeout(function() {
                    for (var id in app.codeEditors) {
                        if($('#' + id).is(':visible') || $('#' + id).closest('.tab-pane.active').length || $('#' + id).closest('#config-detail-view').is(':visible')) {
                            app.codeEditors[id].refresh();
                        }
                    }
                }, 150);
            },
            testSql: function() {
                var sql = this.codeEditors['det_sql_query'] ? this.codeEditors['det_sql_query'].getValue() : $('#det_sql_query').val();
                if(!sql || sql.trim() === '') {
                    this.showErrorToast("SQL-Test", "Bitte erst eine SQL Abfrage eingeben!");
                    return;
                }
                $.post(AJAX_URL, {
                    action: 'test_sql',
                    sql: sql
                }, function(res) {
                    if (res.status === 'ok') {
                        var details = "Abfrage erfolgreich!\n\nBeispiele (Max. 5):\n" + JSON.stringify(res.data, null, 2);
                        alert(details);
                    } else if (res.error) {
                        app.showErrorToast("SQL-Fehler", res.error);
                    }
                }, 'json').fail(function() {
                    app.showErrorToast("Server-Fehler", "SQL konnte aufgrund eines Serverfehlers nicht getestet werden.");
                });
            },
            loadCodeEditors: function() {
                if (window.CodeMirror) { this.initCodeMirrorEditors(); return; }
                $('#loading').show();
                $('<link/>', {rel:'stylesheet', href:'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.css'}).appendTo('head');
                $('<link/>', {rel:'stylesheet', href:'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/theme/dracula.min.css'}).appendTo('head');
                $('<link/>', {rel:'stylesheet', href:'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/addon/hint/show-hint.min.css'}).appendTo('head');
                $('<style>').text('.CodeMirror { font-family: monospace; font-size: 13px !important; border-radius: 4px; border: 1px solid #ced4da; margin-bottom: 5px; } .CodeMirror-hints { z-index: 10000 !important; font-family: monospace; font-size: 13px; }').appendTo('head');
                
                var scripts = [
                    'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/codemirror.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/addon/edit/matchbrackets.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/addon/hint/show-hint.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/xml/xml.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/javascript/javascript.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/css/css.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/clike/clike.min.js',
                    'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.13/mode/php/php.min.js'
                ];
                var i = 0;
                function loadNext() {
                    if (i < scripts.length) {
                        $.getScript(scripts[i], function() { i++; loadNext(); });
                    } else {
                        $('#loading').hide();
                        app.registerCodeMirrorHints();
                        app.initCodeMirrorEditors();
                    }
                }
                loadNext();
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
                    app.config.fields.forEach(function(f) {
                        if (f.fieldName.toLowerCase().indexOf(currentWord.toLowerCase()) !== -1) {
                            list.push({
                                text: f.fieldName + "']",
                                displayText: f.fieldName + " (" + (f.label || 'unbenannt') + ")"
                            });
                        }
                    });
                    return {
                        list: list,
                        from: CodeMirror.Pos(cursor.line, start - currentWord.length),
                        to: CodeMirror.Pos(cursor.line, end)
                    };
                });
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
            initCodeMirrorEditors: function() {
                var textareas = ['cfg_evt_onload', 'cfg_evt_onbeforesave', 'cfg_evt_onaftersave', 'cfg_evt_libraries', 'det_evt_onchange', 'det_evt_onclick', 'det_evt_onfocus', 'det_evt_onblur', 'det_evt_onkeypress', 'det_sql_query'];
                var theme = this.isDarkTheme() ? 'dracula' : 'default';
                
                textareas.forEach(function(id) {
                    var el = document.getElementById(id);
                    if (el && !app.codeEditors[id]) {
                        // Label modifizieren (AI Pencil hinzufügen, falls noch nicht da)
                        var label = $(el).prev('label');
                        if (label.length && label.find('.ai-code-btn').length === 0) {
                            label.addClass('d-flex justify-content-between w-100');
                            label.append('<i class="fas fa-magic text-primary ai-code-btn" data-target="'+id+'" style="cursor:pointer; display:none;" title="KI Code-Assistent" onclick="app.openAiCodeModal(\''+id+'\')"></i>');
                        }

                        var mode = 'application/x-httpd-php-open';
                        if (id === 'det_sql_query') mode = 'text/x-sql';

                        var cm = CodeMirror.fromTextArea(el, {
                            lineNumbers: true,
                            mode: mode,
                            theme: theme,
                            indentUnit: 4,
                            matchBrackets: true,
                            viewportMargin: Infinity,
                            extraKeys: {"Ctrl-Space": "autocomplete"}
                        });
                        if (id !== 'cfg_evt_libraries' && id !== 'det_sql_query') {
                            cm.on("keyup", function (cm, event) {
                                if (!cm.state.completionActive && event.keyCode >= 65 && event.keyCode <= 90) {
                                    CodeMirror.commands.autocomplete(cm, null, {completeSingle: false});
                                }
                            });
                        }
                        if (id === 'det_sql_query') {
                            cm.on("blur", function() { app.updateFieldPreview(); });
                        }
                        cm.on('change', function(instance) { instance.save(); });
                        cm.on('keyup', function(cm, event) {
                            if (!cm.state.completionActive && event.key === "'" || event.key === '"') {
                                var cur = cm.getCursor();
                                var token = cm.getTokenAt(cur);
                                var line = cm.getLine(cur.line).slice(0, cur.ch);
                                if (line.match(/\$data\[['"]$/)) {
                                    CodeMirror.showHint(cm, CodeMirror.hint.ag_php_data, {completeSingle: false});
                                }
                            }
                        });
                        cm.setSize(null, id === 'cfg_evt_libraries' ? "300px" : "150px");
                        app.codeEditors[id] = cm;
                    } else if (app.codeEditors[id]) {
                        app.codeEditors[id].setOption("theme", theme);
                    }
                });
                app.refreshEditors();
                app.checkAiPencils();
            },

            updateBehaviorVisibility: function() {
                var t = $('#det_type').val() || 'text';
                
                if (t === 'text' || t === 'multiple_line_text') $('#beh_row_counter').show();
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
                    
                    $('#det_label').prev('label').text('Label (Feld-Beschriftung)');
                    
                    if ($('#btn-hint-alert').length) $('#btn-hint-alert').hide();
                }
            },

            checkAiPencils: function() {
                var gem = $('#cfg_api_gemini').val();
                var gpt = $('#cfg_api_chatgpt').val();
                var cla = $('#cfg_api_anthropic').val();
                
                var hasKey = Boolean(gem || gpt || cla);
                
                if (!hasKey && this.config.apiKeys) {
                    hasKey = Boolean(this.config.apiKeys.gemini || this.config.apiKeys.chatgpt || this.config.apiKeys.anthropic);
                }
                
                if (hasKey) {
                    $('.ai-code-btn').show();
                } else {
                    $('.ai-code-btn').hide();
                }
            },

            updateFieldPreview: function() {
                var t = $('#det_type').val() || 'text';
                
                // Label Updates
                var lbl = $('#preview-label');
                if (t === 'button') {
                    lbl.hide();
                } else {
                    lbl.show();
                    lbl.text($('#det_label').val() || 'Beispiel Label');
                    
                    var la = $('#det_lbl_align').val();
                    if (la) { lbl.css('display', 'block'); lbl.css('text-align', la); } else { lbl.css('display', 'inline-block'); lbl.css('text-align', 'left'); }
                    
                    var lw = $('#det_lbl_weight').val();
                    lbl.css('font-weight', lw === 'bold' ? 'bold' : 'normal');
                    lbl.css('font-style', lw === 'italic' ? 'italic' : 'normal');
                    lbl.css('font-size', $('#det_lbl_size').val() || '14px');
                    lbl.css('color', $('#det_lbl_color_en').is(':checked') ? $('#det_lbl_color').val() : '#333');
                }

                // Dynamic Input Element based on Type
                var t = $('#det_type').val() || 'text';
                var container = $('#preview-input-container');
                var valStr = 'Muster 1234,56';
                if (t === 'checkbox' || t === 'radio') valStr = '';
                
                var lType = $('#det_lookup_type').val();
                var optionsArr = [];
                if (['select', 'checkbox', 'radio'].indexOf(t) !== -1) {
                    if (lType === 'manual') {
                        $('#det_manual_rows .lookup-row').each(function() {
                            var v = $(this).find('.lookup-val').val();
                            if (v && v.trim()) optionsArr.push(v);
                        });
                    } else if (lType === 'sql') {
                        var hasQuery = app.codeEditors['det_sql_query'] ? app.codeEditors['det_sql_query'].getValue() : $('#det_sql_query').val();
                        if (hasQuery && hasQuery.trim()) {
                            if (app._lastSqlPreviewQuery !== hasQuery && !app._isFetchingSql) {
                                app._isFetchingSql = true;
                                $.post(AJAX_URL, { action: 'preview_sql_query', sql: hasQuery, gridName: GRID_NAME }, function(res) {
                                    app._isFetchingSql = false;
                                    app._lastSqlPreviewQuery = hasQuery;
                                    if(res.status === 'ok' && res.data) {
                                        app._lastSqlPreviewData = res.data;
                                    } else if (res.error) {
                                        app._lastSqlPreviewData = ["(SQL Fehler)"];
                                    } else {
                                        app._lastSqlPreviewData = ["(Kein Resultat)"];
                                    }
                                    app.updateFieldPreview();
                                }, 'json').fail(function() { app._isFetchingSql = false; });
                            }
                            if (app._lastSqlPreviewData && app._lastSqlPreviewData.length > 0) {
                                app._lastSqlPreviewData.forEach(function(o) { optionsArr.push(o); });
                            } else {
                                optionsArr.push("Lade Vorschau...");
                            }
                        }
                    }
                    if (optionsArr.length === 0) {
                        optionsArr.push('Keine Optionen definiert');
                    }
                }
                
                var newHtml = '';
                var ph = $('#det_beh_placeholder').val();
                var phAttr = ph ? ' placeholder="' + ph.replace(/"/g, '&quot;') + '"' : '';
                var dec = $('#det_beh_decimals').val();
                var step = dec ? Math.pow(10, -parseInt(dec)) : '0.01';
                var commonDef = 'id="preview-input" class="form-control"' + phAttr;
                
                switch(t) {
                    case 'multiple_line_text': newHtml = '<textarea ' + commonDef + ' rows="2">' + valStr + '</textarea>'; break;
                    case 'date': newHtml = '<input type="date" ' + commonDef + ' value="2026-04-01">'; break;
                    case 'integer': newHtml = '<input type="number" step="1" ' + commonDef + ' value="100">'; break;
                    case 'decimal': newHtml = '<input type="number" step="' + step + '" ' + commonDef + ' value="100.50">'; break;
                    case 'select': 
                        var selHtml = '';
                        optionsArr.forEach(function(o) { selHtml += '<option>'+o+'</option>'; });
                        newHtml = '<select ' + commonDef + '>' + selHtml + '</select>'; 
                        break;
                    case 'checkbox': newHtml = '<button class="btn btn-sm btn-light border btn-block text-left" id="preview-input">1 gewählt <i class="fas fa-caret-down float-right"></i></button>'; break;
                    case 'radio': 
                        var rHtml = '';
                        optionsArr.forEach(function(o, i) { 
                            rHtml += '<label class="btn btn-outline-secondary '+(i===0?'active':'')+'" style="flex:1"><input type="radio" '+(i===0?'checked':'')+'> '+o+'</label>'; 
                        });
                        newHtml = '<div class="btn-group btn-group-sm btn-group-toggle w-100" data-toggle="buttons" id="preview-input">'+rHtml+'</div>'; 
                        break;
                    case 'image': case 'signature': newHtml = '<div class="d-flex align-items-center bg-white p-2 border rounded" id="preview-input" style="height:38px"><span class="small text-muted">Leer</span><i class="fas fa-camera btn-icon ml-auto"></i></div>'; break;
                    case 'button':
                        var pTxt = valStr || 'Button';
                        var pseudoF = {
                            fieldName: 'previewBtn', fieldTyp: 'button', label: $('#det_label').val(), defaultValue: '',
                            behavior: {
                                prefix: $('#det_beh_prefix').val(), suffix: $('#det_beh_suffix').val(), btnStyle: $('#det_beh_btnStyle').val(), btn3d: $('#det_beh_btn3d').is(':checked')
                            },
                            style: {
                                textAlign: $('#det_style_align').val(), weight: $('#det_style_weight').val(), size: $('#det_style_size').val(),
                                color: $('#det_style_color_en').is(':checked') ? $('#det_style_color').val() : '',
                                backgroundColor: $('#det_style_bg_en').is(':checked') ? $('#det_style_bg').val() : ''
                            }
                        };
                        newHtml = app.getInputHtml(pseudoF);
                        break;
                    default: newHtml = '<input type="text" ' + commonDef + ' value="' + valStr + '">'; break;
                }
                
                var pre = $('#det_beh_prefix').val();
                var suf = $('#det_beh_suffix').val();
                if ((pre || suf) && ['text', 'integer', 'decimal', 'date', 'multiple_line_text'].indexOf(t) !== -1) {
                    var preHtml = app.buildPrefixSuffix(pre, true);
                    var sufHtml = app.buildPrefixSuffix(suf, false);
                    newHtml = '<div class="input-group input-group-sm h-100">' + preHtml + newHtml + sufHtml + '</div>';
                }
                
                if ($('#det_beh_counter').is(':checked') && (t === 'text' || t === 'multiple_line_text')) {
                    newHtml = '<div class="position-relative">' + newHtml + '<div class="text-muted text-right" id="preview-counter" style="font-size:10px; line-height:1; margin-top:2px;">0 / ' + ($('#det_val_max').val() || 'Max') + '</div></div>';
                }
                
                container.html(newHtml);
                
                if ($('#det_beh_counter').is(':checked')) {
                    var $pi = $('#preview-input');
                    $pi.on('input', function() {
                        var max = $('#det_val_max').val() || 'Max';
                        $('#preview-counter').text(($pi.val() || '').length + ' / ' + max);
                    });
                }

                // Apply CSS to the freshly built element
                var inp = $('#preview-input');
                var textA = $('#det_style_align').val() || 'left';
                var sW = $('#det_style_weight').val();
                var sS = $('#det_style_size').val() || '14px';
                var col = $('#det_style_color_en').is(':checked') ? $('#det_style_color').val() : '';
                var bgc = $('#det_style_bg_en').is(':checked') ? $('#det_style_bg').val() : '';
                
                var inlineCss = 'text-align: ' + textA + ' !important; ';
                inlineCss += 'font-weight: ' + (sW === 'bold' ? 'bold' : 'normal') + ' !important; ';
                inlineCss += 'font-style: ' + (sW === 'italic' ? 'italic' : 'normal') + ' !important; ';
                inlineCss += 'font-family: ' + (sW === 'monospace' ? 'monospace' : 'inherit') + ' !important; ';
                inlineCss += 'font-size: ' + sS + ' !important; ';
                
                if (col) {
                    inlineCss += 'color: ' + col + ' !important; ';
                    if (t === 'radio') {
                        container.find('.btn-outline-secondary').attr('style', 'color: ' + col + ' !important');
                    }
                }
                if (t !== 'button' && bgc) {
                    inlineCss += 'background-color: ' + bgc + ' !important; ';
                    if (t === 'radio') {
                        container.find('.btn-outline-secondary').attr('style', 'background-color: ' + bgc + ' !important');
                    }
                }
                
                if (t !== 'button') inp.attr('style', inlineCss);
            },

            openConfigEditor: function () {
                $('#cfg_table').val(this.config.tableName);
                $('#cfg_cw').val((this.config.canvas && this.config.canvas.width) ? this.config.canvas.width : 1000);
                $('#cfg_ch').val((this.config.canvas && this.config.canvas.height) ? this.config.canvas.height : 800);
                $('#cfg_api_gemini').val(this.config.apiKeys ? this.config.apiKeys.gemini : '');
                $('#cfg_api_chatgpt').val(this.config.apiKeys ? this.config.apiKeys.chatgpt : '');
                $('#cfg_api_anthropic').val(this.config.apiKeys ? this.config.apiKeys.anthropic : '');
                
                if (!this.config.events) this.config.events = {};
                app.setEditorValue('cfg_evt_onload', this.config.events.onLoad || '');
                app.setEditorValue('cfg_evt_onbeforesave', this.config.events.onBeforeSave || '');
                app.setEditorValue('cfg_evt_onaftersave', this.config.events.onAfterSave || '');
                app.setEditorValue('cfg_evt_libraries', this.config.events.libraries || '');
                
                $('#config-json-editor').val(JSON.stringify(this.config, null, 4));
                this.renderConfigFields();
                this.renderTabOrder();
                $('#config-main-view').show(); $('#config-detail-view').hide();
                $('#config-sidebar').show();

                this.loadCodeEditors();

                if (!$('#config-sidebar').hasClass('ui-resizable')) {
                    $('#config-sidebar').resizable({
                        handles: 'w',
                        minWidth: 400,
                        maxWidth: 900
                    });
                }
            },
            closeConfigSidebar: function() { $('#config-sidebar').hide(); },
            renderConfigFields: function () { 
                var html = '<table class="table table-sm table-hover"><thead><tr><th>Feld</th><th>Label</th><th>Typ</th><th>RO</th><th>Hidden</th><th style="width:90px"></th></tr></thead><tbody>'; 
                var self = this;
                this.config.fields.forEach(function (f, i) { 
                    var isCst = f.isCustom ? true : false;
                    var cstBadge = isCst ? ' <span class="badge badge-info py-0 px-1" style="font-size:0.6rem">Custom</span>' : '';
                    var delBtn = isCst ? '<button class="btn btn-sm btn-outline-danger ml-1" onclick="app.deleteCustomField(' + i + ')" title="Feld löschen"><i class="fas fa-trash"></i></button>' : '';
                    html += '<tr data-idx="' + i + '"><td><small class="font-weight-bold">' + f.fieldName + cstBadge + '</small></td><td>' + (f.label || f.fieldName) + '</td><td>' + (f.fieldTyp || 'text') + '</td><td class="text-center"><input type="checkbox" class="c-ro" ' + (f.readonly ? 'checked' : '') + '></td><td class="text-center"><input type="checkbox" class="c-hid" ' + (f.hidden ? 'checked' : '') + '></td><td class="text-right d-flex justify-content-end align-items-center"><button class="btn btn-sm btn-light border" onclick="app.editFieldDetail(' + i + ')" title="Konfigurieren"><i class="fas fa-cog"></i></button>' + delBtn + '</td></tr>'; 
                }); 
                html += '</tbody></table><button class="btn btn-sm btn-success mt-2 mb-4" onclick="app.addNewCustomField()"><i class="fas fa-plus"></i> Neues benutzerdefiniertes Feld</button>'; 
                $('#content-fld').html(html); 
            },
            addNewCustomField: function() {
                var newIdx = 1;
                var fn = 'benutzerdefiniertes_feld_' + newIdx;
                var searchLoop = true;
                while(searchLoop) {
                    var exists = this.config.fields.some(function(f){ return f.fieldName === fn; });
                    if(exists) { newIdx++; fn = 'benutzerdefiniertes_feld_' + newIdx; }
                    else { searchLoop = false; }
                }
                var lbl = 'Benutzerdefiniertes Feld ' + newIdx;
                var typ = 'text';
                
                var maxIdx = 0; var maxY = 25;
                this.config.fields.forEach(function(f) {
                    if(f.tabIndex && f.tabIndex > maxIdx) maxIdx = f.tabIndex;
                    if(f.form && f.form.lbl && f.form.lbl.y > maxY) maxY = f.form.lbl.y;
                });
                
                var newField = {
                    fieldName: fn,
                    label: lbl,
                    fieldTyp: typ,
                    isCustom: true,
                    width: 250,
                    height: null,
                    readonly: false,
                    hidden: false,
                    tabIndex: maxIdx + 1,
                    form: { lbl: { x: 20, y: maxY + 45 }, inp: { x: 150, y: maxY + 40 } },
                    defaultValue: ''
                };
                this.config.fields.push(newField);
                this.renderConfigFields();
                this.editFieldDetail(this.config.fields.length - 1); 
            },
            deleteCustomField: function(idx) {
                if(!confirm("Möchten Sie dieses benutzerdefinierte Feld unwiderruflich löschen?")) return;
                this.config.fields.splice(idx, 1);
                this.renderConfigFields();
                // Speichere die neu gerenderte Tabelle direkt sicherheitshalber ins JSON
                this.saveConfigFull();
            },
            renderTabOrder: function () { var html = ''; var sortedFields = this.config.fields.slice().sort(function (a, b) { return (a.tabIndex || 9999) - (b.tabIndex || 9999); }); sortedFields.forEach(function (f) { html += '<li class="list-group-item d-flex justify-content-between align-items-center bg-white" data-field="' + f.fieldName + '" style="cursor: grab;"><div class="d-flex align-items-center"><i class="fas fa-grip-vertical text-muted mr-3"></i> <span class="font-weight-bold">' + (f.label || f.fieldName) + '</span></div><span class="badge badge-light border">' + (f.fieldTyp) + '</span></li>'; }); var $sortable = $('#sortable-tab-order'); $sortable.html(html); if ($sortable.data('ui-sortable')) $sortable.sortable('destroy'); $sortable.sortable({ axis: 'y', opacity: 0.8, cursor: 'grabbing' }); },
            editFieldDetail: function (idx) { 
                this.currentEditFieldIdx = idx; 
                var f = this.config.fields[idx]; 
                
                if (f.isCustom) {
                    $('#detail-field-name').html('<input type="text" id="det_field_name_edit" class="form-control form-control-sm" value="' + f.fieldName + '">');
                } else {
                    $('#detail-field-name').text(f.fieldName); 
                }

                var types = ['text', 'multiple_line_text', 'date', 'integer', 'decimal', 'select', 'radio', 'checkbox', 'htmlEditor', 'image', 'signature', 'GoogleMaps', 'email', 'url', 'youTube', 'video', 'button']; 
                var opts = ''; 
                types.forEach(function (t) { opts += '<option value="' + t + '" ' + (f.fieldTyp == t ? 'selected' : '') + '>' + t + '</option>'; }); 
                $('#det_type').html(opts); 
                $('#det_label').val(f.label || f.fieldName); 
                $('#det_label').off('keyup').on('keyup', function() { app.updateFieldPreview(); });
                $('#det_width').val(f.width || 200); 
                $('#det_height').val(f.height || ''); 
                $('#det_help').val(f.helpText || ''); 

                // Event listener on type selector for re-rendering custom default tools
                $('#det_type').off('change').on('change', function() {
                    app.checkHeightEnabled();
                    app.renderConfigDefaultValue(idx); 
                    app.updateBehaviorVisibility();
                });
                
                app.renderConfigDefaultValue(idx);

                $('#det_val_req').prop('checked', f.val_req || false); $('#det_val_min').val(f.val_min || ''); $('#det_val_max').val(f.val_max || ''); $('#det_val_regex').val(f.val_regex || ''); $('#det_val_regex_msg').val(f.val_regex_msg || ''); var lType = ''; if (f.lookup) { if (f.lookup.manual) lType = 'manual'; else if (f.lookup.type == 'sql') lType = 'sql'; } $('#det_lookup_type').val(lType); this.toggleLookupType(lType); $('#det_manual_rows').html(''); if (f.lookup && f.lookup.manual) { for (var k in f.lookup.manual) { this.addLookupRow(k, f.lookup.manual[k]); } } $('#det_sql_query').val((f.lookup && f.lookup.type == 'sql') ? f.lookup.source : ''); 
                
                // Style Overrides füllen
                var inputStyle = f.style || {};
                $('#det_style_align').val(inputStyle.textAlign || '');
                $('#det_style_weight').val(inputStyle.weight || '');
                $('#det_style_size').val(inputStyle.size || '');
                if (inputStyle.color) { $('#det_style_color').val(inputStyle.color); $('#det_style_color_en').prop('checked', true); } else { $('#det_style_color_en').prop('checked', false); }
                if (inputStyle.backgroundColor) { $('#det_style_bg').val(inputStyle.backgroundColor); $('#det_style_bg_en').prop('checked', true); } else { $('#det_style_bg_en').prop('checked', false); }

                var lblStyle = f.lblStyle || {};
                $('#det_lbl_align').val(lblStyle.textAlign || '');
                $('#det_lbl_weight').val(lblStyle.weight || '');
                $('#det_lbl_size').val(lblStyle.size || '');
                if (lblStyle.color) { $('#det_lbl_color').val(lblStyle.color); $('#det_lbl_color_en').prop('checked', true); } else { $('#det_lbl_color_en').prop('checked', false); }

                // Behavior füllen
                var behavior = f.behavior || {};
                $('#det_beh_placeholder').val(behavior.placeholder || '');
                $('#det_beh_prefix').val(behavior.prefix || '');
                $('#det_beh_suffix').val(behavior.suffix || '');
                $('#det_beh_thousands').val(behavior.thousands || '');
                $('#det_beh_decimals').val(behavior.decimals !== undefined ? behavior.decimals : '');
                $('#det_beh_counter').prop('checked', behavior.counter === true);
                $('#det_beh_select2').prop('checked', behavior.searchSelect === true);
                $('#det_beh_btnStyle').val(behavior.btnStyle || 'primary');
                $('#det_beh_btnUrl').val(behavior.btnUrl || '');
                $('#det_beh_btnTarget').val(behavior.btnTarget || '_self');
                $('#det_beh_btn3d').prop('checked', behavior.btn3d === true);

                if (!f.events) f.events = {};
                app.setEditorValue('det_evt_onchange', f.events.onChange || '');
                app.setEditorValue('det_evt_onclick', f.events.onClick || '');
                app.setEditorValue('det_evt_onfocus', f.events.onFocus || '');
                app.setEditorValue('det_evt_onblur', f.events.onBlur || '');
                app.setEditorValue('det_evt_onkeypress', f.events.onKeyPress || '');

                $('#config-main-view').hide(); $('#config-detail-view').show(); this.checkHeightEnabled();
                app.updateBehaviorVisibility();
                app.updateFieldPreview();
                app.refreshEditors();
            },
            renderConfigDefaultValue: function(idx) {
                var f = this.config.fields[idx];
                if ($('#det_def_val_container').length === 0) {
                    var defHtml = '<div id="det_def_val_container" class="form-group mt-4 pt-3 border-top"><label class="text-info font-weight-bold"><i class="fas fa-star"></i> Standardinhalt</label><small class="d-block text-muted mb-2">Die Oberfläche passt sich automatisch an den gewählten Feldtyp an.</small><div id="det_def_val_wrapper"></div></div>';
                    $('#det_help').closest('.form-group').after(defHtml);
                }
                if (!f.isCustom) {
                    $('#det_def_val_container').hide();
                    return;
                }
                
                $('#det_def_val_container').show();
                var dW = $('#det_def_val_wrapper');
                // Cleanup tricky plugins
                if (dW.find('.trumbowyg-textarea').length > 0) { dW.empty(); }
                
                var t = $('#det_type').val() || f.fieldTyp;
                var commonDef = 'id="det_def_val" class="form-control form-control-sm"';
                var valStr = f.defaultValue || '';
                var newHtml = '';
                
                switch(t) {
                    case 'multiple_line_text': newHtml = '<textarea ' + commonDef + ' rows="4">' + valStr + '</textarea>'; break;
                    case 'date': newHtml = '<input type="date" ' + commonDef + ' value="' + valStr + '">'; break;
                    case 'integer': newHtml = '<input type="number" step="1" ' + commonDef + ' value="' + valStr + '">'; break;
                    case 'decimal': newHtml = '<input type="number" step="0.01" ' + commonDef + ' value="' + valStr + '">'; break;
                    case 'email': newHtml = '<div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-envelope"></i></span></div><input type="email" ' + commonDef + ' value="' + valStr + '"></div>'; break;
                    case 'url': case 'image': case 'video': case 'youTube': case 'GoogleMaps': case 'signature':
                        newHtml = '<div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-link"></i> Pfad/Link</span></div><input type="text" ' + commonDef + ' value="' + valStr + '" placeholder="http://..."></div>'; break;
                    case 'checkbox': case 'radio': case 'select':
                        var sel = '<select id="det_def_val" class="form-control form-control-sm"><option value=""></option>'; 
                        var opts = this.lookups[f.fieldName] || {};
                        for (var k in opts) sel += '<option value="' + k + '" ' + (k == valStr ? 'selected' : '') + '>' + opts[k] + '</option>'; 
                        newHtml = sel + '</select><small class="text-muted">Hinweis: Aktualisiere zuerst die Lookup-Einträge unten, um hier Werte auswählen zu können.</small>'; break;
                    case 'htmlEditor': 
                        newHtml = '<textarea id="det_def_val_html" class="form-control">' + valStr + '</textarea>'; 
                        dW.html(newHtml);
                        $('#det_def_val_html').trumbowyg({ btns: [ ['viewHTML'], ['undo', 'redo'], ['formatting'], ['strong', 'em', 'underline'], ['foreColor', 'backColor'] ] }); 
                        return;
                    default: newHtml = '<input type="text" ' + commonDef + ' value="' + valStr + '">'; break;
                }
                dW.html(newHtml);
            },
            checkHeightEnabled: function () { var t = $('#det_type').val(); var allowH = ['multiple_line_text', 'htmlEditor', 'image', 'signature', 'GoogleMaps', 'youTube', 'video']; $('#det_height').prop('disabled', allowH.indexOf(t) === -1); },
            closeFieldDetail: function () { 
                if (this.currentEditFieldIdx !== null) { 
                    var f = this.config.fields[this.currentEditFieldIdx]; 
                    
                    if (f.isCustom && $('#det_field_name_edit').length) {
                        var newFn = $('#det_field_name_edit').val().replace(/[^a-zA-Z0-9_]/g, '');
                        if (newFn && newFn !== f.fieldName) {
                            var exists = this.config.fields.find(function(c) { return c.fieldName === newFn; });
                            if (exists) {
                                alert("Der neue Feldname existiert bereits!");
                                return;
                            }
                            f.fieldName = newFn;
                        }
                    }

                    f.fieldTyp = $('#det_type').val(); 
                    f.label = $('#det_label').val(); 
                    f.width = parseInt($('#det_width').val()); 
                    if (!$('#det_height').prop('disabled')) f.height = parseInt($('#det_height').val()); 
                    f.helpText = $('#det_help').val(); 
                    if(f.isCustom) {
                        if (f.fieldTyp === 'htmlEditor' && $('#det_def_val_html').length) {
                            f.defaultValue = $('#det_def_val_html').trumbowyg('html');
                        } else {
                            f.defaultValue = $('#det_def_val').val();
                        }
                    }
                    f.val_req = $('#det_val_req').is(':checked'); f.val_min = $('#det_val_min').val(); f.val_max = $('#det_val_max').val(); f.val_regex = $('#det_val_regex').val(); f.val_regex_msg = $('#det_val_regex_msg').val(); var lType = $('#det_lookup_type').val(); if (lType === '') { delete f.lookup; } else if (lType === 'sql') { f.lookup = { type: 'sql', source: $('#det_sql_query').val() }; } else if (lType === 'manual') { var man = {}; $('#det_manual_rows .lookup-row').each(function () { var k = $(this).find('.lookup-key').val(); var v = $(this).find('.lookup-val').val(); if (k) man[k] = v; }); f.lookup = { manual: man }; } 
                    
                    if (!f.events) f.events = {};
                    f.events.onChange = $('#det_evt_onchange').val();
                    f.events.onClick = $('#det_evt_onclick').val();
                    f.events.onFocus = $('#det_evt_onfocus').val();
                    f.events.onBlur = $('#det_evt_onblur').val();
                    f.events.onKeyPress = $('#det_evt_onkeypress').val();

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

                    this.renderConfigFields(); this.renderTabOrder(); 
                } 
                $('#config-detail-view').hide(); $('#config-main-view').show(); 
            },
            saveConfigFull: function () { 
                if ($('#config-detail-view').is(':visible')) this.closeFieldDetail(); 
                if (!this.config.canvas) this.config.canvas = {}; 
                this.config.canvas.width = parseInt($('#cfg_cw').val()) || 1000; 
                this.config.canvas.height = parseInt($('#cfg_ch').val()) || 800; 
                if (!this.config.apiKeys) this.config.apiKeys = {}; 
                this.config.apiKeys.gemini = $('#cfg_api_gemini').val(); 
                this.config.apiKeys.chatgpt = $('#cfg_api_chatgpt').val(); 
                this.config.apiKeys.anthropic = $('#cfg_api_anthropic').val(); 
                
                if (!this.config.events) this.config.events = {};
                this.config.events.onLoad = $('#cfg_evt_onload').val();
                this.config.events.onBeforeSave = $('#cfg_evt_onbeforesave').val();
                this.config.events.onAfterSave = $('#cfg_evt_onaftersave').val();
                this.config.events.libraries = $('#cfg_evt_libraries').val();
                
                this.config.themeName = $('#ag-theme-selector').length ? $('#ag-theme-selector').val() : (this.config.themeName || ''); 
                if ($('#tab-json').hasClass('active')) { try { this.config = JSON.parse($('#config-json-editor').val()); } catch (e) { alert('JSON Invalid'); return; } } else { var self = this; $('#content-fld tbody tr').each(function () { var idx = $(this).data('idx'); self.config.fields[idx].readonly = $(this).find('.c-ro').is(':checked'); self.config.fields[idx].hidden = $(this).find('.c-hid').is(':checked'); }); var newTabIdx = 1; $('#sortable-tab-order li').each(function () { var fName = $(this).data('field'); var fieldConf = self.config.fields.find(function (f) { return f.fieldName === fName; }); if (fieldConf) fieldConf.tabIndex = newTabIdx++; }); } $.post(AJAX_URL, { action: 'save_config', gridName: GRID_NAME, config: JSON.stringify(this.config) }, function (res) { if (res.status === 'ok') { app.loadData(app.currentId); } else alert('Fehler: ' + res.error); }, 'json'); 
            },

            openRegexBuilder: function () { $('#rb_regex_code').val($('#det_val_regex').val()); $('#rb_test_input').val(''); $('#rb_test_result').html(''); $('#rb_ai_prompt').val(''); $('#rb_templates').val(''); this.testRegexLive(); $('#regexBuilderModal').modal('show'); },
            applyRegexTemplate: function (val) { if (val !== '') { $('#rb_regex_code').val(val); this.testRegexLive(); } },
            applyRegex: function () { $('#det_val_regex').val($('#rb_regex_code').val()); $('#regexBuilderModal').modal('hide'); },
            testRegexLive: function () { var reg = $('#rb_regex_code').val(); var inp = $('#rb_test_input').val(); if (!reg || !inp) { $('#rb_test_result').html(''); return; } try { var m = inp.match(new RegExp(reg)); if (m) $('#rb_test_result').html('<span class="text-success"><i class="fas fa-check"></i> Match gefunden!</span>'); else $('#rb_test_result').html('<span class="text-danger"><i class="fas fa-times"></i> Kein Match</span>'); } catch (e) { $('#rb_test_result').html('<span class="text-danger">Ungültiger RegEx</span>'); } },
            generateRegexAI: function () { var p = $('#rb_ai_prompt').val(); if (!p) return; var keys = this.config.apiKeys || { gemini: '', chatgpt: '', anthropic: '' }; $('#rb_regex_code').val("Lade..."); $.post(AJAX_URL, { action: 'generate_regex', prompt: p, keys: keys }, function (res) { if (res.regex) $('#rb_regex_code').val(res.regex); else alert(res.error); app.testRegexLive(); }, 'json'); },

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
                
                var targetId = $('#ea_target_id').val();
                if (targetId === 'det_beh_btnUrl') {
                    p = "Generiere KEIN PHP. Generiere AUSSCHLIESSLICH eine URL (z.B. https://domain.com/?param={feldname}). Nutze die vorhandenen Felder als Parameter-Platzhalter in geschweiften Klammern. Antworte NUR mit der URL, kein Text, kein Markdown. " + p;
                }
                
                var keys = this.config.apiKeys || {};
                var fields = JSON.stringify(this.config.fields);
                var evName = $('#ea_target_name').text();
                $('#ea_loading').show();
                $.post(AJAX_URL, {
                    action: 'generate_event_code',
                    prompt: p,
                    keys: keys,
                    fields: fields,
                    eventName: evName
                }, function(res) {
                    $('#ea_loading').hide();
                    if(res.status === 'ok') {
                        $('#ea_result').val(res.code);
                    } else {
                        alert(res.error);
                    }
                }, 'json').fail(function(){ $('#ea_loading').hide(); alert("Kritischer API Fehler"); });
            },
            applyEventCode: function() {
                var t = $('#ea_target_id').val();
                var c = $('#ea_result').val();
                if(t && c) {
                    var currentV = app.codeEditors[t] ? app.codeEditors[t].getValue() : $('#'+t).val();
                    var newV = currentV ? currentV + "\n" + c : c;
                    app.setEditorValue(t, newV);
                    $('#eventCodeAiModal').modal('hide');
                }
            },
            startSprachEingabeCode: function() {
                if (!('webkitSpeechRecognition' in window)) { alert("Spracheingabe wird leider nur in Google Chrome untertützt."); return; }
                var r = new webkitSpeechRecognition();
                r.lang = 'de-DE';
                r.interimResults = false;
                r.start();
                $('#ea_mic_btn').addClass('btn-danger').removeClass('btn-outline-secondary');
                r.onresult = function(e) {
                    var text = e.results[0][0].transcript;
                    var c = $('#ea_prompt').val();
                    $('#ea_prompt').val(c + (c ? ' ' : '') + text);
                };
                r.onend = function() {
                    $('#ea_mic_btn').removeClass('btn-danger').addClass('btn-outline-secondary');
                };
            },

            toggleDesign: function (act) { this.isDesign = act; $('#form-canvas').toggleClass('design-mode', act); $('.app-input').prop('disabled', act); $('.design-only-ui').toggle(act); if (act) { try { $(".app-input.ui-autocomplete-input").autocomplete("disable"); } catch (e) { } this.enableResize(); this.enableDrag(); this.openConfigEditor(); } else { if ($('.form-element').data('ui-draggable')) $('.form-element').draggable('destroy'); if ($('.form-element.f-input').data('ui-resizable')) $('.form-element.f-input').resizable('destroy'); try { $(".app-input.ui-autocomplete-input").autocomplete("enable"); } catch (e) { } this.closeConfigSidebar(); } this.updateButtons(); },
            updateGrid: function (v) { this.snapping = parseInt(v); if (this.isDesign) $('.form-element').draggable('option', 'grid', [this.snapping, this.snapping]); },
            resetLayout: function () { if (!confirm("Layout resetten?")) return; var y = 20; var c = []; this.config.fields.forEach(function (f) { c.push({ fieldName: f.fieldName, type: 'lbl', newX: 20, newY: y + 5 }); c.push({ fieldName: f.fieldName, type: 'inp', newX: 150, newY: y }); y += 45; }); $.post(AJAX_URL, { action: 'save_layout', gridName: GRID_NAME, changes: JSON.stringify(c) }, function () { app.loadData(app.currentId); }); },
            handleUndo: function () { if (this.isDesign) this.undoLayout(); else this.undoData(); },
            handleRedo: function () { if (this.isDesign) this.redoLayout(); else this.redoData(); },
            updateButtons: function () { if (this.isDesign) { $('#btn-undo').prop('disabled', !this.layoutUndo.length); $('#btn-redo').prop('disabled', !this.layoutRedo.length); } else { $('#btn-undo').prop('disabled', !this.dataUndo.length); $('#btn-redo').prop('disabled', !this.dataRedo.length); } },

            enableDrag: function () { var self = this; $('.form-element').draggable({ grid: [self.snapping, self.snapping], containment: "#form-canvas", cancel: ".ui-resizable-handle", stop: function (e, ui) { var id = $(this).attr('id'); var type = id.substr(0, 3); var fn = id.substr(4); var chg = [{ fieldName: fn, type: type, newX: ui.position.left, newY: ui.position.top, oldX: ui.originalPosition.left, oldY: ui.originalPosition.top, elementId: id }]; if (type == 'inp') { var dx = ui.position.left - ui.originalPosition.left; var dy = ui.position.top - ui.originalPosition.top; var l = $('#lbl_' + fn); if (l.length) { var op = l.position(); var nx = op.left + dx; var ny = op.top + dy; l.css({ left: nx, top: ny }); chg.push({ fieldName: fn, type: 'lbl', newX: nx, newY: ny, oldX: op.left, oldY: op.top, elementId: 'lbl_' + fn }); } } self.layoutUndo.push(chg); self.layoutRedo = []; self.updateButtons(); $.post(AJAX_URL, { action: 'save_layout', gridName: GRID_NAME, changes: JSON.stringify(chg) }); } }); },

            // NEU: Logik, damit einfache Felder nicht beim Drag/Resize fälschlicherweise eine Höhe abspeichern
            enableResize: function () {
                var self = this;
                $('.form-element.f-input').each(function () {
                    var el = $(this); var id = el.attr('id'); var fName = id.substr(4);
                    var fConf = self.config.fields.find(function (f) { return f.fieldName == fName; });
                    var isHeightResizable = (fConf && ['multiple_line_text', 'htmlEditor', 'image', 'signature', 'GoogleMaps', 'youTube', 'video'].indexOf(fConf.fieldTyp) !== -1);
                    var handles = isHeightResizable ? "e, s, se" : "e";

                    if (el.data('ui-resizable')) el.resizable('destroy');

                    el.resizable({
                        handles: handles,
                        grid: [self.snapping, self.snapping],
                        stop: function (e, ui) {
                            var chgItem = { fieldName: fName, type: 'inp', newX: ui.position.left, newY: ui.position.top, w: ui.size.width, elementId: id };
                            if (isHeightResizable) chgItem.h = ui.size.height; // Höhe nur speichern, wenn es erlaubt ist
                            var chg = [chgItem];
                            $.post(AJAX_URL, { action: 'save_layout', gridName: GRID_NAME, changes: JSON.stringify(chg) });
                        }
                    });
                });
            },

            undoLayout: function () { if (!this.layoutUndo.length) return; var b = this.layoutUndo.pop(); this.layoutRedo.push(b); this.updateButtons(); var r = []; b.forEach(function (c) { $('#' + c.elementId).css({ left: c.oldX, top: c.oldY }); r.push({ fieldName: c.fieldName, type: c.type, newX: c.oldX, newY: c.oldY }); }); $.post(AJAX_URL, { action: 'save_layout', gridName: GRID_NAME, changes: JSON.stringify(r) }); },
            redoLayout: function () { if (!this.layoutRedo.length) return; var b = this.layoutRedo.pop(); this.layoutUndo.push(b); this.updateButtons(); var r = []; b.forEach(function (c) { $('#' + c.elementId).css({ left: c.newX, top: c.newY }); r.push({ fieldName: c.fieldName, type: c.type, newX: c.newX, newY: c.newY }); }); $.post(AJAX_URL, { action: 'save_layout', gridName: GRID_NAME, changes: JSON.stringify(r) }); },
            pushDataUndo: function (f, newVal, oldVal) { if (oldVal === undefined || oldVal === null) oldVal = this.tempVal; this.dataUndo.push({ f: f, o: oldVal, n: newVal }); this.dataRedo = []; this.updateButtons(); },
            undoData: function () { if (!this.dataUndo.length) return; var action = this.dataUndo.pop(); this.dataRedo.push(action); this.updateButtons(); this.restoreDataValue(action.f, action.o); },
            redoData: function () { if (!this.dataRedo.length) return; var action = this.dataRedo.pop(); this.dataUndo.push(action); this.updateButtons(); this.restoreDataValue(action.f, action.n); },
            restoreDataValue: function (f, val) { var el = $('#inp_' + f).find('.app-input'); if (el.length) el.val(val); this.saveDataDirect(f, val, el); },
            toggleLookupType: function (v) { 
                $('#det_lookup_manual_area').hide(); 
                $('#det_lookup_sql_area').hide(); 
                if (v == 'manual') $('#det_lookup_manual_area').show(); 
                if (v == 'sql') { 
                    $('#det_lookup_sql_area').show(); 
                    if (app.codeEditors['det_sql_query']) app.codeEditors['det_sql_query'].refresh(); 
                } 
                app.updateFieldPreview();
            },
            addLookupRow: function (k, v) { 
                $('#det_manual_rows').append('<div class="row align-items-center mb-1 lookup-row"><div class="col-4 pl-1 pr-1"><input class="form-control form-control-sm lookup-key" placeholder="z.B. id" value="' + (k || '') + '"></div><div class="col-7 pl-1 pr-1"><input class="form-control form-control-sm lookup-val" placeholder="z.B. Name" value="' + (v || '') + '" oninput="app.updateFieldPreview()"></div><div class="col-1 px-0 text-center"><button class="btn btn-sm btn-light text-danger py-0 border-0" onclick="$(this).closest(\'.lookup-row\').remove(); app.updateFieldPreview();"><i class="fas fa-trash-alt"></i></button></div></div>'); 
                app.updateFieldPreview();
            }
        };

        $(document).ready(function () { 
            app.init(); 
            $('body').on('shown.bs.tab', 'a[data-toggle="tab"]', function (e) {
                app.refreshEditors();
            });
        });
    </script>
</body>

</html>
<?php
// END ERROR/OUTPUT BUFFERING
$output = ob_get_clean();
echo $output;
?>
