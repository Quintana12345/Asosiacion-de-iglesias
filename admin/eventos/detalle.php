<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
requireAdmin();

$db = getDB();

// Obtener el ID del evento
$id = $_GET['id'] ?? 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

// Obtener datos del evento con JOINS para mostrar nombres
$query = "SELECT e.*, 
          c.nombre as comision_nombre,
          z.nombre as zona_nombre,
          s.nombre as sector_nombre,
          u.nombre_completo as creador_nombre,
          DATE_FORMAT(e.fecha_inicio, '%d/%m/%Y %H:%i') as fecha_inicio_formatted,
          DATE_FORMAT(e.fecha_fin, '%d/%m/%Y %H:%i') as fecha_fin_formatted,
          DATE_FORMAT(e.fecha_creacion, '%d/%m/%Y %H:%i') as fecha_creacion_formatted
          FROM eventos e
          LEFT JOIN comisiones c ON e.comision_id = c.id
          LEFT JOIN zonas z ON e.zona_id = z.id
          LEFT JOIN sectores s ON e.sector_id = s.id
          LEFT JOIN usuarios u ON e.creador_id = u.id
          WHERE e.id = ?";
          
$stmt = $db->prepare($query);
$stmt->execute([$id]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
    $_SESSION['error_msg'] = 'Evento no encontrado';
    header('Location: index.php');
    exit;
}

// Determinar estado visual
$status_class = '';
$status_text = '';

switch ($evento['estado']) {
    case 'cancelado':
        $status_class = 'status-cancelado';
        $status_text = 'Cancelado';
        break;
    case 'completado':
        $status_class = 'status-completado';
        $status_text = 'Completado';
        break;
    case 'activo':
    default:
        $now = time();
        $fecha_inicio = strtotime($evento['fecha_inicio']);
        $fecha_fin = strtotime($evento['fecha_fin']);
        
        if ($fecha_inicio > $now && ($fecha_inicio - $now) <= 86400) { // 24 horas
            $status_class = 'status-proximo';
            $status_text = 'Próximo';
        } elseif ($fecha_inicio <= $now && $fecha_fin >= $now) {
            $status_class = 'status-en-curso';
            $status_text = 'En Curso';
        } elseif ($fecha_fin < $now) {
            $status_class = 'status-finalizado';
            $status_text = 'Finalizado';
        } else {
            $status_class = 'status-activo';
            $status_text = 'Activo';
        }
        break;
}

$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle del Evento - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        <?php include '../admin-styles.css'; ?>
        
        .content-container {
            padding: 2rem;
            max-width: 1000px;
            margin: 0 auto;
        }
        
        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }
        
        .page-header h1 {
            margin: 0;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.75rem;
        }
        
        .page-header p {
            color: var(--gray);
            margin: 0.5rem 0 0 0;
            font-size: 0.95rem;
        }
        
        .detail-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 2.5rem;
            box-shadow: var(--box-shadow);
        }
        
        .event-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            margin: -2.5rem -2.5rem 2.5rem -2.5rem;
            position: relative;
        }
        
        .event-status {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-proximo {
            background: #27ae60;
            color: white;
        }
        
        .status-en-curso {
            background: #f39c12;
            color: white;
        }
        
        .status-finalizado {
            background: #e74c3c;
            color: white;
        }
        
        .status-activo {
            background: #3498db;
            color: white;
        }
        
        .status-cancelado {
            background: #95a5a6;
            color: white;
        }
        
        .status-completado {
            background: #27ae60;
            color: white;
        }
        
        .event-title {
            margin: 0 0 1rem 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .event-date {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .detail-section {
            margin-bottom: 2.5rem;
        }
        
        .detail-section h3 {
            margin: 0 0 1rem 0;
            color: var(--primary);
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .detail-content {
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border-left: 4px solid var(--primary);
        }
        
        .detail-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .detail-item {
            margin-bottom: 0.75rem;
        }
        
        .detail-label {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
            display: block;
        }
        
        .detail-value {
            color: var(--text);
            line-height: 1.5;
        }
        
        .detail-value.empty {
            color: var(--gray);
            font-style: italic;
        }
        
        .description-content {
            white-space: pre-wrap;
            line-height: 1.6;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-light);
        }
        
        .alert {
            padding: 1.25rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .meta-info {
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-top: 2rem;
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .meta-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .content-container {
                padding: 1rem;
            }
            
            .detail-container {
                padding: 1.5rem;
            }
            
            .event-header {
                margin: -1.5rem -1.5rem 1.5rem -1.5rem;
                padding: 1.5rem;
            }
            
            .event-title {
                font-size: 1.5rem;
            }
            
            .detail-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .action-buttons .btn {
                width: 100%;
                text-align: center;
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
                <h1>Detalle del Evento</h1>
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
            <?php if ($success_msg): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>
            
            <div class="page-header">
                <h1>
                    <i class="fas fa-calendar-alt"></i>
                    Información del Evento
                </h1>
                <p>Visualice todos los detalles del evento seleccionado</p>
            </div>
            
            <div class="detail-container">
                <div class="event-header">
                    <span class="event-status <?php echo $status_class; ?>">
                        <?php echo $status_text; ?>
                    </span>
                    <h1 class="event-title"><?php echo htmlspecialchars($evento['titulo']); ?></h1>
                    <div class="event-date">
                        <i class="far fa-calendar-alt"></i>
                        <?php echo $evento['fecha_inicio_formatted']; ?> - <?php echo $evento['fecha_fin_formatted']; ?>
                    </div>
                </div>
                
                <?php if ($evento['descripcion']): ?>
                <div class="detail-section">
                    <h3><i class="fas fa-align-left"></i> Descripción</h3>
                    <div class="detail-content">
                        <div class="description-content">
                            <?php echo nl2br(htmlspecialchars($evento['descripcion'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="detail-section">
                    <h3><i class="fas fa-info-circle"></i> Información General</h3>
                    <div class="detail-content">
                        <div class="detail-row">
                            <div class="detail-item">
                                <span class="detail-label">Ubicación:</span>
                                <span class="detail-value <?php echo empty($evento['ubicacion']) ? 'empty' : ''; ?>">
                                    <?php echo !empty($evento['ubicacion']) ? htmlspecialchars($evento['ubicacion']) : 'No especificada'; ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Comisión Responsable:</span>
                                <span class="detail-value <?php echo empty($evento['comision_nombre']) ? 'empty' : ''; ?>">
                                    <?php echo !empty($evento['comision_nombre']) ? htmlspecialchars($evento['comision_nombre']) : 'No asignada'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-item">
                                <span class="detail-label">Zona:</span>
                                <span class="detail-value <?php echo empty($evento['zona_nombre']) ? 'empty' : ''; ?>">
                                    <?php echo !empty($evento['zona_nombre']) ? htmlspecialchars($evento['zona_nombre']) : 'No asignada'; ?>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Sector:</span>
                                <span class="detail-value <?php echo empty($evento['sector_nombre']) ? 'empty' : ''; ?>">
                                    <?php echo !empty($evento['sector_nombre']) ? htmlspecialchars($evento['sector_nombre']) : 'No asignado'; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h3><i class="fas fa-cog"></i> Configuración del Evento</h3>
                    <div class="detail-content">
                        <div class="detail-row">
                            <div class="detail-item">
                                <span class="detail-label">Estado:</span>
                                <span class="detail-value">
                                    <span class="event-status <?php echo $status_class; ?>" style="padding: 0.25rem 0.75rem; font-size: 0.85rem;">
                                        <?php echo htmlspecialchars($status_text); ?>
                                    </span>
                                    <small style="color: var(--gray); margin-left: 0.5rem;">
                                        (<?php echo htmlspecialchars($evento['estado']); ?>)
                                    </small>
                                </span>
                            </div>
                            
                            <div class="detail-item">
                                <span class="detail-label">Creado por:</span>
                                <span class="detail-value">
                                    <?php echo htmlspecialchars($evento['creador_nombre'] ?? 'Sistema'); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="meta-info">
                    <div class="meta-row">
                        <span>ID del Evento:</span>
                        <span><strong><?php echo $evento['id']; ?></strong></span>
                    </div>
                    <div class="meta-row">
                        <span>Fecha de creación:</span>
                        <span><?php echo $evento['fecha_creacion_formatted']; ?></span>
                    </div>
                    <?php if (isset($evento['fecha_actualizacion'])): ?>
                    <div class="meta-row">
                        <span>Última actualización:</span>
                        <span><?php echo date('d/m/Y H:i', strtotime($evento['fecha_actualizacion'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="action-buttons">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver al Listado
                    </a>
                    <a href="editar.php?id=<?php echo $evento['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar Evento
                    </a>
                    <form method="POST" action="index.php" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar este evento? Esta acción no se puede deshacer.')">
                        <input type="hidden" name="action" value="eliminar">
                        <input type="hidden" name="id" value="<?php echo $evento['id']; ?>">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-trash"></i> Eliminar Evento
                        </button>
                    </form>
                </div>
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
        
        // Confirmar eliminación
        document.querySelector('form[onsubmit]').onsubmit = function() {
            return confirm('¿Está seguro de eliminar este evento? Esta acción no se puede deshacer.');
        };
    </script>
</body>
</html>