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

// Obtener ID de la iglesia
$id = $_GET['id'] ?? 0;
if (!$id) {
    $_SESSION['error'] = 'No se especificó la iglesia';
    redirect('index.php');
}

// Obtener datos de la iglesia
$iglesia = null;
try {
    $stmt = $db->prepare("
        SELECT i.*, s.zona_id as zona_id
        FROM iglesias i 
        LEFT JOIN sectores s ON i.sector_id = s.id
        WHERE i.id = ?
    ");
    $stmt->execute([$id]);
    $iglesia = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$iglesia) {
        $_SESSION['error'] = 'Iglesia no encontrada';
        redirect('index.php');
    }
} catch (Exception $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

// Obtener datos para selects
$zonas = [];
$sectores = [];
$pastores = [];

try {
    // Obtener zonas activas
    $stmt = $db->query("SELECT id, nombre FROM zonas WHERE activo = 1 ORDER BY nombre");
    $zonas = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Obtener sectores de la zona actual (si existe)
    if ($iglesia && $iglesia['zona_id']) {
        $stmt = $db->prepare("SELECT id, nombre FROM sectores WHERE zona_id = ? AND activo = 1 ORDER BY nombre");
        $stmt->execute([$iglesia['zona_id']]);
        $sectores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Obtener pastores (usuarios con cargo pastor o administrador)
    $stmt = $db->query("SELECT id, nombre_completo FROM usuarios WHERE cargo IN ('pastor', 'administrador') AND activo = 1 ORDER BY nombre_completo");
    $pastores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $sector_id = $_POST['sector_id'] ?: null;
    $pastor_id = $_POST['pastor_id'] ?: null;
    $activo = $_POST['activo'] ?? 1;

    try {
        // Validaciones
        if (empty($nombre)) {
            throw new Exception('El nombre de la iglesia es requerido');
        }

        if (empty($sector_id)) {
            throw new Exception('Debe seleccionar un sector');
        }

        // Verificar si ya existe otra iglesia con el mismo nombre en el mismo sector
        $stmt = $db->prepare("SELECT id FROM iglesias WHERE nombre = ? AND sector_id = ? AND id != ?");
        $stmt->execute([$nombre, $sector_id, $id]);
        if ($stmt->fetch()) {
            throw new Exception('Ya existe otra iglesia con ese nombre en este sector');
        }

        // Actualizar iglesia (solo campos que existen)
        $stmt = $db->prepare("UPDATE iglesias SET 
            nombre = ?, 
            direccion = ?, 
            telefono = ?, 
            sector_id = ?, 
            pastor_id = ?, 
            activo = ?
            WHERE id = ?");
        
        $stmt->execute([
            $nombre, $direccion, $telefono, $sector_id, $pastor_id, $activo, $id
        ]);

        $success = '✅ Iglesia actualizada exitosamente';
        
        // Actualizar datos locales
        $iglesia = array_merge($iglesia, [
            'nombre' => $nombre,
            'direccion' => $direccion,
            'telefono' => $telefono,
            'sector_id' => $sector_id,
            'pastor_id' => $pastor_id,
            'activo' => $activo
        ]);
        
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
    <title>Editar Iglesia - <?php echo SITE_NAME; ?></title>
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
            min-height: 100px;
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
        
        .back-button {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--primary);
            text-decoration: none;
            font-weight: 500;
            margin-bottom: 1.5rem;
            padding: 0.5rem 1rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
            width: fit-content;
        }
        
        .back-button:hover {
            background: rgba(44, 90, 160, 0.05);
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
                    <li class="nav-item active">
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
                <h1>Editar Iglesia</h1>
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
            <a href="detalles.php?id=<?php echo $id; ?>" class="back-button">
                <i class="fas fa-arrow-left"></i>
                Volver a detalles
            </a>
            
            <div class="form-container">
                <div class="form-header">
                    <h2><i class="fas fa-edit"></i> Editar Iglesia: <?php echo htmlspecialchars($iglesia['nombre']); ?></h2>
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

                    <form method="POST" id="iglesiaForm">
                        <!-- Información Básica -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Información Básica
                            </h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="nombre" class="form-label required">
                                        <i class="fas fa-church"></i>
                                        Nombre de la Iglesia
                                    </label>
                                    <input type="text" id="nombre" name="nombre" 
                                           class="form-input" required
                                           value="<?php echo htmlspecialchars($iglesia['nombre']); ?>"
                                           placeholder="Ej: Iglesia Central, Iglesia del Nazareno...">
                                </div>
                                
                                <div class="form-group">
                                    <label for="zona_id" class="form-label required">
                                        <i class="fas fa-map-marker-alt"></i>
                                        Zona
                                    </label>
                                    <select id="zona_id" name="zona_id" class="form-select" required onchange="cargarSectores()">
                                        <option value="">Seleccionar zona</option>
                                        <?php foreach ($zonas as $zona): ?>
                                            <option value="<?php echo $zona['id']; ?>" 
                                                <?php echo ($iglesia['zona_id'] ?? '') == $zona['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($zona['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="sector_id" class="form-label required">
                                        <i class="fas fa-layer-group"></i>
                                        Sector
                                    </label>
                                    <select id="sector_id" name="sector_id" class="form-select" required>
                                        <option value="">Seleccionar sector</option>
                                        <?php foreach ($sectores as $sector): ?>
                                            <option value="<?php echo $sector['id']; ?>" 
                                                <?php echo $iglesia['sector_id'] == $sector['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($sector['nombre']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="activo" class="form-label required">
                                        <i class="fas fa-circle"></i>
                                        Estado
                                    </label>
                                    <select id="activo" name="activo" class="form-select" required>
                                        <option value="1" <?php echo $iglesia['activo'] ? 'selected' : ''; ?>>Activa</option>
                                        <option value="0" <?php echo !$iglesia['activo'] ? 'selected' : ''; ?>>Inactiva</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Información de Contacto -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-address-book"></i>
                                Información de Contacto
                            </h3>
                            <div class="form-grid">
                                <div class="form-group full-width">
                                    <label for="direccion" class="form-label">
                                        <i class="fas fa-map-pin"></i>
                                        Dirección
                                    </label>
                                    <textarea id="direccion" name="direccion" rows="3" 
                                              class="form-textarea"
                                              placeholder="Dirección completa de la iglesia..."><?php echo htmlspecialchars($iglesia['direccion'] ?? ''); ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="telefono" class="form-label">
                                        <i class="fas fa-phone"></i>
                                        Teléfono
                                    </label>
                                    <input type="tel" id="telefono" name="telefono" 
                                           class="form-input"
                                           value="<?php echo htmlspecialchars($iglesia['telefono'] ?? ''); ?>"
                                           placeholder="Ej: +51 987654321">
                                </div>
                            </div>
                        </div>

                        <!-- Liderazgo -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-user-tie"></i>
                                Liderazgo
                            </h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="pastor_id" class="form-label">
                                        <i class="fas fa-hands-praying"></i>
                                        Pastor
                                    </label>
                                    <select id="pastor_id" name="pastor_id" class="form-select">
                                        <option value="">Seleccionar pastor (opcional)</option>
                                        <?php foreach ($pastores as $pastor): ?>
                                            <option value="<?php echo $pastor['id']; ?>" 
                                                <?php echo ($iglesia['pastor_id'] ?? '') == $pastor['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($pastor['nombre_completo']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="info-text">Solo se muestran usuarios con cargo de "Pastor" o "Administrador"</div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <div class="form-actions">
                    <a href="detalles.php?id=<?php echo $id; ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i>
                        Cancelar
                    </a>
                    <button type="submit" form="iglesiaForm" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Validación del formulario
            const form = document.getElementById('iglesiaForm');
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

        // Cargar sectores por zona
        function cargarSectores() {
            const zonaId = document.getElementById('zona_id').value;
            const sectorSelect = document.getElementById('sector_id');
            
            if (zonaId) {
                // Mostrar loading
                sectorSelect.innerHTML = '<option value="">Cargando sectores...</option>';
                
                // Usar fetch para cargar sectores
                fetch(`../../api/sectores.php?action=by_zona&zona_id=${zonaId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error en la respuesta del servidor');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.sectores) {
                            sectorSelect.innerHTML = '<option value="">Seleccionar sector</option>';
                            data.sectores.forEach(sector => {
                                const option = document.createElement('option');
                                option.value = sector.id;
                                option.textContent = sector.nombre;
                                sectorSelect.appendChild(option);
                            });
                        } else {
                            sectorSelect.innerHTML = '<option value="">Error cargando sectores</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error cargando sectores:', error);
                        sectorSelect.innerHTML = '<option value="">Error al conectar con el servidor</option>';
                    });
            } else {
                sectorSelect.innerHTML = '<option value="">Primero seleccione una zona</option>';
            }
        }
    </script>
</body>
</html>