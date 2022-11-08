<div class="col-12 mt-4">
    <!-- general form elements -->
    <div class="card card-primary">
        <div class="card-header">
        <h3 class="card-title">EMITIR FACTURA ELECTRONICA</h3>
        </div>
        <!-- /.card-header -->
        <!-- form start -->
        <form id="frmVenta" submit="return false">
            <input type="hidden" name="accion" id="accion" value="GUARDAR_VENTA">

            <div class="card-body">

                <div class="row">
                    <div class="col-4">
                        <div class="form-group">
                            <label for="exampleInputEmail1">Emisor</label>
                            <select name="idemisor" id="idemisor" class="form-control">

                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Moneda</label>
                            <select name="moneda" id="moneda" class="form-control">

                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Fecha Emisión</label>
                            <input type="date" class="form-control" name="fecha_emision" id="fecha_emision" value="<?php echo date('Y-m-d')?>">

                        </div>
                        <div class="form-group">
                            <label>Forma de pago</label>
                            <select name="forma_pago" id="forma_pago" class="form-control">
                                <option value="Contado">Contado</option>
                                <option value="Credito">Crédito</option>
                            </select>

                            <div id="div_monto_pendiente">

                            </div>
                        </div>

                        <div class="form-group">
                            <div id="div_monto_pendiente">

                            </div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label for="exampleInputEmail1">Tipo Comprobante</label>
                            <select name="tipocomp" id="tipocomp" class="form-control" onchange="ConsultarSerie()">

                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Serie</label>
                            <select name="idserie" id="idserie" class="form-control" onchange="ConsultarCorrelativo()">

                            </select>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Correlativo</label>
                            <input type="number" class="form-control" name="correlativo" id="correlativo" readonly>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputEmail1">Cuotas</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="cuotas" id="cuotas" min="1">
                                <div class="input-group-addon">
                                    <button type="button" class="btn btn-default" onclick="GenerarCuotas()">
                                        <li class="fas fa-plus" title="Generar Cuotas"></li>
                                    </button>
                                </div>
                            </div>
                            

                        </div>
                    </div>
                    <div class="col-4">
                        <div class="form-group">
                            <label for="exampleInputEmail1">Tipo Documento</label>
                            <select name="tipodoc" id="tipodoc" class="form-control">

                            </select>
                        </div>

                        <div class="form-group">
                            <label for="exampleInputEmail1">NRO DOCUMENTO</label>
                            <div class="input-group">
                                <input type="text" name="nrodoc" id="nrodoc" class="form-control">
                                <div class="input-group-addon">
                                    <button type="button" class="btn btn-default" onclick="ObtenerDatosEmpresa()">
                                        <li class="fas fa-search" title="Buscar"></li>
                                    </button>
                                </div>
                            </div>
                            
                        </div>

                        <div class="form-group">
                            <label for="exampleInputEmail1">RAZON SOCIAL</label>
                            <input type="text" class="form-control" name="razon_social" id="razon_social">
                        </div>

                        <div class="form-group">
                            <label for="exampleInputEmail1">DIRECCION</label>
                            <input type="text" class="form-control" name="direccion" id="direccion">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-4">
                        <table class="table table-hover table-sm">
                            <thead class="text-center">
                                <th>Cuota</th>
                                <th>Fecha</th>
                                <th>Monto</th>
                            </thead>
                            <tbody id="div_cuotas">

                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="row">
                    <div class="col-6">
                        <div class="form-group">
                            <div class="input-group">
                                <input type="search" class="form-control" name="producto" id="producto" placeholder="Buscar producto..">
                                <div class="input-group-addon">
                                    <button type="button" class="btn btn-default" onclick="BuscarProducto()">
                                        <li class="fa fa-search"></li>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <table class="table table-hover table-sm">
                                    <thead class="text-center">
                                        <th>Codigo</th>
                                        <th>Producto</th>
                                        <th>Valor Unitario</th>
                                        <th>Cantidad</th>
                                        <th>
                                            <button type="button" class="btn btn-default">+</button>
                                        </th>
                                    </thead>
                                    <tbody id = "div_productos">

                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="col-12" id="div_carrito">

                        </div>
                    </div>
                </div>

            </div>
            <!-- /.card-body -->

            <div class="card-footer">
                <button type="button" class="btn btn-primary" onclick="Guardar()"><i class="fa fa-save"></i> Guardar</button>

                <button type="button" class="btn btn-danger" onclick="Cancelar()"><i class="fa fa-trash-alt"></i> Cancelar</button>
            </div>
        </form>
    </div>
    <!-- /.card -->

</div>


<script>

    $("#tipocomp").val('01'); //valor por defecto de factura 01
    ConsultarSerie();
    listar_emisores();
    listar_monedas();
    listar_comprobantes();
    listar_documentos();
    

    function listar_emisores(){
        $.ajax({
            method: "POST",
            url: "apifacturacion/controlador/controlador.php",
            data: {
                "accion" : "LISTAR_EMISORES"
            }
        }).done(function(text){
            json = JSON.parse(text);
            emisores = json.emisores;
            options = '';

            for (i = 0; i < emisores.length; i++) {
                options = options + '<option value="' + emisores[i].id + '">' + emisores[i].razon_social + '</option>';                
            }

            $('#idemisor').html(options);
        })
    }

    function listar_monedas(){
        $.ajax({
            method: "POST",
            url: "apifacturacion/controlador/controlador.php",
            data: {
                "accion" : "LISTAR_MONEDAS"
            }
        }).done(function(text){
            json = JSON.parse(text);
            listado = json.listado;
            options = '';

            for (i = 0; i < listado.length; i++) {
                options = options + '<option value="' + listado[i].codigo + '">' + listado[i].descripcion + '</option>';                
            }

            $('#moneda').html(options);
        })
    }

    function listar_comprobantes(){
        $.ajax({
            method: "POST",
            url: "apifacturacion/controlador/controlador.php",
            data: {
                "accion" : "LISTAR_COMPROBANTES",
                "tipo" : "01"
            }
        }).done(function(text){
            json = JSON.parse(text);
            listado = json.listado;
            options = '';

            for (i = 0; i < listado.length; i++) {
                options = options + '<option value="' + listado[i].codigo + '">' + listado[i].descripcion + '</option>';                
            }

            $('#tipocomp').html(options);
        })
    }

    function listar_documentos(){
        $.ajax({
            method: "POST",
            url: "apifacturacion/controlador/controlador.php",
            data: {
                "accion" : "LISTAR_DOCUMENTOS",
                "tipo" : "6"
            }
        }).done(function(text){
            json = JSON.parse(text);
            listado = json.listado;
            options = '';

            for (i = 0; i < listado.length; i++) {
                options = options + '<option value="' + listado[i].codigo + '">' + listado[i].descripcion + '</option>';                
            }

            $('#tipodoc').html(options);
        })
    }

    function ConsultarSerie()
    {
        $.ajax(
            {
                method: "POST",
                url: "apifacturacion/controlador/controlador.php",
                data: {
                    "accion": "LISTAR_SERIES",
                    "tipocomp": "01"
                }
            }
        ).done(function(text){
            json = JSON.parse(text);
            series = json.series;
            options = '';
            for ( i = 0; i < series.length; i++) {
                options = options + '<option value="' + series[i].id + '">' + series[i].serie + '</option>';    
            }
            $('#idserie').html(options);
            ConsultarCorrelativo();            
        });
    }

    function ConsultarCorrelativo(){
        $.ajax({
            method: "POST",
            url: "apifacturacion/controlador/controlador.php",
            data: {
                "accion": "OBTENER_CORRELATIVO",
                "idserie": $('#idserie').val()
            }
        }).done(function(correlativo){
            $('#correlativo').val(correlativo);
        })
    }

    function ObtenerDatosEmpresa(){
        tipodoc = $('#tipodoc').val();
        if (tipodoc == 1) {
            ObtenerDatosDNI();
        }else if(tipodoc == 6){
            ObtenerDatosRUC();
        } 
    }

    function ObtenerDatosDNI(){
        $.ajax({
            method: "POST",
            url: "apifacturacion/controlador/controlador.php",
            data: {
                "accion": "CONSULTA_DNI",
                "dni": $('#nrodoc').val()
            }
        }).done(function(text){
            json = JSON.parse(text);
            $('#razon_social').val(json.result.Nombre + ' ' + json.result.Paterno + ' ' + json.result.Materno);
            $('#direccion').val('');
        })
    }

    function ObtenerDatosRUC(){
        $.ajax({
            method: "POST",
            url: "apifacturacion/controlador/controlador.php",
            data: {
                "accion": "CONSULTA_RUC",
                "ruc": $('#nrodoc').val()
            }
        }).done(function(text){
            json = JSON.parse(text);
            $('#razon_social').val(json.result.razon_social);
            $('#direccion').val('');
        })
    }

    function BuscarProducto(){
        $.ajax({
            method: "POST",
            url: "apifacturacion/controlador/controlador.php",
            data: {
                "accion": "BUSCAR_PRODUCTO",
                "filtro": $('#producto').val()
            }
        }).done(function(resultado){
            json = JSON.parse(resultado);
            productos = json.productos;
            listado = '';
            for(i = 0; i < productos.length; i++){
                listado = listado + '<tr><td>'+productos[i].codigo+'</td><td>'+productos[i].nombre+'</td><td>'+productos[i].precio+'</td><td><input class="form-control input-sm" id="txtCantidad'+productos[i].codigo+'" value="1" type="number" min="1" /></td><td><button type="button" class="btn btn-primary btn-sm" onclick="AgregarCarrito('+productos[i].codigo+')"> + </button></td></tr>';
            }

            $('#div_productos').html(listado);
        })
    }

    function AgregarCarrito(codigo){
        $.ajax({
            method: "POST",
            url: "apifacturacion/controlador/controlador.php",
            data: {
                "accion": "ADD_PRODUCTO",
                "codigo": codigo,
                "cantidad": $('#txtCantidad' + codigo).val()
            }
        }).done(function(resultado){
            $('#div_carrito').html(resultado);
        })
    }

    function Cancelar(){
        $.ajax({
            method: "POST",
            url: "apifacturacion/controlador/controlador.php",
            data: {
                "accion": "CANCELAR_CARRITO"
            }
        }).done(function(resultado){
            $('#div_carrito').html(resultado);
        })
    }

    function Guardar(){
        var datax = $('#frmVenta').serializeArray();
        $.ajax({
            method: "POST",
            url: "apifacturacion/controlador/controlador.php",
            data: datax
        }).done(function(resultado){
            $('#div_carrito').html(resultado);
        })
    }

    function GenerarCuotas(){
        listado = '';
        cuotas = $('#cuotas').val()
        for (let i = 1; i <= cuotas; i++) {
            listado = listado + '<tr><td><input class="form-control input-sm" name="txtCuota' + i +'" type="text" value="Cuota ' + i + '" readonly/></td>'
                        + '<td><input class="form-control input-sm" name="txtFecha' + i +'" type="date"/></td>'
                        + '<td><input class="form-control input-sm" name="txtMonto' + i +'" type="number"/></td></tr>';
        }

        $('#div_cuotas').html(listado);

        if (cuotas > 0) {
            monto_pendiente = '<div class="form-group"><label>Monto pendiente</label><input class="form-control" type="number" name="monto_pendiente" id="monto_pendiente" value="0.0" /></div>';
            $('#div_monto_pendiente').html(monto_pendiente);
        }
    }



</script>