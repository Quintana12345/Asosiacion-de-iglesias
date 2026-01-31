<?php
require_once '../includes/config.php';
requireAdmin();

header('Content-Type: application/json');

$db = getDB();
$response = ['success' => false, 'message' => ''];

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'delete':
            $id = $_GET['id'] ?? 0;
            
            if ($id) {
                $stmt = $db->prepare("UPDATE iglesias SET activo = 0 WHERE id = ?");
                $stmt->execute([$id]);
                
                $response['success'] = true;
                $response['message'] = 'Iglesia eliminada correctamente';
            }
            break;
            
        default:
            throw new Exception("Acción no válida");
    }
    
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
?>