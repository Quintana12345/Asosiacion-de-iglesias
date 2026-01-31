<?php
// Funciones utilitarias del sistema

function sanitizeInput($data) {
    if (!isset($data)) return '';
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_cargo']) && $_SESSION['user_cargo'] === 'administrador';
}

function isJefeZona() {
    return isset($_SESSION['user_cargo']) && $_SESSION['user_cargo'] === 'jefe_zona';
}

function isJefeSector() {
    return isset($_SESSION['user_cargo']) && $_SESSION['user_cargo'] === 'jefe_sector';
}

function formatDate($date, $format = 'd/m/Y') {
    if (!$date) return '';
    $dateTime = new DateTime($date);
    return $dateTime->format($format);
}

function uploadFile($file, $directory) {
    $target_dir = UPLOAD_PATH . $directory . '/';
    
    // Crear directorio si no existe
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $file_name = uniqid() . '_' . time() . '.' . $file_extension;
    $target_file = $target_dir . $file_name;
    
    // Validar tipo de archivo
    if (!in_array($file_extension, ALLOWED_IMAGE_TYPES)) {
        return ['success' => false, 'message' => 'Tipo de archivo no permitido'];
    }
    
    // Validar tamaño
    if ($file["size"] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Archivo demasiado grande'];
    }
    
    // Mover archivo
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'file_name' => $file_name];
    } else {
        return ['success' => false, 'message' => 'Error al subir archivo'];
    }
}

function deleteFile($filename, $directory) {
    $file_path = UPLOAD_PATH . $directory . '/' . $filename;
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    return false;
}

function getZonas() {
    $db = getDB();
    $query = "SELECT * FROM zonas WHERE activo = 1 ORDER BY nombre";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getComisiones() {
    $db = getDB();
    $query = "SELECT * FROM comisiones WHERE activo = 1 ORDER BY nombre";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getCategoriasBlog() {
    $db = getDB();
    $query = "SELECT * FROM categorias_blog WHERE activo = 1 ORDER BY nombre";
    $stmt = $db->prepare($query);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Función para verificar si la base de datos existe
function checkDatabase() {
    try {
        $db = getDB();
        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>