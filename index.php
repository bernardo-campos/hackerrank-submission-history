<?php

// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Response as json
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
	http_response_code(200);
	exit;
}

if (!isset($_GET["username"])) {
	http_response_code(400);
	die(json_encode(["error" => "Missing 'username' parameter"]));
}
if (preg_match('/[^a-zA-Z0-9_.]/', $_GET["username"])) {
	http_response_code(400);
	die(json_encode(["error" => "The username contains invalid characters"]));
}

$username = filter_var($_GET["username"], FILTER_SANITIZE_STRING);

$url = "https://www.hackerrank.com/rest/hackers/{$username}/submission_histories";

// Initialize cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36');

// Execute cURL request
$output = curl_exec($ch);

// Check for cURL errors or HTTP response code
if ($output === false) {
	$error_msg = curl_error($ch);
	$response = array("error" => "cURL error: " . $error_msg);
	http_response_code(500);
} else {
	// Get HTTP response code
	$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	if ($http_code == 200) {
		// Successful response, return the output
		$response = json_decode($output, true);
	} else {
		// Handle HTTP response errors
		switch ($http_code) {
			case 404:
				$response = array("error" => "Resource not found");
				http_response_code(404);
				break;
			case 500:
				$response = array("error" => "Internal server error");
				http_response_code(500);
				break;
			default:
				$response = array("error" => "Unexpected HTTP response: " . $http_code);
				http_response_code($http_code);
        }
    }
}

// Close cURL resource to free up system resources
curl_close($ch);

echo $output;

?>
