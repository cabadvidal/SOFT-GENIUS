<?PHP
/**
 * Contiene consultas básicas a las bases de datos predefinidas.
 * 
 * @version 0.7.
 * 
 * @author Group βackend.
 * 
 * @copyright 2023 - 2024 Soft Genius ©.
 */

/**
 * Función que ejecuta una consulta SELECT en la base de datos y devuelve los resultados.
 * Si no se pasa el nombre de la base de datos, se conecta como administrador.
 * 
 * @param string $sql Consulta SQL a ejecutar.
 * @param string $db_name Nombre de la base de datos.
 * 
 * @return mixed Devuelve un array con los resultados de la consulta si tiene éxito, o false si falla.
 */
function executeJudgmentsSelect($sql, $db_name)
{
    try {
        require_once __DIR__ . "/../db_access/verify_login.php";

        
        // Conexión a la base de datos llamando a la clase connectDB
        $mysqli = verify_login::createConnectDBS($db_name);
       

        if ($mysqli === null) {
            return false;
        }

        // Verifica la conexión
        if ($mysqli->connect_error) {
            die("Error de conexión: " . $mysqli->connect_error);
        }

        // Ejecuta la consulta preparada
        $stmt = $mysqli->prepare($sql);

        // Ejecuta la consulta
        if ($stmt->execute()) {
            // Obtiene los resultados
            $result = $stmt->get_result();

            // Crea un array para almacenar los datos
            $data = array();

            // Itera sobre los resultados y los guarda en el array
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            // Cierra el statement y la conexión
            $stmt->close();
            $mysqli->close();

            // Retorna los datos
            return $data;
        } else {
            echo "Error al ejecutar la consulta: " . $stmt->error;
            $stmt->close();
            $mysqli->close();
            return false;
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }
}



/**
 * Construye consulta para obtener datos de una columna.
 * 
 * @param string $table_name Contiene el nombre de la tabla.
 * @param string $column Contiene el nombre de la columna.
 * 
 * @return string Devuelve la consulta. 
 */
function selectRecordsColumn ($table_name, $column) {
    return "SELECT $column FROM $table_name";
}

/**
| ******************************************************************* 
| ******************************************************************* 
| ***************************TESTING********************************* 
| ******************************************************************* 
| ******************************************************************* 
 */
/*
require_once ("create_db.php");

require_once ("verify_data.php");
        $db_name = "bbdd_cuentas_softgenius";
        $table_name = "cuenta";
        $column = "PrivilegioID";

        $sql = selectRecordsColumn($table_name, $column);
        $result = executeJudgments($sql, $db_name); 
        if($result) {
            $result_array = [];
            $result_array = executeJudgmentsSelect($sql, $db_name);
           
            print_r($result_array);
        }
 */
?>