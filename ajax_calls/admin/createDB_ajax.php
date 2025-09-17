<?PHP
/**
 * Archivo de gestión de solicitud por Ajax desde el archivo js funciones generales
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
// Si recibimos una solicitud POST con parámetros 'function_name' y 'params'
if (isset($_POST['db_name'])) {
    $db_name ="bbdd_" . $_POST['db_name'];

    $db_name_tmp = strtolower($db_name);
    $db_name_tmp = str_replace(' ', '_', $db_name_tmp);
    if (isCheckText($db_name_tmp)) {
        $db_name = $db_name_tmp;
        $sql = createDB($db_name);
        if (executeJudgments($sql)) {
            // echo json_encode("Base de datos $db_name creada correctamente.");
            echo json_encode(true);
        } else {
            $error = executeJudgments($sql);
            echo json_encode($error);
        }
    } else {
        echo json_encode("Caracteres no válidos en el nombre de la base de datos $db_name");
    }
} else {
    echo json_encode("Datos no recibidos.");
}
