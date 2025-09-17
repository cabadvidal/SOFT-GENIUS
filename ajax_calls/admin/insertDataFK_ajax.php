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
require_once __DIR__ . "/../../db_func/query_db.php";
require_once __DIR__ . "/../../db_func/verify_data.php";
// Si recibimos una solicitud POST con parámetros 'function_name' y 'params'
if (isset($_POST['db_name']) && isset($_POST['table_name']) && isset($_POST['column'])) {
    $db_name = $_POST['db_name'];
    $table_name = $_POST['table_name'];
    $column = $_POST['column'];

    $db_name_tmp = strtolower($db_name);
    $db_name_tmp = str_replace(' ', '_', $db_name_tmp);

    $table_name_tmp = strtolower($table_name);
    $table_name_tmp = str_replace(' ', '_', $table_name_tmp);

    $column_tmp = strtolower($column);
    $column_tmp = str_replace(' ', '_', $column_tmp);

    if (isCheckText($db_name_tmp) && isCheckText($table_name_tmp) && isCheckText($column_tmp)) {
        $db_name = $db_name_tmp;
        $table_name = $table_name_tmp;
        $column = $column_tmp;

        $sql = selectRecordsColumn($table_name, $column);
        $result = executeJudgments($sql, $db_name); 
        if($result) {
            $result_array = [];
            $result_array = executeJudgmentsSelect($sql, $db_name);
           
            echo json_encode($result_array);
        } else { 
            echo json_encode($result);
        }
    } else {
        echo json_encode("Caracteres no válidos.");
    }
} else {
    echo json_encode("Datos no recibidos.");
}