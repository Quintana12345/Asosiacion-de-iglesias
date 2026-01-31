<?php
require_once '../includes/config.php';
requireLogin();

// Si no es administrador, redirigir según su cargo
if (!isAdmin()) {
    if (isJefeZona()) {
        // Redirigir a gestión de su zona
        $_SESSION['info'] = 'Bienvenido Jefe de Zona';
    } elseif (isJefeSector()) {
        // Redirigir a gestión de su sector
        $_SESSION['info'] = 'Bienvenido Jefe de Sector';
    } else {
        $_SESSION['info'] = 'Bienvenido Pastor';
    }
}

$db = getDB();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 280px;
            --sidebar-collapsed: 80px;
            --header-height: 70px;
            --primary: #2c5aa0;
            --primary-dark: #1e3d72;
            --secondary: #e74c3c;
            --accent: #f39c12;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
            --light: #f8f9fa;
            --dark: #343a40;
            --gray: #6c757d;
            --gray-light: #e9ecef;
            --border-radius: 12px;
            --box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
            min-height: 100vh;
            display: flex;
        }

        /* Sidebar Styles */
        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            flex-direction: column;
            transition: var(--transition);
            z-index: 1000;
            box-shadow: 4px 0 20px rgba(0,0,0,0.15);
        }

        .sidebar-header {
            padding: 1.5rem 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-asociacion {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            object-fit: contain;
            background: white;
            padding: 5px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border: 1px solid rgba(255,255,255,0.3);
        }

        .logo-text {
            flex: 1;
        }

        .logo-text h2 {
            color: white;
            font-size: 1.3rem;
            margin-bottom: 0.25rem;
            font-weight: 700;
        }

        .logo-text p {
            color: rgba(255,255,255,0.7);
            font-size: 0.8rem;
        }

        .sidebar-nav {
            flex: 1;
            padding: 1.5rem 0;
        }

        .nav-section {
            margin-bottom: 2rem;
        }

        .nav-section h3 {
            padding: 0 1.5rem 0.75rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            color: rgba(255,255,255,0.5);
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .nav-item {
            margin: 0.25rem 0;
        }

        .nav-item a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: var(--transition);
            border-left: 3px solid transparent;
            margin: 0 0.5rem;
            border-radius: 8px;
        }

        .nav-item a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .nav-item.active a {
            background: rgba(255,255,255,0.15);
            color: white;
            border-left-color: var(--accent);
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }

        .nav-item i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .sidebar-footer {
            padding: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: rgba(255,255,255,0.1);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
            font-weight: 500;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .logout-btn:hover {
            background: var(--danger);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
        }

        /* Main Content */
        .main-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            transition: var(--transition);
        }

        /* Content Header */
        .content-header {
            height: var(--header-height);
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
            box-shadow: 0 2px 20px rgba(0,0,0,0.08);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--gray);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            display: none;
        }

        .sidebar-toggle:hover {
            background: var(--gray-light);
            color: var(--primary);
        }

        .content-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: var(--transition);
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border: 1px solid var(--gray-light);
        }

        .user-menu:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .user-menu-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .user-name {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }

        .user-role {
            font-size: 0.75rem;
            color: var(--primary);
            text-transform: capitalize;
            font-weight: 500;
            background: rgba(44, 90, 160, 0.1);
            padding: 0.2rem 0.5rem;
            border-radius: 20px;
        }

        .header-logo {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: contain;
            background: white;
            padding: 3px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid var(--gray-light);
        }

        .user-avatar {
            font-size: 2rem;
            color: var(--primary);
        }

        /* Content Container */
        .content-container {
            flex: 1;
            padding: 2rem;
            overflow-y: auto;
            background: transparent;
        }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--box-shadow);
            position: relative;
            overflow: hidden;
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 200px;
            height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }

        .welcome-banner h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .welcome-banner p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: var(--transition);
            border: 1px solid rgba(255,255,255,0.2);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: white;
            box-shadow: 0 8px 20px rgba(44, 90, 160, 0.3);
        }

        .stat-info {
            flex: 1;
        }

        .stat-info h3 {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 0.5rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            line-height: 1;
            background: linear-gradient(135deg, var(--dark) 0%, var(--primary) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-trend {
            font-size: 0.8rem;
            color: var(--success);
            font-weight: 600;
            margin-top: 0.5rem;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }

        .content-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: var(--transition);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        }

        .card-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .card-header h3 {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-link {
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            background: rgba(44, 90, 160, 0.1);
        }

        .btn-link:hover {
            background: var(--primary);
            color: white;
            transform: translateX(3px);
        }

        .card-body {
            padding: 2rem;
        }

        /* Activity List */
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.2rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .activity-item:hover {
            background: var(--gray-light);
            border-color: var(--gray-light);
            transform: translateX(5px);
        }

        .activity-icon {
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.1rem;
            box-shadow: 0 4px 15px rgba(44, 90, 160, 0.3);
        }

        .activity-content {
            flex: 1;
        }

        .activity-content p {
            margin: 0;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
        }

        .activity-time {
            font-size: 0.8rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }

        /* Event List */
        .event-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .event-item {
            display: flex;
            align-items: center;
            gap: 1.2rem;
            padding: 1.2rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .event-item:hover {
            background: var(--gray-light);
            border-color: var(--gray-light);
            transform: translateX(5px);
        }

        .event-date {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--accent) 0%, #e67e22 100%);
            color: white;
            border-radius: var(--border-radius);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(243, 156, 18, 0.3);
        }

        .event-day {
            font-size: 1.4rem;
            line-height: 1;
        }

        .event-month {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .event-info {
            flex: 1;
        }

        .event-info h4 {
            margin: 0 0 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            color: var(--dark);
        }

        .event-info p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 3rem 2rem;
            color: var(--gray);
        }

        .empty-state i {
            margin-bottom: 1rem;
            color: var(--gray-light);
            font-size: 3rem;
            opacity: 0.5;
        }

        .empty-state p {
            margin: 0;
            font-size: 1rem;
            color: var(--gray);
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-btn {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: white;
            border-radius: var(--border-radius);
            text-decoration: none;
            color: var(--dark);
            transition: var(--transition);
            box-shadow: var(--box-shadow);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .action-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
            color: var(--primary);
        }

        .action-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .action-text {
            font-weight: 600;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                position: fixed;
                height: 100vh;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }

            .sidebar-toggle {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .content-header {
                padding: 0 1rem;
            }
            
            .content-container {
                padding: 1rem;
            }

            .welcome-banner {
                padding: 1.5rem;
            }

            .welcome-banner h2 {
                font-size: 1.5rem;
            }

            .stat-card {
                padding: 1.5rem;
            }

            .stat-icon {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }

            .stat-number {
                font-size: 2rem;
            }

            .quick-actions {
                grid-template-columns: 1fr 1fr;
            }

            .logo-asociacion {
                width: 40px;
                height: 40px;
            }
            
            .header-logo {
                width: 35px;
                height: 35px;
            }
        }

        @media (max-width: 480px) {
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .stat-card {
                flex-direction: column;
                text-align: center;
            }
            
            .user-menu .user-info {
                display: none;
            }

            .logo-text h2 {
                font-size: 1.1rem;
            }
            
            .header-logo {
                display: none;
            }
        }
    </style>
</head>
<body class="dashboard-body">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <img src="../assets/img/Logo-SMS-GRO.jpeg" alt="Logo ICIAR" class="logo-asociacion">
                <div class="logo-text">
                    <h2><?php echo SITE_NAME; ?></h2>
                    <p>Panel de Administración</p>
                </div>
            </div>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <h3>Principal</h3>
                <ul>
                    <li class="nav-item active">
                        <a href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <?php if (isAdmin()): ?>
            <div class="nav-section">
                <h3>Gestión</h3>
                <ul>
                    <li class="nav-item">
                        <a href="usuarios/">
                            <i class="fas fa-users"></i>
                            <span>Usuarios</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="zonas/">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Zonas</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="sectores/">
                            <i class="fas fa-layer-group"></i>
                            <span>Sectores</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="iglesias/">
                            <i class="fas fa-church"></i>
                            <span>Iglesias</span>
                        </a>
                    </li>
                </ul>
            </div>
            <?php endif; ?>
            
            <div class="nav-section">
                <h3>Contenido</h3>
                <ul>
                    <li class="nav-item">
                        <a href="comisiones/">
                            <i class="fas fa-tasks"></i>
                            <span>Comisiones</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="blog/">
                            <i class="fas fa-blog"></i>
                            <span>Blog</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="eventos/">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Eventos</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <a href="../includes/logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span>Cerrar Sesión</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Header -->
        <header class="content-header">
            <div class="header-left">
                <button class="sidebar-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>Dashboard</h1>
            </div>
            <div class="header-right">
                <div class="user-menu">
                    <div class="user-menu-left">
                        <img src="../assets/img/Logo-ICIAR.jpeg" alt="Logo SMS" class="header-logo">
                        <div class="user-info">
                            <span class="user-name"><?php echo $_SESSION['user_nombre']; ?></span>
                            <span class="user-role"><?php echo $_SESSION['user_cargo']; ?></span>
                        </div>
                    </div>
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Container -->
        <div class="content-container">
            <!-- Welcome Banner -->
            <div class="welcome-banner">
                <h2>¡Bienvenido, <?php echo explode(' ', $_SESSION['user_nombre'])[0]; ?>! 👋</h2>
                <p>Gestiona y supervisa todas las actividades de la asociación desde un solo lugar.</p>
            </div>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Usuarios</h3>
                        <span class="stat-number"><?php
                            $stmt = $db->query("SELECT COUNT(*) FROM usuarios WHERE activo = 1");
                            echo $stmt->fetchColumn();
                        ?></span>
                        <div class="stat-trend">
                            <i class="fas fa-arrow-up"></i> Activos en el sistema
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-church"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Iglesias</h3>
                        <span class="stat-number"><?php
                            $stmt = $db->query("SELECT COUNT(*) FROM iglesias WHERE activo = 1");
                            echo $stmt->fetchColumn();
                        ?></span>
                        <div class="stat-trend">
                            <i class="fas fa-map-marker-alt"></i> Registradas
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Zonas Activas</h3>
                        <span class="stat-number"><?php
                            $stmt = $db->query("SELECT COUNT(*) FROM zonas WHERE activo = 1");
                            echo $stmt->fetchColumn();
                        ?></span>
                        <div class="stat-trend">
                            <i class="fas fa-layer-group"></i> En operación
                        </div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-blog"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Publicaciones</h3>
                        <span class="stat-number"><?php
                            $stmt = $db->query("SELECT COUNT(*) FROM blog WHERE estado = 'publicado'");
                            echo $stmt->fetchColumn();
                        ?></span>
                        <div class="stat-trend">
                            <i class="fas fa-newspaper"></i> En el blog
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions - SOLO LOS 4 QUE NECESITAS -->
            <div class="quick-actions">
                <?php if (isAdmin()): ?>
                <a href="usuarios/index.php?action=nuevo" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="action-text">Agregar Usuario</div>
                </a>
                <?php endif; ?>
                
                <a href="blog/crear.php" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div class="action-text">Nueva Publicación</div>
                </a>
                
                <a href="eventos/crear.php" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <div class="action-text">Crear Evento</div>
                </a>
                
                <?php if (isAdmin()): ?>
                <a href="iglesias/index.php?action=nuevo" class="action-btn">
                    <div class="action-icon">
                        <i class="fas fa-church"></i>
                    </div>
                    <div class="action-text">Registrar Iglesia</div>
                </a>
                <?php endif; ?>
            </div>

            <!-- Recent Activity -->
            <div class="content-grid">
                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-history"></i> Actividad Reciente</h3>
                        <a href="blog/" class="btn-link">
                            Ver todo <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="activity-list">
                            <?php
                            $query = "SELECT * FROM blog 
                                     WHERE estado = 'publicado' 
                                     ORDER BY fecha_publicacion DESC 
                                     LIMIT 5";
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (empty($recent_posts)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-file-alt"></i>
                                    <p>No hay publicaciones recientes</p>
                                </div>
                            <?php else:
                                foreach ($recent_posts as $post):
                            ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-file-alt"></i>
                                </div>
                                <div class="activity-content">
                                    <p><strong><?php echo $post['titulo']; ?></strong></p>
                                    <span class="activity-time">
                                        <i class="fas fa-calendar"></i> 
                                        <?php echo date('d/m/Y', strtotime($post['fecha_publicacion'])); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>

                <div class="content-card">
                    <div class="card-header">
                        <h3><i class="fas fa-calendar-check"></i> Próximos Eventos</h3>
                        <a href="eventos/" class="btn-link">
                            Ver todos <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="event-list">
                            <?php
                            $query = "SELECT * FROM eventos 
                                     WHERE estado = 'activo' AND fecha_inicio >= NOW() 
                                     ORDER BY fecha_inicio ASC 
                                     LIMIT 5";
                            $stmt = $db->prepare($query);
                            $stmt->execute();
                            $upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            
                            if (empty($upcoming_events)): ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-alt"></i>
                                    <p>No hay eventos próximos</p>
                                </div>
                            <?php else:
                                foreach ($upcoming_events as $event):
                            ?>
                            <div class="event-item">
                                <div class="event-date">
                                    <span class="event-day"><?php echo date('d', strtotime($event['fecha_inicio'])); ?></span>
                                    <span class="event-month"><?php echo date('M', strtotime($event['fecha_inicio'])); ?></span>
                                </div>
                                <div class="event-info">
                                    <h4><?php echo $event['titulo']; ?></h4>
                                    <p>
                                        <i class="fas fa-map-marker-alt"></i>
                                        <?php echo $event['ubicacion']; ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // JavaScript para el sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 1024) {
                    if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                        sidebar.classList.remove('active');
                    }
                }
            });

            // Efectos de animación para las tarjetas
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                card.style.animationDelay = `${index * 0.1}s`;
                card.style.animation = 'fadeInUp 0.6s ease forwards';
            });

            // Agregar estilos de animación
            const style = document.createElement('style');
            style.textContent = `
                @keyframes fadeInUp {
                    from {
                        opacity: 0;
                        transform: translateY(30px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }
                
                .stat-card {
                    opacity: 0;
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>