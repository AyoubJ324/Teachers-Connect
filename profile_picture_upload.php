<?php
  /**
    * This PHP script has utilities for uploading profile pictures and also printing the profile picture form.
    */
    require_once "constants.php";
    require_once "database.php";

    $profile_picture_error = "";
    $profile_picture_success_message = "";

    /**
      * Displays a success alert if the profile picture uploaded successfully
      */
    function displayProfilePictureSuccessAlert() {
      global $profile_picture_success_message;

      if (!empty($profile_picture_success_message)) {
        echo "<div class=\"row alert m-auto mt-2 alert-success alert-dismissable fade show\" role=\"alert\">{$profile_picture_success_message}";
        echo "<div class=\"col text-end\">";
        echo "<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button></div></div>";
      }
    }

    /**
      * Print the form for the profile photo
      * Pass in true for teacher, false for organisation
      */
    function getProfilePictureForm($isTeacher) {
      global $teacher;
      global $organisation;
      global $profile_picture_error;

      if ($isTeacher === true || $isTeacher === false) {
        $type = ($isTeacher) ? TEACHER:ORGANISATION;
        $action = htmlspecialchars($_SERVER["PHP_SELF"]);
        echo "<div class=\"row mt-5 shadow card padding-1pcent\">";
        echo "<h4>Update Profile Picture</h4>";
        echo "<div class=\"row\">";
        echo "<div class=\"col-4\">";
        $profile_photo = ($isTeacher) ? $teacher->profile_photo():$organisation->profile_photo();
        $profile_photo = ($profile_photo == null || empty($profile_photo)) ? (($isTeacher) ? DEFAULT_TEACHER_PROFILE_PIC:DEFAULT_ORG_PROFILE_PIC):$profile_photo;
        echo "<img class=\"img-fluid rounded-circle\" src=\"{$profile_photo}\"></div>";
        echo "<div class=\"col-8\">";
        echo "<form enctype=\"multipart/form-data\" action=\"{$action}\" method=\"post\">";
        echo "<div class=\"form-group\">";
        echo "<label>Choose profile picture</label>";
        echo "<input type=\"file\" class=\"form-control\" name=\"profile_photo\" required>";
        echo "<input type=\"hidden\" name=\"profile_type\" value=\"{$type}\">";
        echo "<span class=\"help-block login-error-message\">{$profile_picture_error}</span>";
        echo "<div class=\"row text-end\">";
        echo "<div class=\"col\">";
        echo "<button class=\"btn btn-primary\">Save</button>";
        echo "</div></div>";
        echo "</div>";
        echo "</form></div></div>";
        displayProfilePictureSuccessAlert();
        echo "</div>";
      } else {
        die("Unknown value for teacher passed into getProfilePicture. Expected a boolean");
      }
    }

    /**
      * Updates the profile_photo entry in the mysql mysql table
      */
    function updateSQLProfilePath($path, $type, $username) {
      global $conn;

      $table = ($type == TEACHER) ? "teachers":"organisations";
      $sql = "UPDATE {$table} SET profile_photo = ? WHERE username = ?;";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $param_path, $param_username);
        $param_path = $path;
        $param_username = $username;

        if (!$stmt->execute()) {
          die("Database Error: {$stmt->error}");
        }

        $stmt->close();
      } else {
        die("Database Error: {$conn->error}");
      }
    }

    /**
      * Uploads the profile picture and updates the profile picture for the user's profile
      * in mysql
      */
    function uploadProfilePicture($type, $username) {
      global $profile_picture_error;
      global $profile_picture_success_message;

      if ($type != TEACHER && $type != ORGANISATION) {
        die("Invalid type given to the function uploadProfilePicture");
      }

      $target_dir = "images/profile-pictures/";
      $target_file = $target_dir . "{$username}-" . basename($_FILES["profile_photo"]["name"]);
      $uploadOk = true;
      $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

      if (isset($_POST["profile_type"])) {
        $type = $_POST["profile_type"];
        $check = getimagesize($_FILES["profile_photo"]["tmp_name"]);

        if ($check == false) {
          $profile_picture_error = "The file provided is not an image";
          return false;
        }

        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg") {
          $profile_picture_error = "Only JPG, JPEG and PNG files are allowed.";
          return false;
        }

        if (move_uploaded_file($_FILES["profile_photo"]["tmp_name"], $target_file)) {
          updateSQLProfilePath($target_file, $type, $username);
          $profile_picture_success_message = "Profile Picture updated successfully";
          return true;
        } else {
          $profile_picture_error = "There was an error updating your profile picture";
          return false;
        }
      } else {
        $profile_picture_error = "An unknown error occurred uploading the file";
        $uploadOk = false;
      }

      return $uploadOk;
    }
 ?>
