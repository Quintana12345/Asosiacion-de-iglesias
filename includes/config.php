<?php
// Configuración del sistema
session_start();

// Configuración de la base de datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'asociacion_iglesias');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuración del sitio
define('SITE_NAME', 'Asociación de Iglesias de Guerrero');
define('SITE_URL', 'http://localhost/asociacion_iglesias');
define('UPLOAD_PATH', dirname(__FILE__) . '/../assets/uploads/');

// Configuración de correo
define('EMAIL_FROM', 'noreply@asociacioniglesias.com');
define('EMAIL_NAME', 'Asociación de Iglesias');

// Configuración de subida de archivos
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);

// Manejo de errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir otras configuraciones
require_once 'database.php';
require_once 'functions.php';
require_once 'auth.php';

// Función para verificar si estamos en desarrollo
function is_development() {
    return $_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1';
}
?>