<!DOCTYPE html>
<html>
  <head>
    <title>Teacher Sign-Up</title>
    <link type="text/css" rel="stylesheet" href="css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body>
    <?php
      require "notifications_utils.php";
      require "database.php";
      require "error.php";

      session_start();

      // Initialise variables that are used in our signup form
      $first_name = $last_name = $username = $email = $password = "";
      $first_name_error = $last_name_error = $username_error = $email_error = $password_error = "";
      $display_signed_up_modal = false;

      /**
       * This method processes the first name that has been entered on the form
       */
      function processFirstname() {
        global $first_name;
        global $first_name_error;

        $temp_first_name = trim($_POST["first_name"]);

        $valid_name = true;

        if (empty($temp_first_name)) {
          $first_name_error = "Please enter your first name";
          $valid_name = false;
        } else {
          $first_name = $temp_first_name;
        }

        return $valid_name;
      }

      /**
       * This method processes the last name that has been entered on the form
       */
      function processLastname() {
        global $last_name;
        global $last_name_error;

        $temp_last_name = trim($_POST["last_name"]);

        $valid_name = true;

        if (empty($temp_last_name)) {
          $last_name_error = "Please enter your last name";
          $valid_name = false;
        } else {
          $last_name = $temp_last_name;
        }

        return $valid_name;
      }

      /**
       * This method processes the username that has been entered on the form
       */
      function processUsername() {
        global $username;
        global $username_error;

        $temp_username = trim($_POST["username"]);

        $valid_user = true;

        if (empty($temp_username)) {
          $username_error = "Please enter a username";
          $valid_user = false;
        } else {
          $sql = "SELECT username FROM accounts WHERE username = ?;";

          global $conn;
          if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_user);
            $param_user = $temp_username;

            if ($stmt->execute()) {
              $stmt->store_result();

              if ($stmt->num_rows == 1) {
                $username_error = "This username is already in use";
                $valid_user = false;
              }

              $username = $temp_username;
            } else {
              doSQLError($stmt->error);
            }

            $stmt->close();
          } else {
            doSQLError($conn->error);
          }
        }

        return $valid_user;
      }

      /**
       * This method processes the email that has been entered on the form
       */
      function processEmail() {
        global $email;
        global $email_error;

        $temp_email = trim($_POST["email"]);

        $valid_email = true;

        if (empty($temp_email)) {
          $email_error = "Please enter your e-mail address";
          $valid_email = false;
        } else {
          $sql = "SELECT email FROM email_blacklist WHERE email = ?;";

          global $conn;
          if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_email);
            $param_email = $temp_email;

            if ($stmt->execute()) {
              $stmt->store_result();

              if ($stmt->num_rows == 1) {
                $email_error = "This email has been blacklisted from the site";
                $valid_email = false;
              }
            } else {
              doSQLError($stmt->error);
            }

            $stmt->close();
          } else {
            doSQLError($conn->error);
          }
        }

        $email = $temp_email;

        return $valid_email;
      }

      /**
       * This method processes the password that has been entered on the form
       */
      function processPassword() {
        global $password;
        global $password_error;

        $temp_password = trim($_POST["password"]);

        $valid_password = true;

        if (empty($temp_password)) {
          $password_error = "Please enter a password";
          $valid_password = false;
        } else if (strlen($temp_password) < 8) {
          $password_error = "Please enter a password with at least 8 characters";
          $valid_password = false;
        } else {
          $password = $temp_password;
        }

        return $valid_password;
      }

      /**
       * This function processes all user input and returns true if valid
       */
      function process() {
        $fnameProcess = processFirstname();
        $lnameProcess = processLastname();
        $unameProcess = processUsername();
        $emailProcess = processEmail();
        $passwordProcess = processPassword();

        return $fnameProcess && $lnameProcess && $unameProcess && $emailProcess
          && $passwordProcess;
      }

      /**
        * Sends the new user a welcome notification
        */
          function sendWelcomeNotification($user_name) {

            $username = $user_name;
            $sender = 'Teachers Connect Bot';
            $link = "teacher_profile.php?username={$username}";

            $notification = new AdminNotification($sender, $username, false, $link, null);

            AddAdminNotification($notification);

          }


      /**
       * After successful processing, this method should save the user to the database
       */
      function saveUser() {
        $sql = "INSERT INTO accounts (username, email, password, type) VALUES (?, ?, ?, ?);";

        global $conn;
        global $first_name;
        global $last_name;
        global $username;
        global $email;
        global $password;
        global $display_signed_up_modal;

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("ssss", $param_user, $param_email, $param_password, $param_type);
          $param_user = $username;
          $param_email = $email;
          $param_password = password_hash($password, PASSWORD_DEFAULT);
          $param_type = "teacher";

          if ($stmt->execute()) {
            $sql = "INSERT INTO teachers (username, first_name, last_name) VALUES (?, ?, ?);";

            if ($stmt1 = $conn->prepare($sql)) {
              $stmt1->bind_param("sss", $param_user, $param_first_name, $param_last_name);
              $param_user = $username;
              $param_first_name = $first_name;
              $param_last_name = $last_name;

              if ($stmt1->execute()) {
                $display_signed_up_modal = true;
              } else {
                doSQLError($stmt1->error);
              }

              $stmt1->close();
            } else {
              doSQLError($conn->error);
            }
          } else {
            doSQLError($stmt->error);
          }

          $stmt->close();
        } else {
          doSQLError($conn->error);
        }
      }

      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (process()) {
            saveUser();
            sendWelcomeNotification($username);
        } else {
          doError("A field isn't valid. Please correct the error and try again");
        }
      }

     ?>

    <style>
      .form-group {
        margin-bottom: 2%;
      }
    </style>

    <div class="container-fluid main-background overflow-auto d-flex align-items-center justify-content-center">
      <div class="login-card">
        <div class="row login-logo m-auto">
          <img class="img-fluid" src="images/logo.png" alt="Teacher's Connect logo">
        </div>
        <div class="row card mt-5 mb-5 shadow">
          <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="row">
              <div class="col">
                <div class="form-group login-input <?php echo (!empty($first_name_error)) ? 'has-error' : ''; ?>">
                  <label>First Name</label>
                  <input type="text" pattern="[A-Za-z\-]*" name="first_name" title="Please enter alphabetical characters only" class="form-control" placeholder="John" value="<?php echo $first_name; ?>" required>
                  <span class="help-block login-error-message"><?php echo $first_name_error; ?></span>
                </div>
              </div>
              <div class="col">
                <div class="form-group login-input <?php echo (!empty($last_name_error)) ? 'has-error' : ''; ?>">
                  <label>Last Name</label>
                  <input type="text" pattern="[A-Za-z\-]*" name="last_name" title="Please enter alphabetical characters only" class="form-control" placeholder="Doe" value="<?php echo $last_name; ?>" required>
                  <span class="help-block login-error-message"><?php echo $last_name_error; ?></span>
                </div>
              </div>
            </div>
            <div class="form-group login-input <?php echo (!empty($username_error)) ? 'has-error' : ''; ?>">
              <label>Username</label>
              <input type="text" pattern="[A-Za-z0-9_\-]*" title="Please enter alphanumeric characters only" name="username" class="form-control" placeholder="jdoe" value="<?php echo $username; ?>" required>
              <span class="help-block login-error-message"><?php echo $username_error; ?></span>
            </div>
            <div class="form-group login-input <?php echo (!empty($email_error)) ? 'has-error' : ''; ?>">
              <label>E-mail</label>
              <input type="email" name="email" title="Please enter an email address only" class="form-control" placeholder="jdoe@email.com" value="<?php echo $email; ?>" required>
              <span class="help-block login-error-message"><?php echo $email_error; ?></span>
            </div>
            <div class="form-group login-input <?php echo (!empty($password_error)) ? 'has-error' : ''; ?>">
              <label>Password (8 or more characters)</label>
              <input type="password" name="password" minlength="8" class="form-control" required>
              <span class="help-block login-error-message"><?php echo $password_error; ?></span>
            </div>
            <div class="row">
              <p class="login-info-text">By clicking Join now, you agree to Teacher Connect's <a href="#">User Agreement</a>,<a href="#">Privacy Policy</a>,<a href="#">Cookie usage</a></p>
            </div>
            <?php
                displayError();
             ?>
            <div class="row login-button m-auto w-50">
              <button type="submit" class="btn btn-primary login-button">Join now</button>
            </div>
          </form>
        </div>
        <div class="row">
          <div class="col align-self-center text-center">
            <h5>Have an account?</h5>
            <a href="login.php">Login</a>
            <div class="align-self-center separator">or</div>
            <h5>Want to sign up as an organisation?</h5>
            <a href="organisation_signup.php">Create an Organisation</a>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="signupModal" tabindex="-1" role="dialog" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalLabel">Sign-up successful</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="hideModal();">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <p>Teacher <?php echo "{$first_name} {$last_name} "; ?> has been created successfully. Do you want to login?<p>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="hideModal();">Close</button>
            <button type="button" class="btn btn-primary" onclick="goToLogin();">Login</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/js/bootstrap.min.js" integrity="sha384-nsg8ua9HAw1y0W1btsyWgBklPnCUAFLuTMS2G72MMONqmOymq585AcH49TLBQObG" crossorigin="anonymous"></script>
    <script>
      var displayModal = <?php echo json_encode($display_signed_up_modal); ?>;
      if (displayModal) {
        var modal = document.getElementById('signupModal');
        modal.style.display = "block";
        modal.classList.add("show");
      }

      function hideModal() {
        var modal = document.getElementById('signupModal');
        modal.style.display = "none";
        modal.classList.remove("show");
      }

      function goToLogin() {
        window.location.href = "login.php";
      }
    </script>
  </body>
</html>
