<?php
header('Content-Type: application/json');

/**
 * Elimina todos los archivos de la carpeta "uploads"
 */

$response = array('success' => false, 'error' => '');

$directory = '../../../uploads';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'eliminar') {
        if (is_dir($directory)) {
            $files = glob($directory . '/*'); // Obtiene todos los ficheros en la carpeta
            
            foreach ($files as $file) {
                if (is_file($file)) {
                    if (!unlink($file)) {
                        $response['error'] = 'Error al eliminar el fichero ' . $file;
                        echo json_encode($response);
                        exit;
                    }
                }
            }
            
            $response['success'] = true;
        } else {
            $response['error'] = 'La carpeta no existe.';
        }
    } else {
        $response['error'] = 'Acción no permitida.';
    }
} else {
    $response['error'] = 'Método no permitido.';
}

echo json_encode($response);
?>