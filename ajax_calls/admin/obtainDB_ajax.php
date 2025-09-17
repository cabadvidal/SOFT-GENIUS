<?PHP
/**
 * Archivo de gestión de solicitud por Ajax desde el archivo js menu izquierdo, mostrar dbs, mostrar campos y mostrar tablas
 * Se gestiona la consulta la estructura de las base de datos y sus tablas.
 * 
 * @version 0.7.
 * 
 * @author Group βackend.
 * 
 * @copyright 2023 - 2024 Soft Genius ©.
 */
require_once __DIR__ . "/../../db_func/read_db.php";

$data_bases = obtainDB();
// Arreglo para almacenar la información de las bases de datos y sus tablas
$data_base_tables = [];

// Iterar sobre cada base de datos y obtener sus tablas
foreach ($data_bases as $data_base_name => $db_name) {
    // Obtener los nombres de las tablas para esta base de datos
    $tables_name = extractTableNames($db_name);
    
    // Agregar el nombre de la base de datos junto con sus tablas al arreglo
    $data_base_tables[$db_name] = $tables_name;
}
header('Content-Type: application/json');
// json_encode me devolvía un string vacío y con esto puedo compruebo el error
try {
    $json = json_encode($data_base_tables);
    if ($json === false) {
        throw new Exception('Error encoding JSON: ' . json_last_error_msg());
    }
    echo $json;
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}