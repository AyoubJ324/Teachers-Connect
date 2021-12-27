<!DOCTYPE html>
<html>
    <head>
        <title>Notifications</title>
        <link href = "css/notifications.css" rel="stylesheet" type="text/css">

        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
        <!--<script src="/test.js"></script>-->
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>
    <body class="notificationPage">

       <?php
        require "navbar.php";
        require "notifications_utils.php";
        require "database.php";
        generateNavBar(NOTIFICATIONS);
      ?>

      <div class = "wrapper">
          <P id="heading-2"> Notifications<P>
          <a href = "deleteall.php" class = "clear-all"> Clear All</a>

          <div class="notification-tray">
              <?php
                  /**
                    * Retrieve the profile photo for the user
                    */
                  function getSenderProfilePhoto($sender, $user_type) {
                    global $conn;

                    if ($sender == "Teachers Connect Bot") {
                      return "images/bot.png";
                    }

                    $table = null;
                    if ($user_type == TEACHER) {
                      $table = "teachers";
                    } else if ($user_type == ORGANISATION) {
                      $table = "organisations";
                    }

                    if ($table != null) {
                      $sql = "SELECT profile_photo FROM {$table} WHERE username = ?;";

                      if ($stmt = $conn->prepare($sql)) {
                        $stmt->bind_param("s", $param_user);
                        $param_user = $sender;

                        $profile_photo = ($user_type == TEACHER) ? DEFAULT_TEACHER_PROFILE_PIC:DEFAULT_ORG_PROFILE_PIC;
                        if ($stmt->execute()) {
                          $result = $stmt->get_result();

                          while ($row = $result->fetch_assoc()) {
                            $photo = $row['profile_photo'];

                            if ($photo != null) {
                              $profile_photo = $photo;
                            }
                          }
                        } else {
                          die("Database Error: {$stmt->error}");
                        }

                        $stmt->close();
                        return $profile_photo;
                      } else {
                        die("Database Error: {$conn->error}");
                      }
                    }
                  }

                  /**
                    * Sets the viewed value to true of the given notification
                    */
                  function updateViewedValue($notification_id) {
                    global $conn;

                    $sql = "UPDATE notifications SET viewed = 1 WHERE id = ?;";

                    if ($stmt = $conn->prepare($sql)) {
                      $stmt->bind_param("i", $param_id);
                      $param_id = $notification_id;

                      if (!$stmt->execute()) {
                        die("Database Error: {$stmt->error}");
                      }

                      $stmt->close();
                    } else {
                      die("Database Error: {$conn->error}");
                    }
                  }

                  $sql = "SELECT * FROM notifications WHERE username = ? ORDER BY created_at DESC;";


                  if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("s", $param_username);
                    $param_username = $username;

                    if ($stmt->execute()) {
                      $result = $stmt->get_result();

                      while($row = $result->fetch_assoc()) {
                          $sender = $row['sender'];
                          $type = $row['type'];
                          $link = $row['target_link'];
                          $time = format_time($row['created_at']);
                          $id = $row['id'];
                          $username = $row['username'];

                          if ($link == null || empty($link)) {
                            $link = "#";
                          }

                          $notificationType = "";
                          $button_text = "";

                          if($type == 'view'){
                            $notificationType = "Viewed your profile";
                            $button_text = "Go to profile";
                          } else if($type == 'request'){
                            $notificationType = "Wants to connect with you";
                            $button_text = "Go to connection";
                          } else if($type == 'like'){
                            $notificationType = "Liked your post";
                            $button_text = "Go to post";
                          } else if($type == 'admin'){
                            $notificationType = "Welcome to Teachers Connect!";
                            $button_text = "Contact Us";
                            $link = "./landingpage.php#landing-contact";
                          } else if ($type == 'org_invite') {
                            $notificationType = "Wants you to join their organisation";
                            $button_text = "Go to invite";
                          } else if ($type == 'accepted') {
                            $notificationType = "Has accepted your connection request";
                            $button_text = "Go to profile";
                          } else if ($type == 'follow') {
                            $notificationType = "Has followed your organisation";
                            $button_text = "Go to profile";
                          }

                          $type = getUserType($sender);
                          $profile_photo = getSenderProfilePhoto($sender, $type);
                          $profile_photo = ($profile_photo == null || empty($profile_photo)) ? "images/logo.png":$profile_photo;
                          $sender_name = getSenderName($sender, $type);
                          $sender = ($sender_name == null || empty($sender_name)) ? $sender:$sender_name;

                          updateViewedValue($id);

                          echo "
                              <div class=\"notifications__item\">
                                  <div class=\"notifications__item__avatar\">
                                      <img src=\"{$profile_photo}\" />
                                      </div>
                                      <div class=\"notifications__item__content\">
                                      <span class=\"notifications__item__title\">{$sender}</span>
                                      <span class=\"notifications__item__message\">{$notificationType}</span>
                                      <a class = \"btn btn-primary\" href=\"{$link}\">{$button_text}</a>
                                      <div class=\"side-group\">
                                          <h6 class = \"time\"> $time</h6>
                                          <a href=\"deleteitem.php?id=$id\">Delete</a>
                                        </div>
                                  </div>
                          </div>";
                          // echo "<a href=\"deleteall.php?username=$username\">Clear</a>";

                          // echo "<a href=\"deleteall.php?username=$username\">Delete</a>";
                      }
                    } else {
                      echo "An error occurred: {$stmt->error}";
                    }
                  } else {
                    echo "An error occurred: {$conn->error}";
                  }
              ?>
          </div>
       </div>
    </body>
</html>
