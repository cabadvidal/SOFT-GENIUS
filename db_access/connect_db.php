<?PHP
require_once __DIR__ . "/../config/config.php";
/**
 * Clase que gestiona la conexión msqli y devuelve la conexión correspondiente
 * con respecto al parámetro de base de datos que se le pasa.
 * 
 * @version 0.7.
 * 
 * @author Group βackend.
 * 
 * @copyright 2023 - 2024 Soft Genius ©.
 */
class connectDB
{
    private static $instance;
    private $connectDBS;
    private $connectDBA;
    private $connectDBF;

    private $db_name;

    /**
     * Constructor de clase singleton para realizar los diferentes tipos de conexión a MySQL
     * como admin para obtener las bases de datos, para obtener valores de una base de datos 
     * concreta pasando el valor de la misma y la conexión por defecto a la base de datos 
     * diseñada en el proyecto.
     * 
     * @access private
     * 
     * @param string $db_name Nombre de la base de datos(opcional).
     */
    private function __construct($db_name = '')
    {
        try {
            // Conexión con la base de datos especificada
            $this->connectDBS = new mysqli(DB_HOST, DB_USER, DB_PASS, $db_name);

            // Establecer el conjunto de caracteres a UTF-8
            mysqli_set_charset($this->connectDBS, "utf8mb4");

            // Conexión sin base de datos
            $this->connectDBA = new mysqli(DB_HOST, DB_USER, DB_PASS);

            // Establecer el conjunto de caracteres a UTF-8
            mysqli_set_charset($this->connectDBA, "utf8mb4");

            // Conexión para obtener nombres de bases de datos
            $this->connectDBF = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

            // Establecer el conjunto de caracteres a UTF-8
            mysqli_set_charset($this->connectDBF, "utf8mb4");

            // Manejar errores de conexión
            if ($this->connectDBS->connect_error || $this->connectDBA->connect_error || $this->connectDBF->connect_error) {
                die('Error de conexión a la base de datos: ' . $this->connectDBS->connect_error . ' ' . $this->connectDBA->connect_error . ' ' . $this->connectDBF->connect_error);
            }
        } catch (Exception $e) {
            // Pendiente devolver error por AJAX o GET
            die("Error: Fallo a instanciar conexión en el constructor del archivo connectDB.php");
        }
    }

    /**
     * Función que obtiene la instancia de la clase puede o no recibir un parametro.
     * 
     * @access public
     * @static
     * 
     * @param string $db_name Contiene el nombre de la base de datos(opcional).
     * @return self Devuelve la instancia de la conexión.
     */
    public static function getInstance($db_name = '')
    {
        // Si la instance aún no ha sido creada o si la base de datos ha cambiado, crear una nueva instance
        if (!isset(self::$instance) || ($db_name !== '' && self::$instance->db_name !== $db_name)) {
            self::$instance = new self($db_name);
        }

        // Devolver la instance única
        return self::$instance;
    }

    /**
     * Función que establece la base de datos para la conexión.
     * Pendiente verificar su uso.
     * 
     * @access public
     * 
     * @param string $db_name Nombre de la base de datos.
     */
    public function setdb_name($db_name)
    {
        if (empty($this->connectDBS)) {
            $this->connectDBS = new mysqli(DB_HOST, DB_USER, DB_PASS, $db_name);
            $this->db_name = $db_name;
        }
    }

    /**
     * Función que devuelve el nombre de la base de datos actualmente seleccionada.
     * Pendiente verificar su uso.
     * 
     * @access public
     * 
     * @return string Devuelve la base de datos instanciada en la conexión.
     */
    public function getDBName()
    {
        return $this->db_name;
    }

    /**
     * Función que devuelve la conexión segura.
     * 
     * @access public
     * 
     * @return mysqli Devuelve la conexión segura.
     */
    public function getConnectDBS()
    {
        return $this->connectDBS;
    }

    /**
     * Función que devuelve la conexión para admin para consultar bases de datos.
     * 
     * @access public
     * 
     * @return mysqli Devuelve la conexión segura para administrar bases de datos.
     */
    public function getConnectDBA()
    {
        return $this->connectDBA;
    }

    /**
     * Función que devuelve la conexión por defecto.
     * 
     * @access public
     * 
     * @return mysqli Devuelve la conexión por defecto para administrar bases de datos.
     */
    public function getConnectDBF()
    {
        return $this->connectDBF;
    }
}



