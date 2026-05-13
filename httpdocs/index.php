<?php
require_once 'includes/db.php';
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Application | Welcome</title>
    <meta name="description" content="Moderne PHP & MySQL CRM Applikation">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;800&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header>
            <div class="logo">CRM Engine</div>
        </header>

        <main>
            <section class="hero">
                <h1>Willkommen zur CRM App</h1>
                <p class="subtitle">Ihre moderne Entwicklungsumgebung ist bereit.</p>
            </section>

            <div class="grid">
                <!-- PHP Status Card -->
                <div class="card" id="php-status">
                    <h2>PHP Umgebung</h2>
                    <p>Version: <?php echo PHP_VERSION; ?></p>
                    <p>SAPI: <?php echo php_sapi_name(); ?></p>
                    <div class="status status-ok">Aktiv</div>
                </div>

                <!-- Database Status Card -->
                <div class="card" id="db-status">
                    <h2>Datenbank (MySQL 8)</h2>
                    <?php if (isset($db_result) && $db_result['success']): ?>
                        <p>Verbindung erfolgreich hergestellt via ag_database_layer.</p>
                        <div class="status status-ok">Verbunden</div>
                        
                        <?php 
                        // Test query using the new layer
                        $test_query = db_query("SELECT VERSION() as version");
                        if ($test_query['success']): ?>
                            <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">
                                Server Version: <?php echo $test_query['data'][0]['version']; ?>
                            </p>
                        <?php endif; ?>

                    <?php else: ?>
                        <p>Verbindung fehlgeschlagen oder Datenbank nicht gefunden.</p>
                        <p style="font-size: 0.8rem; color: var(--text-secondary); margin-top: 0.5rem;">
                            Error: <?php echo isset($db_connection_error) ? $db_connection_error : 'Unbekannt'; ?>
                        </p>
                        <div class="status status-err">Getrennt</div>
                        <p style="margin-top: 1rem; font-size: 0.9rem;">
                            Bitte erstelle die Datenbank <code>dev_db</code> in deinem MySQL Server.
                        </p>
                    <?php endif; ?>
                </div>

                <!-- Next Steps Card -->
                <div class="card">
                    <h2>Nächste Schritte</h2>
                    <p>Beginnen Sie mit der Erstellung Ihrer ersten Module im <code>httpdocs</code> Verzeichnis.</p>
                    <a href="#" class="btn">Dokumentation öffnen</a>
                </div>
            </div>
        </main>

        <footer class="footer">
            &copy; <?php echo date('Y'); ?> CRM Engine - Built with Antigravity AI
        </footer>
    </div>
</body>
</html>
