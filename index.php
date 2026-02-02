<?php
require_once 'includes/config.php';

// Obtener entradas recientes del blog
$db = getDB();
$query = "SELECT b.*, u.nombre_completo as autor, c.nombre as categoria 
          FROM blog b 
          LEFT JOIN usuarios u ON b.autor_id = u.id 
          LEFT JOIN categorias_blog c ON b.categoria_id = c.id 
          WHERE b.estado = 'publicado' 
          ORDER BY b.fecha_publicacion DESC 
          LIMIT 3";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Unidos en Fe</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        :root {
            --primary: #2c5aa0;
            --primary-dark: #1e3d72;
            --primary-light: #3a6bc5;
            --secondary: #f39c12;
            --secondary-dark: #e67e22;
            --accent: #27ae60;
            --accent-light: #2ecc71;
            --light: #f8fafc;
            --light-gray: #f1f5f9;
            --dark: #1e293b;
            --darker: #0f172a;
            --text: #334155;
            --text-light: #64748b;
            --text-lighter: #94a3b8;
            --white: #ffffff;
            --shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
            --shadow-hover: 0 20px 40px rgba(0, 0, 0, 0.12);
            --shadow-light: 0 4px 12px rgba(0, 0, 0, 0.05);
            --gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --gradient-secondary: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            --gradient-accent: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
            --border-radius: 16px;
            --border-radius-sm: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
            overflow-x: hidden;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            color: var(--text);
            background: var(--white);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 10% 20%, rgba(44, 90, 160, 0.02) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(243, 156, 18, 0.02) 0%, transparent 20%);
            z-index: -1;
            pointer-events: none;
        }
        
        /* Header Moderno y Compacto */
        .header {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            padding: 1.2rem 0;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: var(--shadow-light);
            transition: var(--transition);
        }
        
        .header.scrolled {
            padding: 1rem 0;
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }
        
        .nav-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo-container {
            display: flex;
            align-items: center;
            gap: 1rem;
            text-decoration: none;
        }
        
        .logo-icon {
            width: 50px;
            height: 50px;
            background: var(--gradient);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.5rem;
            box-shadow: 0 6px 20px rgba(44, 90, 160, 0.2);
            transition: var(--transition);
        }
        
        .logo-container:hover .logo-icon {
            transform: scale(1.05) rotate(5deg);
        }
        
        .logo-text {
            display: flex;
            flex-direction: column;
        }
        
        .logo-text h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.3px;
            line-height: 1.2;
        }
        
        .logo-text span {
            font-size: 0.8rem;
            color: var(--text-light);
            font-weight: 500;
            letter-spacing: 0.5px;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 0.5rem;
            align-items: center;
        }
        
        .nav-link {
            color: var(--text);
            text-decoration: none;
            font-weight: 600;
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            transition: var(--transition);
            font-size: 0.9rem;
            position: relative;
        }
        
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: var(--gradient);
            border-radius: 1px;
            transition: var(--transition);
        }
        
        .nav-link:hover::after {
            width: 70%;
        }
        
        .nav-link:hover {
            color: var(--primary);
            background: rgba(44, 90, 160, 0.05);
        }
        
        .nav-cta {
            background: var(--gradient);
            color: var(--white) !important;
            padding: 0.7rem 1.5rem !important;
            margin-left: 0.5rem;
            box-shadow: 0 6px 20px rgba(44, 90, 160, 0.2);
        }
        
        .nav-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(44, 90, 160, 0.3);
        }
        
        /* Hero Moderno */
        .hero-section {
            min-height: 90vh;
            position: relative;
            display: flex;
            align-items: center;
            padding: 8rem 0 4rem;
            background: var(--gradient);
            overflow: hidden;
        }
        
        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 30%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(255, 255, 255, 0.05) 0%, transparent 50%);
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 900px;
            margin: 0 auto;
            text-align: center;
            padding: 0 2rem;
        }
        
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            padding: 0.6rem 1.5rem;
            border-radius: 50px;
            margin-bottom: 2rem;
            font-weight: 600;
            color: var(--white);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 0.9rem;
        }
        
        .hero-title {
            font-family: 'Poppins', sans-serif;
            font-size: 3.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: var(--white);
            line-height: 1.1;
            letter-spacing: -0.5px;
        }
        
        .hero-subtitle {
            font-size: 1.2rem;
            margin-bottom: 3rem;
            color: rgba(255, 255, 255, 0.9);
            line-height: 1.6;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* Botones Modernos */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2.5rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            transition: var(--transition);
            border: none;
            font-size: 1rem;
            cursor: pointer;
        }
        
        .btn-primary {
            background: var(--gradient-secondary);
            color: var(--white);
            box-shadow: 0 8px 25px rgba(243, 156, 18, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(243, 156, 18, 0.4);
        }
        
        .btn-outline {
            background: rgba(255, 255, 255, 0.1);
            color: var(--white);
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }
        
        .btn-outline:hover {
            background: var(--white);
            color: var(--primary);
            border-color: var(--white);
            transform: translateY(-3px);
        }
        
        /* Secciones */
        .section {
            padding: 6rem 0;
            position: relative;
        }
        
        .section-alt {
            background: var(--light);
        }
        
        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
        }
        
        .section-badge {
            display: inline-block;
            background: var(--gradient);
            color: var(--white);
            padding: 0.6rem 1.8rem;
            border-radius: 50px;
            font-weight: 700;
            margin-bottom: 1.5rem;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
            box-shadow: 0 6px 20px rgba(44, 90, 160, 0.15);
        }
        
        .section-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2.8rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: var(--dark);
            line-height: 1.1;
        }
        
        .section-subtitle {
            font-size: 1.1rem;
            color: var(--text-light);
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }
        
        /* Tarjetas Compactas */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 2.5rem 2rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            height: 100%;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }
        
        .card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
        }
        
        .card-icon {
            width: 60px;
            height: 60px;
            background: var(--gradient);
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: var(--white);
            font-size: 1.5rem;
            box-shadow: 0 8px 20px rgba(44, 90, 160, 0.2);
            transition: var(--transition);
        }
        
        .card:hover .card-icon {
            transform: rotate(10deg) scale(1.1);
            background: var(--gradient-secondary);
        }
        
        .card h3 {
            font-size: 1.4rem;
            margin-bottom: 1rem;
            color: var(--dark);
            font-weight: 700;
        }
        
        .card p {
            color: var(--text-light);
            line-height: 1.6;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }
        
        .card-footer {
            padding-top: 1.5rem;
            border-top: 1px solid var(--light-gray);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-lighter);
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        /* BLOG ESTILO FACEBOOK - RESTAURADO CON IMÁGENES COMPLETAS */
        .blog-section {
            padding: 4rem 0;
        }
        
        .blog-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .blog-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        /* Post estilo Facebook - Restaurado */
        .facebook-post {
            background: var(--white);
            border-radius: 12px;
            box-shadow: var(--shadow);
            margin-bottom: 2rem;
            transition: var(--transition);
            border: 1px solid #e0e0e0;
            overflow: hidden;
        }
        
        .facebook-post:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }
        
        .post-header {
            display: flex;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .post-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.2rem;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .post-author-info {
            flex: 1;
        }
        
        .post-author {
            font-weight: 700;
            color: var(--dark);
            font-size: 1rem;
            margin-bottom: 0.25rem;
        }
        
        .post-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-light);
            font-size: 0.85rem;
        }
        
        .post-meta i {
            font-size: 0.8rem;
        }
        
        .post-content {
            padding: 1.5rem 1.5rem 1rem;
        }
        
        .post-title {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin-bottom: 0.75rem;
            line-height: 1.4;
        }
        
        .post-excerpt {
            color: var(--text);
            line-height: 1.6;
            font-size: 0.95rem;
            margin-bottom: 1rem;
        }
        
        /* IMAGEN MEJORADA - Se muestra completa */
        .post-image-container {
            border-radius: 8px;
            overflow: hidden;
            margin: 1rem 0;
            position: relative;
            background: var(--light-gray);
            min-height: 200px;
            max-height: 400px;
        }
        
        .post-image {
            width: 100%;
            height: auto;
            display: block;
            transition: transform 0.5s ease;
            object-fit: contain;
            max-height: 400px;
        }
        
        .facebook-post:hover .post-image {
            transform: scale(1.05);
        }
        
        .post-image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to bottom, rgba(0,0,0,0.1), transparent);
            opacity: 0;
            transition: var(--transition);
        }
        
        .facebook-post:hover .post-image-overlay {
            opacity: 0.3;
        }
        
        .post-category {
            display: inline-block;
            background: var(--gradient-accent);
            color: var(--white);
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .post-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: flex-end;
            align-items: center;
        }
        
        .read-more-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            background: rgba(44, 90, 160, 0.08);
            transition: var(--transition);
        }
        
        .read-more-btn:hover {
            background: var(--primary);
            color: var(--white);
            transform: translateX(5px);
        }
        
        /* CTA Moderno */
        .cta-section {
            background: var(--darker);
            color: var(--white);
            padding: 5rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .cta-content {
            text-align: center;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .cta-title {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            line-height: 1.1;
            color: var(--white);
        }
        
        .cta-text {
            font-size: 1.1rem;
            opacity: 0.9;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .cta-button {
            background: var(--gradient-secondary);
            color: var(--white);
            padding: 1rem 3rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            transition: var(--transition);
            box-shadow: 0 8px 25px rgba(243, 156, 18, 0.3);
        }
        
        .cta-button:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(243, 156, 18, 0.4);
        }
        
        /* Footer Compacto */
        .footer {
            background: var(--darker);
            color: var(--white);
            padding: 4rem 0 2rem;
            margin-top: auto;
        }
        
        .footer-container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 3rem;
            margin-bottom: 3rem;
        }
        
        .footer-col h3 {
            font-size: 1.3rem;
            margin-bottom: 1.5rem;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: var(--white);
        }
        
        .footer-col p {
            color: #cbd5e1;
            line-height: 1.6;
            font-size: 0.95rem;
            margin-bottom: 1.5rem;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 0.75rem;
        }
        
        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }
        
        .footer-links a:hover {
            color: var(--white);
            transform: translateX(5px);
        }
        
        .social-icons {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }
        
        .social-icons a {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            text-decoration: none;
            transition: var(--transition);
            font-size: 1rem;
        }
        
        .social-icons a:hover {
            background: var(--gradient);
            transform: translateY(-3px);
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #64748b;
            font-size: 0.9rem;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .hero-title {
                font-size: 3rem;
            }
            
            .section-title {
                font-size: 2.5rem;
            }
            
            .cards-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .nav-menu {
                display: none;
            }
            
            .hero-title {
                font-size: 2.5rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .hero-subtitle,
            .section-subtitle {
                font-size: 1.1rem;
            }
            
            .cards-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 280px;
                justify-content: center;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 2rem;
            }
            
            .social-icons {
                justify-content: center;
            }
            
            .post-header {
                padding: 1rem;
            }
            
            .post-content {
                padding: 1rem;
            }
            
            .post-footer {
                padding: 1rem;
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .post-image-container {
                max-height: 300px;
            }
            
            .post-image {
                max-height: 300px;
            }
        }
        
        @media (max-width: 480px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .section-title, .cta-title {
                font-size: 1.8rem;
            }
            
            .card {
                padding: 1.5rem;
            }
            
            .container {
                padding: 0 1rem;
            }
            
            .nav-container {
                padding: 0 1rem;
            }
            
            .facebook-post {
                margin-bottom: 1.5rem;
            }
            
            .post-image-container {
                min-height: 150px;
                max-height: 250px;
            }
            
            .post-image {
                max-height: 250px;
            }
        }
        
        @media (max-width: 380px) {
            .post-image {
                max-height: 200px;
            }
            
            .post-image-container {
                max-height: 200px;
            }
        }
    </style>
</head>
<body>
    <!-- Header Moderno -->
    <header class="header">
        <div class="nav-container">
            <a href="#inicio" class="logo-container">
                <div class="logo-icon">
                    <i class="fas fa-church"></i>
                </div>
                <div class="logo-text">
                    <h1><?php echo SITE_NAME; ?></h1>
                    <span>FE • UNIÓN • SERVICIO</span>
                </div>
            </a>
            
            <ul class="nav-menu">
                <li><a href="#inicio" class="nav-link">Inicio</a></li>
                <li><a href="#nosotros" class="nav-link">Nosotros</a></li>
                <li><a href="#zonas" class="nav-link">Zonas</a></li>
                <li><a href="#comisiones" class="nav-link">Comisiones</a></li>
                <li><a href="#blog" class="nav-link">Blog</a></li>
                <li><a href="#contacto" class="nav-link">Contacto</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="admin/dashboard.php" class="nav-link nav-cta">Admin</a></li>
                <?php else: ?>
                    <li><a href="admin/index.php" class="nav-link nav-cta">Acceder</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </header>

    <!-- Hero Section -->
    <section id="inicio" class="hero-section">
        <div class="hero-background"></div>
        <div class="container">
            <div class="hero-content">
                <div class="hero-badge">
                    <i class="fas fa-star"></i>
                    <span>TRANSFORMANDO VIDAS DESDE 1990</span>
                </div>
                <h1 class="hero-title">Unidos en Fe y Servicio</h1>
                <p class="hero-subtitle">Asociación de Iglesias del Estado de Guerrero - Transformando vidas a través del evangelio y el servicio comunitario</p>
                <div class="hero-buttons">
                    <a href="#nosotros" class="btn btn-primary">
                        <i class="fas fa-compass"></i>
                        Conócenos
                    </a>
                    <a href="#blog" class="btn btn-outline">
                        <i class="fas fa-newspaper"></i>
                        Ver Publicaciones
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección Nosotros -->
    <section id="nosotros" class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">NUESTRA ESENCIA</span>
                <h2 class="section-title">Fe que Transforma</h2>
                <p class="section-subtitle">Los pilares fundamentales que guían nuestro trabajo y misión</p>
            </div>
            
            <div class="cards-grid">
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>
                    <h3>Misión</h3>
                    <p>Unir y fortalecer las iglesias del estado de Guerrero para el crecimiento espiritual y el servicio comunitario, promoviendo la unidad cristiana.</p>
                    <div class="card-footer">
                        <i class="fas fa-cross"></i>
                        <span>Propósito Divino</span>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-eye"></i>
                    </div>
                    <h3>Visión</h3>
                    <p>Ser la asociación líder que inspire y coordine el trabajo conjunto de las iglesias, siendo agentes de cambio que transformen vidas.</p>
                    <div class="card-footer">
                        <i class="fas fa-rocket"></i>
                        <span>Futuro Brillante</span>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-gem"></i>
                    </div>
                    <h3>Valores</h3>
                    <p>Fe inquebrantable, Unidad en la diversidad, Servicio desinteresado, Integridad absoluta y Amor al prójimo.</p>
                    <div class="card-footer">
                        <i class="fas fa-heart"></i>
                        <span>Principios Eternos</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección Zonas -->
    <section id="zonas" class="section section-alt">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">ALCANCE TERRITORIAL</span>
                <h2 class="section-title">Llegamos a Todo Guerrero</h2>
                <p class="section-subtitle">Presencia transformadora en cada rincón del estado</p>
            </div>
            
            <div class="cards-grid">
                <?php
                $zonas = getZonas();
                foreach ($zonas as $zona):
                ?>
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h3><?php echo $zona['nombre']; ?></h3>
                    <p><?php echo $zona['descripcion']; ?></p>
                    <div class="card-footer">
                        <i class="fas fa-church"></i>
                        <span>Comunidad Activa</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Sección Comisiones -->
    <section id="comisiones" class="section">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">COMISIONES</span>
                <h2 class="section-title">Equipos Especializados</h2>
                <p class="section-subtitle">Trabajo enfocado para mayor impacto</p>
            </div>
            
            <div class="cards-grid">
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-hands-praying"></i>
                    </div>
                    <h3>Evangelismo</h3>
                    <p>Coordinamos actividades de evangelización en todo el estado, organizando campañas y capacitación para evangelistas.</p>
                    <div class="card-footer">
                        <i class="fas fa-users"></i>
                        <span>+500 Líderes</span>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Educación</h3>
                    <p>Formamos y capacitamos líderes y miembros de las iglesias con programas de discipulado y seminarios teológicos.</p>
                    <div class="card-footer">
                        <i class="fas fa-book"></i>
                        <span>Programas Certificados</span>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-icon">
                        <i class="fas fa-hand-holding-heart"></i>
                    </div>
                    <h3>Acción Social</h3>
                    <p>Coordinamos proyectos de ayuda comunitaria y servicio social, implementando programas de asistencia.</p>
                    <div class="card-footer">
                        <i class="fas fa-hands-helping"></i>
                        <span>Impacto Medible</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Sección Blog estilo Facebook - RESTAURADA -->
    <section id="blog" class="section section-alt blog-section">
        <div class="container">
            <div class="section-header">
                <span class="section-badge">PUBLICACIONES</span>
                <h2 class="section-title">Últimas Noticias</h2>
                <p class="section-subtitle">Mantente informado con nuestras publicaciones recientes</p>
            </div>
            
            <div class="blog-container">
                <?php if (!empty($recent_posts)): ?>
                    <?php foreach ($recent_posts as $post): ?>
                    <article class="facebook-post">
                        <div class="post-header">
                            <div class="post-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="post-author-info">
                                <div class="post-author"><?php echo $post['autor']; ?></div>
                                <div class="post-meta">
                                    <i class="fas fa-calendar"></i>
                                    <span><?php echo formatDate($post['fecha_publicacion']); ?></span>
                                    <i class="fas fa-tag"></i>
                                    <span><?php echo $post['categoria']; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="post-content">
                            <div class="post-category"><?php echo $post['categoria']; ?></div>
                            <h3 class="post-title"><?php echo $post['titulo']; ?></h3>
                            <div class="post-excerpt">
                                <?php echo substr($post['resumen'] ?: $post['contenido'], 0, 200) . '...'; ?>
                            </div>
                            
                            <?php if ($post['imagen_principal']): ?>
                            <div class="post-image-container">
                                <img src="assets/uploads/blog/<?php echo $post['imagen_principal']; ?>" 
                                     alt="<?php echo $post['titulo']; ?>" 
                                     class="post-image"
                                     loading="lazy">
                                <div class="post-image-overlay"></div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="post-footer">
                            <a href="blog/post.php?id=<?php echo $post['id']; ?>" class="read-more-btn">
                                Leer más <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card" style="text-align: center; padding: 3rem 2rem;">
                        <div class="card-icon" style="margin: 0 auto 1.5rem;">
                            <i class="fas fa-pen-nib"></i>
                        </div>
                        <h3>Próximas Publicaciones</h3>
                        <p>Estamos preparando contenido inspirador para nuestro blog. Muy pronto tendremos publicaciones que edificarán tu fe y conocimiento.</p>
                        <div class="card-footer" style="justify-content: center; border: none; margin-top: 1.5rem;">
                            <i class="fas fa-clock"></i>
                            <span>Próximamente</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="text-align: center; margin-top: 3rem;">
                <a href="blog/" class="btn btn-primary">
                    <i class="fas fa-newspaper"></i>
                    Ver Todas las Publicaciones
                </a>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section id="contacto" class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2 class="cta-title">¿Listo para unirte?</h2>
                <p class="cta-text">Únete a nuestra familia espiritual y participa en proyectos transformadores en Guerrero.</p>
                <a href="#contacto" class="cta-button">
                    <i class="fas fa-hands-praying"></i>
                    Contáctanos
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3><?php echo SITE_NAME; ?></h3>
                    <p>Una red de iglesias unidas por la fe, comprometidas con el servicio y dedicadas a transformar vidas en Guerrero.</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                
                <div class="footer-col">
                    <h3>Enlaces</h3>
                    <ul class="footer-links">
                        <li><a href="#inicio"><i class="fas fa-chevron-right"></i> Inicio</a></li>
                        <li><a href="#nosotros"><i class="fas fa-chevron-right"></i> Nosotros</a></li>
                        <li><a href="#zonas"><i class="fas fa-chevron-right"></i> Zonas</a></li>
                        <li><a href="#comisiones"><i class="fas fa-chevron-right"></i> Comisiones</a></li>
                        <li><a href="#blog"><i class="fas fa-chevron-right"></i> Blog</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h3>Contacto</h3>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-map-marker-alt"></i> Guerrero, México</a></li>
                        <li><a href="tel:+527441234567"><i class="fas fa-phone"></i> +52 744 123 4567</a></li>
                        <li><a href="mailto:info@asociacioniglesias.com"><i class="fas fa-envelope"></i> contacto@iglesias.org</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script>
        // Header scroll effect
        window.addEventListener('scroll', () => {
            const header = document.querySelector('.header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if (targetId === '#') return;
                
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    const headerHeight = document.querySelector('.header').offsetHeight;
                    const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - headerHeight;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Hover effects for cards
        document.querySelectorAll('.card, .facebook-post').forEach(element => {
            element.addEventListener('mouseenter', () => {
                element.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';
            });
            
            element.addEventListener('mouseleave', () => {
                element.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';
            });
        });

        // Initialize animations
        document.addEventListener('DOMContentLoaded', () => {
            // Add fade-in animation to elements
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            }, observerOptions);

            // Observe all cards and posts
            document.querySelectorAll('.card, .facebook-post').forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(el);
            });

            // Add click effect to buttons
            document.querySelectorAll('.btn, .cta-button, .read-more-btn').forEach(button => {
                button.addEventListener('click', function(e) {
                    // Create ripple effect
                    const ripple = document.createElement('span');
                    const rect = this.getBoundingClientRect();
                    const size = Math.max(rect.width, rect.height);
                    const x = e.clientX - rect.left - size / 2;
                    const y = e.clientY - rect.top - size / 2;
                    
                    ripple.style.cssText = `
                        position: absolute;
                        border-radius: 50%;
                        background: rgba(255, 255, 255, 0.3);
                        transform: scale(0);
                        animation: ripple 0.6s linear;
                        width: ${size}px;
                        height: ${size}px;
                        top: ${y}px;
                        left: ${x}px;
                        pointer-events: none;
                    `;
                    
                    this.style.position = 'relative';
                    this.style.overflow = 'hidden';
                    this.appendChild(ripple);
                    
                    setTimeout(() => {
                        ripple.remove();
                    }, 600);
                });
            });

            // Add CSS for ripple effect
            const style = document.createElement('style');
            style.textContent = `
                @keyframes ripple {
                    to {
                        transform: scale(4);
                        opacity: 0;
                    }
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>
</html>