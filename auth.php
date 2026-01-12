<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Brak akcji']);
    exit;
}

$action = $input['action'];

try {
    $db = getDbConnection();
    
    if ($action === 'register') {
        // Rejestracja
        if (!isset($input['username']) || !isset($input['password'])) {
            echo json_encode(['success' => false, 'message' => 'Brak wymaganych pól']);
            exit;
        }
        
        $username = trim($input['username']);
        $password = $input['password'];
        
        if (strlen($username) < 3) {
            echo json_encode(['success' => false, 'message' => 'Login musi mieć co najmniej 3 znaki']);
            exit;
        }
        
        if (strlen($password) < 4) {
            echo json_encode(['success' => false, 'message' => 'Hasło musi mieć co najmniej 4 znaki']);
            exit;
        }
        
        // Sprawdź czy użytkownik już istnieje
        $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Użytkownik o takim loginie już istnieje']);
            exit;
        }
        
        // Utwórz użytkownika
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
        $stmt->execute([$username, $passwordHash]);
        
        echo json_encode(['success' => true, 'message' => 'Rejestracja pomyślna']);
        
    } elseif ($action === 'login') {
        // Logowanie
        if (!isset($input['username']) || !isset($input['password'])) {
            echo json_encode(['success' => false, 'message' => 'Brak wymaganych pól']);
            exit;
        }
        
        $username = trim($input['username']);
        $password = $input['password'];
        
        // Znajdź użytkownika
        $stmt = $db->prepare("SELECT id, username, password_hash FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            echo json_encode(['success' => false, 'message' => 'Nieprawidłowy login lub hasło']);
            exit;
        }
        
        // Utwórz sesję
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        
        // Ustaw cookie na 30 dni
        setcookie('shop_bilans_user', $user['id'], time() + (30 * 24 * 60 * 60), '/');
        
        echo json_encode([
            'success' => true,
            'message' => 'Logowanie pomyślne',
            'user' => [
                'id' => $user['id'],
                'username' => $user['username']
            ]
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Nieznana akcja']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
?>
