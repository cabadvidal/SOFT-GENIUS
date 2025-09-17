<?PHP
/**
 * Archivo de gestión de solicitud por Ajax desde el archivo js mostrar campos
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

if (isset($_POST['bd_name_pk']) && isset($_POST['table_name_pk']) && isset($_POST['bd_name_fk']) && isset($_POST['table_name_fk'])) {
    $bd_name_pk = $_POST['bd_name_pk'];
    $table_name_pk = $_POST['table_name_pk'];
    $db_name_fk = $_POST['bd_name_fk']; // Corregir nombre de variable
    $table_name_fk = $_POST['table_name_fk'];

    $column_created = alterTableAddColumnFK($bd_name_pk, $table_name_pk, $db_name_fk, $table_name_fk);

    if ($column_created) {
        echo json_encode("si");
    } else {
        echo json_encode("no");
    }
} else {
    echo json_encode("Datos no recibidos.");
}
