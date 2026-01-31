<?php
require_once '../../includes/config.php';
requireAdmin();

$db = getDB();

// Filtros
$sector_id = $_GET['sector_id'] ?? '';
$zona_id = $_GET['zona_id'] ?? '';
$search = $_GET['search'] ?? '';

$where = "WHERE i.activo = 1";
$params = [];

if ($sector_id) {
    $where .= " AND i.sector_id = ?";
    $params[] = $sector_id;
} elseif ($zona_id) {
    $where .= " AND i.sector_id IN (SELECT id FROM sectores WHERE zona_id = ?)";
    $params[] = $zona_id;
}

if ($search) {
    $where .= " AND (i.nombre LIKE ? OR i.direccion LIKE ? OR i.telefono LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Obtener iglesias
$query = "SELECT i.*, s.nombre as sector_nombre, z.nombre as zona_nombre, 
          u.nombre_completo as pastor_nombre
          FROM iglesias i 
          LEFT JOIN sectores s ON i.sector_id = s.id 
          LEFT JOIN zonas z ON s.zona_id = z.id 
          LEFT JOIN usuarios u ON i.pastor_id = u.id 
          $where 
          ORDER BY z.nombre, s.nombre, i.nombre";

$stmt = $db->prepare($query);
$stmt->execute($params);
$iglesias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos para filtros
$zonas = [];
$sectores = [];

try {
    $stmt = $db->query("SELECT id, nombre FROM zonas WHERE activo = 1 ORDER BY nombre");
    $zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($zona_id) {
        $stmt = $db->prepare("SELECT id, nombre FROM sectores WHERE zona_id = ? AND activo = 1 ORDER BY nombre");
        $stmt->execute([$zona_id]);
        $sectores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error cargando filtros: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Iglesias - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        <?php include '../admin-styles.css'; ?>
        
        .content-container {
            padding: 2rem;
        }
        
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .toolbar-right {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-form {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }
        
        .form-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        select, .search-input {
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            font-family: inherit;
            background: white;
            transition: var(--transition);
        }
        
        select {
            min-width: 180px;
        }
        
        .search-input {
            min-width: 250px;
        }
        
        select:focus, .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 90, 160, 0.1);
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
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .stat-info h3 {
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
        }
        
        .table-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
            overflow-x: auto;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1000px;
        }
        
        .data-table th {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1.25rem 1rem;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .data-table th:first-child {
            border-top-left-radius: var(--border-radius);
        }
        
        .data-table th:last-child {
            border-top-right-radius: var(--border-radius);
        }
        
        .data-table td {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-light);
            vertical-align: middle;
        }
        
        .data-table tr:hover {
            background: rgba(44, 90, 160, 0.02);
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .table-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
        }
        
        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            border-radius: var(--border-radius);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .empty-state p {
            margin: 0 0 1.5rem 0;
            font-size: 1rem;
            color: var(--gray);
        }
        
        .text-muted {
            color: var(--gray) !important;
            font-style: italic;
        }
        
        @media (max-width: 1024px) {
            .toolbar {
                flex-direction: column;
                gap: 1.5rem;
                align-items: stretch;
            }
            
            .toolbar-right {
                justify-content: stretch;
            }
            
            .search-form {
                flex-direction: column;
                width: 100%;
            }
            
            .form-group {
                width: 100%;
            }
            
            select, .search-input {
                width: 100%;
                min-width: unset;
            }
        }
        
        @media (max-width: 768px) {
            .content-container {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
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
                <h1>Gestión de Iglesias</h1>
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
            <!-- Barra de herramientas -->
            <div class="toolbar">
                <div class="toolbar-left">
                    <a href="agregar.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Nueva Iglesia
                    </a>
                </div>
                <div class="toolbar-right">
                    <form method="GET" class="search-form">
                        <select name="zona_id" id="zona_filter" onchange="cargarSectores()">
                            <option value="">Todas las zonas</option>
                            <?php foreach ($zonas as $zona): ?>
                            <option value="<?php echo $zona['id']; ?>" 
                                <?php echo $zona_id == $zona['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($zona['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="sector_id" id="sector_filter" onchange="this.form.submit()">
                            <option value="">Todos los sectores</option>
                            <?php foreach ($sectores as $sector): ?>
                            <option value="<?php echo $sector['id']; ?>" 
                                <?php echo $sector_id == $sector['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sector['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Buscar iglesias..." 
                                   value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-church"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Iglesias</h3>
                        <span class="stat-number"><?php echo count($iglesias); ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Zonas Activas</h3>
                        <span class="stat-number">
                            <?php
                            $stmt = $db->query("SELECT COUNT(DISTINCT s.zona_id) FROM iglesias i JOIN sectores s ON i.sector_id = s.id WHERE i.activo = 1");
                            echo $stmt->fetchColumn();
                            ?>
                        </span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-layer-group"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Sectores Activos</h3>
                        <span class="stat-number">
                            <?php
                            $stmt = $db->query("SELECT COUNT(DISTINCT sector_id) FROM iglesias WHERE activo = 1");
                            echo $stmt->fetchColumn();
                            ?>
                        </span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Pastores</h3>
                        <span class="stat-number">
                            <?php
                            $stmt = $db->query("SELECT COUNT(DISTINCT pastor_id) FROM iglesias WHERE pastor_id IS NOT NULL AND activo = 1");
                            echo $stmt->fetchColumn();
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tabla de iglesias -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Zona</th>
                            <th>Sector</th>
                            <th>Dirección</th>
                            <th>Teléfono</th>
                            <th>Pastor</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($iglesias as $iglesia): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <strong><?php echo htmlspecialchars($iglesia['nombre']); ?></strong>
                                    <small style="color: var(--gray); font-size: 0.85rem;">
                                        ID: <?php echo $iglesia['id']; ?>
                                    </small>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($iglesia['zona_nombre']); ?></td>
                            <td><?php echo htmlspecialchars($iglesia['sector_nombre']); ?></td>
                            <td><?php echo htmlspecialchars($iglesia['direccion'] ?: 'Sin dirección'); ?></td>
                            <td><?php echo $iglesia['telefono'] ?: 'N/A'; ?></td>
                            <td>
                                <?php if ($iglesia['pastor_nombre']): ?>
                                    <?php echo htmlspecialchars($iglesia['pastor_nombre']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Sin asignar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="detalles.php?id=<?php echo $iglesia['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="editar.php?id=<?php echo $iglesia['id']; ?>" class="btn btn-sm btn-secondary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="eliminar.php" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar esta iglesia?')">
                                        <input type="hidden" name="id" value="<?php echo $iglesia['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (empty($iglesias)): ?>
                <div class="empty-state">
                    <i class="fas fa-church"></i>
                    <h3>No se encontraron iglesias</h3>
                    <p><?php echo $zona_id || $sector_id || $search ? 'Intenta con otros filtros' : 'Comienza agregando la primera iglesia'; ?></p>
                    <a href="agregar.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Agregar Iglesia
                    </a>
                </div>
                <?php endif; ?>
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

        // Cargar sectores por zona
        function cargarSectores() {
            const zonaId = document.getElementById('zona_filter').value;
            const sectorFilter = document.getElementById('sector_filter');
            const form = sectorFilter.closest('form');
            
            if (zonaId) {
                // Usar fetch para cargar sectores
                fetch(`../../api/sectores.php?action=by_zona&zona_id=${zonaId}`)
                    .then(response => response.json())
                    .then(data => {
                        sectorFilter.innerHTML = '<option value="">Todos los sectores</option>';
                        if (data.success && data.sectores) {
                            data.sectores.forEach(sector => {
                                const option = document.createElement('option');
                                option.value = sector.id;
                                option.textContent = sector.nombre;
                                sectorFilter.appendChild(option);
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error cargando sectores:', error);
                    });
            } else {
                sectorFilter.innerHTML = '<option value="">Todos los sectores</option>';
            }
            
            // Cambiar el sector a vacío y submit
            sectorFilter.value = '';
            setTimeout(() => form.submit(), 300);
        }

        // Cargar sectores al inicio si hay zona seleccionada
        document.addEventListener('DOMContentLoaded', function() {
            const zonaFilter = document.getElementById('zona_filter');
            if (zonaFilter.value) {
                cargarSectores();
            }
        });
    </script>
</body>
</html>