<?PHP
/**
 * Archivo de gestión de solicitud por Ajax desde el archivo js mostrar tabla
 * Se gestiona la actualización de registros en los campos de una tabla.
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
if (isset($_POST['db_name']) && isset($_POST['table_name']) && isset($_POST['array'])) {
    $db_name = "bbdd_" . $_POST['db_name'];
    $table_name = $_POST['table_name'];
    $array = $_POST['array'];

    // Recogemos los valores del array
    $column_name = $array[0];
    $new_value = $array[1];
    $id_column_name = $array[2];
    $id_value = $array[3];

    // Creamos un array asociativo con el nombre de la columna a actualizar y su nuevo valor
    $data = [
        $column_name => $new_value
    ];

    // Otro array asociativo con el nombre de la columna ID y el valor del registro donde actualizar
    $conditions = [
        $id_column_name => $id_value
    ];

    $db_name_tmp = strtolower($db_name);
    $db_name_tmp = str_replace(' ', '_', $db_name_tmp);

    $table_name_tmp = strtolower($table_name);
    $table_name_tmp = str_replace(' ', '_', $table_name_tmp);


    if (isCheckText($db_name_tmp) && isCheckText($table_name_tmp) && isCheckValue($new_value)) {
        $db_name = $db_name_tmp;
        $table_name = $table_name_tmp;

        if (updateTable($db_name, $table_name, $data, $conditions)) {
            // echo json_encode("Datos actualizados correctamente en la tabla $table_name.");
            echo json_encode(true);
            exit;
        } else {
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


$altura = 5;

for ($a = 1; $a <= $altura; $a++) {

    for ($e = 1; $e <= $altura - $a; $e++) {
        print_r(" ");
    }

    for ($c = 1; $c <= 2 * $a - 1; $c++) {
        print_r("*");

    }
    print_r("\n");
}