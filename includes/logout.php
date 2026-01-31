<?php
session_start();
require_once 'config.php';

// Destruir la sesión
session_destroy();

// Redireccionar al login
header('Location: ../admin/index.php');
exit();
?>