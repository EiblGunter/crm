<?php
/**
 * Application: Adresse Management 
 * Description: Features auto-save, navigation, undo/redo, and automatic table creation.
 * Guidelines: Scriptcase-like macros simulated, prozeduraler db_layer verwendet.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/tools/db/db.php';

// --- DATABASE CONNECTION ---
if (!getenv('MYSQL_HOST')) {
    $envPath = __DIR__ . '/../../../.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name); $value = trim($value);
                putenv(sprintf('%s=%s', $name, $value));
            }
        }
    }
}

$mysql_config = array(
    'driver'  => 'mysql',
    'host'    => getenv('MYSQL_HOST') ?: '127.0.0.1',
    'port'    => getenv('MYSQL_PORT') ?: '3307',
    'db'      => getenv('MYSQL_DATABASE') ?: 'crm_db',
    'user'    => getenv('MYSQL_USER') ?: 'root',
    'pass'    => getenv('MYSQL_PASSWORD') ?: 'Hotel111',
    'charset' => 'utf8mb4'
);

db_connect($mysql_config, 'default');

// --- DATABASE INITIALIZATION ---
// Handled via init.sql, but we ensure the table exists here for the demo.
function initDatabase()
{
    $sql = "CREATE TABLE IF NOT EXISTS customers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        vorname VARCHAR(100),
        nachname VARCHAR(100),
        strasse VARCHAR(150),
        plz VARCHAR(20),
        ort VARCHAR(200),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    db_query($sql, array());
}

initDatabase();

// --- AJAX API HANDLERS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];
    $response = array('success' => false);

    if ($action === 'save') {
        $id = intval($_POST['id']);
        $field = $_POST['field'];
        $value = $_POST['value'];

        // Validate field to prevent injection
        $allowedFields = array('vorname', 'nachname', 'strasse', 'plz', 'ort');
        if (in_array($field, $allowedFields)) {
            $now = date('Y-m-d H:i:s');
            $result = db_update('customers', array($field => $value), array('id' => $id));

            if ($result['success']) {
                $response['success'] = true;
                $response['updated'] = $now;
            } else {
                $response['error'] = $result['error'];
            }
        }
    } elseif ($action === 'fetch') {
        $id = intval($_POST['id']);
        $result = db_select('customers', array('id' => $id));
        
        if ($result['success'] && !empty($result['data'])) {
            $response['success'] = true;
            $response['data'] = $result['data'][0];
        } elseif (!$result['success']) {
            $response['error'] = $result['error'];
        }
    } elseif ($action === 'navigate') {
        $currentId = intval($_POST['current_id']);
        $direction = $_POST['direction']; 

        $sql = "";
        $params = array();

        if ($direction === 'next') {
            $sql = "SELECT * FROM customers WHERE id > ? ORDER BY id ASC LIMIT 1";
            $params = array($currentId);
        } elseif ($direction === 'prev') {
            $sql = "SELECT * FROM customers WHERE id < ? ORDER BY id DESC LIMIT 1";
            $params = array($currentId);
        } elseif ($direction === 'first') {
            $sql = "SELECT * FROM customers ORDER BY id ASC LIMIT 1";
        } elseif ($direction === 'last') {
            $sql = "SELECT * FROM customers ORDER BY id DESC LIMIT 1";
        }

        $result = db_query($sql, $params);
        $data = null;
        
        if ($result['success'] && !empty($result['data'])) {
            $data = $result['data'][0];
        }

        if (!$data && ($direction === 'next' || $direction === 'prev')) {
            $currResult = db_select('customers', array('id' => $currentId));
            if ($currResult['success'] && !empty($currResult['data'])) {
                $data = $currResult['data'][0];
            }
        }

        if ($data) {
            $response['success'] = true;
            $response['data'] = $data;
        } else {
            $response['error'] = $result['error'] ?? 'Keine Daten gefunden';
        }
    } elseif ($action === 'new') {
        $result = db_insert('customers', array('vorname' => ''));
        
        if ($result['success']) {
            $newId = $result['last_id'];
            $selResult = db_select('customers', array('id' => $newId));
            
            if ($selResult['success'] && !empty($selResult['data'])) {
                $response['success'] = true;
                $response['data'] = $selResult['data'][0];
            }
        } else {
            $response['error'] = $result['error'];
        }
    }

    echo json_encode($response);
    exit;
}

// --- INITIAL LOAD ---
$initialData = null;
$error = null;

$result = db_select('customers', array(), array('order_by' => 'id ASC', 'limit' => 1));

if ($result['success'] && !empty($result['data'])) {
    $initialData = $result['data'][0];
} elseif ($result['success'] && empty($result['data'])) {
    // Create first record if empty
    $insRes = db_insert('customers', array(
        'vorname' => '',
        'nachname' => ''
    ));
    
    if ($insRes['success']) {
        $newId = $insRes['last_id'];
        $selRes = db_select('customers', array('id' => $newId));
        if ($selRes['success'] && !empty($selRes['data'])) {
            $initialData = $selRes['data'][0];
        }
    } else {
        $error = $insRes['error'];
    }
} else {
    $error = $result['error'];
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adressverwaltung</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #007bff;
            --primary-hover: #0056b3;
            --bg: #f8f9fa;
            --card-bg: #ffffff;
            --text: #333;
            --text-muted: #666;
            --border: #dee2e6;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            --radius: 8px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text);
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 800px;
            background: var(--card-bg);
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        /* Toolbar */
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .btn-group {
            display: flex;
            gap: 8px;
        }

        button {
            padding: 8px 16px;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: #fff;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }

        button:hover:not(:disabled) {
            background: #f0f0f0;
            border-color: #ccc;
        }

        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-primary {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .btn-primary:hover:not(:disabled) {
            background: var(--primary-hover);
        }

        .status-badge {
            background: #f1f3f5;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 13px;
            color: var(--text-muted);
            border: 1px solid var(--border);
        }

        /* Form Layout */
        .grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .full-width {
            grid-column: span 2;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        label {
            font-weight: 500;
            font-size: 14px;
            color: var(--text-muted);
        }

        input {
            padding: 12px;
            border: 1px solid var(--border);
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        /* Footer Info */
        .footer-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: var(--text-muted);
        }

        /* Notification */
        #notification {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            padding: 10px 20px;
            border-radius: 30px;
            background: #333;
            color: white;
            font-size: 14px;
            opacity: 0;
            transition: opacity 0.3s;
            z-index: 1000;
        }

        #notification.show {
            opacity: 1;
        }

        /* Icons (Simple SVG) */
        .icon {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }
    </style>
</head>

<body>

    <div id="notification">Gespeichert</div>

    <div class="container">
        <?php if ($error): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; margin-bottom: 20px; border-radius: 6px; border: 1px solid #f5c6cb;">
            <strong>Fehler:</strong> <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <div class="toolbar">
            <div class="btn-group">
                <button onclick="undo()" id="btnUndo" title="Rückgängig">
                    <svg class="icon" viewBox="0 0 24 24">
                        <path
                            d="M12.5 8c-2.65 0-5.05.99-6.9 2.6L2 7v9h9l-3.62-3.62c1.39-1.16 3.16-1.88 5.12-1.88 3.54 0 6.55 2.31 7.6 5.5l2.37-.78C21.08 11.03 17.15 8 12.5 8z" />
                    </svg>
                </button>
                <button onclick="redo()" id="btnRedo" title="Wiederholen">
                    <svg class="icon" viewBox="0 0 24 24">
                        <path
                            d="M18.4 10.6C16.55 8.99 14.15 8 11.5 8c-4.65 0-8.58 3.03-9.96 7.22L3.91 16c1.05-3.19 4.06-5.5 7.59-5.5 1.96 0 3.73.72 5.12 1.88L13 16h9V7l-3.6 3.6z" />
                    </svg>
                </button>
                <button onclick="reloadRecord()" title="Neu laden">
                    <svg class="icon" viewBox="0 0 24 24">
                        <path
                            d="M17.65 6.35C16.2 4.9 14.21 4 12 4c-4.42 0-7.99 3.58-7.99 8s3.57 8 7.99 8c3.73 0 6.84-2.55 7.73-6h-2.08c-.82 2.33-3.04 4-5.65 4-3.31 0-6-2.69-6-6s2.69-6 6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35z" />
                    </svg>
                </button>
            </div>

            <div class="status-badge">
                Geladen: <span id="currentIdDisplay">
                    <?php echo $initialData['id'] ?? '-'; ?>
                </span>
            </div>

            <div class="btn-group">
                <button onclick="navigate('prev')" title="Vorheriger">
                    <svg class="icon" viewBox="0 0 24 24">
                        <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" />
                    </svg>
                </button>
                <button onclick="createNew()" class="btn-primary">
                    + Neu
                </button>
                <button onclick="navigate('next')" title="Nächster">
                    <svg class="icon" viewBox="0 0 24 24">
                        <path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z" />
                    </svg>
                </button>
            </div>
        </div>

        <form id="addressForm" class="grid" onsubmit="return false;">
            <input type="hidden" name="id" id="field_id" value="<?php echo $initialData['id'] ?? ''; ?>">

            <div class="form-group">
                <label for="vorname">Vorname</label>
                <input type="text" id="vorname" name="vorname"
                    value="<?php echo htmlspecialchars($initialData['vorname'] ?? ''); ?>" onblur="autoSave(this)">
            </div>

            <div class="form-group">
                <label for="nachname">Name</label>
                <input type="text" id="nachname" name="nachname"
                    value="<?php echo htmlspecialchars($initialData['nachname'] ?? ''); ?>" onblur="autoSave(this)">
            </div>

            <div class="form-group full-width">
                <label for="strasse">Straße</label>
                <input type="text" id="strasse" name="strasse"
                    value="<?php echo htmlspecialchars($initialData['strasse'] ?? ''); ?>" onblur="autoSave(this)">
            </div>

            <div class="form-group">
                <label for="plz">PLZ</label>
                <input type="text" id="plz" name="plz"
                    value="<?php echo htmlspecialchars($initialData['plz'] ?? ''); ?>" onblur="autoSave(this)">
            </div>

            <div class="form-group">
                <label for="ort">Ort</label>
                <input type="text" id="ort" name="ort"
                    value="<?php echo htmlspecialchars($initialData['ort'] ?? ''); ?>" onblur="autoSave(this)">
            </div>
        </form>

        <div class="footer-info">
            <div>Erstellt: <span id="display_created">
                    <?php echo $initialData['created_at'] ?? '-'; ?>
                </span></div>
            <div>Geändert: <span id="display_updated">
                    <?php echo $initialData['updated_at'] ?? '-'; ?>
                </span></div>
        </div>
    </div>

    <script>
        let undoStack = [];
        let redoStack = [];
        let isNavigating = false;

        function showNotification(text) {
            const n = document.getElementById('notification');
            n.textContent = text;
            n.classList.add('show');
            setTimeout(() => n.classList.remove('show'), 2000);
        }

        async function saveToServer(field, value) {
            const id = document.getElementById('field_id').value;
            const formData = new FormData();
            formData.append('action', 'save');
            formData.append('id', id);
            formData.append('field', field);
            formData.append('value', value);

            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    showNotification('Gespeichert');
                    document.getElementById('display_updated').textContent = result.updated;
                    document.getElementById(field).setAttribute('data-prev', value);
                } else if (result.error) {
                    console.error('Save failed: ' + result.error);
                }
            } catch (e) {
                console.error('Save error', e);
            }
        }

        async function autoSave(input) {
            if (isNavigating) return;

            const field = input.name;
            const value = input.value;
            const previousValue = input.getAttribute('data-prev') || '';

            if (value === previousValue) return;

            // Push to undo stack for manual changes
            undoStack.push({ field, value: previousValue });
            redoStack = []; // Clear redo on new change
            updateUndoRedoButtons();

            await saveToServer(field, value);
        }

        async function navigate(direction) {
            isNavigating = true;
            const currentId = document.getElementById('field_id').value;
            const formData = new FormData();
            formData.append('action', 'navigate');
            formData.append('current_id', currentId);
            formData.append('direction', direction);

            await fetchAndPopulate(formData);
            isNavigating = false;
        }

        async function createNew() {
            isNavigating = true;
            const formData = new FormData();
            formData.append('action', 'new');
            await fetchAndPopulate(formData);
            isNavigating = false;
        }

        async function reloadRecord() {
            const id = document.getElementById('field_id').value;
            const formData = new FormData();
            formData.append('action', 'fetch');
            formData.append('id', id);
            await fetchAndPopulate(formData);
        }

        async function fetchAndPopulate(formData) {
            try {
                const response = await fetch('', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (result.success) {
                    const data = result.data;
                    document.getElementById('field_id').value = data.id;
                    document.getElementById('currentIdDisplay').textContent = data.id;

                    const fields = ['vorname', 'nachname', 'strasse', 'plz', 'ort'];
                    fields.forEach(f => {
                        const el = document.getElementById(f);
                        el.value = data[f] || '';
                        el.setAttribute('data-prev', el.value);
                    });

                    document.getElementById('display_created').textContent = data.created_at;
                    document.getElementById('display_updated').textContent = data.updated_at;

                    // Reset stacks on record change
                    undoStack = [];
                    redoStack = [];
                    updateUndoRedoButtons();
                } else if (result.error) {
                    console.error('Fetch error: ' + result.error);
                }
            } catch (e) {
                console.error('Fetch failed', e);
            }
        }

        async function undo() {
            if (undoStack.length === 0) return;
            const change = undoStack.pop();
            const el = document.getElementById(change.field);
            const currentValue = el.value;

            // Move current to redo stack
            redoStack.push({ field: change.field, value: currentValue });

            el.value = change.value;
            await saveToServer(change.field, change.value);
            updateUndoRedoButtons();
        }

        async function redo() {
            if (redoStack.length === 0) return;
            const change = redoStack.pop();
            const el = document.getElementById(change.field);
            const currentValue = el.value;

            // Move current to undo stack
            undoStack.push({ field: change.field, value: currentValue });

            el.value = change.value;
            await saveToServer(change.field, change.value);
            updateUndoRedoButtons();
        }

        function updateUndoRedoButtons() {
            document.getElementById('btnUndo').disabled = undoStack.length === 0;
            document.getElementById('btnRedo').disabled = redoStack.length === 0;
        }

        // Initialize data-prev attributes
        window.onload = () => {
            const fields = ['vorname', 'nachname', 'strasse', 'plz', 'ort'];
            fields.forEach(f => {
                const el = document.getElementById(f);
                if (el) el.setAttribute('data-prev', el.value);
            });
            updateUndoRedoButtons();
        };
    </script>

</body>

</html>
