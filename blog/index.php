<?php
require_once '../includes/config.php';

$db = getDB();

// Paginación
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 9;
$offset = ($current_page - 1) * $per_page;

// Filtros
$categoria_id = $_GET['categoria_id'] ?? '';
$search = $_GET['search'] ?? '';

$where = "WHERE b.estado = 'publicado'";
$params = [];
$params_count = [];

if ($categoria_id) {
    $where .= " AND b.categoria_id = ?";
    $params[] = $categoria_id;
    $params_count[] = $categoria_id;
}

if ($search) {
    $where .= " AND (b.titulo LIKE ? OR b.contenido LIKE ? OR b.resumen LIKE ? OR b.tags LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params_count[] = $search_term;
    $params_count[] = $search_term;
    $params_count[] = $search_term;
    $params_count[] = $search_term;
}

// Obtener total de posts
$count_query = "SELECT COUNT(*) as total FROM blog b $where";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute($params_count);
$total_posts = $count_stmt->fetch()['total'];
$total_pages = ceil($total_posts / $per_page);

// Obtener posts
$query = "SELECT b.*, u.nombre_completo as autor_nombre, c.nombre as categoria_nombre,
          DATE_FORMAT(b.fecha_publicacion, '%d/%m/%Y') as fecha_formateada
          FROM blog b 
          LEFT JOIN usuarios u ON b.autor_id = u.id 
          LEFT JOIN categorias_blog c ON b.categoria_id = c.id 
          $where 
          ORDER BY b.fecha_publicacion DESC 
          LIMIT ? OFFSET ?";

$stmt = $db->prepare($query);

// Agregar todos los parámetros de búsqueda
$param_index = 1;
foreach ($params as $param) {
    $stmt->bindValue($param_index, $param);
    $param_index++;
}

// Agregar LIMIT y OFFSET como enteros
$stmt->bindValue($param_index, $per_page, PDO::PARAM_INT);
$param_index++;
$stmt->bindValue($param_index, $offset, PDO::PARAM_INT);

$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener categorías
$categorias = [];
$stmt = $db->query("SELECT id, nombre, (SELECT COUNT(*) FROM blog WHERE categoria_id = categorias_blog.id AND estado = 'publicado') as total FROM categorias_blog WHERE activo = 1 ORDER BY nombre");
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Posts destacados
$stmt = $db->query("SELECT b.*, u.nombre_completo as autor_nombre, c.nombre as categoria_nombre FROM blog b LEFT JOIN usuarios u ON b.autor_id = u.id LEFT JOIN categorias_blog c ON b.categoria_id = c.id WHERE b.estado = 'publicado' AND b.destacado = 1 ORDER BY b.fecha_publicacion DESC LIMIT 3");
$destacados = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
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
            --border-radius: 16px;
            --shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.7;
            color: var(--text);
            background: var(--light);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header */
        .blog-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 100px 0 60px;
            text-align: center;
        }

        .blog-header h1 {
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            letter-spacing: -1px;
        }

        .blog-header p {
            font-size: 1.2rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto 2rem;
        }

        /* Blog Content */
        .blog-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 3rem;
            padding: 4rem 0;
        }

        @media (max-width: 992px) {
            .blog-content {
                grid-template-columns: 1fr;
            }
        }

        /* Filtros */
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
        }

        .search-box {
            position: relative;
            margin-bottom: 1.5rem;
        }

        .search-box input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            transition: var(--transition);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 90, 160, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray);
        }

        .category-filter {
            margin-bottom: 1.5rem;
        }

        .category-filter h3 {
            margin-bottom: 1rem;
            color: var(--primary);
            font-size: 1.1rem;
            font-weight: 600;
        }

        .category-list {
            list-style: none;
        }

        .category-list li {
            margin-bottom: 0.5rem;
        }

        .category-list a {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            color: var(--text);
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }

        .category-list a:hover {
            background: var(--light);
            color: var(--primary);
        }

        .category-list a.active {
            background: var(--primary);
            color: white;
        }

        .category-count {
            font-size: 0.85rem;
            background: var(--light);
            color: var(--gray);
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
        }

        .category-list a.active .category-count {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }

        /* Posts Grid */
        .posts-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .post-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }

        .post-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .post-image {
            height: 200px;
            background: var(--primary);
            position: relative;
            overflow: hidden;
        }

        .post-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .post-card:hover .post-image img {
            transform: scale(1.05);
        }

        .post-category {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: var(--primary);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .post-content {
            padding: 1.5rem;
        }

        .post-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray);
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .post-title {
            margin: 0 0 1rem 0;
            font-size: 1.25rem;
            font-weight: 700;
            line-height: 1.4;
        }

        .post-title a {
            color: var(--dark);
            text-decoration: none;
            transition: var(--transition);
        }

        .post-title a:hover {
            color: var(--primary);
        }

        .post-excerpt {
            color: var(--gray);
            margin-bottom: 1.5rem;
            line-height: 1.6;
        }

        .post-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-light);
            font-size: 0.9rem;
            color: var(--gray);
        }

        .post-author {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .post-views {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        /* Sidebar */
        .sidebar {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .sidebar-section {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .sidebar-title {
            margin-bottom: 1.5rem;
            color: var(--primary);
            font-size: 1.2rem;
            font-weight: 700;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light);
        }

        .featured-post {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .featured-post:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .featured-image {
            height: 150px;
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .featured-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .featured-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .featured-title a {
            color: var(--dark);
            text-decoration: none;
            transition: var(--transition);
        }

        .featured-title a:hover {
            color: var(--primary);
        }

        .featured-date {
            color: var(--gray);
            font-size: 0.85rem;
        }

        /* Paginación */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 3rem;
        }

        .pagination a, .pagination span {
            padding: 0.75rem 1.25rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            color: var(--text);
            border: 1px solid var(--gray-light);
            transition: var(--transition);
        }

        .pagination a:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination .active {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Empty State */
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

        /* Footer */
        .blog-footer {
            background: var(--dark);
            color: white;
            padding: 4rem 0;
            text-align: center;
            margin-top: 4rem;
        }

        .blog-footer a {
            color: white;
            text-decoration: none;
            transition: var(--transition);
        }

        .blog-footer a:hover {
            color: var(--secondary);
        }

        @media (max-width: 768px) {
            .blog-header h1 {
                font-size: 2.5rem;
            }
            
            .posts-grid {
                grid-template-columns: 1fr;
            }
            
            .blog-content {
                padding: 2rem 0;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="blog-header">
        <div class="container">
            <h1>Nuestro Blog</h1>
            <p>Reflexiones, noticias y enseñanzas para edificar tu fe y fortalecer nuestra comunidad</p>
        </div>
    </header>

    <!-- Blog Content -->
    <div class="container">
        <div class="blog-content">
            <!-- Main Content -->
            <main class="main-content">
                <!-- Filtros -->
                <div class="filters">
                    <form method="GET" class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" name="search" placeholder="Buscar artículos..." 
                               value="<?php echo htmlspecialchars($search); ?>">
                    </form>
                    
                    <div class="category-filter">
                        <h3>Categorías</h3>
                        <ul class="category-list">
                            <li>
                                <a href="?" class="<?php echo !$categoria_id ? 'active' : ''; ?>">
                                    Todas las categorías
                                    <span class="category-count"><?php echo $total_posts; ?></span>
                                </a>
                            </li>
                            <?php foreach ($categorias as $categoria): ?>
                            <li>
                                <a href="?categoria_id=<?php echo $categoria['id']; ?>" 
                                   class="<?php echo $categoria_id == $categoria['id'] ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                    <span class="category-count"><?php echo $categoria['total']; ?></span>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <!-- Posts Grid -->
                <?php if (empty($posts)): ?>
                    <div class="empty-state">
                        <i class="fas fa-newspaper"></i>
                        <h3>No se encontraron artículos</h3>
                        <p><?php echo $search || $categoria_id ? 'Intenta con otros filtros' : 'Próximamente tendremos contenido disponible'; ?></p>
                    </div>
                <?php else: ?>
                    <div class="posts-grid">
                        <?php foreach ($posts as $post): ?>
                        <article class="post-card">
                            <div class="post-image">
                                <?php if ($post['imagen_principal']): ?>
                                    <img src="../assets/uploads/blog/<?php echo htmlspecialchars($post['imagen_principal']); ?>" 
                                         alt="<?php echo htmlspecialchars($post['titulo']); ?>">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%); display: flex; align-items: center; justify-content: center; color: white;">
                                        <i class="fas fa-cross" style="font-size: 3rem; opacity: 0.5;"></i>
                                    </div>
                                <?php endif; ?>
                                <?php if ($post['categoria_nombre']): ?>
                                <span class="post-category"><?php echo htmlspecialchars($post['categoria_nombre']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="post-content">
                                <div class="post-date">
                                    <i class="far fa-calendar"></i>
                                    <?php echo $post['fecha_formateada']; ?>
                                </div>
                                <h3 class="post-title">
                                    <a href="post.php?id=<?php echo $post['id']; ?>">
                                        <?php echo htmlspecialchars($post['titulo']); ?>
                                    </a>
                                </h3>
                                <p class="post-excerpt">
                                    <?php echo htmlspecialchars(substr($post['resumen'] ?: $post['contenido'], 0, 150) . '...'); ?>
                                </p>
                                <div class="post-meta">
                                    <div class="post-author">
                                        <i class="far fa-user"></i>
                                        <?php echo htmlspecialchars($post['autor_nombre']); ?>
                                    </div>
                                    <div class="post-views">
                                        <i class="far fa-eye"></i>
                                        <?php echo $post['vistas']; ?>
                                    </div>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Paginación -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($current_page > 1): ?>
                        <a href="?page=<?php echo $current_page - 1; ?><?php echo $categoria_id ? '&categoria_id=' . $categoria_id : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span class="active"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?><?php echo $categoria_id ? '&categoria_id=' . $categoria_id : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?><?php echo $categoria_id ? '&categoria_id=' . $categoria_id : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </main>

            <!-- Sidebar -->
            <aside class="sidebar">
                <!-- Destacados -->
                <?php if (!empty($destacados)): ?>
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Artículos Destacados</h3>
                    <?php foreach ($destacados as $destacado): ?>
                    <div class="featured-post">
                        <div class="featured-image">
                            <?php if ($destacado['imagen_principal']): ?>
                                <img src="../assets/uploads/blog/<?php echo htmlspecialchars($destacado['imagen_principal']); ?>" 
                                     alt="<?php echo htmlspecialchars($destacado['titulo']); ?>">
                            <?php else: ?>
                                <div style="width: 100%; height: 100%; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);"></div>
                            <?php endif; ?>
                        </div>
                        <h4 class="featured-title">
                            <a href="post.php?id=<?php echo $destacado['id']; ?>">
                                <?php echo htmlspecialchars(substr($destacado['titulo'], 0, 60) . (strlen($destacado['titulo']) > 60 ? '...' : '')); ?>
                            </a>
                        </h4>
                        <div class="featured-date">
                            <?php echo date('d/m/Y', strtotime($destacado['fecha_publicacion'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Estadísticas -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Estadísticas</h3>
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--gray-light);">
                            <span>Total de artículos:</span>
                            <strong style="color: var(--primary);"><?php echo $total_posts; ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0; border-bottom: 1px solid var(--gray-light);">
                            <span>Categorías:</span>
                            <strong style="color: var(--primary);"><?php echo count($categorias); ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.75rem 0;">
                            <span>Destacados:</span>
                            <strong style="color: var(--primary);"><?php echo count($destacados); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- Información -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Sobre el Blog</h3>
                    <p style="color: var(--gray); line-height: 1.6;">
                        Este blog es un espacio para compartir reflexiones, noticias y enseñanzas 
                        que edifican nuestra fe y fortalecen nuestra comunidad cristiana.
                    </p>
                    <div style="margin-top: 1.5rem;">
                        <a href="../" class="btn" style="display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; background: var(--primary); color: white; text-decoration: none; border-radius: var(--border-radius); font-weight: 600; transition: var(--transition);">
                            <i class="fas fa-home"></i> Volver al Inicio
                        </a>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- Footer -->
    <footer class="blog-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.</p>
            <p style="margin-top: 1rem; opacity: 0.8;">
                <a href="../">Inicio</a> | 
                <a href="?">Blog</a> | 
                <a href="../#contacto">Contacto</a>
            </p>
        </div>
    </footer>

    <script>
        // Búsqueda en tiempo real
        const searchInput = document.querySelector('input[name="search"]');
        let searchTimeout;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.closest('form').submit();
            }, 500);
        });
        
        // Contador de vistas al hacer hover
        document.querySelectorAll('.post-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                const viewsElement = this.querySelector('.post-views');
                if (viewsElement) {
                    const currentViews = parseInt(viewsElement.textContent.match(/\d+/)[0]);
                    viewsElement.innerHTML = `<i class="far fa-eye"></i> ${currentViews + 1}`;
                }
            });
        });
    </script>
</body>
</html>