<?php
require_once '../includes/config.php';
requireLogin();

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No tienes permisos para realizar esta acción']);
    exit();
}

header('Content-Type: application/json');

$db = getDB();
$response = ['success' => false, 'message' => ''];

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'delete':
            $id = $_GET['id'] ?? 0;
            
            if ($id) {
                // No permitir eliminar el propio usuario
                if ($id == $_SESSION['user_id']) {
                    throw new Exception("No puedes eliminar tu propio usuario");
                }
                
                // Verificar que el usuario existe
                $stmt = $db->prepare("SELECT nombre_completo FROM usuarios WHERE id = ? AND activo = 1");
                $stmt->execute([$id]);
                $usuario = $stmt->fetch();
                
                if (!$usuario) {
                    throw new Exception("El usuario no existe o ya fue eliminado");
                }
                
                $stmt = $db->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
                $stmt->execute([$id]);
                
                $response['success'] = true;
                $response['message'] = 'Usuario eliminado correctamente';
                $response['usuario'] = $usuario['nombre_completo'];
            } else {
                throw new Exception("ID de usuario no válido");
            }
            break;
            
        case 'toggle_status':
            $id = $_GET['id'] ?? 0;
            $status = $_GET['status'] ?? 0;
            
            if ($id) {
                // No permitir desactivar el propio usuario
                if ($id == $_SESSION['user_id'] && !$status) {
                    throw new Exception("No puedes desactivar tu propio usuario");
                }
                
                $stmt = $db->prepare("UPDATE usuarios SET activo = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
                
                $response['success'] = true;
                $response['message'] = 'Estado actualizado correctamente';
            }
            break;
            
        case 'check_email':
            $email = $_GET['email'] ?? '';
            $exclude_id = $_GET['exclude_id'] ?? 0;
            
            if ($email) {
                $query = "SELECT id FROM usuarios WHERE email = ?";
                $params = [$email];
                
                if ($exclude_id) {
                    $query .= " AND id != ?";
                    $params[] = $exclude_id;
                }
                
                $stmt = $db->prepare($query);
                $stmt->execute($params);
                
                $response['success'] = true;
                $response['exists'] = $stmt->fetch() !== false;
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