/**
 * Anti-Gravity Theme Engine
 * Manages dynamic loading and application of CSS variables from Scriptcase .ini themes.
 */
document.addEventListener('DOMContentLoaded', () => {
    // Find the URL of this script to dynamically resolve ag_converter.php
    const scripts = document.getElementsByTagName('script');
    let converterUrl = '/tools/design_templates/ag_converter.php'; // Default fallback
    
    for (let script of scripts) {
        if (script.src && script.src.includes('ag_theme_engine.js')) {
            const baseUrl = script.src.split('ag_theme_engine.js')[0];
            converterUrl = baseUrl + 'ag_converter.php';
            break;
        }
    }

    const agThemeEngine = {
        init: async function() {
            try {
                const response = await fetch(`${converterUrl}?action=list_files`);
                const files = await response.json();
                
                // Find target header to inject selector
                const headerTarget = document.querySelector('#ag-header .flex.items-center.gap-4');
                if (!headerTarget) {
                    console.warn('AG Theme Engine: Target header #ag-header not found.');
                    return;
                }

                // Prevent multiple injections
                if (document.getElementById('ag-theme-selector')) return;

                const select = document.createElement('select');
                select.id = 'ag-theme-selector';
                select.className = 'bg-slate-100 border-none text-[10px] font-black uppercase px-3 py-2 rounded-lg cursor-pointer hover:bg-white hover:shadow-sm transition-all focus:ring-2 focus:ring-blue-500 appearance-none text-slate-600 outline-none';
                
                const defaultOpt = document.createElement('option');
                defaultOpt.text = 'THEME WÄHLEN';
                defaultOpt.value = '';
                select.add(defaultOpt);

                files.forEach(file => {
                    const opt = document.createElement('option');
                    opt.value = file;
                    opt.text = file.replace('.ini', '').toUpperCase();
                    select.add(opt);
                });

                select.addEventListener('change', (e) => this.changeTheme(e.target.value));
                headerTarget.insertBefore(select, headerTarget.firstChild);
            } catch (e) {
                console.error('AG Theme Engine: Failed to load template list', e);
            }
        },

        changeTheme: async function(file) {
            if (!file) return;
            try {
                const response = await fetch(`${converterUrl}?action=convert&file=${file}`);
                const theme = await response.json();
                this.applyTheme(theme);
                
                // Optional: Dispatch an event so apps can react to theme changes
                document.dispatchEvent(new CustomEvent('agThemeChanged', { detail: { theme: theme, file: file } }));
            } catch (e) {
                console.error('AG Theme Engine: Failed to load theme file', e);
            }
        },

        applyTheme: function(theme) {
            const root = document.documentElement;
            // Fallback for brand color if missing
            if (theme.colors && !theme.colors.brand) {
                theme.colors.brand = theme.colors.surface || theme.colors.toolbar_bg || '#2563eb';
            }
            
            if (theme.colors) {
                for (const [k, v] of Object.entries(theme.colors)) {
                    root.style.setProperty(`--ag-color-${k}`, v);
                }
            }
            if (theme.spacing) {
                for (const [k, v] of Object.entries(theme.spacing)) {
                    root.style.setProperty(`--ag-spacing-${k}`, v);
                }
            }
            if (theme.typography) {
                for (const [k, v] of Object.entries(theme.typography)) {
                    const cssKey = k.replace('_', '-');
                    root.style.setProperty(`--ag-font-${cssKey}`, v);
                }
            }
            if (theme.design_mode) {
                for (const [k, v] of Object.entries(theme.design_mode)) {
                    root.style.setProperty(`--ag-design-${k}`, v);
                }
            }
        }
    };

    agThemeEngine.init();
    
    // Expose globally
    window.AgThemeEngine = agThemeEngine;
});
