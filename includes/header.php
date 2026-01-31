<?php
// Este archivo proporciona la estructura común del dashboard para todas las páginas del admin
require_once 'config.php';
requireLogin();
if (!isset($page_title)) {
    $page_title = 'Asociación de Iglesias - Guerrero';
}

// Definir la ruta base
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/asociacion_iglesias';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <a href="<?php echo $base_url; ?>/index.php">
                    <i class="fas fa-church"></i>
                    <span>Asociación de Iglesias</span>
                </a>
            </div>
            
            <div class="nav-menu">
                <a href="<?php echo $base_url; ?>/index.php" class="nav-link">Inicio</a>
                <a href="<?php echo $base_url; ?>/blog/index.php" class="nav-link">Blog</a>
                <a href="#about" class="nav-link">Nosotros</a>
                <a href="#contact" class="nav-link">Contacto</a>
                
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="<?php echo $base_url; ?>/admin/dashboard.php" class="nav-link admin-btn">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="?logout=true" class="nav-link logout-btn">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                <?php else: ?>
                    <a href="<?php echo $base_url; ?>/admin/index.php" class="nav-link login-btn">
                        <i class="fas fa-sign-in-alt"></i> Iniciar Sesión
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="main-content">