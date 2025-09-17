<?PHP
/**
 * Archivo de gestión de solicitud por Ajax desde el archivo js mostrar tabla.
 * Gestiona los datos enviados para eliminar un registro de una tabla.
 * 
 * @version 0.7.
 * 
 * @author Group βackend.
 * 
 * @copyright 2023 - 2024 Soft Genius ©.
 */

require_once __DIR__ . "/../../db_func/create_db.php";

if (isset($_POST['db_name']) && isset($_POST['table_name']) && isset($_POST['id'])) {
    $db_name = "bbdd_" . $_POST['db_name'];
    $table_name = $_POST['table_name'];
    $id = $_POST['id'];

    $result = deleteRecord($db_name, $table_name, $id);

    echo json_encode($result);

} else {
    echo json_encode("Datos no recibidos.");
}