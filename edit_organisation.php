<!DOCTYPE html>
<html>
  <head>
    <title>Edit Organisation Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
    <link type="text/css" href="css/styles.css" rel="stylesheet">
    <link type="text/css" href="css/feed.css" rel="stylesheet">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body>
    <?php
      require "navbar.php";
      require "organisation_variables.php";
      require "profile_picture_upload.php";

      /**
       * Parses the URL for any GET parameters
       */
      function parseURL() {
        global $username;

        if ($_SERVER["REQUEST_METHOD"] == "GET") {
          $parsed_url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY);
          $params = array();
          parse_str($parsed_url, $params);

          if (isset($params['username'])) {
            $username = $params['username'];
          }
        }
      }

      parseURL();

      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST["profile_type"])) {
          uploadProfilePicture($_POST["profile_type"], $username);
        }
      }

      $user_type = $_SESSION[USER_TYPE];
      $loggedin_username = $_SESSION[USERNAME];

      if ($user_type != ADMIN && $loggedin_username != $username) {
        doError("You can only edit a profile that is your own");
      } else {
        loadOrganisation($username);
        if (empty($error_message)) {
          $name = $organisation->name();
          $headline = $organisation->headline();
          $about = $organisation->about();
          $location = $organisation->location();
        }
      }
     ?>

     <?php
        generateNavBar(PROFILE);
        $error_occurred = displayError();

        if (!$error_occurred):
      ?>
      <div class="container main-background">
        <div class="row mt-5 shadow card padding-1pcent" id="update_profile">
          <h4>Update Profile</h4>
          <form id="update_profile_form">
            <div class="form-group">
              <label>Name</label>
              <input type="text" pattern="[A-Za-z\- ]*" name="name" id="name" title="Please enter alphabetical characters only" class="form-control" placeholder="Organisation Name" value="<?php echo $name; ?>" required>
            </div>
            <div class="row">
              <div class="col-9">
                <div class="form-group">
                  <label>Headline</label>
                  <input type="text" name="headline" id="headline" class="form-control" maxlength="255" placeholder="Headline" value="<?php echo $headline; ?>">
                  <div class="form-text">
                    Enter a short summary (up to 255 characters) outlining your organisation goal etc.
                  </div>
                </div>
              </div>
              <div class="col-3">
                <div class="form-group">
                  <label>Location</label>
                  <input type="text" name="location" id="location" class="form-control" maxlength="64" placeholder="Location" value="<?php echo $location; ?>">
                  <div class="form-text">
                    Enter your location, e.g. City, Country
                  </div>
                </div>
              </div>
            </div>
            <div class="form-group">
              <label>About</label>
              <textarea name="about" id="about" class="form-control" rows="5"><?php echo $organisation->about(); ?></textarea>
              <div class="form-text">
                Enter a more detailed piece of information about your organisation here
              </div>
            </div>
            <div class="row text-end">
              <div class="col">
                <button type="button" onclick="handleUpdateProfile();" class="btn btn-primary">Save</button>
              </div>
            </div>
          </form>
        </div>
        <?php
          getProfilePictureForm(false);
        ?>
        <div class="row mt-5 shadow card padding-1pcent" id="update_password">
          <h4>Update Password</h4>
          <form id="update_password_form">
            <div class="row">
              <div class="col">
                <div class="form-group">
                  <label>Password</label>
                  <input type="password" name="password" oninput="onPasswordInput();" id="password" minlength="8" class="form-control" required>
                  <div class="form-text">
                    Enter your new password
                  </div>
                </div>
              </div>
              <div class="col">
                <div class="form-group">
                  <label>Confirm Password</label>
                  <input type="password" name="confirm_password" oninput="onConfirmPasswordInput();" id="confirm_password" minlength="8" class="form-control" required>
                  <div class="form-text">
                    Confirm your new password
                  </div>
                </div>
              </div>
            </div>
            <div class="row text-end">
              <div class="col">
                <button type="button" onclick="handleUpdatePassword();" class="btn btn-primary" id="password_button">Save</button>
              </div>
            </div>
          </form>
        </div>
        <div class="row mt-5 shadow card padding-1pcent" id="delete_account">
          <h4>Delete Account</h4>
          <?php if ($user_type != ADMIN): ?>
          <p>This form is used to delete your account. To delete it, you will have to enter your password to confirm. Please note that
            this action is irreversible</p>
          <form id="delete_account_form">
            <div class="form-group">
              <label>Password</label>
              <input type="password" minlength="8" class="form-control" name="delete_password" id="delete_password" required>
              <div class="form-text">
                Enter your account password
              </div>
            </div>
            <div class="row text-end mt-2">
              <div class="col">
                <button type="button" onclick="handleDeleteAccount();" id="remove_button" class="btn btn-danger">Delete</button>
              </div>
            </div>
          </form>
          <?php else: ?>
          <p>This form is used to delete this user's account. Deleting the account is irreversible.</p>
          <div class="row text-end mt-2">
            <div class="col">
              <button type="button" onclick="handleDeleteAccount();" id="remove_button" class="btn btn-danger">Delete</button>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <script type="text/javascript" src="forms.js"></script>
    <script type="text/javascript" src="ajax.js"></script>
    <script>
      const admin = <?php echo json_encode($user_type == ADMIN); ?>;
      const username = <?php echo json_encode($username); ?>;
      const password = document.getElementById('password');
      const confirm_password = document.getElementById('confirm_password');
      confirm_password.disabled = true;
      const password_button = document.getElementById('password_button');
      password_button.disabled = true;

      /**
        * Handles the update of the profile
        */
      function handleUpdateProfile() {
        var valid = validateForm('update_profile_form');
        if (valid) {
          var data = serializeForm('update_profile', 'input,textarea');
          data['username'] = username;
          data['edit_type'] = "organisation";
          data['edit_form'] = "update_profile";

          var ajax = getAJAX();
          if (ajax != null) {
            ajax.onreadystatechange = function() {
              if (ajax.readyState == 4) {
                var response = ajax.response;

                try {
                  var responseBody = JSON.parse(response);
                  var success = responseBody.success;
                  var message = responseBody.message;

                  if (success && message == "UPDATED") {
                    addAlertMessage(true, "Profile has been updated successfully", "update_profile");
                  } else {
                    addAlertMessage(false, "An error occurred updating your profile: " + message, "update_profile");
                  }

                  clearValidation('update_profile_form');
                } catch (e) {
                  alert(e);
                }
              }
            }
          }

          ajax.open("POST", "edit-profile-ajax.php", true);
          ajax.send(JSON.stringify(data));
        }

        return false;
      }

      /**
        * Handles the update of the password
        */
      function handleUpdatePassword() {
        var valid = validateForm('update_password_form');
        if (valid) {
          var data = serializeForm('update_password', 'input');
          data['username'] = username;
          data['edit_type'] = "organisation";
          data['edit_form'] = "update_password";

          var ajax = getAJAX();
          if (ajax != null) {
            ajax.onreadystatechange = function() {
              if (ajax.readyState == 4) {
                var response = ajax.response;

                try {
                  var responseBody = JSON.parse(response);
                  var success = responseBody.success;
                  var message = responseBody.message;

                  if (success && message == "UPDATED") {
                    addAlertMessage(true, "Your password has been updated successfully", "update_password");
                  } else {
                    addAlertMessage(false, "An error occurred updating your password: " + message, "update_password");
                  }

                  clearValidation('update_password_form');
                } catch (e) {
                  alert(e);
                }
              }
            }
          }

          ajax.open("POST", "edit-profile-ajax.php", true);
          ajax.send(JSON.stringify(data));
        }

        return false;
      }

      /**
        * Handles password input
        */
      function onPasswordInput() {
        document.getElementById('update_password_form').classList.add("was-validated");
        var text = password.value;

        if (text.length < 8) {
          confirm_password.disabled = true;
          var remaining = 8 - text.length;
          password.setCustomValidity("You need to enter " + remaining + " more characters");
        } else {
          password.setCustomValidity("");
          confirm_password.disabled = false;
        }

        if (!confirm_password.disabled && confirm_password.value.length > 0)
          onConfirmPasswordInput(); // update the validity of the confirm password field
      }

      /**
        * Handles confirm password input
        */
      function onConfirmPasswordInput() {
        var text = confirm_password.value;

        if (text != password.value) {
          confirm_password.setCustomValidity("The passwords do not match");
          password_button.disabled = true;
        } else {
          confirm_password.setCustomValidity("");
          password_button.disabled = false;
        }
      }

      /**
        * Handles the deletion of the account
        */
      function handleDeleteAccount() {
        var valid = admin || validateForm('delete_account_form');
        if (valid) {
          var data = serializeForm('delete_account', 'input');

          if (admin) {
            data = {};
            data['delete_password'] = "n/a";
          }

          data['username'] = username;
          data['edit_type'] = "teacher";
          data['edit_form'] = "delete_account";
          data['admin_override'] = admin; // if admin, override the password check and just delete the account
          var ajax = getAJAX();
          if (ajax != null) {
            ajax.onreadystatechange = function() {
              if (ajax.readyState == 4) {
                var response = ajax.response;

                try {
                  var responseBody = JSON.parse(response);
                  var success = responseBody.success;
                  var message = responseBody.message;

                  if (success && message == "DELETED") {
                    window.location.href = "logout.php";
                  } else {
                    addAlertMessage(false, "An error occurred deleting your account: " + message, "delete_account");
                  }
                } catch (e) {
                  alert(e);
                }
              }
            }

            ajax.open("POST", "edit-profile-ajax.php", true);
            ajax.send(JSON.stringify(data));
          }
        }

        return false;
      }
    </script>
  </body>
</html>
