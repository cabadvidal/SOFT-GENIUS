<?php
/**
 * Fichero que recibiría la petición de cierre de sesión del front y elimina contenidos de _SESSION
 * y destruye la misma.
 * 
 * @version 0.7.
 * 
 * @author Group βackend.
 * 
 * @copyright 2023 - 2024 Soft Genius ©.
 */


    /**
     * Elimina y destruye los datos dentro de la función session_*
     */
    session_start();
    // Cerrar sesión y olvidar las credenciales de acceso
    $_SESSION = array(); // Vacía todas las variables de sesión
    session_destroy(); // Destruye la sesión
?>
<!-- 
    Script optativo para modificar icono de login dentro del html
    y redireccionar al usuario a la página principal
-->
<script>
    sessionStorage.removeItem("login");
    window.location.href = "../../index.html";
</script>