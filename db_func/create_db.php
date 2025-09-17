<?PHP
require_once __DIR__ . "/../db_access/verify_login.php";
/**
 * Fichero que contiene las funciones para crear, modificar e insertar datos en las base de datos.
 * 
 * @version 0.7.
 * 
 * @author Group βackend.
 * 
 * @copyright 2023 - 2024 Soft Genius ©.
 */

/**
 * Función que ejecuta las sentencias a la base de datos sino se pasa el valor del nombre de base de datos
 * este será null por defecto y realizará conexión como administrador.
 * 
 * @param string $db_name Nombre de la base de datos a modificar(opcional).
 * @param string $sql Contiene la sentencia para ejecutar en la base de datos.
 * 
 * @return mixed Devuelve bool si se puede o no realizar la consulta y string con el error de MySQL.
 */
function executeJudgments($sql, $db_name = null)
{
    try {
        if ($db_name != null) {
            // Conexión a la base de datos llamando a la clase connectDB
            $mysqli = verify_login::createConnectDBS($db_name);
        } else {
            $mysqli = verify_login::createConnectDBA();
        }


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
            $stmt->close();
            $mysqli->close();
            return true;
        } else {
            //echo "Error al ejecutar la consulta: " . $stmt->error;
            $stmt->close();
            $mysqli->close();
            return false;
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }
}
/**
 * Función que obtiene los tipos de datos de la tabla y modifica el array de inserción
 * para generar arrays asociativos en caso de que sean múltiples inserciones.
 * 
 * @param string $db_name Nombre de la base de datos.
 * @param string $table_name Nombre de la tabla en la que se va a realizar la inserción.
 * @param array $array Array simple con los valores a insertar.
 * 
 * @return mixed Devuelve el array asociativo o el array de arrays asociativo o si hay error false.
 */

function transformArray($db_name, $table_name, $array)
{
    // Conexión a la base de datos llamando a la clase connectDB
    $mysqli = verify_login::createConnectDBS($db_name);

    if ($mysqli === null) {
        return false;
    }

    // Consultar las columnas de la tabla para determinar los tipos de datos
    $columns_query = "SHOW COLUMNS FROM $table_name";
    $result_columns = $mysqli->query($columns_query);

    // Verificar si se obtuvieron resultados
    if ($result_columns->num_rows > 0) {
        $data = array();

        while ($row = $result_columns->fetch_assoc()) {
            // Verificar si el índice 'Extra' existe antes de intentar acceder a él
            if (isset($row['Extra'])) {
                $is_auto_increment = $row["Extra"] === "auto_increment" ? true : false;
            } else {
                // Manejar el caso en que 'Extra' no está definido
                // Por ejemplo, puedes establecer $is_auto_increment en false
                $is_auto_increment = false;
            }
            // Si es auto incremental la columna se omite de la sentencia
            // ARRAY ASOCIATIVO
            if (!$is_auto_increment) {
                $column_name = $row['Field'];
                $array_row[] = $column_name;
                //$i++;
            }
        }

        // Obtiene el número de insert para generar array asociativo
        $number_insert = count($array) / count($array_row);
        $k = 0;
        $index_sub = 0;
        $index_sub_temp = 0;

        // Se genera el array a devolver en base al número de inserciones

        // Lee los datos del array que se pasa como argumento
        for ($i = 0; $i < count($array); $i++) {
            // Lee el array asociativo
            for ($j = 0; $j < count($array_row); $j++) {
                // Se genera el array asociativo con los valores del array q pasamos como parámetro
                // Verificar si la clave existe antes de intentar acceder a ella
                if (isset($array[$j + $index_sub])) {
                    $data[$array_row[$j]] = $array[$j + $index_sub];
                } else {
                    // Si la clave no existe, asignar un valor predeterminado o manejar el error de alguna manera
                    $data[$array_row[$j]] = null;
                }
                $index_sub_temp = $j;
            }
            // Se aumenta el valor del index_sub con el valor del recorrido del bucle +1
            $index_sub += $index_sub_temp + 1;

            $k++;

            if ($k <= $number_insert) {
                $data2[] = $data;
            }
        }

        return $data2;
    } else {
        //echo "Error: No se pudieron obtener las columnas de la tabla.";
        $mysqli->close();
        return false;
    }
}

/**
 * Función que ejecuta el insert into mediante bind_param
 * 
 * @param string $db_name Nombre de la base de datos.
 * @param string $sql Contiene la consulta del insert.
 * @param array $data_array Contiene los datos a insertar.
 * 
 * @return mixed Devuelve true si la inserción es correcta sino false. Si hay error de conexión a MySQL dicho mensaje.
 */
function executeInsert($db_name, $sql, $data_array)
{
    try {
        // Obtener el nombre de la tabla de la consulta SQL
        if (preg_match('/INSERT INTO (\w+)/', $sql, $matches)) {
            $table_name = $matches[1];

            // Conexión a la base de datos llamando a la clase connectDB
            $mysqli = verify_login::createConnectDBS($db_name);

            if ($mysqli === null) {
                return false;
            }

            // Verificar la conexión
            if ($mysqli->connect_error) {
                die("Error de conexión: " . $mysqli->connect_error);
            }

            // Consultar las columnas de la tabla para determinar los tipos de datos
            $columns_query = "SHOW COLUMNS FROM $table_name";
            $result_columns = $mysqli->query($columns_query);

            // Verificar si se obtuvieron resultados
            if ($result_columns->num_rows > 0) {
                // Construir la cadena de tipos de datos para bind_param
                $types = "";
                $params = array();
                $blobs = array();
                $blob_column_index = -1;
                while ($row = $result_columns->fetch_assoc()) {
                    // Si es auto incremental la columna se omite de la sentencia
                    $is_auto_increment = $row["Extra"] === "auto_increment" ? true : false;

                    if (!$is_auto_increment) {

                        if (strpos($row['Type'], 'int') !== false) {
                            $types .= "i"; // Entero
                        } elseif (strpos($row['Type'], 'float') !== false || strpos($row['Type'], 'double') !== false) {
                            $types .= "d"; // Doble
                        } elseif (
                            strpos($row['Type'], 'blob') !== false || strpos($row['Type'], 'binary') !== false ||
                            strpos($row['Type'], 'varbinary') !== false || strpos($row['Type'], 'tinyblob') !== false ||
                            strpos($row['Type'], 'mediumblob') !== false || strpos($row['Type'], 'longblob') !== false
                        ) {
                            $types .= "b"; // Binario
                            // Almacena el índice de la columna de blob si no ha sido almacenado previamente
                            if ($blob_column_index === -1) {
                                $blob_column_index = count($params);
                                $blobs[] = $blob_column_index;
                                $blob_column_index = -1;
                            }
                        } else {
                            $types .= "s"; // Cadena
                        }
                        // Crear una variable para cada parámetro y agregarla al array
                        $params[] = &$data_array[$row['Field']];
                    }
                }

                // Crear la consulta preparada
                $stmt = $mysqli->prepare($sql);

                // Verificar si la preparación de la consulta fue exitosa
                if ($stmt === false) {
                    //echo "Error al preparar la consulta: " . $mysqli->error;
                    $mysqli->close();
                    return false;
                }

                // Unir los tipos a la consulta preparada
                array_unshift($params, $types);

                // Usar call_user_func_array para pasar los parámetros a bind_param
                if (!call_user_func_array(array($stmt, 'bind_param'), $params)) {
                    echo "Error al enlazar parámetros: " . $stmt->error;
                    $stmt->close();
                    $mysqli->close();
                    return false;
                }

                // Recorremos el array de los blobs para modificar el envío de los datos en
                // los casos en los cuales los campos son blob y requieran de usar la función send_long_data
                for ($i=0; $i < count($blobs); $i++) { 
                    $stmt->send_long_data($blobs[$i], $params[$blobs[$i]+1]);
                }

                // Ejecutar la consulta
                if ($stmt->execute()) {
                    $stmt->close();
                    $mysqli->close();
                    return true;
                } else {
                    echo "Error al ejecutar la consulta: " . $stmt->error;
                    $stmt->close();
                    $mysqli->close();
                    return false;
                }
            } else {
                echo "Error: No se pudieron obtener las columnas de la tabla.";
                $mysqli->close();
                return false;
            }
        } else {
            echo "Error: No se pudo obtener el nombre de la tabla de la sentencia SQL.";
            return false; // No se pudo obtener el nombre de la tabla
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }
}



/**
| ******************************************************************* 
| ******************************************************************* 
| ***************************TESTING********************************* 
| ******************************************************************* 
| ******************************************************************* 
 */
/*
$sql = "INSERT INTO cliente (nombre, apellido_A, apellido_B, DNI, Direccion, Movil, Mail, Password) VALUES (?, ?, ?, ?, ?, ?, ?, ?);";
$db_name = "bbdd_softgenius";
$array = [
    "pedro1",
    "pedro2",
    "pedro3",
    "pedro4",
    "pedro5",
    "pedro6",
    "pedro7",
    "pedro8",
    "pedro11",
    "pedro21",
    "pedro31",
    "pedro41",
    "pedro51",
    "pedro61",
    "pedro71",
    "pedro81",
    "pedro12",
    "pedro22",
    "pedro32",
    "pedro42",
    "pedro52",
    "pedro62",
    "pedro72",
    "pedro82",
    "pedro13",
    "pedro233",
    "pedro33",
    "pedro43",
    "pedro53",
    "pedro63"
];
$table_name = "clientes";
$result = insertFront($array, $db_name, $table_name);
print_r($result);
$array7 =
    [
        "nombre" => "pedro",
        "apellidoA" => "pedro2",
        "apellidoB" => "pedro",
        "DNI" => "pedro",
        "Direccion" => "pedro3",
        "Movil" => "pedro",
        "Mail" => "pedro",
        "Password" => "pedro",
    ];

$array2 = transformArray($db_name, $table_name, $array);
print_r($array2);
//executeInsert($db_name, $sql, $array2);
$array_asociativo = $array2;
if (!empty($array_asociativo) && is_array($array_asociativo[0])) {
    // Inicializamos un array vacío para almacenar los resultados combinados
    $merged_array = [];

    // Iteramos sobre cada array asociativo en $array_asociativo
    foreach ($array_asociativo as $sub_array) {
        // Combinamos el array actual con el array acumulado utilizando array_merge
        $merged_array = array_merge($merged_array, $sub_array);

        // Llamamos a la función executeInsert con el array combinado
       $result = executeInsert($db_name, $sql, $merged_array);
    }
    return $result;
} else {
   return executeInsert($db_name, $sql, $array_asociativo);
} */

/**
 * Llama a las diferentes funciones para poder realizar la inserción.
 * 
 * @param array $array_insert Contiene los datos a insertar.
 * @param string $db_name Contiene el nombre de la base de datos.
 * @param string $table_name Contiene el nombre de la tabla a insertar los datos.
 * 
 * @return mixed Devuelve true si se realiza la inserción false si hay datos erroneos y mensaje de error que devuelve mysql.
 */
function insertFront($array_insert, $db_name, $table_name)
{
    $sql = generateInsertSQL($db_name, $table_name);


    $array_transform = transformArray($db_name, $table_name, $array_insert);

    if (!empty($array_transform) && is_array($array_transform[0])) {
        // Inicializamos un array vacío para almacenar los resultados combinados
        $merged_array = [];
        $tmp_result = "";
        // Iteramos sobre cada array asociativo en $array_asociativo
        foreach ($array_transform as $sub_array) {
            // Combinamos el array actual con el array acumulado utilizando array_merge
            $merged_array = array_merge($merged_array, $sub_array);
            $tmp_result = "";
            // Llamamos a la función executeInsert con el array combinado
            $tmp_result = executeInsert($db_name, $sql, $merged_array);

            if (!$tmp_result === true) {
                return $tmp_result;
            }
        }
        return $tmp_result;
    } else {
        $result_one_insert = executeInsert($db_name, $sql, $array_transform);
        return $result_one_insert;
    }
}

/**
 * Función para la creación de bases de datos.
 * Pendiente de saber si se pasa como variable el tipo de utf8.
 * 
 * @param string $db_name Nombre de la base de datos.
 * @return string Devuelve la sentencia de creación de la base de datos.
 */
function createDB($db_name)
{
    $sql = "CREATE DATABASE IF NOT EXISTS $db_name CHARACTER SET utf8 COLLATE utf8_general_ci;";
    return $sql;
}

/**
| ******************************************************************* 
| ******************************************************************* 
| ***************************TESTING********************************* 
| ******************************************************************* 
| ******************************************************************* 
 */
/*
$db_name = "nombre_de_tu_base_datos";

$sql = createDB($db_name);

echo "\nSQL del método createTable línea 71:\n   $sql\n";
*/


/**
 * Función para la creación de diferentes tablas.
 * 
 * @param string $table_name Nombre de la tabla.
 * @param array $columns Datos de las columnas que ha introducido el usuario.
 * 
 * @return string Devuelve la sentencia de creación de la tabla.
 */
function createTable($tableName, $columns)
{
    $sql = "CREATE TABLE IF NOT EXISTS `$tableName` (";

    foreach ($columns as $column) {
        $column_name = $column['name'];
        $data_type = $column['type'];
        $length = isset($column['length']) ? $column['length'] : null;
        $is_not_null = isset($column['not_null']) && $column['not_null'] ? "NOT NULL" : "";
        //$default_value = isset($column['default_value']) ? "DEFAULT '{$column['default_value']}'" : "";
        $pk = isset($column['pk']) && $column['pk'] ? "PRIMARY KEY" : "";
        $auto_increment = isset($column['auto_increment']) && $column['auto_increment'] ? "AUTO_INCREMENT" : "";


        $sql .= "`$column_name` $data_type";

        if ($length !== null) {
            $sql .= "($length)";
        }

        $sql .= " $is_not_null $pk $auto_increment, ";
    }

    // Remove the trailing comma and space
    $sql = rtrim($sql, ', ');

    $sql .= ")";

    return $sql;
}

/**
| ******************************************************************* 
| ******************************************************************* 
| ***************************TESTING********************************* 
| ******************************************************************* 
| ******************************************************************* 
 */
/*
$table_name = "example_table";
$columns = [
    //["name" => "id", "type" => "INT", "length" => 200, "not_null" => true, "default_value" => null],
    //["name" => "name", "new_name" => "name56", "type" => "VARCHAR", "length" => 255, "not_null" => true, "default_value" => null]
    //["name" => "email", "type" => "VARCHAR", "length" => 255, "not_null" => false, "default_value" => null, "action" => "DROP"]
    ["name" => "nombre_columna", "type" => "BIGINT", "not_null" => false, "default_value" => null]


];

$db_name = "casaempeno";

$sql = createTable($table_name, $columns);

echo "\nSQL del método createTable línea 132:\n  $sql\n";
*/

//$prueba = executeJudgments($db_nam2, $sql);

//echo "\nSQL del método alterTable línea 119:$prueba\n";


/**
 * Función para modificar los párametros de las diferentes tablas de la base datos.
 * 
 * @param string $table_name Nombre de la tabla.
 * @param array $columns Datos de las columnas que ha introducido el usuario para modificar.
 * 
 * @return string Devuelve la sentencia de creación del alter table.
 */
function alterTable($table_name, $data_array)
{
    $sql = "ALTER TABLE `$table_name`";

    $column_name = isset($data_array['name']) ? $data_array['name'] : null;
    $new_column_name = isset($data_array['new_name']) ? $data_array['new_name'] : null;
    $new_table_name = isset($data_array['new_table_name']) ? $data_array['new_table_name'] : null;
    $data_type = isset($data_array['type']) ? $data_array['type'] : null;
    $length = isset($data_array['length']) ? $data_array['length'] : null;
    $array = isset($data_array['array']) ? $data_array['array'] : null;
    $action = isset($data_array['action']) ? $data_array['action'] : null;

    if ($action != null) {
        switch ($action) {
            // Si la acción es 'DROP', simplemente eliminamos la columna
            case "DROP":
                $sql .= " DROP COLUMN `$column_name`";
                break;
            // Si la acción es 'MODIFY', modificamos la columna
            case "MODIFY":
                $sql .= " MODIFY `$column_name` $data_type";

                // Gestión de los campos ENUM y SET
                if ($data_type === "ENUM" || $data_type === "SET") {
                    // Añadimos paréntesis al inicio
                    $sql .= "(";
                    // Obtenemos el valor y lo ponemos entre '' y separamos del siguiente por una ,
                    for ($i = 0; $i < count($array); $i++) {
                        $sql .= "'$array[$i]',";
                    }
                    // Eliminamos la útima coma y añadimos el cierre del paréntesis
                    $sql = rtrim($sql, ', ');
                    $sql .= ")";
                } else {
                    // Si la longitud no es null la añadimos a la construcción
                    if ($length != null) {
                        $sql .= "($length)";
                    }
                }

                // Dejamos los campos not null
                $sql .= " NOT NULL";

                break;
            // Si la acción es 'RENAME_C', renombramos la columna
            case "RENAME_C":
                if ($length != null) {
                    $sql .= " CHANGE `$column_name` `$new_column_name` $data_type($length)";
                } else {
                    $sql .= " CHANGE `$column_name` `$new_column_name` $data_type";
                }
                // Dejamos los campos not null
                $sql .= " NOT NULL";

                break;
            // Si la acción es ADD añadimos una columna
            case "ADD":
                $sql .= " ADD `$column_name` $data_type";

                if ($length != null) {

                    // Gestión de los campos ENUM y SET
                    if ($data_type === "ENUM" || $data_type === "SET") {
                        // Añadimos paréntesis al inicio
                        $sql .= "(";
                        // Obtenemos el valor y lo ponemos entre '' y separamos del siguiente por una ,
                        for ($i = 0; $i < count($array); $i++) {
                            $sql .= "'$array[$i]',";
                        }
                        // Eliminamos la útima coma y añadimos el cierre del paréntesis
                        $sql = rtrim($sql, ', ');
                        $sql .= ")";
                    } else {
                        // Si la longitud no es null la añadimos a la construcción
                        if ($length != null) {
                            $sql .= "($length)";
                        }
                    }

                }
                // Dejamos los campos not null
                $sql .= " NOT NULL";
                //print_r($sql);
                break;
            // Si la acción es 'RENAME_T', renombramos la tabla
            case "RENAME_T":
                $sql .= " RENAME TO $new_table_name";
                break;
            default:
                return null;
        }
        return $sql;
    } else {
        return false;
    }

}
/*
$table_name = "cliente";
$array = ["hola", "pa ti mi cola"];
$data_array = [
    "name" => "Nombre",
    "new_name" => "nombre2",
    "new_table_name" => "",
    "type" => "SET",
    "length" => "",
    "array" => $array,
    "action" => "CHANGE"
];

$sql = alterTable($table_name, $data_array);

print_r($sql); 
*/

/**
 * Función que gestiona las multiples llamadas a las funciones: 
 * getPrimaryKeyColumn, createConnectDBS y alterTableAddFK, para realizar
 * la creación de la clave foranea en una tabla.
 * 
 * @param string $db_name_pk Contiene el nombre de la base de datos de la tabla con la primary key.
 * @param string $table_name_pk Contiene el nombre de la tabla con la primary key.
 * @param string $db_name_fk Contiene el nombre de la base de datos de la tabla con la foraign key.
 * @param string $table_name_fk Contiene el nombre de la tabla con la foraign key.
 * 
 * @return bool Devuelve true si la modificación es correcta sino false.
 */
function alterTableAddColumnFK($db_name_pk, $table_name_pk, $db_name_fk, $table_name_fk)
{
    // Llamada para obtener la clave primaria de la columna
    $column_pk = getPrimaryKeyColumn($db_name_pk, $table_name_pk);

    // Obtiene la conexión
    $mysqli_fk = verify_login::createConnectDBS($db_name_fk);

    // Se genera la sentencia sql para agregar la columna fk a la tabla con la clave foraign key
    $sql = "ALTER TABLE `$table_name_fk` ADD `" . $column_pk . "_fk` INT";

    if ($mysqli_fk->query($sql) === TRUE) {
        // Se obtiene la construcción de la sentencia para la ejecución de la foraign key 
        $sql_fk = alterTableAddFK($db_name_pk, $table_name_pk, $db_name_fk, $table_name_fk, $column_pk);

        if ($mysqli_fk->query($sql_fk) === TRUE) {
            return true;
        } else {
            return false;
        }
    } else {
        return false;
    }
}

/**
 * Función que contruye la sentencia para generar una foraign key en una tabla.
 * 
 * @param string $db_name_pk Contiene el nombre de la base de datos de la tabla con la primary key.
 * @param string $table_name_pk Contiene el nombre de la tabla con la primary key.
 * @param string $db_name_fk Contiene el nombre de la base de datos de la tabla con la foraign key.
 * @param string $table_name_fk Contiene el nombre de la tabla con la foraign key.
 * @param string $column_pk Contiene el nombre de la columna primaria, para la tabla con la clave foranea se añade _fk al nombre.
 * 
 * @return string Devuelve la consulta sql para poder realizar la modificación.
 */
function alterTableAddFK($db_name_pk, $table_name_pk, $db_name_fk, $table_name_fk, $column_pk)
{
    $sql = "ALTER TABLE $table_name_fk
    ADD CONSTRAINT fk_" . $table_name_fk . "_" . $table_name_pk . "
    FOREIGN KEY (" . $column_pk . "_fk) REFERENCES `$db_name_pk`.$table_name_pk($column_pk)";

    return $sql;
}

/**
 * Función que obtiene el campo de la clave primaria de una función.
 * 
 * @param string $db_name Contiene el nombre de la base de datos.
 * @param string $table_name Contiene el nombre de la tabla a insertar los datos.
 * 
 * @return mixed Devuelve como string el nombre de la clave primaria o null si la tabla no tiene.
 */
function getPrimaryKeyColumn($db_name, $table_name)
{
    $mysqli = verify_login::createConnectDBS($db_name);

    // Se lanza la consulta show columns contra la base datos y su correspondiente tabla y se obtiene el resultado
    $result = $mysqli->query("SHOW COLUMNS FROM `$db_name`.`$table_name`");

    // Se obtiene el nombre de la columna que es primary key
    while ($row = $result->fetch_assoc()) {
        if ($row['Key'] === 'PRI') {
            return $row['Field'];
        }
    }

    return null;
}

/**
| ******************************************************************* 
| ******************************************************************* 
| ***************************TESTING********************************* 
| ******************************************************************* 
| ******************************************************************* 
 */
/*
// Datos de la tabla director
$table_name = 'director';
$columns = [
    [
        'name' => 'Inicio_Carrera',
        'type' => 'int(11)',
        'default_value' => '0',
        'action' => '0000' // El action si solo se quiere hacer alter pasar vacío o poner cualquier otra cosa q no sea DROP o CHANGE
    ]
];

// Llamar al método alterTable
$sql = alterTable($table_name, $columns);

// Mostrar el SQL generado
echo "\nSQL del método alterTable línea 202:\n   $sql\n";

*/


/**
 * Función para modificar el contenido de las columnas.
 * 
 * @param string $db_name Nombre de la base de datos.
 * @param string $table_name Nombre de la tabla.
 * @param array $data Datos de las columnas y valores que quiere modificar el usuario.
 * @param array $conditions Condición que indica la columna con clave primaria y posición en la misma.
 * 
 * @return bool Devuelve true o false si la sentencia se ejecuta true sino false.
 */
function updateTable($db_name, $table_name, $data, $conditions)
{
    $mysqli = verify_login::createConnectDBS($db_name);
    // Preparar la consulta
    $sql = "UPDATE `$table_name` SET ";

    $type = "";
    $column_value = "";

    // Construir la parte SET de la consulta
    foreach ($data as $column => $value) {
        $sql .= "`$column` = ?, ";
        // Obtener el tipo de dato del valor y agregarlo a la cadena
        $type = getColumnParamType($db_name, $table_name, $column);
        // Obtiene el valor a modificar
        $column_value = $value;
    }

    // Eliminar la coma y el espacio extra al final
    $sql = rtrim($sql, ', ');

    // Añadir condiciones WHERE
    $sql .= " WHERE ";

    foreach ($conditions as $column => $value) {
        $sql .= "`$column` = '$value' AND ";
    }

    // Eliminar el último 'AND' y espacios
    $sql = rtrim($sql, 'AND ');

    // Preparar la sentencia    
    $stmt = $mysqli->prepare($sql);

    // Si se prepara la sentencia se ejecuta mediante bind_param con el tipo y el valor
    if ($stmt) {
        $stmt->bind_param($type, $column_value);
        $stmt->execute();
        $stmt->close();
        $mysqli->close();
        return true;
    } else {
        $stmt->close();
        $mysqli->close();
        return false;
    }
}

/**
 * Función que devuelve el tipo de campo de una columna para poder ejecutar la sentencia por bind_param.
 * 
 * @param string $db_name Contiene el nombre de la base de datos.
 * @param string $table_name Contiene el nombre de la tabla.
 * @param string $column_name Contiene el nombre de la columana a verificar.
 * 
 * @return string Devuelve la letra correspondiente al tipo de campo.
 */
function getColumnParamType($db_name, $table_name, $column_name)
{
    $mysqli = verify_login::createConnectDBS($db_name);

    $result = $mysqli->query("SHOW COLUMNS FROM `$table_name`");
    $type = "";
    while ($row = $result->fetch_assoc()) {
        if ($row['Field'] == $column_name) {
            // Convierte el tipo de dato de la columna en el tipo de dato de parámetro de bind_param

            if (strpos($row['Type'], 'int') !== false) {
                $type .= "i"; // Entero
            } elseif (strpos($row['Type'], 'float') !== false || strpos($row['Type'], 'double') !== false) {
                $type .= "d"; // Doble
            } elseif (
                strpos($row['Type'], 'blob') !== false || strpos($row['Type'], 'binary') !== false ||
                strpos($row['Type'], 'varbinary') !== false || strpos($row['Type'], 'tinyblob') !== false ||
                strpos($row['Type'], 'mediumblob') !== false || strpos($row['Type'], 'longblob') !== false
            ) {
                $type .= "b"; // Binario
            } else {
                $type .= "s"; // Cadena
            }

        }
    }
    return $type;
}

/**
| ******************************************************************* 
| ******************************************************************* 
| ***************************TESTING********************************* 
| ******************************************************************* 
| ******************************************************************* 
 */
/*
// Ejemplo de uso
$table_name = "director";
$data = [
    "Nombre_Director" => "Juanito",
    "Inicio_Carrera" => "1999"
];
$conditions = [
    "Director_ID" => 1
];

$sql = updateTable($table_name, $data, $conditions);

echo "\nSQL del método updateTable línea 257:\n  $sql\n";
*/

/**
 * Método para la creación de diferentes de sentencias de inserción.
 * Lee las columnas de la base de datos excepto la columna clave primaria si es AUTO_INCREMENT.
 * Agrega las columnas a la sentencia y se pasan las mismas como VALUES como = ('?').
 * 
 * @param string $db_name Nombre de la de la base de datos.
 * @param string $table_name Nombre de la tabla.
 * 
 * @return mixed Devuelve la sentencia de insert into de la tabla o null si falla la ejecución.
 */
function generateInsertSQL($db_name, $table_name)
{
    // Obtiene la conexión para la base de datos que recibe como parámetro
    require_once __DIR__ . "/../db_access/verify_login.php";
    $mysqli = verify_login::createConnectDBS($db_name);

    // Verifica si se ha cargado la conexión a la base de datos
    if ($mysqli === null) {
        return null;
    }

    // Consultar las columnas de la tabla
    $sql_columns = "SHOW COLUMNS FROM $table_name";
    $result_columns = $mysqli->query($sql_columns);

    // Verificar si se obtuvieron resultados
    if ($result_columns->num_rows > 0) {
        $columns = [];
        // Iterar sobre cada columna y obtener su nombre
        while ($row = $result_columns->fetch_assoc()) {
            $column_name = $row["Field"];
            // Si es auto incremental la columna se omite de la sentencia
            $is_auto_increment = $row["Extra"] === "auto_increment" ? true : false;
            if (!$is_auto_increment) {
                $columns[] = $column_name;
            }
        }

        // Construir la sentencia SQL de inserción
        $columns_str = implode(', ', $columns);
        $values_placeholder = implode(', ', array_fill(0, count($columns), '?'));

        // Sentencia SQL completa
        $sql = "INSERT INTO $table_name ($columns_str) VALUES ($values_placeholder);";

        // Cerrar conexión
        $mysqli->close();

        // Devolver la sentencia SQL de inserción
        return $sql;
    } else {
        // Si no hay resultados, cerrar conexión y devolver null
        $mysqli->close();
        return null;
    }
}

/**
 * Función que elimina registros de las tablas.
 * 
 * @param string $db_name Nombre de la de la base de datos.
 * @param string $table_name Nombre de la tabla.
 * @param int $id Contiene el número del id del registro a borrar
 * 
 * @return mixed Devuelve true si se elimina el registro correctamente false sino, si hay error de ejecución MySQL devuelve el error.
 */
function deleteRecord($db_name, $table_name, $id)
{
    try {
        $mysqli = verify_login::createConnectDBS($db_name);
        $column_pk = getPrimaryKeyColumn($db_name, $table_name);

        // Sentencia SQL preparada
        $sql = "DELETE FROM $table_name WHERE $column_pk = ?";
        // Preparar la sentencia    
        $stmt = $mysqli->prepare($sql);

        // Si se prepara la sentencia se ejecuta mediante bind_param con el tipo y el valor
        if ($stmt) {
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $rowCount = $stmt->affected_rows;
            // Retornar el número de filas afectadas
            if ($rowCount > 0) {
                $stmt->close();
                $mysqli->close();
                return true;
            }
            $stmt->close();
            $mysqli->close();
        } else {
            $stmt->close();
            $mysqli->close();
            return false;
        }
    } catch (Exception $e) {
        return $e->getMessage();
    }
}

/**
| ******************************************************************* 
| ******************************************************************* 
| ***************************TESTING********************************* 
| ******************************************************************* 
| ******************************************************************* 
 */
/*
$db_name = "casaempeño";

$table_name = "cliente";

$sql = generateInsertSQL($db_name, $table_name);

echo "\nSQL del método generateInsertSQL línea 330:\n  $sql\n";
*/


?>