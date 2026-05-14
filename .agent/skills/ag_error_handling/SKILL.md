---
name: AG Error Handling & Output Buffering
description: Strict guidelines for handling "Silent Failures" and protecting UI/AJAX responses via global output buffering.
---

# AG Error Handling & Output Buffering

## 🎯 Purpose
To prevent "Silent Failures" (e.g., faulty libraries, runtime errors, unwanted `echo` outputs) from breaking JSON AJAX responses or corrupting the UI in Blank Applications. This is achieved through a mandatory global output buffering pattern.

## 📜 Core Rules

### 1. Initialization (Start)
You MUST inject `ob_start();` at the very beginning of the processing logic.
- Place it directly after session starts, database connections, and standard includes.
- It MUST be placed *before* any business logic or external function calls.

```php
// Example: Start of processing
require_once 'config.php';
session_start();

// 1. START ERROR/OUTPUT BUFFERING
ob_start();
```

### 2. Processing
Execute the entire business logic (DB updates, `ag_database_layer` function calls, third-party library calls) within this buffer. Any accidental `echo` statements or PHP runtime errors will be caught in this buffer.

### 3. Finalization (End & Assignment)
Close the buffer and assign its contents to the `$sys_debug_log` variable exactly before the output rendering begins (e.g., before `<!DOCTYPE html>` or before `json_encode` for AJAX).

```php
// 3. END ERROR/OUTPUT BUFFERING
$sys_debug_log = trim(ob_get_clean());
```

### 4. UI Output (For HTML Views)
In HTML views, integrate a conditional, highly visible block to display `$sys_debug_log` if it is not empty.
- Place this block in the visible HTML area (e.g., at the top of the `<main>` container).
- Use the AG Design System (Tailwind CSS) for styling.

**Standardized UI Block:**
```php
<?php if (!empty($sys_debug_log)): ?>
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-800 p-4 mb-6 shadow-sm rounded-r">
        <h3 class="font-bold text-sm mb-2">System Debug / Uncaught Output:</h3>
        <pre class="text-xs overflow-auto whitespace-pre-wrap"><?= htmlspecialchars($sys_debug_log) ?></pre>
    </div>
<?php endif; ?>
```

### 5. AJAX / API Output (For JSON Responses)
When the application responds to an AJAX request, `$sys_debug_log` MUST NOT be echoed as raw HTML.
- Inject the `$sys_debug_log` variable into the JSON response array so the frontend can handle it safely without breaking `JSON.parse()`.

**Standardized AJAX Response:**
```php
$response = [
    'success' => true, // or false depending on logic
    'message' => 'Action completed',
    'sys_debug_log' => $sys_debug_log // ALWAYS include this if you use the buffer
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($response);
exit;
```
