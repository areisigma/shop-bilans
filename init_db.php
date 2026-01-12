<?php
// Skrypt inicjalizujący bazę danych
// Uruchom ten plik raz, aby utworzyć bazę danych i tabelę

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'shop_bilans';

try {
    // Najpierw połącz się bez wyboru bazy danych
    $conn = new PDO("mysql:host=$host;charset=utf8mb4", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Utwórz bazę danych jeśli nie istnieje
    $conn->exec("CREATE DATABASE IF NOT EXISTS $dbname CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Baza danych '$dbname' została utworzona lub już istnieje.\n";
    
    // Wybierz bazę danych
    $conn->exec("USE $dbname");
    
    // Utwórz tabelę users
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "Tabela 'users' została utworzona lub już istnieje.\n";
    
    // Utwórz tabelę entries
    $sql = "CREATE TABLE IF NOT EXISTS entries (
        id VARCHAR(50) PRIMARY KEY,
        user_id INT NOT NULL,
        amount DECIMAL(10, 2) NOT NULL,
        description VARCHAR(255) NOT NULL,
        timestamp DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $conn->exec($sql);
    echo "Tabela 'entries' została utworzona lub już istnieje.\n";
    
    echo "\nBaza danych została pomyślnie zainicjalizowana!\n";
    echo "Możesz teraz korzystać z aplikacji.\n";
    
} catch(PDOException $e) {
    die("Błąd: " . $e->getMessage() . "\n");
}
?>
