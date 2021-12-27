<?php
  /**
    * This script holds variables and functions related to teachers such as the current
    * teacher, their current organisation etc.
    */
    require "database.php";
    require "error.php";
    require "teacher.php";
    require "organisation.php";
    require "education_history.php";
    require "employment_history.php";

    $teacher = null;
    $current_organisation = null;

    /**
      * Load the teacher into the teacher variable
      */
    function loadTeacher($username) {
      global $teacher;
      global $conn;

      $sql = "SELECT * FROM teachers WHERE username = ?;";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $param_user);
        $param_user = $username;

        if ($stmt->execute()) {
          $result = $stmt->get_result();

          if ($result->num_rows == 1) {
            while ($row = $result->fetch_assoc()) {
              $teacher = new Teacher($row['username'], $row['first_name'],
              $row['last_name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);
            }

            loadCurrentOrganisation($username);
          } else {
            doError("No teacher found for username {$username}!");
          }
        } else {
          doSQLError($stmt->error);
        }

        $stmt->close();
      } else {
        doSQLError($conn->error);
      }
    }

    /**
      * Load the current organisation of the provided user into the current organisation variable
      */
    function loadCurrentOrganisation($username) {
      global $current_organisation;
      global $conn;

      $sql = "SELECT * FROM organisations WHERE organisation_id IN (SELECT organisation_id FROM organisation_members WHERE teacher_username = ?);";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $param_user);
        $param_user = $username;

        if ($stmt->execute()) {
          $result = $stmt->get_result();

          if ($result->num_rows == 1) {
            while ($row = $result->fetch_assoc()) {
              $current_organisation = new Organisation($row['organisation_id'], $row['username'],
              $row['name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);
            }
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
