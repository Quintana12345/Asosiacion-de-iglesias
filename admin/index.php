<?php
require_once '../includes/config.php';

// Verificar si la base de datos existe
if (!checkDatabase()) {
    die("Error: La base de datos no está configurada correctamente. Por favor, ejecuta el archivo de instalación.");
}

if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_POST) {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (login($email, $password)) {
        redirect('dashboard.php');
    } else {
        $error = 'Credenciales incorrectas. Por favor, verifica tu email y contraseña.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Seguro - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2c5aa0;
            --primary-dark: #1e3d72;
            --primary-light: #3a6bc5;
            --secondary: #f39c12;
            --secondary-dark: #e67e22;
            --accent: #27ae60;
            --accent-light: #2ecc71;
            --white: #ffffff;
            --light: #f8fafc;
            --light-gray: #f1f5f9;
            --dark: #1e293b;
            --darker: #0f172a;
            --text: #334155;
            --text-light: #64748b;
            --text-lighter: #94a3b8;
            --success: #10b981;
            --warning: #f59e0b;
            --error: #ef4444;
            --border-radius: 20px;
            --border-radius-sm: 12px;
            --shadow: 0 20px 50px -12px rgba(0, 0, 0, 0.2);
            --shadow-hover: 0 30px 60px -12px rgba(0, 0, 0, 0.3);
            --shadow-light: 0 10px 30px rgba(0, 0, 0, 0.1);
            --gradient: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            --gradient-secondary: linear-gradient(135deg, var(--secondary) 0%, var(--secondary-dark) 100%);
            --gradient-accent: linear-gradient(135deg, var(--accent) 0%, var(--accent-light) 100%);
            --transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--darker);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        /* Fondo animado */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .bg-animation::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(44, 90, 160, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(243, 156, 18, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(39, 174, 96, 0.15) 0%, transparent 50%);
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0%, 100% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
        }

        .floating-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .shape {
            position: absolute;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 50%;
            animation: float 20s infinite linear;
        }

        .shape:nth-child(1) {
            width: 300px;
            height: 300px;
            top: -150px;
            left: -150px;
            animation-delay: 0s;
        }

        .shape:nth-child(2) {
            width: 200px;
            height: 200px;
            top: 20%;
            right: -100px;
            animation-delay: 5s;
        }

        .shape:nth-child(3) {
            width: 150px;
            height: 150px;
            bottom: 10%;
            left: 10%;
            animation-delay: 10s;
        }

        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
            }
            33% {
                transform: translateY(-20px) rotate(120deg);
            }
            66% {
                transform: translateY(20px) rotate(240deg);
            }
            100% {
                transform: translateY(0) rotate(360deg);
            }
        }

        /* Contenedor principal */
        .login-wrapper {
            width: 100%;
            max-width: 1200px;
            display: flex;
            min-height: 700px;
            position: relative;
            animation: fadeIn 0.8s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Panel izquierdo - Información */
        .login-info-panel {
            flex: 1;
            background: var(--gradient);
            border-radius: var(--border-radius) 0 0 var(--border-radius);
            padding: 4rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
            overflow: hidden;
            z-index: 1;
        }

        .login-info-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            animation: patternMove 30s linear infinite;
            z-index: -1;
        }

        @keyframes patternMove {
            0% {
                transform: translate(0, 0);
            }
            100% {
                transform: translate(100px, 100px);
            }
        }

        .info-content {
            position: relative;
            z-index: 2;
        }

        .info-logo {
            width: 120px;
            height: 120px;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2.5rem;
            border: 2px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        }

        .info-logo i {
            font-size: 3.5rem;
            color: var(--white);
            opacity: 0.9;
        }

        .info-content h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 3rem;
            font-weight: 800;
            color: var(--white);
            margin-bottom: 1rem;
            line-height: 1.1;
            text-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .info-content p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 2rem;
            max-width: 500px;
        }

        .info-features {
            list-style: none;
            margin-top: 3rem;
        }

        .info-features li {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            color: rgba(255, 255, 255, 0.85);
        }

        .info-features i {
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        /* Panel derecho - Login */
        .login-form-panel {
            flex: 1;
            background: var(--white);
            border-radius: 0 var(--border-radius) var(--border-radius) 0;
            padding: 4rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: var(--shadow);
            position: relative;
        }

        .form-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .form-header h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--dark);
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }

        .form-header h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 4px;
            background: var(--gradient);
            border-radius: 2px;
        }

        .form-header p {
            color: var(--text-light);
            font-size: 1.1rem;
            margin-top: 1.5rem;
        }

        /* Formulario */
        .login-form {
            display: flex;
            flex-direction: column;
            gap: 2rem;
        }

        .input-group {
            position: relative;
        }

        .input-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text);
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-lighter);
            font-size: 1.2rem;
            transition: var(--transition);
            z-index: 2;
        }

        .form-input {
            width: 100%;
            padding: 1.2rem 1.2rem 1.2rem 3.5rem;
            border: 2px solid var(--light-gray);
            border-radius: var(--border-radius-sm);
            font-size: 1rem;
            font-family: 'Inter', sans-serif;
            transition: var(--transition);
            background: var(--white);
            color: var(--text);
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(44, 90, 160, 0.1);
        }

        .form-input:focus + .input-icon {
            color: var(--primary);
        }

        .password-toggle {
            position: absolute;
            right: 1.2rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-lighter);
            cursor: pointer;
            font-size: 1.2rem;
            transition: var(--transition);
            padding: 0.25rem;
            border-radius: 4px;
        }

        .password-toggle:hover {
            color: var(--primary);
            background: var(--light-gray);
        }

        /* Botón de login */
        .login-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            padding: 1.25rem 2.5rem;
            background: var(--gradient-secondary);
            color: var(--white);
            border: none;
            border-radius: var(--border-radius-sm);
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: var(--transition);
            font-family: 'Inter', sans-serif;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(243, 156, 18, 0.3);
            margin-top: 1rem;
        }

        .login-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.7s ease;
        }

        .login-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(243, 156, 18, 0.4);
        }

        .login-btn:hover::before {
            left: 100%;
        }

        .login-btn i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .login-btn:hover i {
            transform: translateX(5px);
        }

        /* Alertas */
        .alert {
            padding: 1.25rem 1.5rem;
            border-radius: var(--border-radius-sm);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideDown 0.4s ease;
            border-left: 4px solid;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
            border-left-color: var(--error);
            color: var(--error);
        }

        .alert-icon {
            font-size: 1.3rem;
            flex-shrink: 0;
        }

        .alert-message {
            flex: 1;
            font-weight: 500;
        }

        /* Footer del formulario */
        .form-footer {
            margin-top: 3rem;
            text-align: center;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--text-light);
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius-sm);
            background: var(--light);
        }

        .back-link:hover {
            color: var(--primary);
            background: var(--light-gray);
            transform: translateY(-2px);
        }

        .back-link i {
            transition: transform 0.3s ease;
        }

        .back-link:hover i {
            transform: translateX(-3px);
        }

        /* Seguridad */
        .security-info {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 2rem;
            color: var(--text-lighter);
            font-size: 0.9rem;
        }

        .security-info i {
            color: var(--success);
            font-size: 1.1rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .login-wrapper {
                flex-direction: column;
                max-width: 500px;
                min-height: auto;
            }
            
            .login-info-panel {
                border-radius: var(--border-radius) var(--border-radius) 0 0;
                padding: 3rem 2rem;
            }
            
            .login-form-panel {
                border-radius: 0 0 var(--border-radius) var(--border-radius);
                padding: 3rem 2rem;
            }
            
            .info-content h1 {
                font-size: 2.5rem;
            }
            
            .form-header h2 {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px;
            }
            
            .login-info-panel,
            .login-form-panel {
                padding: 2rem 1.5rem;
            }
            
            .info-content h1 {
                font-size: 2rem;
            }
            
            .form-header h2 {
                font-size: 1.8rem;
            }
            
            .info-logo {
                width: 90px;
                height: 90px;
            }
            
            .info-logo i {
                font-size: 2.5rem;
            }
        }

        /* Efectos de partículas */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: particleFloat 15s infinite linear;
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Fondo animado -->
    <div class="bg-animation">
        <div class="floating-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>
    </div>

    <!-- Partículas -->
    <div class="particles" id="particles"></div>

    <div class="login-wrapper">
        <!-- Panel izquierdo - Información -->
        <div class="login-info-panel">
            <div class="info-content">
                <div class="info-logo">
                    <i class="fas fa-church"></i>
                </div>
                
                <h1><?php echo SITE_NAME; ?></h1>
                <p>Sistema de Administración Seguro - Panel de control exclusivo para administradores autorizados de la asociación.</p>
                
                <ul class="info-features">
                    <li>
                        <i class="fas fa-shield-alt"></i>
                        <span>Acceso seguro con encriptación de datos</span>
                    </li>
                    <li>
                        <i class="fas fa-chart-line"></i>
                        <span>Gestión completa de contenido y usuarios</span>
                    </li>
                    <li>
                        <i class="fas fa-users-cog"></i>
                        <span>Control administrativo avanzado</span>
                    </li>
                    <li>
                        <i class="fas fa-history"></i>
                        <span>Registro detallado de actividades</span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Panel derecho - Login -->
        <div class="login-form-panel">
            <div class="form-header">
                <h2>Acceso Seguro</h2>
                <p>Ingresa tus credenciales para acceder al sistema administrativo</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle alert-icon"></i>
                <span class="alert-message"><?php echo $error; ?></span>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="input-group">
                    <label class="input-label">
                        <i class="fas fa-user-circle"></i>
                        Correo Electrónico
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope input-icon"></i>
                        <input type="email" 
                               name="email" 
                               class="form-input" 
                               placeholder="admin@asociacion.com"
                               value="<?php echo $_POST['email'] ?? ''; ?>"
                               required
                               autocomplete="username">
                    </div>
                </div>
                
                <div class="input-group">
                    <label class="input-label">
                        <i class="fas fa-key"></i>
                        Contraseña
                    </label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               name="password" 
                               id="password"
                               class="form-input" 
                               placeholder="••••••••"
                               required
                               autocomplete="current-password">
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="login-btn">
                    <i class="fas fa-sign-in-alt"></i>
                    Acceder al Sistema
                </button>
            </form>
            
            <div class="form-footer">
                <a href="../index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i>
                    Volver al Sitio Principal
                </a>
                <div class="security-info">
                    <i class="fas fa-lock"></i>
                    <span>Conexión segura • Protegido con SSL</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle para mostrar/ocultar contraseña
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });

        // Efectos de entrada para inputs
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.parentElement.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.parentElement.style.transform = 'translateY(0)';
            });
        });

        // Animación de partículas
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const particleCount = 30;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Tamaño aleatorio
                const size = Math.random() * 10 + 5;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Posición aleatoria
                particle.style.left = `${Math.random() * 100}vw`;
                
                // Retraso de animación aleatorio
                particle.style.animationDelay = `${Math.random() * 15}s`;
                
                // Duración de animación aleatoria
                const duration = Math.random() * 10 + 15;
                particle.style.animationDuration = `${duration}s`;
                
                particlesContainer.appendChild(particle);
            }
        }

        // Efecto de entrada para el formulario
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Animación de entrada
            const formPanel = document.querySelector('.login-form-panel');
            formPanel.style.opacity = '0';
            formPanel.style.transform = 'translateX(30px)';
            
            setTimeout(() => {
                formPanel.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                formPanel.style.opacity = '1';
                formPanel.style.transform = 'translateX(0)';
            }, 300);
        });

        // Efecto al enviar formulario
        document.querySelector('.login-form').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('.login-btn');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
            submitBtn.disabled = true;
            
            // Simular carga si es necesario
            setTimeout(() => {
                submitBtn.disabled = false;
            }, 2000);
        });

        // Efecto de focus en inputs
        const inputs = document.querySelectorAll('.form-input');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                const parent = this.closest('.input-group');
                parent.style.transform = 'translateY(-2px)';
                parent.style.transition = 'transform 0.3s ease';
            });
            
            input.addEventListener('blur', function() {
                const parent = this.closest('.input-group');
                parent.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>