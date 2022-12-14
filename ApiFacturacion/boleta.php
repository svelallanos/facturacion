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

$cliente = array(
    'tipodoc'               => '1',
    'nrodoc'                =>  '12345678',
    'razon_social'          =>  'MICKI MOUSE',
    'direccion'             =>  'PARQUE',
    'pais'                  =>  'PE'
);

$comprobante = array(
    'tipodoc'               =>  '03', //01:factura, 03:boleta
    'serie'                 =>  'BJKL',
    'correlativo'           =>  '11',
    'fecha_emision'         =>  date('Y-m-d'),
    'hora'                  =>  '00:00:00',
    'fecha_vencimiento'     =>  date('Y-m-d'),
    'moneda'                =>  'PEN',
    'total_opgravadas'      =>  0.00,
    'total_opexoneradas'    =>  0.00,
    'total_opinafectas'     =>  0.00,
    'total_impbolsas'       =>  0.00,
    'igv'                   =>  0.00,
    'total'                 =>  0.00,
    'total_texto'           =>  ''
);

$detalle = array(
    array(
        'item'              =>  1,
        'codigo'            =>  'PROD001',
        'descripcion'       =>  'IMPRESORA EPSON L666',
        'cantidad'          =>  2,
        'precio_unitario'   =>  800.00,//incluir impuestos IGV:18
        'valor_unitario'    =>  677.97,//sin impuestos IGV=0
        'igv'               =>  244.07,
        'tipo_precio'       =>  '01', //Catalogo
        'porcentaje_igv'    =>  18,
        'importe_total'     =>  1600.00,//cantidad * precio unitario
        'valor_total'       =>  1355.93,//cantidad * valor unitario
        'unidad'            =>  'NIU',
        'bolsa_plastica'        =>  'NO',

        //IMPORTANTE
        'tipo_afectacion_igv'   =>  '10', //Cat??logo No. 07: C??digos de tipo de afectaci??n del IGV: 10:GRA, 20:EXO, 30:INA
        'codigo_tipo_tributo'   =>  '1000', //Cat??logo No. 05: C??digos de tipos de tributos
        'tipo_tributo'          =>  'VAT', //Cat??logo No. 05: C??digos de tipos de tributos
        'nombre_tributo'        =>  'IGV', //Cat??logo No. 05: C??digos de tipos de tributos
    ),
    array(
        'item'              =>  2,
        'codigo'            =>  'PROD002',
        'descripcion'       =>  'LIBRO DANZA DE DRAGONES',
        'cantidad'          =>  1,
        'precio_unitario'   =>  200.00,//incluir impuestos IGV:0
        'valor_unitario'    =>  200.00,//sin impuestos IGV=0
        'igv'               =>  0.00,
        'tipo_precio'       =>  '01', //Catalogo
        'porcentaje_igv'    =>  0,
        'importe_total'     =>  200.00,//cantidad * precio unitario
        'valor_total'       =>  200.00,//cantidad * valor unitario
        'unidad'            =>  'NIU',
        'bolsa_plastica'        =>  'NO',

        //IMPORTANTE
        'tipo_afectacion_igv'   =>  '20', //Cat??logo No. 07: C??digos de tipo de afectaci??n del IGV: 10:GRA, 20:EXO, 30:INA
        'codigo_tipo_tributo'   =>  '9997', //Cat??logo No. 05: C??digos de tipos de tributos
        'tipo_tributo'          =>  'VAT', //Cat??logo No. 05: C??digos de tipos de tributos
        'nombre_tributo'        =>  'EXO', //Cat??logo No. 05: C??digos de tipos de tributos
    ),
    array(
        'item'              =>  3,
        'codigo'            =>  'PROD003',
        'descripcion'       =>  'MANZANA ROJA IMPORTADA USA',
        'cantidad'          =>  8,
        'precio_unitario'   =>  2.00,//incluir impuestos IGV:18
        'valor_unitario'    =>  2.00,//sin impuestos IGV=0
        'igv'               =>  0.00,
        'tipo_precio'       =>  '01', //Catalogo
        'porcentaje_igv'    =>  0,
        'importe_total'     =>  16.00,//cantidad * precio unitario
        'valor_total'       =>  16.00,//cantidad * valor unitario
        'unidad'            =>  'NIU',
        'bolsa_plastica'        =>  'NO',

        //IMPORTANTE
        'tipo_afectacion_igv'   =>  '30', //Cat??logo No. 07: C??digos de tipo de afectaci??n del IGV: 10:GRA, 20:EXO, 30:INA
        'codigo_tipo_tributo'   =>  '9998', //Cat??logo No. 05: C??digos de tipos de tributos
        'tipo_tributo'          =>  'FRE', //Cat??logo No. 05: C??digos de tipos de tributos
        'nombre_tributo'        =>  'INA', //Cat??logo No. 05: C??digos de tipos de tributos
    )
);

//INICIALIZAR VARIABLES
$total_opgravadas = 0;
$total_opexoneradas = 0;
$total_opinafectas = 0;
$total_impbolsas = 0;
$igv = 0;
$total = 0;

foreach ($detalle as $key => $value) {
    if ($value['tipo_afectacion_igv'] == 10) { //OP GRAVADAS
        $total_opgravadas += $value['valor_total'];
    }

    if ($value['tipo_afectacion_igv'] == 20) { //OP EXONERADAS
        $total_opexoneradas += $value['valor_total'];
    }

    if ($value['tipo_afectacion_igv'] == 30) { //OP INAFECTAS
        $total_opinafectas += $value['valor_total'];
    }

    $igv += $value['igv'];
    $total += $value['importe_total'] + $total_impbolsas;
}

$comprobante['total_opgravadas'] = $total_opgravadas;
$comprobante['total_opexoneradas'] = $total_opexoneradas;
$comprobante['total_opinafectas'] = $total_opinafectas;
$comprobante['total_impbolsas'] = $total_impbolsas;
$comprobante['igv'] = $igv;
$comprobante['total'] = $total;

//PASO 01 - CREAR EL XML DE BOLETA
require_once('api_genera_xml.php');
$api_xml = new api_genera_xml();

$nombreXML = $emisor['nrodoc'] . '-' . $comprobante['tipodoc'] . '-' . $comprobante['serie'] . '-' . $comprobante['correlativo'];
$rutaXML = 'xml/';


$api_xml->crea_xml_invoice($rutaXML . $nombreXML, $emisor, $cliente, $comprobante, $detalle, null);
echo '</br> PARTE 01 - CREAR XML DE BOLETA';
echo '</br> - Se creo el xml con exito';


require_once('api_facturacion.php');
$objApi = new api_facturacion();

$estado_facturacion  = $objApi->enviar_comprobante($emisor, $nombreXML);

echo '</br> Estado Facturaci??n: ' . $estado_facturacion['estado'];
echo '</br> Mensaje Facturaci??n: ' . $estado_facturacion['estado_mensaje'];
echo '</br> descripcion: ' . $estado_facturacion['descripcion'];
echo '</br> nota: ' . $estado_facturacion['nota'];
echo '</br> codigo_error: ' . $estado_facturacion['codigo_error'];
echo '</br> mensaje_error: ' . $estado_facturacion['mensaje_error'];
echo '</br> http_code: ' . $estado_facturacion['http_code'];
echo '</br> output: ' . $estado_facturacion['output'];

?>