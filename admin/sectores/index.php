<?php
require_once '../../includes/config.php';
requireLogin();
if (!isAdmin()) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    redirect('../dashboard.php');
}

$db = getDB();

// Procesar eliminación de sector si se envió el parámetro
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    try {
        // PRIMERO: Desvincular iglesias que tienen este sector asignado
        $stmt = $db->prepare("UPDATE iglesias SET sector_id = NULL WHERE sector_id = ?");
        $stmt->execute([$delete_id]);
        
        // SEGUNDO: Eliminar el sector
        $stmt = $db->prepare("DELETE FROM sectores WHERE id = ?");
        $stmt->execute([$delete_id]);
        
        $_SESSION['success'] = 'Sector eliminado correctamente';
        // Redirigir a la misma página sin el parámetro de eliminación
        redirect('index.php');
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error al eliminar sector: ' . $e->getMessage();
        redirect('index.php');
    }
}

// Inicializar variables con valores por defecto
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$estado = $_GET['estado'] ?? '';
$zona_id = $_GET['zona_id'] ?? '';
$sectores = [];
$totalSectores = 0;
$totalPages = 0;

try {
    // Construir consulta base - MOSTRAR TODOS LOS SECTORES
    $where = "WHERE 1=1";
    $params = [];

    if ($search) {
        $where .= " AND (s.nombre LIKE ? OR s.descripcion LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($estado === 'activo') {
        $where .= " AND s.activo = 1";
    } elseif ($estado === 'inactivo') {
        $where .= " AND s.activo = 0";
    }

    if ($zona_id) {
        $where .= " AND s.zona_id = ?";
        $params[] = $zona_id;
    }

    // Obtener sectores
    $query = "SELECT s.*, 
                     z.nombre as zona_nombre,
                     u.nombre_completo as jefe_nombre,
                     COUNT(DISTINCT ig.id) as total_iglesias,
                     (SELECT COUNT(*) FROM iglesias ig2 WHERE ig2.sector_id = s.id) as total_iglesias_asignadas
              FROM sectores s 
              LEFT JOIN zonas z ON s.zona_id = z.id 
              LEFT JOIN usuarios u ON s.jefe_sector_id = u.id 
              LEFT JOIN iglesias ig ON s.id = ig.sector_id
              $where 
              GROUP BY s.id
              ORDER BY s.fecha_creacion DESC 
              LIMIT $limit OFFSET $offset";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $sectores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contar total para paginación
    $countQuery = "SELECT COUNT(*) FROM sectores s $where";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $totalSectores = $stmt->fetchColumn();
    $totalPages = ceil($totalSectores / $limit);

} catch (Exception $e) {
    $error = "Error al cargar sectores: " . $e->getMessage();
}

// Obtener estadísticas
try {
    $totalActivos = $db->query("SELECT COUNT(*) FROM sectores WHERE activo = 1")->fetchColumn();
    $totalInactivos = $db->query("SELECT COUNT(*) FROM sectores WHERE activo = 0")->fetchColumn();
    $totalConJefe = $db->query("SELECT COUNT(*) FROM sectores WHERE jefe_sector_id IS NOT NULL")->fetchColumn();
} catch (Exception $e) {
    $totalActivos = 0;
    $totalInactivos = 0;
    $totalConJefe = 0;
}

// Obtener zonas para el filtro
$zonas = [];
try {
    $stmt = $db->query("SELECT id, nombre FROM zonas WHERE activo = 1 ORDER BY nombre");
    $zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Si hay error, simplemente no mostrar el filtro
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Sectores - <?php echo SITE_NAME; ?></title>
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

        .badge-activo {
            background: var(--success-light);
            color: var(--success);
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .badge-inactivo {
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
                    <li class="nav-item">
                        <a href="../zonas/">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Zonas</span>
                        </a>
                    </li>
                    <li class="nav-item active">
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
                <h1>Gestión de Sectores</h1>
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
                        Nuevo Sector
                    </a>
                </div>
                <div class="toolbar-right">
                    <form method="GET" class="search-form">
                        <select name="zona_id" class="filter-select" onchange="this.form.submit()">
                            <option value="">Todas las zonas</option>
                            <?php foreach ($zonas as $zona): ?>
                                <option value="<?php echo $zona['id']; ?>" <?php echo $zona_id == $zona['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($zona['nombre']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <select name="estado" class="filter-select" onchange="this.form.submit()">
                            <option value="">Todos los estados</option>
                            <option value="activo" <?php echo $estado == 'activo' ? 'selected' : ''; ?>>Activos</option>
                            <option value="inactivo" <?php echo $estado == 'inactivo' ? 'selected' : ''; ?>>Inactivos</option>
                        </select>
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Buscar sectores..." 
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
                    <div class="stat-mini-number"><?php echo $totalSectores; ?></div>
                    <div class="stat-mini-label">Total Sectores</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-number"><?php echo $totalActivos; ?></div>
                    <div class="stat-mini-label">Sectores Activos</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-number"><?php echo $totalInactivos; ?></div>
                    <div class="stat-mini-label">Sectores Inactivos</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-number"><?php echo $totalConJefe; ?></div>
                    <div class="stat-mini-label">Con Jefe Asignado</div>
                </div>
            </div>

            <!-- Tabla de Sectores -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Sector</th>
                            <th>Zona</th>
                            <th>Jefe de Sector</th>
                            <th>Estadísticas</th>
                            <th>Estado</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($sectores)): ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fas fa-layer-group"></i>
                                        <h3>No se encontraron sectores</h3>
                                        <p><?php echo $search || $estado || $zona_id ? 'Intenta con otros filtros de búsqueda' : 'Comienza agregando el primer sector'; ?></p>
                                        <a href="agregar.php" class="btn btn-primary">
                                            <i class="fas fa-plus-circle"></i>
                                            Agregar Sector
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($sectores as $sector): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div class="user-avatar" style="background: var(--info-light); color: var(--info);">
                                            <i class="fas fa-layer-group"></i>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--dark);"><?php echo htmlspecialchars($sector['nombre']); ?></div>
                                            <div style="font-size: 0.8rem; color: var(--gray);">
                                                <?php echo $sector['descripcion'] ? htmlspecialchars(substr($sector['descripcion'], 0, 50)) . '...' : 'Sin descripción'; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($sector['zona_nombre']): ?>
                                        <div style="font-size: 0.9rem; font-weight: 500;">
                                            <i class="fas fa-map-marker-alt" style="color: var(--primary);"></i> 
                                            <?php echo htmlspecialchars($sector['zona_nombre']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--gray); font-size: 0.9rem;">Sin zona asignada</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($sector['jefe_nombre']): ?>
                                        <div style="font-size: 0.9rem; font-weight: 500;">
                                            <i class="fas fa-user"></i> 
                                            <?php echo htmlspecialchars($sector['jefe_nombre']); ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--gray); font-size: 0.9rem;">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 1rem; font-size: 0.85rem;">
                                        <span style="display: inline-flex; align-items: center; gap: 0.25rem;">
                                            <i class="fas fa-church" style="color: var(--success);"></i>
                                            <?php echo $sector['total_iglesias']; ?> iglesias
                                        </span>
                                        <?php if ($sector['total_iglesias_asignadas'] > 0): ?>
                                        <span style="display: inline-flex; align-items: center; gap: 0.25rem;">
                                            <i class="fas fa-exclamation-triangle" style="color: var(--warning);"></i>
                                            <?php echo $sector['total_iglesias_asignadas']; ?> asignadas
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                    $activoSector = isset($sector['activo']) ? $sector['activo'] : 1;
                                    $estadoTexto = $activoSector == 1 ? 'activo' : 'inactivo';
                                    $claseEstado = $activoSector == 1 ? 'badge-activo' : 'badge-inactivo';
                                    ?>
                                    <span class="<?php echo $claseEstado; ?>">
                                        <?php echo ucfirst($estadoTexto); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem; color: var(--gray);">
                                        <?php echo date('d/m/Y', strtotime($sector['fecha_creacion'])); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="editar.php?id=<?php echo $sector['id']; ?>" class="btn-table btn-edit" title="Editar Sector">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="detalles.php?id=<?php echo $sector['id']; ?>" class="btn-table btn-view" title="Ver Detalles">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($sector['total_iglesias_asignadas'] > 0): ?>
                                        <span class="warning-badge" title="Este sector tiene iglesias asignadas. Se desvincularán automáticamente al eliminar.">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <?php echo $sector['total_iglesias_asignadas']; ?> iglesias
                                        </span>
                                        <?php endif; ?>
                                        <a href="?delete_id=<?php echo $sector['id']; ?>" 
                                           class="btn-table btn-delete" 
                                           onclick="return confirm('¿Está seguro de eliminar este sector? <?php echo $sector['total_iglesias_asignadas'] > 0 ? 'Se desvincularán ' . $sector['total_iglesias_asignadas'] . ' iglesias asignadas. ' : ''; ?>Esta acción no se puede deshacer.')"
                                           title="Eliminar Sector">
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
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>&zona_id=<?php echo urlencode($zona_id); ?>" class="btn btn-secondary">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="pagination-info">
                        Página <?php echo $page; ?> de <?php echo $totalPages; ?> 
                        (<?php echo $totalSectores; ?> sectores en total)
                    </div>
                    
                    <div>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&estado=<?php echo urlencode($estado); ?>&zona_id=<?php echo urlencode($zona_id); ?>" class="btn btn-secondary">
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
        });
    </script>
</body>
</html>