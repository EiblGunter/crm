<?php
/**
 * Anti-Gravity Design System Library
 * Standard components for Header, Footer and Field Groups.
 */

function ag_inject_css_variables($theme_overrides = array()) {
    ?>
    <style>
        :root {
            --ag-color-background: #f1f5f9;
            --ag-color-surface: #ffffff;
            --ag-color-brand: #3b82f6;
            --ag-color-text-main: #0f172a;
            --ag-color-text-muted: #64748b;
            --ag-color-border: #e2e8f0;
            --ag-color-input_bg: #ffffff;
            --ag-spacing-border_radius: 1.5rem;
            --ag-spacing-input_radius: 0.75rem;
            --ag-font-input-size: 14px;
        }

        body {
            background-color: var(--ag-color-background) !important;
            color: var(--ag-color-text-main) !important;
            transition: background-color 0.4s ease, color 0.4s ease;
        }

        .ag-page-card {
            background-color: var(--ag-color-surface) !important;
            border: 1px solid var(--ag-color-border) !important;
            border-radius: var(--ag-spacing-border_radius);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }

        .ag-input {
            width: 100%;
            border: 1px solid var(--ag-color-border) !important;
            border-radius: var(--ag-spacing-input_radius) !important;
            padding: 0.75rem 1rem !important;
            font-size: var(--ag-font-input-size) !important;
            transition: all 0.2s ease;
        }

        .ag-input:focus {
            outline: none;
            border-color: var(--ag-color-brand) !important;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1) !important;
        }

        .ag-label {
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--ag-color-text-muted);
            margin-bottom: 0.5rem;
            display: block;
        }
    </style>
    <script>
        // Dummy Theme Engine for UI compatibility
        window.AG = {
            setTheme: (theme) => console.log("Theme set to:", theme)
        };
    </script>
    <?php
}

function ag_render_header($title, $subtitle = '') {
    ?>
    <header class="max-w-4xl mx-auto pt-12 px-6 flex justify-between items-end">
        <div>
            <h1 class="text-4xl font-black tracking-tight text-slate-900"><?php echo htmlspecialchars($title); ?></h1>
            <p class="text-sm font-bold text-blue-500 uppercase tracking-widest mt-1"><?php echo htmlspecialchars($subtitle); ?></p>
        </div>
        <div class="flex gap-2">
            <button onclick="app.nav('prev')" class="p-2 bg-white rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors">
                <i class="fas fa-chevron-left text-slate-400"></i>
            </button>
            <button onclick="app.nav('next')" class="p-2 bg-white rounded-lg border border-slate-200 hover:bg-slate-50 transition-colors">
                <i class="fas fa-chevron-right text-slate-400"></i>
            </button>
        </div>
    </header>
    <?php
}

function ag_render_footer($text = "Anti-Gravity CRM") {
    ?>
    <footer class="max-w-4xl mx-auto py-12 px-6 text-center text-[11px] font-bold text-slate-400 uppercase tracking-widest">
        &copy; <?php echo date('Y'); ?> &bull; <?php echo htmlspecialchars($text); ?>
    </footer>
    <?php
}

function ag_render_field_group($cfg, $input_html) {
    ?>
    <div class="mb-6">
        <label class="ag-label"><?php echo htmlspecialchars($cfg['label']); ?></label>
        <?php echo $input_html; ?>
    </div>
    <?php
}
