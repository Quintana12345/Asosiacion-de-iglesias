<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    
    if ($id) {
        try {
            // Obtener información del artículo para eliminar la imagen
            $stmt = $db->prepare("SELECT imagen_principal FROM blog WHERE id = ?");
            $stmt->execute([$id]);
            $post = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Eliminar imagen si existe
            if ($post && $post['imagen_principal']) {
                $image_path = '../../assets/uploads/blog/' . $post['imagen_principal'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // Eliminar artículo
            $stmt = $db->prepare("DELETE FROM blog WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success_msg'] = 'Artículo eliminado exitosamente';
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = 'Error al eliminar el artículo: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error_msg'] = 'ID de artículo no válido';
    }
}

header('Location: index.php');
exit;