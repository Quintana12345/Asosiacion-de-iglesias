<?php
// api/sectores.php
header('Content-Type: application/json');

require_once '../includes/config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'No autenticado']);
    exit;
}

$db = getDB();

// Obtener sectores por zona
if (isset($_GET['action']) && $_GET['action'] === 'by_zona' && isset($_GET['zona_id'])) {
    try {
        $zona_id = intval($_GET['zona_id']);
        
        $stmt = $db->prepare("SELECT id, nombre FROM sectores WHERE zona_id = ? AND activo = 1 ORDER BY nombre");
        $stmt->execute([$zona_id]);
        $sectores = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'sectores' => $sectores
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Error al obtener sectores: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Acción por defecto
echo json_encode([
    'success' => false,
    'error' => 'Acción no válida'
]);
?>