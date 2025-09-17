<?PHP
require_once __DIR__ . "/connect_db.php";
/**
 * Clase que gestiona la autenticación del usuario, guarda credenciales en SESSION y da acceso a las tres conexiones 
 * a la base de datos a través de la clase ConnectDB.
 * 
 * @version 0.7.
 * 
 * @author Group βackend.
 * 
 * @copyright 2023 - 2024 Soft Genius ©.
 */
class verify_login
{
    /**
     * Método para redirigir al apartado de inicio de sesión si hay un error de conexión.
     * @access private
     * @static
     * 
     * @return void Método que redirecciona en caso de fallo.
     */
    private static function redirectFailedConnect()
    {
        echo "Error de conexión. Serás redirigido a la página de inicio de sesión.";
        header("refresh:2;url=../../php/login.php?error=user&pass1");
        exit();
    }

    /**
     * Método que establece los datos que se almacenan en _SESSION.
     * 
     * @access private
     * @static
     * 
     * @param string $user Nombre del usuario verificado.
     * @param string $pass Contraseña encriptada del usuario.
     * @global int $privilege_lvl Nivel de creedencial del usuario en la base datos.
     * 
     * @return void Guarda los datos de sesión y establece las variables de sesión del usuario.
     */
    private static function saveSession($user, $pass)
    {
        global $privilege_lvl;

        // Iniciar la sesión si no está iniciada
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Almacenar los valores en la sesión
        $_SESSION['usuario'] = $user;
        $_SESSION['clave'] = $pass;
        $_SESSION['nivel_privilegios'] = $privilege_lvl;

        // Idea sobre como almacenar la  $db_name con la trabaja el usuario ejemplo 3 Admin
        if ($privilege_lvl === "3") {
            $_SESSION['db_name'] = "base_datos_por_defecto";
        }
    }

    /**
     * Función que verifica si las credenciales ingresadas por el usuario son correctas y almacena los privilegios en la variable $privilege_lvl.
     * 
     * @access public
     * @static
     * 
     * @param string $user Usuario para el inicio de sesión del usuario.
     * @param string $pass Contraseña para el inicio de sesión del usuario.
     * @global int $privilege_lvl Almacena el nivel de privilegios del usuario para gestionar sus accesos.
     * 
     * @return bool Devuelve true si las credenciales son correctas, en caso contrario false.
     */
    public static function checkCredentials($user, $pass)
    {
        global $privilege_lvl;

        // Variables de conexión a la base de datos
        $db_name = 'cuentas_softgenius';

        $mysqli = verify_login::createConnectDBS($db_name);

        // Verificar el nivel de privilegio del usuario pendiente de concretar forma de encriptación de la contraseña
        $stmt = $mysqli->prepare("SELECT nivel_privilegios FROM cuentas WHERE nombre = ? AND contrasenha = SHA2(?, 256)");
        $stmt->bind_param("ss", $user, $pass);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $privilege_lvl = $row['nivel_privilegios'];
            verify_login::saveSession($user, $pass);
            $stmt->close();
            $mysqli->close();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Método que determina la ruta del usuario en base a sus privilegios.
     * 
     * @access private
     * @static
     * 
     * @return void Verifica los credenciales y redirecciona.
     */
    private static function checkPrivileges()
    {
        global $privilege_lvl;

        switch ($privilege_lvl) {
            case '1':
                // Usuario
                header("Location: rutas/pendiente/de_especificar_por_front");
                break;
            case '2':
                // Empresario
                header("Location: rutas/pendiente/de_especificar_por_front");
                break;
            case '3':
                // Admin
                header("Location: rutas/pendiente/de_especificar_por_front");
                break;
            case '4':
                // Empleado
                header("Location: rutas/pendiente/de_especificar_por_front");
                break;
            default:
                # code...
                break;
        }
    }

    /**
     * Función para crear la conexión a la base de datos mediante usuario logueado para realización de consultas privadas.
     * Obtiene la instancia de conexión a MySQL de la clase connectDB.
     * Pendiente verificar datos de acceso según propuestas del departamento de BB.DD.
     * 
     * @access public
     * @static
     * @param string $db_name Contiene el nombre de la base de datos a la que conectar.
     * 
     * @return mysqli|null $mysqli Devuelve la conexion a la base de datos para acceder a bases datos concretas o se devuelve null.
     */
    public static function createConnectDBS($db_name)
    {
        
        // Obtener una instance única de la clase connectDB
        $instance = connectDB::getInstance($db_name);

        // Obtener la conexión MySQLi de la clase connectDB
        $mysqli = $instance->getConnectDBS();

        if ($mysqli->connect_error) {
            return null;
        } else {
            return $mysqli;
        }
    }

    /**
     * Función para crear la conexión a la base de datos mediante usuario por defecto para realización de consultas públicas.
     * Obtiene la instancia de conexión a MySQL de la clase connectDB.
     * Pendiente verificar datos de acceso según propuestas del departamento de BB.DD.
     * 
     * @access public
     * @static
     * 
     * @return mysqli|null $mysqli Devuelve la conexion a la base de datos para consultas públicas o se devuelve null.
     */
    public static function createConnectDBF()
    {
        // Obtener una instance única de la clase connectDB
        $instance = connectDB::getInstance();

        // Obtener la conexión MySQLi de la clase connectDB
        $mysqli = $instance->getConnectDBF();

        if ($mysqli->connect_error) {
            return null;
        } else {
            return $mysqli;
        }
    }


    /**
     * Función para crear la conexión a la base de datos mediante usuario administrador para crear bases de datos.
     * Obtiene la instancia de conexión a MySQL de la clase connectDB.
     * Pendiente verificar datos de acceso según propuestas del departamento de BB.DD.
     * 
     * @access public
     * @static
     * @return mysqli|null $mysqli Devuelve la conexion a la base de datos como admin o se devuelve null.
     */
    public static function createConnectDBA()
    {
        // Obtener una instance única de la clase connectDB
        $instance = connectDB::getInstance();

        // Obtener la conexión MySQLi de la clase connectDB
        $mysqli = $instance->getConnectDBA();

        if ($mysqli->connect_error) {
            return null;
        } else {
            return $mysqli;
        }
    }
}