<?php

function esquerda($entra,$comp){
	return substr($entra,0,$comp);
}

function direita($entra,$comp){
	return substr($entra,strlen($entra)-$comp,$comp);
}

function fbarcode($valor){

$fino = 1 ;
$largo = 3 ;
$altura = 50 ;

	$barcodes[0] = "00110" ;
	$barcodes[1] = "10001" ;
	$barcodes[2] = "01001" ;
	$barcodes[3] = "11000" ;
	$barcodes[4] = "00101" ;
	$barcodes[5] = "10100" ;
	$barcodes[6] = "01100" ;
	$barcodes[7] = "00011" ;
	$barcodes[8] = "10010" ;
	$barcodes[9] = "01010" ;
	for($f1=9;$f1>=0;$f1--){
	for($f2=9;$f2>=0;$f2--){
		$f = ($f1 * 10) + $f2 ;
		$texto = "" ;
		for($i=1;$i<6;$i++){
		$texto .=  substr($barcodes[$f1],($i-1),1) . substr($barcodes[$f2],($i-1),1);
		}
		$barcodes[$f] = $texto;
	}
	}


//Desenho da barra


//Guarda inicial
?>
<img src={{ asset('img/p.png') }} width=<?php echo $fino?> height=<?php echo $altura?> border=0><img
src={{ asset('img/b.png') }} width=<?php echo $fino?> height=<?php echo $altura?> border=0><img
src={{ asset('img/p.png') }} width=<?php echo $fino?> height=<?php echo $altura?> border=0><img
src={{ asset('img/b.png') }} width=<?php echo $fino?> height=<?php echo $altura?> border=0><img
<?php
$texto = $valor ;
if((strlen($texto) % 2) <> 0){
	$texto = "0" . $texto;
}

// Draw dos dados
while (strlen($texto) > 0) {
	$i = round(esquerda($texto,2));
	$texto = direita($texto,strlen($texto)-2);
	$f = $barcodes[$i];
	for($i=1;$i<11;$i+=2){
	if (substr($f,($i-1),1) == "0") {
		$f1 = $fino ;
	}else{
		$f1 = $largo ;
	}
?>
	src={{ asset('img/p.png') }} width=<?php echo $f1?> height=<?php echo $altura?> border=0><img
<?php
	if (substr($f,$i,1) == "0") {
		$f2 = $fino ;
	}else{
		$f2 = $largo ;
	}
?>
	src={{ asset('img/b.png') }} width=<?php echo $f2?> height=<?php echo $altura?> border=0><img
<?php
	}
}

// Draw guarda final
?>
src={{ asset('img/p.png') }} width=<?php echo $largo?> height=<?php echo $altura?> border=0><img
src={{ asset('img/b.png') }} width=<?php echo $fino?> height=<?php echo $altura?> border=0><img
src={{ asset('img/p.png') }} width=<?php echo 1?> height=<?php echo $altura?> border=0>
	<?php
} //Fim da fun��o
?>
<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">

<title><?=$dadosboleto["identificacao"];?></title>
<meta http-equiv="Content-Type" content="text/html" charset="ISO-8859-1">
<meta name="Generator" content="<?=$dadosboleto["identificacao"];?>">

<style type="text/css">
<!--
.ti {font: 9px Arial, Helvetica, sans-serif}
-->
</style>
<style type="text/css">
@page  {
  margin: 0 !important;
  size: letter !important; /*or width x height 150mm 50mm*/
}
.backpack.dropzone {
  font-family: 'SF UI Display', 'Segoe UI';
  font-size: 15px;
  text-align: center;
  display: flex;
  flex-direction: column;
  justify-content: center;
  align-items: center;
  width: 250px;
  height: 150px;
  font-weight: lighter;
  color: white;
  will-change: right;
  z-index: 2147483647;
  bottom: 20%;
  background: #333;
  position: fixed;
  user-select: none;
  transition: left .5s, right .5s;
  right: 0px; }
  .backpack.dropzone .animation {
    height: 80px;
    width: 250px;
    background: url("chrome-extension://lifbcibllhkdhoafpjfnlhfpfgnpldfl/assets/backpack/dropzone/hoverstate.png") left center; }
  .backpack.dropzone .title::before {
    content: 'Save to'; }
  .backpack.dropzone.closed {
    right: -250px; }
  .backpack.dropzone.hover .animation {
    animation: sxt-play-anim-hover 0.91s steps(21);
    animation-fill-mode: forwards;
    background: url("chrome-extension://lifbcibllhkdhoafpjfnlhfpfgnpldfl/assets/backpack/dropzone/hoverstate.png") left center; }

@keyframes sxt-play-anim-hover {
  from {
    background-position: 0px; }
  to {
    background-position: -5250px; } }
  .backpack.dropzone.saving .title::before {
    content: 'Saving to'; }
  .backpack.dropzone.saving .animation {
    background: url("chrome-extension://lifbcibllhkdhoafpjfnlhfpfgnpldfl/assets/backpack/dropzone/saving_loop.png") left center;
    animation: sxt-play-anim-saving steps(59) 2.46s infinite; }

@keyframes sxt-play-anim-saving {
  100% {
    background-position: -14750px; } }
  .backpack.dropzone.saved .title::before {
    content: 'Saved to'; }
  .backpack.dropzone.saved .animation {
    background: url("chrome-extension://lifbcibllhkdhoafpjfnlhfpfgnpldfl/assets/backpack/dropzone/saved.png") left center;
    animation: sxt-play-anim-saved steps(20) 0.83s forwards; }

@keyframes sxt-play-anim-saved {
  100% {
    background-position: -5000px; } }
</style></head>
<body cz-shortcut-listen="true">
<style>

@media screen,print {

/* *** TIPOGRAFIA BASICA *** */

* {
	font-family: Arial;
	font-size: 12px;
	margin: 0;
	padding: 0;
}

.notice {
	color: red;
}


/* *** LINHAS GERAIS *** */

#container {
	width: 666px;
	margin: 0px auto;
	padding: 10px 20px;
	background-color: white;
    overflow: hidden;
}

#instructions {
	margin: 0;
	padding: 0 0 20px 0;
}

#boleto {
	width: 666px;
	margin: 0;
	padding: 0;
}


/* *** CABECALHO *** */

#instr_header {

	padding-left: 160px;
	height: 65px;
}

#instr_header h1 {
	font-size: 16px;
	margin: 5px 0px;
}

#instr_header address {
	font-style: normal;
}

#instr_content {

}

#instr_content h2 {
	font-size: 10px;
	font-weight: bold;
}

#instr_content p {
	font-size: 10px;
	margin: 4px 0px;
}

#instr_content ol {
	font-size: 10px;
	margin: 5px 0;
}

#instr_content ol li {
	font-size: 10px;
	text-indent: 10px;
	margin: 2px 0px;
	list-style-position: inside;
}

#instr_content ol li p {
	font-size: 10px;
	padding-bottom: 4px;
}


/* *** BOLETO *** */

#boleto .cut {
	width: 666px;
	margin: 0px auto;
	border-bottom: 1px #000 dashed;
}

#boleto .cut p {
	margin: 0 0 5px 0;
	padding: 0px;
	font-family: 'Arial Narrow';
	font-size: 9px;
	color: #000;
}

table.header {
	width: 666px;
	height: 38px;
	margin-top: 20px;
	margin-bottom: 10px;
	border-bottom: 2px #000 solid;

}


table.header div.field_cod_banco {
	width: 46px;
	height: 19px;
  margin-left: 5px;
	padding-top: 3px;
	text-align: center;
	font-size: 14px;
	font-weight: bold;
	color: #000;
	border-right: 2px solid #000;
	border-left: 2px solid #000;
}

table.header td.linha_digitavel {
	width: 464px;
	text-align: right;
	font: bold 15px Arial;
	color: #000
}

table.line {
	margin-bottom: 3px;
	padding-bottom: 1px;
	border-bottom: 1px black solid;
}

table.line tr.titulos td {
	height: 13px;
	font-family: 'Arial Narrow';
	font-size: 9px;
	color: #000;
	border-left: 5px #000000 solid;
	padding-left: 2px;
}

table.line tr.titulos td.noborder{
	border-left:none !important;
}

table.line tr.campos td {
	height: 12px;
	font-size: 10px;
	color: black;
	border-left: 5px #000000  solid;
	padding-left: 2px;
}

table.line tr.campos td.noborder{
	border-left:none !important;
}

table.line td p {
	font-size: 10px;
}


table.line tr.campos td.ag_cod_cedente,
table.line tr.campos td.nosso_numero,
table.line tr.campos td.valor_doc,
table.line tr.campos td.vencimento2,
table.line tr.campos td.ag_cod_cedente2,
table.line tr.campos td.nosso_numero2,
table.line tr.campos td.xvalor,
table.line tr.campos td.valor_doc2
{
	text-align: right;
}

table.line tr.campos td.especie,
table.line tr.campos td.qtd,
table.line tr.campos td.vencimento,
table.line tr.campos td.especie_doc,
table.line tr.campos td.aceite,
table.line tr.campos td.carteira,
table.line tr.campos td.especie2,
table.line tr.campos td.qtd2
{
	text-align: center;
}

table.line td.last_line {
	vertical-align: top;
	height: 25px;
}

table.line td.last_line table.line {
	margin-bottom: -5px;
	border: 0 white none;
}

td.last_line table.line td.instrucoes {
	border-left: 0 white none;
	padding-left: 5px;
	padding-bottom: 0;
	margin-bottom: 0;
	height: 20px;
	vertical-align: top;
}

table.line td.cedente {
	width: 298px;
}

table.line td.valor_cobrado2 {
	padding-bottom: 0;
	margin-bottom: 0;
}


table.line td.ag_cod_cedente {
	width: 126px;
}

table.line td.especie {
	width: 35px;
}

table.line td.qtd {
	width: 53px;
}

table.line td.nosso_numero {
	/* width: 120px; */
	width: 115px;
	padding-right: 5px;
}

table.line td.num_doc {
	width: 113px;
}

table.line td.contrato {
	width: 72px;
}

table.line td.cpf_cei_cnpj {
	width: 132px;
}

table.line td.vencimento {
	width: 134px;
}

table.line td.valor_doc {
	/* width: 180px; */
	width: 175px;
	padding-right: 5px;
}

table.line td.desconto {
	width: 113px;
}

table.line td.outras_deducoes {
	width: 112px;
}

table.line td.mora_multa {
	width: 113px;
}

table.line td.outros_acrescimos {
	width: 113px;
}

table.line td.valor_cobrado {
	/* width: 180px; */
	width: 175px;
	padding-right: 5px;
	background-color: #f3f3f3;
}

table.line td.sacado {
	width: 659px;
}

table.line td.local_pagto {
	width: 472px;
}

table.line td.vencimento2 {
	/* width: 180px; */
	width: 175px;
	padding-right: 5px;
	background-color: #f3f3f3;
}

table.line td.cedente2 {
	width: 472px;
}

table.line td.ag_cod_cedente2 {
	/* width: 180px; */
	width: 175px;
	padding-right: 5px;
}

table.line td.data_doc {
	width: 93px;
}

table.line td.num_doc2 {
	width: 173px;
}

table.line td.especie_doc {
	width: 72px;
}

table.line td.aceite {
	width: 34px;
}

table.line td.data_process {
	width: 72px;
}

table.line td.nosso_numero2 {
	/* width: 180px; */
	width: 175px;
	padding-right: 5px;
}

table.line td.reservado {
	width: 93px;
	background-color: #f3f3f3;
}

table.line td.carteira {
	width: 93px;
}

table.line td.especie2 {
	width: 53px;
}

table.line td.qtd2 {
	width: 133px;
}

table.line td.xvalor {
	/* width: 72px; */
	width: 67px;
	padding-right: 5px;
}

table.line td.valor_doc2 {
	/* width: 180px; */
	width: 175px;
	padding-right: 5px;
}
table.line td.instrucoes {
	width: 475px;
}

table.line td.desconto2 {
	/* width: 180px; */
	width: 175px;
	padding-right: 5px;
}

table.line td.outras_deducoes2 {
	/* width: 180px; */
	width: 175px;
	padding-right: 5px;
}

table.line td.mora_multa2 {
	/* width: 180px; */
	width: 175px;
	padding-right: 5px;
}

table.line td.outros_acrescimos2 {
	/* width: 180px; */
	width: 175px;
	padding-right: 5px;
}

table.line td.valor_cobrado2 {
	/* width: 180px; */
	width: 175px;
	padding-right: 5px;
	background-color: #f3f3f3 ;
}

table.line td.sacado2 {
	width: 659px;
}

table.line td.sacador_avalista {
	width: 659px;
}

table.line tr.campos td.sacador_avalista {
	width: 472px;
}

table.line td.cod_baixa {
	color: #000;
	width: 180px;
}




div.footer {
	margin-bottom: 30px;
}

div.footer p {
	width: 88px;
	margin: 0;
	padding: 0;
	padding-left: 525px;
	font-family: 'Arial Narro';
	font-size: 9px;
	color: #000;
}


div.barcode {
	width: 666px;
	margin-bottom: 20px;
}

}



@media print {

#instructions {
	height: 1px;
	visibility: hidden;
	overflow: hidden;
}

}

body{
    background-color: #9e9e9e;
    background-image: url(data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI1IiBoZWlnaHQ9IjUiPgo8cmVjdCB3aWR0aD0iNSIgaGVpZ2h0PSI1IiBmaWxsPSIjOWU5ZTllIj48L3JlY3Q+CjxwYXRoIGQ9Ik0wIDVMNSAwWk02IDRMNCA2Wk0tMSAxTDEgLTFaIiBzdHJva2U9IiM4ODgiIHN0cm9rZS13aWR0aD0iMSI+PC9wYXRoPgo8L3N2Zz4=);
    -webkit-transition: left 500ms;
    transition: left 500ms;
}
@media (min-width:1367px) and (max-width:2800px){
	#page-container{
		width: 100vw;
		height: 100vh;
		display: flex;
		flex-direction: row;
		justify-content: center;
		align-items: center;
	}
}
</style>

<link rel="shortcut icon" href="{{asset('img/favicon.png')}}">
<link rel="icon" type="image/vnd.microsoft.icon" href="{{asset('img/favicon.png')}}">
<link rel="icon" type="image/x-icon" href="{{asset('img/favicon.png')}}">
<link rel="icon" href="{{asset('img/favicon.png')}}">
<link rel="icon" type="image/gif" href="{{asset('img/favicon.png')}}">
<link rel="icon" type="image/png" href="{{asset('img/favicon.png')}}">
<link rel="icon" type="image/svg+xml" href="{{asset('img/favicon.png')}}">


<div id="page-container">
	<div id="container">

		<div id="instr_header" style="padding-left:0;">
			<table width="666" cellspacing="0" cellpadding="0" border="0" align="Default" style="margin-top:10px;">
				<tbody>
				<tr>
					<td class="ti" width="90%">
						<?=$dadosboleto["identificacao"]; ?> <?=isset($dadosboleto["cpf_cnpj"]) ? "<br>".$dadosboleto["cpf_cnpj"] : '' ?><br>
						<div style="font-size:10px;color: #000;"><u><?=$dadosboleto['observacoes_header'];?></u></div><br/>
					</td>
					<td align="RIGHT" width="10%" class="ti"><img src="{{ asset('img/print-icon.png') }}" width="35" style="cursor:pointer;margin-top:-17px;" onclick="window.print();"></td>
					</tr>
				</tbody>
			</table>
			<br>
		</div>	<!-- id="instr_header" -->

		<div id="" style="margin-top:-5px;">

			<div id="instr_content">
				<p><b><?=$dadosboleto["informacaopagamento1"];?></b></p>
				<?php if($dadosboleto["informacaopagamento2"] != ""){ ?><p><?=$dadosboleto["informacaopagamento2"];?></p><?php } ?>
				<p><?=$dadosboleto["informacaopagamento3"];?></p>
			</div>	<!-- id="instr_content" -->
		</div>	<!-- id="instructions" -->

		<div id="boleto">
			<div class="cut" style="text-align:right;">
				<p>Corte na linha pontilhada</p>
			</div>
			<table cellspacing="0" cellpadding="0" width="666" border="0" style="margin-top:5px;">
				<tbody>
					<tr>
						<td class="ct" width="666">
							<div align="left"><b class="cp">Recibo do Sacado</b></div>
						</td>
					</tr>
				</tbody>
			</table>
			<table class="header" border="0" cellspacing="0" cellpadding="0" style="margin-top:5px;">
				<tbody>
					<tr>
						<td width="150"><img src="{{ asset('img/icon-menu-hiden.png') }}" style="width:40px;" width="40"></td>
						<td class="linha_digitavel"><?=$dadosboleto["linha_digitavel"];?></td>
					</tr>
				</tbody>
			</table>

			<table class="line" cellspacing="0" cellpadding="0">
			<tbody>
			<tr class="titulos">
				<td class="cedente">Cedente</td>
				<td class="ag_cod_cedente noborder" style="width:256px !important;">&nbsp;</td>
				<td class="especie">Espécie</td>
				<td class="qtd">Quantidade</td>
				<td class="nosso_numero">Nosso número</td>
			</tr>

			<tr class="campos">
				<td class="cedente"><?=$dadosboleto["cedente"];?>&nbsp;</td>
				<td class="ag_cod_cedente noborder" style="width:256px !important;">&nbsp;</td>
				<td class="especie"><?=$dadosboleto["especie"];?>&nbsp;</td>
				<td class="qtd"><?=$dadosboleto["quantidade"];?>&nbsp;</td>
				<td class="nosso_numero"><?=$dadosboleto["nosso_numero"];?>&nbsp;</td>
			</tr>
			</tbody>
			</table>

			<table class="line" cellspacing="0" cellpadding="0">
			<tbody>
			<tr class="titulos">
				<td class="num_doc">No. documento</td>
				<td class="contrato noborder">&nbsp;</td>
				<td class="cpf_cei_cnpj">CPF/CEI/CNPJ</td>
				<td class="vencmento">Vencimento</td>
				<td class="valor_doc">Valor documento</td>
			</tr>
			<tr class="campos">
				<td class="num_doc"><?=$dadosboleto["numero_documento"];?></td>
				<td class="contrato noborder">&nbsp;</td>
				<td class="cpf_cei_cnpj"><?=$dadosboleto["cpf_cnpj"];?></td>
				<td class="vencimento"><?=$dadosboleto["data_vencimento"];?></td>
				<td class="valor_doc"><?=$dadosboleto["valor_boleto"];?></td>
			</tr>
		</tbody>
		</table>

			<table class="line" cellspacing="0" cellpadding="0">
			<tbody>
			<tr class="titulos">
				<td class="desconto">(-) Desconto / Abatimento</td>
				<td class="outras_deducoes">(-) Outras deduções</td>
				<td class="mora_multa">(+) Mora / Multa</td>
				<td class="outros_acrescimos">(+) Outros acréscimos</td>
				<td class="valor_cobrado">(=) Valor cobrado</td>
			</tr>
			<tr class="campos">
				<td class="desconto">&nbsp;</td>
				<td class="outras_deducoes">&nbsp;</td>
				<td class="mora_multa">&nbsp;</td>
				<td class="outros_acrescimos">&nbsp;</td>
				<td class="valor_cobrado">&nbsp;</td>
			</tr>
			</tbody>
			</table>


			<table class="line" cellspacing="0" cellpadding="0">
			<tbody>
			<tr class="titulos">
				<td class="sacado">Sacado</td>
			</tr>
			<tr class="campos">
				<td class="sacado"><?=$dadosboleto["sacado"];?></td>
			</tr>
			</tbody>
			</table>

			<div class="footer">
				<p>Autenticação mecânica</p>
			</div>

			<div class="cut">
				<p>Corte na linha pontilhada</p>
			</div>


			<table class="header" border="0" cellspacing="0" cellpadding="0" style="margin-top:5px;">
				<tbody>
					<tr>
						<td width="150"><img src="{{ asset('img/icon-menu-hiden.png') }}" style="width:40px;" width="40"></td>
						<td class="linha_digitavel"><?=$dadosboleto["linha_digitavel"];?></td>
					</tr>
				</tbody>
			</table>

			<table class="line" cellspacing="0" cellpadding="0">
			<tbody>
			<tr class="titulos">
				<td class="local_pagto">Local de pagamento</td>
				<td class="vencimento2">Vencimento</td>
			</tr>
			<tr class="campos">
				<td class="local_pagto">QUALQUER BANCO ATÉ O VENCIMENTO</td>
				<td class="vencimento2"><?=$dadosboleto["data_vencimento"];?></td>
			</tr>
			</tbody>
			</table>

			<table class="line" cellspacing="0" cellpadding="0">
			<tbody>
			<tr class="titulos">
				<td class="cedente2" style="width:657px;">Cedente</td>
			</tr>
			<tr class="campos">
				<td class="cedente2" style="width:657px;"><?=$dadosboleto["cedente"];?></td>
			</tr>
			</tbody>
			</table>

			<table class="line" cellspacing="0" cellpadding="0">
			<tbody>
			<tr class="titulos">
				<td class="data_doc">Data do documento</td>
				<td class="num_doc2">No. documento</td>
				<td class="especie_doc">Espécie doc.</td>
				<td class="aceite">Aceite</td>
				<td class="data_process">Data process.</td>
				<td class="nosso_numero2">Nosso número</td>
			</tr>
			<tr class="campos">
			<td class="data_doc"><?=$dadosboleto["data_documento"];?></td>
				<td class="num_doc2"><?=$dadosboleto["numero_documento"];?></td>
				<td class="especie_doc"><?=$dadosboleto["especie_doc"];?></td>
				<td class="aceite"><?=$dadosboleto["aceite"];?></td>
				<td class="data_process"><?=$dadosboleto["data_processamento"];?></td>
				<td class="nosso_numero2"><?=$dadosboleto["nosso_numero"];?></td>
			</tr>
			</tbody>
			</table>

			<table class="line" cellspacing="0" cellpadding="0">
			<tbody>
			<tr class="titulos">
				<td class="reservado">Uso do banco</td>
				<td class="carteira">Carteira</td>
				<td class="especie2">Espécie</td>
				<td class="qtd2">Quantidade</td>
				<td class="xvalor">x Valor</td>
				<td class="valor_doc2">(=) Valor documento</td>
			</tr>
			<tr class="campos">
				<td class="reservado">&nbsp;</td>
				<td class="carteira">&nbsp;</td>
				<td class="especie2">R$</td>
				<td class="qtd2"></td>
				<td class="xvalor"></td>
				<td class="valor_doc2"><?=$dadosboleto["valor_boleto"];?></td>
			</tr>
			</tbody>
			</table>


			<table class="line" cellspacing="0" cellpadding="0">
			<tbody>
			<tr><td class="last_line" rowspan="6">
				<table class="line" cellspacing="0" cellpadding="0">
				<tbody>
				<tr class="titulos">
					<td class="instrucoes">
							Instruções (Texto de responsabilidade do cedente)
					</td>
				</tr>
				<tr class="campos">
					<td class="instrucoes" rowspan="5">
						<p><?=$dadosboleto["demonstrativo1"];?></p>
						<p><?=$dadosboleto["demonstrativo2"];?></p>
						<p><?=$dadosboleto["demonstrativo3"];?></p>
						<p><?=$dadosboleto["instrucoes1"];?></p>
						<p><?=$dadosboleto["instrucoes2"];?></p>
						<p><?=$dadosboleto["instrucoes3"];?></p>
						<p><?=$dadosboleto["instrucoes4"];?></p>
					</td>
				</tr>
				</tbody>
				</table>
			</td></tr>

			<tr><td>
				<table class="line" cellspacing="0" cellpadding="0">
				<tbody>
				<tr class="titulos">
					<td class="desconto2">(-) Desconto / Abatimento</td>
				</tr>
				<tr class="campos">
					<td class="desconto2">&nbsp;</td>
				</tr>
				</tbody>
				</table>
			</td></tr>

			<tr><td>
				<table class="line" cellspacing="0" cellpadding="0">
				<tbody>
				<tr class="titulos">
					<td class="outras_deducoes2">(-) Outras deduções</td>
				</tr>
				<tr class="campos">
					<td class="outras_deducoes2">&nbsp;</td>
				</tr>
				</tbody>
				</table>
			</td></tr>

			<tr><td>
				<table class="line" cellspacing="0" cellpadding="0">
				<tbody>
				<tr class="titulos">
					<td class="mora_multa2">(+) Mora / Multa</td>
				</tr>
				<tr class="campos">
					<td class="mora_multa2">&nbsp;</td>
				</tr>
				</tbody>
				</table>
			</td></tr>

			<tr><td>
				<table class="line" cellspacing="0" cellpadding="0">
				<tbody>
				<tr class="titulos">
					<td class="outros_acrescimos2">(+) Outros Acréscimos</td>
				</tr>
				<tr class="campos">
					<td class="outros_acrescimos2">&nbsp;</td>
				</tr>
				</tbody>
				</table>
			</td></tr>

			<tr><td class="last_line">
				<table class="line" cellspacing="0" cellpadding="0">
				<tbody>
				<tr class="titulos">
					<td class="valor_cobrado2">(=) Valor cobrado</td>
				</tr>
				<tr class="campos">
					<td class="valor_cobrado2">&nbsp;</td>
				</tr>
				</tbody>
				</table>
			</td></tr>
			</tbody>
			</table>

			<table class="line" cellspacing="0" cellpadding="0">
			<tbody>
			<tr class="titulos">
				<td class="sacado2">Sacado</td>
			</tr>
			<tr class="campos">
				<td class="sacado2">
					<p><?=$dadosboleto["sacado"];?></p>
					<p><?=$dadosboleto["endereco1"];?></p>
					<p><?=$dadosboleto["endereco2"];?></p>
				</td>
			</tr>
			</tbody>
			</table>

			<table cellspacing="0" cellpadding="0" width="666" border="0"><tbody><tr><td width="666" align="right"><font style="font-size: 10px;">Autenticação mecânica - Ficha de Compensação</font></td></tr></tbody></table>

			<div class="barcode">
				<p><?php fbarcode($dadosboleto["codigo_barras"]); ?></p>
			</div>
			<div class="cut">
				<p>Corte na linha pontilhada</p>
			</div>

		</div>

	</div>
</div>




</body></html>
