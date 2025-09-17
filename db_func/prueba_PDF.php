<?PHP
require_once('../lib/vendor/tecnickcom/tcpdf/tcpdf.php');
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
?>

<!-- COMO SERÍA LA LLAMADA DESDE JAVASCRIPT PARA VER LA LISTA DE FICHEROS PDF -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var listaArchivos = document.getElementById('lista-archivos');

        // Ruta de la carpeta que contiene los archivos PDF
        var carpeta = 'ruta/a/la/carpeta/';

        // Realizar una solicitud AJAX para obtener la lista de archivos PDF
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'listar_archivos.php?carpeta=' + encodeURIComponent(carpeta), true);
        xhr.onload = function() {
            if (xhr.status == 200) {
                var archivos = JSON.parse(xhr.responseText);

                // Mostrar la lista de archivos PDF como enlaces
                archivos.forEach(function(archivo) {
                    var enlace = document.createElement('a');
                    enlace.href = 'descargar.php?archivo=' + encodeURIComponent(archivo);
                    enlace.textContent = archivo;
                    enlace.target = '_blank'; // Abrir en una nueva pestaña
                    enlace.style.display = 'block'; // Mostrar enlaces en líneas separadas
                    listaArchivos.appendChild(enlace);
                });
            } else {
                console.error('Error al obtener la lista de archivos: ' + xhr.status);
            }
        };
        xhr.send();
    });
</script>