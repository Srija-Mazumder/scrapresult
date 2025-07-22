<?php
$cookieFile = "makaut_cookies.txt";
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

$startUrl = "https://www.makautexam.net/";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $startUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
curl_setopt($ch, CURLOPT_REFERER, "https://www.google.com/");
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response1 = curl_exec($ch);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response1, 0, $headerSize);
$body = substr($response1, $headerSize);
$finalUrl1 = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "🔁 HTTP Code: $httpCode\n";
echo "🔁 Redirected to: $finalUrl1\n";
echo "📋 Response Headers:\n$header\n\n";

file_put_contents("initial_response.html", $body);

// Check for JS redirect (not likely needed, but kept for completeness)
if (preg_match('/window\.location\.href\s*=\s*["\']([^"\']+)["\']/', $body, $matches) ||
    preg_match('/window\.location\s*=\s*["\']([^"\']+)["\']/', $body, $matches) ||
    preg_match('/location\.replace\(["\']([^"\']+)["\']\)/', $body, $matches)) {
    $jsRedirectUrl = $matches[1];
    echo "🔍 Found JavaScript redirect to: $jsRedirectUrl\n\n";
    if (strpos($jsRedirectUrl, 'http') !== 0) {
        $jsRedirectUrl = rtrim($startUrl, '/') . '/' . ltrim($jsRedirectUrl, '/');
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $jsRedirectUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_REFERER, $startUrl);
    $response2 = curl_exec($ch);
    $finalUrl2 = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
} else {
    echo "⚠️ No JavaScript redirect found. Trying direct access to login page...\n\n";
    $directLoginUrl = "https://makaut1.ucanapply.com/smartexam/public/";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $directLoginUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_REFERER, $startUrl);
    $response2 = curl_exec($ch);
    $finalUrl2 = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    curl_close($ch);
}

echo "🧭 Final Page: $finalUrl2\n\n";
file_put_contents("login_page.html", $response2);
echo "✅ Login page saved to login_page.html\n\n";

// Step 4: Get dynamic login form with CSRF token
$formUrl = "https://makaut1.ucanapply.com/smartexam/public/get-login-form?typ=5";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $formUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'X-Requested-With: XMLHttpRequest',
    'Accept: application/json, text/javascript, */*; q=0.01',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    'Referer: https://makaut1.ucanapply.com/smartexam/public/'
]);
$jsonResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "📡 get-login-form HTTP Code: $httpCode\n";
echo "📡 Response length: " . strlen($jsonResponse) . " bytes\n";
file_put_contents("get_login_form_response.txt", $jsonResponse);

$data = json_decode($jsonResponse, true);
if (!isset($data['html'])) {
    echo "❌ Failed to retrieve login form. Response:\n";
    echo substr($jsonResponse, 0, 500) . "...\n";
    die();
}
file_put_contents("login_form_html.html", $data['html']);

if (!preg_match('/name=[\'"]_token[\'"][^>]*value=[\'"]([^\'"]+)[\'"]/', $data['html'], $matches)) {
    die("CSRF token not found.\n");
}
$csrfToken = $matches[1];
echo "🔑 CSRF Token: $csrfToken\n";

// Step 6: Prepare and send login POST request
$loginUrl = "https://makaut1.ucanapply.com/smartexam/public/checkLogin";
$username = ""; // <-- Your roll/username
$password = "";    // <-- Your password

$postData = http_build_query([
    '_token' => $csrfToken,
    'typ' => 5,
    'username' => $username,
    'password' => $password,
]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    "X-CSRF-TOKEN: $csrfToken",
    'X-Requested-With: XMLHttpRequest',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
    'Referer: https://makaut1.ucanapply.com/smartexam/public/'
]);
$loginResponse = curl_exec($ch);
$loginFinalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
$loginHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

file_put_contents("dashboard.html", $loginResponse);
echo "✅ Login POST complete. HTTP Code: $loginHttpCode\n";
echo "📄 Final URL after login: $loginFinalUrl\n";
echo "📝 Dashboard HTML saved to dashboard.html\n";

if (strpos($loginResponse, 'dashboard') !== false || strpos($loginFinalUrl, 'dashboard') !== false) {
    echo "✅ Login seems successful.\n";
} else {
    echo "❌ Login might have failed. Check dashboard.html manually.\n";
}

// Step 7: Fetch the student activity page after login
$activityUrl = "https://makaut1.ucanapply.com/smartexam/public/student/student-activity";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $activityUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
curl_setopt($ch, CURLOPT_REFERER, $loginFinalUrl);
$activityHtml = curl_exec($ch);
curl_close($ch);

file_put_contents("student_activity.html", $activityHtml);
echo "✅ Student Activity page fetched and saved to student_activity.html\n";

// Step 8: Extract ALL Result forms and download ALL PDFs

// ... [Keep all your existing code up to Step 7] ...

// Step 8: Extract ALL Result forms and download ALL PDFs

// First, let's see what's in the activity page
echo "\n📋 Analyzing student_activity.html...\n";

// More flexible form extraction
preg_match_all('/<form[^>]*>(.*?)<\/form>/is', $activityHtml, $allForms);
echo "Found " . count($allForms[0]) . " total forms\n";

// Look for forms with results-details action
$resultForms = [];
foreach ($allForms[0] as $formHtml) {
    if (strpos($formHtml, 'results-details') !== false) {
        $resultForms[] = $formHtml;
    }
}

echo "Found " . count($resultForms) . " result forms\n\n";

if (empty($resultForms)) {
    echo "❌ No result forms found. Let's check what URLs are available:\n";
    // Extract all URLs that might be result links
    preg_match_all('/href="([^"]*results-details[^"]*)"/', $activityHtml, $urlMatches);
    if ($urlMatches[1]) {
        echo "Found these result URLs:\n";
        foreach ($urlMatches[1] as $url) {
            echo " - $url\n";
        }
    }
    
    // Also check for onclick handlers
    preg_match_all('/onclick="[^"]*results-details[^"]*"/', $activityHtml, $onclickMatches);
    if ($onclickMatches[0]) {
        echo "\nFound onclick handlers:\n";
        foreach ($onclickMatches[0] as $onclick) {
            echo " - $onclick\n";
        }
    }
    exit;
}

// Process each result form
foreach ($resultForms as $i => $formHtml) {
    echo "\n🔍 Processing form " . ($i + 1) . "...\n";
    
    // Extract form action
    preg_match('/action="([^"]+)"/', $formHtml, $actionMatch);
    if (!$actionMatch) {
        echo "❌ No action found in form\n";
        continue;
    }
    
    $formAction = html_entity_decode($actionMatch[1]);
    
    // Make sure URL is complete
    if (!preg_match('/^https?:\/\//', $formAction)) {
        $formAction = 'https://makaut1.ucanapply.com' . $formAction;
    }
    
    echo "📍 Form action: $formAction\n";
    
    // Extract ALL input fields (more flexible regex)
    preg_match_all('/<input[^>]+>/i', $formHtml, $inputs);
    
    $postFields = [];
    foreach ($inputs[0] as $input) {
        // Extract name
        if (preg_match('/name=["\']([^"\']+)["\']/', $input, $nameMatch)) {
            $name = $nameMatch[1];
            
            // Extract value
            $value = '';
            if (preg_match('/value=["\']([^"\']*)["\']/', $input, $valueMatch)) {
                $value = html_entity_decode($valueMatch[1]);
            }
            
            $postFields[$name] = $value;
            echo "  Field: $name = " . substr($value, 0, 50) . (strlen($value) > 50 ? '...' : '') . "\n";
        }
    }
    
    // Check for CSRF token in the page
    if (preg_match('/name=["\']_token["\'][^>]*value=["\']([^"\']+)["\']/', $activityHtml, $tokenMatch)) {
        $postFields['_token'] = $tokenMatch[1];
        echo "  Added CSRF token\n";
    }
    
    // Determine filename
    $filename = "result_semester_" . ($i + 1) . ".pdf";
    if (isset($postFields['provisional']) && $postFields['provisional'] === 'Y') {
        $filename = "provisional_certificate.pdf";
    }
    
    echo "📥 Downloading to: $filename\n";
    
    // Download the PDF
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $formAction);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/pdf,application/xhtml+xml,text/html;q=0.9,*/*;q=0.8',
        'Referer: https://makaut1.ucanapply.com/smartexam/public/student/student-activity'
    ]);
    
    // Add verbose output for debugging
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $resultData = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    
    // Get verbose output
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    
    curl_close($ch);
    
    echo "  HTTP Code: $httpCode\n";
    echo "  Content-Type: $contentType\n";
    echo "  Effective URL: $effectiveUrl\n";
    echo "  Response size: " . strlen($resultData) . " bytes\n";
    
    if (strpos($contentType, 'application/pdf') !== false || substr($resultData, 0, 4) === '%PDF') {
        file_put_contents($filename, $resultData);
        echo "✅ Successfully downloaded: $filename\n";
    } 
}

// Alternative approach if forms don't work
echo "\n\n🔄 Alternative approach - trying direct links:\n";

// Look for direct PDF links
preg_match_all('/href="([^"]*\.pdf[^"]*)"/', $activityHtml, $pdfLinks);
if ($pdfLinks[1]) {
    foreach ($pdfLinks[1] as $i => $pdfUrl) {
        if (!preg_match('/^https?:\/\//', $pdfUrl)) {
            $pdfUrl = 'https://makaut1.ucanapply.com' . $pdfUrl;
        }
        
        echo "\nTrying direct PDF link: $pdfUrl\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $pdfUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_REFERER, 'https://makaut1.ucanapply.com/smartexam/public/student/student-activity');
        
        $pdfData = curl_exec($ch);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        if (strpos($contentType, 'application/pdf') !== false || substr($pdfData, 0, 4) === '%PDF') {
            $filename = "direct_pdf_" . ($i + 1) . ".pdf";
            file_put_contents($filename, $pdfData);
            echo "✅ Downloaded: $filename\n";
        }
    }
}
?>
