<?php
/**
 * Anti-Gravity Scriptcase-to-JSON Converter & API
 * Konvertiert .ini in das AG-Design-Schema und liefert Daten für das Frontend.
 */

function ag_convert_sc_to_json($ini_content)
{
    $data = @unserialize($ini_content);
    if (!$data || !isset($data['schema'])) {
        return array("error" => "Ungültiges Scriptcase-Format");
    }

    $sc = $data['schema'];

    // Stark erweitertes Mapping für Feldhöhen, Paddings und Typografie
    $mapping = array(
        // Allgemeines Layout
        'css_schema_info_name' => 'metadata.name',
        'css_form_pagina_background_color' => 'colors.background',
        'css_form_moldura_background_color' => 'colors.surface',
        'css_form_toolbar_background_color' => 'colors.toolbar_bg',
        'css_form_pagina_color' => 'colors.text_main',
        'css_form_moldura_border_color' => 'colors.border',

        // Label-Spezifikationen (Beschriftungen)
        'css_form_label_impar_simples_color' => 'colors.label_text',
        'css_form_label_impar_simples_font_size' => 'typography.label_size',
        'css_form_label_impar_simples_font_weight' => 'typography.label_weight',

        // Input-Feld-Spezifikationen (Objekte)
        'css_form_objeto_impar_simples_color' => 'colors.input_text',
        'css_form_objeto_impar_simples_background_color' => 'colors.input_bg',
        'css_form_objeto_impar_simples_border_color' => 'colors.input_border',
        'css_form_objeto_impar_simples_radius' => 'spacing.input_radius',
        'css_form_objeto_impar_simples_padding' => 'spacing.input_padding', // Bestimmt meistens die Feldhöhe!
        'css_form_objeto_impar_simples_height' => 'spacing.input_height',  // Falls explizit gesetzt
        'css_form_objeto_impar_simples_font_size' => 'typography.input_size',

        // Design- & Fehler-Status
        'css_form_objetoerror_impar_simples_background_color' => 'design_mode.invalid_bg',
        'css_form_objetofocus_impar_simples_background_color' => 'design_mode.focus_bg',

        // Toolbar & Blöcke
        'css_form_bloco_radius' => 'spacing.border_radius',
        'css_form_toolbar_height' => 'spacing.toolbar_height',
    );

    $ag_theme = array(
        "metadata" => array("version" => "2.21.0-extended"),
        "colors" => array(),
        "spacing" => array(),
        "typography" => array(),
        "design_mode" => array()
    );

    foreach ($mapping as $sc_key => $ag_path) {
        if (isset($sc[$sc_key]) && $sc[$sc_key] !== "") {
            $parts = explode('.', $ag_path);
            $val = $sc[$sc_key];

            // Format-Fixes: Wenn SC "3" liefert, machen wir "3px" draus.
            if ($parts[0] == 'spacing' && is_numeric($val))
                $val .= 'px';

            $ag_theme[$parts[0]][$parts[1]] = $val;
        }
    }

    // Standard-Fallbacks, falls eine INI-Datei leere Werte liefert
    if (empty($ag_theme['colors']['toolbar_bg']))
        $ag_theme['colors']['toolbar_bg'] = "#ffffff";
    if (empty($ag_theme['colors']['input_bg']))
        $ag_theme['colors']['input_bg'] = "#ffffff";
    if (empty($ag_theme['spacing']['input_padding']))
        $ag_theme['spacing']['input_padding'] = "6px 10px";

    return $ag_theme;
}

// AJAX API ENDPUNKT
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    // Basis-Pfad zu den neuen Vorlagen
    $vorlagen_dir = __DIR__ . '/sc_vorlagen/';
    
    if ($_GET['action'] == 'list_files') {
        $files = glob($vorlagen_dir . "*.ini");
        $basenames = $files ? array_map('basename', $files) : array();
        echo json_encode(array_values($basenames));
        exit;
    }
    
    if ($_GET['action'] == 'convert' && isset($_GET['file'])) {
        $file = $vorlagen_dir . basename($_GET['file']);
        if (file_exists($file)) {
            $content = file_get_contents($file);
            echo json_encode(ag_convert_sc_to_json($content));
        } else {
            echo json_encode(array("error" => "Datei nicht gefunden"));
        }
        exit;
    }
}
?>