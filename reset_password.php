<!DOCTYPE html>
<html>
  <head>
    <title>Password Reset</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
    <link type="text/css" href="css/styles.css" rel="stylesheet">
  </head>
  <body>
    <?php
      require "database.php";
      require "error.php";

      session_start();

      $username = "";
      $key = "";
      $password = $password_error = $confirm_password = $confirm_password_error = "";

      /**
       * Checks the key validity
       */
      function checkKey() {
        global $username;
        global $key;
        global $conn;

        $sql = "SELECT expiry FROM password_reset_keys WHERE username = ? AND reset_key = ?;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("ss", $param_username, $param_key);
          $param_username = $username;
          $param_key = $key;

          $valid = false;
          if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
              $row = $result->fetch_assoc();
              $timestamp = $row['expiry'];
              $timestamp = strtotime($timestamp);
              $valid = $timestamp >= time();

              if (!$valid) {
                doError("This password reset request has expired");
              }
            } else {
              doError("This password reset request no longer exists");
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
        * Deletes any password keys for this user
        */
      function deleteKeys() {
        global $username;
        global $conn;

        $sql = "DELETE FROM password_reset_keys WHERE username = ?;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("s", $param_username);
          $param_username = $username;

          if (!$stmt->execute()) {
            doSQLError($stmt->error());
          }

          $stmt->close();
        } else {
          doSQLError($conn->error);
        }
      }

      /**
        * Save the new password
        */
      function savePassword() {
        global $username;
        global $password;
        global $conn;

        $sql = "UPDATE accounts SET password = ? WHERE username = ?;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("ss", $param_password, $param_username);
          $param_password = password_hash($password, PASSWORD_DEFAULT);
          $param_username = $username;

          if (!$stmt->execute()) {
            doSQLError($stmt->error);
          }

          $stmt->close();
          $_SESSION['SUCCESS_MESSAGE'] = "Your password has been reset successfully";
          header("Location: login.php");
          exit;
        } else {
          doSQLError($conn->error);
        }
      }

      if ($_SERVER["REQUEST_METHOD"] == "GET") {
        if (!isset($_GET['username']) && !isset($_GET['key'])) {
          doError("Invalid request");
        } else {
          $username = $_GET['username'];
          $key = $_GET['key'];

          checkKey();
        }
      } else if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'];

        if (isset($_POST['password'])) {
          $password = $_POST['password'];
        } else {
          $password_error = "You need to provide a password";
        }

        if (isset($_POST['confirm_password'])) {
          $confirm_password = $_POST['confirm_password'];
        } else {
          $confirm_password_error = "You need to confirm your password";
        }

        if (!empty($password) && !empty($confirm_password)) {
          if ($password != $confirm_password) {
            $confirm_password_error = "The passwords do not match";
          } else {
            deleteKeys();
            savePassword();
          }
        }
      }
     ?>

     <style>
       .form-group {
         margin-bottom: 2%;
       }
     </style>

    <div class="container-fluid main-background overflow-auto flex-fill d-flex align-items-center justify-content-center">
     <?php
        $error_displayed = displayError();

        if (!$error_displayed):
      ?>
      <div class="login-card">
        <div class="row login-logo m-auto">
          <img class="img-fluid" src="images/logo.png" alt="Teacher's Connect logo">
        </div>
        <div class="row card shadow mb-5 mt-5">
          <div class="row text-center">
            <h4 class="underlined-header">Reset Password</h4>
            <p>Enter your new password that will be then used to login to your account</p>
          </div>
          <div class="row">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
              <div class="form-group login-input <?php echo (!empty($password_error)) ? 'has-error' : ''; ?>">
                <label>Password (8 or more characters)</label>
                <input type="password" name="password" minlength="8" class="form-control" required>
                <span class="help-block login-error-message"><?php echo $password_error; ?></span>
              </div>
              <div class="form-group login-input <?php echo (!empty($confirm_password_error)) ? 'has-error' : ''; ?>">
                <label>Confirm Password</label>
                <input type="password" name="confirm_password" minlength="8" class="form-control" required>
                <span class="help-block login-error-message"><?php echo $confirm_password_error; ?></span>
              </div>
              <input type="hidden" name="username" value="<?php echo $username; ?>">
              <div class="row login-button m-auto w-50">
                <button type="submit" class="btn btn-primary login-button">Reset</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </body>
</html>
