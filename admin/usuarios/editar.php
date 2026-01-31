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

// Obtener datos del usuario a editar
$id = $_GET['id'] ?? 0;
$usuario = null;

if ($id) {
    $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$usuario) {
        $error = "Usuario no encontrado";
    }
}

if ($_POST && $usuario) {
    try {
        $nombre = sanitizeInput($_POST['nombre_completo']);
        $email = sanitizeInput($_POST['email']);
        $password = $_POST['password'];
        $rfc = sanitizeInput($_POST['rfc']);
        $telefono = sanitizeInput($_POST['telefono']);
        $celular = sanitizeInput($_POST['celular']);
        $cargo = sanitizeInput($_POST['cargo']);
        $zona_id = $_POST['zona_id'] ?: null;
        $comision_id = $_POST['comision_id'] ?: null;
        $es_presidente = isset($_POST['es_presidente_comision']) ? 1 : 0;
        $activo = isset($_POST['activo']) ? 1 : 0;

        // Validaciones
        if (empty($nombre) || empty($email)) {
            throw new Exception("Todos los campos marcados con * son obligatorios");
        }

        // Validar email único (excluyendo el usuario actual)
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            throw new Exception("El email ya está registrado en el sistema");
        }

        // Construir query de actualización
        $query = "UPDATE usuarios SET 
                 nombre_completo = ?, email = ?, rfc = ?, telefono = ?, 
                 celular = ?, cargo = ?, zona_id = ?, comision_id = ?, 
                 es_presidente_comision = ?, activo = ?";
        
        $params = [$nombre, $email, $rfc, $telefono, $celular, $cargo, 
                  $zona_id, $comision_id, $es_presidente, $activo];

        // Si se proporcionó nueva contraseña
        if (!empty($password)) {
            $query .= ", password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }

        $query .= " WHERE id = ?";
        $params[] = $id;
        
        $stmt = $db->prepare($query);
        $stmt->execute($params);
        
        $success = "✅ Usuario actualizado exitosamente";
        
        // Actualizar datos del usuario en la variable
        $usuario = array_merge($usuario, $_POST);
        $usuario['activo'] = $activo;
        
    } catch (Exception $e) {
        $error = "❌ " . $e->getMessage();
    }
}

// Obtener datos para selects
$zonas = getZonas();
$comisiones = getComisiones();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuario - <?php echo SITE_NAME; ?></title>
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
            background: linear-gradient(135deg, var(--warning) 0%, #e67e22 100%);
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
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        
        .status-active {
            background: rgba(39, 174, 96, 0.1);
            color: #27ae60;
            border: 1px solid rgba(39, 174, 96, 0.2);
        }
        
        .status-inactive {
            background: rgba(231, 76, 60, 0.1);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.2);
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
        
        .password-note {
            font-size: 0.8rem;
            color: var(--gray);
            font-style: italic;
            margin-top: 0.5rem;
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
                        <a href="index.php">
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
                <h1>Editar Usuario</h1>
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
            <?php if ($error && !$usuario): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
                <div class="form-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Volver a la lista
                    </a>
                </div>
            <?php else: ?>
            <div class="form-container">
                <div class="form-header">
                    <h2><i class="fas fa-user-edit"></i> Editar Usuario</h2>
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

                    <?php if ($usuario): ?>
                    <form method="POST" id="userForm">
                        <!-- Información Personal -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-id-card"></i>
                                Información Personal
                            </h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="nombre_completo" class="form-label required">
                                        <i class="fas fa-user"></i>
                                        Nombre Completo
                                    </label>
                                    <input type="text" id="nombre_completo" name="nombre_completo" 
                                           class="form-input" required
                                           value="<?php echo htmlspecialchars($usuario['nombre_completo']); ?>"
                                           placeholder="Ej: Juan Pérez García">
                                </div>
                                
                                <div class="form-group">
                                    <label for="email" class="form-label required">
                                        <i class="fas fa-envelope"></i>
                                        Correo Electrónico
                                    </label>
                                    <input type="email" id="email" name="email" 
                                           class="form-input" required
                                           value="<?php echo htmlspecialchars($usuario['email']); ?>"
                                           placeholder="usuario@asociacion.com">
                                </div>
                                
                                <div class="form-group">
                                    <label for="rfc" class="form-label">
                                        <i class="fas fa-id-card"></i>
                                        RFC
                                    </label>
                                    <input type="text" id="rfc" name="rfc" 
                                           class="form-input"
                                           value="<?php echo htmlspecialchars($usuario['rfc']); ?>"
                                           placeholder="Ej: XAXX010101000">
                                </div>
                                
                                <div class="form-group">
                                    <label for="telefono" class="form-label">
                                        <i class="fas fa-phone"></i>
                                        Teléfono Fijo
                                    </label>
                                    <input type="tel" id="telefono" name="telefono" 
                                           class="form-input"
                                           value="<?php echo htmlspecialchars($usuario['telefono']); ?>"
                                           placeholder="Ej: 7441234567">
                                </div>
                                
                                <div class="form-group">
                                    <label for="celular" class="form-label">
                                        <i class="fas fa-mobile-alt"></i>
                                        Celular
                                    </label>
                                    <input type="tel" id="celular" name="celular" 
                                           class="form-input"
                                           value="<?php echo htmlspecialchars($usuario['celular']); ?>"
                                           placeholder="Ej: 7449876543">
                                </div>
                            </div>
                        </div>

                        <!-- Información de Cuenta -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-user-shield"></i>
                                Información de Cuenta
                            </h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="password" class="form-label">
                                        <i class="fas fa-lock"></i>
                                        Nueva Contraseña
                                    </label>
                                    <input type="password" id="password" name="password" 
                                           class="form-input"
                                           placeholder="Dejar en blanco para mantener la actual"
                                           minlength="8">
                                    <div class="password-note">
                                        Solo complete este campo si desea cambiar la contraseña
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="cargo" class="form-label required">
                                        <i class="fas fa-briefcase"></i>
                                        Cargo
                                    </label>
                                    <select id="cargo" name="cargo" class="form-select" required>
                                        <option value="">Seleccionar cargo</option>
                                        <option value="pastor" <?php echo $usuario['cargo'] == 'pastor' ? 'selected' : ''; ?>>Pastor</option>
                                        <option value="jefe_sector" <?php echo $usuario['cargo'] == 'jefe_sector' ? 'selected' : ''; ?>>Jefe de Sector</option>
                                        <option value="jefe_zona" <?php echo $usuario['cargo'] == 'jefe_zona' ? 'selected' : ''; ?>>Jefe de Zona</option>
                                        <option value="administrador" <?php echo $usuario['cargo'] == 'administrador' ? 'selected' : ''; ?>>Administrador</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-user-clock"></i>
                                        Estado del Usuario
                                    </label>
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="activo" name="activo" value="1"
                                               class="checkbox-input"
                                               <?php echo $usuario['activo'] ? 'checked' : ''; ?>>
                                        <label for="activo" class="checkbox-label">
                                            Usuario activo en el sistema
                                        </label>
                                    </div>
                                    <div class="status-badge <?php echo $usuario['activo'] ? 'status-active' : 'status-inactive'; ?>">
                                        <i class="fas fa-circle"></i>
                                        <?php echo $usuario['activo'] ? 'Activo' : 'Inactivo'; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Asignaciones -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-map-marker-alt"></i>
                                Asignaciones
                            </h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label for="zona_id" class="form-label">
                                        <i class="fas fa-map"></i>
                                        Zona Asignada
                                    </label>
                                    <select id="zona_id" name="zona_id" class="form-select">
                                        <option value="">Seleccionar zona (opcional)</option>
                                        <?php foreach ($zonas as $zona): ?>
                                        <option value="<?php echo $zona['id']; ?>" 
                                            <?php echo $usuario['zona_id'] == $zona['id'] ? 'selected' : ''; ?>>
                                            <?php echo $zona['nombre']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="comision_id" class="form-label">
                                        <i class="fas fa-tasks"></i>
                                        Comisión
                                    </label>
                                    <select id="comision_id" name="comision_id" class="form-select">
                                        <option value="">Seleccionar comisión (opcional)</option>
                                        <?php foreach ($comisiones as $comision): ?>
                                        <option value="<?php echo $comision['id']; ?>" 
                                            <?php echo $usuario['comision_id'] == $comision['id'] ? 'selected' : ''; ?>>
                                            <?php echo $comision['nombre']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group full-width">
                                    <div class="checkbox-group">
                                        <input type="checkbox" id="es_presidente_comision" name="es_presidente_comision" value="1"
                                               class="checkbox-input"
                                               <?php echo $usuario['es_presidente_comision'] ? 'checked' : ''; ?>>
                                        <label for="es_presidente_comision" class="checkbox-label">
                                            Este usuario es presidente de la comisión asignada
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Información del Sistema -->
                        <div class="form-section">
                            <h3 class="section-title">
                                <i class="fas fa-info-circle"></i>
                                Información del Sistema
                            </h3>
                            <div class="form-grid">
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-calendar-plus"></i>
                                        Fecha de Registro
                                    </label>
                                    <div style="padding: 0.875rem 1rem; background: #f8f9fa; border-radius: var(--border-radius); color: var(--gray);">
                                        <?php echo formatDate($usuario['fecha_creacion'], 'd/m/Y H:i:s'); ?>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label class="form-label">
                                        <i class="fas fa-calendar-check"></i>
                                        Última Actualización
                                    </label>
                                    <div style="padding: 0.875rem 1rem; background: #f8f9fa; border-radius: var(--border-radius); color: var(--gray);">
                                        <?php echo formatDate($usuario['fecha_actualizacion'], 'd/m/Y H:i:s'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Cancelar y Volver
                    </a>
                    <?php if ($usuario): ?>
                    <button type="submit" form="userForm" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Actualizar Usuario
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Validación del formulario
            const form = document.getElementById('userForm');
            if (form) {
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
                    
                    // Validar email
                    const email = document.getElementById('email');
                    if (email && email.value && !isValidEmail(email.value)) {
                        valid = false;
                        email.style.borderColor = 'var(--danger)';
                        alert('Por favor ingrese un email válido');
                    }
                    
                    if (!valid) {
                        e.preventDefault();
                        alert('Por favor complete todos los campos requeridos correctamente');
                    }
                });

                function isValidEmail(email) {
                    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    return re.test(email);
                }

                // Efectos visuales para los inputs
                const inputs = document.querySelectorAll('.form-input, .form-select');
                inputs.forEach(input => {
                    input.addEventListener('focus', function() {
                        this.parentElement.style.transform = 'translateY(-2px)';
                    });
                    
                    input.addEventListener('blur', function() {
                        this.parentElement.style.transform = 'translateY(0)';
                    });
                });

                // Actualizar badge de estado
                const statusCheckbox = document.getElementById('activo');
                const statusBadge = document.querySelector('.status-badge');
                
                if (statusCheckbox && statusBadge) {
                    statusCheckbox.addEventListener('change', function() {
                        if (this.checked) {
                            statusBadge.className = 'status-badge status-active';
                            statusBadge.innerHTML = '<i class="fas fa-circle"></i> Activo';
                        } else {
                            statusBadge.className = 'status-badge status-inactive';
                            statusBadge.innerHTML = '<i class="fas fa-circle"></i> Inactivo';
                        }
                    });
                }
            }
        });
    </script>
</body>
</html>