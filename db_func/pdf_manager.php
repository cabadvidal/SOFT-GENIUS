<?PHP
require_once __DIR__ .('/../lib/vendor/tecnickcom/tcpdf/tcpdf.php');
require_once __DIR__ .('/../db_access/verify_login.php');

/**
 * Genera un informe en formato PDF utilizando TCPDF y guarda el contenido del PDF en la base de datos.
 *
 * @param string $dni El DNI del empleado.
 * @param string $title El título del informe.
 * @param string $subject El asunto del informe.
 * @param string $file_name El nombre del archivo PDF a generar.
 * @param string $template La plantilla HTML para generar el contenido del informe.
 *
 * @return string|void Devuelve un mensaje de error si no se encuentra el DNI, o nada si el informe se genera correctamente.
 */
function generateReport($dni, $title, $subject, $file_name, $template)
{
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Configurar información del documento
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('SoftGenius Corporation S.A.');
    $pdf->SetTitle($title);
    $pdf->SetSubject($subject);

    // Configurar márgenes
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Añadir una página
    $pdf->AddPage();

    // Establecer la fuente
    $pdf->SetFont('helvetica', '', 12);

    // Conexión a la bbdd
	$db_name = "bbdd_empleados_softgenius";
    $mysqli = verify_login::createConnectDBS($db_name);

    // Consulta SQL para obtener el EmpleadoID a partir del DNI
    $sql_dni = "SELECT EmpleadoID FROM empleado WHERE DNI = ?";
    $stmt_dni = $mysqli->prepare($sql_dni);
    $stmt_dni->bind_param('s', $dni);
    $stmt_dni->execute();
    $result_dni = $stmt_dni->get_result();

    // Verificar si se encontró el DNI
    if ($result_dni->num_rows === 0) {
        return "No existe ningún empleado con el DNI proporcionado.";
    }

    // Guardar el empleadoID en una variable global para utilizarlo en la plantilla
    $empleado = $result_dni->fetch_assoc();
    global $empleadoID;
    $empleadoID = $empleado['EmpleadoID'];

    if ($title == 'Informe de Vacaciones') {
        $html = include __DIR__ . '/../../html/empleados/plantillasInformes/'.$template;
        $tipoID = 1;
    }

    if ($title == 'Informe Salario Actual') {
        $html = include __DIR__ . '/../../html/empleados/plantillasInformes/'.$template;
        $tipoID = 2;
    }

    if ($title == 'Informe de Formaciones') {
        $html = include __DIR__ . '/../../html/empleados/plantillasInformes/'.$template;
        $tipoID = 3;
    }

    $pdf->writeHTML($html, true, false, true, false, '');

    $pdfContent = $pdf->Output($file_name.'.pdf', 'S');
    savePDFbbdd($pdfContent, $tipoID, $file_name, $empleadoID);

    return "Informe guardado en la base de datos correctamente";
}
/*
$dni = '33334444S';
$title = 'Informe de Vacaciones';
            $subject = 'Notificación de Vacaciones Pendientes';
            $template = 'vacaciones.php';
            $file_name = 'InformeVacaciones_'.date("d_m_Y");
generateReport($dni, $title, $subject, $file_name, $template);
*/
/**
 * Guarda un informe en formato PDF en la base de datos.
 *
 * @param string $pdfContent El contenido del PDF en formato binario.
 * @param int $tipoID El ID del tipo de informe.
 * @param string $file_name El nombre del archivo PDF.
 * @param int $empleadoID El ID del empleado asociado al informe.
 *
 * @return void
 */
function savePDFbbdd($pdfContent, $tipoID, $file_name, $empleadoID){
    //
    // Conexión a la bbdd_tramites_softgenius para guardar el PDF
    $db_name_tramites = "bbdd_tramites_softgenius";
    $mysqli_tramites = verify_login::createConnectDBS($db_name_tramites);

    // Consulta SQL para insertar el informe en la base de datos
    $sql_informe = "INSERT INTO informes (TipoID, Descripcion, Informe, EmpleadoID) VALUES (?, ?, ?, ?)";
    $stmt_informe = $mysqli_tramites->prepare($sql_informe);
    $descripcion = $file_name.'.pdf';
    $stmt_informe->bind_param('isss', $tipoID, $descripcion, $pdfContent, $empleadoID);
    $stmt_informe->execute();
}

/**
 * Descarga un informe en formato PDF desde la base de datos.
 *
 * @param int $informeID El ID del informe a descargar.
 *
 * @return string|void Devuelve un mensaje de error si no se encuentra el informe, 
 * o inicia la descarga del PDF si se encuentra.
 */
function downloadReport($informeID) {
    // Conexión a la bbdd_tramites_softgenius
    $db_name_tramites = "bbdd_tramites_softgenius";
    $mysqli_tramites = verify_login::createConnectDBS($db_name_tramites);

    // Consulta SQL para obtener el informe
    $sql_informe = "SELECT TipoID, Descripcion, Informe, EmpleadoID FROM informes WHERE InformeID = ?";
    $stmt_informe = $mysqli_tramites->prepare($sql_informe);
    $stmt_informe->bind_param('i', $informeID);
    $stmt_informe->execute();
    $result_informe = $stmt_informe->get_result();

    // Verificar si se encontró el informe
    if ($result_informe->num_rows === 0) {
        return "No existe ningún informe con el ID proporcionado.";
    }

    $informe = $result_informe->fetch_assoc();
    $pdfContent = $informe['Informe'];
    $descripcion = $informe['Descripcion'];

    // Enviar el PDF al navegador para su descarga
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $descripcion . '.pdf"');
    echo $pdfContent;
}

/**
 * Obtiene una lista de informes asociados a un empleado utilizando su DNI.
 *
 * @param string $dni El DNI del empleado.
 *
 * @return string|array Devuelve un mensaje de error si no se encuentra el DNI o si no existen informes,
 * o un array de informes si se encuentran.
 */
function getReportsByDNI($dni) {
    // Conexión a la bbdd_empleados_softgenius para obtener el EmpleadoID
    $db_name_empleados = "bbdd_empleados_softgenius";
    $mysqli_empleados = verify_login::createConnectDBS($db_name_empleados);

    // Consulta SQL para obtener el EmpleadoID a partir del DNI
    $sql_dni = "SELECT EmpleadoID FROM empleado WHERE DNI = ?";
    $stmt_dni = $mysqli_empleados->prepare($sql_dni);
    $stmt_dni->bind_param('s', $dni);
    $stmt_dni->execute();
    $result_dni = $stmt_dni->get_result();

    // Verificar si se encontró el DNI
    if ($result_dni->num_rows === 0) {
        return "No existe ningún empleado con el DNI proporcionado.";
    }

    $empleado = $result_dni->fetch_assoc();
    $empleadoID = $empleado['EmpleadoID'];

    // Conexión a la bbdd_tramites_softgenius para obtener los informes
    $db_name_tramites = "bbdd_tramites_softgenius";
    $mysqli_tramites = verify_login::createConnectDBS($db_name_tramites);

    // Consulta SQL para obtener los informes del empleado
    $sql_informes = "SELECT InformeID, TipoID, Descripcion, EmpleadoID FROM informes WHERE EmpleadoID = ?";
    $stmt_informes = $mysqli_tramites->prepare($sql_informes);
    $stmt_informes->bind_param('i', $empleadoID);
    $stmt_informes->execute();
    $result_informes = $stmt_informes->get_result();

    // Verificar si se encontraron informes
    if ($result_informes->num_rows === 0) {
        return "No existen informes para el empleado con el DNI proporcionado.";
    }

    $informes = [];
    while ($row = $result_informes->fetch_assoc()) {
        $informes[] = $row;
    }

    return $informes;
}


/********************* FUNCIONES DE PRUEBA SIN IMPLEMNTAR ********************/

/**
 *  Genera un pdf con la información de una BB.DD.
 *  
 *  @param string $db_name Nombre de la base de datos.
 * 
 *  @return mixed Devuelve un pdf con las tablas de una base de datos.
 */
function generatePDF($db_name)
{
    require_once('../lib/vendor/tecnickcom/tcpdf/tcpdf.php');
    // Crear nuevo objeto TCPDF
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

    // Establecer información del documento
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Your Name');
    $pdf->SetTitle('Database Tables');
    $pdf->SetSubject('Database Tables Information');
    $pdf->SetKeywords('TCPDF, PDF, database, tables');

    // Establecer márgenes
    $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
    $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
    $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

    // Establecer modo de fuente subconjunto
    $pdf->setFontSubsetting(true);

    // Agregar una página
    $pdf->AddPage();

    // Título del documento
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Database Tables Information', 0, 1, 'C');

    // Conexión a la base de datos
    require_once ('../db_access/verify_login.php');
    $mysqli = verify_login::createConnectDBS($db_name);

    // Verificar la conexión
    if ($mysqli == null) {
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'Error: No se pudo conectar a la base de datos.', 0, 1);
        return $pdf;
    }

    // Obtener las tablas de la base de datos
    $sql = "SHOW TABLES";
    $result = $mysqli->query($sql);

    // Verificar si hay tablas
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_row()) {
            $table_name = $row[0];

            // Escribir el nombre de la tabla
            $pdf->Ln();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'Table: ' . $table_name, 0, 1);

            // Obtener la estructura de la tabla
            $sql_columns = "SHOW COLUMNS FROM $table_name";
            $result_columns = $mysqli->query($sql_columns);

            // Verificar si hay columnas
            if ($result_columns->num_rows > 0) {
                $pdf->SetFont('helvetica', '', 12);
                while ($column = $result_columns->fetch_assoc()) {
                    $pdf->Cell(0, 10, 'Column: ' . $column['Field'] . ' | Type: ' . $column['Type'], 0, 1);
                }
            } else {
                $pdf->Cell(0, 10, 'No columns found for this table.', 0, 1);
            }
        }
    } else {
        $pdf->SetFont('helvetica', '', 12);
        $pdf->Cell(0, 10, 'No tables found in the database.', 0, 1);
    }

    // Cerrar la conexión
    $mysqli->close();

    return $pdf;
}
// PRUEBA DE generatePDF
//$pdf = generatePDF('casaempeno');

/**
 *  Función para guardar un fichero PDF
 */
function savePDF($pdf, $filename)
{
    // Obtener el contenido del PDF como una cadena
    $output = $pdf->Output('', 'S');

    // Guardar el contenido en un archivo
    file_put_contents($filename, $output);
}

function savePDFNavegador($pdf, $filename)
{
    // Obtener el contenido del PDF como una cadena
    $output = $pdf->Output('', 'S');

    // Establecer los encabezados para forzar la descarga del archivo
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // Enviar el contenido del PDF al navegador
    echo $output;
}

function obtenerFicherosPDFCarpeta(){
    // Ruta de la carpeta que contiene los archivos PDF
    $carpeta = 'ruta/a/la/carpeta/';

    // Obtener la lista de archivos PDF en la carpeta
    $archivos = glob($carpeta . '*.pdf');

    // Mostrar la lista de archivos PDF como enlaces
    foreach ($archivos as $archivo) {
        $nombre_archivo = basename($archivo);
        echo '<a href="descargar.php?archivo=' . urlencode($nombre_archivo) . '">' . $nombre_archivo . '</a><br>';
    }
}

function leerPDFNavegador(){
    // Verificar si se proporcionó un nombre de archivo válido en la consulta
    if (isset($_GET['archivo'])) {
        // Ruta completa del archivo PDF seleccionado
        $archivo = 'ruta/a/la/carpeta/' . $_GET['archivo'];

        // Verificar si el archivo existe
        if (file_exists($archivo)) {
            // Establecer los encabezados para forzar la descarga del archivo
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . basename($archivo) . '"');

            // Enviar el contenido del PDF al navegador
            readfile($archivo);
            exit;
        } else {
            // El archivo no existe
            echo 'El archivo seleccionado no existe.';
        }
    } else {
        // No se proporcionó un nombre de archivo válido en la consulta
        echo 'Nombre de archivo no especificado.';
    }
}

//savePDF($pdf, 'pdfletal.pdf');
//print_r($pdf);

/**
 *  Inserta un archivo PDF como BLOB en una BB.DD y lo recibe 
 *  y genera una copia como archivo PDF.
 *  
 *  @param string $db_name Nombre de la base de datos.
 */
function generarPDFPrueba($rutaPDF){
    

    // Guardar el PDF en la base de datos
    $contenido_pdf = file_get_contents($rutaPDF); // Obtener el contenido del PDF como una cadena de bytes

    $mysqli = new mysqli('localhost', 'root', '', 'pruebapdf');
    if ($mysqli->connect_error) {
        die('Error de conexión: ' . $mysqli->connect_error);
    }

    $sql = "INSERT INTO pruebapdf (PDF) VALUES (?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('b', $contenido_pdf); // 'b' indica que es un parámetro de tipo BLOB
    $stmt->send_long_data(0, $contenido_pdf); // Envía datos de longitud completa para columnas de tipo BLOB
    $stmt->execute();
    $stmt->close();
}
/**
 *  Recuperar el PDF de la base de datos y generar un nuevo fichero
 * 
 */
function guardarPDFPrueba($id, $pdf){
    $sql = "SELECT PDF FROM pruebapdf WHERE ID = ?";

    $mysqli = new mysqli('localhost', 'root', '', 'pruebapdf');
    if ($mysqli->connect_error) {
        die('Error de conexión: ' . $mysqli->connect_error);
    }

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($pdf);
    $stmt->fetch();
    //print($contenido_recuperado);
    file_put_contents('contenido_recuperado.pdf', $pdf);
    $mysqli->close();
}
// PRUEBA DE generarPDFPrueba()
//$pdf = generarPDFPrueba('C:\1.pdf');
//guardarPDFPrueba(1,$pdf);
