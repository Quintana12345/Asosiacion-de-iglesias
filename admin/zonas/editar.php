<?php
require_once '../../includes/config.php';
requireLogin();
if (!isAdmin()) {
    $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
    redirect('../dashboard.php');
}

$db = getDB();

// Obtener ID de la zona a editar
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    $_SESSION['error'] = 'ID de zona no válido';
    redirect('index.php');
}

// Obtener datos de la zona
try {
    $stmt = $db->prepare("SELECT * FROM zonas WHERE id = ?");
    $stmt->execute([$id]);
    $zona = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$zona) {
        $_SESSION['error'] = 'Zona no encontrada';
        redirect('index.php');
    }
} catch (Exception $e) {
    $_SESSION['error'] = 'Error al cargar zona: ' . $e->getMessage();
    redirect('index.php');
}

// Obtener jefes de zona disponibles
$jefesZona = [];
try {
    $stmt = $db->query("SELECT id, nombre_completo, email FROM usuarios WHERE cargo IN ('jefe_zona', 'administrador') AND activo = 1 ORDER BY nombre_completo");
    $jefesZona = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Error al cargar jefes de zona: " . $e->getMessage();
}

// Procesar formulario de edición
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $jefe_zona_id = $_POST['jefe_zona_id'] ?: null;
    $activo = $_POST['activo'] ?? 1;

    try {
        // Validaciones
        if (empty($nombre)) {
            throw new Exception('El nombre de la zona es requerido');
        }

        // Verificar si ya existe otra zona con el mismo nombre
        $stmt = $db->prepare("SELECT id FROM zonas WHERE nombre = ? AND id != ?");
        $stmt->execute([$nombre, $id]);
        if ($stmt->fetch()) {
            throw new Exception('Ya existe otra zona con ese nombre');
        }

        // Actualizar zona - usando activo en lugar de estado
        $stmt = $db->prepare("UPDATE zonas SET nombre = ?, descripcion = ?, jefe_zona_id = ?, activo = ? WHERE id = ?");
        $stmt->execute([$nombre, $descripcion, $jefe_zona_id, $activo, $id]);

        $success = '✅ Zona actualizada exitosamente';
        
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
    <title>Editar Zona - <?php echo SITE_NAME; ?></title>
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
            grid-template-columns: 1fr 1fr;
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
            min-height: 100px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem;
            background: rgba(44, 90, 160, 0.05);
            border-radius: var(--border-radius);
            border: 1px solid rgba(44, 90, 160, 0.1);
        }
        
        .checkbox-input {
            width: 18px;
            height: 18px;
            accent-color: var(--primary);
        }
        
        .checkbox-label {
            font-weight: 500;
            color: var(--dark);
            font-size: 0.9rem;
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
                    <li class="nav-item active">
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
                <h1>Editar Zona</h1>
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
                    <h2><i class="fas fa-edit"></i> Editar Zona: <?php echo htmlspecialchars($zona['nombre']); ?></h2>
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

                    <form method="POST" id="zonaForm">
                        <!-- Información Básica -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Información Básica
                            </h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="nombre" class="form-label required">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Nombre de la Zona
                                    </label>
                                    <input type="text" id="nombre" name="nombre" 
                                           class="form-input" required
                                           value="<?php echo htmlspecialchars($zona['nombre']); ?>"
                                           placeholder="Ej: Zona Norte, Zona Centro...">
                                </div>
                                
                                <div class="form-group">
                                    <label for="activo" class="form-label required">
                                        <i class="fas fa-circle"></i>
                                        Estado
                                    </label>
                                    <select id="activo" name="activo" class="form-select" required>
                                        <option value="1" <?php echo ($zona['activo'] ?? 1) == 1 ? 'selected' : ''; ?>>Activa</option>
                                        <option value="0" <?php echo ($zona['activo'] ?? 1) == 0 ? 'selected' : ''; ?>>Inactiva</option>
                                    </select>
                                </div>
                                
                                <div class="form-group full-width">
                                    <label for="descripcion" class="form-label">
                                        <i class="fas fa-align-left"></i>
                                        Descripción
                                    </label>
                                    <textarea id="descripcion" name="descripcion" rows="3" 
                                              class="form-textarea"
                                              placeholder="Descripción breve de la zona, ubicación, características..."><?php echo htmlspecialchars($zona['descripcion']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Asignación de Liderazgo -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-user-tie"></i>
                                Asignación de Liderazgo
                            </h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="jefe_zona_id" class="form-label">
                                        <i class="fas fa-user"></i>
                                        Jefe de Zona
                                    </label>
                                    <select id="jefe_zona_id" name="jefe_zona_id" class="form-select">
                                        <option value="">Seleccionar jefe de zona (opcional)</option>
                                        <?php foreach ($jefesZona as $jefe): ?>
                                            <option value="<?php echo $jefe['id']; ?>" 
                                                <?php echo ($zona['jefe_zona_id'] ?? '') == $jefe['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($jefe['nombre_completo'] . ' (' . $jefe['email'] . ')'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small style="color: var(--gray); font-size: 0.8rem; margin-top: 0.25rem;">
                                        Solo se muestran usuarios con cargo de "Jefe de Zona" o "Administrador"
                                    </small>
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
                    <button type="submit" form="zonaForm" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Actualizar Zona
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Validación del formulario
            const form = document.getElementById('zonaForm');
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
            constsidebar = document.querySelector('.sidebar');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                if (window.innerWidth <= 1024) {
                    if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                        sidebar.classList.remove('active');
                    }
                }
            });
        });
    </script>
</body>
</html>