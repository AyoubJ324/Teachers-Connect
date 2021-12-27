<!DOCTYPE html>
<html>
  <head>
    <title>Forgot Password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
    <link type="text/css" href="css/styles.css" rel="stylesheet">
  </head>
  <body>
    <?php
      require "database.php";
      require "admin_profile_utils.php";
      require "notifications_utils.php";
      require "constants.php";

      $email = $email_error = "";
      $username = $username_error = "";
      $multiple_accounts = false;
      $accounts = array();

      /**
        * Check if the username exists
        */
      function checkEmailExists() {
        global $email;
        global $email_error;
        global $accounts;
        global $username;
        global $multiple_accounts;
        global $conn;

        $sql = "SELECT * FROM accounts WHERE email = ?;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("s", $param_email);
          $param_email = $email;

          $exists = false;
          if ($stmt->execute()) {
            $result = $stmt->get_result();
            $exists = $result->num_rows > 0;

            if ($exists) {
              if ($result->num_rows == 1) {
                $row = $result->fetch_assoc();
                $username = $row['username'];
              } else {
                $multiple_accounts = true;
                while ($row = $result->fetch_assoc()) {
                  $type = ucfirst($row['type']);
                  $temp_username = $row['username'];
                  $accounts[$temp_username] = "{$temp_username} - {$type}";
                }
              }
            }
          } else {
            $username_error = "An error occurred, please try again";
            return false;
          }

          $stmt->close();
          return $exists;
        } else {
          $username_error = "An error occurred, please try again";
          return false;
        }
      }

      /**
        * Gets the account options
        */
      function getAccountsOptions() {
        global $accounts;

        echo '<option value="dont-use">Select an account</option>';
        foreach ($accounts as $key => $value) {
          echo '<option value="'.$key.'">'.$value.'</option>';
        }
      }

      /**
        * Delete all old keys before creating this one
        */
      function deleteAllUserKeys() {
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
        * Creates the reset key, inserts into the database and returns it
        */
      function createResetKey() {
        global $username;
        global $conn;

        deleteAllUserKeys();

        $key = md5((2418*2).$username);
        $addKey = substr(md5(uniqid(rand(),1)),3,10);
        $key = $key . $addKey;

        $sql = "INSERT INTO password_reset_keys (`username`, `reset_key`, `expiry`) VALUES (?, ?, ?);";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("sss", $param_username, $param_key, $param_expiry);
          $param_username = $username;
          $param_key = $key;
          $expFormat = mktime(date("H") + 1, date("i"), date("s"), date("m") ,date("d"), date("Y"));
          $param_expiry = date("Y-m-d H:i:s",$expFormat);

          if (!$stmt->execute()) {
            die("Database Error: {$stmt->error}");
          }

          $stmt->close();
          return $key;
        } else {
          die("Database Error: {$conn->error}");
        }
      }

      use PHPMailer\PHPMailer\PHPMailer;
      use PHPMailer\PHPMailer\Exception;

      require 'mail/Exception.php';
      require 'mail/PHPMailer.php';
      require 'mail/SMTP.php';

      require 'email.php';

      /**
        * Sends the reset e-mail
        */
      function sendResetEmail() {
        global $username;
        global $email;
        global $email_username;
        global $email_server;
        global $email_port;
        global $email_password;
        global $username_error;

        $type = getUserType($username);
        $name = "User";

        if ($type != ADMIN) {
          $name = getSenderName($username, $type);
        }

        $key = createResetKey();
        $url = $_SERVER["REQUEST_URI"];
        $path = substr($url, 0, strpos($url, "forgot_password.php") - 1);
        $link = $_SERVER["SERVER_NAME"] . $path . "/reset_password.php?key={$key}&username={$username}";

        if (!empty($_SERVER['HTTPS'])) {
          $link = "https://" . $link;
        }

        $body = '<html><body><p>Dear '.$name.',</p>';
        $body .= '<p>Please click on the link provided to reset your password.</p>';
        $body .= '<hr>';
        $body .= "<p><b>Username:</b> {$username}</p>";
        $body .= '<p><b>Password Reset Link:</b> <a href="'.$link.'">'.$link.'</a></p>';
        $body .= '<p>If the link does not work, copy it into your browser\'s url bar.</p>';
        $body .= '<p>Please note that the link will expire after 1 hour.</p>';
        $body .= '<p>If you did not request this, please ignore this e-mail and change your password.</p>';
        $body .= '<br><p>Thank You,</p>';
        $body .= '<p>TeachersConnect Team</p>';
        $body .= '<br><p><i>Please do not reply to this e-mail as it is not monitored</i></p></body></html>';

        $date = date("Y-m-d H:i:s", time());
        $subject = "Password Recovery - TeachersConnect - {$username} - {$date}";

        $email_to = $email;
        $from_server = $email_username;
        $mail = new PHPMailer();
        $mail->IsSMTP();
        $mail->Host = $email_server;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = "tls";
        $mail->Username = $email_username; // Enter your email here
        $mail->Password = $email_password; //Enter your password here
        $mail->Port = $email_port;
        $mail->IsHTML(true);
        $mail->SetFrom($email_username);
        $mail->FromName = "TeachersConnect Team";
        $mail->Sender = $from_server;
        $mail->Subject = $subject;
        $mail->MsgHTML($body);
        $mail->AddAddress($email_to);

        if (!$mail->Send()) {
          $username_error = "An error occurred sending the e-mail. Please try again";
        }
      }

      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username_set = isset($_POST['username']);
        if ($username_set) {
          $multiple_accounts = true;
          $username = $_POST['username'];
        }

        if (isset($_POST['email'])) {
          $email = $_POST['email'];

          if ($multiple_accounts) {
            checkEmailExists(); // load up the accounts again
          }

          if ($username == "dont-use") {
            $username_error = "You need to select an account";
          } else {
            $exists = $username_set || checkEmailExists();
            if (empty($email_error) && $exists && (!$multiple_accounts || $username_set)) {
              sendResetEmail();
              $multiple_accounts = false;
            } else if (!$multiple_accounts && !$exists) {
              $email_error = "No account with that e-mail exists";
            } else if (!$multiple_accounts){
              $email_error = "An e-mail has to be provided";
            }
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
      <?php if (empty($email) || !empty($email_error)): ?>
      <div class="login-card">
        <div class="row login-logo m-auto">
          <img class="img-fluid" src="images/logo.png" alt="Teacher's Connect logo">
        </div>
        <div class="row card shadow mb-5 mt-5">
          <div class="row text-center">
            <h4 class="underlined-header">Forgot Password</h4>
            <p>Enter the e-mail address that you used to create your account. If more than 1 account exists for
              that address, you will be able to choose the account on the next screen</p>
          </div>
          <div class="row">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
              <div class="form-group login-input <?php echo (!empty($email_error)) ? 'has-error' : ''; ?>">
                <label>E-mail Address</label>
                <input type="email" name="email" class="form-control" value="<?php echo $email; ?>" required>
                <span class="help-block login-error-message"><?php echo $email_error; ?></span>
              </div>
              <div class="row login-button">
                <button class="btn btn-primary login-button m-auto w-50" type="submit">Send Reset E-mail</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php elseif (!empty($email) && $multiple_accounts): ?>
      <div class="registration-card">
        <div class="row card shadow">
          <img class="img-fluid w-50 h-50 m-auto" src="images/logo.png" alt="Teacher's Connect logo">
          <div class="row mb-5 mt-5 text-center">
            <h4 class="underlined-header">Choose account to reset</h4>
            <p>There are multiple accounts associated with <?php echo $email; ?>. Please choose the account you want to reset here</p>
          </div>
          <div class="row">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
              <div class="form-group login-input <?php echo (!empty($username_error)) ? 'has-error' : ''; ?>">
                <label>Account</label>
                <select class="form-select" name="username">
                  <?php getAccountsOptions(); ?>
                </select>
                <span class="help-block login-error-message"><?php echo $username_error; ?></span>
              </div>
              <input type="hidden" name="email" value="<?php echo $email; ?>">
              <div class="row login-button">
                <button class="btn btn-primary login-button m-auto w-50" type="submit">Send Reset E-mail</button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <?php else: ?>
        <div class="registration-card">
          <div class="row card mt-5 mb-5 shadow text-center">
            <h4>Password Reset E-mail Sent</h4>
            <p>A password reset e-mail has been sent to <?php echo $email; ?>. The link is only valid for 1 hour</p>
            <p>Want to go back to <a href="login.php">login</a>?</p>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </body>
</html>
