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
$zona = null;

// Obtener ID de la zona
$id = $_GET['id'] ?? 0;
if (!$id) {
    $_SESSION['error'] = 'No se especificó la zona';
    redirect('index.php');
}

try {
    // Obtener información de la zona
    $stmt = $db->prepare("
        SELECT z.*, 
               (SELECT COUNT(*) FROM sectores WHERE zona_id = z.id) as total_sectores,
               (SELECT COUNT(*) FROM sectores WHERE zona_id = z.id AND activo = 1) as sectores_activos
        FROM zonas z
        WHERE z.id = ?
    ");
    $stmt->execute([$id]);
    $zona = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$zona) {
        $_SESSION['error'] = 'Zona no encontrada';
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
            $nuevo_estado = $zona['activo'] ? 0 : 1;
            $stmt = $db->prepare("UPDATE zonas SET activo = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $id]);
            
            $success = $nuevo_estado ? '✅ Zona activada exitosamente' : '✅ Zona desactivada exitosamente';
            // Recargar datos
            $zona['activo'] = $nuevo_estado;
            
        } elseif ($action === 'eliminar_zona') {
            // Verificar si hay sectores asignados a esta zona
            if ($zona['total_sectores'] > 0) {
                throw new Exception('No se puede eliminar la zona porque tiene sectores asignados. Elimina o reasigna los sectores primero.');
            }
            
            $stmt = $db->prepare("DELETE FROM zonas WHERE id = ?");
            $stmt->execute([$id]);
            
            $_SESSION['success'] = '✅ Zona eliminada exitosamente';
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
    <title>Detalles de Zona - <?php echo SITE_NAME; ?></title>
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
        
        .stat-number {
            font-size: 2.5rem;
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
                    <li class="nav-item active">
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
                <h1>Detalles de Zona</h1>
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
                        <h2><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($zona['nombre']); ?></h2>
                        <p style="margin-top: 0.5rem; opacity: 0.9; font-size: 0.9rem;">
                            <i class="fas fa-map"></i> 
                            Zona geográfica de sectores
                        </p>
                    </div>
                    <span class="status-badge <?php echo $zona['activo'] ? 'status-active' : 'status-inactive'; ?>">
                        <i class="fas fa-circle" style="font-size: 0.5rem; vertical-align: middle;"></i>
                        <?php echo $zona['activo'] ? 'ACTIVA' : 'INACTIVA'; ?>
                    </span>
                </div>
                
                <div class="details-body">
                    <!-- Estadísticas -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $zona['total_sectores']; ?></div>
                            <div class="stat-label">Total Sectores</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $zona['sectores_activos']; ?></div>
                            <div class="stat-label">Sectores Activos</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">
                                <?php 
                                echo $zona['total_sectores'] > 0 
                                    ? round(($zona['sectores_activos'] / $zona['total_sectores']) * 100) 
                                    : 0; 
                                ?>%
                            </div>
                            <div class="stat-label">Tasa de Actividad</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">
                                <?php echo $zona['total_sectores'] - $zona['sectores_activos']; ?>
                            </div>
                            <div class="stat-label">Sectores Inactivos</div>
                        </div>
                    </div>

                    <!-- Información de la Zona -->
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Información de la Zona
                    </h3>
                    
                    <div class="info-grid">
                        <div class="info-card">
                            <h3><i class="fas fa-cog"></i> Detalles</h3>
                            <div class="info-row">
                                <span class="info-label">Nombre:</span>
                                <span class="info-value"><?php echo htmlspecialchars($zona['nombre']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Código:</span>
                                <span class="info-value"><?php echo htmlspecialchars($zona['codigo'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Estado:</span>
                                <span class="info-value">
                                    <span class="status-badge <?php echo $zona['activo'] ? 'status-active' : 'status-inactive'; ?>" style="font-size: 0.75rem;">
                                        <?php echo $zona['activo'] ? 'Activa' : 'Inactiva'; ?>
                                    </span>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Fecha Creación:</span>
                                <span class="info-value">
                                    <?php echo date('d/m/Y', strtotime($zona['fecha_creacion'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <h3><i class="fas fa-map-marked-alt"></i> Ubicación</h3>
                            <div class="info-row">
                                <span class="info-label">Región:</span>
                                <span class="info-value"><?php echo htmlspecialchars($zona['region'] ?? 'No especificada'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Ciudad:</span>
                                <span class="info-value"><?php echo htmlspecialchars($zona['ciudad'] ?? 'No especificada'); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Área (km²):</span>
                                <span class="info-value"><?php echo $zona['area'] ?? 'N/A'; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Referencia:</span>
                                <span class="info-value"><?php echo htmlspecialchars($zona['referencia'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <h3><i class="fas fa-align-left"></i> Descripción</h3>
                            <div style="padding: 1rem 0; color: var(--gray-dark); line-height: 1.6;">
                                <?php echo $zona['descripcion'] 
                                    ? nl2br(htmlspecialchars($zona['descripcion'])) 
                                    : '<span style="color: var(--gray); font-style: italic;">Sin descripción</span>'; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Panel de Acciones -->
                <div class="actions-panel">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Volver a Zonas
                    </a>
                    <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i>
                        Editar Zona
                    </a>
                    
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="cambiar_estado">
                        <button type="submit" class="btn <?php echo $zona['activo'] ? 'btn-warning' : 'btn-success'; ?>">
                            <i class="fas fa-power-off"></i>
                            <?php echo $zona['activo'] ? 'Desactivar' : 'Activar'; ?> Zona
                        </button>
                    </form>
                    
                    <button type="button" class="btn btn-danger" onclick="mostrarModalEliminar()">
                        <i class="fas fa-trash"></i>
                        Eliminar Zona
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para eliminar zona -->
    <div id="modalEliminar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación</h3>
            </div>
            <p>
                ¿Estás seguro de que deseas eliminar la zona 
                "<strong><?php echo htmlspecialchars($zona['nombre']); ?></strong>"?
            </p>
            <?php if ($zona['total_sectores'] > 0): ?>
            <div style="background: rgba(231, 76, 60, 0.1); padding: 1rem; border-radius: var(--border-radius); margin: 1rem 0;">
                <i class="fas fa-exclamation-circle" style="color: #e74c3c;"></i>
                <strong>Advertencia:</strong> Esta zona tiene <?php echo $zona['total_sectores']; ?> sector(es) asignado(s).
                Para eliminarla, primero debes eliminar o reasignar estos sectores a otras zonas.
            </div>
            <?php endif; ?>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEliminar')">
                    Cancelar
                </button>
                <?php if ($zona['total_sectores'] == 0): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="eliminar_zona">
                    <button type="submit" class="btn btn-danger">
                        Sí, Eliminar Zona
                    </button>
                </form>
                <?php endif; ?>
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
                    if (!confirm('¿Estás seguro de que deseas eliminar esta zona?')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>
</html>