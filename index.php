<?php

class APIHandler
{
    private $username;
    private $url;
    private $response;

    public function __construct()
    {
        $this->setHeaders();
        $this->handleOptionsRequest();
        $this->validateAndSetUsername();
        $this->setUrl();
        $this->processRequest();
        $this->sendResponse();
    }

    private function setHeaders()
    {
        header("Access-Control-Allow-Origin: *");
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
        header('Content-Type: application/json');
    }

    private function handleOptionsRequest()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    private function validateAndSetUsername()
    {
        if (!isset($_GET["username"])) {
            $this->sendErrorResponse(400, "Missing 'username' parameter");
        }

        if (preg_match('/[^a-zA-Z0-9_.]/', $_GET["username"])) {
            $this->sendErrorResponse(400, "The username contains invalid characters");
        }

        $this->username = filter_var($_GET["username"], FILTER_SANITIZE_STRING);
    }

    private function setUrl()
    {
    	$this->url = "https://www.hackerrank.com/rest/hackers/{$this->username}/submission_histories";
    }

    private function processRequest()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36');

        $output = curl_exec($ch);

        if ($output === false) {
            $error_msg = curl_error($ch);
            $this->sendErrorResponse(500, "cURL error: " . $error_msg);
        } else {
            $this->handleHttpResponse($ch, $output);
        }

        curl_close($ch);
    }

    private function handleHttpResponse($ch, $output)
    {
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        switch ($http_code) {
            case 200:
                $this->response = json_decode($output, true);
                break;
            case 404:
                $this->sendErrorResponse(404, "Resource not found");
                break;
            case 500:
                $this->sendErrorResponse(500, "Internal server error");
                break;
            default:
                $this->sendErrorResponse($http_code, "Unexpected HTTP response: " . $http_code);
        }
    }

    private function sendErrorResponse($code, $message)
    {
        http_response_code($code);
        echo json_encode(["error" => $message]);
        exit;
    }

    private function sendResponse()
    {
        echo json_encode($this->response);
    }
}

new APIHandler();

?>
