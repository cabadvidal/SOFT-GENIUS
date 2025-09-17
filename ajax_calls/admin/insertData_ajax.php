<?PHP
/**
 * Archivo de gestión de solicitud por Ajax desde el archivo js mostrar tabla
 * Se gestiona la creación con las llamadas correspondientes para la creación de la consulta y su ejecución.
 * 
 * @version 0.7.
 * 
 * @author Group βackend.
 * 
 * @copyright 2023 - 2024 Soft Genius ©.
 */
require_once __DIR__ . "/../../db_func/create_db.php";
require_once __DIR__ . "/../../db_func/verify_data.php";

if (isset($_POST['db_name']) && isset($_POST['table_name']) && isset($_POST['array'])) {
    $db_name = "bbdd_" . $_POST['db_name'];
    $table_name = $_POST['table_name'];
    $array = json_decode($_POST['array'], true);

    $db_name_tmp = strtolower($db_name);
    $db_name_tmp = str_replace(' ', '_', $db_name_tmp);

    $table_name_tmp = strtolower($table_name);
    $table_name_tmp = str_replace(' ', '_', $table_name_tmp);

    // Obtenemos el valor en binario de los ficheros que se manden por AJAX,
    // y lo pasamos intercambiamos por el valor que se manda desde el FRONT 
    // para enviar a la función que gestiona la inserción.
    foreach ($_FILES as $key => $file) {
        if ($file['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $file['tmp_name'];
            
            // Abrir el archivo en modo lectura binaria
            $file_handle = fopen($tmp_name, "rb");
    
            // Verificar si el archivo se abrió correctamente
            if ($file_handle !== false) {
                // Leer todo el contenido del archivo
                $file_content = fread($file_handle, filesize($tmp_name));
    
                // Cerrar el archivo
                fclose($file_handle);
    
                // Reemplazar el valor del archivo en el array con su contenido
                foreach ($array as &$value) {
                    if ($value === $file['name']) {
                        $value = $file_content;
                        break;
                    }
                }
            } else {
                // Manejar el error si no se pudo abrir el archivo
                echo "Error al abrir el archivo.";
            }
        }
    }
    
    if (isCheckText($db_name_tmp) && isCheckText($table_name_tmp)) {
        $db_name = $db_name_tmp;
        $table_name = $table_name_tmp;

        if (insertFront($array, $db_name, $table_name)) {
            echo json_encode(true);
            exit;
        } else {
            $error = insertFront($array, $db_name, $table_name);
            echo json_encode($error);
            exit;
        }
    } else {
        echo json_encode("Caracteres no válidos.");
        exit;
    }
} else {
    echo json_encode("Datos no recibidos.");
}