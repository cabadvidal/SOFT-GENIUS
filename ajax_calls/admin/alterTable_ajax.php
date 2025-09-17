<?PHP
/**
 * Archivo de gestión de solicitud por Ajax desde el archivo js mostrar campos.
 * Gestiona los datos enviados según el tipo de modificación se genera el array pertinente
 * para realizar el envió de información a las diferentes funciones que construyen las sentencias sql y la ejecutan.
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
if (isset($_POST['tipo_modificacion'])) {

    $modify_type = $_POST['tipo_modificacion'];

    if ($modify_type === "cambio_longitud") {
        $modify_type = "cambio_tipo";
    }

    switch ($modify_type) {
        // Caso para cambiar el nombre
        case "cambio_nombre":
            if (isset($_POST['nombre_bd']) && isset($_POST['nombre_tabla']) && isset($_POST['nombre_campo']) && isset($_POST['nombre_nuevo']) && isset($_POST['tipo']) && isset($_POST['longitud'])) {
                $db_name = "bbdd_" . $_POST['nombre_bd'];
                $table_name = $_POST['nombre_tabla'];
                $column_name = $_POST['nombre_campo'];
                $new_column_name = $_POST['nombre_nuevo'];
                $data_type = $_POST['tipo'];
                $length = $_POST['longitud'];

                $data_array = [
                    "name" => $column_name,
                    "new_name" => $new_column_name,
                    "new_table_name" => "",
                    "type" => $data_type,
                    "length" => $length,
                    "array" => "",
                    "action" => "RENAME_C"
                ];
                smurf($db_name, $table_name, $data_array);
            }
            break;
        // Caso para cambiar tipo o longitud de un campo
        case "cambio_tipo":
            if (isset($_POST['nombre_bd']) && isset($_POST['nombre_tabla']) && isset($_POST['nombre_campo']) && isset($_POST['tipo'])) {
                $db_name = "bbdd_" . $_POST['nombre_bd'];
                $table_name = $_POST['nombre_tabla'];
                $column_name = $_POST['nombre_campo'];
                $data_type = $_POST['tipo'];
                $length = $_POST['longitud'];

                if ($data_type === ("ENUM") || $data_type === ("SET")) {
                    if (isset($_POST['array_set_enum'])) {
                        $tmp_array = $_POST['array_set_enum']; 

                        // Dividir la cadena en elementos individuales usando explode
                        $individual_values = explode(',', $tmp_array);

                        // Crear un nuevo array para almacenar los valores individuales
                        $array_resultado = array();

                        // Agregar cada valor individual al nuevo array
                        foreach ($individual_values as $value) {
                            // Eliminar los espacios en blanco alrededor del valor y agregarlo al array resultante
                            $array_resultado[] = trim($value);
                        }

                        $data_array = [
                            "name" => $column_name,
                            "new_name" => "",
                            "new_table_name" => "",
                            "type" => $data_type,
                            "length" => $length,
                            "array" => $array_resultado,
                            "action" => "MODIFY"
                        ];
                        smurf($db_name, $table_name, $data_array);
                    }
                } else {
                    $data_array = [
                        "name" => $column_name,
                        "new_name" => "",
                        "new_table_name" => "",
                        "type" => $data_type,
                        "length" => $length,
                        "array" => "",
                        "action" => "MODIFY"
                    ];
                    smurf($db_name, $table_name, $data_array);
                }
            }
            break;
        // Caso para cambiar el nombre de la tabla
        case "tabla":
            if (isset($_POST['db_name']) && isset($_POST['table_name']) && isset($_POST['new_table_name'])) {
                $db_name = "bbdd_" . $_POST['db_name'];
                $table_name = $_POST['table_name'];
                $new_table_name = $_POST['new_table_name'];


                $data_array = [
                    "name" => "",
                    "new_name" => "",
                    "new_table_name" => $new_table_name,
                    "type" => "",
                    "length" => "",
                    "array" => "",
                    "action" => "RENAME_T"
                ];
                smurf($db_name, $table_name, $data_array);
            }
            break;

        // Caso para borrar columna
        case "borrar_columna":
            if (isset($_POST['db_name']) && isset($_POST['table_name']) && isset($_POST['nombre_campo']) && isset($_POST[''])) {
                $db_name = "bbdd_" . $_POST['db_name'];
                $table_name = $_POST['table_name'];
                $column_name = $_POST['nombre_campo'];


                $data_array = [
                    "name" => $column_name,
                    "new_name" => "",
                    "new_table_name" => "",
                    "type" => "",
                    "length" => "",
                    "array" => "",
                    "action" => "DROP"
                ];
                smurf($db_name, $table_name, $data_array);
            }
            break;

        // Caso para borrar tabla
        case "borrar_tabla":
            if (isset($_POST['db_name']) && isset($_POST['table_name'])) {
                $db_name = "bbdd_" . $_POST['db_name'];
                $table_name = $_POST['table_name'];

                $sql = "DROP TABLE $table_name";

                $result = executeJudgments($sql, $db_name);
                // Devuelve True || False || o el error de  MySQL
                echo json_encode($result);

            }
            break;

        case "agregar_columna":
            if (isset($_POST['db_name']) && isset($_POST['table_name']) && isset($_POST['nombre']) && isset($_POST['tipo'])) {
                $db_name = "bbdd_" . $_POST['db_name'];
                $table_name = $_POST['table_name'];
                $column_name = $_POST['nombre'];
                $data_type = $_POST['tipo'];
                $length = $_POST['longitud'];

                if ($data_type === ("ENUM") || $data_type === ("SET")) {
                    if (isset($_POST['array_set_enum'])) {
                        $tmp_array = $_POST['array_set_enum']; 

                        // Dividir la cadena en elementos individuales usando explode
                        $individual_values = explode(',', $tmp_array);

                        // Crear un nuevo array para almacenar los valores individuales
                        $array_resultado = array();

                        // Agregar cada valor individual al nuevo array
                        foreach ($individual_values as $value) {
                            // Eliminar los espacios en blanco alrededor del valor y agregarlo al array resultante
                            $array_resultado[] = trim($value);
                        }

                        $data_array = [
                            "name" => $column_name,
                            "new_name" => "",
                            "new_table_name" => "",
                            "type" => $data_type,
                            "length" => $length,
                            "array" => $array_resultado,
                            "action" => "ADD"
                        ];
                        smurf($db_name, $table_name, $data_array);
                    }
                } else {
                    $data_array = [
                        "name" => $column_name,
                        "new_name" => "",
                        "new_table_name" => "",
                        "type" => $data_type,
                        "length" => $length,
                        "array" => "",
                        "action" => "ADD"
                    ];
                    smurf($db_name, $table_name, $data_array);
                }

            }
            break;
    }
} else {
    echo json_encode("Datos no recibidos.");
}

/**
 * Función que lanza las consultas a las funciones que crean la $sql y la ejecutan y devuelve mensaje al front
 * 
 * @param string $db_name Contiene el nombre de la base de datos.
 * 
 * @param string $table_name Contiene el nombre de la tabla a modificar.
 * 
 * @param array $data_array Contiene los datos para realizar la modificación en la tabla.
 * 
 * @return mixed Devuelve true o false si se ejecuta correctamente la sentencia o $e con el error que devuelve MySQL.
 */
function smurf($db_name, $table_name, $data_array)
{
    $sql = alterTable($table_name, $data_array);

    if (!$sql) {
        echo json_encode("Acción no permitida.");
    }

    $execute = executeJudgments($sql, $db_name);

    echo json_encode($execute);
}


