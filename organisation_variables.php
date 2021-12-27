<?php
/**
  * This script holds variables and functions related to organisations such as the current
  * teacher, their current organisation etc.
  */
  require "database.php";
  require "error.php";
  require "organisation.php";

  $organisation = null;

  /**
    * Load the organisation into the organisation variables
    */
  function loadOrganisation($username) {
    global $organisation;
    global $conn;

    $sql = "SELECT * FROM organisations WHERE username = ?;";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("s", $param_user);
      $param_user = $username;

      if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
          while ($row = $result->fetch_assoc()) {
            $organisation = new Organisation($row['organisation_id'], $username,
            $row['name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);
          }
        } else {
          doError("No organisation found for username {$username}!");
        }
      } else {
        doSQLError($stmt->error);
      }

      $stmt->close();
    } else {
      doSQLError($conn->error);
    }
  }
 ?>
