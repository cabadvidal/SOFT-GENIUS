<?php
/**
 * Archivo de gestión de solicitud por Ajax desde el archivo js crear tabla
 * Se gestiona la creación con las llamadas correspondientes para la creación de la consulta y su ejecución.
 * 
 * @version 0.7.
 * 
 * @author Group βackend.
 * 
 * @copyright 2023 - 2024 Soft Genius ©.
 */
// Importar el archivo que contiene las funciones necesarias
require_once __DIR__ . "/../../db_func/create_db.php";
require_once __DIR__ . "/../../db_func/verify_data.php";

// Verificar si se recibieron los parámetros esperados
if(isset($_POST['db_name']) && isset($_POST['table_name']) && isset($_POST['columns'])) {
    // Obtener los valores de los parámetros
    $db_name = $_POST['db_name'];
    $table_name = $_POST['table_name'];
    // Convertir a minúsculas
    $table_name_tmp = strtolower($table_name); 
    // Reemplazar espacios por _
    $table_name_tmp = str_replace(' ', '_', $table_name_tmp);
    if (isCheckText($table_name_tmp)) {
        // Convertir primera letra a mayúscula
        $table_name_tmp = ucfirst($table_name_tmp);
        $table_name = $table_name_tmp;
    } else {
        echo json_encode("Valor de tabla: $table_name contiene caracteres prohibidos.");
        exit; 
    }

    $columns = $_POST['columns'];

    foreach ($columns as $column) {
        $column_name = $column['name'];
        $data_type = $column['type'];
        $length = isset($column['length']) ? $column['length'] : null;
    
        $column_name_tmp = strtolower($column_name); 
        $column_name_tmp = str_replace(' ', '_', $column_name_tmp);
        $column_name = $column_name_tmp;
        // Verificar el nombre de la columna
        if (!isCheckColumnName($column_name)) {
            echo json_encode("Fallo: el nombre de columna '$column_name' contiene caracteres no permitidos.");
            exit; // Terminar la ejecución si hay un error
        }

        $column_name = ucfirst($column_name);
    
        // Verifica el tipo
        if (!isCheckType($data_type)) {
            echo json_encode("Fallo: el tipo de datos '$data_type' no es válido para la columna '$column_name'.");
            exit; // Terminar la ejecución si hay un error
        }
    

        // Verifica los casos especiales de enum y set y en el resto q la longitud solo sean números
        if($data_type === "ENUM" || $data_type === "SET") {
            $length_tmp = str_replace("'", " ", $length);
            $length_tmp = str_replace(",", " ", $length_tmp);
            $length_tmp = str_replace(" ", "_", $length_tmp);
            echo json_encode($length_tmp);
            if(!isCheckText($length_tmp)) {
                echo json_encode("Fallo: la longitud '$length' de la columna '$column_name' no es un valor válido.");
                exit; // Terminar la ejecución si hay un error
            }
            // Verificar la longitud si es aplicable
        } else{
            if ($length !== null && !is_numeric($length)) {
                echo json_encode("Fallo: la longitud '$length' de la columna '$column_name' no es un número válido.");
                exit; // Terminar la ejecución si hay un error
            }
        }
    }
    

    // Crear la sentencia SQL para crear la tabla
    $sql = createTable($table_name, $columns);

    // Ejecutar la sentencia SQL
    if(executeJudgments($sql, $db_name)){
        // Si la sentencia se ejecutó correctamente, devolver una respuesta exitosa
        // echo json_encode("Tabla $table_name creada correctamente en la BBDD $db_name.");
        echo json_encode(true);
    } else {
        // Si hubo un error al ejecutar la sentencia, devolver un mensaje de error
        $error = executeJudgments($sql, $db_name);
        echo json_encode($error);
    }
} else {
    echo json_encode("Datos no recibidos.");
}
?>
