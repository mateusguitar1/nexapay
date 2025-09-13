<?php

$bank = $data['bank'];

$arquivo = $bank->name.'-'.$bank->holder.'-'.'export-'.date("Y-m-d").'.xls';
$arquivo = str_replace(' ','-',$arquivo);
$arquivo = str_replace(',','-',$arquivo);
$arquivo = str_replace('--','-',$arquivo);

$table = "";
$table .= "<table cellpadding='5' cellspacing='5' border='1'>";
    $table .= "<tr>";
        $table .= "<td>DATA</td>";
        $table .= "<td align='center'>CPF</td>";
        $table .= "<td>NOME COMPLETO</td>";
        $table .= "<td align='right'>VALOR</td>";
    $table .= "</tr>";

foreach($data['transaction'] as $row){

    $user_info = json_decode(base64_decode($row->user_account_data),true);

    $table .= "<tr>";
        $table .= "<td>".datestr(substr($row->final_date,0,10))." ".substr($row->final_date,11,8)."</td>";
        $table .= "<td align='center'>".$user_info['document']."</td>";
        $table .= "<td>".$user_info['name']."</td>";
        $table .= "<td align='right'>R$ ".doublestr($row->amount_solicitation)."</td>";
    $table .= "</tr>";

}

$table .= '</table>';

header ('Cache-Control: no-cache, must-revalidate');
header ('Pragma: no-cache');
header('Content-Type: application/x-msexcel');
header ("Content-Disposition: attachment; filename=".$arquivo);

echo $table;
exit();

?>