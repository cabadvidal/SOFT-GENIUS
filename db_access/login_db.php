<?PHP
/**
 * Fichero que recibiría el POST mediante el login del front y comprueba los credenciales con llamadas a los 
 * diferentes métodos y funciones.
 * 
 * @version 0.7.
 * 
 * @author Group βackend.
 * 
 * @copyright 2023 - 2024 Soft Genius ©.
 */


// Fichero que almacena el login del usuario mediante la función php session_*
session_start();
require_once __DIR__ . "/../db_func/verify_data.php";
require_once __DIR__ . "/verify_login.php";

// Verifica si se enviaron los datos del formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener los valores del formulario
    $user = "";
    $pass = "";
    // Comprobamos los valores recibidos por el formulario
    if (isCheckSecurity($_POST['usuario']) && isCheckSecurity($_POST['clave'])) {
        $user = isset($_POST['usuario']) ? $_POST['usuario'] : null;
        $pass = isset($_POST['clave']) ? $_POST['clave'] : null;
        $user = filter_input(INPUT_POST, 'usuario', FILTER_SANITIZE_STRING);
    } else {
        header("location: ../../php/login.php?error=user&pass2");
        exit();
    }

    // Llamar a la función verificarCredenciales con los valores del formulario
    if (verify_login::checkCredentials($user, $pass)) {

        verify_login::checkPrivileges();
        // Indicamos que se ha realizado correctamente el login al javascript
        // PENDIENTE DEL FRONT

    } else {
        verify_login::redirectFailedConnect();
    }
} else {
    echo "Acceso no autorizado.";
    header("refresh:2;url=ruta_relativa/archivo_login_front");
    exit();
}
?>