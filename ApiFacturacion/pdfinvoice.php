<?php

//importar las libreria
define('FPDF_FONTPATH', 'font');

require_once('fpdf/fpdf.php');
require_once('phpqrcode/qrlib.php');

//datos
require_once('ado/clsCliente.php');
require_once('ado/clsCompartido.php');
require_once('ado/clsEmisor.php');
require_once('ado/clsVenta.php');
require_once('cantidad_en_letras.php');

//objetos
$objCliente = new clsCliente();
$objCompartido = new clsCompartido();
$objVenta = new clsVenta();
$objEmisor = new clsEmisor();


//obtener el id de la venta
$id = $_GET['id'];

$venta = $objVenta->obtenerComprobanteId($id);
$venta = $venta->fetch(PDO::FETCH_NAMED);

$detalle = $objVenta->listarDetalleComprobanteId($id);
$detalle = $detalle->fetchAll(PDO::FETCH_NAMED);

$emisor = $objEmisor->obtenerEmisor($venta['idemisor']);
$emisor = $emisor->fetch(PDO::FETCH_NAMED);

$tipo_comprobante = $objCompartido->obtenerComprobante($venta['tipocomp']);
$tipo_comprobante = $tipo_comprobante->fetch(PDO::FETCH_NAMED);

$cliente = $objCliente->consultarClientePorCodigo($venta['codcliente']);
$cliente = $cliente->fetch(PDO::FETCH_NAMED);

//crear el pdf

$pdf = new FPDF();
$pdf->AddPage('P', 'A4');

//insetamos imagenes
$pdf->Image('logo_empresa.jpg', 50, 2, 25, 25);

//Mostrar datos

$pdf->Ln(20);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(100, 6, $emisor['nrodoc'] . '-' . $emisor['razon_social']);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(80, 6, $emisor['nrodoc'], 'LRT', 1, 'C', 0);

$pdf->SetFont('Arial', '', 8);
$pdf->Cell(100, 6, $emisor['direccion']);

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(80, 6, $tipo_comprobante['descripcion'] . ' ELECTRONICA', 'LR', 1, 'C', 0);
$pdf->Cell(100);
$pdf->Cell(80, 6, $venta['serie'] . '-' . $venta['correlativo'], 'BLR', 0, 'C', 0);
$pdf->Ln();

//datos del cliente
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(30, 6, 'DNI/RUC: ', 0, 0, 'L', 0);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 6, $cliente['nrodoc'], 0, 1, 'L', 0);

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(30, 6, 'CLIENTE: ', 0, 0, 'L', 0);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 6, $cliente['razon_social'], 0, 1, 'L', 0);

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(30, 6, 'DIRECCION: ', 0, 0, 'L', 0);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 6, $cliente['direccion'], 0, 1, 'L', 0);

$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(30, 6, 'FECHA EMISION: ', 0, 0, 'L', 0);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(30, 6, $venta['fecha_emision'], 0, 1, 'L', 0);


//detalle de los productos
$pdf->SetFont('Arial', 'B', 8);
$pdf->Cell(20, 6, 'ITEM', 1, 0, 'C', 0);
$pdf->Cell(20, 6, 'CANTIDAD', 1, 0, 'C', 0);
$pdf->Cell(100, 6, 'PRODUCTO', 1, 0, 'C', 0);
$pdf->Cell(30, 6, 'VALOR UNITARIO', 1, 0, 'C', 0);
$pdf->Cell(25, 6, 'SUB TOTAL', 1, 1, 'C', 0);

$pdf->SetFont('Arial', '', 8);
foreach ($detalle as $key => $value) {
    $pdf->Cell(20, 6, $value['item'], 1, 0, 'C', 0);
    $pdf->Cell(20, 6, $value['cantidad'], 1, 0, 'R', 0);
    $pdf->Cell(100, 6, utf8_decode($value['nombre']), 1, 0, 'L', 0);
    $pdf->Cell(30, 6, $value['valor_unitario'], 1, 0, 'R', 0);
    $pdf->Cell(25, 6, $value['valor_total'], 1, 1, 'R', 0);
}

//totales
$pdf->Cell(170, 6, 'OP. GRAVADAS', '', 0, 'R', 0);
$pdf->Cell(25, 6, $venta['op_gravadas'], 1, 1, 'R', 0);

$pdf->Cell(170, 6, 'OP. EXONERADAS', '', 0, 'R', 0);
$pdf->Cell(25, 6, $venta['op_exoneradas'], 1, 1, 'R', 0);

$pdf->Cell(170, 6, 'OP. INAFECTAS', '', 0, 'R', 0);
$pdf->Cell(25, 6, $venta['op_inafectas'], 1, 1, 'R', 0);

$pdf->Cell(170, 6, 'IGV', '', 0, 'R', 0);
$pdf->Cell(25, 6, $venta['igv'], 1, 1, 'R', 0);

$pdf->Cell(170, 6, 'TOTAL', '', 0, 'R', 0);
$pdf->Cell(25, 6, $venta['total'], 1, 1, 'R', 0);

//total en letras
$pdf->Ln(10);
$pdf->Cell(170, 6, utf8_decode('SON: ' . CantidadEnLetra($venta['total'])), 0, 0, 'C', 0);
$pdf->Ln(10);

//codigo QR
// RUC|TIPOCOMP|SERIE|CORRELATIVO|IGV|TOTAL|FECHA|TIPO DOC|NUM DOC|
$ruc = $emisor['nrodoc'];
$tipo = $venta['tipocomp'];
$serie = $venta['serie'];
$correlativo = $venta['correlativo'];
$igv = $venta['igv'];
$total = $venta['total'];
$fecha = $venta['fecha_emision'];
$tipcl = $cliente['tipodoc'];
$nrocl = $cliente['nrodoc'];

$textoqr = $ruc . '|' . $tipo  . '|' . $serie  . '|' .  $correlativo . '|' . $igv .'|' . $total . '|' . $tipcl . '|' . $nrocl . '|';

$nombre_qr = $ruc . '-' . $tipo . '-'  . $serie . '-' . $correlativo;
$ruta_qr = $nombre_qr . '.PNG';

QRcode::png($textoqr, $ruta_qr, 'Q', 15, 0);

$pdf->Image($ruta_qr, 80, $pdf->GetY(), 25, 25);

$pdf->Ln(30);
$pdf->Cell(170, 6, utf8_decode('REPRESENTACIÓN IMPRESA DEL COMPROBANTE ELECTRÓNICO'), 0, 0, 'C', 0);
$pdf->Ln(10);
$pdf->Cell(170, 6, utf8_decode('Este comprobante puede ser valdiad en ceti.org o sunat.org.pe'), 0, 0, 'C', 0);


//salida del pdf
$pdf->Output('I', 'invoice.pdf');
?>