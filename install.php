<?php
// Archivo de instalación del sistema
session_start();

// Configuración básica
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');

$error = '';
$success = '';

if ($_POST) {
    try {
        $db_name = 'asociacion_iglesias';
        
        // Crear conexión
        $conn = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Crear base de datos si no existe
        $conn->exec("CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $conn->exec("USE $db_name");
        
        // Leer y ejecutar el archivo SQL
        $sql_file = file_get_contents('database/asociacion_iglesias.sql');
        $conn->exec($sql_file);
        
        $success = "Sistema instalado correctamente. Puedes iniciar sesión con:<br>
                   <strong>Email:</strong> admin@asociacioniglesias.com<br>
                   <strong>Contraseña:</strong> password";
        
    } catch(PDOException $e) {
        $error = "Error durante la instalación: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Asociación de Iglesias</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        .alert-error {
            background: #fee;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .alert-success {
            background: #eff8f0;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .btn {
            background: #2c5aa0;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Instalación del Sistema</h1>
        <p>Asociación de Iglesias de Guerrero</p>
        
        <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
            <p><a href="admin/index.php" class="btn">Ir al Login</a></p>
        </div>
        <?php else: ?>
        <p>Este instalador creará la base de datos y las tablas necesarias para el sistema.</p>
        <p><strong>Requisitos:</strong></p>
        <ul>
            <li>Servidor MySQL ejecutándose</li>
            <li>Credenciales de acceso a MySQL</li>
            <li>PHP 7.4 o superior</li>
        </ul>
        
        <form method="POST">
            <button type="submit" class="btn">Instalar Sistema</button>
        </form>
        <?php endif; ?>
    </div>
</body>
</html>