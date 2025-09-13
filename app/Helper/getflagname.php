<?php
function getflagname($flag,$name = null,$width=30){
	switch($flag){
		case "001":
			$icon = "banco-do-brasil.png";
		break;
		case "1":
			$icon = "banco-do-brasil.png";
		break;
        case "033":
			if(strtolower($name) == "cielo"){
				$icon = "cielo.png";
			}else{
				$icon = "santander.png";
			}
		break;
		case "197":
			$icon = "stone.png";
		break;
        case "104":
			if(strtolower($name) == "cielo"){
				$icon = "cielo.png";
			}else{
				$icon = "caixa.png";
			}
		break;
        case "145":
			$icon = "ame.png";
		break;
        case "212":
			$icon = "original.png";
		break;
        case "218":
			$icon = "bs2.png";
		break;
        case "219":
			$icon = "genial.png";
		break;
		case "220":
			$icon = "gerencianet.png";
		break;
        case "221":
			$icon = "openpix.png";
		break;
        case "222":
			$icon = "shipay.png";
		break;
        case "223":
			$icon = "paghiper.png";
		break;
		case "237":
			if(strtolower($name) == "cielo"){
				$icon = "cielo.png";
			}else{
				$icon = "bradesco.png";
			}
		break;
        case "330":
			$icon = "facilpay.png";
		break;
		case "323":
			$icon = "mercado-pago.png";
		break;
        case "341":
			$icon = "itau.png";
		break;
        case "336":
			$icon = "c6.png";
		break;
        case "403":
			$icon = "cora.png";
		break;
        case "461":
			$icon = "asaas.png";
		break;
		case "487":
			$icon = "credpay.png";
		break;
        case "587":
			$icon = "celcoin.png";
		break;
        case "544":
			$icon = "coinext.png";
		break;
        case "763":
			$icon = "pagseguro.png";
		break;
		case "9999":
			$icon = "fast-bank.png";
		break;

		default:
			$icon = "fast-bank.png";
		break;
	}

	return ( (int)$width > 0 ? $icon : $text );
}
