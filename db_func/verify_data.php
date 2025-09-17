<?PHP

/**
 * Fichero que contiene las funciones para verificar datos que se reciben del front.
 * 
 * @version 0.7.
 * 
 * @author Group βackend.
 * 
 * @copyright 2023 - 2024 Soft Genius ©.
 */

/**
 * Método que verifica que el texto no contenga "'", '"', ";", "--", "#", "=", "?", "%", "(", ")", "-", "+", "@", ",".
 * 
 * @param string $text Valor introducido por el usuario.
 * 
 * @return bool Devuelve true sino contiene dichos valores sino false.
 */
function isCheckSecurity($text)
{
    $forbidden_characters = array("'", '"', ";", "--", "#", "=", "?", "%", "(", ")", "-", "+", "@", ",", "*", "/", "|");
    foreach ($forbidden_characters as $character) {
        if (strpos($text, $character) !== false) {
            // Si la variable contiene uno de los caracteres prohibidos
            return false;
        }
    }
    // Si la variable no contiene ninguno de los caracteres prohibidos
    return true;
}

/**
 * Función que verifica si el nombre de la columna solo contiene letras y máximo un '_' bajo.
 * 
 * @param string $column_name Nombre de la columna.
 * 
 * @return bool Devuelve true si cumple con los requisitos sino false.
 */
 function isCheckColumnName($column_name)
{
    return preg_match('/^[a-zA-Z]+(_[a-zA-Z]+)*$/', $column_name);
}

/**
 * Función que verifica si el tipo de dato es válido.
 * 
 * @param string $data_type Variable que contiene el tipo de dato de la columna.
 * 
 * @return bool Devuelve true si es un tipo de dato de MySQL sino false. 
 */
function isCheckType($data_type)
{
    // Lista de tipos de datos permitidos
    $allowed_data_types = array(
        'INT', 'TINYINT', 'BOOL', 'SMALLINT', 'MEDIUMINT', 'BIGINT',
        'FLOAT', 'DOUBLE', 'DECIMAL', 'BIT', 'VARCHAR', 'CHAR', 'TEXT',
        'DATE', 'TIME', 'DATETIME', 'TIMESTAMP', 'YEAR', 'ENUM', 'SET',
        'BINARY', 'VARBINARY', 'TINYBLOB', 'TINYTEXT', 'BLOB', 'MEDIUMBLOB',
        'MEDIUMTEXT', 'LONGBLOB', 'LONGTEXT'
    );

    // Verifica si el tipo de dato está en la lista de tipos permitidos
    return in_array(strtoupper($data_type), $allowed_data_types);
}

/**
 * Función que verifica si si los datos introducidos con tienen texto letras mayúsculas, minúsculas y con acentos.
 * 
 * @param string $text Contiene el texto a comprobar.
 * 
 * @return bool Devuelve true si contiene carácteres válidos sino false.
 */
function isCheckText($text)
{
    // Expresión regular para buscar caracteres no permitidos
    $pattern = "/^[a-zA-ZÁÉÍÓÚáéíóúÜü_]+$/";

    // Verificar si el texto contiene caracteres no permitidos
    return preg_match($pattern, $text) === 1;
}

/**
 * Función que verifica si el DNI con la letra es válido.
 * 
 * @param $dni Contiene el DNI introducido por el usuario.
 * 
 * @return bool|string Devuelve true si es correcto, sino devuelve un mensaje indicando la letra correcta.
 */
function isCheckDNI($dni)
{
    $letters = array("T", "R", "W", "A", "G", "M", "Y", "F", "P", "D", 
    "X", "B", "N", "J", "Z", "S", "Q", "V", "H", "L", "C", "K", "E");

    $letter_DNI = strtoupper(substr($dni, 8));
    $num_DNI = substr($dni, 0, 8);

    if (is_numeric($num_DNI)) {
        $rest = $num_DNI % 23;
    } else {
        return false;
    }
    
    if ($letter_DNI == $letters[$rest]) {
        return true;
    } else {
        $correct_letter = $letters[$rest];
        return "La letra correcta es $correct_letter";
    }
}

/**
 * Función que devuelve si el string de dirección es válido comprueba que solo contenga letras mayúsculas, minúsculas, con acentos, números, espacios, 'º', '-' y '.'.
 * 
 * @param string $address Contiene los datos de dirección introducidos por el usuario.
 * 
 * @return bool Devuelve true si es correcto sino false.
 */
function isCheckValue($address)
{
    $pattern = "/^[a-zA-ZÁÉÍÓÚáéíóúÜü0-9 º\-.]+$/u";

    return preg_match($pattern, $address) === 1;
}

/**
 * Función que verifica si el movil es númerico e igual a longitud 9.
 * 
 * @param int $cell_phone Movil introducido por el usuario.
 * 
 * @return bool Devuelve true si es correcto el movil sino devuelve false.
 */
function isCheckCellular($cell_phone)
{   
    return (is_numeric($cell_phone) && strlen($cell_phone) == 9) === 1;
}

/**
 * Función que devuelve si el correo es válido en construcción y en Mail Exchange.
 * 
 * @param $mail Correo introducido por el usuario.
 * 
 * @return bool Devuelve true si es correcto y devuelve conexión MX sino false.
 */
function isCheckMail($mail)
{
    // Primero eliminamos cualquier carácter que no sea correcto
    $mail = filter_var($mail, FILTER_SANITIZE_EMAIL);

    // Verifica si el formato del correo es válido
    if (!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // Verifica si el dominio del correo tiene registros MX (Mail Exchange)
    list(, $domain) = explode('@', $mail);
    if (!checkdnsrr($domain, 'MX')) {
        return false;
    }

    return true;
}
?>