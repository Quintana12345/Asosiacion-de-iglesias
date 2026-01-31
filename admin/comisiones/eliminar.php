<?php
require_once '../../includes/config.php';
requireLogin();
if (!isAdmin()) {
    $_SESSION['error'] = 'No tienes permisos para realizar esta acción';
    redirect('../dashboard.php');
}

$db = getDB();

// Verificar si se recibió un ID
$comision_id = 0;
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $comision_id = intval($_GET['id']);
} elseif (isset($_POST['id']) && !empty($_POST['id'])) {
    $comision_id = intval($_POST['id']);
}

if (!$comision_id) {
    $_SESSION['error'] = 'No se especificó la comisión a eliminar';
    redirect('index.php');
}

// Determinar acción y confirmación
$accion = '';
$confirmada = false;

// Para solicitudes GET (desde detalles.php o confirmación)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $accion = $_GET['accion'] ?? '';
    $confirmada = isset($_GET['confirmar']) && $_GET['confirmar'] === 'si';
}
// Para solicitudes POST (desde index.php)
elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = 'eliminar'; // Por defecto eliminar desde index
    $confirmada = true;    // Desde index viene directamente confirmado
}

// Verificar si la comisión existe
try {
    $stmt = $db->prepare("
        SELECT c.*, 
               u.nombre_completo as presidente_nombre
        FROM comisiones c 
        LEFT JOIN usuarios u ON c.presidente_id = u.id 
        WHERE c.id = ?
    ");
    $stmt->execute([$comision_id]);
    $comision = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$comision) {
        $_SESSION['error'] = 'Comisión no encontrada';
        redirect('index.php');
    }
    
    // Contar miembros de la comisión
    $stmt_miembros = $db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE comision_id = ? AND activo = 1");
    $stmt_miembros->execute([$comision_id]);
    $miembros_count = $stmt_miembros->fetch(PDO::FETCH_ASSOC)['total'];
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Error al verificar la comisión: ' . $e->getMessage();
    redirect('index.php');
}

// Procesar eliminación/desactivación si está confirmada
if ($confirmada && in_array($accion, ['desactivar', 'eliminar'])) {
    try {
        // Iniciar transacción
        $db->beginTransaction();
        
        // 1. Desasignar presidente si existe
        if ($comision['presidente_id']) {
            $stmt = $db->prepare("UPDATE usuarios SET es_presidente_comision = 0 WHERE id = ?");
            $stmt->execute([$comision['presidente_id']]);
        }
        
        // 2. Quitar comisión a todos los miembros
        $stmt = $db->prepare("UPDATE usuarios SET comision_id = NULL WHERE comision_id = ?");
        $stmt->execute([$comision_id]);
        
        if ($accion === 'desactivar') {
            // 3a. Desactivar la comisión
            $stmt = $db->prepare("UPDATE comisiones SET activo = 0 WHERE id = ?");
            $stmt->execute([$comision_id]);
            
            $db->commit();
            
            $_SESSION['success'] = '✅ Comisión desactivada exitosamente. ' . $miembros_count . ' miembro(s) han sido desasignados.';
            
        } elseif ($accion === 'eliminar') {
            // 3b. Eliminar la comisión completamente
            $stmt = $db->prepare("DELETE FROM comisiones WHERE id = ?");
            $stmt->execute([$comision_id]);
            
            $db->commit();
            
            $_SESSION['success'] = '✅ Comisión eliminada exitosamente. ' . $miembros_count . ' miembro(s) han sido desasignados.';
        }
        
        redirect('index.php');
        
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = '❌ Error al procesar la acción: ' . $e->getMessage();
        redirect('index.php');
    }
}

// Si no está confirmada, mostrar página de confirmación
// PERO, si viene de index.php (POST), mostrar confirmación simple
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Redirigir a la página de confirmación detallada
    header("Location: eliminar.php?id=$comision_id");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eliminar Comisión - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        <?php include '../admin-styles.css'; ?>
        
        .confirmation-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            max-width: 600px;
            margin: 0 auto;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .confirmation-header {
            background: linear-gradient(135deg, var(--danger) 0%, #c0392b 100%);
            color: white;
            padding: 2.5rem;
            text-align: center;
        }
        
        .confirmation-header h2 {
            margin: 0;
            font-size: 1.75rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }
        
        .confirmation-body {
            padding: 2.5rem;
        }
        
        .warning-box {
            background: rgba(231, 76, 60, 0.1);
            border: 1px solid rgba(231, 76, 60, 0.2);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .warning-icon {
            color: var(--danger);
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .comision-details {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .detail-item {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }
        
        .detail-label {
            font-size: 0.85rem;
            color: var(--gray);
            font-weight: 500;
        }
        
        .detail-value {
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
        
        .badge-warning {
            background: rgba(241, 196, 15, 0.1);
            color: #f39c12;
            border: 1px solid rgba(241, 196, 15, 0.2);
        }
        
        .members-warning {
            background: rgba(241, 196, 15, 0.1);
            border: 1px solid rgba(241, 196, 15, 0.2);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .members-warning h4 {
            color: #f39c12;
            margin: 0 0 1rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .options-grid {
            display: grid;
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .option-card {
            background: white;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .option-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
        }
        
        .option-card.selected {
            border-color: var(--primary);
            background: rgba(44, 90, 160, 0.05);
        }
        
        .option-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .option-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }
        
        .icon-warning {
            background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
        }
        
        .icon-danger {
            background: linear-gradient(135deg, var(--danger) 0%, #c0392b 100%);
        }
        
        .option-title {
            font-weight: 700;
            color: var(--dark);
            margin: 0;
        }
        
        .option-description {
            color: var(--gray);
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        .consequences {
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid var(--gray-light);
        }
        
        .consequences h5 {
            color: var(--danger);
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
        }
        
        .consequences ul {
            margin: 0;
            padding-left: 1.5rem;
            color: var(--gray);
            font-size: 0.85rem;
        }
        
        .consequences li {
            margin-bottom: 0.25rem;
        }
        
        .confirmation-actions {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .selected-action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
        
        @media (max-width: 768px) {
            .confirmation-body {
                padding: 1.5rem;
            }
            
            .confirmation-actions {
                flex-direction: column;
            }
            
            .details-grid {
                grid-template-columns: 1fr;
            }
            
            .selected-action-buttons {
                flex-direction: column;
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
                <h1>Eliminar Comisión</h1>
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
            <div class="confirmation-container">
                <div class="confirmation-header">
                    <h2><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación</h2>
                </div>
                
                <div class="confirmation-body">
                    <!-- Mensaje de advertencia -->
                    <div class="warning-box">
                        <div class="warning-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <div>
                            <h3 style="margin: 0 0 0.5rem 0; color: var(--danger);">¡Atención! Esta acción es irreversible</h3>
                            <p style="margin: 0; color: var(--gray-dark);">
                                Estás a punto de eliminar o desactivar una comisión del sistema. 
                                Por favor, revisa cuidadosamente la información y selecciona una opción.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Información de la comisión -->
                    <div class="comision-details">
                        <h3 style="color: var(--primary); margin: 0 0 1rem 0;">
                            <i class="fas fa-tasks"></i> Comisión a eliminar
                        </h3>
                        <div class="details-grid">
                            <div class="detail-item">
                                <span class="detail-label">Nombre:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($comision['nombre']); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">ID:</span>
                                <span class="detail-value"><?php echo $comision['id']; ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Estado actual:</span>
                                <span class="badge <?php echo $comision['activo'] ? 'badge-activo' : 'badge-inactivo'; ?>">
                                    <?php echo $comision['activo'] ? 'Activa' : 'Inactiva'; ?>
                                </span>
                            </div>
                            <?php if ($comision['presidente_nombre']): ?>
                            <div class="detail-item">
                                <span class="detail-label">Presidente:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($comision['presidente_nombre']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($miembros_count > 0): ?>
                    <!-- Advertencia de miembros -->
                    <div class="members-warning">
                        <h4><i class="fas fa-users"></i> ¡Comisión con miembros asignados!</h4>
                        <p style="margin: 0 0 1rem 0; color: var(--gray-dark);">
                            Esta comisión tiene <strong><?php echo $miembros_count; ?> miembro(s)</strong> asignado(s).
                            Al eliminar la comisión, estos miembros perderán su asignación.
                        </p>
                        <p style="margin: 0; color: var(--gray); font-size: 0.9rem;">
                            <i class="fas fa-lightbulb"></i> <strong>Recomendación:</strong> 
                            Considera desactivar la comisión en lugar de eliminarla.
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Opciones disponibles -->
                    <h3 style="color: var(--primary); margin: 0 0 1rem 0;">
                        <i class="fas fa-cogs"></i> Selecciona una opción
                    </h3>
                    
                    <div class="options-grid" id="opcionesContainer">
                        <?php if ($miembros_count > 0): ?>
                        <!-- Opción 1: Desactivar comisión -->
                        <div class="option-card" id="optionDesactivar" onclick="seleccionarOpcion('desactivar')">
                            <div class="option-header">
                                <div class="option-icon icon-warning">
                                    <i class="fas fa-power-off"></i>
                                </div>
                                <div>
                                    <h4 class="option-title">Desactivar Comisión</h4>
                                    <span class="badge badge-warning">RECOMENDADO</span>
                                </div>
                            </div>
                            <div class="option-description">
                                <p>La comisión se desactivará y no será visible en el sistema, pero permanecerá en la base de datos.</p>
                                <div class="consequences">
                                    <h5>Consecuencias:</h5>
                                    <ul>
                                        <li>La comisión se marcará como inactiva</li>
                                        <li>Todos los miembros serán desasignados</li>
                                        <li>El presidente perderá su cargo</li>
                                        <li>La información permanece en la base de datos</li>
                                        <li>Se puede reactivar en el futuro</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Opción 2: Eliminar completamente -->
                        <div class="option-card" id="optionEliminar" onclick="seleccionarOpcion('eliminar')">
                            <div class="option-header">
                                <div class="option-icon icon-danger">
                                    <i class="fas fa-trash"></i>
                                </div>
                                <div>
                                    <h4 class="option-title">Eliminar Completamente</h4>
                                </div>
                            </div>
                            <div class="option-description">
                                <p>La comisión será eliminada permanentemente del sistema junto con todas sus asignaciones.</p>
                                <div class="consequences">
                                    <h5>Consecuencias:</h5>
                                    <ul>
                                        <li>La comisión será eliminada permanentemente</li>
                                        <li>Todos los miembros serán desasignados</li>
                                        <li>El presidente perderá su cargo</li>
                                        <li>La información será eliminada de la base de datos</li>
                                        <li><strong>Esta acción no se puede deshacer</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        
                        <?php else: ?>
                        <!-- Opción única: Eliminar comisión sin miembros -->
                        <div class="option-card selected" id="optionEliminar" onclick="seleccionarOpcion('eliminar')">
                            <div class="option-header">
                                <div class="option-icon icon-danger">
                                    <i class="fas fa-trash"></i>
                                </div>
                                <div>
                                    <h4 class="option-title">Eliminar Comisión</h4>
                                </div>
                            </div>
                            <div class="option-description">
                                <p>Esta comisión no tiene miembros asignados, por lo que puede ser eliminada completamente del sistema.</p>
                                <div class="consequences">
                                    <h5>Consecuencias:</h5>
                                    <ul>
                                        <li>La comisión será eliminada permanentemente</li>
                                        <li>Si hay presidente asignado, perderá su cargo</li>
                                        <li>La información será eliminada de la base de datos</li>
                                        <li><strong>Esta acción no se puede deshacer</strong></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Botones de acción seleccionada -->
                    <div class="selected-action-buttons" id="actionButtons">
                        <?php if ($miembros_count > 0): ?>
                        <!-- Botones para comisiones con miembros -->
                        <button type="button" class="btn btn-warning" id="btnDesactivar" onclick="confirmarAccion('desactivar')" style="display: none;">
                            <i class="fas fa-power-off"></i>
                            Confirmar Desactivación
                        </button>
                        <button type="button" class="btn btn-danger" id="btnEliminar" onclick="confirmarAccion('eliminar')" style="display: none;">
                            <i class="fas fa-trash"></i>
                            Confirmar Eliminación
                        </button>
                        <?php else: ?>
                        <!-- Botón para comisiones sin miembros -->
                        <button type="button" class="btn btn-danger" onclick="confirmarAccion('eliminar')">
                            <i class="fas fa-trash"></i>
                            Confirmar Eliminación
                        </button>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Botones de navegación -->
                    <div class="confirmation-actions">
                        <a href="detalles.php?id=<?php echo $comision_id; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </a>
                        
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left"></i>
                            Volver al Listado
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        let opcionSeleccionada = '';
        
        function seleccionarOpcion(opcion) {
            opcionSeleccionada = opcion;
            
            // Remover selección de todas las opciones
            document.querySelectorAll('.option-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Agregar selección a la opción elegida
            const opcionCard = document.getElementById('option' + opcion.charAt(0).toUpperCase() + opcion.slice(1));
            if (opcionCard) {
                opcionCard.classList.add('selected');
            }
            
            // Mostrar botones de acción correspondientes
            <?php if ($miembros_count > 0): ?>
            if (opcion === 'desactivar') {
                document.getElementById('btnDesactivar').style.display = 'block';
                document.getElementById('btnEliminar').style.display = 'none';
            } else if (opcion === 'eliminar') {
                document.getElementById('btnDesactivar').style.display = 'none';
                document.getElementById('btnEliminar').style.display = 'block';
            }
            <?php endif; ?>
        }
        
        function confirmarAccion(accion) {
            let mensaje = '';
            
            if (accion === 'desactivar') {
                mensaje = '¿Estás seguro de desactivar esta comisión?\n\n' +
                         '• La comisión se marcará como inactiva\n' +
                         '• Todos los miembros serán desasignados\n' +
                         '• El presidente perderá su cargo\n\n' +
                         'La comisión permanecerá en la base de datos y podrá reactivarse en el futuro.';
            } else if (accion === 'eliminar') {
                mensaje = '¿Estás seguro de ELIMINAR PERMANENTEMENTE esta comisión?\n\n' +
                         '• La comisión será eliminada completamente\n' +
                         '• Todos los miembros serán desasignados\n' +
                         '• El presidente perderá su cargo\n' +
                         '• La información será eliminada de la base de datos\n\n' +
                         '⚠️  ESTA ACCIÓN NO SE PUEDE DESHACER ⚠️';
            }
            
            if (confirm(mensaje)) {
                // Crear formulario dinámico
                const form = document.createElement('form');
                form.method = 'GET';
                form.action = 'eliminar.php';
                
                // Agregar parámetros
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'id';
                idInput.value = '<?php echo $comision_id; ?>';
                form.appendChild(idInput);
                
                const confirmarInput = document.createElement('input');
                confirmarInput.type = 'hidden';
                confirmarInput.name = 'confirmar';
                confirmarInput.value = 'si';
                form.appendChild(confirmarInput);
                
                const accionInput = document.createElement('input');
                accionInput.type = 'hidden';
                accionInput.name = 'accion';
                accionInput.value = accion;
                form.appendChild(accionInput);
                
                // Enviar formulario
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($miembros_count > 0): ?>
            // Por defecto seleccionar desactivar si hay miembros
            seleccionarOpcion('desactivar');
            <?php else: ?>
            // Por defecto seleccionar eliminar si no hay miembros
            opcionSeleccionada = 'eliminar';
            <?php endif; ?>
            
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