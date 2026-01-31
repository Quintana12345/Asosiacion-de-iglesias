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
    <title><?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            --shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
            --shadow-hover: 0 35px 60px -12px rgba(0, 0, 0, 0.25);
            --shadow-light: 0 10px 30px rgba(0, 0, 0, 0.08);
            --gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --gradient-secondary: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            --gradient-accent: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
            --border-radius: 24px;
            --border-radius-sm: 16px;
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
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
            background: linear-gradient(135deg, var(--light) 0%, var(--light-gray) 100%);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .main-content {
            flex: 1;
            width: 100%;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }
        
        /* Header Ultra Premium */
        .header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(40px) saturate(180%);
            padding: 1.4rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            transition: var(--transition);
        }
        
        .header.scrolled {
            background: rgba(255, 255, 255, 0.95);
            box-shadow: var(--shadow-light);
            padding: 1rem 0;
        }
        
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }
        
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 0.875rem;
        }
        
        .nav-brand h2 {
            font-size: 1.6rem;
            font-weight: 800;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.5px;
        }
        
        .nav-brand i {
            font-size: 2rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2.5rem;
            align-items: center;
        }
        
        .nav-menu a {
            color: var(--text);
            text-decoration: none;
            font-weight: 600;
            padding: 0.75rem 1.25rem;
            border-radius: 50px;
            transition: var(--transition);
            position: relative;
            font-size: 0.95rem;
        }
        
        .nav-menu a::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            width: 0;
            height: 2px;
            background: var(--gradient);
            transition: var(--transition);
            transform: translateX(-50%);
        }
        
        .nav-menu a:hover::before {
            width: 80%;
        }
        
        .nav-menu a:hover {
            color: var(--primary);
            background: rgba(44, 90, 160, 0.05);
        }
        
        /* Hero Section Ultra Premium */
        .hero {
            background: var(--gradient);
            color: var(--white);
            padding: 200px 0 120px;
            text-align: center;
            margin-top: 80px;
            position: relative;
            overflow: hidden;
            width: 100%;
        }
        
        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 80%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.05) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(255,255,255,0.08) 0%, transparent 50%);
            animation: float 20s ease-in-out infinite;
        }
        
        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .hero-title {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.1;
            letter-spacing: -1px;
            text-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .hero-subtitle {
            font-size: 1.4rem;
            margin-bottom: 3.5rem;
            opacity: 0.95;
            font-weight: 300;
            line-height: 1.6;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* Botones Ultra Premium */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.875rem;
            padding: 1.25rem 2.75rem;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 700;
            transition: var(--transition);
            border: 2px solid transparent;
            font-size: 1.05rem;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s ease;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: var(--gradient-secondary);
            color: var(--white);
            box-shadow: 0 15px 40px rgba(243, 156, 18, 0.4);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 25px 50px rgba(243, 156, 18, 0.5);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--white);
            border: 2px solid rgba(255,255,255,0.4);
            backdrop-filter: blur(10px);
        }
        
        .btn-outline:hover {
            background: var(--white);
            color: var(--primary);
            border-color: var(--white);
            transform: translateY(-3px);
            box-shadow: 0 20px 40px rgba(255,255,255,0.2);
        }
        
        /* Secciones */
        .section {
            padding: 120px 0;
            width: 100%;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 1.5rem;
            color: var(--primary);
            font-size: 3.2rem;
            font-weight: 800;
            letter-spacing: -1px;
        }
        
        .section-subtitle {
            text-align: center;
            margin-bottom: 5rem;
            color: var(--text-light);
            font-size: 1.3rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
            font-weight: 400;
        }
        
        /* DISEÑO MEJORADO PARA TARJETAS COMPACTAS - TODAS LAS SECCIONES */
        .essence-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2.5rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .essence-card {
            background: var(--white);
            padding: 2.5rem 2rem;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .essence-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 6px;
            height: 100%;
            background: var(--gradient);
            transition: var(--transition);
        }
        
        .essence-card::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(44, 90, 160, 0.03), transparent);
            transition: left 0.8s ease;
        }
        
        .essence-card:hover::after {
            left: 100%;
        }
        
        .essence-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-hover);
        }
        
        .essence-card:hover::before {
            width: 8px;
        }
        
        .card-icon {
            width: 70px;
            height: 70px;
            background: var(--gradient);
            border-radius: var(--border-radius-sm);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            color: var(--white);
            font-size: 1.8rem;
            box-shadow: 0 15px 30px rgba(44, 90, 160, 0.3);
            transition: var(--transition);
        }
        
        .essence-card:hover .card-icon {
            transform: scale(1.1) rotate(5deg);
            box-shadow: 0 20px 40px rgba(44, 90, 160, 0.4);
        }
        
        .essence-card h3 {
            color: var(--primary);
            margin-bottom: 1rem;
            font-size: 1.5rem;
            font-weight: 700;
            letter-spacing: -0.3px;
        }
        
        .essence-card p {
            color: var(--text-light);
            line-height: 1.7;
            font-size: 1rem;
        }
        
        .card-meta {
            color: var(--text-lighter);
            font-size: 0.95rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }
        
        /* Blog Items Ultra Premium */
        .blog-item {
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(255, 255, 255, 0.8);
        }
        
        .blog-item:hover {
            transform: translateY(-12px) scale(1.02);
            box-shadow: var(--shadow-hover);
        }
        
        .blog-image {
            width: 100%;
            height: 280px;
            background: var(--gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }
        
        .blog-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(0,0,0,0.1), transparent);
        }
        
        .blog-image i {
            font-size: 4rem;
            opacity: 0.9;
            z-index: 2;
        }
        
        .blog-content {
            padding: 3rem;
        }
        
        .blog-category {
            background: var(--gradient);
            color: var(--white);
            padding: 0.75rem 1.5rem;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 20px rgba(44, 90, 160, 0.2);
        }
        
        .blog-content h3 {
            color: var(--text);
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            line-height: 1.4;
            letter-spacing: -0.3px;
        }
        
        .blog-meta {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            font-size: 0.95rem;
            color: var(--text-light);
            padding-top: 2rem;
            border-top: 1px solid #f1f5f9;
        }
        
        .blog-meta span {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }

        /* Footer Ultra Premium - Ancho completo */
        .footer {
            background: var(--darker);
            color: var(--white);
            padding: 6rem 0 3rem;
            margin-top: auto;
            width: 100%;
            position: relative;
        }

        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primary), transparent);
        }

        .footer-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 4rem;
            margin-bottom: 4rem;
        }
        
        .footer-col h3 {
            margin-bottom: 2rem;
            font-size: 1.4rem;
            font-weight: 700;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .footer-col p {
            color: #cbd5e1;
            line-height: 1.7;
            font-size: 1.05rem;
        }
        
        .footer-links {
            list-style: none;
        }
        
        .footer-links li {
            margin-bottom: 1rem;
        }
        
        .footer-links a {
            color: #94a3b8;
            text-decoration: none;
            transition: var(--transition);
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .footer-links a:hover {
            color: var(--white);
            transform: translateX(8px);
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        .social-links a {
            width: 50px;
            height: 50px;
            background: rgba(255,255,255,0.08);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #94a3b8;
            text-decoration: none;
            transition: var(--transition);
            border: 1px solid rgba(255,255,255,0.1);
        }
        
        .social-links a:hover {
            background: var(--gradient);
            color: var(--white);
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(44, 90, 160, 0.3);
            border-color: transparent;
        }
        
        .footer-bottom {
            text-align: center;
            margin-top: 4rem;
            padding-top: 3rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: #64748b;
            font-size: 1rem;
            font-weight: 500;
        }

        /* Animaciones */
        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }

        /* Efectos de partículas para el hero */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 15s infinite linear;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .nav-menu {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .hero-title {
                font-size: 2.8rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .hero-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .section-title {
                font-size: 2.5rem;
            }
            
            .essence-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .essence-card {
                padding: 2rem 1.5rem;
            }
            
            .blog-content {
                padding: 2.5rem;
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
                gap: 3rem;
            }

            .social-links {
                justify-content: center;
            }

            .footer-links a {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .hero-title {
                font-size: 2.2rem;
            }
            
            .hero-subtitle {
                font-size: 1.1rem;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .essence-card {
                padding: 1.5rem;
            }

            .btn {
                padding: 1rem 2rem;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Header Ultra Premium -->
    <header class="header">
        <nav class="nav">
            <div class="nav-brand">
                <i class="fas fa-church"></i>
                <h2><?php echo SITE_NAME; ?></h2>
            </div>
            <ul class="nav-menu">
                <li><a href="#inicio">Inicio</a></li>
                <li><a href="#nosotros">Nosotros</a></li>
                <li><a href="#zonas">Zonas</a></li>
                <li><a href="#comisiones">Comisiones</a></li>
                <li><a href="blog/">Blog</a></li>
                <li><a href="#contacto">Contacto</a></li>
                <?php if (isLoggedIn()): ?>
                    <li><a href="admin/dashboard.php" style="background: var(--gradient); color: white; padding: 0.75rem 1.5rem; border-radius: 50px; font-weight: 600;">Admin</a></li>
                <?php else: ?>
                    <li><a href="admin/index.php" style="background: var(--gradient-accent); color: white; padding: 0.75rem 1.5rem; border-radius: 50px; font-weight: 600;">Acceder</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <main class="main-content">
        <!-- Hero Section Ultra Premium -->
        <section id="inicio" class="hero">
            <div class="container">
                <div class="hero-content">
                    <h1 class="hero-title">Unidos en Fe y Servicio</h1>
                    <p class="hero-subtitle">Asociación de Iglesias del Estado de Guerrero - Transformando vidas a través del evangelio y el servicio comunitario con amor y dedicación</p>
                    <div class="hero-buttons">
                        <a href="#nosotros" class="btn btn-primary">
                            <i class="fas fa-arrow-down"></i>
                            Conócenos
                        </a>
                        <a href="blog/" class="btn btn-outline">
                            <i class="fas fa-newspaper"></i>
                            Nuestro Blog
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección Nosotros - DISEÑO MEJORADO CON TARJETAS COMPACTAS -->
        <section id="nosotros" class="section">
            <div class="container">
                <h2 class="section-title">Nuestra Esencia</h2>
                <p class="section-subtitle">Trabajando unidos para transformar vidas y comunidades a través de la fe, el servicio y el amor cristiano</p>
                
                <div class="essence-grid">
                    <div class="essence-card">
                        <div class="card-icon">
                            <i class="fas fa-cross"></i>
                        </div>
                        <h3>Misión</h3>
                        <p>Unir y fortalecer las iglesias del estado de Guerrero para el crecimiento espiritual y el servicio comunitario, promoviendo la unidad cristiana y el desarrollo integral de nuestras comunidades mediante programas de capacitación, evangelización y servicio social.</p>
                    </div>
                    
                    <div class="essence-card">
                        <div class="card-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3>Visión</h3>
                        <p>Ser la asociación líder que inspire y coordine el trabajo conjunto de las iglesias, siendo agentes de cambio que transformen vidas y comunidades mediante el evangelio vivo y el servicio desinteresado, impactando positivamente en la sociedad guerrerense.</p>
                    </div>
                    
                    <div class="essence-card">
                        <div class="card-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h3>Valores Fundamentales</h3>
                        <p>Fe inquebrantable, Unidad en la diversidad, Servicio desinteresado, Integridad absoluta y Amor al prójimo como pilares fundamentales de nuestra labor ministerial y comunitaria, guiando cada una de nuestras acciones y decisiones.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección Zonas - DISEÑO MEJORADO CON TARJETAS COMPACTAS -->
        <section id="zonas" class="section" style="background: var(--light);">
            <div class="container">
                <h2 class="section-title">Nuestro Alcance Territorial</h2>
                <p class="section-subtitle">Llegando a cada rincón del estado de Guerrero con el mensaje de esperanza, amor y transformación espiritual</p>
                
                <div class="essence-grid">
                    <?php
                    $zonas = getZonas();
                    foreach ($zonas as $zona):
                    ?>
                    <div class="essence-card">
                        <div class="card-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <h3><?php echo $zona['nombre']; ?></h3>
                        <p><?php echo $zona['descripcion']; ?></p>
                        <div class="card-meta">
                            <i class="fas fa-church"></i> Sectores en desarrollo - Próximamente
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <!-- Sección Comisiones - DISEÑO MEJORADO CON TARJETAS COMPACTAS -->
        <section id="comisiones" class="section">
            <div class="container">
                <h2 class="section-title">Nuestras Comisiones</h2>
                <p class="section-subtitle">Trabajando en áreas específicas para un impacto más efectivo y transformador en nuestra comunidad</p>
                
                <div class="essence-grid">
                    <div class="essence-card">
                        <div class="card-icon">
                            <i class="fas fa-hands-praying"></i>
                        </div>
                        <h3>Comisión de Evangelismo</h3>
                        <p>Encargada de coordinar y promover actividades de evangelización en todo el estado, organizando campañas evangelísticas, capacitación para evangelistas y materiales de apoyo para el crecimiento espiritual de nuestras congregaciones.</p>
                    </div>
                    
                    <div class="essence-card">
                        <div class="card-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                        <h3>Comisión de Educación</h3>
                        <p>Dedicada a la formación y capacitación de líderes y miembros de las iglesias, desarrollando programas de discipulado, seminarios teológicos y herramientas para el crecimiento ministerial y desarrollo personal.</p>
                    </div>
                    
                    <div class="essence-card">
                        <div class="card-icon">
                            <i class="fas fa-hand-holding-heart"></i>
                        </div>
                        <h3>Comisión Social</h3>
                        <p>Coordina proyectos de ayuda comunitaria y servicio social, implementando programas de asistencia a grupos vulnerables, desarrollo comunitario y respuesta ante emergencias con amor cristiano.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Sección Blog - DISEÑO MEJORADO CON TARJETAS COMPACTAS -->
        <section class="section" style="background: var(--light);">
            <div class="container">
                <h2 class="section-title">Inspiración Reciente</h2>
                <p class="section-subtitle">Mantente informado con nuestras últimas noticias, reflexiones espirituales y testimonios de fe</p>
                
                <div class="essence-grid">
                    <?php if (!empty($recent_posts)): ?>
                        <?php foreach ($recent_posts as $post): ?>
                        <article class="essence-card blog-item">
                            <div class="blog-image">
                                <?php if ($post['imagen_principal']): ?>
                                    <img src="assets/uploads/blog/<?php echo $post['imagen_principal']; ?>" alt="<?php echo $post['titulo']; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    <i class="fas fa-cross"></i>
                                <?php endif; ?>
                            </div>
                            <div class="blog-content">
                                <span class="blog-category"><?php echo $post['categoria']; ?></span>
                                <h3><?php echo $post['titulo']; ?></h3>
                                <p style="color: var(--text-light); line-height: 1.7; margin-bottom: 1.5rem;">
                                    <?php echo substr($post['resumen'] ?: $post['contenido'], 0, 150) . '...'; ?>
                                </p>
                                <div class="blog-meta">
                                    <span><i class="fas fa-user"></i> <?php echo $post['autor']; ?></span>
                                    <span><i class="fas fa-calendar"></i> <?php echo formatDate($post['fecha_publicacion']); ?></span>
                                </div>
                                <a href="blog/post.php?id=<?php echo $post['id']; ?>" 
                                   style="display: inline-flex; align-items: center; gap: 0.75rem; margin-top: 2rem; color: var(--primary); text-decoration: none; font-weight: 700; transition: var(--transition);">
                                    Leer más <i class="fas fa-arrow-right" style="transition: transform 0.3s ease;"></i>
                                </a>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="essence-card" style="text-align: center;">
                            <div class="card-icon">
                                <i class="fas fa-pen-fancy"></i>
                            </div>
                            <h3>Próximamente</h3>
                            <p>Estamos preparando contenido inspirador para nuestro blog. Muy pronto tendremos publicaciones que edificarán tu fe y conocimiento, con reflexiones bíblicas, testimonios de vida, noticias relevantes de nuestra asociación y recursos espirituales para el crecimiento personal.</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="text-align: center; margin-top: 4rem;">
                    <a href="blog/" class="btn btn-primary">
                        <i class="fas fa-book-open"></i>
                        Explorar Blog Completo
                    </a>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer Ultra Premium - Ancho completo -->
    <footer class="footer">
        <div class="footer-container">
            <div class="footer-content">
                <div class="footer-col">
                    <h3><i class="fas fa-church"></i> <?php echo SITE_NAME; ?></h3>
                    <p>Unidos en fe y servicio para transformar nuestro estado mediante el amor de Cristo y el trabajo comunitario, construyendo un mejor futuro para las generaciones venideras con esperanza y dedicación.</p>
                </div>
                
                <div class="footer-col">
                    <h4>Navegación</h4>
                    <ul class="footer-links">
                        <li><a href="#inicio"><i class="fas fa-home"></i> Inicio</a></li>
                        <li><a href="#nosotros"><i class="fas fa-users"></i> Nosotros</a></li>
                        <li><a href="#zonas"><i class="fas fa-map"></i> Zonas</a></li>
                        <li><a href="#comisiones"><i class="fas fa-tasks"></i> Comisiones</a></li>
                        <li><a href="blog/"><i class="fas fa-newspaper"></i> Blog</a></li>
                        <li><a href="#contacto"><i class="fas fa-envelope"></i> Contacto</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Contacto</h4>
                    <ul class="footer-links">
                        <li><a href="#"><i class="fas fa-map-marker-alt"></i> Guerrero, México</a></li>
                        <li><a href="tel:+527441234567"><i class="fas fa-phone"></i> +52 744 123 4567</a></li>
                        <li><a href="mailto:info@asociacioniglesias.com"><i class="fas fa-envelope"></i> info@asociacioniglesias.com</a></li>
                    </ul>
                </div>
                
                <div class="footer-col">
                    <h4>Síguenos</h4>
                    <p>Conecta con nosotros en nuestras redes sociales para estar al tanto de nuestras actividades y eventos.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Todos los derechos reservados. | Unidos en Cristo para Servir y Transformar</p>
            </div>
        </div>
    </footer>

    <script>
        // Header scroll effect
        const header = document.querySelector('.header');
        window.addEventListener('scroll', function() {
            if (window.scrollY > 100) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Smooth scroll para enlaces internos
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Efectos hover mejorados para todas las tarjetas
        document.querySelectorAll('.essence-card').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-8px) scale(1.02)';
            });
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });
    </script>
</body>
</html>6+