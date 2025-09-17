<?PHP
/**
 * Archivo de gestión de solicitud por Ajax desde el archivo js mostrar campos y mostrar tabla
 * Se gestiona la consulta de la estructura de una tabla.
 * 
 * @version 0.7.
 * 
 * @author Group βackend.
 * 
 * @copyright 2023 - 2024 Soft Genius ©.
 */
require_once __DIR__ . "/../../db_func/read_db.php";
require_once __DIR__ . "/../../db_func/verify_data.php";
if (isset($_POST['db_name']) && isset($_POST['table_name'])) {
    $db_name = "bbdd_" . $_POST['db_name'];
    $table_name = $_POST['table_name'];

    $db_name_tmp = strtolower($db_name);
    $db_name_tmp = str_replace(' ', '_', $db_name_tmp);
    $table_name_tmp = strtolower($table_name);
    $table_name_tmp = str_replace(' ', '_', $table_name_tmp);
    if (isCheckText($db_name_tmp) && isCheckText($table_name_tmp)) {
        $db_name = $db_name_tmp;
        $table_name = $table_name_tmp;
        $array_table = loadTableInsert($db_name, $table_name);
        if ($array_table != null) {
            echo json_encode($array_table);
        } else {
            
            echo json_encode("Fallo conexión base de datos.");
        }
    } else {
        echo json_encode("Caracteres no válidos en el nombre de la base de datos $db_name");
    }
} else {
    echo json_encode("Datos no recibidos.");
}