:root {
    --sidebar-width: 280px;
    --sidebar-collapsed: 80px;
    --header-height: 70px;
    --primary: #2c5aa0;
    --primary-dark: #1e3d72;
    --secondary: #e74c3c;
    --accent: #f39c12;
    --success: #27ae60;
    --warning: #f39c12;
    --danger: #e74c3c;
    --light: #f8f9fa;
    --dark: #343a40;
    --gray: #6c757d;
    --gray-light: #e9ecef;
    --border-radius: 12px;
    --box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    --transition: all 0.3s ease;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #e4edf5 100%);
    min-height: 100vh;
    display: flex;
}

/* Sidebar Styles */
.sidebar {
    width: var(--sidebar-width);
    background: linear-gradient(180deg, var(--primary) 0%, var(--primary-dark) 100%);
    display: flex;
    flex-direction: column;
    transition: var(--transition);
    z-index: 1000;
    box-shadow: 4px 0 20px rgba(0,0,0,0.15);
}

.sidebar-header {
    padding: 2rem 1.5rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.sidebar-header h2 {
    color: white;
    font-size: 1.3rem;
    margin-bottom: 0.25rem;
    font-weight: 700;
}

.sidebar-header p {
    color: rgba(255,255,255,0.7);
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
    color: rgba(255,255,255,0.5);
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
    color: rgba(255,255,255,0.8);
    text-decoration: none;
    transition: var(--transition);
    border-left: 3px solid transparent;
    margin: 0 0.5rem;
    border-radius: 8px;
}

.nav-item a:hover {
    background: rgba(255,255,255,0.1);
    color: white;
    transform: translateX(5px);
}

.nav-item.active a {
    background: rgba(255,255,255,0.15);
    color: white;
    border-left-color: var(--accent);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.nav-item i {
    width: 20px;
    text-align: center;
    font-size: 1.1rem;
}

.sidebar-footer {
    padding: 1.5rem;
    border-top: 1px solid rgba(255,255,255,0.1);
}

.logout-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    background: rgba(255,255,255,0.1);
    color: white;
    text-decoration: none;
    border-radius: var(--border-radius);
    transition: var(--transition);
    font-weight: 500;
    border: 1px solid rgba(255,255,255,0.2);
}

.logout-btn:hover {
    background: var(--danger);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
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
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid var(--gray-light);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 2rem;
    box-shadow: 0 2px 20px rgba(0,0,0,0.08);
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
    font-weight: 700;
    color: var(--primary);
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
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
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius);
    cursor: pointer;
    transition: var(--transition);
    background: white;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    border: 1px solid var(--gray-light);
}

.user-menu:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
}

.user-name {
    font-weight: 600;
    color: var(--dark);
    font-size: 0.9rem;
}

.user-role {
    font-size: 0.75rem;
    color: var(--primary);
    text-transform: capitalize;
    font-weight: 500;
    background: rgba(44, 90, 160, 0.1);
    padding: 0.2rem 0.5rem;
    border-radius: 20px;
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
    background: transparent;
}

/* Toolbar */
.toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding: 1.5rem 2rem;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    border: 1px solid rgba(255,255,255,0.2);
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

.search-input {
    padding: 0.75rem 1rem;
    border: 2px solid var(--gray-light);
    border-radius: var(--border-radius);
    font-size: 0.9rem;
    min-width: 300px;
    transition: var(--transition);
}

.search-input:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(44, 90, 160, 0.1);
}

.filter-select {
    padding: 0.75rem 1rem;
    border: 2px solid var(--gray-light);
    border-radius: var(--border-radius);
    font-size: 0.9rem;
    background: white;
    cursor: pointer;
    transition: var(--transition);
}

.filter-select:focus {
    outline: none;
    border-color: var(--primary);
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

.btn-sm {
    padding: 0.5rem 1rem;
    font-size: 0.875rem;
}

/* Table */
.table-container {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.2);
}

.data-table {
    width: 100%;
    border-collapse: collapse;
    background: white;
}

.data-table th {
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    color: white;
    padding: 1.2rem 1rem;
    text-align: left;
    font-weight: 600;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.data-table td {
    padding: 1.2rem 1rem;
    border-bottom: 1px solid var(--gray-light);
    font-size: 0.9rem;
}

.data-table tr:hover {
    background: rgba(44, 90, 160, 0.03);
}

.data-table tr:last-child td {
    border-bottom: none;
}

.user-avatar {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 0.9rem;
}

.badge {
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-administrador {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
}

.badge-jefe_zona {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
}

.badge-jefe_sector {
    background: linear-gradient(135deg, #9b59b6 0%, #8e44ad 100%);
    color: white;
}

.badge-pastor {
    background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
    color: white;
}

.table-actions {
    display: flex;
    gap: 0.5rem;
}

.btn-table {
    padding: 0.5rem;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    width: 35px;
    height: 35px;
}

.btn-edit {
    background: rgba(52, 152, 219, 0.1);
    color: #3498db;
    border: 1px solid rgba(52, 152, 219, 0.2);
}

.btn-edit:hover {
    background: #3498db;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}

.btn-delete {
    background: rgba(231, 76, 60, 0.1);
    color: #e74c3c;
    border: 1px solid rgba(231, 76, 60, 0.2);
}

.btn-delete:hover {
    background: #e74c3c;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1.5rem 2rem;
    background: white;
    border-top: 1px solid var(--gray-light);
}

.pagination-info {
    color: var(--gray);
    font-size: 0.9rem;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: var(--gray);
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    color: var(--gray-light);
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: var(--dark);
}

.empty-state p {
    margin-bottom: 2rem;
    color: var(--gray);
}

/* Stats Overview */
.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-mini {
    background: white;
    padding: 1.5rem;
    border-radius: var(--border-radius);
    box-shadow: var(--box-shadow);
    text-align: center;
    border: 1px solid rgba(255,255,255,0.2);
}

.stat-mini-number {
    font-size: 2rem;
    font-weight: 800;
    color: var(--primary);
    margin-bottom: 0.5rem;
}

.stat-mini-label {
    font-size: 0.8rem;
    color: var(--gray);
    text-transform: uppercase;
    letter-spacing: 0.5px;
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
}

@media (max-width: 768px) {
    .stats-overview {
        grid-template-columns: 1fr 1fr;
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

    .search-input {
        min-width: auto;
    }

    .pagination {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}

@media (max-width: 480px) {
    .stats-overview {
        grid-template-columns: 1fr;
    }
    
    .user-menu .user-info {
        display: none;
    }
}