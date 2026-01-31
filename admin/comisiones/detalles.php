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
    $_SESSION['error'] = 'No se especificó la comisión';
    redirect('index.php');
}

try {
    // Obtener información completa de la comisión
    $stmt = $db->prepare("
        SELECT c.*, 
               u.nombre_completo as presidente_nombre,
               u.cargo as presidente_cargo,
               u.email as presidente_email,
               u.telefono as presidente_telefono
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
    
    // Obtener miembros de la comisión (de la tabla usuarios)
    $stmt_miembros = $db->prepare("
        SELECT 
            id,
            nombre_completo,
            cargo,
            email,
            telefono,
            celular,
            es_presidente_comision
        FROM usuarios 
        WHERE comision_id = ? AND activo = 1
        ORDER BY nombre_completo
    ");
    $stmt_miembros->execute([$id]);
    $miembros = $stmt_miembros->fetchAll(PDO::FETCH_ASSOC);
    
    // Contar miembros
    $total_miembros = count($miembros);
    
} catch (Exception $e) {
    $error = "Error al cargar datos: " . $e->getMessage();
}

// Procesar cambios de estado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'cambiar_estado') {
            $nuevo_estado = $comision['activo'] ? 0 : 1;
            $stmt = $db->prepare("UPDATE comisiones SET activo = ? WHERE id = ?");
            $stmt->execute([$nuevo_estado, $id]);
            
            $success = $nuevo_estado ? '✅ Comisión activada exitosamente' : '✅ Comisión desactivada exitosamente';
            // Recargar datos
            $comision['activo'] = $nuevo_estado;
            
        } elseif ($action === 'eliminar_comision') {
            // Verificar si la comisión tiene miembros antes de eliminar
            $stmt_verificar = $db->prepare("SELECT COUNT(*) as total FROM usuarios WHERE comision_id = ? AND activo = 1");
            $stmt_verificar->execute([$id]);
            $tiene_miembros = $stmt_verificar->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($tiene_miembros > 0) {
                // No eliminar, solo desactivar si tiene miembros
                $stmt = $db->prepare("UPDATE comisiones SET activo = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = '✅ Comisión desactivada (no eliminada porque tiene miembros)';
            } else {
                // Eliminar solo si no tiene miembros
                $stmt = $db->prepare("DELETE FROM comisiones WHERE id = ?");
                $stmt->execute([$id]);
                $_SESSION['success'] = '✅ Comisión eliminada exitosamente';
            }
            
            redirect('index.php');
        }
        
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
    <title>Detalles de Comisión - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        <?php include '../admin-styles.css'; ?>
        
        .details-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .details-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .details-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .comision-info {
            margin-top: 0.5rem;
            opacity: 0.9;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-active {
            background: rgba(39, 174, 96, 0.2);
            color: #27ae60;
            border: 1px solid rgba(39, 174, 96, 0.3);
        }
        
        .status-inactive {
            background: rgba(231, 76, 60, 0.2);
            color: #e74c3c;
            border: 1px solid rgba(231, 76, 60, 0.3);
        }
        
        .details-body {
            padding: 2rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid var(--gray-light);
            text-align: center;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.25rem;
            margin: 0 auto 1rem;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .info-card {
            background: #f8f9fa;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            border: 1px solid var(--gray-light);
        }
        
        .info-card h3 {
            color: var(--primary);
            margin-top: 0;
            margin-bottom: 1rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--gray-light);
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }
        
        .info-value {
            color: var(--gray-dark);
            text-align: right;
            max-width: 60%;
        }
        
        .section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary);
            margin: 2rem 0 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--gray-light);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .actions-panel {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            padding: 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid var(--gray-light);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--border-radius);
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            margin-bottom: 1.5rem;
        }
        
        .modal-header h3 {
            margin: 0;
            color: var(--primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2rem;
        }
        
        .contact-info {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.25rem 0;
            color: var(--gray-dark);
            font-size: 0.9rem;
        }
        
        .members-list {
            margin-top: 1rem;
        }
        
        .member-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: white;
            border-radius: var(--border-radius);
            margin-bottom: 0.75rem;
            border: 1px solid var(--gray-light);
        }
        
        .member-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
        }
        
        .member-info {
            flex: 1;
        }
        
        .member-name {
            font-weight: 600;
            color: var(--dark);
        }
        
        .member-details {
            display: flex;
            gap: 1rem;
            margin-top: 0.25rem;
            font-size: 0.85rem;
            color: var(--gray);
            flex-wrap: wrap;
        }
        
        .member-contact {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .no-members {
            text-align: center;
            padding: 2rem;
            color: var(--gray);
        }
        
        .no-members i {
            font-size: 2rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .description-box {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            border: 1px solid var(--gray-light);
            line-height: 1.6;
            white-space: pre-line;
        }
        
        .presidente-badge {
            display: inline-block;
            background: var(--primary);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 3px;
            margin-left: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .details-header {
                flex-direction: column;
                gap: 1rem;
                align-items: flex-start;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
            }
            
            .actions-panel {
                flex-direction: column;
            }
            
            .comision-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            
            .member-details {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .details-body {
                padding: 1rem;
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
                <h1>Detalles de Comisión</h1>
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

            <?php if ($comision): ?>
            <div class="details-container">
                <div class="details-header">
                    <div>
                        <h2><i class="fas fa-tasks"></i> <?php echo htmlspecialchars($comision['nombre']); ?></h2>
                        <div class="comision-info">
                            <span><i class="fas fa-hashtag"></i> ID: <?php echo $comision['id']; ?></span>
                            <?php if ($comision['presidente_nombre']): ?>
                            <span><i class="fas fa-user-tie"></i> Presidente: <?php echo htmlspecialchars($comision['presidente_nombre']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <span class="status-badge <?php echo $comision['activo'] ? 'status-active' : 'status-inactive'; ?>">
                        <i class="fas fa-circle" style="font-size: 0.5rem; vertical-align: middle;"></i>
                        <?php echo $comision['activo'] ? 'ACTIVA' : 'INACTIVA'; ?>
                    </span>
                </div>
                
                <div class="details-body">
                    <!-- Estadísticas -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-hashtag"></i>
                            </div>
                            <div class="stat-number"><?php echo $comision['id']; ?></div>
                            <div class="stat-label">ID</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-tie"></i>
                            </div>
                            <div class="stat-number">
                                <?php echo $comision['presidente_nombre'] ? 'Sí' : 'No'; ?>
                            </div>
                            <div class="stat-label">Presidente Asignado</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-number"><?php echo $total_miembros; ?></div>
                            <div class="stat-label">Miembros</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-plus"></i>
                            </div>
                            <div class="stat-number">
                                <?php echo date('Y', strtotime($comision['fecha_creacion'])); ?>
                            </div>
                            <div class="stat-label">Año de Creación</div>
                        </div>
                    </div>

                    <!-- Información de la Comisión -->
                    <h3 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Información de la Comisión
                    </h3>
                    
                    <div class="info-grid">
                        <div class="info-card">
                            <h3><i class="fas fa-cog"></i> Detalles</h3>
                            <div class="info-row">
                                <span class="info-label">ID:</span>
                                <span class="info-value"><?php echo $comision['id']; ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Nombre:</span>
                                <span class="info-value"><?php echo htmlspecialchars($comision['nombre']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Estado:</span>
                                <span class="info-value">
                                    <span class="status-badge <?php echo $comision['activo'] ? 'status-active' : 'status-inactive'; ?>" style="font-size: 0.75rem;">
                                        <?php echo $comision['activo'] ? 'Activa' : 'Inactiva'; ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                        
                        <div class="info-card">
                            <h3><i class="fas fa-user-tie"></i> Liderazgo</h3>
                            <?php if ($comision['presidente_nombre']): ?>
                            <div class="info-row">
                                <span class="info-label">Presidente:</span>
                                <span class="info-value"><?php echo htmlspecialchars($comision['presidente_nombre']); ?></span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Cargo:</span>
                                <span class="info-value"><?php echo htmlspecialchars(ucfirst($comision['presidente_cargo'])); ?></span>
                            </div>
                            <?php if ($comision['presidente_email']): ?>
                            <div class="contact-info">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($comision['presidente_email']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($comision['presidente_telefono']): ?>
                            <div class="contact-info">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($comision['presidente_telefono']); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php else: ?>
                            <div class="info-row">
                                <span class="info-label">Presidente:</span>
                                <span class="info-value" style="color: var(--gray); font-style: italic;">
                                    Sin asignar
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Descripción -->
                    <?php if (!empty($comision['descripcion'])): ?>
                    <h3 class="section-title">
                        <i class="fas fa-align-left"></i>
                        Descripción
                    </h3>
                    <div class="description-box">
                        <?php echo htmlspecialchars($comision['descripcion']); ?>
                    </div>
                    <?php endif; ?>

                    <!-- Miembros de la Comisión -->
                    <h3 class="section-title">
                        <i class="fas fa-users"></i>
                        Miembros (<?php echo $total_miembros; ?>)
                    </h3>
                    
                    <?php if (empty($miembros)): ?>
                        <div class="no-members">
                            <i class="fas fa-users-slash"></i>
                            <p>No hay miembros asignados a esta comisión.</p>
                            <p style="margin-top: 1rem; font-size: 0.9rem;">
                                <a href="../usuarios/nuevo.php?comision_id=<?php echo $id; ?>" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-user-plus"></i> Agregar miembro
                                </a>
                            </p>
                        </div>
                    <?php else: ?>
                        <div class="members-list">
                            <?php foreach ($miembros as $miembro): ?>
                            <div class="member-item">
                                <div class="member-avatar">
                                    <?php echo strtoupper(substr($miembro['nombre_completo'], 0, 1)); ?>
                                </div>
                                <div class="member-info">
                                    <div class="member-name">
                                        <?php echo htmlspecialchars($miembro['nombre_completo']); ?>
                                        <?php if ($miembro['es_presidente_comision']): ?>
                                        <span class="presidente-badge">PRESIDENTE</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="member-details">
                                        <span class="member-contact">
                                            <i class="fas fa-user-tag"></i>
                                            <?php echo htmlspecialchars(ucfirst($miembro['cargo'])); ?>
                                        </span>
                                        <?php if ($miembro['email']): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($miembro['email']); ?>" class="member-contact">
                                            <i class="fas fa-envelope"></i>
                                            <?php echo htmlspecialchars($miembro['email']); ?>
                                        </a>
                                        <?php endif; ?>
                                        <?php if ($miembro['telefono']): ?>
                                        <a href="tel:<?php echo htmlspecialchars($miembro['telefono']); ?>" class="member-contact">
                                            <i class="fas fa-phone"></i>
                                            <?php echo htmlspecialchars($miembro['telefono']); ?>
                                        </a>
                                        <?php elseif ($miembro['celular']): ?>
                                        <a href="tel:<?php echo htmlspecialchars($miembro['celular']); ?>" class="member-contact">
                                            <i class="fas fa-mobile-alt"></i>
                                            <?php echo htmlspecialchars($miembro['celular']); ?>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <a href="../usuarios/editar.php?id=<?php echo $miembro['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Editar miembro">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div style="margin-top: 1.5rem; text-align: center;">
                            <a href="../usuarios/nuevo.php?comision_id=<?php echo $id; ?>" class="btn btn-outline-primary">
                                <i class="fas fa-user-plus"></i> Agregar Nuevo Miembro
                            </a>
                            <a href="../usuarios/?comision=<?php echo $id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-list"></i> Ver Todos los Miembros
                            </a>
                        </div>
                    <?php endif; ?>

                    <!-- Información del Sistema -->
                    <h3 class="section-title">
                        <i class="fas fa-database"></i>
                        Información del Sistema
                    </h3>
                    <div class="info-grid">
                        <div class="info-card">
                            <h3><i class="fas fa-calendar-plus"></i> Fechas</h3>
                            <div class="info-row">
                                <span class="info-label">Fecha de Creación:</span>
                                <span class="info-value">
                                    <?php echo date('d/m/Y H:i', strtotime($comision['fecha_creacion'])); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Panel de Acciones -->
                <div class="actions-panel">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i>
                        Volver a Comisiones
                    </a>
                    <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i>
                        Editar Comisión
                    </a>
                    
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="cambiar_estado">
                        <button type="submit" class="btn <?php echo $comision['activo'] ? 'btn-warning' : 'btn-success'; ?>">
                            <i class="fas fa-power-off"></i>
                            <?php echo $comision['activo'] ? 'Desactivar' : 'Activar'; ?> Comisión
                        </button>
                    </form>
                    
                    <button type="button" class="btn btn-danger" onclick="mostrarModalEliminar()">
                        <i class="fas fa-trash"></i>
                        Eliminar Comisión
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal para eliminar comisión -->
    <div id="modalEliminar" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación</h3>
            </div>
            <p>
                ¿Estás seguro de que deseas eliminar la comisión 
                "<strong><?php echo htmlspecialchars($comision['nombre']); ?></strong>"?
            </p>
            <?php if ($total_miembros > 0): ?>
            <div class="alert alert-warning" style="margin: 1rem 0;">
                <i class="fas fa-exclamation-circle"></i>
                <strong>¡Advertencia!</strong> Esta comisión tiene <?php echo $total_miembros; ?> miembro(s) asignado(s). 
                <br><br>
                <strong>Opción recomendada:</strong> Desactivar la comisión en lugar de eliminarla.
                <br>
                Los miembros mantendrán su asignación pero la comisión no será visible.
            </div>
            <?php endif; ?>
            <p style="color: var(--gray); font-size: 0.9rem; margin-top: 0.5rem;">
                <?php if ($total_miembros > 0): ?>
                <strong>Nota:</strong> Si eliminas esta comisión, <?php echo $total_miembros; ?> miembro(s) perderán su asignación.
                <?php else: ?>
                Esta acción no se puede deshacer.
                <?php endif; ?>
            </p>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="cerrarModal('modalEliminar')">
                    Cancelar
                </button>
                <?php if ($total_miembros > 0): ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="cambiar_estado">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-power-off"></i>
                        Solo Desactivar
                    </button>
                </form>
                <?php endif; ?>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="eliminar_comision">
                    <button type="submit" class="btn btn-danger">
                        <?php if ($total_miembros > 0): ?>
                        <i class="fas fa-exclamation-triangle"></i>
                        Eliminar de Todas Formas
                        <?php else: ?>
                        Sí, Eliminar Comisión
                        <?php endif; ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function mostrarModalEliminar() {
            document.getElementById('modalEliminar').classList.add('active');
        }
        
        function cerrarModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        // Cerrar modal al hacer clic fuera
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
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
        
        // Confirmación para activar/desactivar
        document.addEventListener('DOMContentLoaded', function() {
            const statusForm = document.querySelector('form[action*="cambiar_estado"]');
            if (statusForm) {
                statusForm.addEventListener('submit', function(e) {
                    const currentStatus = <?php echo $comision ? ($comision['activo'] ? 'true' : 'false') : 'false'; ?>;
                    const action = currentStatus ? 'desactivar' : 'activar';
                    
                    if (!confirm(`¿Estás seguro de que deseas ${action} esta comisión?`)) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</body>
</html>