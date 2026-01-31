<?php
require_once '../../includes/config.php';
requireAdmin();

$db = getDB();

// Filtros
$search = $_GET['search'] ?? '';
$estado = $_GET['estado'] ?? '';

$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (c.nombre LIKE ? OR c.descripcion LIKE ? OR u.nombre_completo LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($estado !== '') {
    $where .= " AND c.activo = ?";
    $params[] = $estado;
}

// Obtener comisiones
$query = "SELECT c.*, u.nombre_completo as presidente_nombre
          FROM comisiones c 
          LEFT JOIN usuarios u ON c.presidente_id = u.id 
          $where 
          ORDER BY c.nombre";

$stmt = $db->prepare($query);
$stmt->execute($params);
$comisiones = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas
$stats = [
    'total' => 0,
    'activas' => 0,
    'inactivas' => 0,
    'con_presidente' => 0
];

try {
    $stmt = $db->query("SELECT COUNT(*) as total FROM comisiones");
    $stats['total'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM comisiones WHERE activo = 1");
    $stats['activas'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM comisiones WHERE activo = 0");
    $stats['inactivas'] = $stmt->fetchColumn();
    
    $stmt = $db->query("SELECT COUNT(*) FROM comisiones WHERE presidente_id IS NOT NULL");
    $stats['con_presidente'] = $stmt->fetchColumn();
} catch (Exception $e) {
    error_log("Error obteniendo estadísticas: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Comisiones - <?php echo SITE_NAME; ?></title>
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
            min-width: 800px;
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
        
        .comision-info {
            display: flex;
            flex-direction: column;
        }
        
        .comision-desc {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 0.25rem;
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
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
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-active {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }
        
        .status-inactive {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
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
                    <li class="nav-item">
                        <a href="../iglesias/">
                            <i class="fas fa-church"></i>
                            <span>Iglesias</span>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a href="../comisiones/">
                            <i class="fas fa-tasks"></i>
                            <span>Comisiones</span>
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="nav-section">
                <h3>Contenido</h3>
                <ul>
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
                <h1>Gestión de Comisiones</h1>
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
                        Nueva Comisión
                    </a>
                </div>
                <div class="toolbar-right">
                    <form method="GET" class="search-form">
                        <select name="estado" onchange="this.form.submit()">
                            <option value="">Todos los estados</option>
                            <option value="1" <?php echo $estado === '1' ? 'selected' : ''; ?>>Activas</option>
                            <option value="0" <?php echo $estado === '0' ? 'selected' : ''; ?>>Inactivas</option>
                        </select>
                        
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Buscar comisiones..." 
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
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Comisiones</h3>
                        <span class="stat-number"><?php echo $stats['total']; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Activas</h3>
                        <span class="stat-number"><?php echo $stats['activas']; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-times-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Inactivas</h3>
                        <span class="stat-number"><?php echo $stats['inactivas']; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Con Presidente</h3>
                        <span class="stat-number"><?php echo $stats['con_presidente']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Tabla de comisiones -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Presidente</th>
                            <th>Descripción</th>
                            <th>Estado</th>
                            <th>Fecha Creación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comisiones as $comision): ?>
                        <tr>
                            <td>
                                <div class="comision-info">
                                    <strong><?php echo htmlspecialchars($comision['nombre']); ?></strong>
                                    <small style="color: var(--gray); font-size: 0.85rem;">
                                        ID: <?php echo $comision['id']; ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <?php if ($comision['presidente_nombre']): ?>
                                    <?php echo htmlspecialchars($comision['presidente_nombre']); ?>
                                <?php else: ?>
                                    <span class="text-muted">Sin asignar</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="comision-desc">
                                    <?php echo htmlspecialchars($comision['descripcion'] ?: 'Sin descripción'); ?>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $comision['activo'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $comision['activo'] ? 'Activa' : 'Inactiva'; ?>
                                </span>
                            </td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($comision['fecha_creacion'])); ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="detalles.php?id=<?php echo $comision['id']; ?>" class="btn btn-sm btn-info" title="Ver detalles">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="editar.php?id=<?php echo $comision['id']; ?>" class="btn btn-sm btn-secondary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="eliminar.php" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar esta comisión?')">
                                        <input type="hidden" name="id" value="<?php echo $comision['id']; ?>">
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

                <?php if (empty($comisiones)): ?>
                <div class="empty-state">
                    <i class="fas fa-tasks"></i>
                    <h3>No se encontraron comisiones</h3>
                    <p><?php echo $search || $estado !== '' ? 'Intenta con otros filtros' : 'Comienza agregando la primera comisión'; ?></p>
                    <a href="agregar.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Agregar Comisión
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

        // Smooth scroll para contenido
        document.addEventListener('DOMContentLoaded', function() {
            // Efecto hover para tarjetas de estadísticas
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 10px 20px rgba(0,0,0,0.1)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = 'none';
                });
            });
        });
    </script>
</body>
</html>