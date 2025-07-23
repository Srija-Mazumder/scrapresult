<?php
$cookie = "makaut_cookies.txt"; $user = "17600121040"; $pass = "31082002"; 

function curl_get($url, $cookie, $ref = null, $hdrs = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => 1, CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT => 'Mozilla/5.0'
    ]);
    if ($ref) curl_setopt($ch, CURLOPT_REFERER, $ref);
    if ($hdrs) curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
    $r = curl_exec($ch); curl_close($ch); return $r;
}function curl_post($url, $post, $cookie, $ref = null, $hdrs = []) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => 1, CURLOPT_POST => 1, CURLOPT_POSTFIELDS => $post,
        CURLOPT_COOKIEJAR => $cookie, CURLOPT_COOKIEFILE => $cookie,
        CURLOPT_FOLLOWLOCATION => 1, CURLOPT_SSL_VERIFYPEER => 0, CURLOPT_SSL_VERIFYHOST => 0,
        CURLOPT_USERAGENT => 'Mozilla/5.0'
    ]);
    if ($ref) curl_setopt($ch, CURLOPT_REFERER, $ref);
    if ($hdrs) curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);
    $r = curl_exec($ch); curl_close($ch); return $r;
}
$formJson = curl_get("https://makaut1.ucanapply.com/smartexam/public/get-login-form?typ=5", $cookie, null, ['X-Requested-With: XMLHttpRequest','Accept: application/json, text/javascript, */*; q=0.01']);
$data = json_decode($formJson, 1);
preg_match('/name=[\'"]_token[\'"][^>]*value=[\'"]([^\'"]+)/', $data['html'], $m);
$csrf = $m[1];
$post = http_build_query(['_token'=>$csrf,'typ'=>5,'username'=>$user,'password'=>$pass]);
curl_post("https://makaut1.ucanapply.com/smartexam/public/checkLogin", $post, $cookie, null, [
    'Content-Type: application/x-www-form-urlencoded',"X-CSRF-TOKEN: $csrf",'X-Requested-With: XMLHttpRequest'
]);
$html = curl_get("https://makaut1.ucanapply.com/smartexam/public/student/student-activity", $cookie);
preg_match_all('/<form[^>]*action="([^"]*results-details[^"]*)"[^>]*>(.*?)<\/form>/is', $html, $forms, PREG_SET_ORDER);
foreach ($forms as $i => $f) {
    $act = strpos($f[1],'http')===0?$f[1]:'https://makaut1.ucanapply.com'.$f[1];
    preg_match_all('/<input[^>]+name=["\']([^"\']+)["\'][^>]*value=["\']([^"\']*)["\'][^>]*>/i', $f[2], $flds, PREG_SET_ORDER);
    $in = []; foreach ($flds as $x) $in[$x[1]]=html_entity_decode($x[2]);
    if (!isset($in['_token']) && preg_match('/name=["\']_token["\'][^>]*value=["\']([^"\']+)/', $html, $tm)) $in['_token']=$tm[1];
    $pdf = curl_post($act, http_build_query($in), $cookie, null, [
        'Content-Type: application/x-www-form-urlencoded','Accept: application/pdf'
    ]);
    $fname = "result_semester_".($i+1).".pdf";
    if (substr($pdf,0,4)==='%PDF') file_put_contents($fname,$pdf);
    echo "Downloaded: $fname\n";
}
?>

