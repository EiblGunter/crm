# Scriptcase KI-Entwicklung Richtlinien

Diese Richtlinien wurden automatisch aus dem NotebookLM "Scriptcase KI-Entwicklung" (Quelle: "Scriptcase Entwicklung Anweisung an KI") extrahiert.

## 1. Syntax in Blank-Applikationen (`onExecute`)
- **Start:** Kein `<?php` am Anfang von reinen PHP-Blöcken.
- **HTML-Mischung:** Wenn HTML am Anfang steht, PHP-Block mit `?>` schließen.
- **Ende:** PHP muss nicht abgeschlossen werden. Falls HTML/JS am Ende steht, danach wieder ein `<?php` setzen, um den automatischen Scriptcase-Abschluss zu neutralisieren.

## 2. Datenbankzugriffe (CRUD)
- **Befehl:** Immer `sc_select()` verwenden (niemals `sc_lookup()` oder `sc_exec_sql()`).
- **Fehlerbehandlung:** Jeder `sc_select()`-Aufruf **muss** eine explizite Fehlerprüfung enthalten.

## 3. Variablen & Arrays
- **Array-Syntax:** Immer `array()` verwenden, niemals die Kurzschreibweise `[]`.
- **Global-Conflict:** Eckige Klammern `[]` generell im Code und in Strings vermeiden, da sie mit der Syntax für globale Variablen in Scriptcase kollidieren.

## 4. Navigation & Parameter
- **sc_redir():** Parameter in Kleinschrift, ohne Umlaute und Sonderzeichen.

## 5. Design & Dokumentation
- **Responsive:** Alle UIs müssen für Mobilgeräte und Desktop optimiert sein.
- **Kommentare:** Ausführliche technische Dokumentation im Code ist Pflicht.

---
*Zuletzt aktualisiert via Browser-Sync: 2026-02-16*
