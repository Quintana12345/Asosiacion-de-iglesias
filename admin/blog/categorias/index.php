<?php
// SOLUCIÓN PARA RUTAS - Intenta diferentes rutas
$config_paths = [
    '../../../includes/config.php',  // 3 niveles arriba
    '../../../../includes/config.php', // 4 niveles arriba
    '../../includes/config.php',     // 2 niveles arriba
];

$config_loaded = false;
foreach ($config_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $config_loaded = true;
        break;
    }
}

if (!$config_loaded) {
    die("Error: No se pudo encontrar config.php. Verifica la ruta del archivo.");
}

require_once '../../../includes/auth.php';
requireAdmin();

$db = getDB();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'crear':
                    $nombre = trim($_POST['nombre'] ?? '');
                    $descripcion = trim($_POST['descripcion'] ?? '');
                    
                    if ($nombre) {
                        $stmt = $db->prepare("INSERT INTO categorias_blog (nombre, descripcion) VALUES (?, ?)");
                        $stmt->execute([$nombre, $descripcion]);
                        $_SESSION['success_msg'] = 'Categoría creada exitosamente';
                    }
                    break;
                    
                case 'editar':
                    $id = $_POST['id'] ?? 0;
                    $nombre = trim($_POST['nombre'] ?? '');
                    $descripcion = trim($_POST['descripcion'] ?? '');
                    
                    if ($id && $nombre) {
                        $stmt = $db->prepare("UPDATE categorias_blog SET nombre = ?, descripcion = ? WHERE id = ?");
                        $stmt->execute([$nombre, $descripcion, $id]);
                        $_SESSION['success_msg'] = 'Categoría actualizada exitosamente';
                    }
                    break;
                    
                case 'eliminar':
                    $id = $_POST['id'] ?? 0;
                    
                    if ($id) {
                        // Verificar si hay posts usando esta categoría
                        $stmt = $db->prepare("SELECT COUNT(*) FROM blog WHERE categoria_id = ?");
                        $stmt->execute([$id]);
                        $count = $stmt->fetchColumn();
                        
                        if ($count == 0) {
                            $stmt = $db->prepare("DELETE FROM categorias_blog WHERE id = ?");
                            $stmt->execute([$id]);
                            $_SESSION['success_msg'] = 'Categoría eliminada exitosamente';
                        } else {
                            $_SESSION['error_msg'] = 'No se puede eliminar la categoría porque tiene artículos asociados';
                        }
                    }
                    break;
                    
                case 'toggle_estado':
                    $id = $_POST['id'] ?? 0;
                    
                    if ($id) {
                        $stmt = $db->prepare("UPDATE categorias_blog SET activo = NOT activo WHERE id = ?");
                        $stmt->execute([$id]);
                        $_SESSION['success_msg'] = 'Estado de categoría actualizado';
                    }
                    break;
            }
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = 'Error: ' . $e->getMessage();
        }
    }
}

// Obtener categorías
$stmt = $db->query("SELECT * FROM categorias_blog ORDER BY nombre");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener conteo de posts por categoría
$posts_por_categoria = [];
$stmt = $db->query("SELECT categoria_id, COUNT(*) as total FROM blog GROUP BY categoria_id");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $posts_por_categoria[$row['categoria_id']] = $row['total'];
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
    <title>Categorías del Blog - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        <?php 
        // Verificar ruta del CSS
        $css_paths = [
            '../../admin-styles.css',
            '../../../admin-styles.css',
            '../../admin/admin-styles.css'
        ];
        
        $css_loaded = false;
        foreach ($css_paths as $css_path) {
            if (file_exists($css_path)) {
                include $css_path;
                $css_loaded = true;
                break;
            }
        }
        
        if (!$css_loaded): ?>
        /* Estilos básicos si no encuentra el archivo CSS */
        :root {
            --primary: #2c5aa0;
            --primary-dark: #1e3d72;
            --primary-light: #3a6bc5;
            --secondary: #f39c12;
            --accent: #27ae60;
            --light: #f8fafc;
            --dark: #1e293b;
            --text: #334155;
            --gray: #64748b;
            --gray-light: #e2e8f0;
            --border-radius: 12px;
            --box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            --transition: all 0.3s ease;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            color: var(--text);
            line-height: 1.6;
            display: flex;
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1.5rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar-header {
            padding: 0 1.5rem 1.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar-header h2 {
            margin: 0;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .sidebar-header p {
            margin: 0.25rem 0 0;
            opacity: 0.8;
            font-size: 0.85rem;
        }
        
        .sidebar-nav {
            padding: 1.5rem 0;
        }
        
        .nav-section {
            margin-bottom: 1.5rem;
        }
        
        .nav-section h3 {
            padding: 0 1.5rem 0.5rem;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.7;
            font-weight: 600;
            margin-bottom: 0.5rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .nav-section ul {
            list-style: none;
        }
        
        .nav-item a {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .nav-item a:hover {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        
        .nav-item.active a {
            background: rgba(255,255,255,0.15);
            color: white;
            border-right: 3px solid var(--secondary);
        }
        
        .nav-item i {
            width: 20px;
            text-align: center;
        }
        
        .sidebar-footer {
            margin-top: auto;
            padding: 1.5rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: white;
            text-decoration: none;
            padding: 0.75rem;
            border-radius: var(--border-radius);
            background: rgba(255,255,255,0.1);
            transition: var(--transition);
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .main-content {
            flex: 1;
            margin-left: 250px;
        }
        
        .content-header {
            background: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--gray-light);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: var(--gray);
            cursor: pointer;
            padding: 0.5rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .sidebar-toggle:hover {
            background: var(--gray-light);
            color: var(--dark);
        }
        
        .content-header h1 {
            margin: 0;
            font-size: 1.5rem;
            color: var(--dark);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            display: block;
            font-weight: 600;
            color: var(--dark);
        }
        
        .user-role {
            display: block;
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: var(--gray-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray);
            font-size: 1.25rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--border-radius);
            font-family: inherit;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(44, 90, 160, 0.2);
        }
        
        .btn-secondary {
            background: var(--gray-light);
            color: var(--dark);
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-warning {
            background: #f39c12;
            color: white;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            border-radius: var(--border-radius);
        }
        
        .btn-info {
            background: #17a2b8;
            color: white;
        }
        <?php endif; ?>
        
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
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
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
            width: 100%;
            max-width: 500px;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }
        
        .modal-header h3 {
            margin: 0;
            color: var(--primary);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-family: inherit;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 90, 160, 0.1);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-light);
        }
        
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .category-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid var(--gray-light);
            transition: var(--transition);
        }
        
        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }
        
        .category-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .category-title {
            margin: 0;
            color: var(--primary);
            font-size: 1.25rem;
        }
        
        .category-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-active {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        
        .category-description {
            color: var(--gray);
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        .category-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: var(--gray);
        }
        
        .category-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
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
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 1000;
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .content-container {
                padding: 1rem;
            }
            
            .toolbar {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .categories-grid {
                grid-template-columns: 1fr;
            }
            
            .modal-content {
                margin: 1rem;
                padding: 1.5rem;
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
                        <a href="../../dashboard.php">
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
                        <a href="../../usuarios/">
                            <i class="fas fa-users"></i>
                            <span>Usuarios</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../../zonas/">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Zonas</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../../sectores/">
                            <i class="fas fa-layer-group"></i>
                            <span>Sectores</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../../iglesias/">
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
                        <a href="../../comisiones/">
                            <i class="fas fa-tasks"></i>
                            <span>Comisiones</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../">
                            <i class="fas fa-blog"></i>
                            <span>Blog</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../../eventos/">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Eventos</span>
                        </a>
                    </li>
                    <li class="nav-item active">
                        <a href="./">
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
                <h1>Categorías del Blog</h1>
            </div>
            <div class="header-right">
                <div class="user-menu">
                    <div class="user-info">
                        <span class="user-name">
                            <?php 
                            // CORREGIDO - Verifica múltiples posibilidades
                            if (isset($_SESSION['user_nombre']) && !empty($_SESSION['user_nombre'])) {
                                echo htmlspecialchars($_SESSION['user_nombre']);
                            } elseif (isset($_SESSION['nombre_completo']) && !empty($_SESSION['nombre_completo'])) {
                                echo htmlspecialchars($_SESSION['nombre_completo']);
                            } elseif (isset($_SESSION['user']['nombre_completo']) && !empty($_SESSION['user']['nombre_completo'])) {
                                echo htmlspecialchars($_SESSION['user']['nombre_completo']);
                            } else {
                                echo 'Administrador';
                            }
                            ?>
                        </span>
                        <span class="user-role">
                            <?php 
                            // CORREGIDO - Verifica múltiples posibilidades
                            if (isset($_SESSION['user_cargo']) && !empty($_SESSION['user_cargo'])) {
                                echo htmlspecialchars($_SESSION['user_cargo']);
                            } elseif (isset($_SESSION['cargo']) && !empty($_SESSION['cargo'])) {
                                echo htmlspecialchars($_SESSION['cargo']);
                            } elseif (isset($_SESSION['user']['rol']) && !empty($_SESSION['user']['rol'])) {
                                echo htmlspecialchars($_SESSION['user']['rol']);
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
            
            <!-- Barra de herramientas -->
            <div class="toolbar">
                <div class="toolbar-left">
                    <h3 style="margin: 0;">Administrar Categorías</h3>
                </div>
                <div class="toolbar-right">
                    <button class="btn btn-primary" onclick="openModal('crear')">
                        <i class="fas fa-plus"></i>
                        Nueva Categoría
                    </button>
                </div>
            </div>
            
            <!-- Lista de categorías -->
            <?php if (empty($categorias)): ?>
                <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <h3>No hay categorías</h3>
                    <p>Crea tu primera categoría para organizar los artículos del blog</p>
                    <button class="btn btn-primary" onclick="openModal('crear')">
                        <i class="fas fa-plus"></i>
                        Crear Categoría
                    </button>
                </div>
            <?php else: ?>
                <div class="categories-grid">
                    <?php foreach ($categorias as $categoria): ?>
                    <div class="category-card">
                        <div class="category-header">
                            <h3 class="category-title"><?php echo htmlspecialchars($categoria['nombre']); ?></h3>
                            <span class="category-badge <?php echo $categoria['activo'] ? 'badge-active' : 'badge-inactive'; ?>">
                                <?php echo $categoria['activo'] ? 'Activa' : 'Inactiva'; ?>
                            </span>
                        </div>
                        
                        <?php if ($categoria['descripcion']): ?>
                        <div class="category-description">
                            <?php echo htmlspecialchars($categoria['descripcion']); ?>
                        </div>
                        <?php endif; ?>
                        
                        <div class="category-stats">
                            <span>
                                <i class="fas fa-newspaper"></i>
                                <?php echo $posts_por_categoria[$categoria['id']] ?? 0; ?> artículos
                            </span>
                            <span>
                                <i class="fas fa-calendar"></i>
                                <?php echo date('d/m/Y', strtotime($categoria['fecha_creacion'])); ?>
                            </span>
                        </div>
                        
                        <div class="category-actions">
                            <button class="btn btn-sm btn-secondary" onclick="openModal('editar', <?php echo $categoria['id']; ?>, '<?php echo htmlspecialchars($categoria['nombre']); ?>', '<?php echo htmlspecialchars($categoria['descripcion']); ?>')">
                                <i class="fas fa-edit"></i> Editar
                            </button>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle_estado">
                                <input type="hidden" name="id" value="<?php echo $categoria['id']; ?>">
                                <button type="submit" class="btn btn-sm <?php echo $categoria['activo'] ? 'btn-warning' : 'btn-success'; ?>">
                                    <i class="fas fa-power-off"></i> <?php echo $categoria['activo'] ? 'Desactivar' : 'Activar'; ?>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar esta categoría?')">
                                <input type="hidden" name="action" value="eliminar">
                                <input type="hidden" name="id" value="<?php echo $categoria['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i> Eliminar
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para crear/editar categorías -->
    <div class="modal" id="categoryModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalTitle">Nueva Categoría</h3>
            </div>
            <form method="POST" id="categoryForm">
                <input type="hidden" name="action" id="formAction" value="crear">
                <input type="hidden" name="id" id="formId" value="">
                
                <div class="form-group">
                    <label for="modalNombre">Nombre *</label>
                    <input type="text" id="modalNombre" name="nombre" class="form-control" required maxlength="100">
                </div>
                
                <div class="form-group">
                    <label for="modalDescripcion">Descripción (opcional)</label>
                    <textarea id="modalDescripcion" name="descripcion" class="form-control" rows="4" maxlength="500"></textarea>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar
                    </button>
                </div>
            </form>
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
        
        // Modal functions
        function openModal(action, id = null, nombre = '', descripcion = '') {
            const modal = document.getElementById('categoryModal');
            const modalTitle = document.getElementById('modalTitle');
            const formAction = document.getElementById('formAction');
            const formId = document.getElementById('formId');
            const modalNombre = document.getElementById('modalNombre');
            const modalDescripcion = document.getElementById('modalDescripcion');
            
            if (action === 'crear') {
                modalTitle.textContent = 'Nueva Categoría';
                formAction.value = 'crear';
                formId.value = '';
                modalNombre.value = '';
                modalDescripcion.value = '';
            } else if (action === 'editar') {
                modalTitle.textContent = 'Editar Categoría';
                formAction.value = 'editar';
                formId.value = id;
                modalNombre.value = nombre;
                modalDescripcion.value = descripcion;
            }
            
            modal.classList.add('active');
            modalNombre.focus();
        }
        
        function closeModal() {
            const modal = document.getElementById('categoryModal');
            modal.classList.remove('active');
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('categoryModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Cerrar modal con Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>