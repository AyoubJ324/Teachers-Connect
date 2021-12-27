<?php
  /**
    * This script provides blocking/blacklisting utilities for admins.
    * It is intended to be 'required' into the profile scripts to access variables such as user_type etc
    */
    require_once "database.php";

    $banned = false;
    $blacklisted = false;

    /**
      * Get the type of the user
      */
    function getUserType($username) {
      global $conn;

      $sql = "SELECT type FROM accounts WHERE username = ?;";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $param_username);
        $param_username = $username;

        $type = null;

        if ($stmt->execute()) {
          $result = $stmt->get_result();

          if ($result->num_rows == 1) {
            while ($row = $result->fetch_assoc()) {
              $type = $row['type'];
            }
          }
        } else {
          doSQLError($stmt->error);
        }

        $stmt->close();
        return $type;
      } else {
        doSQLError($conn->error);
      }
    }

    /**
      * Remove the ban if it expired
      */
    function removeBan($username) {
      global $conn;

      $sql = "DELETE FROM banned_users WHERE username = ?;";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $param_user);
        $param_user = $username;

        if (!$stmt->execute()) {
          doSQLError($stmt->error);
        }

        $stmt->close();
      } else {
        doSQLError($conn->error);
      }
    }

    /**
      * Check if the user is banned or not
      */
    function checkBanned($username) {
      global $conn;
      global $banned;

      $sql = "SELECT * FROM banned_users WHERE username = ?;";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $param_user);
        $param_user = $username;

        if ($stmt->execute()) {
          $result = $stmt->get_result();

          if ($result->num_rows == 1) {
            while ($row = $result->fetch_assoc()) {
              $date_to = $row['date_to'];
              $date_to = strtotime($date_to);
              $cur_time = time();

              $banned = $cur_time < $date_to;

              if (!$banned) {
                removeBan($username);
              }
            }
          }

          return $banned;
        } else {
          doSQLError($stmt->error);
        }

        $stmt->close();
      } else {
        doSQLError($conn->error);
      }
    }

    /**
      * Check if the user is blacklisted or not
      */
    function checkBlacklist($username) {
      global $conn;
      global $blacklisted;

      $sql = "SELECT email FROM email_blacklist NATURAL JOIN accounts WHERE username = ?;";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $param_user);
        $param_user = $username;

        if ($stmt->execute()) {
          $stmt->store_result();

          $blacklisted = $stmt->num_rows == 1;
          return $blacklisted;
        } else {
          doSQLError($stmt->error);
        }

        $stmt->close();
      } else {
        doSQLError($conn->error);
      }
    }

    /**
      * Get the block button to display.
      * checkBanned and checkBlacklist should be called before this call
      */
    function getBlockButton() {
      global $banned;
      global $blacklisted;

      if (!$blacklisted) {
        $button = "<button type=\"button\" class=\"btn btn-danger\" style=\"margin-right: 1vw;\"";

        if ($banned) {
          $button = $button." onclick=\"ban();\">Unban</button>";
        } else {
          $button = $button." data-bs-toggle=\"modal\" data-bs-target=\"#banModal\">Ban</button>";
        }

        echo $button;
      }

      echo "";
    }

    /**
      * Get the blacklist button to display.
      * checkBanned and checkBlacklist should be called before this call
      */
    function getBlacklistButton() {
      global $blacklisted;

      $button = "<button type=\"button\" class=\"btn btn-dark\"";

      if ($blacklisted) {
        $button = $button." onclick=\"blacklist();\">Unblacklist</button>";
      } else {
        $button = $button." data-bs-toggle=\"modal\" data-bs-target=\"#blacklistModal\">Blacklist</button>";
      }

      echo $button;
    }
 ?>
