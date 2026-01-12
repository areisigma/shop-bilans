<?php
session_start();
header('Content-Type: application/json');

require_once 'config.php';

// Sprawdź czy użytkownik jest zalogowany
if (!isset($_SESSION['user_id'])) {
    // Spróbuj przywrócić sesję z cookie
    if (isset($_COOKIE['shop_bilans_user'])) {
        $db = getDbConnection();
        $stmt = $db->prepare("SELECT id, username FROM users WHERE id = ?");
        $stmt->execute([$_COOKIE['shop_bilans_user']]);
        $user = $stmt->fetch();
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
        } else {
            echo json_encode(['success' => false, 'message' => 'Użytkownik niezalogowany', 'redirect' => 'login.php']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Użytkownik niezalogowany', 'redirect' => 'login.php']);
        exit;
    }
}

$userId = $_SESSION['user_id'];

// Obsługa różnych metod HTTP
$method = $_SERVER['REQUEST_METHOD'];

try {
    $db = getDbConnection();
    
    switch ($method) {
        case 'GET':
            // Pobierz wszystkie wpisy (wspólne dla wszystkich użytkowników)
            $stmt = $db->query("SELECT e.id, e.amount, e.description, e.timestamp, u.username 
                               FROM entries e 
                               JOIN users u ON e.user_id = u.id 
                               ORDER BY e.timestamp DESC");
            $entries = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $entries]);
            break;
        
        case 'POST':
            // Dodaj nowy wpis
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['amount']) || !isset($input['description'])) {
                echo json_encode(['success' => false, 'message' => 'Brak wymaganych pól']);
                exit;
            }
            
            $id = uniqid();
            $amount = floatval($input['amount']);
            $description = $input['description'];
            $timestamp = date('Y-m-d H:i:s');
            
            $stmt = $db->prepare("INSERT INTO entries (id, user_id, amount, description, timestamp) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$id, $userId, $amount, $description, $timestamp]);
            
            $newEntry = [
                'id' => $id,
                'amount' => $amount,
                'description' => $description,
                'timestamp' => $timestamp
            ];
            
            echo json_encode(['success' => true, 'data' => $newEntry]);
            break;
        
        case 'DELETE':
            // Usuń wpis (tylko własny)
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($input['id'])) {
                echo json_encode(['success' => false, 'message' => 'Brak ID wpisu']);
                exit;
            }
            
            // Sprawdź czy wpis należy do użytkownika
            $stmt = $db->prepare("SELECT user_id FROM entries WHERE id = ?");
            $stmt->execute([$input['id']]);
            $entry = $stmt->fetch();
            
            if (!$entry) {
                echo json_encode(['success' => false, 'message' => 'Wpis nie istnieje']);
                exit;
            }
            
            if ($entry['user_id'] != $userId) {
                echo json_encode(['success' => false, 'message' => 'Nie możesz usunąć wpisu innego użytkownika']);
                exit;
            }
            
            $stmt = $db->prepare("DELETE FROM entries WHERE id = ?");
            $stmt->execute([$input['id']]);
            
            echo json_encode(['success' => true]);
            break;
        
        default:
            echo json_encode(['success' => false, 'message' => 'Nieobsługiwana metoda']);
            break;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych: ' . $e->getMessage()]);
}
?>
