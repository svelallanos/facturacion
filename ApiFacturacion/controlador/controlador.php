<?php

require_once("../ado/clsEmisor.php");
require_once("../ado/clsCompartido.php");
require_once("../ado/clsCliente.php");
require_once("../ado/clsVenta.php");
require_once('../ado/clsNotaCredito.php');
require_once('../ado/clsNotaDebito.php');


require_once("../api_facturacion.php");
require_once("../api_genera_xml.php");

$accion = $_POST['accion'];

operaciones($accion);

function operaciones($accion)
{
    $objEmisor = new clsEmisor();
    $objCompartido = new clsCompartido();
    $objCliente = new clsCliente();
    $objVenta = new clsVenta();

    $objNC = new clsNotaCredito();
    $objND = new clsNotaDebito();
    $api = new api_facturacion();
    $generadorXML = new api_genera_xml();

    switch ($accion) {
        case 'LISTAR_EMISORES':
            $listar_emisores = $objEmisor->consultarListaEmisores();
            $listar_emisores = $listar_emisores->fetchAll(PDO::FETCH_NAMED);
            $emisores = array(
                'emisores' =>   $listar_emisores
            );
            echo json_encode($emisores);
            
            break;

        case 'LISTAR_MONEDAS':
            $listado = $objCompartido->listarMonedas();
            $listado = $listado->fetchAll(PDO::FETCH_NAMED);
            $listado = array(
                'listado' =>   $listado
            );
            echo json_encode($listado);
            
            break;
        
        case 'LISTAR_COMPROBANTES':
            $listado = $objCompartido->listarComprobantesCodigo($_POST['tipo']);
            $listado = $listado->fetchAll(PDO::FETCH_NAMED);
            $listado = array(
                'listado' =>   $listado
            );
            echo json_encode($listado);
            
            break;

        case 'LISTAR_DOCUMENTOS':
            $listado = $objCompartido->listarTipoDocumentoCodigo($_POST['tipo']);
            $listado = $listado->fetchAll(PDO::FETCH_NAMED);
            $listado = array(
                'listado' =>   $listado
            );
            echo json_encode($listado);
            
            break;

        case 'LISTAR_DOCUMENTOS_TODOS':
            $listado = $objCompartido->listarTipoDocumento();
            $listado = $listado->fetchAll(PDO::FETCH_NAMED);
            $listado = array(
                'listado' =>   $listado
            );
            echo json_encode($listado);
            
            break;            

        case 'LISTAR_SERIES':
            $listado = $objCompartido->listarSerie($_POST['tipocomp']);
            $listado = $listado->fetchAll(PDO::FETCH_NAMED);
            $listado = array(
                'series' =>   $listado
            );
            
            echo json_encode($listado);
            
            break;

        case 'OBTENER_CORRELATIVO':
            $listado = $objCompartido->obtenerSerie($_POST['idserie']);
            $listado = $listado->fetch(PDO::FETCH_NAMED);
            $correlativo = $listado['correlativo'];
            echo $correlativo;
            
            break;

        case 'CONSULTA_DNI':
            $dni = $_POST['dni'];
            $url_ws = "https://consultaruc.win/api/dni/$dni?format=json";
            $header = array();

                // create curl resource
            $ch = curl_init();           

            // set url del ws de sunat            
            curl_setopt($ch, CURLOPT_URL, $url_ws);

            //return the transfer as a string
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

            // $output contains the output string
            $output = curl_exec($ch);
            curl_close($ch);
            echo $output;
            break;

        case 'CONSULTA_RUC':
            $ruc = $_POST['ruc'];
            $url_ws = "https://consultaruc.win/api/ruc/$ruc?format=json";
            $header = array();
            
                // create curl resource
            $ch = curl_init();           

            // set url del ws de sunat            
            curl_setopt($ch, CURLOPT_URL, $url_ws);

            //return the transfer as a string
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

            // $output contains the output string
            $output = curl_exec($ch);
            curl_close($ch);
            echo $output;
            break;

        case "BUSCAR_PRODUCTO":
            $listado = $objCompartido->listarProducto($_POST['filtro']);
            $listado = $listado->fetchAll(PDO::FETCH_NAMED);
            $listado = array(
                'productos' =>   $listado
            );
            echo json_encode($listado);
            
            break;

        case "ADD_PRODUCTO":


            //Inicia carrito
            $producto = $objCompartido->obtenerProducto($_POST['codigo']);
            $producto = $producto->fetch(PDO::FETCH_NAMED);

            $cantidad_agregar = 1;

            if (isset($_POST['cantidad'])) {
                $cantidad_agregar = $_POST['cantidad'];
            }

            session_start();
            if (!isset($_SESSION['carrito'])) {
                $_SESSION['carrito'] = array();
            }

            $carrito = $_SESSION['carrito'];

            $item = count($carrito) + 1;
            $existe = false;

            foreach ($carrito as $key => $value) {
                if ($value['codigo'] == $_POST['codigo']) {
                    $item = $key;
                    $existe = true;
                    break;
                }
            }

            if ($existe) {
                $carrito[$item]['cantidad'] = $carrito[$item]['cantidad'] + $cantidad_agregar;
            }else{
                $carrito[$item] = array(
                    'codigo'            => $producto['codigo'],
                    'nombre'            => $producto['nombre'],
                    'precio'            => $producto['precio'],
                    'unidad'            => $producto['unidad'],
                    'codigoafectacion'            => $producto['codigoafectacion'],
                    'cantidad'            => $cantidad_agregar,
                );
            }

            $_SESSION['carrito'] = $carrito;

            //Fin de carrito

            //Inicializamos variables
            $op_opgravadas = 0.00;
            $op_opexoneradas = 0.00;
            $op_opinafectas = 0.00;
            $igv = 0.00;
            $igv_porcentaje = 0.18;

            foreach ($carrito as $key => $value) {
                if ($value['codigoafectacion'] == '10') { //OP GRAVADAS
                    $op_opgravadas += $value['precio'] * $value['cantidad'];
                }
                if ($value['codigoafectacion'] == '20') { //OP EXONERADAS
                    $op_opexoneradas += $value['precio'] * $value['cantidad'];
                }
                if ($value['codigoafectacion'] == '30') { //OP INAFECTAS
                    $op_opinafectas += $value['precio'] * $value['cantidad'];
                }
            }

            $igv = $op_opgravadas * $igv_porcentaje;
            $total = $op_opgravadas + $op_opexoneradas + $op_opinafectas + $igv;

            $html = "<table class='table table-hover table-sm'>
                    <tr>
                        <th>ITEM</th>
                        <th>CANT</th>
                        <th>UND</th>
                        <th>PRODUCTO</th>
                        <th>P.U.</th>
                        <th>SUBTOTAL</th>
                    </tr>";
            
            $det_html = '';
            foreach ($carrito as $key => $value) {
            $det_html = $det_html . "<tr>
                        <td>" . $key . "</td>
                        <td>" . $value['cantidad'] . "</td>
                        <td>" . $value['unidad'] . "</td>
                        <td>" . $value['nombre'] . "</td>
                        <td>" . $value['precio'] . "</td>
                        <td>" . $value['cantidad'] * $value['precio'] . "</td>
                    </tr>";
            }
            $html = $html . $det_html;
            $html = $html . "<tr><td colspan='5' align='right'>OP. GRAVADAS</td><td>" . $op_opgravadas . "</td></tr>";
            $html = $html . "<tr><td colspan='5' align='right'>OP. EXONERADAS</td><td>" . $op_opexoneradas . "</td></tr>";
            $html = $html . "<tr><td colspan='5' align='right'>OP. INAFECTAS</td><td>" . $op_opinafectas . "</td></tr>";
            $html = $html . "<tr><td colspan='5' align='right'>IGV</td><td>" . $igv . "</td></tr>";
            $html = $html . "<tr><td colspan='5' align='right'><b>TOTAL</b></td><td><b>" . $total . "</b></td></tr>";
            $html = $html . "</table>";

            echo $html;


            break;

        case "ADD_PRODUCTO_BOLSA_GRATUITA":

            //Inicia carrito
            $producto = $objCompartido->obtenerProducto(11);
            $producto = $producto->fetch(PDO::FETCH_NAMED);

            $cantidad_agregar = 1;

            if (isset($_POST['cantidad'])) {
                $cantidad_agregar = $_POST['cantidad'];
            }

            session_start();
            if (!isset($_SESSION['carrito'])) {
                $_SESSION['carrito'] = array();
            }

            $carrito = $_SESSION['carrito'];

            $item = count($carrito) + 1;
            $existe = false;

            foreach ($carrito as $key => $value) {
                if ($value['codigo'] == 11) {
                    $item = $key;
                    $existe = true;
                    break;
                }
            }

            if ($existe) {
                $carrito[$item]['cantidad'] = $carrito[$item]['cantidad'] + $cantidad_agregar;
            }else{
                $carrito[$item] = array(
                    'codigo'            => $producto['codigo'],
                    'nombre'            => $producto['nombre'],
                    'precio'            => $producto['precio'],
                    'unidad'            => $producto['unidad'],
                    'codigoafectacion'            => $producto['codigoafectacion'],
                    'cantidad'            => $cantidad_agregar,
                );
            }

            $_SESSION['carrito'] = $carrito;

            //Fin de carrito

            //Inicializamos variables
            $op_opgravadas = 0.00;
            $op_opexoneradas = 0.00;
            $op_opinafectas = 0.00;
            $igv = 0.00;
            $igv_porcentaje = 0.18;

            foreach ($carrito as $key => $value) {
                if ($value['codigoafectacion'] == '10') { //OP GRAVADAS
                    $op_opgravadas += $value['precio'] * $value['cantidad'];
                }
                if ($value['codigoafectacion'] == '20') { //OP EXONERADAS
                    $op_opexoneradas += $value['precio'] * $value['cantidad'];
                }
                if ($value['codigoafectacion'] == '30') { //OP INAFECTAS
                    $op_opinafectas += $value['precio'] * $value['cantidad'];
                }
            }

            $igv = $op_opgravadas * $igv_porcentaje;
            $total = $op_opgravadas + $op_opexoneradas + $op_opinafectas + $igv;

            $html = "<table class='table table-hover table-sm'>
                    <tr>
                        <th>ITEM</th>
                        <th>CANT</th>
                        <th>UND</th>
                        <th>PRODUCTO</th>
                        <th>P.U.</th>
                        <th>SUBTOTAL</th>
                    </tr>";
            
            $det_html = '';
            foreach ($carrito as $key => $value) {
            $det_html = $det_html . "<tr>
                        <td>" . $key . "</td>
                        <td>" . $value['cantidad'] . "</td>
                        <td>" . $value['unidad'] . "</td>
                        <td>" . $value['nombre'] . "</td>
                        <td>" . $value['precio'] . "</td>
                        <td>" . $value['cantidad'] * $value['precio'] . "</td>
                    </tr>";
            }
            $html = $html . $det_html;
            $html = $html . "<tr><td colspan='5' align='right'>OP. GRAVADAS</td><td>" . $op_opgravadas . "</td></tr>";
            $html = $html . "<tr><td colspan='5' align='right'>OP. EXONERADAS</td><td>" . $op_opexoneradas . "</td></tr>";
            $html = $html . "<tr><td colspan='5' align='right'>OP. INAFECTAS</td><td>" . $op_opinafectas . "</td></tr>";
            $html = $html . "<tr><td colspan='5' align='right'>IGV</td><td>" . $igv . "</td></tr>";
            $html = $html . "<tr><td colspan='5' align='right'><b>TOTAL</b></td><td><b>" . $total . "</b></td></tr>";
            $html = $html . "</table>";

            echo $html;


            break;

        case "CANCELAR_CARRITO":

            session_start();
            session_destroy();

            break;

        case "GUARDAR_VENTA":
            session_start();

            //datos del emisor
            $idemisor = $_POST['idemisor'];
            $emisor = $objEmisor->obtenerEmisor($idemisor);
            $emisor = $emisor->fetch(PDO::FETCH_NAMED);

            //datos del cliente
            $cliente = array(
                'tipodoc'           =>  $_POST['tipodoc'],
                'nrodoc'           =>  $_POST['nrodoc'],
                'razon_social'           =>  $_POST['razon_social'],
                'direccion'           =>  $_POST['direccion'],
                'pais'              =>  'PE'
            );

            $cliente_existe = $objCliente->consultarCliente($_POST['nrodoc']);
            if ($cliente_existe->rowCount() > 0) {
                $cliente_existe = $cliente_existe->fetch(PDO::FETCH_NAMED);                
            }else{
                $objCliente->insertarCliente($cliente);
                $cliente_existe = $objCliente->consultarCliente($_POST['nrodoc']);
                $cliente_existe = $cliente_existe->fetch(PDO::FETCH_NAMED);
            }

            $idcliente = $cliente_existe['id'];

            //obtener los datos del carrito

            $carrito = $_SESSION['carrito'];
            $detalle = array();
            $igv_porcentaje = 0.18;
            $op_gravadas = 0.00;
            $op_exoneradas = 0.00;
            $op_inafectas = 0.00;
            $igv = 0.00;
            $op_gratuitas1 = 0.00;
            $op_gratuitas2 = 0.00;
            $total_impuesto_bolsas = 0.00;

            foreach ($carrito as $key => $value) {
                $producto = $objCompartido->obtenerProducto($value['codigo']);
                $producto = $producto->fetch(PDO::FETCH_NAMED);

                $afectacion = $objCompartido->obtenerRegistroAfectacion($producto['codigoafectacion']);
                $afectacion = $afectacion->fetch(PDO::FETCH_NAMED);

                $igv_detalle = 0;
                $factor_porcentaje = 1;

                if ($producto['codigoafectacion'] == 10 || $producto['codigoafectacion'] == 12) {
                    $igv_detalle = $value['precio'] * $value['cantidad'] * $igv_porcentaje;
                    $factor_porcentaje = 1 + $igv_porcentaje;
                }

                if ($producto['codigoafectacion'] == 12) { //ejempplo de bolsa gratuita, con icbper
                    $item_producto = array(
                        'item'                              =>  $key, //correlativo iniciando desde 1
                        'codigo'                            =>  $value['codigo'], //codigo del producto/servicio
                        'descripcion'                       =>  $value['nombre'],
                        'cantidad'                          =>  $value['cantidad'],
                        'valor_unitario'                    =>  0, //no incluye impuestos
                        'precio_unitario'                   =>  0.05, //si incluye impuestos
                        'tipo_precio'                       =>  $producto['tipo_precio'], //Catálogo No. 16: Códigos – Tipo de precio de venta unitario
                        'igv'                               =>  $value['cantidad'] * 0.01,
                        'porcentaje_igv'                    =>  $igv_porcentaje * 100,
                        'valor_total'                       =>  0.05 * $value['cantidad'], //Cantidad * valor unitario
                        'importe_total'                     =>  0.40 * $value['cantidad'], //Cantidad * precio unitario
                        'unidad'                            =>  $value['unidad'],
                        'tipo_afectacion_igv'               =>  $producto['codigoafectacion'], //GRAVADO GRATUITO: 12
                        'codigo_tipo_tributo'               =>  '9996', //Catálogo No. 05: Códigos de tipos de tributos
                        'tipo_tributo'                      =>  'FRE',
                        'nombre_tributo'                    =>  'GRA',
                        'bolsa_plastica'                    =>  'SI',
                        'total_impuesto_bolsas'             =>  0.40 * $value['cantidad'],//factor * cantidad
                    );
                }else{
                    $item_producto = array(
                        'item'                              =>  $key, //correlativo iniciando desde 1
                        'codigo'                            =>  $value['codigo'], //codigo del producto/servicio
                        'descripcion'                       =>  $value['nombre'],
                        'cantidad'                          =>  $value['cantidad'],
                        'valor_unitario'                    =>  $value['precio'], //no incluye impuestos
                        'precio_unitario'                   =>  $value['precio'] * $factor_porcentaje, //si incluye impuestos
                        'tipo_precio'                       =>  $producto['tipo_precio'], //Catálogo No. 16: Códigos – Tipo de precio de venta unitario
                        'igv'                               =>  $igv_detalle,
                        'porcentaje_igv'                    =>  $igv_porcentaje * 100,
                        'valor_total'                       =>  $value['precio'] * $value['cantidad'], //Cantidad * valor unitario
                        'importe_total'                     =>  $value['precio'] * $value['cantidad'] * $factor_porcentaje, //Cantidad * precio unitario
                        'unidad'                            =>  $value['unidad'],
                        'tipo_afectacion_igv'               =>  $producto['codigoafectacion'], //GRAVADO: 10, EXONERADO: 20, INAFECTO: 30, Catálogo No. 07: Códigos de tipo de afectación del IGV
                        'codigo_tipo_tributo'               =>  $afectacion['codigo_afectacion'], //Catálogo No. 05: Códigos de tipos de tributos
                        'tipo_tributo'                      =>  $afectacion['tipo_afectacion'],
                        'nombre_tributo'                    =>  $afectacion['nombre_afectacion'],
                        'bolsa_plastica'                    =>  'NO',
                        'total_impuesto_bolsas'             =>  0.00
                    );
                }


                $detalle[] = $item_producto;

                if ($item_producto['tipo_afectacion_igv'] == 10) {
                    $op_gravadas = $op_gravadas + $item_producto['valor_total'];
                }

                if ($item_producto['tipo_afectacion_igv'] == 12) {
                    $op_gratuitas1 = $op_gratuitas1 + $item_producto['valor_total'];
                    $op_gratuitas2 = $op_gratuitas2 + $item_producto['igv'];
                }

                if ($item_producto['tipo_afectacion_igv'] == 20) {
                    $op_exoneradas = $op_exoneradas + $item_producto['valor_total'];
                }

                if ($item_producto['tipo_afectacion_igv'] == 30) {
                    $op_inafectas = $op_inafectas + $item_producto['valor_total'];
                }

                $igv = $igv + $igv_detalle;
                $total_impuesto_bolsas = $total_impuesto_bolsas + $item_producto['total_impuesto_bolsas'];
            }

            $total = $op_gravadas + $op_exoneradas + $op_inafectas + $igv + $total_impuesto_bolsas;

            //obtener la serie
            $idserie = $_POST['idserie'];
            $seriex = $objCompartido->obtenerSerie($idserie);
            $seriex = $seriex->fetch(PDO::FETCH_NAMED);

            //forma de pago
            $monto_pendiente = 0.00;
            if ($_POST['forma_pago'] == 'Credito') {
                $monto_pendiente = $_POST['monto_pendiente'];
            }

            $comprobante = array(
                'tipodoc'                   =>  $_POST['tipocomp'], //tipo de comprobante de pago: Catálogo No. 01: Código de tipo de documento
                'idserie'                   =>  $idserie,
                'serie'                     =>  $seriex['serie'],
                'correlativo'               =>  $seriex['correlativo'],
                'fecha_emision'             =>  $_POST['fecha_emision'],
                'hora'                      =>  '00:00:00',
                'fecha_vencimiento'         =>  $_POST['fecha_emision'],
                'moneda'                    =>  $_POST['moneda'], //SOLES=PEN, DOLARES=USD
                'total_opgravadas'          =>  $op_gravadas,
                'total_opexoneradas'        =>  $op_exoneradas,
                'total_opinafectas'         =>  $op_inafectas,
                'total_opgratuitas_1'       =>  $op_gratuitas1,
                'total_opgratuitas_2'       =>  $op_gratuitas2,
                'total_impbolsas'           =>  $total_impuesto_bolsas,
                'igv'                       =>  $igv,
                'total'                     =>  $total,
                'total_texto'               =>  '', //CantidadEnLetra($total),
                'forma_pago'                =>  $_POST['forma_pago'], //Contado o Credito
                'monto_pendiente'           =>  $monto_pendiente,
                'codcliente'                => $idcliente
            );

            if ($_POST['forma_pago'] == 'Credito') {
                $nrocuotas = $_POST['cuotas'];
                $cuotas = array();
                for ($i=1; $i <= $nrocuotas ; $i++) { 
                    $cuotas[] = array(
                        'cuota'     => 'Cuota' . str_pad($i, 3 , "0", STR_PAD_LEFT),
                        'monto'     => $_POST['txtMonto' . $i],
                        'fecha'     => $_POST['txtFecha' . $i],
                    );
                }
            }else{
                $cuotas = null;
            }

            //INSERTAR LA VENTA EN LA BASE DE DATOS
            $objCompartido->actualizarSerie($idserie, $comprobante['correlativo']);
            $objVenta->insertarVenta($idemisor, $comprobante);
            $venta = $objVenta->obtenerUltimoComprobanteId();
            $venta = $venta->fetch(PDO::FETCH_NAMED);
            $objVenta->insertarDetalle($venta['id'], $detalle);

            //FACTURACION ELECTRONICA - INICIO

            //CREAR EL XML
            $api_xml = new api_genera_xml();

            $nombreXML = $emisor['nrodoc'] . '-' . $comprobante['tipodoc'] . '-' . $comprobante['serie'] . '-' . $comprobante['correlativo'];
            $rutaXML = '../xml/';

            $api_xml->crea_xml_invoice($rutaXML . $nombreXML, $emisor, $cliente, $comprobante, $detalle, $cuotas);

            //ENVIA A SUNAT CPE
            $obj_fac = new api_facturacion();
            $estado_envio = $obj_fac->enviar_comprobante($emisor, $nombreXML,"../certificado_digital/", "../xml/", "../cdr/");

            echo $estado_envio['mensaje_error'];

            $objVenta->actualiza_envio_fe($venta['id'], $estado_envio);

            if ($estado_envio['estado'] == 8) {
                echo 'VENTA REGISTRADA CON EXITO';
            }else
                echo 'ERROR O OBSERVACION DE ENVIOA A SUNAT, REVISAR EL PROCESO';           

            //FACTURACION ELECTRONICA - FIN

            session_destroy();
            //IMPRESION
            echo "<script>window.open('./apifacturacion/pdfInvoice.php?id=" . $venta['id'] . "','_blank')</script>";
            


            break;

        case 'GUARDAR_NC':
            session_start();
            
            //datos del emisor
            $idemisor = $_POST['idemisor'];
            $emisor = $objEmisor->obtenerEmisor($idemisor);
            $emisor = $emisor->fetch(PDO::FETCH_NAMED);

            //datos del cliente
            $cliente = array(
                'tipodoc'                   =>  $_POST['tipodoc'],
                'nrodoc'                       =>  $_POST['nrodoc'],
                'razon_social'              =>  $_POST['razon_social'],
                'direccion'                 =>  $_POST['direccion'],
                'pais'                      =>  'PE'
            );

            $cliente_existe = $objCliente->consultarCliente($_POST['nrodoc']);
            if ($cliente_existe->rowCount() > 0) {
                $cliente_existe = $cliente_existe->fetch(PDO::FETCH_NAMED);
            }else{
                $objCliente->insertarCliente($cliente);
                $cliente_existe = $objCliente->consultarCliente($_POST['nrodoc']);
                $cliente_existe = $cliente_existe->fetch(PDO::FETCH_NAMED);
            }

            $idcliente = $cliente_existe['id'];

            //obtener el carrito
            $carrito = $_SESSION['carrito'];
            $detalle = array();
            $igv_porcentaje = 0.18;
            $op_gravadas = 0.00;
            $op_exoneradas = 0.00;
            $op_inafectas = 0.00;
            $igv = 0.00;

            foreach ($carrito as $key => $value) {
                $producto = $objCompartido->obtenerProducto($value['codigo']);
                $producto = $producto->fetch(PDO::FETCH_NAMED);

                $afectacion = $objCompartido->obtenerRegistroAfectacion($producto['codigoafectacion']);
                $afectacion = $afectacion->fetch(PDO::FETCH_NAMED);

                $igv_detalle = 0;
                $factor_porcentaje = 1;

                if ($producto['codigoafectacion'] == 10) {
                    $igv_detalle = $value['precio'] * $value['cantidad'] * $igv_porcentaje;
                    $factor_porcentaje = 1 + $igv_porcentaje;
                }

                $item_producto = array(
                    'item'                              =>  $key, //correlativo iniciando desde 1
                    'codigo'                            =>  $value['codigo'], //codigo del producto/servicio
                    'descripcion'                       =>  $value['nombre'],
                    'cantidad'                          =>  $value['cantidad'],
                    'valor_unitario'                    =>  $value['precio'], //no incluye impuestos
                    'precio_unitario'                   =>  $value['precio'] * $factor_porcentaje, //si incluye impuestos
                    'tipo_precio'                       =>  $producto['tipo_precio'], //Catálogo No. 16: Códigos – Tipo de precio de venta unitario
                    'igv'                               =>  $igv_detalle,
                    'porcentaje_igv'                    =>  $igv_porcentaje * 100,
                    'valor_total'                       =>  $value['precio'] * $value['cantidad'], //Cantidad * valor unitario
                    'importe_total'                     =>  $value['precio'] * $value['cantidad'] * $factor_porcentaje, //Cantidad * precio unitario
                    'unidad'                            =>  $value['unidad'],
                    'tipo_afectacion_igv'               =>  $producto['codigoafectacion'], //GRAVADO: 10, EXONERADO: 20, INAFECTO: 30, Catálogo No. 07: Códigos de tipo de afectación del IGV
                    'codigo_tipo_tributo'               =>  $afectacion['codigo_afectacion'], //Catálogo No. 05: Códigos de tipos de tributos
                    'tipo_tributo'                      =>  $afectacion['tipo_afectacion'],
                    'nombre_tributo'                    =>  $afectacion['nombre_afectacion'],
                    'bolsa_plastica'                    =>  'NO'
                );

                $detalle[] = $item_producto;

                if ($item_producto['tipo_afectacion_igv'] == 10) {
                    $op_gravadas = $op_gravadas + $item_producto['valor_total'];
                }

                if ($item_producto['tipo_afectacion_igv'] == 20) {
                    $op_exoneradas = $op_exoneradas + $item_producto['valor_total'];
                }

                if ($item_producto['tipo_afectacion_igv'] == 30) {
                    $op_inafectas = $op_inafectas + $item_producto['valor_total'];
                }

                $igv = $igv + $igv_detalle;
            }

            $total = $op_gravadas + $op_exoneradas + $op_inafectas + $igv;

            $idserie = $_POST['idserie'];
            $seriex = $objCompartido->obtenerSerie($idserie);
            $seriex = $seriex->fetch(PDO::FETCH_NAMED);

            $motivo = $objCompartido->getRegistroTablaParametrica('C', $_POST['motivo']);
            $motivo = $motivo->fetch(PDO::FETCH_NAMED);

            $comprobante = array(
                'tipodoc'                   =>  $_POST['tipocomp'], //tipo de comprobante de pago: Catálogo No. 01: Código de tipo de documento
                'idserie'                   =>  $idserie,
                'serie'                     =>  $seriex['serie'],
                'correlativo'               =>  $seriex['correlativo'] + 1,
                'fecha_emision'             =>  $_POST['fecha_emision'],
                'hora'                      =>  '00:00:00',
                'fecha_vencimiento'         =>  $_POST['fecha_emision'],
                'moneda'                    =>  $_POST['moneda'], //SOLES=PEN, DOLARES=USD
                'total_opgravadas'          =>  $op_gravadas,
                'total_opexoneradas'        =>  $op_exoneradas,
                'total_opinafectas'         =>  $op_inafectas,
                'total_impbolsas'           =>  0.00,
                'igv'                       =>  $igv,
                'total'                     =>  $total,
                'total_texto'               =>  '',//CantidadEnLetra($total),
                'codcliente'                => $idcliente,
                'tipodoc_ref'               =>  $_POST['tipocomp_ref'],
                'serie_ref'                 =>  $_POST['serie_ref'],
                'correlativo_ref'           =>  $_POST['correlativo_ref'],
                'codmotivo'                 =>  $_POST['motivo'],
                'descripcion'               =>  $motivo['descripcion']

            );

            //INSERTAMOS LA VENTA EN BASE DE DATOS
            $objCompartido->actualizarSerie($idserie, $comprobante['correlativo']);
            $objNC->insertarNotaCredito($idemisor, $comprobante);
            $nc = $objNC->obtenerUltimoComprobanteId();
            $nc = $nc->fetch(PDO::FETCH_NAMED);
            $objNC->insertarDetalleNotaCredito($nc['id'], $detalle);

            //ENVIO DE COMPROBANTE A SUNAT
            //1. XML
            $nombre = $emisor['ruc'] . '-' . $comprobante['tipodoc'] . '-' . $comprobante['serie']  . '-' .  $comprobante['correlativo'];
            $ruta = '../xml/';

            $generadorXML->crea_xml_notacredito($ruta . $nombre, $emisor, $cliente, $comprobante, $detalle);

            //2. ENVIO A WS-SUNAT
            $api->enviar_comprobante($emisor, $nombre, "../", "../xml/", "../cdr/");

            //MENSAJE
            echo "</br> NOTA DE CRÉDITO REGISTRADA CON EXITO";
            session_destroy();

            //IMPRESION
            //echo "<script>window.open('./apifacturacion/pdfInvoice.php?id=" . $venta['id'] . "','_blank')</script>";

            break;        
    
        case 'GUARDAR_ND':
            session_start();
            
            //datos del emisor
            $idemisor = $_POST['idemisor'];
            $emisor = $objEmisor->obtenerEmisor($idemisor);
            $emisor = $emisor->fetch(PDO::FETCH_NAMED);

            //datos del cliente
            $cliente = array(
                'tipodoc'                   =>  $_POST['tipodoc'],
                'nrodoc'                       =>  $_POST['nrodoc'],
                'razon_social'              =>  $_POST['razon_social'],
                'direccion'                 =>  $_POST['direccion'],
                'pais'                      =>  'PE'
            );

            $cliente_existe = $objCliente->consultarCliente($_POST['nrodoc']);
            if ($cliente_existe->rowCount() > 0) {
                $cliente_existe = $cliente_existe->fetch(PDO::FETCH_NAMED);
            }else{
                $objCliente->insertarCliente($cliente);
                $cliente_existe = $objCliente->consultarCliente($_POST['nrodoc']);
                $cliente_existe = $cliente_existe->fetch(PDO::FETCH_NAMED);
            }

            $idcliente = $cliente_existe['id'];

            //obtener el carrito
            $carrito = $_SESSION['carrito'];
            $detalle = array();
            $igv_porcentaje = 0.18;
            $op_gravadas = 0.00;
            $op_exoneradas = 0.00;
            $op_inafectas = 0.00;
            $igv = 0.00;

            foreach ($carrito as $key => $value) {
                $producto = $objCompartido->obtenerProducto($value['codigo']);
                $producto = $producto->fetch(PDO::FETCH_NAMED);

                $afectacion = $objCompartido->obtenerRegistroAfectacion($producto['codigoafectacion']);
                $afectacion = $afectacion->fetch(PDO::FETCH_NAMED);

                $igv_detalle = 0;
                $factor_porcentaje = 1;

                if ($producto['codigoafectacion'] == 10) {
                    $igv_detalle = $value['precio'] * $value['cantidad'] * $igv_porcentaje;
                    $factor_porcentaje = 1 + $igv_porcentaje;
                }

                $item_producto = array(
                    'item'                              =>  $key, //correlativo iniciando desde 1
                    'codigo'                            =>  $value['codigo'], //codigo del producto/servicio
                    'descripcion'                       =>  $value['nombre'],
                    'cantidad'                          =>  $value['cantidad'],
                    'valor_unitario'                    =>  $value['precio'], //no incluye impuestos
                    'precio_unitario'                   =>  $value['precio'] * $factor_porcentaje, //si incluye impuestos
                    'tipo_precio'                       =>  $producto['tipo_precio'], //Catálogo No. 16: Códigos – Tipo de precio de venta unitario
                    'igv'                               =>  $igv_detalle,
                    'porcentaje_igv'                    =>  $igv_porcentaje * 100,
                    'valor_total'                       =>  $value['precio'] * $value['cantidad'], //Cantidad * valor unitario
                    'importe_total'                     =>  $value['precio'] * $value['cantidad'] * $factor_porcentaje, //Cantidad * precio unitario
                    'unidad'                            =>  $value['unidad'],
                    'tipo_afectacion_igv'               =>  $producto['codigoafectacion'], //GRAVADO: 10, EXONERADO: 20, INAFECTO: 30, Catálogo No. 07: Códigos de tipo de afectación del IGV
                    'codigo_tipo_tributo'               =>  $afectacion['codigo_afectacion'], //Catálogo No. 05: Códigos de tipos de tributos
                    'tipo_tributo'                      =>  $afectacion['tipo_afectacion'],
                    'nombre_tributo'                    =>  $afectacion['nombre_afectacion'],
                    'bolsa_plastica'                    =>  'NO'
                );

                $detalle[] = $item_producto;

                if ($item_producto['tipo_afectacion_igv'] == 10) {
                    $op_gravadas = $op_gravadas + $item_producto['valor_total'];
                }

                if ($item_producto['tipo_afectacion_igv'] == 20) {
                    $op_exoneradas = $op_exoneradas + $item_producto['valor_total'];
                }

                if ($item_producto['tipo_afectacion_igv'] == 30) {
                    $op_inafectas = $op_inafectas + $item_producto['valor_total'];
                }

                $igv = $igv + $igv_detalle;
            }

            $total = $op_gravadas + $op_exoneradas + $op_inafectas + $igv;

            $idserie = $_POST['idserie'];
            $seriex = $objCompartido->obtenerSerie($idserie);
            $seriex = $seriex->fetch(PDO::FETCH_NAMED);

            $motivo = $objCompartido->getRegistroTablaParametrica('D', $_POST['motivo']);
            $motivo = $motivo->fetch(PDO::FETCH_NAMED);

            $comprobante = array(
                'tipodoc'                   =>  $_POST['tipocomp'], //tipo de comprobante de pago: Catálogo No. 01: Código de tipo de documento
                'idserie'                   =>  $idserie,
                'serie'                     =>  $seriex['serie'],
                'correlativo'               =>  $seriex['correlativo'] + 1,
                'fecha_emision'             =>  $_POST['fecha_emision'],
                'hora'                      =>  '00:00:00',
                'fecha_vencimiento'         =>  $_POST['fecha_emision'],
                'moneda'                    =>  $_POST['moneda'], //SOLES=PEN, DOLARES=USD
                'total_opgravadas'          =>  $op_gravadas,
                'total_opexoneradas'        =>  $op_exoneradas,
                'total_opinafectas'         =>  $op_inafectas,
                'total_impbolsas'           =>  0.00,
                'igv'                       =>  $igv,
                'total'                     =>  $total,
                'total_texto'               =>  '', //CantidadEnLetra($total),
                'codcliente'                => $idcliente,
                'tipodoc_ref'               =>  $_POST['tipocomp_ref'],
                'serie_ref'                 =>  $_POST['serie_ref'],
                'correlativo_ref'           =>  $_POST['correlativo_ref'],
                'codmotivo'                 =>  $_POST['motivo'],
                'descripcion'               =>  $motivo['descripcion']

            );

            //INSERTAMOS LA VENTA EN BASE DE DATOS
            $objCompartido->actualizarSerie($idserie, $comprobante['correlativo']);
            $objND->insertarNotaDebito($idemisor, $comprobante);
            $nd = $objND->obtenerUltimoComprobanteId();
            $nd = $nd->fetch(PDO::FETCH_NAMED);
            $objND->insertarDetalleNotaDebito($nd['id'], $detalle);

            //ENVIO DE COMPROBANTE A SUNAT
            //1. XML
            $nombre = $emisor['ruc'] . '-' . $comprobante['tipodoc'] . '-' . $comprobante['serie']  . '-' .  $comprobante['correlativo'];
            $ruta = '../xml/';

            $generadorXML->crea_xml_notadebito($ruta . $nombre, $emisor, $cliente, $comprobante, $detalle);

            //2. ENVIO A WS-SUNAT
            $api->enviar_comprobante($emisor, $nombre, "../", "../xml/", "../cdr/");

            //MENSAJE
            echo "</br> NOTA DE DÉBITO REGISTRADA CON EXITO";
            session_destroy();

            //IMPRESION
            //echo "<script>window.open('./apifacturacion/pdfInvoice.php?id=" . $venta['id'] . "','_blank')</script>";

            break;
        
        case 'ENVIO_RESUMEN':

            $idemisor = $_POST['idemisor'];
            $emisor = $objEmisor->obtenerEmisor($idemisor);
            $emisor = $emisor->fetch(PDO::FETCH_NAMED);

            //varios al dia
            $serie = date('Ymd');
            $fila_serie = $objCompartido->obtenerSerieResumen('RC');
            $fila_serie = $fila_serie->fetch(PDO::FETCH_NAMED);

            $correlativo = 1;
            if ($fila_serie['serie'] != $serie) {
                $objCompartido->actualizarSerieResumen('RC', $serie);
            }else{
                $correlativo = $fila_serie['correlativo'] + 1;
            }

            $objCompartido->actualizarSerie($fila_serie['id'], $correlativo);

            $cabecera = array(
                'tipodoc'       => 'RC',
                'serie'         =>  $serie,
                'correlativo'   =>  $correlativo,
                'fecha_emision' =>  date('Y-m-d'),
                'fecha_envio'   =>  date('Y-m-d')
            );

            $items = array();
            $ids = $_POST['documento'];
            $i = 1;
            foreach ($ids as $key => $value) {
                $boleta = $objVenta->obtenerComprobanteId($value);
                $boleta = $boleta->fetch((PDO::FETCH_NAMED));

                $cliente = $objCliente->consultarClientePorCodigo($boleta['codcliente']);
                $cliente = $cliente->fetch(PDO::FETCH_NAMED);

                $items[] = array(
                    'item'              => $i,
                    'tipodoc'           => $boleta['tipocomp'],
                    'serie'             => $boleta['serie'],
                    'correlativo'       => $boleta['correlativo'],
                    'condicion'         => 1,
                    'moneda'            => $boleta['codmoneda'],
                    'importe_total'     =>  $boleta['total'],
                    'valor_total'       =>  $boleta['op_gravadas'],
                    'igv_total'         =>  $boleta['igv'],
                    'tipo_total'        =>  '01',
                    "codigo_afectacion"	=> "1000",
                    "nombre_afectacion"	=> "IGV",
                    "tipo_afectacion"	=> "VAT",
                    'tipodoci'          =>  $cliente['tipodoc'], //tipo de documento de identidad del cliente
                    'numdoci'           =>  $cliente['nrodoc'], //numero de dcoumento de identidad del cliente
                );

                $i++;
            }

            $ruta = "../xml/";
            $nombrexml = $emisor['ruc'].'-'.$cabecera['tipodoc'].'-'.$cabecera['serie'].'-'.$cabecera['correlativo'];

            $generadorXML->CrearXMLResumenDocumentos($emisor, $cabecera, $items, $ruta.$nombrexml);

            $ticket = $api->enviar_resumen($emisor,$nombrexml,"../","../xml/");

            $api->consultar_ticket($emisor, $cabecera, $ticket,"../cdr/");

            echo 'envio realizado';

            break;

        case 'ENVIO_BAJAS':

            $idemisor = $_POST['idemisor'];
            $emisor = $objEmisor->obtenerEmisor($idemisor);
            $emisor = $emisor->fetch(PDO::FETCH_NAMED);

            $serie = date('Ymd');
            $fila_serie = $objCompartido->obtenerSerieResumen('RA');
            $fila_serie = $fila_serie->fetch(PDO::FETCH_NAMED);

            $correlativo = 1;
            if($fila_serie['serie']!=$serie){
                $objCompartido->actualizarSerieResumen('RA', $serie);
            }else{
                $correlativo = $fila_serie['correlativo']+1;
            }

            $objCompartido->actualizarSerie($fila_serie['id'], $correlativo);

            $cabecera = array(
                        "tipodoc"		=>"RA",
                        "serie"			=>$serie,
                        "correlativo"	=>$correlativo,
                        "fecha_emision" =>date('Y-m-d'),			
                        "fecha_envio"	=>date('Y-m-d')	
                );


            $items = array();

            $ids = $_POST['documento'];
            $i=1;
            foreach($ids as $v){
                $factura = $objVenta->obtenerComprobanteId($v);
                $factura = $factura->fetch(PDO::FETCH_NAMED);

                $items[] = array(
                        "item"				=> $i,
                        "tipodoc"			=> $factura["tipocomp"],
                        "serie"				=> $factura["serie"],
                        "correlativo"		=> $factura["correlativo"],
                        "motivo"			=> "ERROR EN DOCUMENTO"
                    );
                $i++;
            }
            
            $ruta = "../xml/";
            $nombrexml = $emisor['ruc'].'-'.$cabecera['tipodoc'].'-'.$cabecera['serie'].'-'.$cabecera['correlativo'];

            $generadorXML->CrearXmlBajaDocumentos($emisor, $cabecera, $items, $ruta.$nombrexml);

            $ticket = $api->enviar_resumen($emisor,$nombrexml,"../","../xml/");

            $api->consultar_ticket($emisor, $cabecera, $ticket, "../cdr/");

            echo 'envío realizado';
            break;
    

        default:
            # code...
            break;
    }
}

?>