<?php

$emisor = array(
    'tipodoc'               => '6',
    'nrodoc'                =>  '20123456789',
    'razon_social'          =>  'CETI ORG',
    'nombre_comercial'      =>  'CETI',
    'direccion'             =>  'VIRTUAL',
    'ubigeo'                =>  '130101',
    'departamento'          =>  'LAMBAYEQUE',
    'provincia'             =>  'CHICLAYO',
    'distrito'              =>  'CHICLAYO',
    'pais'                  =>  'PE',
    'usuario_secundario'    =>  'MODDATOS',
    'clave_usuario_secundario'  =>  'MODDATOS'
);

$cabecera = array(
    'tipodoc'               =>  'RC', //RC:resumen de comprobantes, RA: resumen de anulaciones
    'serie'                 =>  date('Ymd'),
    'correlativo'           =>  1,
    'fecha_emision'         =>  date('Y-m-d'),
    'fecha_envio'           =>  date('Y-m-d')
);

$detalle = array();

$cant_comp = 10;

for ($i=1; $i <= $cant_comp ; $i++) { 
    $item_total = rand(50, 900);
    $item_valor = (float) number_format($item_total/1.18,2, '.', 1);
    $item_igv = $item_total - $item_valor;

    $detalle[] = array(
        'item'              =>  $i,
        'tipodoc'           =>  '03', //BV, NC, ND
        'serie'             =>  'B001',
        'correlativo'       =>  $i,
        'tipodoci'          =>  '1',
        'numdoci'           =>  rand(10000000, 99999999),
        'condicion'         =>  rand(1, 3), //1:alta, 2: modificaion, 3:baja o anulacion
        'moneda'            =>  'PEN',
        'importe_total'     =>  $item_total,
        'valor_total'       =>  $item_valor,
        'igv_total'         =>  $item_igv,
        'tipo_total'        =>  '01',
        'codigo_afectacion' =>  '1000',
        'nombre_afectacion' =>  'IGV',
        'tipo_afectacion'   =>  'VAT'
    );
}

//CREAR XML DE RC
require_once('api_genera_xml.php');

$objXML = new api_genera_xml();
$nombreXML = $emisor['nrodoc'] . '-' . $cabecera['tipodoc'] . '-' . $cabecera['serie'] . '-' . $cabecera['correlativo'];
$rutaXML = 'xml/';

$objXML->CrearXMLResumenDocumentos($emisor, $cabecera, $detalle, $rutaXML . $nombreXML);
echo '</br> XML DE RESUMEN DE COMPROBANTES CREADO';

require_once('api_facturacion.php');
$apiFac = new api_facturacion();

$estado_facturacion = $apiFac->enviar_resumen($emisor, $nombreXML);

echo '</br> Nro Ticket: ' . $estado_facturacion['ticket'];

if ($estado_facturacion['ticket'] > 0) {
    $estado_facturacion = $apiFac->consultar_ticket($emisor, $cabecera, $estado_facturacion['ticket']);
}

echo '</br> Estado Facturación: ' . $estado_facturacion['estado'];
echo '</br> Mensaje Facturación: ' . $estado_facturacion['estado_mensaje'];
echo '</br> descripcion: ' . $estado_facturacion['descripcion'];
echo '</br> nota: ' . $estado_facturacion['nota'];
echo '</br> codigo_error: ' . $estado_facturacion['codigo_error'];
echo '</br> mensaje_error: ' . $estado_facturacion['mensaje_error'];
echo '</br> http_code: ' . $estado_facturacion['http_code'];
echo '</br> output: ' . $estado_facturacion['output'];

?>