<?php
function qrCode($content,$width,$height){
    return "https://image-charts.com/chart?chs=".$width."x".$height."&cht=qr&chl=".$content;
}
?>
