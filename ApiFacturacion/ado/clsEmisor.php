<?php

require_once('conexion.php');

class clsEmisor{

    public function consultarListaEmisores()
    {
        $sql = "SELECT * FROM emisor";
        global $cnx;
        return $cnx->query($sql);
    }

    public function obtenerEmisor($id)
    {
        $sql = "SELECT * FROM emisor WHERE id =:id";
        global $cnx;

        $parametros = array(
            ':id' =>    $id
        );
        $pre = $cnx->prepare($sql);
        $pre->execute($parametros);
        return $pre;
    }

}

?>