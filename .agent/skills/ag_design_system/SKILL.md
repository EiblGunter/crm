---
name: Anti-Gravity Design System
description: Core UI guidelines and code templates for creating or refactoring PHP applications into the AG Variable Design System (Tailwind, Live-Theming).
---

# Anti-Gravity Design System (UI/UX)

Wenn du (die KI) aufgefordert wirst, eine neue Seite, ein Formular, ein Grid zu erstellen ODER eine bestehende Bootstrap/Scriptcase-Anwendung zu refaktorieren, **musst du zwingend diese Richtlinien befolgen**, um das neue Premium-Design-System dieses Projekts zu verwenden.

## 1. Kernarchitektur
- **Tech Stack:** Vanilla PHP, Tailwind CSS (via CDN), Google Font 'Inter' (Weights: 400, 700, 900). jQuery/FontAwesome sind bei Bedarf erlaubt.
- **Bibliothek:** Integriere immer die zentrale UI-Bücherei: `require_once __DIR__ . '/../../tools/design_templates/ag_library.php';` (Relativen Pfad ggf. anpassen).
- **CSS-Injection & Auto-Theming:** Rufe zwingend `<?php ag_inject_css_variables([]); ?>` im `<head>` auf. Das injiziert das CSS-Token-Grundgerüst UND lädt automatisch die JS-Theme-Engine (`ag_theme_engine.js`). Baue NIEMALS eigene JS-Logik für den reinen Theme-Wechsel!
- **Layout-Schale:**
  - Start direkt nach dem `<body>`: `<?php ag_render_header('Dein Titel', 'MODUL'); ?>`
  - Hauptinhalt Wrapper (Zentrum): `<main class="max-w-[98%] mx-auto my-8"><div class="ag-page-card shadow-2xl p-6"> ... INHALT ... </div></main>`
  - Ende vor `</body>`: `<?php ag_render_footer(); ?>`

## 2. CSS-Variablen Mapping (Kritisch)
Damit Tailwind auf die live-geladenen Themes reagiert, musst du immer diesen `<style>`-Block in den `<head>` jeder Datei einfügen:
```css
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

/* ZUSATZ FÜR LEGACY APPS (z.B. komplexe Bootstrap-Formulare): */
/* Wenn Bootstrap aufgrund komplexer Logik nicht entfernt werden kann, MÜSSEN dessen harte Farben hier an die Live-Themes überschrieben werden: */
.form-control, .bg-white, .modal-content, .card {
    background-color: var(--ag-color-surface, #ffffff) !important;
    color: var(--ag-color-text-main, #212529) !important;
    border-color: var(--ag-color-border, #dee2e6) !important;
}
.bg-light, .modal-header, .card-header, .nav-tabs .nav-link {
    background-color: var(--ag-color-toolbar_bg, #f8f9fa) !important;
    border-color: var(--ag-color-border, #dee2e6) !important;
    color: var(--ag-color-text-main, #495057) !important;
}
.form-control:focus {
    box-shadow: 0 0 0 0.2rem var(--ag-color-brand, rgba(0, 123, 255, 0.25)) !important;
    background-color: var(--ag-design-focus_bg, #ffffff) !important;
    border-color: var(--ag-color-brand, #80bdff) !important;
}
```

## 3. Design & Ästhetik
- **Veraltete Styles löschen:** Kein Bootstrap, keine alten Bootstrap-Modal-Klassen ohne Translation, keine alten `.sc-` Klassen. (Ausnahme: Wenn Legacy Code (Punkt 4.1) die Beibehaltung komplexer JS/Bootstrap-Stacks erfordert, greift zwingend der CSS-Override-Block aus Punkt 2).
- **Premium Look:** Nutze weiche Animationen (`transition-all duration-300`), starke Rundungen für Felder und Buttons (`rounded-xl` / `rounded-lg`) und softe Schatten (`shadow-sm`, `shadow-lg`).
- **Fokus & Ringe:** Stelle sicher, dass aktive Elemente edel wirken (z.B. Eingabefelder: `focus:ring-2 focus:ring-[var(--ag-color-brand)] outline-none`).

## 4. Refactoring-Regeln für Legacy Code
Wenn Legacy-Code in dieses Design umgebaut werden soll:
1. Behalte **100% der Business-Logik** (PHP-Scripts, AJAX-Schnittstellen, SQL-Statements, JS-Stacks, Undo/Redo) komplett intakt!
2. Übertrage NUR das visuelle HTML-Skelett in diese Tailwind-Struktur. (Falls komplexe Bootstrap-JS Logik daran hängt, nutze die CSS Overrides aus Schritt 2, um Live-Theming auf Bootstrap zu erzwingen).
