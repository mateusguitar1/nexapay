<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FunctionsBBController extends Controller
{
    //

    public function formata_numero($numero,$loop,$insert,$tipo = "geral") {
        if ($tipo == "geral") {
            $numero = str_replace(",","",$numero);
            while(strlen($numero)<$loop){
                $numero = $insert . $numero;
            }
        }
        if ($tipo == "valor") {
            /*
            retira as virgulas
            formata o numero
            preenche com zeros
            */
            $numero = str_replace(",","",$numero);
            while(strlen($numero)<$loop){
                $numero = $insert . $numero;
            }
        }
        if ($tipo == "convenio") {
            while(strlen($numero)<$loop){
                $numero = $numero . $insert;
            }
        }
        return $numero;
    }

    public function esquerda($entra,$comp){
        return substr($entra,0,$comp);
    }

    public function direita($entra,$comp){
        return substr($entra,strlen($entra)-$comp,$comp);
    }

    public function fator_vencimento($data) {
        $data = explode("/",$data);
        $ano = $data[2];
        $mes = $data[1];
        $dia = $data[0];
        return(abs(($this->_dateToDays("1997","10","07")) - ($this->_dateToDays($ano, $mes, $dia))));
    }

    public function _dateToDays($year,$month,$day) {
        $century = substr($year, 0, 2);
        $year = substr($year, 2, 2);
        if ($month > 2) {
            $month -= 3;
        } else {
            $month += 9;
            if ($year) {
                $year--;
            } else {
                $year = 99;
                $century --;
            }
        }

        return ( floor((  146097 * $century)    /  4 ) +
                floor(( 1461 * $year)        /  4 ) +
                floor(( 153 * $month +  2) /  5 ) +
                    $day +  1721119);
    }

    public function modulo_10($num) { 
        $numtotal10 = 0;
        $fator = 2;
    
        for ($i = strlen($num); $i > 0; $i--) {
            $numeros[$i] = substr($num,$i-1,1);
            $parcial10[$i] = $numeros[$i] * $fator;
            $numtotal10 .= $parcial10[$i];
            if ($fator == 2) {
                $fator = 1;
            }
            else {
                $fator = 2; 
            }
        }
        
        $soma = 0;
        for ($i = strlen($numtotal10); $i > 0; $i--) {
            $numeros[$i] = substr($numtotal10,$i-1,1);
            $soma += $numeros[$i]; 
        }
        $resto = $soma % 10;
        $digito = 10 - $resto;
        if ($resto == 0) {
            $digito = 0;
        }

        return $digito;
    }

    public function modulo_11($num, $base=9, $r=0) {
        $soma = 0;
        $fator = 2; 
        for ($i = strlen($num); $i > 0; $i--) {
            $numeros[$i] = substr($num,$i-1,1);
            $parcial[$i] = $numeros[$i] * $fator;
            $soma += $parcial[$i];
            if ($fator == $base) {
                $fator = 1;
            }
            $fator++;
        }
        if ($r == 0) {
            $soma *= 10;
            $digito = $soma % 11;
            
            //corrigido
            if ($digito == 10) {
                $digito = "X";
            }
            
            if (strlen($num) == "43") {
                //ent�o estamos checando a linha digit�vel
                if ($digito == "0" or $digito == "X" or $digito > 9) {
                        $digito = 1;
                }
            }
            return $digito;
        } 
        elseif ($r == 1){
            $resto = $soma % 11;
            return $resto;
        }
    }

    public function calcula_codigo_de_barras($codigo){
        
        $p1 = substr($codigo,0,4);
        $campo1 = $p1;

        $p1 = substr($codigo,32,1);
        $campo2 = $p1;

        $p1 = substr($codigo,33,4);
        $campo3 = $p1;

        $p1 = substr($codigo,37,19);
        $campo4 = $p1;

        $p1 = substr($codigo,4,5);
        $campo5 = $p1;
        
        $p1 = substr($codigo,10,10);
        $campo6 = $p1;
        
        $p1 = substr($codigo,21,10);
        $campo7 = $p1;

        $final = $campo1.$campo2.$campo3.$campo4.$campo5.$campo6.$campo7;
        
        return $final; 
        
    }

    public function monta_linha_digitavel($codigo) {
    
        $p1 = substr($codigo,0,5);
        $p2 = substr($codigo,5,6);
        $campo1 = $p1.".".$p2;
        
        $p1 = substr($codigo,11,5);
        $p2 = substr($codigo,16,6);
        $campo2 = $p1.".".$p2;
        
        $p1 = substr($codigo,22,5);
        $p2 = substr($codigo,27,6);
        $campo3 = $p1.".".$p2;
        
        $p1 = substr($codigo,33,1);
        $campo4 = $p1;
        
        $p1 = substr($codigo,34,14);
        $campo5 = $p1;
        
        return "$campo1 $campo2 $campo3 $campo4 $campo5"; 
    }

    public function geraCodigoBanco($numero) {
        $parte1 = substr($numero, 0, 3);
        $parte2 = $this->modulo_11($parte1);
        return $parte1 . "-" . $parte2;
    }
}
