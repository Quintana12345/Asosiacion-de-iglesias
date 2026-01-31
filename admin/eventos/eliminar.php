<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
requireAdmin();

$db = getDB();

$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: index.php');
    exit;
}

// Obtener evento para confirmación
$stmt = $db->prepare("SELECT titulo FROM eventos WHERE id = ?");
$stmt->execute([$id]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $db->prepare("DELETE FROM eventos WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success_msg'] = 'Evento eliminado exitosamente';
        header('Location: index.php');
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = 'Error al eliminar el evento: ' . $e->getMessage();
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Evento - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        <?php include '../admin-styles.css'; ?>
        
        .content-container {
            padding: 2rem;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .confirmation-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--box-shadow);
            text-align: center;
        }
        
        .confirmation-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            margin: 0 auto 1.5rem;
        }
        
        .confirmation-title {
            margin: 0 0 1rem 0;
            color: var(--dark);
            font-size: 1.5rem;
        }
        
        .confirmation-message {
            color: var(--gray);
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        .event-info {
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .event-info h4 {
            margin: 0 0 1rem 0;
            color: var(--primary);
        }
        
        .confirmation-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .content-container {
                padding: 1rem;
            }
            
            .confirmation-actions {
                flex-direction: column;
            }
            
            .confirmation-actions .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body class="dashboard-body">
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-church"></i> <?php echo SITE_NAME; ?></h2>
            <p>Panel de Administración</p>
        </div>
        
        <nav class="sidebar-nav">
            <div class="nav-section">
                <h3>Principal</h3>
                <ul>
                    <li class="nav-item">
                        <a href="../dashboard.php">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="nav-section">
                <h3>Gestión</h3>
                <ul>
                    <li class="nav-item">
                        <a href="../usuarios/">
                            <i class="fas fa-users"></i>
                            <span>Usuarios</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../zonas/">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Zonas</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../sectores/">
                            <i class="fas fa-layer-group"></i>
                            <span>Sectores</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../iglesias/">
                            <i class="fas fa-church"></i>
                            <span>Iglesias</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="nav-section">
                <h3>Contenido</h3>
                <ul>
                    <li class="nav-item">
                        <a href="../comisiones/">
                            <i class="fas fa-tasks"></i>
                            <span>Comisiones</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../blog/">
                            <i class="fas fa-blog"></i>
                            <span>Blog</span>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a href="./">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Eventos</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../blog/categorias/">
                            <i class="fas fa-tags"></i>
                            <span>Categorías Blog</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <a href="../../includes/logout.php" class="logout-btn">
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
                <h1>Eliminar Evento</h1>
            </div>
            <div class="header-right">
                <div class="user-menu">
                    <div class="user-info">
                        <span class="user-name">
                            <?php 
                            if (isset($_SESSION['user_nombre']) && !empty($_SESSION['user_nombre'])) {
                                echo htmlspecialchars($_SESSION['user_nombre']);
                            } elseif (isset($_SESSION['nombre_completo']) && !empty($_SESSION['nombre_completo'])) {
                                echo htmlspecialchars($_SESSION['nombre_completo']);
                            } else {
                                echo 'Administrador';
                            }
                            ?>
                        </span>
                        <span class="user-role">
                            <?php 
                            if (isset($_SESSION['user_cargo']) && !empty($_SESSION['user_cargo'])) {
                                echo htmlspecialchars($_SESSION['user_cargo']);
                            } elseif (isset($_SESSION['cargo']) && !empty($_SESSION['cargo'])) {
                                echo htmlspecialchars($_SESSION['cargo']);
                            } else {
                                echo 'Administrador';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Container -->
        <div class="content-container">
            <div class="confirmation-container">
                <div class="confirmation-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                
                <h2 class="confirmation-title">¿Está seguro de eliminar este evento?</h2>
                
                <p class="confirmation-message">
                    Esta acción eliminará permanentemente el evento y no se podrá recuperar.
                    Asegúrese de que esta es la acción que desea realizar.
                </p>
                
                <div class="event-info">
                    <h4><i class="fas fa-calendar-alt"></i> Evento a eliminar:</h4>
                    <p><strong>ID:</strong> <?php echo $id; ?></p>
                    <p><strong>Título:</strong> <?php echo htmlspecialchars($evento['titulo']); ?></p>
                    <p><strong>Advertencia:</strong> Esta acción no se puede deshacer.</p>
                </div>
                
                <form method="POST" class="confirmation-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Sí, eliminar evento
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }
    </script>
</body>
</html>