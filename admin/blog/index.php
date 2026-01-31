<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
requireAdmin();

$db = getDB();

// Filtros
$categoria_id = $_GET['categoria_id'] ?? '';
$estado = $_GET['estado'] ?? '';
$search = $_GET['search'] ?? '';

$where = "WHERE 1=1";
$params = [];

if ($categoria_id) {
    $where .= " AND b.categoria_id = ?";
    $params[] = $categoria_id;
}

if ($estado) {
    $where .= " AND b.estado = ?";
    $params[] = $estado;
}

if ($search) {
    $where .= " AND (b.titulo LIKE ? OR b.contenido LIKE ? OR b.resumen LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Obtener posts
$query = "SELECT b.*, u.nombre_completo as autor_nombre, c.nombre as categoria_nombre
          FROM blog b 
          LEFT JOIN usuarios u ON b.autor_id = u.id 
          LEFT JOIN categorias_blog c ON b.categoria_id = c.id 
          $where 
          ORDER BY b.fecha_creacion DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener datos para filtros
$categorias = [];
try {
    $stmt = $db->query("SELECT id, nombre FROM categorias_blog WHERE activo = 1 ORDER BY nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error cargando categorías: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Blog - <?php echo SITE_NAME; ?></title>
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
            min-width: 1200px;
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
        
        .post-title {
            font-weight: 600;
            color: var(--dark);
            margin-bottom: 0.25rem;
        }
        
        .post-excerpt {
            font-size: 0.85rem;
            color: var(--gray);
            line-height: 1.4;
            margin-bottom: 0.5rem;
        }
        
        .post-meta {
            font-size: 0.75rem;
            color: var(--gray);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .post-meta i {
            font-size: 0.7rem;
        }
        
        .post-image {
            width: 80px;
            height: 60px;
            border-radius: 8px;
            object-fit: cover;
            background: var(--gray-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            overflow: hidden;
        }
        
        .post-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-publicado {
            background: #d4edda;
            color: #155724;
        }
        
        .status-borrador {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-programado {
            background: #d1ecf1;
            color: #0c5460;
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
                    <li class="nav-item active">
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
                    <li class="nav-item">
                        <a href="categorias/">
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
                <h1>Gestión de Blog</h1>
            </div>
            <div class="header-right">
                <div class="user-menu">
                    <div class="user-info">
                        <span class="user-name">
                            <?php 
                            // LÍNEA 468 CORREGIDA
                            if (isset($_SESSION['user_nombre']) && !empty($_SESSION['user_nombre'])) {
                                echo htmlspecialchars($_SESSION['user_nombre']);
                            } elseif (isset($_SESSION['user']['nombre_completo'])) {
                                echo htmlspecialchars($_SESSION['user']['nombre_completo']);
                            } else {
                                echo 'Usuario';
                            }
                            ?>
                        </span>
                        <span class="user-role">
                            <?php 
                            // LÍNEA 469 CORREGIDA
                            if (isset($_SESSION['user_cargo']) && !empty($_SESSION['user_cargo'])) {
                                echo htmlspecialchars($_SESSION['user_cargo']);
                            } elseif (isset($_SESSION['user']['cargo'])) {
                                echo htmlspecialchars($_SESSION['user']['cargo']);
                            } elseif (isset($_SESSION['user']['rol'])) {
                                echo htmlspecialchars($_SESSION['user']['rol']);
                            } else {
                                echo 'Sin cargo';
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
            <!-- Barra de herramientas -->
            <div class="toolbar">
                <div class="toolbar-left">
                    <a href="crear.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Nuevo Artículo
                    </a>
                </div>
                <div class="toolbar-right">
                    <form method="GET" class="search-form">
                        <select name="categoria_id" onchange="this.form.submit()">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $categoria): ?>
                            <option value="<?php echo $categoria['id']; ?>" 
                                <?php echo $categoria_id == $categoria['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($categoria['nombre']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <select name="estado" onchange="this.form.submit()">
                            <option value="">Todos los estados</option>
                            <option value="publicado" <?php echo $estado == 'publicado' ? 'selected' : ''; ?>>Publicados</option>
                            <option value="borrador" <?php echo $estado == 'borrador' ? 'selected' : ''; ?>>Borradores</option>
                            <option value="programado" <?php echo $estado == 'programado' ? 'selected' : ''; ?>>Programados</option>
                        </select>
                        
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Buscar artículos..." 
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
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Artículos</h3>
                        <span class="stat-number">
                            <?php
                            $stmt = $db->query("SELECT COUNT(*) FROM blog");
                            echo $stmt->fetchColumn();
                            ?>
                        </span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Publicados</h3>
                        <span class="stat-number">
                            <?php
                            $stmt = $db->query("SELECT COUNT(*) FROM blog WHERE estado = 'publicado'");
                            echo $stmt->fetchColumn();
                            ?>
                        </span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-pen"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Borradores</h3>
                        <span class="stat-number">
                            <?php
                            $stmt = $db->query("SELECT COUNT(*) FROM blog WHERE estado = 'borrador'");
                            echo $stmt->fetchColumn();
                            ?>
                        </span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Vistas Totales</h3>
                        <span class="stat-number">
                            <?php
                            $stmt = $db->query("SELECT SUM(vistas) FROM blog");
                            echo $stmt->fetchColumn() ?: 0;
                            ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tabla de artículos -->
            <div class="table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Título</th>
                            <th>Imagen</th>
                            <th>Categoría</th>
                            <th>Autor</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th>Vistas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                        <tr>
                            <td>
                                <div class="post-title"><?php echo htmlspecialchars($post['titulo']); ?></div>
                                <div class="post-excerpt"><?php echo htmlspecialchars(substr($post['resumen'] ?: $post['contenido'], 0, 100)) . '...'; ?></div>
                                <div class="post-meta">
                                    <i class="fas fa-id-badge"></i> ID: <?php echo $post['id']; ?>
                                </div>
                            </td>
                            <td>
                                <div class="post-image">
                                    <?php if ($post['imagen_principal']): ?>
                                        <img src="../../assets/uploads/blog/<?php echo htmlspecialchars($post['imagen_principal']); ?>" 
                                             alt="<?php echo htmlspecialchars($post['titulo']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-image"></i>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($post['categoria_nombre'] ?: 'Sin categoría'); ?></td>
                            <td><?php echo htmlspecialchars($post['autor_nombre']); ?></td>
                            <td>
                                <?php echo date('d/m/Y', strtotime($post['fecha_creacion'])); ?><br>
                                <small style="color: var(--gray);"><?php echo date('H:i', strtotime($post['fecha_creacion'])); ?></small>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo $post['estado']; ?>">
                                    <?php echo ucfirst($post['estado']); ?>
                                </span>
                            </td>
                            <td>
                                <strong><?php echo $post['vistas']; ?></strong>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="editar.php?id=<?php echo $post['id']; ?>" class="btn btn-sm btn-secondary" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../../blog/post.php?id=<?php echo $post['id']; ?>" target="_blank" class="btn btn-sm btn-info" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <form method="POST" action="eliminar.php" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar este artículo?')">
                                        <input type="hidden" name="id" value="<?php echo $post['id']; ?>">
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

                <?php if (empty($posts)): ?>
                <div class="empty-state">
                    <i class="fas fa-newspaper"></i>
                    <h3>No se encontraron artículos</h3>
                    <p><?php echo $categoria_id || $estado || $search ? 'Intenta con otros filtros' : 'Comienza creando el primer artículo'; ?></p>
                    <a href="crear.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Crear Artículo
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
    </script>
</body>
</html>