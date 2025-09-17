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
// Importar el archivo que contiene las funciones necesarias
require_once __DIR__ . "/../../db_func/create_db.php";
require_once __DIR__ . "/../../db_func/verify_data.php";
require_once __DIR__ . "/../../db_access/verify_login.php";
if (isset($_POST['db_name'])) {
    $db_name = "bbdd_" . $_POST['db_name'];

    $db_name_tmp = strtolower($db_name);
    $db_name_tmp = str_replace(' ', '_', $db_name_tmp);

    if (isCheckText($db_name_tmp)) {
        $db_name = $db_name_tmp;

        $mysqli = verify_login::createConnectDBS($db_name);

        // Verificar la conexión
        if ($mysqli->connect_error) {
            die("Error de conexión: " . $mysqli->connect_error);
        }

        // Desactivar la verificación de claves foráneas temporalmente
        $mysqli->query("SET FOREIGN_KEY_CHECKS = 0");

        // Obtener las claves foráneas
        $result = $mysqli->query("SELECT CONSTRAINT_NAME, TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = '$db_name' AND REFERENCED_TABLE_NAME IS NOT NULL");

        // Iterar sobre los resultados y eliminar las claves foráneas
        while ($row = $result->fetch_assoc()) {
            $table = $row['TABLE_NAME'];
            $constraint = $row['CONSTRAINT_NAME'];
            $sql = "ALTER TABLE $table DROP FOREIGN KEY $constraint";
            $mysqli->query($sql);
            $sql2 = "DROP TABLE $table";
            $mysqli->query($sql2);
        }

        // Reactivar la verificación de claves foráneas
        $mysqli->query("SET FOREIGN_KEY_CHECKS = 1");

        // Eliminar la base de datos
        $mysqli->query("DROP DATABASE $db_name");

        // Cerrar la conexión
        $mysqli->close();

        echo json_encode("Base de datos $db_name borrada con éxito.");

    } else {
        echo json_encode("Caracteres no válidos en el nombre de la base de datos $db_name");
    }
} else {
    echo json_encode("Datos no recibidos.");
}
