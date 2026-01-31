<?php
require_once '../includes/config.php';

$db = getDB();

// Obtener ID del post
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: index.php');
    exit;
}

// Obtener post
$stmt = $db->prepare("SELECT b.*, u.nombre_completo as autor_nombre, u.email as autor_email, c.nombre as categoria_nombre,
                      DATE_FORMAT(b.fecha_publicacion, '%d de %M de %Y') as fecha_formateada
                      FROM blog b 
                      LEFT JOIN usuarios u ON b.autor_id = u.id 
                      LEFT JOIN categorias_blog c ON b.categoria_id = c.id 
                      WHERE b.id = ? AND b.estado = 'publicado'");
$stmt->execute([$id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    header('Location: index.php');
    exit;
}

// Incrementar contador de vistas
$stmt = $db->prepare("UPDATE blog SET vistas = vistas + 1 WHERE id = ?");
$stmt->execute([$id]);

// Obtener posts relacionados (misma categoría)
$related_posts = [];
if ($post['categoria_id']) {
    $stmt = $db->prepare("SELECT b.id, b.titulo, b.imagen_principal, b.resumen,
                          DATE_FORMAT(b.fecha_publicacion, '%d/%m/%Y') as fecha_formateada
                          FROM blog b 
                          WHERE b.categoria_id = ? AND b.estado = 'publicado' AND b.id != ?
                          ORDER BY b.fecha_publicacion DESC 
                          LIMIT 3");
    $stmt->execute([$post['categoria_id'], $id]);
    $related_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Obtener posts recientes
$stmt = $db->prepare("SELECT b.id, b.titulo, b.imagen_principal, 
                      DATE_FORMAT(b.fecha_publicacion, '%d/%m/%Y') as fecha_formateada
                      FROM blog b 
                      WHERE b.estado = 'publicado' AND b.id != ?
                      ORDER BY b.fecha_publicacion DESC 
                      LIMIT 5");
$stmt->execute([$id]);
$recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Función para convertir fecha a español
function fechaEnEspanol($fecha) {
    if (empty($fecha)) return '';
    
    // Definir meses en español
    $meses = [
        'January' => 'enero',
        'February' => 'febrero',
        'March' => 'marzo',
        'April' => 'abril',
        'May' => 'mayo',
        'June' => 'junio',
        'July' => 'julio',
        'August' => 'agosto',
        'September' => 'septiembre',
        'October' => 'octubre',
        'November' => 'noviembre',
        'December' => 'diciembre'
    ];
    
    // Extraer partes de la fecha
    $fecha_obj = new DateTime($fecha);
    $dia = $fecha_obj->format('d');
    $mes_ingles = $fecha_obj->format('F');
    $ano = $fecha_obj->format('Y');
    
    // Convertir mes a español
    $mes_espanol = $meses[$mes_ingles] ?? $mes_ingles;
    
    return "$dia de $mes_espanol de $ano";
}

// Convertir la fecha del post a español
$fecha_espanol = fechaEnEspanol($post['fecha_publicacion']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['titulo']); ?> - <?php echo SITE_NAME; ?></title>
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

        /* Post Header */
        .post-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 80px 0 40px;
            margin-bottom: 3rem;
        }

        .post-header-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .post-meta {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .post-meta span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .post-category {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            text-decoration: none;
            color: white;
            transition: var(--transition);
        }

        .post-category:hover {
            background: rgba(255, 255, 255, 0.3);
        }

        .post-title {
            font-size: 3rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            letter-spacing: -0.5px;
        }

        .post-subtitle {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .post-author {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .author-avatar {
            width: 60px;
            height: 60px;
            background: white;
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .author-info h4 {
            margin: 0 0 0.25rem 0;
            font-size: 1.1rem;
        }

        .author-info p {
            margin: 0;
            opacity: 0.8;
            font-size: 0.9rem;
        }

        /* Post Content */
        .post-content-wrapper {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 3rem;
            padding-bottom: 4rem;
        }

        @media (max-width: 992px) {
            .post-content-wrapper {
                grid-template-columns: 1fr;
            }
        }

        /* Main Content */
        .post-main {
            background: white;
            border-radius: var(--border-radius);
            padding: 3rem;
            box-shadow: var(--shadow);
        }

        .post-image {
            width: 100%;
            height: 400px;
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 2.5rem;
        }

        .post-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .post-content {
            font-size: 1.1rem;
            line-height: 1.8;
        }

        .post-content h2,
        .post-content h3,
        .post-content h4 {
            margin: 2rem 0 1rem;
            color: var(--primary);
            font-weight: 700;
        }

        .post-content h2 {
            font-size: 2rem;
        }

        .post-content h3 {
            font-size: 1.5rem;
        }

        .post-content p {
            margin-bottom: 1.5rem;
        }

        .post-content ul,
        .post-content ol {
            margin: 1.5rem 0;
            padding-left: 2rem;
        }

        .post-content li {
            margin-bottom: 0.5rem;
        }

        .post-content blockquote {
            border-left: 4px solid var(--primary);
            padding: 1rem 1.5rem;
            margin: 2rem 0;
            background: var(--light);
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
            font-style: italic;
        }

        .post-content img {
            max-width: 100%;
            height: auto;
            border-radius: var(--border-radius);
            margin: 1.5rem 0;
        }

        .post-stats {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-light);
            color: var(--gray);
            font-size: 0.9rem;
        }

        .post-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .tag {
            background: var(--light);
            color: var(--gray);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            text-decoration: none;
            transition: var(--transition);
        }

        .tag:hover {
            background: var(--primary);
            color: white;
        }

        /* Sidebar */
        .post-sidebar {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .sidebar-widget {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }

        .widget-title {
            margin-bottom: 1.5rem;
            color: var(--primary);
            font-size: 1.2rem;
            font-weight: 700;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--light);
        }

        .related-post {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .related-post:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .related-image {
            height: 120px;
            border-radius: var(--border-radius);
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .related-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .related-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }

        .related-title a {
            color: var(--dark);
            text-decoration: none;
            transition: var(--transition);
        }

        .related-title a:hover {
            color: var(--primary);
        }

        .related-date {
            color: var(--gray);
            font-size: 0.85rem;
        }

        .recent-post {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }

        .recent-post:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .recent-image {
            width: 80px;
            height: 60px;
            border-radius: 8px;
            overflow: hidden;
            flex-shrink: 0;
        }

        .recent-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .recent-info {
            flex: 1;
        }

        .recent-title {
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            line-height: 1.4;
        }

        .recent-title a {
            color: var(--dark);
            text-decoration: none;
            transition: var(--transition);
        }

        .recent-title a:hover {
            color: var(--primary);
        }

        .recent-date {
            color: var(--gray);
            font-size: 0.8rem;
        }

        /* Share Buttons */
        .share-buttons {
            display: flex;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .share-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: var(--transition);
        }

        .share-btn:hover {
            transform: translateY(-3px);
        }

        .facebook { background: #3b5998; }
        .twitter { background: #1da1f2; }
        .whatsapp { background: #25d366; }
        .linkedin { background: #0077b5; }

        /* Navigation */
        .post-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-light);
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 1.5rem;
            background: white;
            border-radius: var(--border-radius);
            text-decoration: none;
            color: var(--dark);
            box-shadow: var(--shadow);
            transition: var(--transition);
            max-width: 300px;
        }

        .nav-link:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-hover);
            color: var(--primary);
        }

        .nav-link i {
            font-size: 1.5rem;
        }

        .nav-prev {
            text-align: left;
        }

        .nav-next {
            text-align: right;
        }

        .nav-link span {
            font-size: 0.9rem;
            color: var(--gray);
            margin-bottom: 0.25rem;
        }

        .nav-link strong {
            display: block;
            line-height: 1.4;
        }

        /* Footer */
        .post-footer {
            background: var(--dark);
            color: white;
            padding: 4rem 0;
            margin-top: 4rem;
            text-align: center;
        }

        .post-footer a {
            color: white;
            text-decoration: none;
            transition: var(--transition);
        }

        .post-footer a:hover {
            color: var(--secondary);
        }

        @media (max-width: 768px) {
            .post-header {
                padding: 60px 0 30px;
            }
            
            .post-title {
                font-size: 2rem;
            }
            
            .post-subtitle {
                font-size: 1.1rem;
            }
            
            .post-main {
                padding: 2rem;
            }
            
            .post-image {
                height: 250px;
            }
            
            .post-navigation {
                flex-direction: column;
                gap: 1rem;
            }
            
            .nav-link {
                max-width: 100%;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <!-- Post Header -->
    <header class="post-header">
        <div class="container">
            <div class="post-header-content">
                <div class="post-meta">
                    <?php if ($post['categoria_nombre']): ?>
                    <a href="../blog/?categoria_id=<?php echo $post['categoria_id']; ?>" class="post-category">
                        <?php echo htmlspecialchars($post['categoria_nombre']); ?>
                    </a>
                    <?php endif; ?>
                    <span><i class="far fa-calendar"></i> <?php echo $fecha_espanol; ?></span>
                    <span><i class="far fa-eye"></i> <?php echo $post['vistas'] + 1; ?> vistas</span>
                </div>
                
                <h1 class="post-title"><?php echo htmlspecialchars($post['titulo']); ?></h1>
                
                <?php if ($post['resumen']): ?>
                <p class="post-subtitle"><?php echo htmlspecialchars($post['resumen']); ?></p>
                <?php endif; ?>
                
                <div class="post-author">
                    <div class="author-avatar">
                        <?php echo strtoupper(substr($post['autor_nombre'], 0, 1)); ?>
                    </div>
                    <div class="author-info">
                        <h4><?php echo htmlspecialchars($post['autor_nombre']); ?></h4>
                        <p>Autor</p>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Post Content -->
    <div class="container">
        <div class="post-content-wrapper">
            <!-- Main Content -->
            <main class="post-main">
                <?php if ($post['imagen_principal']): ?>
                <div class="post-image">
                    <img src="../assets/uploads/blog/<?php echo htmlspecialchars($post['imagen_principal']); ?>" 
                         alt="<?php echo htmlspecialchars($post['titulo']); ?>">
                </div>
                <?php endif; ?>
                
                <div class="post-content">
                    <?php echo $post['contenido']; ?>
                </div>
                
                <?php 
                // Verificar si la columna 'tags' existe y tiene contenido
                if (isset($post['tags']) && !empty($post['tags'])):
                ?>
                <div class="post-tags">
                    <?php 
                    $tags = explode(',', $post['tags']);
                    foreach ($tags as $tag):
                        $tag = trim($tag);
                        if ($tag):
                    ?>
                    <a href="../blog/?search=<?php echo urlencode($tag); ?>" class="tag">
                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($tag); ?>
                    </a>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
                <?php endif; ?>
                
                <div class="post-stats">
                    <div>
                        <i class="far fa-eye"></i> <?php echo $post['vistas'] + 1; ?> vistas
                    </div>
                    <div>
                        <i class="far fa-calendar"></i> Publicado el <?php echo $fecha_espanol; ?>
                    </div>
                </div>
                
                <!-- Share Buttons -->
                <div class="share-buttons">
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" 
                       target="_blank" class="share-btn facebook" title="Compartir en Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?php echo urlencode("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>&text=<?php echo urlencode($post['titulo']); ?>" 
                       target="_blank" class="share-btn twitter" title="Compartir en Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="https://api.whatsapp.com/send?text=<?php echo urlencode($post['titulo'] . " - " . "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>" 
                       target="_blank" class="share-btn whatsapp" title="Compartir en WhatsApp">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                    <a href="https://www.linkedin.com/shareArticle?mini=true&url=<?php echo urlencode("https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"); ?>&title=<?php echo urlencode($post['titulo']); ?>" 
                       target="_blank" class="share-btn linkedin" title="Compartir en LinkedIn">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
                
                <!-- Navigation -->
                <div class="post-navigation">
                    <a href="index.php" class="nav-link nav-prev">
                        <i class="fas fa-arrow-left"></i>
                        <div>
                            <span>Volver al blog</span>
                            <strong>Ver todos los artículos</strong>
                        </div>
                    </a>
                    
                    <a href="../" class="nav-link nav-next">
                        <div>
                            <span>Ir al inicio</span>
                            <strong>Página principal</strong>
                        </div>
                        <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </main>

            <!-- Sidebar -->
            <aside class="post-sidebar">
                <!-- Posts Relacionados -->
                <?php if (!empty($related_posts)): ?>
                <div class="sidebar-widget">
                    <h3 class="widget-title">Artículos Relacionados</h3>
                    <?php foreach ($related_posts as $related): ?>
                    <div class="related-post">
                        <div class="related-image">
                            <?php if ($related['imagen_principal']): ?>
                                <img src="../assets/uploads/blog/<?php echo htmlspecialchars($related['imagen_principal']); ?>" 
                                     alt="<?php echo htmlspecialchars($related['titulo']); ?>">
                            <?php else: ?>
                                <div style="width: 100%; height: 100%; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);"></div>
                            <?php endif; ?>
                        </div>
                        <h4 class="related-title">
                            <a href="post.php?id=<?php echo $related['id']; ?>">
                                <?php echo htmlspecialchars(substr($related['titulo'], 0, 60) . (strlen($related['titulo']) > 60 ? '...' : '')); ?>
                            </a>
                        </h4>
                        <div class="related-date">
                            <?php echo $related['fecha_formateada']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Posts Recientes -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">Artículos Recientes</h3>
                    <?php foreach ($recent_posts as $recent): ?>
                    <div class="recent-post">
                        <div class="recent-image">
                            <?php if ($recent['imagen_principal']): ?>
                                <img src="../assets/uploads/blog/<?php echo htmlspecialchars($recent['imagen_principal']); ?>" 
                                     alt="<?php echo htmlspecialchars($recent['titulo']); ?>">
                            <?php else: ?>
                                <div style="width: 100%; height: 100%; background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);"></div>
                            <?php endif; ?>
                        </div>
                        <div class="recent-info">
                            <h4 class="recent-title">
                                <a href="post.php?id=<?php echo $recent['id']; ?>">
                                    <?php echo htmlspecialchars(substr($recent['titulo'], 0, 50) . (strlen($recent['titulo']) > 50 ? '...' : '')); ?>
                                </a>
                            </h4>
                            <div class="recent-date">
                                <?php echo $recent['fecha_formateada']; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Información -->
                <div class="sidebar-widget">
                    <h3 class="widget-title">Acerca del Autor</h3>
                    <div style="display: flex; flex-direction: column; align-items: center; text-align: center; gap: 1rem;">
                        <div class="author-avatar" style="width: 80px; height: 80px; font-size: 2rem;">
                            <?php echo strtoupper(substr($post['autor_nombre'], 0, 1)); ?>
                        </div>
                        <div>
                            <h4 style="margin: 0 0 0.5rem 0;"><?php echo htmlspecialchars($post['autor_nombre']); ?></h4>
                            <p style="color: var(--gray); font-size: 0.9rem; margin: 0;">
                                Miembro de <?php echo SITE_NAME; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <!-- Footer -->
    <footer class="post-footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.</p>
            <p style="margin-top: 1rem; opacity: 0.8;">
                <a href="../">Inicio</a> | 
                <a href="index.php">Blog</a> | 
                <a href="../#contacto">Contacto</a>
            </p>
        </div>
    </footer>

    <script>
        // Resaltar enlaces internos
        document.querySelectorAll('.post-content a').forEach(link => {
            if (link.href.includes(window.location.hostname)) {
                link.style.color = 'var(--primary)';
                link.style.fontWeight = '600';
            }
        });
        
        // Smooth scroll para encabezados
        document.querySelectorAll('.post-content h2, .post-content h3').forEach(header => {
            header.style.scrollMarginTop = '80px';
        });
    </script>
</body>
</html>