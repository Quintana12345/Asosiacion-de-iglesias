<?php
// Configuración de rutas base
define('BASE_PATH', dirname(dirname(__FILE__)));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('ADMIN_PATH', BASE_PATH . '/admin');
define('ASSETS_PATH', BASE_PATH . '/assets');
define('API_PATH', BASE_PATH . '/api');

// Función para incluir archivos de forma segura
function require_safe($path) {
    if (file_exists($path)) {
        require_once $path;
    } else {
        throw new Exception("Archivo no encontrado: " . $path);
    }
}

// Función para obtener URL base
function base_url($path = '') {
    return SITE_URL . '/' . ltrim($path, '/');
}
?>