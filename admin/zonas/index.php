<?php
require_once '../../includes/config.php';
requireLogin();
if (!isAdmin()) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    redirect('../dashboard.php');
}

$db = getDB();

// Procesar eliminación de zona si se envió el parámetro
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    try {
        // PRIMERO: Desvincular usuarios que tienen esta zona asignada
        $stmt = $db->prepare("UPDATE usuarios SET zona_id = NULL WHERE zona_id = ?");
        $stmt->execute([$delete_id]);
        
        // SEGUNDO: Eliminar la zona
        $stmt = $db->prepare("DELETE FROM zonas WHERE id = ?");
        $stmt->execute([$delete_id]);
        
        $_SESSION['success'] = 'Zona eliminada correctamente';
        // Redirigir a la misma página sin el parámetro de eliminación
        redirect('index.php');
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error al eliminar zona: ' . $e->getMessage();
        redirect('index.php');
    }
}

// Inicializar variables con valores por defecto
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$estado = $_GET['estado'] ?? '';
$zonas = [];
$totalZonas = 0;
$totalPages = 0;

try {
    // Construir consulta base - MOSTRAR TODAS LAS ZONAS
    $where = "WHERE 1=1";
    $params = [];

    if ($search) {
        $where .= " AND (z.nombre LIKE ? OR z.descripcion LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($estado === 'activa') {
        $where .= " AND z.activo = 1";
    } elseif ($estado === 'inactiva') {
        $where .= " AND z.activo = 0";
    }

    // Obtener zonas - TODAS, no solo activas
    $query = "SELECT z.*, 
                     u.nombre_completo as jefe_nombre,
                     COUNT(DISTINCT s.id) as total_sectores,
                     COUNT(DISTINCT ig.id) as total_iglesias,
                     (SELECT COUNT(*) FROM usuarios us WHERE us.zona_id = z.id) as total_usuarios
              FROM zonas z 
              LEFT JOIN usuarios u ON z.jefe_zona_id = u.id 
              LEFT JOIN sectores s ON z.id = s.zona_id
              LEFT JOIN iglesias ig ON s.id = ig.sector_id
              $where 
              GROUP BY z.id
              ORDER BY z.fecha_creacion DESC 
              LIMIT $limit OFFSET $offset";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contar total para paginación
    $countQuery = "SELECT COUNT(*) FROM zonas z $where";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $totalZonas = $stmt->fetchColumn();
    $totalPages = ceil($totalZonas / $limit);

} catch (Exception $e) {
    $error = "Error al cargar zonas: " . $e->getMessage();
}

// Obtener estadísticas
try {
    $totalActivas = $db->query("SELECT COUNT(*) FROM zonas WHERE activo = 1")->fetchColumn();
    $totalInactivas = $db->query("SELECT COUNT(*) FROM zonas WHERE activo = 0")->fetchColumn();
    $totalConJefe = $db->query("SELECT COUNT(*) FROM zonas WHERE jefe_zona_id IS NOT NULL")->fetchColumn();
} catch (Exception $e) {
    $totalActivas = 0;
    $totalInactivas = 0;
    $totalConJefe = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Zonas - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../admin-styles.css">
    <style>
        .text-center { text-align: center; }
        
        .alert-warning {
            background: rgba(243, 156, 18, 0.1);
            border: 1px solid rgba(243, 156, 18, 0.2);
            color: #f39c12;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-success {
            background: rgba(39, 174, 96, 0.1);
            border: 1px solid rgba(39, 174, 96, 0.2);
            color: #27ae60;
            padding: 1rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .badge-activa {
            background: var(--success-light);
            color: var(--success);
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-inactiva {
            background: rgba(108, 117, 125, 0.1);
            color: #6c757d;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .user-avatar i {
            font-size: 1rem;
        }

        .warning-badge {
            background: rgba(243, 156, 18, 0.1);
            color: #f39c12;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
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
                <h1>Gestión de Zonas</h1>
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
            <?php if (isset($_SESSION['error'])): ?>
            <div class="alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
            <?php endif; ?>

            <?php if (isset($error)): ?>
            <div class="alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <!-- Toolbar -->
            <div class="toolbar">
                <div class="toolbar-left">
                    <a href="agregar.php" class="btn btn-primary">
                        <i class="fas fa-plus-circle"></i>
                        Nueva Zona
                    </a>
                </div>
                <div class="toolbar-right">
                    <form method="GET" class="search-form">
                        <select name="estado" class="filter-select" onchange="this.form.submit()">
                            <option value="">Todos los estados</option>
                            <option value="activa" <?php echo $estado == 'activa' ? 'selected' : ''; ?>>Activas</option>
                            <option value="inactiva" <?php echo $estado == 'inactiva' ? 'selected' : ''; ?>>Inactivas</option>
                        </select>
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Buscar zonas..." 
                                   value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Estadísticas Rápidas -->
            <div class="stats-overview">
                <div class="stat-mini">
                    <div class="stat-mini-number"><?php echo $totalZonas; ?></div>
                    <div class="stat-mini-label">Total Zonas</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-number"><?php echo $totalActivas; ?></div>
                    <div class="stat-mini-label">Zonas Activas</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-number"><?php echo $totalInactivas; ?></div>
                    <div class="stat-mini-label">Zonas Inactivas</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-number"><?php echo $totalConJefe; ?></div>
                    <div class="stat-mini-label">Con Jefe Asignado</div>
                </div>
            </div>

            <!-- Tabla de Zonas -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Zona</th>
                            <th>Jefe de Zona</th>
                            <th>Estadísticas</th>
                            <th>Estado</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($zonas)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <h3>No se encontraron zonas</h3>
                                        <p><?php echo $search || $estado ? 'Intenta con otros filtros de búsqueda' : 'Comienza agregando la primera zona'; ?></p>
                                        <a href="agregar.php" class="btn btn-primary">
                                            <i class="fas fa-plus-circle"></i>
                                            Agregar Zona
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($zonas as $zona): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div class="user-avatar" style="background: var(--primary-light); color: var(--primary);">
                                            <i class="fas fa-map-marker-alt"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--dark);"><?php echo htmlspecialchars($zona['nombre']); ?></div>
                                            <div style="font-size: 0.8rem; color: var(--gray);">
                                                <?php echo $zona['descripcion'] ? htmlspecialchars(substr($zona['descripcion'], 0, 50)) . '...' : 'Sin descripción'; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($zona['jefe_nombre']): ?>
                                        <div style="font-size: 0.9rem; font-weight: 500;">
                                            <i class="fas fa-user"></i> 
                                            <?php echo htmlspecialchars($zona['jefe_nombre']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--gray); font-size: 0.9rem;">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 1rem; font-size: 0.85rem;">
                                        <span style="display: inline-flex; align-items: center; gap: 0.25rem;">
                                            <i class="fas fa-layer-group" style="color: var(--primary);"></i>
                                            <?php echo $zona['total_sectores']; ?> sectores
                                        </span>
                                        <span style="display: inline-flex; align-items: center; gap: 0.25rem;">
                                            <i class="fas fa-church" style="color: var(--success);"></i>
                                            <?php echo $zona['total_iglesias']; ?> iglesias
                                        </span>
                                        <?php if ($zona['total_usuarios'] > 0): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 0.25rem;">
                                            <i class="fas fa-users" style="color: var(--warning);"></i>
                                            <?php echo $zona['total_usuarios']; ?> usuarios
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    // Usar campo activo en lugar de estado
                                    $activoZona = isset($zona['activo']) ? $zona['activo'] : 1;
                                    $estadoTexto = $activoZona == 1 ? 'activa' : 'inactiva';
                                    $claseEstado = $activoZona == 1 ? 'badge-activa' : 'badge-inactiva';
                                    ?>
                                    <span class="<?php echo $claseEstado; ?>">
                                        <?php echo ucfirst($estadoTexto); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem; color: var(--gray);">
                                        <?php echo date('d/m/Y', strtotime($zona['fecha_creacion'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="editar.php?id=<?php echo $zona['id']; ?>" class="btn-table btn-edit" title="Editar Zona">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="detalles.php?id=<?php echo $zona['id']; ?>" class="btn-table btn-view" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($zona['total_usuarios'] > 0): ?>
                                        <span class="warning-badge" title="Esta zona tiene usuarios asignados. Se desvincularán automáticamente al eliminar.">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <?php echo $zona['total_usuarios']; ?> usuarios
                                        </span>
                                        <?php endif; ?>
                                        <a href="?delete_id=<?php echo $zona['id']; ?>" 
                                           class="btn-table btn-delete" 
                                           onclick="return confirm('¿Está seguro de eliminar esta zona? <?php echo $zona['total_usuarios'] > 0 ? 'Se desvincularán ' . $zona['total_usuarios'] . ' usuarios asignados. ' : ''; ?>Esta acción no se puede deshacer.')"
                                           title="Eliminar Zona">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Paginación -->
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <div>
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>" class="btn btn-secondary">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="pagination-info">
                        Página <?php echo $page; ?> de <?php echo $totalPages; ?> 
                        (<?php echo $totalZonas; ?> zonas en total)
                    </div>
                    
                    <div>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>" class="btn btn-secondary">
                                Siguiente <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
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

            // Confirmación para eliminación ya está en el onclick del enlace
        });
    </script>
</body>
</html>