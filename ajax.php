<?php
  /**
    * This script provides functions for working with AJAX requests and responses
    */
    /**
      * Send a simple response with just a success boolean and a message
      */
    function respond($success, $message) {
      http_response_code(($success) ? 200:400);
      $response = [
        'success' => $success,
        'message' => $message
      ];
      echo json_encode($response);
      exit;
    }

    /**
      * Use this method to respond with the success and message but also a data
      * object (an indexed or associative array)
      */
    function respondData($success, $message, $data) {
      http_response_code(($success) ? 200:400);
      $response = [
        'success' => $success,
        'message' => $message,
        'data' => $data
      ];
      echo json_encode($response);
      exit;
    }

    $_POST = json_decode(file_get_contents('php://input'), true); // parse the JSON POST input
 ?>
