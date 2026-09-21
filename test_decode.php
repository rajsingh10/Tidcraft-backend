<?php
$str = "<p>@php<br>&quot;hello&quot;; &#39;world&#39;;<br>@endphp</p>";
$decoded = html_entity_decode($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
$clean = preg_replace_callback('/@php(.*?)@endphp/s', function($m) {
    return '@php' . strip_tags($m[1]) . '@endphp';
}, $decoded);
echo $clean;
