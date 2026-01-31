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
$comision = null;

// Obtener ID de la comisión
$id = $_GET['id'] ?? 0;
if (!$id) {
    $_SESSION['error'] = 'No se especificó la comisión a editar';
    redirect('index.php');
}

// Obtener datos de la comisión
try {
    $stmt = $db->prepare("
        SELECT c.*, 
               u.nombre_completo as presidente_nombre,
               u.cargo as presidente_cargo
        FROM comisiones c 
        LEFT JOIN usuarios u ON c.presidente_id = u.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$id]);
    $comision = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$comision) {
        $_SESSION['error'] = 'Comisión no encontrada';
        redirect('index.php');
    }
    
} catch (Exception $e) {
    $error = "Error al cargar la comisión: " . $e->getMessage();
}

// Obtener datos para selects
$usuarios = [];

try {
    // Obtener usuarios que pueden ser presidentes (administradores y pastores)
    $stmt = $db->query("SELECT id, nombre_completo, cargo, email FROM usuarios WHERE cargo IN ('administrador', 'pastor') AND activo = 1 ORDER BY nombre_completo");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    $error = "Error al cargar usuarios: " . $e->getMessage();
}

// Procesar formulario de edición
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

        // Verificar si ya existe otra comisión con el mismo nombre (excluyendo la actual)
        $stmt = $db->prepare("SELECT id FROM comisiones WHERE nombre = ? AND id != ?");
        $stmt->execute([$nombre, $id]);
        if ($stmt->fetch()) {
            throw new Exception('Ya existe otra comisión con ese nombre');
        }

        // Actualizar comisión
        $stmt = $db->prepare("
            UPDATE comisiones 
            SET nombre = ?, 
                descripcion = ?, 
                presidente_id = ?, 
                activo = ?
            WHERE id = ?
        ");
        $stmt->execute([$nombre, $descripcion, $presidente_id, $activo, $id]);

        // Si se asignó un presidente, actualizar su campo es_presidente_comision
        if ($presidente_id) {
            // Primero, quitar presidente anterior si existe
            if ($comision['presidente_id']) {
                $stmt_quitar = $db->prepare("
                    UPDATE usuarios 
                    SET es_presidente_comision = 0 
                    WHERE id = ?
                ");
                $stmt_quitar->execute([$comision['presidente_id']]);
            }
            
            // Asignar nuevo presidente
            $stmt_asignar = $db->prepare("
                UPDATE usuarios 
                SET es_presidente_comision = 1,
                    comision_id = ?
                WHERE id = ?
            ");
            $stmt_asignar->execute([$id, $presidente_id]);
        } elseif ($comision['presidente_id']) {
            // Si se quitó el presidente, actualizar el usuario anterior
            $stmt_quitar = $db->prepare("
                UPDATE usuarios 
                SET es_presidente_comision = 0 
                WHERE id = ?
            ");
            $stmt_quitar->execute([$comision['presidente_id']]);
        }

        $success = '✅ Comisión actualizada exitosamente';
        
        // Recargar datos actualizados
        $stmt = $db->prepare("SELECT * FROM comisiones WHERE id = ?");
        $stmt->execute([$id]);
        $comision = $stmt->fetch(PDO::FETCH_ASSOC);
        
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
    <title>Editar Comisión - <?php echo SITE_NAME; ?></title>
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
        
        .info-box {
            background: linear-gradient(135deg, var(--primary-light) 0%, #f8fafc 100%);
            border: 1px solid rgba(44, 90, 160, 0.1);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .info-box h4 {
            color: var(--primary);
            margin: 0 0 0.5rem 0;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .info-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .info-label {
            font-size: 0.85rem;
            color: var(--gray);
            font-weight: 500;
        }
        
        .info-value {
            font-weight: 600;
            color: var(--dark);
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .badge-activo {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
            border: 1px solid rgba(39, 174, 96, 0.2);
        }
        
        .badge-inactivo {
            background: rgba(231, 76, 60, 0.1);
            color: #c0392b;
            border: 1px solid rgba(231, 76, 60, 0.2);
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
        
        .info-text {
            font-size: 0.85rem;
            color: var(--gray);
            margin-top: 0.25rem;
            font-style: italic;
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
                <h1>Editar Comisión</h1>
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
                    <h2><i class="fas fa-edit"></i> Editar Comisión: <?php echo htmlspecialchars($comision['nombre']); ?></h2>
                </div>
                
                <div class="form-body">
                    <!-- Información de la comisión -->
                    <div class="info-box">
                        <h4><i class="fas fa-info-circle"></i> Información Actual</h4>
                        <div class="info-grid">
                            <div class="info-item">
                                <span class="info-label">ID:</span>
                                <span class="info-value"><?php echo $comision['id']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Creada el:</span>
                                <span class="info-value"><?php echo date('d/m/Y H:i', strtotime($comision['fecha_creacion'])); ?></span>
                            </div>
                            <div class="info-item">
                                <span class="info-label">Estado actual:</span>
                                <span class="badge <?php echo $comision['activo'] ? 'badge-activo' : 'badge-inactivo'; ?>">
                                    <?php echo $comision['activo'] ? 'Activa' : 'Inactiva'; ?>
                                </span>
                            </div>
                            <?php if ($comision['presidente_nombre']): ?>
                            <div class="info-item">
                                <span class="info-label">Presidente actual:</span>
                                <span class="info-value"><?php echo htmlspecialchars($comision['presidente_nombre'] . ' - ' . ucfirst($comision['presidente_cargo'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

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
                                           value="<?php echo htmlspecialchars($comision['nombre']); ?>"
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
                                                <?php echo ($comision['presidente_id'] ?? '') == $usuario['id'] ? 'selected' : ''; ?>>
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
                                        <option value="1" <?php echo $comision['activo'] == 1 ? 'selected' : ''; ?>>Activa</option>
                                        <option value="0" <?php echo $comision['activo'] == 0 ? 'selected' : ''; ?>>Inactiva</option>
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
                                              placeholder="Describe los objetivos, funciones y alcance de esta comisión..."><?php echo htmlspecialchars($comision['descripcion'] ?? ''); ?></textarea>
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
                    <div class="action-buttons">
                        <a href="detalles.php?id=<?php echo $id; ?>" class="btn btn-info">
                            <i class="fas fa-eye"></i>
                            Ver Detalles
                        </a>
                        <button type="submit" form="comisionForm" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Guardar Cambios
                        </button>
                    </div>
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

            // Confirmar cambios importantes
            const nombreInput = document.getElementById('nombre');
            const nombreOriginal = '<?php echo addslashes($comision['nombre']); ?>';
            
            const estadoSelect = document.getElementById('activo');
            const estadoOriginal = '<?php echo $comision['activo']; ?>';
            
            const presidenteSelect = document.getElementById('presidente_id');
            const presidenteOriginal = '<?php echo $comision['presidente_id'] ?? ''; ?>';
            
            form.addEventListener('submit', function(e) {
                // Si el nombre cambió, confirmar
                if (nombreInput.value.trim() !== nombreOriginal) {
                    if (!confirm('¿Está seguro de cambiar el nombre de la comisión?')) {
                        e.preventDefault();
                        return;
                    }
                }
                
                // Si el estado cambia de activo a inactivo, confirmar
                if (estadoOriginal == '1' && estadoSelect.value == '0') {
                    if (!confirm('¿Está seguro de desactivar esta comisión? Esto podría afectar su visibilidad en el sistema.')) {
                        e.preventDefault();
                        return;
                    }
                }
                
                // Si se cambia el presidente, confirmar
                if (presidenteSelect.value !== presidenteOriginal) {
                    if (presidenteOriginal && !presidenteSelect.value) {
                        // Se está quitando el presidente
                        if (!confirm('¿Está seguro de quitar el presidente de esta comisión?')) {
                            e.preventDefault();
                            return;
                        }
                    } else if (!presidenteOriginal && presidenteSelect.value) {
                        // Se está asignando un nuevo presidente
                        const presidenteNombre = presidenteSelect.options[presidenteSelect.selectedIndex].text;
                        if (!confirm('¿Está seguro de asignar a ' + presidenteNombre + ' como presidente de esta comisión?')) {
                            e.preventDefault();
                            return;
                        }
                    } else if (presidenteOriginal && presidenteSelect.value && presidenteOriginal !== presidenteSelect.value) {
                        // Se está cambiando el presidente
                        const presidenteNombre = presidenteSelect.options[presidenteSelect.selectedIndex].text;
                        if (!confirm('¿Está seguro de cambiar el presidente de esta comisión? El presidente anterior perderá este cargo.')) {
                            e.preventDefault();
                            return;
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>