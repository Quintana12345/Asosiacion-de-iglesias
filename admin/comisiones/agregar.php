<?php
require_once '../../includes/config.php';
requireLogin();
if (!isAdmin()) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    redirect('../dashboard.php');
}

$db = getDB();
$error = '';
$success = '';

// Obtener datos para selects
$usuarios = [];

try {
    // Obtener usuarios que pueden ser presidentes (administradores y pastores)
    $stmt = $db->query("SELECT id, nombre_completo, cargo, email FROM usuarios WHERE cargo IN ('administrador', 'pastor') AND activo = 1 ORDER BY nombre_completo");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $presidente_id = $_POST['presidente_id'] ?: null;
    $activo = $_POST['activo'] ?? 1;

    try {
        // Validaciones
        if (empty($nombre)) {
            throw new Exception('El nombre de la comisión es requerido');
        }

        // Verificar si ya existe una comisión con el mismo nombre
        $stmt = $db->prepare("SELECT id FROM comisiones WHERE nombre = ?");
        $stmt->execute([$nombre]);
        if ($stmt->fetch()) {
            throw new Exception('Ya existe una comisión con ese nombre');
        }

        // Insertar nueva comisión
        $stmt = $db->prepare("INSERT INTO comisiones (nombre, descripcion, presidente_id, activo, fecha_creacion) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$nombre, $descripcion, $presidente_id, $activo]);

        $success = '✅ Comisión creada exitosamente';
        $_POST = []; // Limpiar formulario
        
    } catch (Exception $e) {
        $error = '❌ ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar Comisión - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        <?php include '../admin-styles.css'; ?>
        
        .form-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .form-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
        }
        
        .form-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .form-body {
            padding: 2rem;
        }
        
        .form-section {
            margin-bottom: 2.5rem;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray-light);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .required::after {
            content: '*';
            color: var(--danger);
            margin-left: 0.25rem;
        }
        
        .form-input, .form-select, .form-textarea {
            padding: 0.875rem 1rem;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            transition: var(--transition);
            font-family: inherit;
            background: white;
        }
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 90, 160, 0.1);
            transform: translateY(-1px);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2rem;
            border-top: 1px solid var(--gray-light);
            background: #f8f9fa;
        }
        
        .alert {
            padding: 1.25rem 1.5rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
        }
        
        .alert-error {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.2);
            color: #c0392b;
        }
        
        .alert-success {
            background: rgba(39, 174, 96, 0.1);
            border: 1px solid rgba(39, 174, 96, 0.2);
            color: #27ae60;
        }
        
        .info-text {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 0.25rem;
            font-style: italic;
        }
        
        .user-option {
            display: flex;
            flex-direction: column;
            padding: 0.5rem 0;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--dark);
        }
        
        .user-details {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 0.25rem;
        }
        
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-body {
                padding: 1.5rem;
            }
            
            .form-actions {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
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
                <h1>Agregar Comisión</h1>
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
            <div class="form-container">
                <div class="form-header">
                    <h2><i class="fas fa-plus-circle"></i> Registrar Nueva Comisión</h2>
                </div>
                
                <div class="form-body">
                    <?php if ($error): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success; ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" id="comisionForm">
                        <!-- Información Básica -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Información Básica
                            </h3>
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label for="nombre" class="form-label required">
                                        <i class="fas fa-tag"></i>
                                        Nombre de la Comisión
                                    </label>
                                    <input type="text" id="nombre" name="nombre" 
                                           class="form-input" required
                                           value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                                           placeholder="Ej: Comisión de Evangelismo, Comisión Social...">
                                    <div class="info-text">Nombre oficial y descriptivo de la comisión</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="presidente_id" class="form-label">
                                        <i class="fas fa-user-tie"></i>
                                        Presidente
                                    </label>
                                    <select id="presidente_id" name="presidente_id" class="form-select">
                                        <option value="">Seleccionar presidente (opcional)</option>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <option value="<?php echo $usuario['id']; ?>" 
                                                <?php echo ($_POST['presidente_id'] ?? '') == $usuario['id'] ? 'selected' : ''; ?>>
                                                <div class="user-option">
                                                    <span class="user-name"><?php echo htmlspecialchars($usuario['nombre_completo']); ?></span>
                                                    <span class="user-details"><?php echo htmlspecialchars(ucfirst($usuario['cargo']) . ' - ' . $usuario['email']); ?></span>
                                                </div>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="info-text">Solo se muestran usuarios con cargo de "Administrador" o "Pastor"</div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="activo" class="form-label required">
                                        <i class="fas fa-circle"></i>
                                        Estado
                                    </label>
                                    <select id="activo" name="activo" class="form-select" required>
                                        <option value="1" <?php echo ($_POST['activo'] ?? 1) == 1 ? 'selected' : ''; ?>>Activa</option>
                                        <option value="0" <?php echo ($_POST['activo'] ?? 1) == 0 ? 'selected' : ''; ?>>Inactiva</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Descripción -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-align-left"></i>
                                Descripción
                            </h3>
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label for="descripcion" class="form-label">
                                        <i class="fas fa-file-alt"></i>
                                        Descripción Detallada
                                    </label>
                                    <textarea id="descripcion" name="descripcion" rows="6" 
                                              class="form-textarea"
                                              placeholder="Describe los objetivos, funciones y alcance de esta comisión..."><?php echo htmlspecialchars($_POST['descripcion'] ?? ''); ?></textarea>
                                    <div class="info-text">Incluye objetivos, funciones específicas, alcance y cualquier información relevante</div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="form-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Cancelar y Volver
                    </a>
                    <button type="submit" form="comisionForm" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Guardar Comisión
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Validación del formulario
            const form = document.getElementById('comisionForm');
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let valid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        valid = false;
                        field.style.borderColor = 'var(--danger)';
                    } else {
                        field.style.borderColor = '';
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    alert('Por favor complete todos los campos requeridos');
                }
            });

            // Efectos visuales para los inputs
            const inputs = document.querySelectorAll('.form-input, .form-select, .form-textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.style.transform = 'translateY(-2px)';
                });
                
                input.addEventListener('blur', function() {
                    this.parentElement.style.transform = 'translateY(0)';
                });
            });

            // Toggle sidebar
            const sidebarToggle = document.querySelector('.sidebar-toggle');
            const sidebar = document.querySelector('.sidebar');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
        });
    </script>
</body>
</html>