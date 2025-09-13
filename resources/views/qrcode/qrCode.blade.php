<html style="height: 100%;">
<head>
    <meta name="viewport" content="width=device-width, minimum-scale=0.1">
    <title>
    FastPayments  QrCode</title>
    <style type="text/css">
.vue-notification-group{display:block;position:fixed;z-index:5000}.vue-notification-wrapper{display:block;overflow:hidden;width:100%;margin:0;padding:0}.notification-title{font-weight:600}.vue-notification-template{background:#fff}.vue-notification,.vue-notification-template{display:block;box-sizing:border-box;text-align:left}.vue-notification{font-size:12px;padding:10px;margin:0 5px 5px;color:#fff;background:#44a4fc;border-left:5px solid #187fe7}.vue-notification.warn{background:#ffb648;border-left-color:#f48a06}.vue-notification.error{background:#e54d42;border-left-color:#b82e24}.vue-notification.success{background:#68cd86;border-left-color:#42a85f}.vn-fade-enter-active,.vn-fade-leave-active,.vn-fade-move{transition:all .5s}.vn-fade-enter,.vn-fade-leave-to{opacity:0}</style>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.js"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas2image@1.0.5/canvas2image.min.js"></script>
<script type="text/javascript">
$(document).ready(function(){
    html2canvas(document.getElementById("qrcode"), {
        onrendered: function(canvas) {
            theCanvas = canvas;
            document.body.appendChild(canvas);

            // Convert and download as image
            Canvas2Image.saveAsPNG(canvas);
            $("#img-out").append(canvas);
            // Clean up
            //document.body.removeChild(canvas);
        }
    });
});
</script>
</head>
<div class="qrcode"><img style="display: block;-webkit-user-select: none;margin: auto;cursor: zoom-in;background-color: hsl(0, 0%, 90%);transition: background-color 300ms;" src="{!! qrCode($data['qrpix'],$data['width'],$data['height']) !!}" width="{{ $data['width'] }}" height="{{ $data['height'] }}"></div>
<div id="img-out"></div>
