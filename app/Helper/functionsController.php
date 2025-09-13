<?php

function strdouble($var) {
    $var = str_replace(".","",$var);
    $var = str_replace(",",".",$var);
    return $var;
}

// -- Funcção valor double para string -- //
function doublestr($var) {
    $var = number_format($var, 2, ',', '.');
    return $var;
}

// -- Funcção string para data -- //
function strdate($data){
    $dia = substr($data, -10, 2);
    $mes = substr($data, -7, 2);
    $ano = substr($data, -4);
    $data = "$ano-$mes-$dia";
    return $data;
}

// -- Funcção data para string -- //
function datestr($data){
    $dia = substr($data, -2);
    $mes = substr($data, -5, 2);
    $ano = substr($data, -10, 4);
    $data = "$dia/$mes/$ano";
    return $data;
}

function formatNumberAccount($var){
    $var = str_replace(".","",$var);
    $var = str_replace("-","",$var);
    $var_temp1 = substr($var,0,-1);
    $var_temp2 = substr($var,-1);
    $var = $var_temp1."-".$var_temp2;

    return $var;
}

function formatCPF($cpf_cnpj){
    /*
        Pega qualquer CPF e CNPJ e formata

        CPF: 000.000.000-00
        CNPJ: 00.000.000/0000-00
    */

    ## Retirando tudo que não for número.
    $cpf_cnpj = preg_replace("/[^0-9]/", "", $cpf_cnpj);
    $tipo_dado = NULL;
    if(strlen($cpf_cnpj)==11){
        $tipo_dado = "cpf";
    }
    if(strlen($cpf_cnpj)==14){
        $tipo_dado = "cnpj";
    }
    switch($tipo_dado){
        default:
            $cpf_cnpj_formatado = "Não foi possível definir tipo de dado";
        break;

        case "cpf":
            $bloco_1 = substr($cpf_cnpj,0,3);
            $bloco_2 = substr($cpf_cnpj,3,3);
            $bloco_3 = substr($cpf_cnpj,6,3);
            $dig_verificador = substr($cpf_cnpj,-2);
            $cpf_cnpj_formatado = $bloco_1.".".$bloco_2.".".$bloco_3."-".$dig_verificador;
        break;

        case "cnpj":
            $bloco_1 = substr($cpf_cnpj,0,2);
            $bloco_2 = substr($cpf_cnpj,2,3);
            $bloco_3 = substr($cpf_cnpj,5,3);
            $bloco_4 = substr($cpf_cnpj,8,4);
            $digito_verificador = substr($cpf_cnpj,-2);
            $cpf_cnpj_formatado = $bloco_1.".".$bloco_2.".".$bloco_3."/".$bloco_4."-".$digito_verificador;
        break;
    }
    return $cpf_cnpj_formatado;
}




?>
