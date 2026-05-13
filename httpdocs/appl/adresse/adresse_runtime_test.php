<?php
// adresse_runtime_test.php
// Dies ist ein minimaler HTML Wrapper, um das neue, im Form-Designer (form_simple.php) 
// dynamisch konfigurierte Formular für 'adr_test' als Runtime-Ausführung zu testen.
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Adresse (Runtime Test)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body, html { height: 100%; margin: 0; padding: 0; overflow: hidden; background-color: #f3f4f6; }
        .wrapper { width: 100vw; height: 100vh; display: flex; flex-direction: column; }
        .header { background: #1e40af; color: white; padding: 12px 24px; font-weight: bold; flex-shrink: 0; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); z-index: 10; }
        .iframe-container { flex-grow: 1; border: none; width: 100%; background: #ffffff; }
        .back-link { color: #bfdbfe; text-decoration: none; font-size: 0.875rem; transition: color 0.2s; background: rgba(255,255,255,0.1); padding: 4px 12px; border-radius: 4px; }
        .back-link:hover { color: white; background: rgba(255,255,255,0.2); }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-blue-200" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path></svg>
                <span class="text-lg">Anti-Gravity Runtime Test <span class="text-blue-300 font-normal text-sm ml-2">Applikation: Adresse (Tabelle: adr_test)</span></span>
            </div>
            <a href="adresse.php" class="back-link">&larr; Zurück zur statischen Legacy-Version</a>
        </div>
        <!-- Iframe lädt nun die Runtime Engine und reißt sich Parameter: mode=run & gridName=adr_test -->
        <iframe src="/tools/form/form_simple/form_simple.php?gridName=adr_test&mode=run" class="iframe-container" title="Formular Runtime"></iframe>
    </div>
</body>
</html>
