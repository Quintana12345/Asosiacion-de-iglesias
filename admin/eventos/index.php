<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
requireAdmin();

$db = getDB();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'eliminar':
                    $id = $_POST['id'] ?? 0;
                    if ($id) {
                        $stmt = $db->prepare("DELETE FROM eventos WHERE id = ?");
                        $stmt->execute([$id]);
                        $_SESSION['success_msg'] = 'Evento eliminado exitosamente';
                    }
                    break;
            }
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = 'Error: ' . $e->getMessage();
        }
    }
}

// Filtros
$search = $_GET['search'] ?? '';
$mes = $_GET['mes'] ?? date('m');
$ano = $_GET['ano'] ?? date('Y');

// Construir consulta
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (titulo LIKE ? OR descripcion LIKE ? OR ubicacion LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

if ($mes && $ano) {
    $where .= " AND MONTH(fecha_inicio) = ? AND YEAR(fecha_inicio) = ?";
    $params[] = $mes;
    $params[] = $ano;
}

// Obtener eventos CON JOINS para mostrar nombres en lugar de IDs
$query = "SELECT e.*, 
          c.nombre as comision_nombre,
          z.nombre as zona_nombre,
          s.nombre as sector_nombre,
          u.nombre_completo as creador_nombre,
          CONCAT(DATE_FORMAT(e.fecha_inicio, '%d/%m/%Y %H:%i'), ' - ', DATE_FORMAT(e.fecha_fin, '%d/%m/%Y %H:%i')) as periodo,
          TIMESTAMPDIFF(HOUR, NOW(), e.fecha_inicio) as horas_restantes
          FROM eventos e
          LEFT JOIN comisiones c ON e.comision_id = c.id
          LEFT JOIN zonas z ON e.zona_id = z.id
          LEFT JOIN sectores s ON e.sector_id = s.id
          LEFT JOIN usuarios u ON e.creador_id = u.id
          $where
          ORDER BY e.fecha_inicio DESC, e.fecha_creacion DESC";
          
$stmt = $db->prepare($query);
$stmt->execute($params);
$eventos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Obtener estadísticas CORREGIDAS - Versión PHP para mejor control
$stats = [
    'total' => 0,
    'proximos' => 0,
    'en_curso' => 0,
    'finalizados' => 0
];

// Obtener todos los eventos para calcular estadísticas
$query_stats = "SELECT id, titulo, fecha_inicio, fecha_fin, estado FROM eventos";
$stmt_stats = $db->query($query_stats);
$todos_eventos = $stmt_stats->fetchAll(PDO::FETCH_ASSOC);

$stats['total'] = count($todos_eventos);
$now = date('Y-m-d H:i:s');

foreach ($todos_eventos as $evento) {
    $fecha_inicio = $evento['fecha_inicio'];
    $fecha_fin = $evento['fecha_fin'];
    
    // Primero verificar el estado de la base de datos
    if ($evento['estado'] === 'cancelado') {
        continue; // No contar eventos cancelados en estas categorías
    }
    
    if ($evento['estado'] === 'completado') {
        $stats['finalizados']++;
        continue;
    }
    
    // Si está activo, determinar por fechas
    if ($fecha_inicio > $now) {
        $stats['proximos']++;
    } elseif ($fecha_inicio <= $now && ($fecha_fin === null || $fecha_fin >= $now)) {
        $stats['en_curso']++;
    } elseif ($fecha_fin !== null && $fecha_fin < $now) {
        $stats['finalizados']++;
    } else {
        // Por defecto, considerar como activo/en curso
        $stats['en_curso']++;
    }
}

// Obtener meses disponibles
$stmt = $db->query("SELECT DISTINCT MONTH(fecha_inicio) as mes, 
                    DATE_FORMAT(fecha_inicio, '%M') as mes_nombre,
                    YEAR(fecha_inicio) as ano
                    FROM eventos 
                    ORDER BY ano DESC, mes DESC");
$meses_disponibles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Eventos - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        <?php include '../admin-styles.css'; ?>
        
        .content-container {
            padding: 2rem;
        }
        
        .toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .form-group {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }
        
        select, .search-input {
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 0.9rem;
            font-family: inherit;
            background: white;
            transition: var(--transition);
        }
        
        select {
            min-width: 150px;
        }
        
        .search-input {
            min-width: 250px;
        }
        
        select:focus, .search-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 90, 160, 0.1);
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
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: var(--transition);
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
        }
        
        .stat-info h3 {
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--gray);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            line-height: 1;
        }
        
        .events-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .event-card {
            background: white;
            border-radius: var(--border-radius);
            overflow: hidden;
            border: 1px solid var(--gray-light);
            transition: var(--transition);
        }
        
        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--box-shadow);
        }
        
        .event-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 1.5rem;
            position: relative;
        }
        
        .event-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-proximo {
            background: #27ae60;
            color: white;
        }
        
        .status-en-curso {
            background: #f39c12;
            color: white;
        }
        
        .status-finalizado {
            background: #e74c3c;
            color: white;
        }
        
        .status-activo {
            background: #3498db;
            color: white;
        }
        
        .status-cancelado {
            background: #95a5a6;
            color: white;
        }
        
        .status-completado {
            background: #27ae60;
            color: white;
        }
        
        .event-title {
            margin: 0 0 0.5rem 0;
            font-size: 1.25rem;
            font-weight: 700;
        }
        
        .event-date {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            opacity: 0.9;
        }
        
        .event-body {
            padding: 1.5rem;
        }
        
        .event-description {
            color: var(--gray);
            margin-bottom: 1.5rem;
            line-height: 1.5;
        }
        
        .event-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--text);
        }
        
        .detail-item i {
            color: var(--primary);
            width: 20px;
        }
        
        .event-footer {
            padding: 1rem 1.5rem;
            background: var(--light);
            border-top: 1px solid var(--gray-light);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .event-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.5rem 0.75rem;
            font-size: 0.85rem;
            border-radius: var(--border-radius);
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--gray);
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1.5rem;
            opacity: 0.3;
        }
        
        .empty-state h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--dark);
        }
        
        .empty-state p {
            margin: 0 0 1.5rem 0;
            font-size: 1rem;
            color: var(--gray);
        }
        
        .event-meta {
            font-size: 0.85rem;
            color: var(--gray);
        }
        
        .meta-item {
            margin-bottom: 0.25rem;
        }
        
        .debug-panel {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .debug-panel h4 {
            margin: 0 0 1rem 0;
            color: var(--primary);
            font-size: 1rem;
        }
        
        .debug-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        
        .debug-table th,
        .debug-table td {
            padding: 0.5rem;
            border: 1px solid #dee2e6;
            text-align: left;
        }
        
        .debug-table th {
            background: #e9ecef;
            font-weight: 600;
        }
        
        @media (max-width: 768px) {
            .content-container {
                padding: 1rem;
            }
            
            .toolbar {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }
            
            .search-form {
                flex-direction: column;
                width: 100%;
            }
            
            .form-group {
                width: 100%;
            }
            
            select, .search-input {
                width: 100%;
                min-width: unset;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .events-grid {
                grid-template-columns: 1fr;
            }
            
            .event-details {
                grid-template-columns: 1fr;
            }
            
            .event-footer {
                flex-direction: column;
                gap: 1rem;
            }
            
            .event-actions {
                width: 100%;
                justify-content: center;
            }
            
            .debug-table {
                display: block;
                overflow-x: auto;
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
                <h1>Gestión de Eventos</h1>
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
            <?php if ($success_msg): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success_msg); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_msg): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo htmlspecialchars($error_msg); ?>
                </div>
            <?php endif; ?>
            
            <!-- Panel de depuración (descomenta para ver) -->
            <?php if (false): // Cambia a true para ver información de depuración ?>
            <div class="debug-panel">
                <h4><i class="fas fa-bug"></i> Información de Depuración</h4>
                <p><strong>Hora actual del servidor:</strong> <?php echo $now; ?></p>
                <p><strong>Total eventos en BD:</strong> <?php echo $stats['total']; ?></p>
                <table class="debug-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Estado BD</th>
                            <th>Calculado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todos_eventos as $evento): 
                            $fecha_inicio = $evento['fecha_inicio'];
                            $fecha_fin = $evento['fecha_fin'];
                            $estado_calculado = '';
                            
                            if ($evento['estado'] === 'cancelado') {
                                $estado_calculado = 'Cancelado (excluido)';
                            } elseif ($evento['estado'] === 'completado') {
                                $estado_calculado = 'Completado';
                            } elseif ($fecha_inicio > $now) {
                                $estado_calculado = 'Próximo';
                            } elseif ($fecha_inicio <= $now && ($fecha_fin === null || $fecha_fin >= $now)) {
                                $estado_calculado = 'En Curso';
                            } elseif ($fecha_fin !== null && $fecha_fin < $now) {
                                $estado_calculado = 'Finalizado';
                            } else {
                                $estado_calculado = 'Indeterminado';
                            }
                        ?>
                        <tr>
                            <td><?php echo $evento['id']; ?></td>
                            <td><?php echo htmlspecialchars($evento['titulo']); ?></td>
                            <td><?php echo $fecha_inicio; ?></td>
                            <td><?php echo $fecha_fin ?? 'NULL'; ?></td>
                            <td><?php echo $evento['estado']; ?></td>
                            <td><?php echo $estado_calculado; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <!-- Barra de herramientas -->
            <div class="toolbar">
                <div class="toolbar-left">
                    <a href="crear.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Nuevo Evento
                    </a>
                </div>
                <div class="toolbar-right">
                    <form method="GET" class="search-form">
                        <select name="mes" onchange="this.form.submit()">
                            <option value="">Todos los meses</option>
                            <?php foreach ($meses_disponibles as $mes_info): ?>
                            <option value="<?php echo $mes_info['mes']; ?>" 
                                <?php echo $mes == $mes_info['mes'] && $ano == $mes_info['ano'] ? 'selected' : ''; ?>>
                                <?php echo $mes_info['mes_nombre'] . ' ' . $mes_info['ano']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <div class="form-group">
                            <input type="text" name="search" placeholder="Buscar eventos..." 
                                   value="<?php echo htmlspecialchars($search); ?>" class="search-input">
                            <button type="submit" class="btn btn-secondary">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Estadísticas - AHORA CALCULADAS EN PHP -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Eventos</h3>
                        <span class="stat-number"><?php echo $stats['total']; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Próximos</h3>
                        <span class="stat-number"><?php echo $stats['proximos']; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>En Curso</h3>
                        <span class="stat-number"><?php echo $stats['en_curso']; ?></span>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Finalizados</h3>
                        <span class="stat-number"><?php echo $stats['finalizados']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Lista de eventos -->
            <?php if (empty($eventos)): ?>
                <div class="empty-state">
                    <i class="fas fa-calendar-alt"></i>
                    <h3>No hay eventos</h3>
                    <p><?php echo $search || $mes ? 'Intenta con otros filtros' : 'Comienza creando el primer evento'; ?></p>
                    <a href="crear.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Crear Evento
                    </a>
                </div>
            <?php else: ?>
                <div class="events-grid">
                    <?php foreach ($eventos as $evento): 
                        // Determinar estado basado en fechas y estado
                        $status_class = '';
                        $status_text = '';
                        
                        // Primero verificar el estado de la base de datos
                        switch ($evento['estado']) {
                            case 'cancelado':
                                $status_class = 'status-cancelado';
                                $status_text = 'Cancelado';
                                break;
                            case 'completado':
                                $status_class = 'status-completado';
                                $status_text = 'Completado';
                                break;
                            case 'activo':
                            default:
                                // Si está activo, determinar por fechas
                                $fecha_inicio = $evento['fecha_inicio'];
                                $fecha_fin = $evento['fecha_fin'];
                                
                                if ($fecha_inicio > $now) {
                                    if ($evento['horas_restantes'] > 0 && $evento['horas_restantes'] <= 24) {
                                        $status_class = 'status-proximo';
                                        $status_text = 'Próximo';
                                    } else {
                                        $status_class = 'status-activo';
                                        $status_text = 'Programado';
                                    }
                                } elseif ($fecha_inicio <= $now && ($fecha_fin === null || $fecha_fin >= $now)) {
                                    $status_class = 'status-en-curso';
                                    $status_text = 'En Curso';
                                } elseif ($fecha_fin !== null && $fecha_fin < $now) {
                                    $status_class = 'status-finalizado';
                                    $status_text = 'Finalizado';
                                } else {
                                    $status_class = 'status-activo';
                                    $status_text = 'Activo';
                                }
                                break;
                        }
                    ?>
                    <div class="event-card">
                        <div class="event-header">
                            <span class="event-status <?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                            <h3 class="event-title"><?php echo htmlspecialchars($evento['titulo']); ?></h3>
                            <div class="event-date">
                                <i class="far fa-calendar"></i>
                                <?php echo $evento['periodo']; ?>
                            </div>
                        </div>
                        
                        <div class="event-body">
                            <?php if ($evento['descripcion']): ?>
                            <div class="event-description">
                                <?php echo htmlspecialchars(substr($evento['descripcion'], 0, 150) . (strlen($evento['descripcion']) > 150 ? '...' : '')); ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="event-details">
                                <?php if ($evento['ubicacion']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <span><?php echo htmlspecialchars($evento['ubicacion']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($evento['comision_nombre']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-tasks"></i>
                                    <span><?php echo htmlspecialchars($evento['comision_nombre']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($evento['zona_nombre']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-map"></i>
                                    <span><?php echo htmlspecialchars($evento['zona_nombre']); ?></span>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($evento['sector_nombre']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-layer-group"></i>
                                    <span><?php echo htmlspecialchars($evento['sector_nombre']); ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="event-footer">
                            <div class="event-meta">
                                <div class="meta-item">
                                    <small style="color: var(--gray);">
                                        ID: <?php echo $evento['id']; ?>
                                    </small>
                                </div>
                                <div class="meta-item">
                                    <small style="color: var(--gray);">
                                        Creado por: <?php echo htmlspecialchars($evento['creador_nombre'] ?? 'Sistema'); ?>
                                    </small>
                                </div>
                                <div class="meta-item">
                                    <small style="color: var(--gray);">
                                        Estado: <?php echo htmlspecialchars($evento['estado']); ?>
                                    </small>
                                </div>
                            </div>
                            <div class="event-actions">
                                <a href="editar.php?id=<?php echo $evento['id']; ?>" class="btn btn-sm btn-secondary" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="detalle.php?id=<?php echo $evento['id']; ?>" class="btn btn-sm btn-info" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de eliminar este evento?')">
                                    <input type="hidden" name="action" value="eliminar">
                                    <input type="hidden" name="id" value="<?php echo $evento['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
        
        // Mostrar confirmación antes de eliminar
        document.querySelectorAll('form[onsubmit]').forEach(form => {
            form.onsubmit = function() {
                return confirm('¿Está seguro de eliminar este evento? Esta acción no se puede deshacer.');
            };
        });
    </script>
</body>
</html>