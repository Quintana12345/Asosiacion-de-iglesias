<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
requireAdmin();

$db = getDB();

$error = '';
$success = '';

// Obtener el ID del evento a editar
$id = $_GET['id'] ?? 0;

if (!$id) {
    header('Location: index.php');
    exit;
}

// Obtener datos del evento
$stmt = $db->prepare("SELECT * FROM eventos WHERE id = ?");
$stmt->execute([$id]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
    $_SESSION['error_msg'] = 'Evento no encontrado';
    header('Location: index.php');
    exit;
}

// Obtener datos para selects
$comisiones = $db->query("SELECT id, nombre FROM comisiones WHERE activo = 1 ORDER BY nombre")->fetchAll();
$zonas = $db->query("SELECT id, nombre FROM zonas WHERE activo = 1 ORDER BY nombre")->fetchAll();
$sectores = $db->query("SELECT id, nombre FROM sectores WHERE activo = 1 ORDER BY nombre")->fetchAll();

// Si es POST, procesar actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Recoger y sanitizar datos
        $titulo = trim($_POST['titulo'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $fecha_inicio = $_POST['fecha_inicio'] ?? '';
        $fecha_fin = $_POST['fecha_fin'] ?? $fecha_inicio;
        $ubicacion = trim($_POST['ubicacion'] ?? '');
        $comision_id = !empty($_POST['comision_id']) ? (int)$_POST['comision_id'] : null;
        $zona_id = !empty($_POST['zona_id']) ? (int)$_POST['zona_id'] : null;
        $sector_id = !empty($_POST['sector_id']) ? (int)$_POST['sector_id'] : null;
        $estado = $_POST['estado'] ?? 'activo';
        
        // Validaciones básicas
        if (empty($titulo)) {
            $error = 'El título es obligatorio';
        } elseif (strlen($titulo) > 255) {
            $error = 'El título no puede tener más de 255 caracteres';
        } elseif (empty($fecha_inicio)) {
            $error = 'La fecha de inicio es obligatoria';
        } elseif ($fecha_fin && strtotime($fecha_inicio) > strtotime($fecha_fin)) {
            $error = 'La fecha de fin no puede ser anterior a la fecha de inicio';
        } else {
            // Actualizar evento
            $stmt = $db->prepare("UPDATE eventos SET 
                titulo = ?, 
                descripcion = ?, 
                fecha_inicio = ?, 
                fecha_fin = ?, 
                ubicacion = ?, 
                comision_id = ?, 
                zona_id = ?, 
                sector_id = ?, 
                estado = ?
                WHERE id = ?");
            
            $stmt->execute([
                $titulo, 
                $descripcion, 
                $fecha_inicio, 
                $fecha_fin,
                $ubicacion,
                $comision_id,
                $zona_id,
                $sector_id,
                $estado,
                $id
            ]);
            
            $_SESSION['success_msg'] = 'Evento actualizado exitosamente';
            header('Location: index.php');
            exit;
        }
        
    } catch (PDOException $e) {
        $error = 'Error al actualizar el evento: ' . $e->getMessage();
    }
}

// Establecer valores por defecto (si no hay POST, usar los de la BD)
$defaults = [
    'titulo' => $evento['titulo'] ?? '',
    'descripcion' => $evento['descripcion'] ?? '',
    'fecha_inicio' => $evento['fecha_inicio'] ?? date('Y-m-d') . ' 09:00',
    'fecha_fin' => $evento['fecha_fin'] ?? $evento['fecha_inicio'] ?? date('Y-m-d') . ' 18:00',
    'ubicacion' => $evento['ubicacion'] ?? '',
    'comision_id' => $evento['comision_id'] ?? '',
    'zona_id' => $evento['zona_id'] ?? '',
    'sector_id' => $evento['sector_id'] ?? '',
    'estado' => $evento['estado'] ?? 'activo'
];

// Si hay datos POST (por error), sobreescribir con ellos
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $defaults = [
        'titulo' => $_POST['titulo'] ?? '',
        'descripcion' => $_POST['descripcion'] ?? '',
        'fecha_inicio' => $_POST['fecha_inicio'] ?? '',
        'fecha_fin' => $_POST['fecha_fin'] ?? '',
        'ubicacion' => $_POST['ubicacion'] ?? '',
        'comision_id' => $_POST['comision_id'] ?? '',
        'zona_id' => $_POST['zona_id'] ?? '',
        'sector_id' => $_POST['sector_id'] ?? '',
        'estado' => $_POST['estado'] ?? 'activo'
    ];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Evento - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        <?php include '../admin-styles.css'; ?>
        
        .content-container {
            padding: 2rem;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .page-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }
        
        .page-header h1 {
            margin: 0;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.75rem;
        }
        
        .page-header p {
            color: var(--gray);
            margin: 0.5rem 0 0 0;
            font-size: 0.95rem;
        }
        
        .form-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 2.5rem;
            box-shadow: var(--box-shadow);
        }
        
        .form-group {
            margin-bottom: 1.75rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.75rem;
            font-weight: 600;
            color: var(--dark);
            font-size: 0.95rem;
        }
        
        .form-group label.required::after {
            content: ' *';
            color: #e74c3c;
        }
        
        .form-control, .form-select {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-family: inherit;
            transition: var(--transition);
            background: white;
        }
        
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 16px;
        }
        
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 90, 160, 0.1);
        }
        
        textarea.form-control {
            min-height: 140px;
            resize: vertical;
            line-height: 1.5;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .form-row-3 {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.5rem;
        }
        
        .form-help {
            display: block;
            margin-top: 0.5rem;
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--gray-light);
        }
        
        .alert {
            padding: 1.25rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideIn 0.3s ease;
        }
        
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        
        .alert-error i {
            color: #dc2626;
        }
        
        .alert-success {
            background: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
        }
        
        .alert-success i {
            color: #0f5132;
        }
        
        .event-info {
            background: var(--light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary);
        }
        
        .event-info p {
            margin: 0.5rem 0;
            font-size: 0.95rem;
        }
        
        .event-info strong {
            color: var(--primary);
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .content-container {
                padding: 1rem;
            }
            
            .form-container {
                padding: 1.5rem;
            }
            
            .form-row, .form-row-3 {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .form-actions {
                flex-direction: column-reverse;
            }
            
            .form-actions .btn {
                width: 100%;
                text-align: center;
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
                    <li class="nav-item active">
                        <a href="./">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Eventos</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../blog/categorias/">
                            <i class="fas fa-tags"></i>
                            <span>Categorías Blog</span>
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
                <h1>Editar Evento</h1>
            </div>
            <div class="header-right">
                <div class="user-menu">
                    <div class="user-info">
                        <span class="user-name">
                            <?php 
                            if (isset($_SESSION['user_nombre']) && !empty($_SESSION['user_nombre'])) {
                                echo htmlspecialchars($_SESSION['user_nombre']);
                            } elseif (isset($_SESSION['nombre_completo']) && !empty($_SESSION['nombre_completo'])) {
                                echo htmlspecialchars($_SESSION['nombre_completo']);
                            } else {
                                echo 'Administrador';
                            }
                            ?>
                        </span>
                        <span class="user-role">
                            <?php 
                            if (isset($_SESSION['user_cargo']) && !empty($_SESSION['user_cargo'])) {
                                echo htmlspecialchars($_SESSION['user_cargo']);
                            } elseif (isset($_SESSION['cargo']) && !empty($_SESSION['cargo'])) {
                                echo htmlspecialchars($_SESSION['cargo']);
                            } else {
                                echo 'Administrador';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Container -->
        <div class="content-container">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <strong>Error:</strong> <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <div class="page-header">
                <h1>
                    <i class="fas fa-edit"></i>
                    Editar Evento
                </h1>
                <p>Modifique los datos del evento según sea necesario</p>
            </div>
            
            <div class="event-info">
                <p><strong>ID:</strong> <?php echo $id; ?></p>
                <p><strong>Creado el:</strong> <?php echo date('d/m/Y H:i', strtotime($evento['fecha_creacion'])); ?></p>
                <p><strong>Última modificación:</strong> <?php echo isset($evento['fecha_actualizacion']) ? date('d/m/Y H:i', strtotime($evento['fecha_actualizacion'])) : 'No modificado'; ?></p>
            </div>
            
            <div class="form-container">
                <form method="POST" id="eventForm" novalidate>
                    <div class="form-group">
                        <label for="titulo" class="required">Título del Evento</label>
                        <input type="text" id="titulo" name="titulo" class="form-control" 
                               value="<?php echo htmlspecialchars($defaults['titulo']); ?>" 
                               required maxlength="255" 
                               placeholder="Ej: Retiro Espiritual 2024">
                        <span class="form-help">Máximo 255 caracteres</span>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea id="descripcion" name="descripcion" class="form-control" 
                                  rows="6" maxlength="1000"
                                  placeholder="Describa el evento, objetivos, actividades, etc."><?php echo htmlspecialchars($defaults['descripcion']); ?></textarea>
                        <span class="form-help">Máximo 1000 caracteres</span>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="fecha_inicio" class="required">Fecha y Hora de Inicio</label>
                            <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" class="form-control" 
                                   value="<?php echo htmlspecialchars(date('Y-m-d\TH:i', strtotime($defaults['fecha_inicio']))); ?>" 
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="fecha_fin">Fecha y Hora de Fin</label>
                            <input type="datetime-local" id="fecha_fin" name="fecha_fin" class="form-control" 
                                   value="<?php echo !empty($defaults['fecha_fin']) ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($defaults['fecha_fin']))) : ''; ?>">
                            <span class="form-help">Si no se especifica, se usará la misma fecha y hora de inicio</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="ubicacion">Ubicación/Lugar</label>
                        <input type="text" id="ubicacion" name="ubicacion" class="form-control" 
                               value="<?php echo htmlspecialchars($defaults['ubicacion']); ?>" 
                               maxlength="255" 
                               placeholder="Ej: Iglesia Central, Salón de Conferencias">
                        <span class="form-help">Máximo 255 caracteres</span>
                    </div>
                    
                    <div class="form-row-3">
                        <div class="form-group">
                            <label for="comision_id">Comisión Responsable</label>
                            <select id="comision_id" name="comision_id" class="form-select">
                                <option value="">Seleccionar comisión...</option>
                                <?php foreach ($comisiones as $comision): ?>
                                <option value="<?php echo $comision['id']; ?>" 
                                    <?php echo ($defaults['comision_id'] == $comision['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($comision['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="zona_id">Zona</label>
                            <select id="zona_id" name="zona_id" class="form-select">
                                <option value="">Seleccionar zona...</option>
                                <?php foreach ($zonas as $zona): ?>
                                <option value="<?php echo $zona['id']; ?>" 
                                    <?php echo ($defaults['zona_id'] == $zona['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($zona['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="sector_id">Sector</label>
                            <select id="sector_id" name="sector_id" class="form-select">
                                <option value="">Seleccionar sector...</option>
                                <?php foreach ($sectores as $sector): ?>
                                <option value="<?php echo $sector['id']; ?>" 
                                    <?php echo ($defaults['sector_id'] == $sector['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($sector['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="estado">Estado del Evento</label>
                        <select id="estado" name="estado" class="form-select">
                            <option value="activo" <?php echo ($defaults['estado'] == 'activo') ? 'selected' : ''; ?>>Activo</option>
                            <option value="completado" <?php echo ($defaults['estado'] == 'completado') ? 'selected' : ''; ?>>Completado</option>
                            <option value="cancelado" <?php echo ($defaults['estado'] == 'cancelado') ? 'selected' : ''; ?>>Cancelado</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Actualizar Evento
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toggle sidebar
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }
        
        // Validación de fechas
        const fechaInicioInput = document.getElementById('fecha_inicio');
        const fechaFinInput = document.getElementById('fecha_fin');
        
        // Validar que fecha_fin no sea anterior a fecha_inicio
        fechaFinInput.addEventListener('change', function() {
            const fechaInicio = fechaInicioInput.value;
            if (fechaInicio && this.value && this.value < fechaInicio) {
                alert('La fecha y hora de fin no pueden ser anteriores al inicio');
                this.value = fechaInicio;
            }
        });
        
        // Si fecha_fin está vacía al enviar, copiar fecha_inicio
        document.getElementById('eventForm').addEventListener('submit', function(e) {
            const fechaInicio = fechaInicioInput.value;
            const fechaFin = fechaFinInput.value;
            
            if (!fechaFin) {
                fechaFinInput.value = fechaInicio;
            }
            
            // Validación adicional del lado del cliente
            const titulo = document.getElementById('titulo').value.trim();
            
            if (!titulo) {
                e.preventDefault();
                alert('El título es obligatorio');
                document.getElementById('titulo').focus();
                return false;
            }
        });
    </script>
</body>
</html>