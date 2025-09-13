<?php
function clearDocument($valor){
    $valor = trim($valor);
    $valor = str_replace(".", "", $valor);
    $valor = str_replace(",", "", $valor);
    $valor = str_replace("-", "", $valor);
    $valor = str_replace("/", "", $valor);

    if(strlen($valor) == 9){
        $valor = "00".$valor;
    }elseif(strlen($valor == 10)){
        $valor = "0".$valor;
    }

    return $valor;
}
?>
