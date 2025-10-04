<?php
require_once 'config.php';

if(!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Hozzáférés megtagadva');
}

$action = $_GET['action'] ?? '';

if($action == 'get_characters') {
    $player_id = $_GET['player_id'] ?? '';
    
    if($player_id) {
        try {
            $stmt = $pdo->prepare("SELECT ".CHARACTERS_ID." as id, ".CHARACTERS_NAME." as name FROM characters WHERE ".CHARACTERS_ACCOUNT." = ?");
            $stmt->execute([$player_id]);
            $characters = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'characters' => $characters]);
        } catch(Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Hiányzó player_id']);
    }
} else {
    header('HTTP/1.1 400 Bad Request');
    echo 'Ismeretlen művelet';
}
?>