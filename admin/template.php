<?php
// Plantilla base para todas las páginas del admin
function renderAdminPage($title, $content, $active_menu = '') {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo $title; ?> - <?php echo SITE_NAME; ?></title>
        <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <style>
            :root {
                --sidebar-width: 280px;
                --sidebar-collapsed: 80px;
                --header-height: 70px;
                --primary: #2c5aa0;
                --primary-dark: #1e3d72;
                --secondary: #e74c3c;
                --success: #27ae60;
                --warning: #f39c12;
                --danger: #e74c3c;
                --light: #f8f9fa;
                --dark: #343a40;
                --gray: #6c757d;
                --gray-light: #e9ecef;
                --border-radius: 12px;
                --box-shadow: 0 5px 20px rgba(0,0,0,0.1);
                --transition: all 0.3s ease;
            }

            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Inter', sans-serif;
                background: #f5f7fa;
                display: flex;
                min-height: 100vh;
            }

            /* Sidebar Styles */
            .sidebar {
                width: var(--sidebar-width);
                background: white;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1);
                display: flex;
                flex-direction: column;
                transition: var(--transition);
                z-index: 1000;
            }

            .sidebar-header {
                padding: 2rem 1.5rem 1.5rem;
                border-bottom: 1px solid var(--gray-light);
            }

            .sidebar-header h2 {
                color: var(--primary);
                font-size: 1.3rem;
                margin-bottom: 0.25rem;
            }

            .sidebar-header p {
                color: var(--gray);
                font-size: 0.8rem;
            }

            .sidebar-nav {
                flex: 1;
                padding: 1.5rem 0;
            }

            .nav-section {
                margin-bottom: 2rem;
            }

            .nav-section h3 {
                padding: 0 1.5rem 0.75rem;
                font-size: 0.8rem;
                text-transform: uppercase;
                color: var(--gray);
                font-weight: 600;
                letter-spacing: 0.5px;
            }

            .nav-item {
                margin: 0.25rem 0;
            }

            .nav-item a {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.75rem 1.5rem;
                color: var(--dark);
                text-decoration: none;
                transition: var(--transition);
                border-left: 3px solid transparent;
            }

            .nav-item a:hover {
                background: var(--gray-light);
                color: var(--primary);
            }

            .nav-item.active a {
                background: rgba(44, 90, 160, 0.1);
                color: var(--primary);
                border-left-color: var(--primary);
            }

            .nav-item i {
                width: 20px;
                text-align: center;
            }

            .sidebar-footer {
                padding: 1.5rem;
                border-top: 1px solid var(--gray-light);
            }

            .logout-btn {
                display: flex;
                align-items: center;
                gap: 0.75rem;
                padding: 0.75rem 1rem;
                background: var(--gray-light);
                color: var(--dark);
                text-decoration: none;
                border-radius: var(--border-radius);
                transition: var(--transition);
                font-weight: 500;
            }

            .logout-btn:hover {
                background: var(--danger);
                color: white;
            }

            /* Main Content */
            .main-content {
                flex: 1;
                display: flex;
                flex-direction: column;
                transition: var(--transition);
            }

            /* Content Header */
            .content-header {
                height: var(--header-height);
                background: white;
                border-bottom: 1px solid var(--gray-light);
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 0 2rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            }

            .header-left {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .sidebar-toggle {
                background: none;
                border: none;
                font-size: 1.25rem;
                color: var(--gray);
                cursor: pointer;
                padding: 0.5rem;
                border-radius: var(--border-radius);
                transition: var(--transition);
                display: none;
            }

            .sidebar-toggle:hover {
                background: var(--gray-light);
                color: var(--primary);
            }

            .content-header h1 {
                font-size: 1.5rem;
                font-weight: 600;
                color: var(--dark);
            }

            .header-right {
                display: flex;
                align-items: center;
                gap: 1rem;
            }

            .user-menu {
                display: flex;
                align-items: center;
                gap: 1rem;
                padding: 0.5rem;
                border-radius: var(--border-radius);
                cursor: pointer;
                transition: var(--transition);
            }

            .user-menu:hover {
                background: var(--gray-light);
            }

            .user-info {
                display: flex;
                flex-direction: column;
                align-items: flex-end;
            }

            .user-name {
                font-weight: 600;
                color: var(--dark);
            }

            .user-role {
                font-size: 0.8rem;
                color: var(--gray);
                text-transform: capitalize;
            }

            .user-avatar {
                font-size: 2rem;
                color: var(--primary);
            }

            /* Content Container */
            .content-container {
                flex: 1;
                padding: 2rem;
                overflow-y: auto;
            }

            /* Stats Grid */
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 1.5rem;
                margin-bottom: 2rem;
            }

            .stat-card {
                background: white;
                padding: 1.5rem;
                border-radius: var(--border-radius);
                box-shadow: var(--box-shadow);
                display: flex;
                align-items: center;
                gap: 1rem;
                transition: var(--transition);
                border-left: 4px solid var(--primary);
            }

            .stat-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            }

            .stat-icon {
                width: 60px;
                height: 60px;
                background: rgba(44, 90, 160, 0.1);
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.5rem;
                color: var(--primary);
            }

            .stat-info h3 {
                font-size: 0.9rem;
                color: var(--gray);
                margin-bottom: 0.5rem;
                font-weight: 500;
            }

            .stat-number {
                font-size: 2rem;
                font-weight: 700;
                color: var(--dark);
            }

            /* Toolbar */
            .toolbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 1.5rem;
                padding: 1.5rem;
                background: white;
                border-radius: var(--border-radius);
                box-shadow: var(--box-shadow);
            }

            .search-form {
                display: flex;
                gap: 1rem;
                align-items: center;
            }

            .search-form .form-group {
                margin: 0;
                flex-direction: row;
            }

            /* Table */
            .table-container {
                background: white;
                border-radius: var(--border-radius);
                box-shadow: var(--box-shadow);
                overflow: hidden;
            }

            .data-table {
                width: 100%;
                border-collapse: collapse;
            }

            .data-table th,
            .data-table td {
                padding: 1rem;
                text-align: left;
                border-bottom: 1px solid var(--gray-light);
            }

            .data-table th {
                background: var(--gray-light);
                font-weight: 600;
                color: var(--dark);
            }

            .data-table tr:hover {
                background: var(--gray-light);
            }

            .table-actions {
                display: flex;
                gap: 0.5rem;
            }

            /* Buttons */
            .btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                padding: 0.75rem 1.5rem;
                border: none;
                border-radius: var(--border-radius);
                font-size: 1rem;
                font-weight: 600;
                cursor: pointer;
                transition: var(--transition);
                text-decoration: none;
                font-family: inherit;
            }

            .btn-primary {
                background: var(--primary);
                color: white;
            }

            .btn-primary:hover {
                background: var(--primary-dark);
                transform: translateY(-2px);
                box-shadow: var(--box-shadow);
            }

            .btn-secondary {
                background: var(--gray-light);
                color: var(--dark);
            }

            .btn-secondary:hover {
                background: var(--gray);
                color: white;
            }

            .btn-success {
                background: var(--success);
                color: white;
            }

            .btn-danger {
                background: var(--danger);
                color: white;
            }

            .btn-sm {
                padding: 0.5rem 1rem;
                font-size: 0.875rem;
            }

            .btn-info {
                background: #17a2b8;
                color: white;
            }

            /* Badges */
            .badge {
                padding: 0.25rem 0.5rem;
                border-radius: 20px;
                font-size: 0.8rem;
                font-weight: 600;
                color: white;
            }

            .badge-primary { background: #3498db; }
            .badge-secondary { background: #95a5a6; }
            .badge-success { background: #27ae60; }
            .badge-danger { background: #e74c3c; }
            .badge-warning { background: #f39c12; }

            /* Form Styles */
            .form-container {
                background: white;
                border-radius: var(--border-radius);
                padding: 2rem;
                box-shadow: var(--box-shadow);
                max-width: 800px;
                margin: 0 auto;
            }

            .form-row {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 1.5rem;
                margin-bottom: 1.5rem;
            }

            .form-group {
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .form-group label {
                font-weight: 500;
                color: var(--dark);
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }

            .form-group input,
            .form-group select,
            .form-group textarea {
                padding: 0.75rem 1rem;
                border: 2px solid var(--gray-light);
                border-radius: var(--border-radius);
                font-size: 1rem;
                transition: var(--transition);
                font-family: inherit;
            }

            .form-group input:focus,
            .form-group select:focus,
            .form-group textarea:focus {
                outline: none;
                border-color: var(--primary);
                box-shadow: 0 0 0 3px rgba(44, 90, 160, 0.1);
            }

            .form-actions {
                display: flex;
                gap: 1rem;
                justify-content: flex-end;
                margin-top: 2rem;
                padding-top: 2rem;
                border-top: 1px solid var(--gray-light);
            }

            /* Pagination */
            .pagination {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-top: 1.5rem;
                padding: 1rem;
                background: white;
                border-radius: var(--border-radius);
            }

            /* Empty State */
            .empty-state {
                text-align: center;
                padding: 3rem;
                color: var(--gray);
            }

            .empty-state i {
                margin-bottom: 1rem;
                color: var(--gray-light);
            }

            .empty-state h3 {
                margin-bottom: 0.5rem;
                color: var(--dark);
            }

            /* Alert */
            .alert {
                padding: 1rem 1.5rem;
                border-radius: var(--border-radius);
                margin-bottom: 1.5rem;
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .alert-error {
                background: #fee;
                border: 1px solid #f5c6cb;
                color: #721c24;
            }

            .alert-success {
                background: #eff8f0;
                border: 1px solid #c3e6cb;
                color: #155724;
            }

            /* Responsive */
            @media (max-width: 1024px) {
                .sidebar {
                    transform: translateX(-100%);
                    position: fixed;
                    height: 100vh;
                }
                
                .sidebar.active {
                    transform: translateX(0);
                }
                
                .main-content {
                    margin-left: 0;
                }

                .sidebar-toggle {
                    display: block;
                }

                .form-row {
                    grid-template-columns: 1fr;
                }
            }

            @media (max-width: 768px) {
                .stats-grid {
                    grid-template-columns: 1fr;
                }
                
                .content-header {
                    padding: 0 1rem;
                }
                
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

                .pagination {
                    flex-direction: column;
                    gap: 1rem;
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
                        <li class="nav-item <?php echo $active_menu == 'dashboard' ? 'active' : ''; ?>">
                            <a href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i>
                                <span>Dashboard</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <h3>Gestión</h3>
                    <ul>
                        <li class="nav-item <?php echo $active_menu == 'usuarios' ? 'active' : ''; ?>">
                            <a href="usuarios/">
                                <i class="fas fa-users"></i>
                                <span>Usuarios</span>
                            </a>
                        </li>
                        <li class="nav-item <?php echo $active_menu == 'zonas' ? 'active' : ''; ?>">
                            <a href="zonas/">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>Zonas</span>
                            </a>
                        </li>
                        <li class="nav-item <?php echo $active_menu == 'sectores' ? 'active' : ''; ?>">
                            <a href="sectores/">
                                <i class="fas fa-layer-group"></i>
                                <span>Sectores</span>
                            </a>
                        </li>
                        <li class="nav-item <?php echo $active_menu == 'iglesias' ? 'active' : ''; ?>">
                            <a href="iglesias/">
                                <i class="fas fa-church"></i>
                                <span>Iglesias</span>
                            </a>
                        </li>
                        <li class="nav-item <?php echo $active_menu == 'pastores' ? 'active' : ''; ?>">
                            <a href="pastores/">
                                <i class="fas fa-user-tie"></i>
                                <span>Pastores</span>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="nav-section">
                    <h3>Contenido</h3>
                    <ul>
                        <li class="nav-item <?php echo $active_menu == 'comisiones' ? 'active' : ''; ?>">
                            <a href="comisiones/">
                                <i class="fas fa-tasks"></i>
                                <span>Comisiones</span>
                            </a>
                        </li>
                        <li class="nav-item <?php echo $active_menu == 'blog' ? 'active' : ''; ?>">
                            <a href="blog/">
                                <i class="fas fa-blog"></i>
                                <span>Blog</span>
                            </a>
                        </li>
                        <li class="nav-item <?php echo $active_menu == 'eventos' ? 'active' : ''; ?>">
                            <a href="eventos/">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Eventos</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../includes/logout.php" class="logout-btn">
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
                    <h1><?php echo $title; ?></h1>
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
                <?php echo $content; ?>
            </div>
        </div>

        <script>
            // JavaScript para el sidebar toggle
            document.addEventListener('DOMContentLoaded', function() {
                const sidebarToggle = document.querySelector('.sidebar-toggle');
                const sidebar = document.querySelector('.sidebar');
                
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

                // Form validation
                const forms = document.querySelectorAll('form');
                forms.forEach(form => {
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
                            alert('Por favor, complete todos los campos requeridos');
                        }
                    });
                });

                // Confirmación para acciones peligrosas
                const dangerousButtons = document.querySelectorAll('.btn-danger, a[href*="eliminar"], a[href*="delete"]');
                dangerousButtons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        if (!confirm('¿Está seguro de que desea realizar esta acción? Esta acción no se puede deshacer.')) {
                            e.preventDefault();
                        }
                    });
                });
            });
        </script>
    </body>
    </html>
    <?php
}
?>