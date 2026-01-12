<?php
// Konfiguracja bazy danych
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'shop_bilans');

// Funkcja do nawiązania połączenia z bazą danych
function getDbConnection() {
    try {
        $conn = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        return $conn;
    } catch(PDOException $e) {
        die(json_encode([
            'success' => false,
            'message' => 'Błąd połączenia z bazą danych: ' . $e->getMessage()
        ]));
    }
}
?>
