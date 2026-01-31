<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';
requireAdmin();

$db = getDB();

// Obtener categorías
$categorias = [];
try {
    $stmt = $db->query("SELECT id, nombre FROM categorias_blog WHERE activo = 1 ORDER BY nombre");
    $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Error cargando categorías: " . $e->getMessage());
}

// Procesar formulario
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $contenido = $_POST['contenido'] ?? '';
    $resumen = trim($_POST['resumen'] ?? '');
    $categoria_id = $_POST['categoria_id'] ?? null;
    $estado = $_POST['estado'] ?? 'borrador';
    $destacado = isset($_POST['destacado']) ? 1 : 0;
    $tags = trim($_POST['tags'] ?? '');
    
    // Validaciones
    if (empty($titulo)) {
        $error = 'El título es requerido';
    } elseif (empty($contenido)) {
        $error = 'El contenido es requerido';
    } else {
        try {
            // Manejar imagen
            $imagen_principal = null;
            if (isset($_FILES['imagen_principal']) && $_FILES['imagen_principal']['error'] === 0) {
                $upload_dir = '../../assets/uploads/blog/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['imagen_principal']['name'], PATHINFO_EXTENSION);
                $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (in_array(strtolower($file_extension), $allowed_extensions)) {
                    $filename = uniqid() . '.' . $file_extension;
                    $destination = $upload_dir . $filename;
                    
                    if (move_uploaded_file($_FILES['imagen_principal']['tmp_name'], $destination)) {
                        $imagen_principal = $filename;
                    }
                }
            }
            
            // Obtener ID del usuario actual
            $autor_id = $_SESSION['user_id'] ?? null;
            if (!$autor_id) {
                throw new Exception("No se pudo identificar al usuario autor");
            }
            
            // Verificar si la columna 'tags' existe
            $column_exists = false;
            try {
                $stmt = $db->query("SHOW COLUMNS FROM blog LIKE 'tags'");
                $column_exists = $stmt->rowCount() > 0;
            } catch (Exception $e) {
                // Columna no existe
            }
            
            // Construir query basada en columnas disponibles
            if ($column_exists) {
                // Si la columna tags existe
                $query = "INSERT INTO blog (titulo, contenido, resumen, categoria_id, autor_id, estado, tags, imagen_principal, destacado, fecha_publicacion) 
                          VALUES (:titulo, :contenido, :resumen, :categoria_id, :autor_id, :estado, :tags, :imagen_principal, :destacado, 
                          CASE WHEN :estado = 'publicado' THEN NOW() ELSE NULL END)";
                
                $params = [
                    ':titulo' => $titulo,
                    ':contenido' => $contenido,
                    ':resumen' => $resumen,
                    ':categoria_id' => $categoria_id ?: null,
                    ':autor_id' => $autor_id,
                    ':estado' => $estado,
                    ':tags' => $tags,
                    ':imagen_principal' => $imagen_principal,
                    ':destacado' => $destacado
                ];
            } else {
                // Si la columna tags NO existe
                $query = "INSERT INTO blog (titulo, contenido, resumen, categoria_id, autor_id, estado, imagen_principal, destacado, fecha_publicacion) 
                          VALUES (:titulo, :contenido, :resumen, :categoria_id, :autor_id, :estado, :imagen_principal, :destacado, 
                          CASE WHEN :estado = 'publicado' THEN NOW() ELSE NULL END)";
                
                $params = [
                    ':titulo' => $titulo,
                    ':contenido' => $contenido,
                    ':resumen' => $resumen,
                    ':categoria_id' => $categoria_id ?: null,
                    ':autor_id' => $autor_id,
                    ':estado' => $estado,
                    ':imagen_principal' => $imagen_principal,
                    ':destacado' => $destacado
                ];
            }
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            
            $post_id = $db->lastInsertId();
            
            $_SESSION['success_msg'] = 'Artículo creado exitosamente';
            header("Location: editar.php?id=$post_id");
            exit;
            
        } catch (PDOException $e) {
            $error = 'Error al crear el artículo: ' . $e->getMessage();
            error_log("Error PDO: " . $e->getMessage());
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Artículo - <?php echo SITE_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        <?php include '../admin-styles.css'; ?>
        
        .content-container {
            padding: 2rem;
        }
        
        .form-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 2rem;
        }
        
        .form-header {
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-light);
        }
        
        .form-header h2 {
            margin: 0;
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .form-main {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .form-sidebar {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .form-group label {
            font-weight: 600;
            color: var(--dark);
            font-size: 0.9rem;
        }
        
        .form-control {
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-light);
            border-radius: var(--border-radius);
            font-size: 1rem;
            font-family: inherit;
            transition: var(--transition);
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(44, 90, 160, 0.1);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .editor-container {
            height: 400px;
            margin-bottom: 1rem;
        }
        
        #editor {
            height: 300px;
        }
        
        .image-upload {
            border: 2px dashed var(--gray-light);
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .image-upload:hover {
            border-color: var(--primary);
            background: rgba(44, 90, 160, 0.02);
        }
        
        .image-preview {
            margin-top: 1rem;
            display: none;
        }
        
        .image-preview img {
            max-width: 100%;
            max-height: 200px;
            border-radius: var(--border-radius);
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin: 0.5rem 0;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }
        
        .checkbox-group label {
            font-weight: normal;
            cursor: pointer;
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
        
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--gray-light);
        }
        
        @media (max-width: 1024px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .content-container {
                padding: 1rem;
            }
            
            .form-container {
                padding: 1.5rem;
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
                    <li class="nav-item active">
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
                    <li class="nav-item">
                        <a href="categorias/">
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
                <h1>Crear Artículo</h1>
            </div>
            <div class="header-right">
                <div class="user-menu">
                    <div class="user-info">
                        <span class="user-name">
                            <?php 
                            if (isset($_SESSION['user_nombre']) && !empty($_SESSION['user_nombre'])) {
                                echo htmlspecialchars($_SESSION['user_nombre']);
                            } elseif (isset($_SESSION['user']['nombre_completo'])) {
                                echo htmlspecialchars($_SESSION['user']['nombre_completo']);
                            } else {
                                echo 'Usuario';
                            }
                            ?>
                        </span>
                        <span class="user-role">
                            <?php 
                            if (isset($_SESSION['user_cargo']) && !empty($_SESSION['user_cargo'])) {
                                echo htmlspecialchars($_SESSION['user_cargo']);
                            } elseif (isset($_SESSION['user']['cargo'])) {
                                echo htmlspecialchars($_SESSION['user']['cargo']);
                            } elseif (isset($_SESSION['user']['rol'])) {
                                echo htmlspecialchars($_SESSION['user']['rol']);
                            } else {
                                echo 'Sin cargo';
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
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data" class="form-container">
                <div class="form-header">
                    <h2><i class="fas fa-plus-circle"></i> Nuevo Artículo</h2>
                </div>
                
                <div class="form-grid">
                    <div class="form-main">
                        <div class="form-group">
                            <label for="titulo">Título *</label>
                            <input type="text" id="titulo" name="titulo" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['titulo'] ?? ''); ?>" 
                                   required maxlength="255">
                        </div>
                        
                        <div class="form-group">
                            <label for="resumen">Resumen (opcional)</label>
                            <textarea id="resumen" name="resumen" class="form-control" rows="3" 
                                      maxlength="500"><?php echo htmlspecialchars($_POST['resumen'] ?? ''); ?></textarea>
                            <small style="color: var(--gray);">Máximo 500 caracteres</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="contenido">Contenido *</label>
                            <div class="editor-container">
                                <div id="editor"></div>
                                <textarea id="contenido" name="contenido" style="display: none;" 
                                          required><?php echo htmlspecialchars($_POST['contenido'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-sidebar">
                        <div class="form-group">
                            <label for="categoria_id">Categoría</label>
                            <select id="categoria_id" name="categoria_id" class="form-control">
                                <option value="">Sin categoría</option>
                                <?php foreach ($categorias as $categoria): ?>
                                <option value="<?php echo $categoria['id']; ?>"
                                    <?php echo ($_POST['categoria_id'] ?? '') == $categoria['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($categoria['nombre']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select id="estado" name="estado" class="form-control">
                                <option value="borrador" <?php echo ($_POST['estado'] ?? '') == 'borrador' ? 'selected' : ''; ?>>Borrador</option>
                                <option value="publicado" <?php echo ($_POST['estado'] ?? '') == 'publicado' ? 'selected' : ''; ?>>Publicado</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Imagen principal</label>
                            <div class="image-upload" onclick="document.getElementById('imagen_input').click()">
                                <i class="fas fa-cloud-upload-alt" style="font-size: 2rem; color: var(--gray); margin-bottom: 0.5rem;"></i>
                                <p style="color: var(--gray); margin: 0;">Haz clic para subir una imagen</p>
                                <small style="color: var(--gray);">Formatos: JPG, PNG, GIF, WebP</small>
                            </div>
                            <input type="file" id="imagen_input" name="imagen_principal" accept="image/*" style="display: none;" onchange="previewImage(event)">
                            <div class="image-preview" id="image_preview">
                                <img id="preview_img" src="" alt="Vista previa">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="tags">Etiquetas (opcional)</label>
                            <input type="text" id="tags" name="tags" class="form-control" 
                                   value="<?php echo htmlspecialchars($_POST['tags'] ?? ''); ?>" 
                                   placeholder="Separadas por comas">
                            <small style="color: var(--gray);">Nota: La columna 'tags' puede no estar disponible en tu base de datos</small>
                        </div>
                        
                        <div class="form-group">
                            <div class="checkbox-group">
                                <input type="checkbox" id="destacado" name="destacado" value="1"
                                    <?php echo isset($_POST['destacado']) ? 'checked' : ''; ?>>
                                <label for="destacado">Destacar artículo</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Artículo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quill Editor -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <script>
        // Inicializar editor
        const quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    ['bold', 'italic', 'underline'],
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'image', 'video'],
                    ['clean']
                ]
            },
            placeholder: 'Escribe el contenido aquí...'
        });
        
        // Sincronizar con textarea
        quill.on('text-change', function() {
            document.getElementById('contenido').value = quill.root.innerHTML;
        });
        
        // Si hay contenido previo, cargarlo
        const contenidoTextarea = document.getElementById('contenido');
        if (contenidoTextarea.value) {
            quill.root.innerHTML = contenidoTextarea.value;
        }
        
        // Preview de imagen
        function previewImage(event) {
            const input = event.target;
            const preview = document.getElementById('preview_img');
            const previewContainer = document.getElementById('image_preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.style.display = 'block';
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Toggle sidebar
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
            });
        }
        
        // Contador de caracteres para resumen
        const resumenTextarea = document.getElementById('resumen');
        const resumenCounter = document.createElement('small');
        resumenCounter.style.cssText = 'color: var(--gray); text-align: right; display: block; margin-top: 0.25rem;';
        resumenTextarea.parentNode.appendChild(resumenCounter);
        
        function updateResumenCounter() {
            const length = resumenTextarea.value.length;
            resumenCounter.textContent = `${length}/500 caracteres`;
            
            if (length > 500) {
                resumenCounter.style.color = 'var(--danger)';
            } else {
                resumenCounter.style.color = 'var(--gray)';
            }
        }
        
        resumenTextarea.addEventListener('input', updateResumenCounter);
        updateResumenCounter();
    </script>
</body>
</html>