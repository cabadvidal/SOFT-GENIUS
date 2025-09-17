<?PHP
/**
 * Archivo de gestión de solicitud por Ajax desde el archivo js mostrar tabla
 * Se gestiona la consulta de una tabla con todos sus registros.
 * 
 * @version 0.7.
 * 
 * @author Group βackend.
 * 
 * @copyright 2023 - 2024 Soft Genius ©.
 */
require_once __DIR__ . "/../../db_func/read_db.php";

if (isset($_POST['db_name']) && isset($_POST['table_name'])) {
    $db_name = "bbdd_" . $_POST['db_name'];
    $table_name = $_POST['table_name'];

    $db_name_tmp = strtolower($db_name);
    $db_name_tmp = str_replace(' ', '_', $db_name_tmp);

    $table_name_tmp = strtolower($table_name);
    $table_name_tmp = str_replace(' ', '_', $table_name_tmp);

    $db_name = $db_name_tmp;
    $table_name = $table_name_tmp;
    
    $array = extractTableWithColumnsAndRecords($db_name, $table_name);

    // json_encode me devolvía un string vacío y con esto puedo compruebo el error
    try {
        $json = json_encode($array);
        if ($json === false) {
            throw new Exception('Error encoding JSON: ' . json_last_error_msg());
        }
        print_r($json);
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
} else {
    echo json_encode("Datos no recibidos.");
}
