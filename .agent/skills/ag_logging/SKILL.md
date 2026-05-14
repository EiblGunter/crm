---
name: Anti-Gravity Logging System
description: Core logging guidelines for tracking database changes, user actions, and system events in the CRM. Mirror of Scriptcase crm_log implementation.
---

# Anti-Gravity Logging System (CRM Logging)

Dieses Projekt nutzt eine zentrale Logging-Lösung (`crm_log`), um Datenbankänderungen, Benutzeraktionen und Systemereignisse zu protokollieren. 
Wenn du (die KI) Code generierst, der Datensätze einfügt, aktualisiert, löscht oder kritische Aktionen ausführt, **musst du zwingend die Funktion `crm_log_add()` verwenden**.

## 1. Die Logging-Funktion
Die Funktion `crm_log_add($params_array)` ist global verfügbar (via `httpdocs/includes/functions_log.php`). Sie kapselt das Schreiben in die Tabelle `crm_log`.

### Parameter-Struktur (Keys des Arrays)
Folgende Keys können/sollen dem Array übergeben werden (alle sind optional, aber fülle so viele wie möglich passend zum Kontext):
- `app_name`: (String) Name der aktuellen App oder Datei.
- `my_role`: (String) Rolle des Nutzers (falls bekannt).
- `action_type`: (String) **PFLICHT**. Werte: 'insert', 'update', 'soft_delete', 'restore', 'error', 'login', 'undo', 'redo'.
- `table_name`: (String) Betroffene DB-Tabelle.
- `record_id`: (String/Int) ID des veränderten Datensatzes.
- `changed_field_name`: (String) Nur bei AJAX-Single-Field-Updates nutzen.
- `field_old_value`: (String) Alter Wert bei Single-Field-Update.
- `field_new_value`: (String) Neuer Wert bei Single-Field-Update.
- `full_old_data`: (Array) Kompletter Datensatz VOR der Aktion.
- `full_new_data`: (Array) Kompletter Datensatz NACH der Aktion.
- `description`: (String) Freitext oder Zusammenfassung der Aktion.
- `created_by`: (String) Benutzerkennung (z.B. aus der Session).

## 2. Implementierungs-Regeln
- **Array-Syntax:** Verwende immer `array()` anstelle von `[]` (Scriptcase-Kompatibilität).
- **Zeitpunkt:** Rufe `crm_log_add()` unmittelbar *nach* einer erfolgreichen Datenbank-Operation auf.
- **Daten-Integrität:** Wenn du ein einzelnes Feld aktualisierst (`changed_field_name`), übergib trotzdem die `record_id` und den `table_name`.
- **Fehler-Logging:** Nutze `action_type => 'error'`, um abgefangene Exceptions oder kritische DB-Fehler zu protokollieren.

## 3. Code-Beispiel
```php
// Beispiel: Update eines Datensatzes
$update_data = array('status' => 'active');
$res = db_update('customers', $update_data, array('id' => 123));

if ($res['success']) {
    crm_log_add(array(
        'app_name'    => 'customer_manager',
        'action_type' => 'update',
        'table_name'  => 'customers',
        'record_id'   => 123,
        'description' => 'Status auf aktiv gesetzt',
        'full_new_data' => $update_data
    ));
}
```

## 4. Datenbank-Schema
Die Tabelle `crm_log` wird automatisch erstellt oder muss vorhanden sein. Sie enthält Felder für Metadaten, JSON-Snapshots (`full_old_data`, `full_new_data`) und Zeitstempel.
