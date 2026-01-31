<?php
require_once '../../includes/config.php';
requireLogin();
if (!isAdmin()) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    redirect('../dashboard.php');
}

$db = getDB();

// Procesar eliminación de usuario si se envió el parámetro
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    try {
        // Actualizar el campo activo a 0 en lugar de eliminar físicamente
        $stmt = $db->prepare("UPDATE usuarios SET activo = 0 WHERE id = ?");
        $stmt->execute([$delete_id]);
        
        $_SESSION['success'] = 'Usuario eliminado correctamente';
        // Redirigir a la misma página sin el parámetro de eliminación
        redirect('index.php');
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error al eliminar usuario: ' . $e->getMessage();
        redirect('index.php');
    }
}

// Inicializar variables con valores por defecto
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$search = $_GET['search'] ?? '';
$cargo = $_GET['cargo'] ?? '';
$usuarios = [];
$totalUsuarios = 0;
$totalPages = 0;

try {
    // Construir consulta base
    $where = "WHERE u.activo = 1";
    $params = [];

    if ($search) {
        $where .= " AND (u.nombre_completo LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($cargo) {
        $where .= " AND u.cargo = ?";
        $params[] = $cargo;
    }

    // Obtener usuarios
    $query = "SELECT u.*, z.nombre as zona_nombre, c.nombre as comision_nombre 
              FROM usuarios u 
              LEFT JOIN zonas z ON u.zona_id = z.id 
              LEFT JOIN comisiones c ON u.comision_id = c.id 
              $where 
              ORDER BY u.fecha_creacion DESC 
              LIMIT $limit OFFSET $offset";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contar total para paginación
    $countQuery = "SELECT COUNT(*) FROM usuarios u $where";
    $stmt = $db->prepare($countQuery);
    $stmt->execute($params);
    $totalUsuarios = $stmt->fetchColumn();
    $totalPages = ceil($totalUsuarios / $limit);

} catch (Exception $e) {
    $error = "Error al cargar usuarios: " . $e->getMessage();
}

// Obtener estadísticas
try {
    $totalAdmins = $db->query("SELECT COUNT(*) FROM usuarios WHERE cargo = 'administrador' AND activo = 1")->fetchColumn();
    $totalPastores = $db->query("SELECT COUNT(*) FROM usuarios WHERE cargo = 'pastor' AND activo = 1")->fetchColumn();
    $totalLideres = $db->query("SELECT COUNT(*) FROM usuarios WHERE cargo IN ('jefe_zona', 'jefe_sector') AND activo = 1")->fetchColumn();
} catch (Exception $e) {
    $totalAdmins = 0;
    $totalPastores = 0;
    $totalLideres = 0;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Usuarios - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../admin-styles.css">
    <style>
        .text-center { text-align: center; }
        
        /* Estilos para mensajes de error */
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
                    <li class="nav-item active">
                        <a href="index.php">
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
                <h1>Gestión de Usuarios</h1>
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
                        <i class="fas fa-user-plus"></i>
                        Nuevo Usuario
                    </a>
                </div>
                <div class="toolbar-right">
                    <form method="GET" class="search-form">
                        <select name="cargo" class="filter-select" onchange="this.form.submit()">
                            <option value="">Todos los cargos</option>
                            <option value="administrador" <?php echo $cargo == 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                            <option value="jefe_zona" <?php echo $cargo == 'jefe_zona' ? 'selected' : ''; ?>>Jefe de Zona</option>
                            <option value="jefe_sector" <?php echo $cargo == 'jefe_sector' ? 'selected' : ''; ?>>Jefe de Sector</option>
                            <option value="pastor" <?php echo $cargo == 'pastor' ? 'selected' : ''; ?>>Pastor</option>
                        </select>
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Buscar usuarios..." 
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
                    <div class="stat-mini-number"><?php echo $totalUsuarios; ?></div>
                    <div class="stat-mini-label">Total Usuarios</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-number"><?php echo $totalAdmins; ?></div>
                    <div class="stat-mini-label">Administradores</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-number"><?php echo $totalPastores; ?></div>
                    <div class="stat-mini-label">Pastores</div>
                </div>
                <div class="stat-mini">
                    <div class="stat-mini-number"><?php echo $totalLideres; ?></div>
                    <div class="stat-mini-label">Líderes</div>
                </div>
            </div>

            <!-- Tabla de Usuarios -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Contacto</th>
                            <th>Cargo</th>
                            <th>Asignación</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($usuarios)): ?>
                            <tr>
                                <td colspan="6">
                                    <div class="empty-state">
                                        <i class="fas fa-users"></i>
                                        <h3>No se encontraron usuarios</h3>
                                        <p><?php echo $search || $cargo ? 'Intenta con otros filtros de búsqueda' : 'Comienza agregando el primer usuario'; ?></p>
                                        <a href="agregar.php" class="btn btn-primary">
                                            <i class="fas fa-user-plus"></i>
                                            Agregar Usuario
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($usuario['nombre_completo'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600; color: var(--dark);"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></div>
                                            <div style="font-size: 0.8rem; color: var(--gray);"><?php echo htmlspecialchars($usuario['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.9rem;">
                                        <div><i class="fas fa-phone"></i> <?php echo $usuario['telefono'] ?: 'No especificado'; ?></div>
                                        <div><i class="fas fa-mobile-alt"></i> <?php echo $usuario['celular'] ?: 'No especificado'; ?></div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo $usuario['cargo']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $usuario['cargo'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($usuario['zona_nombre']): ?>
                                        <div style="font-size: 0.9rem;">
                                            <i class="fas fa-map-marker-alt"></i> 
                                            <?php echo $usuario['zona_nombre']; ?>
                                        </div>
                                    <?php elseif ($usuario['comision_nombre']): ?>
                                        <div style="font-size: 0.9rem;">
                                            <i class="fas fa-tasks"></i> 
                                            <?php echo $usuario['comision_nombre']; ?>
                                        </div>
                                    <?php else: ?>
                                        <span style="color: var(--gray); font-size: 0.9rem;">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="display: inline-flex; align-items: center; gap: 0.5rem; color: var(--success); font-weight: 600;">
                                        <i class="fas fa-circle" style="font-size: 0.6rem;"></i>
                                        Activo
                                    </span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="editar.php?id=<?php echo $usuario['id']; ?>" class="btn-table btn-edit" title="Editar Usuario">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?delete_id=<?php echo $usuario['id']; ?>" 
                                           class="btn-table btn-delete" 
                                           onclick="return confirm('¿Está seguro de eliminar este usuario?')"
                                           title="Eliminar Usuario">
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
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&cargo=<?php echo urlencode($cargo); ?>" class="btn btn-secondary">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </a>
                        <?php endif; ?>
                    </div>
                    
                    <div class="pagination-info">
                        Página <?php echo $page; ?> de <?php echo $totalPages; ?> 
                        (<?php echo $totalUsuarios; ?> usuarios en total)
                    </div>
                    
                    <div>
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&cargo=<?php echo urlencode($cargo); ?>" class="btn btn-secondary">
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
        // JavaScript para funcionalidades adicionales
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