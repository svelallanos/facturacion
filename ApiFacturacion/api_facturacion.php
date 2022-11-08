<?php

class api_facturacion{

    //Nos permite enviar a SUNAT(sendBill): BOLETA, FACTURA, NOTA DE CREDITO Y NOTA DE DEBITO
    public function enviar_comprobante($emisor, $nombreXML, $ruta_certificado = 'certificado_digital/', $ruta_archivo_xml = 'xml/', $ruta_archivo_cdr = 'cdr/')
    {
        $estado_envio = 0;

        //FIRMAR XML DIGITALMENTE
        require_once('signature.php');
        $objFirma = new Signature();
        $flgfirma = 0; //ubicación del XML donde se firmara digitalmente
        $ruta_certificado = $ruta_certificado . 'certificado_prueba.pfx';
        $pass_certificado = 'ceti';
        $ruta_xml = $ruta_archivo_xml . $nombreXML . '.XML';

        $objFirma->signature_xml($flgfirma, $ruta_xml, $ruta_certificado, $pass_certificado);
        $estado_envio_mensaje = "XML FIRMADO DIGITALMENTE";
        $estado_envio = 1; //XML FIRMADO DIGITALMENTE

        //COMPRIMIR EL XML EN FORMATO ZIP
        $zip = new ZipArchive();
        $ruta_zip = $ruta_archivo_xml . $nombreXML . '.ZIP';

        if ($zip->open($ruta_zip, ZipArchive::CREATE) == TRUE) {
            $zip->addFile($ruta_xml, $nombreXML . '.XML');
            $zip->close();
        }
        $estado_envio_mensaje = "XML COMPRIMIDO EN FORMATO ZIP";
        $estado_envio = 2; //XML COMPRIMIDO EN FORMATO ZIP

        //CODIFICAR EL ZIP EN BASE 64
        $zip_codificado = base64_encode(file_get_contents($ruta_zip));
        //echo $zip_codificado;
        $estado_envio_mensaje = "CODIFICADO EL ZIP EN BASE 64";
        $estado_envio = 3;

        //CONSUMO DEL WEB SERVICE DE SUNAT
        $url_ws = "https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService"; //beta de prueba
        //$url_ws = "https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService"; //produccion

        $filename_zip = $nombreXML . '.ZIP';

        $xml_envelope = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
        xmlns:ser="http://service.sunat.gob.pe" xmlns:wsse="http://docs.oasisopen.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
            <soapenv:Header>
                <wsse:Security>
                    <wsse:UsernameToken>
                        <wsse:Username>' . $emisor['nrodoc'] . $emisor['usuario_secundario'] . '</wsse:Username>
                        <wsse:Password>' . $emisor['clave_usuario_secundario'] . '</wsse:Password>
                    </wsse:UsernameToken>
                </wsse:Security>
            </soapenv:Header>
            <soapenv:Body>
                <ser:sendBill>
                    <fileName>' . $filename_zip . '</fileName>
                    <contentFile>' . $zip_codificado . '</contentFile>
                </ser:sendBill>
            </soapenv:Body>
        </soapenv:Envelope>';

        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $url_ws); //INDICAMOS LA RUTA DEL WEB SERVICE DE SUNAT

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_envelope); //enviamos el XML ENVELOPE

        // $output contains the output string
        $output = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); //obtenemos el codigo de rpta

        $estado_envio_mensaje = "CONSUMO DEL WEB SERVICE DE SUNAT";
        $estado_envio = 4;


        //RESPUESTA O RECEPCION DEL WS
        $descripcion = "";
        $nota = "";
        $codigo = "";
        $mensaje = "";

        if ($http_code == 200) { //ok
            $doc = new DOMDocument();
            $doc->loadXML($output); //convertimos (xml) y cargamos la respuesta de SUNAT

            //VALIDAMOS QUE CONTENGA LA ETIQUETA APPLICATIONRESPONSE EN LA RTPA DE SUNAT
            if (isset($doc->getElementsByTagName('applicationResponse')->item(0)->nodeValue)) {
                
                $cdr = $doc->getElementsByTagName('applicationResponse')->item(0)->nodeValue;
                $estado_envio_mensaje = "OBTUVIMOS RPTA DE SUNAT-CDR";
                $estado_envio = 5;

                //DECODIFICAR EN BASE 64 (obtenemos el ZIP)
                $cdr = base64_decode($cdr);
                $estado_envio_mensaje = "DECODIFICADO EN BASE 64, OBTUVIMOS EL ZIP";
                $estado_envio = 6;

                //COPIAMOS DE MEMORIA A DISCO EL ZIP
                file_put_contents($ruta_archivo_cdr . 'R-' . $filename_zip, $cdr);
                $estado_envio_mensaje = "ZIP COPIADO A DISCO LOCAL";
                $estado_envio = 7;

                //DESCOMPRIMIR EL ZIP
                $zip = new ZipArchive();
                if ($zip->open($ruta_archivo_cdr . 'R-' . $filename_zip) == TRUE) {
                    $zip->extractTo($ruta_archivo_cdr);
                    $zip->close();


                    $xml_cdr = $ruta_archivo_cdr . 'R-' . $nombreXML . '.XML';
                    $doc_cdr = new DOMDocument();
                    $doc_cdr->loadXML(file_get_contents($xml_cdr));
                    
                    

                    if (isset($doc_cdr->getElementsByTagName('Description')->item(0)->nodeValue)) {
                        $descripcion = $doc_cdr->getElementsByTagName('Description')->item(0)->nodeValue;
                    }

                    if (isset($doc_cdr->getElementsByTagName('Note')->item(0)->nodeValue)) {
                        $nota = $doc_cdr->getElementsByTagName('Note')->item(0)->nodeValue;
                    }
                    $estado_envio_mensaje = "PROCESO TERMINADO CON EXITO";
                    $estado_envio = 8;

                }
            }else{
                $codigo = $doc->getElementsByTagName("faultcode")->item(0)->nodeValue;
                $mensaje = $doc->getElementsByTagName("faultstring")->item(0)->nodeValue;
                $estado_envio_mensaje = "ERROR/RECHAZADO DE SUNAT";
                $estado_envio = 10;
            }
        }else{
            curl_error($ch);
            $estado_envio_mensaje = "Error en consumo del WS/Red/Conexion";
            $estado_envio = 9;

            $doc = new DOMDocument();
            $doc->loadXML($output); //convertimos (xml) y cargamos la respuesta de SUNAT
            $codigo = $doc->getElementsByTagName("faultcode")->item(0)->nodeValue;
            $mensaje = $doc->getElementsByTagName("faultstring")->item(0)->nodeValue;

            $output = "Error en consumo del WS/Red/Conexion: " . $output;            
        }

        $estado_envio = array(
            "estado"            =>  $estado_envio,
            "estado_mensaje"    =>  $estado_envio_mensaje,
            "descripcion"   =>  $descripcion,
            "nota"          =>  $nota,
            "codigo_error"  =>  str_replace("soap-env:Client.", "", $codigo ) ,
            "mensaje_error" =>  $mensaje,
            "http_code"     =>  $http_code,
            "output"        =>  $output
        );

        return $estado_envio;
    }

    //enviar los XML de resumen de de comprobantes y resumen de anulaciones (RC, RA)
    public function enviar_resumen($emisor, $nombreXML, $ruta_certificado = 'certificado_digital/', $ruta_archivo_xml = 'xml/', $ruta_archivo_cdr = 'cdr/')
    {
        $estado_envio = 0;

        //FIRMAR XML DIGITALMENTE
        require_once('signature.php');
        $objFirma = new Signature();
        $flgfirma = 0; //ubicación del XML donde se firmara digitalmente
        $ruta_certificado = $ruta_certificado . 'certificado_prueba.pfx';
        $pass_certificado = 'ceti';
        $ruta_xml = $ruta_archivo_xml . $nombreXML . '.XML';

        $objFirma->signature_xml($flgfirma, $ruta_xml, $ruta_certificado, $pass_certificado);
        $estado_envio_mensaje = "XML FIRMADO DIGITALMENTE";
        $estado_envio = 1; //XML FIRMADO DIGITALMENTE

        //COMPRIMIR EL XML EN FORMATO ZIP
        $zip = new ZipArchive();
        $ruta_zip = $ruta_archivo_xml . $nombreXML . '.ZIP';

        if ($zip->open($ruta_zip, ZipArchive::CREATE) == TRUE) {
            $zip->addFile($ruta_xml, $nombreXML . '.XML');
            $zip->close();
        }
        $estado_envio_mensaje = "XML COMPRIMIDO EN FORMATO ZIP";
        $estado_envio = 2; //XML COMPRIMIDO EN FORMATO ZIP

        //CODIFICAR EL ZIP EN BASE 64
        $zip_codificado = base64_encode(file_get_contents($ruta_zip));
        //echo $zip_codificado;
        $estado_envio_mensaje = "CODIFICADO EL ZIP EN BASE 64";
        $estado_envio = 3;

        //CONSUMO DEL WEB SERVICE DE SUNAT
        $url_ws = "https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService"; //beta de prueba
        //$url_ws = "https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService"; //produccion

        $filename_zip = $nombreXML . '.ZIP';

        $xml_envelope = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
        xmlns:ser="http://service.sunat.gob.pe" xmlns:wsse="http://docs.oasisopen.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
            <soapenv:Header>
                <wsse:Security>
                    <wsse:UsernameToken>
                        <wsse:Username>' . $emisor['nrodoc'] . $emisor['usuario_secundario'] . '</wsse:Username>
                        <wsse:Password>' . $emisor['clave_usuario_secundario'] . '</wsse:Password>
                    </wsse:UsernameToken>
                </wsse:Security>
            </soapenv:Header>
            <soapenv:Body>
                <ser:sendSummary>
                    <fileName>' . $filename_zip . '</fileName>
                    <contentFile>' . $zip_codificado . '</contentFile>
                </ser:sendSummary>
            </soapenv:Body>
        </soapenv:Envelope>';

        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $url_ws); //INDICAMOS LA RUTA DEL WEB SERVICE DE SUNAT

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_envelope); //enviamos el XML ENVELOPE

        // $output contains the output string
        $output = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); //obtenemos el codigo de rpta

        $estado_envio_mensaje = "CONSUMO DEL WEB SERVICE DE SUNAT";
        $estado_envio = 4;


        //RESPUESTA O RECEPCION DEL WS
        $descripcion = "";
        $nota = "";
        $codigo = "";
        $mensaje = "";
        $ticket = 0;

        if ($http_code == 200) { //ok
            $doc = new DOMDocument();
            $doc->loadXML($output); //convertimos (xml) y cargamos la respuesta de SUNAT

            //VALIDAMOS QUE CONTENGA LA ETIQUETA TICKET EN LA RTPA DE SUNAT
            if (isset($doc->getElementsByTagName('ticket')->item(0)->nodeValue)) {
                
                $ticket = $doc->getElementsByTagName('ticket')->item(0)->nodeValue;
                $estado_envio_mensaje = "OBTUVIMOS EL NRO DE TICKET: " . $ticket;
                $estado_envio = 5;
                
            }else{
                $codigo = $doc->getElementsByTagName("faultcode")->item(0)->nodeValue;
                $mensaje = $doc->getElementsByTagName("faultstring")->item(0)->nodeValue;
                $estado_envio_mensaje = "ERROR/RECHAZADO DE SUNAT";
                $estado_envio = 10;
            }
        }else{
            curl_error($ch);
            $estado_envio_mensaje = "Error en consumo del WS/Red/Conexion";
            $estado_envio = 9;

            $doc = new DOMDocument();
            $doc->loadXML($output); //convertimos (xml) y cargamos la respuesta de SUNAT
            $codigo = $doc->getElementsByTagName("faultcode")->item(0)->nodeValue;
            $mensaje = $doc->getElementsByTagName("faultstring")->item(0)->nodeValue;

            $output = "Error en consumo del WS/Red/Conexion: " . $output;            
        }

        $estado_envio = array(
            "estado"            =>  $estado_envio,
            "estado_mensaje"    =>  $estado_envio_mensaje,
            "descripcion"   =>  $descripcion,
            "nota"          =>  $nota,
            "codigo_error"  =>  str_replace("soap-env:Client.", "", $codigo ) ,
            "mensaje_error" =>  $mensaje,
            "http_code"     =>  $http_code,
            "output"        =>  $output,
            "ticket"        =>  $ticket
        );

        return $estado_envio;

    }

    
    public function consultar_ticket($emisor, $cabecera, $ticket, $ruta_archivo_cdr = 'cdr/')
    {
        //CONSUMO DEL WEB SERVICE DE SUNAT
        $url_ws = "https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService"; //beta de prueba
        //$url_ws = "https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService"; //produccion

        $xml_envelope = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/"
        xmlns:ser="http://service.sunat.gob.pe" xmlns:wsse="http://docs.oasisopen.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
            <soapenv:Header>
                <wsse:Security>
                    <wsse:UsernameToken>
                        <wsse:Username>' . $emisor['nrodoc'] . $emisor['usuario_secundario'] . '</wsse:Username>
                        <wsse:Password>' . $emisor['clave_usuario_secundario'] . '</wsse:Password>
                    </wsse:UsernameToken>
                </wsse:Security>
            </soapenv:Header>
            <soapenv:Body>
                <ser:getStatus>
                    <ticket>' . $ticket . '</ticket>
                </ser:getStatus>
            </soapenv:Body>
        </soapenv:Envelope>';

        // create curl resource
        $ch = curl_init();

        // set url
        curl_setopt($ch, CURLOPT_URL, $url_ws); //INDICAMOS LA RUTA DEL WEB SERVICE DE SUNAT

        //return the transfer as a string
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_envelope); //enviamos el XML ENVELOPE

        // $output contains the output string
        $output = curl_exec($ch);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); //obtenemos el codigo de rpta

        $estado_envio_mensaje = "CONSUMO DEL WEB SERVICE DE SUNAT";
        $estado_envio = 4;


        //RESPUESTA O RECEPCION DEL WS
        $descripcion = "";
        $nota = "";
        $codigo = "";
        $mensaje = "";

        $nombreXML = $emisor['nrodoc'] . '-' . $cabecera['tipodoc'] . '-' . $cabecera['serie'] . '-' . $cabecera['correlativo'];
        $filename_zip = $nombreXML . '.ZIP';

        if ($http_code == 200) { //ok
            $doc = new DOMDocument();
            $doc->loadXML($output); //convertimos (xml) y cargamos la respuesta de SUNAT

            //VALIDAMOS QUE CONTENGA LA ETIQUETA CONTENT EN LA RTPA DE SUNAT
            if (isset($doc->getElementsByTagName('content')->item(0)->nodeValue)) {
                
                $cdr = $doc->getElementsByTagName('content')->item(0)->nodeValue;
                $estado_envio_mensaje = "OBTUVIMOS RPTA DE SUNAT-CDR";
                $estado_envio = 5;

                //DECODIFICAR EN BASE 64 (obtenemos el ZIP)
                $cdr = base64_decode($cdr);
                $estado_envio_mensaje = "DECODIFICADO EN BASE 64, OBTUVIMOS EL ZIP";
                $estado_envio = 6;

                //COPIAMOS DE MEMORIA A DISCO EL ZIP
                file_put_contents($ruta_archivo_cdr . 'R-' . $filename_zip, $cdr);
                $estado_envio_mensaje = "ZIP COPIADO A DISCO LOCAL";
                $estado_envio = 7;

                //DESCOMPRIMIR EL ZIP
                $zip = new ZipArchive();
                if ($zip->open($ruta_archivo_cdr . 'R-' . $filename_zip) == TRUE) {
                    $zip->extractTo($ruta_archivo_cdr);
                    $zip->close();


                    $xml_cdr = $ruta_archivo_cdr . 'R-' . $nombreXML . '.XML';
                    $doc_cdr = new DOMDocument();
                    $doc_cdr->loadXML(file_get_contents($xml_cdr));
                    
                    

                    if (isset($doc_cdr->getElementsByTagName('Description')->item(0)->nodeValue)) {
                        $descripcion = $doc_cdr->getElementsByTagName('Description')->item(0)->nodeValue;
                    }

                    if (isset($doc_cdr->getElementsByTagName('Note')->item(0)->nodeValue)) {
                        $nota = $doc_cdr->getElementsByTagName('Note')->item(0)->nodeValue;
                    }
                    $estado_envio_mensaje = "PROCESO TERMINADO CON EXITO";
                    $estado_envio = 8;

                }
            }else{
                $codigo = $doc->getElementsByTagName("faultcode")->item(0)->nodeValue;
                $mensaje = $doc->getElementsByTagName("faultstring")->item(0)->nodeValue;
                $estado_envio_mensaje = "ERROR/RECHAZADO DE SUNAT";
                $estado_envio = 10;
            }
        }else{
            curl_error($ch);
            $estado_envio_mensaje = "Error en consumo del WS/Red/Conexion";
            $estado_envio = 9;

            $doc = new DOMDocument();
            $doc->loadXML($output); //convertimos (xml) y cargamos la respuesta de SUNAT
            $codigo = $doc->getElementsByTagName("faultcode")->item(0)->nodeValue;
            $mensaje = $doc->getElementsByTagName("faultstring")->item(0)->nodeValue;

            $output = "Error en consumo del WS/Red/Conexion: " . $output;            
        }

        $estado_envio = array(
            "estado"            =>  $estado_envio,
            "estado_mensaje"    =>  $estado_envio_mensaje,
            "descripcion"   =>  $descripcion,
            "nota"          =>  $nota,
            "codigo_error"  =>  str_replace("soap-env:Client.", "", $codigo ) ,
            "mensaje_error" =>  $mensaje,
            "http_code"     =>  $http_code,
            "output"        =>  $output
        );

        return $estado_envio;
    }

    function consultarComprobante($emisor, $comprobante)
    {
		try{
            $ws = "https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService";
            $soapUser = "";  
            $soapPassword = "";

            $xml_post_string = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" 
            xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ser="http://service.sunat.gob.pe" 
            xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd">
                <soapenv:Header>
                    <wsse:Security>
                        <wsse:UsernameToken>
                            <wsse:Username>'.$emisor['ruc'].$emisor['usuariosol'].'</wsse:Username>
                            <wsse:Password>'.$emisor['clavesol'].'</wsse:Password>
                        </wsse:UsernameToken>
                    </wsse:Security>
                </soapenv:Header>
                <soapenv:Body>
                    <ser:getStatus>
                        <rucComprobante>'.$emisor['ruc'].'</rucComprobante>
                        <tipoComprobante>'.$comprobante['tipodoc'].'</tipoComprobante>
                        <serieComprobante>'.$comprobante['serie'].'</serieComprobante>
                        <numeroComprobante>'.$comprobante['correlativo'].'</numeroComprobante>
                    </ser:getStatus>
                </soapenv:Body>
            </soapenv:Envelope>';
        
            $headers = array(
                "Content-type: text/xml;charset=\"utf-8\"",
                "Accept: text/xml",
                "Cache-Control: no-cache",
                "Pragma: no-cache",
                "SOAPAction: ",
                "Content-length: " . strlen($xml_post_string),
            ); 			
        
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
            curl_setopt($ch, CURLOPT_URL, $ws);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $xml_post_string); // the SOAP request
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
            //para ejecutar los procesos de forma local en windows
            //enlace de descarga del cacert.pem https://curl.haxx.se/docs/caextract.html
            curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__)."/cacert.pem");

            $response = curl_exec($ch);
            $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            echo var_dump($response);
            
        } catch (Exception $e) {
            echo "SUNAT ESTA FUERA SERVICIO: ".$e->getMessage();
        }
    }

}

?>