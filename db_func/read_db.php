<?php

/**
 * Fichero que contiene las funciones para leer las bases de datos y su estructura.
 * 
 * @version 0.7.
 * 
 * @author Group βackend.
 * 
 * @copyright 2023 - 2024 Soft Genius ©.
 */

require_once __DIR__ . "/../db_access/verify_login.php";

/**
 * Función para obtener los nombres de todas las bases de datos q empiezan por "bbdd_".
 * 
 * @return mixed Devuelve un array $data_base con el nombre de las bases de datos, null si no hay bases de datos o $mysqli si hay un error con la conexión.
 */
function obtainDB()
{
    $mysqli = verify_login::createConnectDBA();

    if ($mysqli == null) {
        return $mysqli;
    }

    // Consultar las bases de datos disponibles
    $sql = "SHOW DATABASES";
    $result = $mysqli->query($sql);

    // Verificar si se obtuvieron results
    if ($result->num_rows > 0) {
        $data_base = array();
        // Iterar sobre cada row de results y almacenar el nombre de la base de datos
        while ($row = $result->fetch_assoc()) {
            $db_name = $row["Database"];
            // Solo se obtienen las bases de datos que empiezan por "bbdd_"
            if (str_contains($db_name, "bbdd_")) {
                $data_base[] = $db_name;
            }
        }
        // Cerrar la conexión
        $mysqli->close();
        // Devolver el array de bases de datos
        return $data_base;
    } else {
        echo "No se encontraron bases de datos.";
        // Cerrar la conexión
        $mysqli->close();
        return null;
    }
}


/**
 * Función para cargar todas las tablas, sus respectivas columnas y la información de estas en un array de arrays.
 * 
 * @param string $db_name nombre de la base de datos para obtener su información.
 * 
 * @return mixed Devuelve un array de arrays con toda la información recogida por la función.
 * Si hay algún error devuelve $mysqli con dicho error o devuelve null sino se encuentran datos.
 */
function loadParamDB($db_name)
{
    // Obtiene la conexión para la base de datos que recibe como parámetro
    $mysqli = verify_login::createConnectDBS($db_name);

    // Verifica si se ha cargado la conexión a la base de datos
    if ($mysqli == null) {
        return $mysqli;
    }

    // Consultar las tablas en la base de datos
    $sql = "SHOW TABLES";
    $result = $mysqli->query($sql);

    // Verificar si se obtuvieron results
    if ($result->num_rows > 0) {
        $table_parameters = array();
        // Iterar sobre cada tabla y obtener sus parámetros
        while ($row = $result->fetch_assoc()) {
            $table_name = $row["Tables_in_" . $db_name];
            $table_params = array();

            // Obtener el nombre de las columnas de las tablas
            $sql_columns = "SHOW COLUMNS FROM $table_name";
            $result_columns = $mysqli->query($sql_columns);

            // Contar el número de columnas
            $num_columns = $result_columns->num_rows;

            // Verificar si se obtuvieron results
            if ($num_columns > 0) {
                // Almacenar el número de columnas y el nombre de la tabla
                $table_parameters[$table_name]['num_columns'] = $num_columns;

                // Iterar sobre cada columna y obtener sus detalles
                while ($row_columns = $result_columns->fetch_assoc()) {
                    $column_name = $row_columns["Field"];
                    $column_type = $row_columns["Type"];
                    $column_length = '';
                    // Obtiene la longitud de la columna si el tipo dispone de la misma mediante expresión regular
                    if (preg_match('/\((\d+(?:,\d+)?)\)/', $column_type, $matches)) {
                        $column_length = $matches[1];
                    }
                    $is_pk = $row_columns["Key"] === "PRI" ? true : false; // Verificar si es clave primaria (PK)
                    $is_fk = $row_columns["Key"] === "MUL" ? true : false; // Verificar si es clave foránea (FK)
                    $is_auto_increment = $row_columns["Extra"] === "auto_increment" ? true : false; // Verificar si es auto_incremental

                    // Almacenar los detalles de la columna en un array
                    $column_details = array(
                        'type' => $column_type,
                        'length' => $column_length,
                        'is_pk' => $is_pk,
                        'is_fk' => $is_fk,
                        'is_auto_increment' => $is_auto_increment
                    );

                    // Agregar los detalles de la columna al array de parámetros de la tabla
                    $table_params[$column_name] = $column_details;
                }
            }


            // Agregar los parámetros de la tabla al array de parámetros de las tablas
            $table_parameters[$table_name]['columns'] = $table_params;
        }

        // Cerrar conexión
        $mysqli->close();

        // Devolver los parámetros de las tablas
        return $table_parameters;
    } else {

        // Si no hay results, cerrar conexión y devolver null
        $mysqli->close();
        return null;
    }
}

/**
 * Función que devuelve el nombre de tablas de una base de datos.
 * 
 * @param string $db_name Nombre de la base de datos a consultar.
 * 
 * @return mixed Devuelve un array solo con los nombres de las tablas de la base de datos si hay un error devuelve null.
 */
function extractTableNames($db_name)
{
    $tableParameters = loadParamDB($db_name);
    // Array para almacenar solo los nombres de las tablas
    $tableNames = [];
    if ($tableParameters != null) {

        // Iterar sobre cada tabla en los parámetros de la tabla
        foreach ($tableParameters as $tableName => $tableDetails) {
            // Agregar el nombre de la tabla al nuevo array
            $tableNames[] = $tableName;
        }
        return $tableNames;
    } else {
        return null;
    }
}


/**
 * Función que devuelve las columnas y todos los registros de una tabla específica de una base de datos.
 * 
 * @param string $db_name Nombre de la base de datos.
 * @param string $table_name Nombre de la tabla a consultar.
 * 
 * @return mixed Devuelve un array con las columnas de la tabla y un array con todos los registros, o null si hay un error.
 */
function extractTableWithColumnsAndRecords($db_name, $table_name)
{
    $tableParameters = loadParamDB($db_name);
    // Verificar si los parámetros de la tabla se cargaron correctamente
    if ($tableParameters != null) {
        // Verificar si la tabla especificada existe en los parámetros de la tabla
        if (isset($tableParameters[$table_name])) {
            // Obtener los detalles de la tabla
            $tableDetails = $tableParameters[$table_name];
            // Crear un array con las columnas de la tabla
            $columns = array_keys($tableDetails['columns']);
            // Crear un array vacío para almacenar los registros
            $records = [];

            // Obtener la conexión a la base de datos
            $mysqli = verify_login::createConnectDBS($db_name);

            // Verificar si se obtuvo la conexión correctamente
            if ($mysqli != null) {
                // Consultar los registros de la tabla
                $sql = "SELECT * FROM $table_name";
                $result = $mysqli->query($sql);

                // Verificar si se obtuvieron resultados
                if ($result) {
                    // Iterar sobre los resultados y agregarlos al array de registros
                    while ($row = $result->fetch_assoc()) {
                        // Iterar sobre cada columna de la fila
                        foreach ($row as $column => $value) {
                            // Verificar si la columna contiene datos binarios
                            if (isBinary($value)) {
                                // Generar un nombre de archivo único
                                $filename = 'archivo_' . uniqid();
                                // Crear una instancia de finfo
                                $finfo = new finfo(FILEINFO_MIME_TYPE);
                                // Obtener el tipo MIME del contenido binario
                                $file_type = $finfo->buffer($value);
                                // Añadir la extensión de archivo correspondiente al tipo MIME
                                $extension = mimeToExtension($file_type);
                                $filename .= '.' . $extension;
                                // Guardar el contenido binario en un archivo en una ruta específica
                                $file_path = '../../../uploads/' . $filename;
                                file_put_contents($file_path, $value);
                                // Reemplazar el valor en la fila con el nombre del archivo
                                $row[$column] = $filename;
                            }
                        }
                        $records[] = $row;
                    }
                }

                // Cerrar la conexión
                $mysqli->close();
            }

            // Crear un array con las columnas y los registros
            $tableData = [
                'columns' => $columns,
                'records' => $records
            ];

            return $tableData;
        } else {
            // La tabla especificada no existe, devolver null
            return null;
        }
    } else {
        // Error al cargar los parámetros de la tabla, devolver null
        return null;
    }
}
/**
 * Función para convertir el tipo MIME en una extensión de archivo.
 * 
 * @param mixed $mime_type Nombre de la extensión o false si da error.
 * 
 * @return string La extensión si está contemplada o sino devuelve .bin.
 */
function mimeToExtension($mime_type) {
    // Definir algunas asociaciones comunes de tipo MIME a extensiones de archivo
    $mime_map = array(
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'application/pdf' => 'pdf',
    );
    // Buscar la extensión correspondiente al tipo MIME
    return isset($mime_map[$mime_type]) ? $mime_map[$mime_type] : 'bin';
}

/**
 * Función para verificar si una cadena contiene datos binarios.
 *  
 * @param string $value Valor del registro.
 * 
 * @return bool Devuelve true si es binario, si no false.
 * 
 */ 
function isBinary($value)
{
    // Si el valor no es una cadena, no es binario
    if (!is_string($value)) {
        return false;
    }

    // Verificar cada carácter de la cadena
    for ($i = 0; $i < strlen($value); $i++) {
        $char = ord($value[$i]);
        // Si el carácter no es imprimible y no es un control de espacio, es binario
        if ($char < 32 || $char > 126) {
            return true;
        }
    }

    // Todos los caracteres son imprimibles, no es binario
    return false;
}

/**
 * Función que devuelve para el front en la inserción de datos la tabla con la información de columnas y la referencia de las fk.
 * 
 * @param string $db_name Contiene el nombre de la base de datos.
 * @param string $table_name Contiene el nombre de la tabla.
 * 
 * @return mixed Devuelve el array con los datos si hay un fallo devuelve null.
 */
function loadTableInsert($db_name, $table_name)
{
    // Obtiene la conexión para la base de datos que recibe como parámetro
    $mysqli = verify_login::createConnectDBS($db_name);

    // Verifica si se ha cargado la conexión a la base de datos
    if ($mysqli == null) {
        return $mysqli;
    }

    // Consultar las tablas en la base de datos

    // Obtener el nombre de las columnas de las tablas
    $sql_columns = "SHOW COLUMNS FROM $table_name";
    $result_columns = $mysqli->query($sql_columns);

    // Contar el número de columnas
    $num_columns = $result_columns->num_rows;

    // Verificar si se obtuvieron resultados
    if ($num_columns > 0) {
        // Almacenar el número de columnas y el nombre de la tabla
        $table_parameters[$table_name]['num_columns'] = $num_columns;

        // Iterar sobre cada columna y obtener sus detalles
        while ($row_columns = $result_columns->fetch_assoc()) {
            $column_name = $row_columns["Field"];
            $column_type = $row_columns["Type"];
            $column_length = '';
            // Obtiene la longitud de la columna si el tipo dispone de la misma mediante expresión regular
            if (preg_match('/\((\d+(?:,\d+)?)\)/', $column_type, $matches)) {
                $column_length = $matches[1];
            }
            $is_pk = $row_columns["Key"] === "PRI" ? true : false; // Verificar si es clave primaria (PK)
            $is_fk = $row_columns["Key"] === "MUL" ? true : false; // Verificar si es clave foránea (FK)
            $is_auto_increment = $row_columns["Extra"] === "auto_increment" ? true : false; // Verificar si es auto_incremental
            $is_nullable = $row_columns["Null"] === "YES" ? true : false;
            // Almacenar los detalles de la columna en un array
            $column_details = array(
                'type' => $column_type,
                'is_pk' => $is_pk,
                'is_fk' => $is_fk,
                'is_auto_increment' => $is_auto_increment,
                'is_nullable' => $is_nullable
            );

            if ($is_fk) {
                // Consultar la definición de la tabla para obtener la restricción de clave foránea
                $sql_foreign_key = "SHOW CREATE TABLE $table_name";
                $result_foreign_key = $mysqli->query($sql_foreign_key);
                $row_foreign_key = $result_foreign_key->fetch_assoc();
                // Analizar la definición de la tabla para obtener la restricción de la clave foránea
                $table_definition = $row_foreign_key['Create Table'];
                if (preg_match("/FOREIGN KEY \(`$column_name`\) REFERENCES `([^`]+)`(?:\.?`([^`]+)`)? \(`([^`]+)`\)/", $table_definition, $matches)) {
                    if (isset($matches[2]) && !empty($matches[2])) {
                        // Clave foránea apunta a otra base de datos
                        $referenced_db = $matches[1];
                        $referenced_table = $matches[2];
                        $referenced_column = $matches[3];
                    } else {
                        // Clave foránea en la misma base de datos
                        $referenced_table = $matches[1];
                        $referenced_column = $matches[3];
                        $referenced_db = $db_name;
                    }

                    // Agregar los detalles de la columna al array de parámetros de la tabla
                    $column_details['referenced_table'] = $referenced_table;
                    $column_details['referenced_column'] = $referenced_column;
                    $column_details['referenced_database'] = $referenced_db;
                }

                // Agregar los detalles de la columna al array de parámetros de la tabla
                $table_parameters[$column_name] = $column_details;
            } else {
                // Agregar los detalles de la columna al array de parámetros de la tabla
                $table_parameters[$column_name] = $column_details;
            }
        }

        // Cerrar conexión
        $mysqli->close();

        // Devolver los parámetros de las tablas
        return $table_parameters;
    } else {
        // Si no hay resultados, cerrar conexión y devolver null
        $mysqli->close();
        return null;
    }
}
