---
name: Anti-Gravity Database Layer
description: Strict guidelines constraints for all database operations in this project. Enforces the use of `db_*` procedural functions over native PDO or Scriptcase macros.
---

# Anti-Gravity Database Layer (DB Abstraction)

Wenn du (die KI) aufgefordert wirst, Datenbank-Operationen (CRUD, Queries, Updates) für dieses Projekt zu schreiben, **musst du zwingend diese Architektur-Richtlinien befolgen**. 
Es dürfen KEINE eigenen PDO-Instanzen, MySQLi-Befehle oder klassenschreibweisen verwendet werden.

## 1. Verfügbare Kernfunktionen (Keine PDO-Instanzen!)
Verwende in deinem Code AUSSCHLIESSLICH diese prozeduralen Wrapper-Funktionen:
- `db_select($table, $filters = array(), $options = array())`
- `db_insert($table, $data = array())`
- `db_update($table, $data = array(), $filters = array())`
- `db_delete($table, $filters = array())`
- `db_query($sql_string, $params_array = array())`  <- Für komplexe JOINs oder Custom-SQL.
- Transaktionen: `db_transaction_start()`, `db_commit()`, `db_rollback()`

## 2. Array-Syntax (Scriptcase-Kompatibilität)
**KRITISCH:** Da der Code zum Teil durch einen Scriptcase-Codegenerator läuft, darfst du **NIEMALS die kurze Array-Schreibweise `[]` benutzen**.
- ❌ Falsch: `$data = ['name' => 'Test'];`
- ✅ Richtig: `$data = array('name' => 'Test');`

## 3. Zwingendes Rückgabe-Schema
Jede Aufruf der `db_*` Funktionen liefert *ausnahmslos* ein Assoziatives Array mit genau diesen 5 Schlüsseln zurück. Dein Code MUSS dieses Schema immer erwarten und auswerten:
```php
$result = db_select('users', array('id' => 1));
// Das Resultat sieht immer so aus:
/* array(
    'success'       => boolean, // True, wenn erfolgreich
    'data'          => array(), // Gefundene Zeilen bei SELECT (z.B. $result['data'][0]['name'])
    'error'         => string,  // Fehlermeldung, falls success = false
    'affected_rows' => integer, // Editierte Zeilen bei UPDATE/DELETE
    'last_id'       => mixed    // Generierte ID bei INSERT
) */
```

## 4. Best Practices für den Code-Flow
1. **Fehler-Prüfung:** Prüfe vor der reinen Datenverarbeitung immer zwingend `$result['success']`. Falls dieser Wert `false` ist, logge oder gib den Inhalt von `$result['error']` aus, aber unterbrich nach Möglichkeit nicht den funktionalen Ablauf mit einem harten `die()` oder `exit()`.
2. **Daten entnehmen:** Hole den Datensatz bei einem Select immer aus der `$result['data']` Rückgabe der Funktion.
3. **Verbindungsaufbau (nur in Standalone):** In Scriptcase ist die DB-Verbindung global aktiv. Schreibst du jedoch ein völlig eigenständiges API-Skript (`Standalone` / AJAX Backend außerhalb von Scriptcase), muss die Verbindung einmalig vorab manuell initialisiert werden. Du musst hierbei immer `tools/db/db.php` einbinden und die `.env` über das Server Root-Verzeichnis auslesen:

```php
require_once $_SERVER['DOCUMENT_ROOT'] . '/tools/db/db.php';

if (!getenv('MYSQL_HOST')) {
    $envPath = $_SERVER['DOCUMENT_ROOT'] . '/../.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                putenv(sprintf('%s=%s', trim($name), trim($value)));
            }
        }
    }
}

$mysql_config = array(
    'driver'  => 'mysql',
    'host'    => getenv('MYSQL_HOST') ?: 'mysql_db',
    'db'      => getenv('MYSQL_DATABASE') ?: 'crm_db',
    'user'    => getenv('MYSQL_USER') ?: 'dev_user',
    'pass'    => getenv('MYSQL_PASSWORD') ?: 'dev_password',
    'charset' => 'utf8mb4'
);
db_connect($mysql_config, 'default');
```
