<?php
// ============================================================
// KONFIGURACJA POŁĄCZENIA Z BAZĄ DANYCH MySQL
// ============================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'db.zmitac');        // <-- zmień na nazwę swojej bazy
define('DB_USER', 'root');           // <-- zmień na swojego użytkownika
define('DB_PASS', '');               // <-- zmień na swoje hasło
define('DB_CHARSET', 'utf8mb4');

/**
 * Zwraca singleton PDO – jedno połączenie na cały request.
 */
function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = sprintf(
                "mysql:host=%s;dbname=%s;charset=%s",
                DB_HOST, DB_NAME, DB_CHARSET
            );
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            die('<b>Błąd połączenia z bazą danych:</b> '
                . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}
