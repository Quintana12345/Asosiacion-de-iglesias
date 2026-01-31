<?php
require_once '../../includes/config.php';
requireLogin();
if (!isAdmin()) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    redirect('../dashboard.php');
}

$db = getDB();
$error = '';
$success = '';
$iglesia = null;

// Obtener ID de la iglesia
$id = $_GET['id'] ?? 0;
if (!$id) {
    $_SESSION['error'] = 'No se especificó la iglesia';
    redirect('index.php');
}

try {
    // Obtener información completa de la iglesia
    $stmt = $db->prepare("
        SELECT i.*, 
               s.nombre as sector_nombre, 
               z.nombre as zona_nombre,
               u.nombre_completo as pastor_nombre,
               u.email as pastor_email,
               u.telefono as pastor_telefono
        FROM iglesias i 
        LEFT JOIN sectores s ON i.sector_id = s.id 
        LEFT JOIN zonas z ON s.zona_id = z.id 
        LEFT JOIN usuarios u ON i.pastor_id = u.id 
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $iglesia = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$iglesia) {
        $_SESSION['error'] = 'Iglesia no encontrada';
        redirect('index.php');
    }
    
} catch (Exception $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

// Procesar cambios de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'cambiar_estado') {
            $nuevo_estado = $iglesia['activo'] ? 0 : 1;
            $stmt = $db->prepare("UPDATE iglesias SET activo = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $id]);
            
            $success = $nuevo_estado ? '✅ Iglesia activada exitosamente' : '✅ Iglesia desactivada exitosamente';
            // Recargar datos
            $iglesia['activo'] = $nuevo_estado;
            
        } elseif ($action === 'eliminar_iglesia') {
            $stmt = $db->prepare("DELETE FROM iglesias WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = '✅ Iglesia eliminada exitosamente';
            redirect('index.php');
        }
        
    } catch (Exception $e) {
        $error = '❌ ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles de Iglesia - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        <?php include '../admin-styles.css'; ?>
        
        .details-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .details-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .details-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .iglesia-info {
            margin-top: 0.5rem;
            opacity: 0.9;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-active {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }
        
        .status-inactive {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .details-body {
            padding: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid var(--gray-light);
            text-align: center;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            margin: 0 auto 1rem;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid var(--gray-light);
        }
        
        .info-card h3 {
            color: var(--primary);
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--gray-light);
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }
        
        .info-value {
            color: var(--gray-dark);
            text-align: right;
            max-width: 60%;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 2rem 0 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray-light);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .actions-panel {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding: 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid var(--gray-light);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            margin-bottom: 1.5rem;
        }
        
        .modal-header h3 {
            margin: 0;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
        
        .contact-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.25rem 0;
            color: var(--gray-dark);
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .details-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .actions-panel {
                flex-direction: column;
            }
            
            .iglesia-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .details-body {
                padding: 1rem;
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
                    <li class="nav-item active">
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
                    <li class="nav-item">
                        <a href="../eventos/">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Eventos</span>
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
                <h1>Detalles de Iglesia</h1>
            </div>
            <div class="header-right">
                <div class="user-menu">
                    <div class="user-info">
                        <span class="user-name"><?php echo $_SESSION['user_nombre']; ?></span>
                        <span class="user-role"><?php echo $_SESSION['user_cargo']; ?></span>
                    </div>
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Container -->
        <div class="content-container">
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
            <?php endif; ?>

            <div class="details-container">
                <div class="details-header">
                    <div>
                        <h2><i class="fas fa-church"></i> <?php echo htmlspecialchars($iglesia['nombre']); ?></h2>
                        <div class="iglesia-info">
                            <span><i class="fas fa-hashtag"></i> ID: <?php echo $iglesia['id']; ?></span>
                            <span><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($iglesia['zona_nombre']); ?></span>
                            <span><i class="fas fa-layer-group"></i> <?php echo htmlspecialchars($iglesia['sector_nombre']); ?></span>
                        </div>
                    </div>
                    <span class="status-badge <?php echo $iglesia['activo'] ? 'status-active' : 'status-inactive'; ?>">
                        <i class="fas fa-circle" style="font-size: 0.5rem; vertical-align: middle;"></i>
                        <?php echo $iglesia['activo'] ? 'ACTIVA' : 'INACTIVA'; ?>
                    </span>
                </div>
                
                <div class="details-body">
                    <!-- Estadísticas -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-id-card"></i>
                            </div>
                            <div class="stat-number"><?php echo $iglesia['id']; ?></div>
                            <div class="stat-label">ID</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="stat-number">
                                <?php echo $iglesia['pastor_nombre'] ? 'Sí' : 'No'; ?>
                            </div>
                            <div class="stat-label">Pastor Asignado</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-phone"></i>
                            </div>
                            <div class="stat-number">
                                <?php echo $iglesia['telefono'] ? 'Sí' : 'No'; ?>
                            </div>
                            <div class="stat-label">Teléfono</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-plus"></i>
                            </div>
                            <div class="stat-number">
                                <?php 
                                $fecha = new DateTime($iglesia['fecha_creacion']);
                                echo $fecha->format('Y');
                                ?>
                            </div>
                            <div class="stat-label">Año de Creación</div>
                        </div>
                    </div>

                    <!-- Información de la Iglesia -->
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Información de la Iglesia
                    </h3>
                    
                    <div class="info-grid">
                        <div class="info-card">
                            <h3><i class="fas fa-cog"></i> Detalles</h3>
                            <div class="info-row">
                                <span class="info-label">ID:</span>
                                <span class="info-value"><?php echo $iglesia['id']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Nombre:</span>
                                <span class="info-value"><?php echo htmlspecialchars($iglesia['nombre']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Zona:</span>
                                <span class="info-value"><?php echo htmlspecialchars($iglesia['zona_nombre']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Sector:</span>
                                <span class="info-value"><?php echo htmlspecialchars($iglesia['sector_nombre']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Estado:</span>
                                <span class="info-value">
                                    <span class="status-badge <?php echo $iglesia['activo'] ? 'status-active' : 'status-inactive'; ?>" style="font-size: 0.75rem;">
                                        <?php echo $iglesia['activo'] ? 'Activa' : 'Inactiva'; ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <h3><i class="fas fa-address-book"></i> Contacto</h3>
                            <?php if ($iglesia['direccion']): ?>
                            <div class="info-row">
                                <span class="info-label">Dirección:</span>
                                <span class="info-value" style="white-space: pre-line;"><?php echo htmlspecialchars($iglesia['direccion']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($iglesia['telefono']): ?>
                            <div class="info-row">
                                <span class="info-label">Teléfono:</span>
                                <span class="info-value"><?php echo htmlspecialchars($iglesia['telefono']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="info-card">
                            <h3><i class="fas fa-user-tie"></i> Liderazgo</h3>
                            <?php if ($iglesia['pastor_nombre']): ?>
                            <div class="info-row">
                                <span class="info-label">Pastor:</span>
                                <span class="info-value"><?php echo htmlspecialchars($iglesia['pastor_nombre']); ?></span>
                            </div>
                            <?php if ($iglesia['pastor_email']): ?>
                            <div class="contact-info">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($iglesia['pastor_email']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($iglesia['pastor_telefono']): ?>
                            <div class="contact-info">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($iglesia['pastor_telefono']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php else: ?>
                            <div class="info-row">
                                <span class="info-label">Pastor:</span>
                                <span class="info-value" style="color: var(--gray); font-style: italic;">
                                    Sin asignar
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Información del Sistema -->
                    <h3 class="section-title">
                        <i class="fas fa-database"></i>
                        Información del Sistema
                    </h3>
                    <div class="info-grid">
                        <div class="info-card">
                            <h3><i class="fas fa-calendar-plus"></i> Fechas</h3>
                            <div class="info-row">
                                <span class="info-label">Fecha de Creación:</span>
                                <span class="info-value">
                                    <?php echo date('d/m/Y H:i', strtotime($iglesia['fecha_creacion'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Panel de Acciones -->
                <div class="actions-panel">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Volver a Iglesias
                    </a>
                    <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i>
                        Editar Iglesia
                    </a>
                    
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="cambiar_estado">
                        <button type="submit" class="btn <?php echo $iglesia['activo'] ? 'btn-warning' : 'btn-success'; ?>">
                            <i class="fas fa-power-off"></i>
                            <?php echo $iglesia['activo'] ? 'Desactivar' : 'Activar'; ?> Iglesia
                        </button>
                    </form>
                    
                    <button type="button" class="btn btn-danger" onclick="mostrarModalEliminar()">
                        <i class="fas fa-trash"></i>
                        Eliminar Iglesia
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para eliminar iglesia -->
    <div id="modalEliminar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación</h3>
            </div>
            <p>
                ¿Estás seguro de que deseas eliminar la iglesia 
                "<strong><?php echo htmlspecialchars($iglesia['nombre']); ?></strong>"?
            </p>
            <p style="color: var(--gray); font-size: 0.9rem; margin-top: 0.5rem;">
                Esta acción no se puede deshacer.
            </p>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEliminar')">
                    Cancelar
                </button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="eliminar_iglesia">
                    <button type="submit" class="btn btn-danger">
                        Sí, Eliminar Iglesia
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function mostrarModalEliminar() {
            document.getElementById('modalEliminar').classList.add('active');
        }
        
        function cerrarModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // Cerrar modal al hacer clic fuera
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });
        
        // Toggle sidebar
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }
        
        // Confirmación para eliminar
        document.addEventListener('DOMContentLoaded', function() {
            const deleteForms = document.querySelectorAll('form[action*="eliminar"]');
            deleteForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('¿Estás seguro de que deseas eliminar esta iglesia?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>