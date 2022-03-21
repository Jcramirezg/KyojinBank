<?php
require 'fpdf/fpdf.php';
require_once './vendor/autoload.php';
use thiagoalessio\TesseractOCR\TesseractOCR;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include './config/config.php';

//Detectatemos si el usuario dio click en el boton guardar
if (isset($_POST['register'])) {
    $tipo = $_POST['tipo']; //Tipo de documento (1-Factura,2-Cuentas de Cobro,3-Cheque)
    $archivo = $_FILES['archivos']; //Obtenenemos la imagen seleccionada y sus atributos
    $usuario = $_POST['usuario']; //Obtenemos el id del usuario
    $archivoRuta = $archivo['name'] . '.pdf'; //Ruta pdf anidada al nombre del archivo

    //Validamos si hay una imagen seleccionada
    if (!isset($archivo)) {
        exit('No hay imagen'); //Genera error si no muestra la imagen
    } else {
        $carpeta = ''; //Variable para guardar la carpeta a donde va a guardar el archivo segun el tipo

        //Validamos los tipos de archivos y asignamos el valor a la variable carpeta
        if ($tipo === '1') {
            $carpeta = 'Facturas/';
        }

        if ($tipo === '2') {
            $carpeta = 'Cuentas/';
        }

        if ($tipo === '3') {
            $carpeta = 'Cheques/';
        }

        $archivoRuta = $archivo['name'] . 'pdf';

        // Ruta absoluta o relativa a la imagen a reconocer
        $filepath = $archivo['tmp_name'];

        // Cree una instancia de tesseract con la ruta de archivo como primer parámetro
        $tesseractInstance = new TesseractOCR($filepath);

        // Ejecute tesseract para reconocer texto
        $result = $tesseractInstance->lang('eng', 'jpn', 'spa')->run();

        // Instantiate and use the FPDF class
        $pdf = new FPDF();

        //Add a new page
        $pdf->AddPage();

        // Set the font for the text
        $pdf->SetFont('Arial', 'B', 18);

        // Prints a cell with given text
        $pdf->MultiCell(300, 7, $result, 1);

        // guardar carpeta seleccionada de acuerdo al tipo
        $pdf->Output($carpeta . '' . $archivo['name'] . 'pdf', 'F');

        //Al guardar la imagen, guardamos los datos en la tabla archivos de la base de datos
        $query = $connection->prepare(
            'INSERT INTO archivos(tipo_archivo,nombre_archivo,usuario) VALUES (:tipo,:archivonombre,:usuario)'
        );
        $query->bindParam('tipo', $tipo, PDO::PARAM_INT);
        $query->bindParam('archivonombre', $archivoRuta, PDO::PARAM_STR);
        $query->bindParam('usuario', $usuario, PDO::PARAM_INT);
        $result = $query->execute();

        if ($result) {
            echo '<center><p class="success">Se ha guardado exitosamente!</p></center>';
        } else {
            echo '<center><p class="error">Algo malo pasó</p></center>';
        }
    }
}
