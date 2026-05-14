<?php
/**
 * Anti-Gravity Variable Design Library v2.3
 * Vollständig auf Tailwind CSS umgestellt.
 * Fokus: Funktionale Programmierung zur einfachen Integration.
 */

/**
 * Injeziert CSS Variablen in den Header und definiert Basis-Overrides.
 * @param array $theme Das aus JSON geladene oder konvertierte Theme-Array.
 */
function ag_inject_css_variables($theme)
{
    // Basis-Variablen initialisieren, falls leer
    if (empty($theme)) $theme = array('colors' => array(), 'spacing' => array());

    echo "\n<style id='ag-variable-design'>\n:root {\n";

    // Farben aus dem Schema
    if (isset($theme['colors'])) {
        foreach ($theme['colors'] as $k => $v)
            echo "  --ag-color-$k: $v;\n";
    }

    // Abstände und Radien
    if (isset($theme['spacing'])) {
        foreach ($theme['spacing'] as $k => $v)
            echo "  --ag-spacing-$k: $v;\n";
    }

    // Typografie-Einstellungen
    if (isset($theme['typography'])) {
        foreach ($theme['typography'] as $k => $v) {
            $key = str_replace('_', '-', $k);
            echo "  --ag-font-$key: $v;\n";
        }
    }

    // Spezifische Design-Modus Variablen
    if (isset($theme['design_mode'])) {
        foreach ($theme['design_mode'] as $k => $v)
            echo "  --ag-design-$k: $v;\n";
    }

    echo "}\n";

    // Tailwind-kompatible Brücken-Klassen
    echo "
        .ag-input { 
            width: 100%; 
            height: 100%; 
            border: 1px solid var(--ag-color-border); 
            border-radius: 4px; 
            padding: 0.5rem; 
            background-color: #fff;
            transition: all 0.2s ease;
        }
        .ag-input:focus { 
            outline: 2px solid var(--ag-color-brand); 
            background-color: var(--ag-design-focus_bg, #ffffd9) !important; 
        }
        .ag-invalid { 
            background-color: var(--ag-design-invalid_bg, #ffaaaa) !important; 
            border-color: #ef4444 !important;
        }
        .ag-header { 
            height: var(--ag-spacing-toolbar_height, 70px); 
            background-color: var(--ag-color-toolbar_bg, #ffffff); 
            border-bottom: 1px solid var(--ag-color-border);
        }
    ";
    echo "</style>\n";
    // Load the dynamic theme engine
    echo "<script src='/tools/design_templates/ag_theme_engine.js' defer></script>\n";
}

/**
 * Rendert den Header-Bereich (Toolbar) mit Tailwind-Klassen.
 */
function ag_render_header($title, $recordId = '...')
{
    ?>
    <header id="ag-header" class="ag-header flex justify-between items-center px-6 sticky top-0 z-[1000] shadow-sm">
        <div class="flex items-center gap-2">
            <button class="p-2 border border-gray-200 rounded-lg hover:bg-gray-50 active:scale-95 transition-all"
                onclick="app.nav('prev')">
                <i class="fas fa-chevron-left text-gray-600"></i>
            </button>
            <span class="font-black text-sm text-slate-700 px-2" id="record-indicator">ID:
                <?php echo htmlspecialchars($recordId); ?>
            </span>
            <button class="p-2 border border-gray-200 rounded-lg hover:bg-gray-50 active:scale-95 transition-all"
                onclick="app.nav('next')">
                <i class="fas fa-chevron-right text-gray-600"></i>
            </button>
        </div>

        <div class="hidden md:block font-bold text-slate-800 tracking-tight truncate flex-1 text-center px-4">
            <?php echo htmlspecialchars($title); ?>
        </div>

        <div class="flex items-center gap-4">
            <div class="flex items-center gap-3">
                <span class="text-[10px] font-black uppercase text-slate-400">Design</span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="designSwitch" class="sr-only peer" onchange="app.toggleDesign(this.checked)">
                    <div
                        class="w-10 h-5 bg-gray-200 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600">
                    </div>
                </label>
            </div>
            <button
                class="flex items-center gap-2 px-3 py-2 bg-slate-900 text-white text-xs font-bold rounded-lg hover:bg-black transition-colors"
                onclick="app.openConfigEditor()">
                <i class="fas fa-cog"></i> CONFIG
            </button>
        </div>
    </header>
    <?php
}

/**
 * Rendert eine Gruppe aus Label und Input mit Z-Index Unterstützung.
 * Nutzt absolute Positionierung für Desktop und Stacking für Mobile.
 */
function ag_render_field_group($f, $input_html)
{
    $name = $f['fieldName'];
    $lx = $f['form']['lbl']['x'] ?? 0;
    $ly = $f['form']['lbl']['y'] ?? 0;
    $ix = $f['form']['inp']['x'] ?? 0;
    $iy = $f['form']['inp']['y'] ?? 0;
    $lz = $f['labelZIndex'] ?? 10;
    $iz = $f['inputZIndex'] ?? 20;
    $w = $f['width'] ?? 200;
    $h = isset($f['height']) ? "height:{$f['height']}px;" : "";

    // Label
    echo "<div id='lbl_{$name}' class='ag-form-element font-bold text-[11px] uppercase tracking-wide text-slate-500 py-1 flex items-center absolute whitespace-nowrap' style='left:{$lx}px; top:{$ly}px; z-index:{$lz};'>";
    echo htmlspecialchars($f['label'] ?? $name);
    if (!empty($f['val_req']))
        echo "<span class='text-red-500 ml-1'>*</span>";
    echo "</div>";

    // Input Container
    echo "<div id='inp_{$name}' class='ag-form-element absolute' style='left:{$ix}px; top:{$iy}px; width:{$w}px; {$h} z-index:{$iz};'>";
    echo $input_html;
    echo "</div>";
}

/**
 * Rendert den Footer.
 */
function ag_render_footer($text = "Anti-Gravity ERP")
{
    echo "<footer class='p-5 bg-white border-t border-gray-200 text-center text-[10px] font-bold uppercase tracking-[0.2em] text-slate-400 opacity-70'>{$text} &copy; " . date('Y') . "</footer>";
}
?>