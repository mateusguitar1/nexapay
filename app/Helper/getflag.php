<?php
function getFlag($flag,$name = null,$width=30){
	switch($flag){
		case "001":
			$icon = "<image src='".asset("img/banco-do-brasil.png")."' width='".$width."'/>";
			$text = 'banco-do-brasil';
		break;
		case "1":
			$icon = "<image src='".asset("img/banco-do-brasil.png")."' width='".$width."'/>";
			$text = 'banco-do-brasil';
		break;
        case "033":
			if(strtolower($name) == "cielo"){
				$icon = "<image src='".asset("img/cielo.png")."' width='".$width."'/>";
				$text = 'cielo';
			}else{
				$icon = "<image src='".asset("img/santander.png")."' width='".$width."'/>";
				$text = 'santander';
			}
		break;
		case "197":
			$icon = "<image src='".asset("img/stone.png")."' width='".$width."'/>";
			$text = 'stone';
		break;
        case "104":
			if(strtolower($name) == "cielo"){
				$icon = "<image src='".asset("img/cielo.png")."' width='".$width."'/>";
				$text = 'cielo';
			}else{
				$icon = "<image src='".asset("img/caixa.png")."' width='".$width."'/>";
				$text = 'caixa';
			}
		break;
        case "145":
			$icon = "<image src='".asset("img/ame.png")."' width='".$width."'/>";
			$text = 'ame';
		break;
        case "212":
			$icon = "<image src='".asset("img/original.png")."' width='".$width."'/>";
			$text = 'original';
		break;
        case "218":
			$icon = "<image src='".asset("img/bs2.png")."' width='".$width."'/>";
			$text = 'bs2';
		break;
        case "219":
			$icon = "<image src='".asset("img/genial.png")."' width='".$width."'/>";
			$text = 'genial';
		break;
		case "220":
			$icon = "<image src='".asset("img/gerencianet.png")."' width='".$width."'/>";
			$text = 'gerencianet';
		break;
        case "221":
			$icon = "<image src='".asset("img/openpix.png")."' width='".$width."'/>";
			$text = 'open-pix';
		break;
        case "222":
			$icon = "<image src='".asset("img/shipay.png")."' width='".$width."'/>";
			$text = 'shipay';
		break;
        case "223":
			$icon = "<image src='".asset("img/paghiper.png")."' width='".$width."'/>";
			$text = 'paghiper';
		break;
		case "237":
			if(strtolower($name) == "cielo"){
				$icon = "<image src='".asset("img/cielo.png")."' width='".$width."'/>";
				$text = 'cielo';
			}else{
				$icon = "<image src='".asset("img/bradesco.png")."' width='".$width."'/>";
				$text = 'bradesco';
			}
		break;
        case "330":
			$icon = "<image src='".asset("img/facilpay.png")."' width='".$width."'/>";
			$text = 'facilpay';
		break;
		case "323":
			$icon = "<image src='".asset("img/mercado-pago.png")."' width='".$width."'/>";
			$text = 'mercado-pago';
		break;
        case "341":
			$icon = "<image src='".asset("img/itau.png")."' width='".$width."'/>";
			$text = 'itau';
		break;
        case "336":
			$icon = "<image src='".asset("img/c6.png")."' width='".$width."'/>";
			$text = 'c6';
		break;
        case "403":
			$icon = "<image src='".asset("img/cora.png")."' width='".$width."'/>";
			$text = 'cora';
		break;
        case "461":
			$icon = "<image src='".asset("img/asaas.png")."' width='".$width."'/>";
			$text = 'asaas';
		break;
		case "487":
			$icon = "<image src='".asset("img/credpay.png")."' width='".$width."'/>";
			$text = 'credpay';
		break;
        case "544":
			$icon = "<image src='".asset("img/coinext.png")."' width='".$width."'/>";
			$text = 'coinext';
            break;
        case "587":
            $icon = "<image src='".asset("img/celcoin.png")."' width='".$width."'/>";
            $text = 'celcoin';
        break;
        case "588":
			$icon = "<image src='".asset("img/voluti.png")."' width='".$width."'/>";
			$text = 'voluti';
		break;
        case "598":
			$icon = "<image src='".asset("img/udibank.png")."' width='".$width."'/>";
			$text = 'udibank';
		break;
        case "763":
			$icon = "<image src='".asset("img/pagseguro.png")."' width='".$width."'/>";
			$text = 'pagseguro';
		break;
        case "787":
			$icon = "<image src='".asset("img/suitpay.png")."' width='".$width."'/>";
			$text = 'suitpay';
		break;
        case "844":
			$icon = "<image src='".asset("img/hubapi.png")."' width='".$width."'/>";
			$text = 'hubapi';
		break;
        case "845":
			$icon = "<image src='".asset("img/voluti.png")."' width='".$width."'/>";
			$text = 'voluti';
		break;
        case "846":
			$icon = "<image src='".asset("img/luxtak.png")."' width='".$width."'/>";
			$text = 'luxtak';
		break;
		case "9999":
			$icon = "<image src='".asset("img/fast-bank.png")."' width='".$width."'/>";
			$text = 'fast-bank';
		break;

		default:
			$icon = "<image src='".asset("img/fast-bank.png")."' width='".$width."'/>";
			$text = 'fast-bank';
		break;
	}

	return ( (int)$width > 0 ? $icon : $text );
}
