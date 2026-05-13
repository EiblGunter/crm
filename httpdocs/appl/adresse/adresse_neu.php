<?php
/**
 * Application: Adresse Management (Refactored to Anti-Gravity Design System)
 * Description: Features auto-save, navigation, undo/redo, and automatic table creation.
 * Guidelines: Nutzt ag_library.php für Header, Footer und Field-Groups.
 */

require_once __DIR__ . '/../../../tools/db/db.php';
require_once __DIR__ . '/../../../tools/design_templates/ag_library.php';

// --- DATABASE CONNECTION ---
if (!getenv('MYSQL_HOST')) {
    $envPath = __DIR__ . '/../../../.env';
    if (file_exists($envPath)) {
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0)
                continue;
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                putenv(sprintf('%s=%s', $name, $value));
            }
        }
    }
}

$mysql_config = array(
    'driver' => 'mysql',
    'host' => getenv('MYSQL_HOST') ?: '127.0.0.1',
    'port' => getenv('MYSQL_PORT') ?: '3307',
    'db' => getenv('MYSQL_DATABASE') ?: 'dev_db',
    'user' => getenv('MYSQL_USER') ?: 'root',
    'pass' => getenv('MYSQL_PASSWORD') ?: 'Hotel111',
    'charset' => 'utf8mb4'
);

db_connect($mysql_config, 'default');

// --- DATABASE INITIALIZATION ---
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
    <!-- Tailwind & Fonts -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <?php ag_inject_css_variables([]); // Default Theme ?>
    <style>
        body {
            background-color: var(--ag-color-background, #f8fafc);
            color: var(--ag-color-text-main, #0f172a);
            transition: background-color 0.4s ease, color 0.4s ease;
        }
        .ag-page-card {
            background-color: var(--ag-color-surface, #ffffff);
            border-color: var(--ag-color-border, #e2e8f0);
            border-radius: var(--ag-spacing-border_radius, 2rem);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .ag-input {
            background-color: var(--ag-color-input_bg, #fff) !important;
            color: var(--ag-color-input_text, inherit) !important;
            border-radius: var(--ag-spacing-input_radius, 6px) !important;
            padding: var(--ag-spacing-input_padding, 0.5rem 0.75rem) !important;
            font-size: var(--ag-font-input-size, 13px) !important;
        }
    </style>
</head>

<body class="min-h-screen font-['Inter'] antialiased">

    <?php ag_render_header('Adressverwaltung', $initialData['id'] ?? '...'); ?>

    <main class="max-w-4xl mx-auto my-12">
        <?php if ($error): ?>
            <div
                class="mx-6 mb-6 p-4 bg-red-50 border border-red-200 text-red-700 rounded-xl text-sm font-bold flex items-center gap-3">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="ag-page-card bg-white shadow-2xl shadow-slate-200/50 border border-slate-200 p-12 relative overflow-hidden"
            style="min-height: 550px;">
            <!-- Design Decorative Elements -->
            <div class="absolute top-0 right-0 w-32 h-32 bg-slate-50 rounded-bl-[4rem] -mr-16 -mt-16 opacity-50"></div>

            <form id="addressForm" onsubmit="return false;" class="relative">
                <input type="hidden" name="id" id="field_id" value="<?php echo $initialData['id'] ?? ''; ?>">

                <?php
                // Definitionen der Felder
                $fields = [
                    'vorname' => ['label' => 'Vorname', 'y' => 40, 'w' => 500],
                    'nachname' => ['label' => 'Nachname', 'y' => 100, 'w' => 500],
                    'strasse' => ['label' => 'Straße & Hausnr.', 'y' => 160, 'w' => 500],
                    'plz' => ['label' => 'PLZ', 'y' => 220, 'w' => 120],
                    'ort' => ['label' => 'Ort', 'y' => 220, 'w' => 360, 'x_offset' => 300]
                ];

                foreach ($fields as $fieldName => $cfg) {
                    $lx = $cfg['x_offset'] ?? 0;
                    $f = [
                        'fieldName' => $fieldName,
                        'label' => $cfg['label'],
                        'form' => [
                            'lbl' => ['x' => 40 + $lx, 'y' => $cfg['y']],
                            'inp' => ['x' => 180 + $lx, 'y' => $cfg['y'] - 5]
                        ],
                        'width' => $cfg['w']
                    ];
                    $val = htmlspecialchars($initialData[$fieldName] ?? '');
                    $input_html = "<input type='text' id='{$fieldName}' name='{$fieldName}' class='ag-input' value='{$val}' onblur='autoSave(this)' placeholder='Geben Sie {$cfg['label']} ein...'>";
                    ag_render_field_group($f, $input_html);
                }
                ?>
            </form>

            <!-- Footer Meta Info -->
            <div
                class="absolute bottom-10 left-12 right-12 flex justify-between text-[10px] font-black uppercase tracking-[0.1em] text-slate-400 border-t border-slate-100 pt-8">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-green-400"></span>
                    Erstellt: <span id="display_created" class="text-slate-600 ml-1">
                        <?php echo $initialData['created_at'] ?? '-'; ?>
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <i class="fas fa-history text-slate-300"></i>
                    Geändert: <span id="display_updated" class="text-slate-600 ml-1">
                        <?php echo $initialData['updated_at'] ?? '-'; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Custom Buttons beneath (Undo/Redo/New) -->
        <div class="mt-8 flex justify-center gap-4">
            <button onclick="undo()" id="btnUndo"
                class="flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 rounded-xl text-slate-600 font-bold text-xs hover:bg-slate-50 disabled:opacity-30 transition-all shadow-sm">
                <i class="fas fa-undo"></i> UNDO
            </button>
            <button onclick="createNew()"
                class="flex items-center gap-2 px-8 py-3 bg-blue-600 text-white rounded-xl font-bold text-xs hover:bg-blue-700 transition-all shadow-lg shadow-blue-200">
                <i class="fas fa-plus"></i> DATENSATZ NEU
            </button>
            <button onclick="redo()" id="btnRedo"
                class="flex items-center gap-2 px-6 py-3 bg-white border border-slate-200 rounded-xl text-slate-600 font-bold text-xs hover:bg-slate-50 disabled:opacity-30 transition-all shadow-sm">
                REDO <i class="fas fa-redo"></i>
            </button>
        </div>
    </main>

    <div id="notification"
        class="fixed top-24 left-1/2 -translate-x-1/2 px-6 py-2 bg-slate-900 text-white text-[11px] font-black uppercase tracking-widest rounded-full opacity-0 transition-all duration-300 z-[2000] shadow-xl pointer-events-none">
        Saved
    </div>

    <?php ag_render_footer("Anti-Gravity ERP &bull; Adresse Management System"); ?>

    <script>
        // Anti-Gravity App Bridge
        const app = {
            nav: (dir) => navigate(dir),
            toggleDesign: (val) => document.body.classList.toggle('ag-design-active', val),
            openConfigEditor: () => alert('Config Editor wird geladen...')
        };

        let undoStack = [];
        let redoStack = [];
        let isNavigating = false;

        function showNotification(text) {
            const n = document.getElementById('notification');
            n.textContent = text;
            n.style.opacity = '1';
            n.style.transform = 'translate(-50%, -10px)';
            setTimeout(() => {
                n.style.opacity = '0';
                n.style.transform = 'translate(-50%, 0)';
            }, 2000);
        }

        async function saveToServer(field, value) {
            const id = document.getElementById('field_id').value;
            const formData = new FormData();
            formData.append('action', 'save');
            formData.append('id', id);
            formData.append('field', field);
            formData.append('value', value);

            try {
                const response = await fetch('', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    showNotification('Gespeichert');
                    document.getElementById('display_updated').textContent = result.updated;
                    document.getElementById(field).setAttribute('data-prev', value);
                }
            } catch (e) { console.error('Save error', e); }
        }

        async function autoSave(input) {
            if (isNavigating) return;
            const field = input.name;
            const value = input.value;
            const previousValue = input.getAttribute('data-prev') || '';

            if (value === previousValue) return;

            undoStack.push({ field, value: previousValue });
            redoStack = [];
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

        async function fetchAndPopulate(formData) {
            try {
                const response = await fetch('', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.success) {
                    const data = result.data;
                    document.getElementById('field_id').value = data.id;
                    if (document.getElementById('record-indicator')) {
                        document.getElementById('record-indicator').textContent = 'ID: ' + data.id;
                    }

                    ['vorname', 'nachname', 'strasse', 'plz', 'ort'].forEach(f => {
                        const el = document.getElementById(f);
                        if (el) {
                            el.value = data[f] || '';
                            el.setAttribute('data-prev', el.value);
                            el.classList.remove('ag-invalid');
                        }
                    });

                    document.getElementById('display_created').textContent = data.created_at;
                    document.getElementById('display_updated').textContent = data.updated_at;

                    undoStack = [];
                    redoStack = [];
                    updateUndoRedoButtons();
                }
            } catch (e) { console.error('Fetch failed', e); }
        }

        async function undo() {
            if (undoStack.length === 0) return;
            const change = undoStack.pop();
            const el = document.getElementById(change.field);
            redoStack.push({ field: change.field, value: el.value });
            el.value = change.value;
            await saveToServer(change.field, change.value);
            updateUndoRedoButtons();
        }

        async function redo() {
            if (redoStack.length === 0) return;
            const change = redoStack.pop();
            const el = document.getElementById(change.field);
            undoStack.push({ field: change.field, value: el.value });
            el.value = change.value;
            await saveToServer(change.field, change.value);
            updateUndoRedoButtons();
        }

        function updateUndoRedoButtons() {
            const u = document.getElementById('btnUndo');
            const r = document.getElementById('btnRedo');
            if (u) u.disabled = undoStack.length === 0;
            if (r) r.disabled = redoStack.length === 0;
        }

        window.onload = () => {
            ['vorname', 'nachname', 'strasse', 'plz', 'ort'].forEach(f => {
                const el = document.getElementById(f);
                if (el) el.setAttribute('data-prev', el.value);
            });
            updateUndoRedoButtons();
        };
    </script>
</body>

</html>