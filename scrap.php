<?php
// Config
$cookieFile = "makaut_cookies.txt";
$username = "17600121040"; // <-- Your roll/username
$password = "31082002";    // <-- Your password

if (file_exists($cookieFile)) unlink($cookieFile);

// Step 1: Get login page and CSRF token
function curl_get($url, $cookieFile, $referer = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    if ($referer) curl_setopt($ch, CURLOPT_REFERER, $referer);
    if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}
function curl_post($url, $post, $cookieFile, $referer = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    if ($referer) curl_setopt($ch, CURLOPT_REFERER, $referer);
    if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

// Step 1: Get login form (for CSRF token)
$loginPage = curl_get("https://makaut1.ucanapply.com/smartexam/public/", $cookieFile);
$formJson = curl_get("https://makaut1.ucanapply.com/smartexam/public/get-login-form?typ=5", $cookieFile, null, [
    'X-Requested-With: XMLHttpRequest',
    'Accept: application/json, text/javascript, */*; q=0.01'
]);
$data = json_decode($formJson, true);
if (!isset($data['html'])) die("Login form not found\n");
if (!preg_match('/name=[\'"]_token[\'"][^>]*value=[\'"]([^\'"]+)[\'"]/', $data['html'], $m)) die("CSRF token not found\n");
$csrfToken = $m[1];

// Step 2: Login
$postData = http_build_query([
    '_token' => $csrfToken,
    'typ' => 5,
    'username' => $username,
    'password' => $password,
]);
$loginRes = curl_post("https://makaut1.ucanapply.com/smartexam/public/checkLogin", $postData, $cookieFile, "https://makaut1.ucanapply.com/smartexam/public/", [
    'Content-Type: application/x-www-form-urlencoded',
    "X-CSRF-TOKEN: $csrfToken",
    'X-Requested-With: XMLHttpRequest'
]);

// Step 3: Fetch student activity page
$activityHtml = curl_get("https://makaut1.ucanapply.com/smartexam/public/student/student-activity", $cookieFile);

// Step 4: Extract all result forms
preg_match_all('/<form[^>]*action="([^"]*results-details[^"]*)"[^>]*>(.*?)<\/form>/is', $activityHtml, $forms, PREG_SET_ORDER);
if (!$forms) die("No result forms found\n");

// Step 5: Download all result PDFs
foreach ($forms as $i => $form) {
    $action = html_entity_decode($form[1]);
    if (strpos($action, 'http') !== 0) $action = 'https://makaut1.ucanapply.com' . $action;
    $inputs = [];
    preg_match_all('/<input[^>]+name=["\']([^"\']+)["\'][^>]*value=["\']([^"\']*)["\'][^>]*>/i', $form[2], $fields, PREG_SET_ORDER);
    foreach ($fields as $f) $inputs[$f[1]] = html_entity_decode($f[2]);
    // Add CSRF token if not present
    if (!isset($inputs['_token']) && preg_match('/name=["\']_token["\'][^>]*value=["\']([^"\']+)["\']/', $activityHtml, $tm)) $inputs['_token'] = $tm[1];
    $pdf = curl_post($action, http_build_query($inputs), $cookieFile, "https://makaut1.ucanapply.com/smartexam/public/student/student-activity", [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/pdf'
    ]);
    $fname = "result_semester_" . ($i+1) . ".pdf";
    if (substr($pdf, 0, 4) === '%PDF') file_put_contents($fname, $pdf);
    echo "Downloaded: $fname\n";
}
?>