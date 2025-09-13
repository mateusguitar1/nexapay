<?php
function switchUrl($route){
    if (config('app.env') == 'local') {
        return url($route);
    }
    else {
        return secure_url($route);
    }
}