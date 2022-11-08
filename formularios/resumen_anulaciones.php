<?php

require_once('../ApiFacturacion/ado/clsEmisor.php');
require_once('../ApiFacturacion/ado/clsVenta.php');

$objVenta = new clsVenta();
$objEmisor = new clsEmisor();

$listado = $objEmisor->consultarListaEmisores();

$listadoFacturas = $objVenta->listarComprobantePorTipo('01');

?>

<div class="col-12">
    <div class="row">
        <div class="card">
            <form id="frmResumen" name="frmResumen" submit="return false">
                <div class="col-md-12">
                    <div class="form-group">
                        <label>Envío de bajas de facturas / Resumen de anulaciones</label>
                        </br>
                        <label>Emisor</label>
                        <select name="idemisor" id="idemisor" class="form-control">
                            <?php
                                foreach ($listado as $key => $value) { ?>
                                    <option value="<?php echo $value['id'] ?>"><?php echo $value['razon_social'] ?>
                                    </option>
                            <?php } ?>
                        </select>
                    </div>

                    <input type="hidden" name="accion" id="accion" value="ENVIO_BAJAS">
                    <input type="hidden" name="ids" id="ids" value="0">
                    <table class="table table-hover table-sm">
                        <thead>
                            <tr>
                                <th>*</th>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Serie</th>
                                <th>Correlativo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($fila = $listadoFacturas->fetch(PDO::FETCH_NAMED)){ ?>
                            <tr>
                                <td><input type="checkbox" name="documento[]" value="<?php echo $fila['id'];?>" />
                                </td>
                                <td><?php echo $fila['id'];?></td>
                                <td><?php echo $fila['fecha_emision'];?></td>
                                <td><?php echo $fila['serie'];?></td>
                                <td><?php echo $fila['correlativo'];?></td>
                            </tr>
                            <?php } ?>
                        </tbody>
                        
                    </table>

                    <div align="right" class="col-md-12">
                        <button class="btn btn-primary" type="button" onclick="EnviarResumenComprobantes()">Enviar Comprobantes</button>
                    </div>
                    <div id="divResultado">
                        
                    </div>
                </div>
            </form>
        </div>        
    </div>
</div>


<script>
	function EnviarResumenComprobantes(){
	  	var datax = $("#frmResumen").serializeArray();

		$.ajax({
	      method: "POST",
	      url: 'apifacturacion/controlador/controlador.php',
	      data: datax
	  	})
	  	.done(function( html ) {
	        $("#divResultado").html(html);
	  	}); 		
	}
</script>
