<?php
// Sistema de autenticación

function login($email, $password) {
    $db = getDB();
    $query = "SELECT * FROM usuarios WHERE email = :email AND activo = 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() == 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nombre'] = $user['nombre_completo'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_cargo'] = $user['cargo'];
            $_SESSION['user_zona'] = $user['zona_id'];
            $_SESSION['user_sector'] = $user['sector_id'];
            return true;
        }
    }
    return false;
}

function logout() {
    session_destroy();
    redirect('../index.php');
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('index.php');
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
        redirect('dashboard.php');
    }
}
?>