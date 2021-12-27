<?php
  $servername = "sql4.freemysqlhosting.net";
  $dbusername = "sql4406995";
  $password = "T6VeLszr2q";
  $db = "sql4406995";

  // Create connection
  $conn = new mysqli($servername, $dbusername, $password, $db);

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $timezone = date('P');
  $timezone_query = "SET time_zone = '{$timezone}';";

  if ($conn->query($timezone_query) === FALSE) {
    error_log("Failed to set timezone on SQL Server. Timezone used: {$timezone} with error: {$conn->error}");
  }
?>
