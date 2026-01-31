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
                // Verificar que no tenga sectores asociados
                $stmt = $db->prepare("SELECT COUNT(*) FROM sectores WHERE zona_id = ? AND activo = 1");
                $stmt->execute([$id]);
                if ($stmt->fetchColumn() > 0) {
                    throw new Exception("No se puede eliminar la zona porque tiene sectores asociados");
                }
                
                $stmt = $db->prepare("UPDATE zonas SET activo = 0 WHERE id = ?");
                $stmt->execute([$id]);
                
                $response['success'] = true;
                $response['message'] = 'Zona eliminada correctamente';
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