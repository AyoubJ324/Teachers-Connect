<!DOCTYPE html>
<html>
  <head>
    <title>Teacher Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
    <link type="text/css" href="css/styles.css" rel="stylesheet">
    <link type="text/css" href="css/feed.css" rel="stylesheet">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body>
    <?php
      require "teacher_variables.php";
      require "navbar.php";
      require "notifications_utils.php";
      require "profile_posts_utils.php";

      $own_profile = false;
      $connected = null;
      $blocked_user = false; // true if we have blocked the viewed user
      $qualification = null; // this is the most recent qualification
      $employment_history = null; // this is the most recent employment history if any

      /**
        * These variables hold the sender and receiver of the connection between the teacher and viewer if any
        */
      $connection_sender = "";
      $connection_receiver = "";
      $connection_pending = "";

      $banned = false;
      $blacklisted = false;

      $organisation_viewer = false; // true if the user viewing the teacher is an organisation

      $contact_button = "";

      $post_id_scroll = -1;

      /**
       * Parses the URL for any GET parameters
       */
      function parseURL() {
        global $username;
        global $post_id_scroll;

        if ($_SERVER["REQUEST_METHOD"] == "GET") {
          $parsed_url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY);
          $params = array();
          parse_str($parsed_url, $params);

          if (isset($params['username'])) {
            $username = $params['username'];
          }

          if (isset($params['post_id'])) {
            $post_id_scroll = $params['post_id'];
          }
        }
      }

      /**
        * This function loads the teacher's most recent qualification if any
        */
      function loadRecentQualification() {
        global $username;
        global $conn;
        global $qualification;
        global $teacher;

        $sql = "SELECT * FROM qualifications NATURAL JOIN academic_degrees WHERE username = ?
          AND date_obtained = (SELECT MAX(date_obtained) FROM qualifications WHERE username = ?)";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("ss", $param_user, $param_user);
          $param_user = $username;

          if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows == 1) {
              while ($row = $result->fetch_assoc()) {
                $academic_degree = new AcademicDegree($row['degree_id'],
                  $row['title'], $row['type'], $row['school'], $row['description'], $row['level']);
                $qualification = new Qualification($teacher, $academic_degree, $row['date_obtained']);
              }
            }

            $stmt->close();
          } else {
            doSQLError($stmt->error);
          }
        } else {
          doSQLError($conn->error);
        }
      }

      /**
        * Loads the most recent employment history if available
        */
      function loadRecentEmploymentHistory() {
        global $username;
        global $teacher;
        global $conn;
        global $employment_history;

        $sql = "SELECT * FROM employment_history JOIN organisations ON employment_history.organisation_id = organisations.organisation_id
          WHERE employment_history.username = ? AND (dateTo IS NULL OR dateTo = (SELECT MAX(dateTo) FROM employment_history WHERE username = ?));"; // NULL means present

          if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ss", $param_user, $param_user);
            $param_user = $username;

            if ($stmt->execute()) {
              $result = $stmt->get_result();

              if ($result->num_rows >= 1) {
                while ($row = $result->fetch_assoc()) {
                  $date_to = $row['dateTo'];
                  $organisation = new Organisation($row['organisation_id'],
                    $row['username'], $row['name'], $row['headline'],
                    $row['about'], $row['location'], $row['profile_photo']);
                  $employment_history = new EmploymentHistory($row['history_id'],
                    $teacher, $organisation, $row['dateFrom'], $date_to, $row['job_title']);

                    if ($date_to == null) {
                      break;
                    }
                }
              }

              $stmt->close();
            } else {
              doSQLError($stmt->error);
            }
          } else {
            doSQLError($conn->error);
          }
      }

      /**
        * Check if you are connected with the current user
        */
      function checkConnection() {
        global $connected;

        if (!isset($connected)) {
          global $username;
          global $conn;
          global $connection_sender;
          global $connection_receiver;
          global $connection_pending;

          $loggedin_username = $_SESSION[USERNAME];

          $sql = "SELECT * FROM connections WHERE (destination = ? AND sender = ?) OR (destination = ? AND sender = ?) LIMIT 1;";

          if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssss", $param_dest1, $param_send1, $param_dest2, $param_send2);
            $param_dest1 = $loggedin_username;
            $param_send1 = $username;
            $param_dest2 = $username;
            $param_send2 = $loggedin_username;

            if ($stmt->execute()) {
              $result = $stmt->get_result();
              if ($result->num_rows == 1) {
                while ($row = $result->fetch_assoc()) {
                  $connection_sender = $row['sender'];
                  $connection_receiver = $row['destination'];
                  $connection_pending = $row['status'] == "pending";

                  $connected = true;
                }
              } else {
                $connection_sender = $loggedin_username;
                $connection_receiver = $username;
                $connected = false;
              }
            } else {
              doSQLError($stmt->error);
              $connected = false;
            }

            $stmt->close();
          } else {
            doSQLError($conn->error);
            $connected = false;
          }
        }

        return $connected;
      }

      /**
        * Determine if connection is pending and the logged in user is the destination
        */
      function displayAcceptConnection() {
        global $connection_pending;
        global $connection_receiver;

        $loggedin_username = $_SESSION[USERNAME];
        return $connection_pending && $loggedin_username == $connection_receiver;
      }

      /**
        * Determine if connection is pending and you sent the connection
        */
      function displayConnectionRequested() {
        global $connection_pending;
        global $connection_sender;

        $loggedin_username = $_SESSION[USERNAME];
        return $connection_pending && $loggedin_username == $connection_sender;
      }

      /**
        * Sends the profile viewed notification
        */
      function sendNotification() {
        global $username;
        global $own_profile;

        if (!$own_profile) {
          $sender = $_SESSION[USERNAME];
          $user_type = $_SESSION[USER_TYPE];

          $link = ($user_type == TEACHER) ? "teacher_profile.php?username={$sender}":"organisation_profile.php?username={$sender}";

          $notification = new ViewNotification($sender, $username, false, $link, null);
          addNotification($notification);
        }
      }

      /**
        * If this profile is not the user's own profile, it checks if they have been blocked.
        * Returns true if they can be viewed, false if not
        */
      function canView() {
        global $own_profile;

        if (!$own_profile && $_SESSION[USER_TYPE] == TEACHER) {
          global $conn;
          global $username;

          $loggedin_username = $_SESSION[USERNAME];

          $sql = "SELECT * FROM blocked_teachers WHERE (blocked_teacher = ? AND blocker = ?) OR (blocked_teacher = ? AND blocker = ?);";

          if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ssss", $param_blocked1, $param_blocker1, $param_blocked2, $param_blocker2);
            $param_blocked1 = $loggedin_username;
            $param_blocker1 = $username;
            $param_blocked2 = $username;
            $param_blocker2 = $loggedin_username;

            if ($stmt->execute()) {
              $result = $stmt->get_result();

              if ($result->num_rows == 1) {
                while ($row = $result->fetch_assoc()) {
                  $blocked = $row['blocked_teacher'];
                  $blocker = $row['blocker'];

                  if ($blocked == $loggedin_username) {
                    doError("You have been blocked by {$row['blocker']}. You cannot view this profile");
                    $stmt->close();
                    return false;
                  } else if ($blocked == $username && $blocker == $loggedin_username) {
                    global $blocked_user;
                    $blocked_user = true;

                    $stmt->close();
                    return true;
                  }
                }
              }

              $stmt->close();
              return true;
            } else {
              doSQLError($stmt->error);
            }

            $stmt->close();
          } else {
            doSQLError($conn->error);
          }

          return false;
        } else {
          return true;
        }
      }

      /**
        * Retrieves the organisation that is viewing this user
        */
      function getViewingOrganisation() {
        global $organisation_viewer;
        global $conn;

        if ($organisation_viewer) {
          $loggedin_username = $_SESSION[USERNAME];
          $sql = "SELECT * FROM organisations WHERE username = ?;";

          if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $loggedin_username;

            $organisation = null;
            if ($stmt->execute()) {
              $result = $stmt->get_result();

              if ($row = $result->fetch_assoc()) {
                $organisation = new Organisation($row['organisation_id'], $loggedin_username,
                $row['name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);
              }
            } else {
              doSQLError($stmt->error);
            }

            $stmt->close();
            return $organisation;
          } else {
            doSQLError($conn->error);
          }
        }

        return null;
      }

      /**
        * Get the button that invites the user to join the organisation if the viewer is an organisation
        * and the user's current organisation isn't already this one's
        */
      function getInviteButton() {
        global $organisation_viewer;
        global $current_organisation;

        if ($organisation_viewer) {
          $organisation = getViewingOrganisation();

          if ($organisation != null && $organisation != $current_organisation) {
            echo "<button class=\"btn btn-primary\" onclick=\"handleOrganisationInvite();\" style=\"margin-right: 1vw;\">Invite to Organisation</button>";
          }
        }
      }

      /**
        * Get the button for the primary button on profile header
        */
      function getPrimaryProfileButton() {
        global $own_profile;

        $btn_class = "\"btn btn-primary\"";
        $btn_target = ($own_profile) ? "onclick=\"handleEdit();\"":"onclick=\"handleConnection();\"";
        $btn = "<button class={$btn_class} style=\"margin-right: 1vw;\" id=\"connect-button\" {$btn_target}>";
        if ($own_profile) {
          return "{$btn}Edit</button></a>";
        } else {
          $connected = checkConnection();
          $text = ($connected) ? "Connected":"Connect";

          if (displayAcceptConnection()) {
            $text = "Accept Connection";
          } else if (displayConnectionRequested()) {
            $text = "Connection Requested";
          }

          return "{$btn}{$text}</button>";
        }
      }

      /**
        * Get the button to reject the connection
        */
      function getRejectConnectionButton() {
        if (displayAcceptConnection()) {
          echo "<button class=\"btn btn-danger\" onclick=\"handleRejectConnection();\" style=\"margin-right: 1vw;\" id=\"reject-button\">Reject Connection</button>";
        }
      }

      /**
        * Gets the contact teacher button
        */
      function loadContactButton() {
        global $username;
        global $conn;
        global $contact_button;
        global $user_type;
        global $own_profile;

        if (!$own_profile) {
          $sql = "SELECT email FROM accounts WHERE username = ?;";

          if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;

            if ($stmt->execute()) {
              $row = $stmt->get_result()->fetch_assoc();

              if ($row) {
                $loggedin_username = $_SESSION[USERNAME];
                $type = $user_type;
                $name = getSenderName($loggedin_username, $type);

                if ($type == TEACHER) {
                  $type = "Teacher";
                } else if ($type == ORGANISATION) {
                  $type = "Organisation";
                } else if ($type == ADMIN) {
                  $type = "Administrator";
                }

                $contact_button = '<a href="mailto:'. $row['email'] . '?subject=Message from ' . $type . ' ' . $name . ' on TeachersConnect" class="btn btn-primary" style="margin-right: 1vw;">Contact Teacher</a>';
              }
            } else {
              doSQLError($stmt->error);
            }

            $stmt->close();
          } else {
            doSQLError($conn->error);
          }
        }
      }

      $loggedin_username = $_SESSION[USERNAME];
      $user_type = $_SESSION[USER_TYPE];

      // parse the URL for get parameters which may include a username to view a different user's profile
      parseURL();

      $own_profile = $user_type == ADMIN || $loggedin_username == $username; // if user is admin treat as own profile. If not, it's our own profile is username matches logged in one

      if (canView()) {
        loadTeacher($username);

        $organisation_viewer = $user_type == ORGANISATION;

        if (empty($error_message)) {
          loadCurrentOrganisation($username);
          if (empty($error_message)) {
            loadRecentQualification();

            if (empty($error_message)) {
              loadRecentEmploymentHistory();
              if (empty($error_message)) {
                checkBanned($username);
                checkBlacklist($username);

                if ($user_type != ADMIN && ($banned || $blacklisted)) {
                  doError("You cannot view this profile as the user has been banned");
                } else {
                  sendNotification();
                }

                if (empty($error_message)) {
                  loadTeacherPosts($teacher);

                  if (empty($error_message)) {
                    loadContactButton();
                  }
                }
              }
            }
          }
        }
      }

      /**
        * Retrieve the dates of the recent organisation if not null
        */
      function getEmploymentDates() {
        global $employment_history;

        if (isset($employment_history)) {
          $from = strtotime($employment_history->dateFrom());
          $to = $employment_history->dateTo();
          $from = date("d/m/Y", $from);
          if ($to != null) {
            $to = strtotime($to);
            $to = date("d/m/Y", $to);
          } else {
            $to = "Present";
          }

          return "{$from} - {$to}";
        }
      }

      /**
        * Load and display this user's skills
        */
      function loadSkills() {
        global $username;
        global $conn;

        $sql = "SELECT * FROM teacher_skills JOIN skills on teacher_skills.skill_id = skills.skill_id WHERE username = ?;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("s", $param_username);
          $param_username = $username;

          if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
              echo "<div class=\"row padding-5pcent-lr\"><ul class=\"list-group list-group-flush\">";
              while ($row = $result->fetch_assoc()) {
                echo "<li class=\"list-group-item\">{$row['name']}</li>";
              }
              echo "</ul></div>";
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

     <?php
        generateNavBar(PROFILE);
        $error_occurred = displayError();

        if (!$error_occurred): // only display the profile if an error didn't occur
      ?>
     <div class="container main-background">
       <div class="row">
         <div class="alert alert-info" id="ajax-progress"></div>
       </div>
       <div class="row shadow profile-card padding-1pcent" id="profile-header">
        <div class="row">
          <h4 class="underlined-header">Teacher</h4>
        </div>
        <div class="col-3">
          <img class="img-fluid rounded-circle" src="<?php $photo = $teacher->profile_photo(); echo ($photo == null) ? DEFAULT_TEACHER_PROFILE_PIC:$photo; ?>" alt="profile-picture">
        </div>
        <div class="col-9">
          <h3><?php echo "{$teacher->firstName()} {$teacher->lastName()}"; ?></h3>
          <h4 class="subtitle"><?php $headline = $teacher->headline(); echo ($headline == null) ? "":$headline; ?></h4>
          <h5><?php echo $teacher->location(); ?></h5>
          <p class="about-me-text new-line-text"><?php $about = $teacher->about(); echo ($about == null) ? "":$about; ?></p>
        </div>
        <?php
          if (isset($current_organisation)):
        ?>
        <div class="row text-align-center">
          <div class="col current-organisation d-flex align-items-center">
            <?php if (!empty($current_organisation->username())): ?>
            <a href="organisation_profile.php?username=<?php echo $current_organisation->username(); ?>"><h5><?php echo $current_organisation->name(); ?></h5></a>
            <?php else: ?>
            <h5><?php echo $current_organisation->name(); ?></h5>
            <?php endif; ?>
            <img class="img-fluid rounded-circle current-organisation-photo" src="<?php $photo = ($current_organisation != null) ? $current_organisation->profile_photo():null; echo ($photo == null) ? DEFAULT_ORG_PROFILE_PIC:$photo; ?>" alt="organisation-photo">
          </div>
        </div>
        <?php endif; ?>
        <div class="row mt-2">
          <div class="btn-toolbar">
            <?php if ($user_type == TEACHER || $user_type == ADMIN): ?>
            <?php echo getPrimaryProfileButton(); ?>
            <?php getRejectConnectionButton(); ?>
            <?php if (!$own_profile): ?>
            <?php
              if (!empty($contact_button)) {
                echo $contact_button;
              }
            ?>
            <div class="dropdown">
              <button class="btn btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                More
              </button>
              <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                <button class="btn btn-light w-100" id="block-button" onclick="handleBlock();"><?php echo ($blocked_user) ? "Unblock":"Block"; ?></button>
              </div>
            </div>
          <?php endif; ?>
          <?php if ($user_type == ADMIN): ?>
            <?php getBlockButton(); ?>
            <?php getBlacklistButton(); ?>
          <?php endif; ?>
          <?php endif; ?>
          <?php
            getInviteButton();
            if ($user_type == ORGANISATION && !empty($contact_button)) {
              echo $contact_button;
            }
            ?>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="col shadow profile-card right-margin-1pcent">
          <div class="row">
            <h4 class="underlined-header">Most Recent Education</h4>
          </div>
          <?php if (isset($qualification)): ?>
          <div class="row align-items-center">
            <div class="col">
              <h4><?php echo $qualification->degree()->title(); ?></h4>
              <h6><?php echo $qualification->degree()->type(); ?></h6>
              <h6><?php echo $qualification->degree()->school(); ?></h6>
              <h5 class="subtitle"><?php echo formatDate($qualification->date_obtained()); ?></h5>
            </div>
            <div class="col">
              <p><?php echo $qualification->degree()->description(); ?></p>
            </div>
          </div>
          <div class="row justify-content-center text-center">
            <div class="col">
              <?php
                $data = array('username' => $username);
                $query = http_build_query($data);
                $url = "teacher_education_history.php?" . $query;

                echo "<a href=\"{$url}\">See more</a>";
               ?>
            </div>
          </div>
        <?php endif; ?>
        </div>
        <div class="col shadow profile-card">
          <div class="row">
            <h4 class="underlined-header">Most Recent Employment</h4>
          </div>
          <?php if (isset($employment_history)): ?>
          <div class="row align-items-center">
            <div class="col-4">
              <img class="img-fluid rounded-circle" src="<?php $photo = $employment_history->organisation()->profile_photo(); echo ($photo == null) ? DEFAULT_ORG_PROFILE_PIC:$photo; ?>" alt="org_photo">
            </div>
            <div class="col-8">
              <?php if ($employment_history->organisation()->username() != null): ?>
                <a href="organisation_profile.php?username=<?php echo $employment_history->organisation()->username(); ?>">
                  <h4><?php echo $employment_history->organisation()->name(); ?></h4>
                </a>
              <?php else: ?>
                <h4><?php $organisation = $employment_history->organisation(); echo "{$organisation->name()}, {$organisation->location()}"; ?></h4>
              <?php endif; ?>
              <h5><?php echo $employment_history->job_title()?></h5>
              <h5 class="subtitle"><?php echo getEmploymentDates(); ?></h5>
            </div>
          </div>
          <div class="row justify-content-center text-center">
            <div class="col">
              <?php
                $data = array('username' => $username);
                $query = http_build_query($data);
                $url = "teacher_employment_history.php?" . $query;

                echo "<a href=\"{$url}\">See more</a>";
               ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="row shadow profile-card">
        <div class="row">
          <h4 class="underlined-header">Skills</h4>
        </div>
        <?php loadSkills(); ?>
      </div>
      <div class="row shadow profile-card">
        <div class="row">
          <h4 class="underlined-header">Posts</h4>
        </div>
        <?php displayPosts(); ?>
      </div>

      <?php require "ban_modal.php"; ?>
      <?php require "blacklist_modal.php"; ?>

      <?php endif; ?>
    </div>

    <script type="text/javascript" src="forms.js"></script>
    <script type="text/javascript" src="ajax.js"></script>
    <script>
      const username = <?php echo json_encode($username); ?>;
      const loggedin_username = <?php echo json_encode($_SESSION[USERNAME]); ?>;
      var own_profile = <?php echo json_encode($own_profile); ?>;
      var organisation_viewer = <?php echo json_encode($organisation_viewer); ?>;
      var blocked_user = <?php echo json_encode($blocked_user); ?>;
      var connected = <?php echo json_encode(checkConnection()); ?>;
      var connection_pending = <?php echo json_encode($connection_pending); ?>;
      var connection_sender = <?php echo json_encode($connection_sender); ?>;
      var connection_receiver = <?php echo json_encode($connection_receiver); ?>;
      var accept_connection = <?php echo json_encode(displayAcceptConnection()); ?>;
      var request_sent = <?php echo json_encode(displayConnectionRequested()); ?>;

      var ajax_progress = document.getElementById('ajax-progress');
      ajax_progress.style.display = "none";
      var profile_header = document.getElementById('profile-header');

      var connectButton = document.getElementById('connect-button');
      if (blocked_user) {
        connectButton.style.display = "none";
      }

      var banned = <?php echo json_encode($banned); ?>;
      var blacklisted = <?php echo json_encode($blacklisted); ?>;

      var reject_button = document.getElementById('reject-button');

      const post_id_scroll = <?php echo json_encode($post_id_scroll); ?>;

			if (post_id_scroll != -1) {
				var post = document.getElementById(`post-card-${post_id_scroll}`);
				if (post != null) {
					post.scrollIntoView();
				}
			}

      /**
        * Updates the ajax_progress message
        */
      function update_progress(progress_message, display) {
        if (!display) {
          setTimeout(function() {
            ajax_progress.style.display = "none";
            ajax_progress.innerHTML = "";
            ajax_progress.style.marginTop = "0%";
            ajax_progress.style.marginBottom = "0%";
            profile_header.style.marginTop = "2%";}, 1000);
        } else {
          ajax_progress.style.display = "block";
          ajax_progress.innerHTML = progress_message;
          ajax_progress.style.marginTop = "2%";
          ajax_progress.style.marginBottom = "2%";
          profile_header.style.marginTop = "0%";
        }
      }

      /**
        * Handle conection response. Pass in null if you don't want callback
        */
      function handleConnectionResponse(responseBody, callback) {
        var success = responseBody.success;
        var message = responseBody.message;
        var button = document.getElementById('connect-button');

        if (success) {
          if (message == "REMOVED") {
            if (button != null)
              button.innerHTML = "Connect";
            connected = false;
            connection_pending = false;
            request_sent = false;
            connection_sender = loggedin_username;
            connection_receiver = username;
            accept_connection = false;
          } else if (message == "PENDING") {
            if (button != null)
              button.innerHTML = "Connection Requested";
            connected = true;
            connection_pending = true;
            request_sent = true;
          } else if (message == "ACCEPTED") {
            if (button != null)
              button.innerHTML = "Connected";

            reject_button.remove();
            connected = true;
            connection_pending = false;
            request_sent = false;
          }

          if (callback != null) {
            callback();
          } else {
            update_progress("", false);
          }
        } else {
          alert(message);
          connected = false;
          connection_pending = false;
        }
      }

      /**
        * Get the url to remove a connection
        */
      function getRemoveConnectionURL() {
        return `profile-action-ajax.php?action=connect&sender=${connection_sender}&destination=${connection_receiver}&action_param=remove`;
      }

      /**
        * Handles connection press
        */
      function handleConnection() {
        var ajaxRequest = getAJAX();

        if (ajaxRequest != null) {
          ajaxRequest.onreadystatechange = function() {
            if (ajaxRequest.readyState == 4) {
              var response = ajaxRequest.response;
              try {
                var responseBody = JSON.parse(response);
                handleConnectionResponse(responseBody, null);
              } catch (e) {
                alert(e);
              }
            }
          }

          var url = "profile-action-ajax.php";
          var data = {};
          data['action'] = "connect";
          data['sender'] = connection_sender;
          data['destination'] = connection_receiver;

          if (!connected) {
            update_progress("Sending connection request", true);
            data['action_param'] = "add";
          } else {
            if (connection_pending) {
              if (accept_connection) {
                update_progress("Accepting connection request", true);
                data['action_param'] = "accept";
              } else if (request_sent) {
                update_progress("Removing connection request", true);
                data['action_param'] = "remove";
              }
            } else {
              update_progress("Removing connection", true);
              data['action_param'] = "remove";
            }
          }

          ajaxRequest.open("POST", url, true);
          var json = JSON.stringify(data);
          ajaxRequest.send(json);
        }
      }

      /**
        * Handles rejecting the connection
        */
      function handleRejectConnection() {
        var ajaxRequest = getAJAX();

        if (ajaxRequest != null) {
          ajaxRequest.onreadystatechange = function() {
            if (ajaxRequest.readyState == 4) {
              var response = ajaxRequest.response;
              try {
                var responseBody = JSON.parse(response);
                var success = responseBody.success;
                var message = responseBody.message;

                if (success) {
                  if (message == "REMOVED") {
                    reject_button.remove();

                    var connect_button = document.getElementById('connect-button');
                    connect_button.innerHTML = "Connect";

                    connection_pending = false;
                    connected = false;
                    request_sent = false;
                    accept_connection = false;

                    connection_sender = loggedin_username;
                    connection_receiver = username;
                  }

                  update_progress("", false);
                } else {
                  alert(message);
                }
              } catch (e) {
                alert(e);
              }
            }
          }

          var url = "profile-action-ajax.php";
          var data = {};
          data['action'] = "connect";
          data['sender'] = connection_sender;
          data['destination'] = connection_receiver;
          data['action_param'] = "remove";

          ajaxRequest.open("POST", url, true);
          var json = JSON.stringify(data);
          ajaxRequest.send(json);
          update_progress("Rejecting connection request", true);
        }
      }

      /**
        * Callback for blocking the user
        */
      function blockCallback() {
        var ajaxRequest = getAJAX();

        if (ajaxRequest != null) {
          ajaxRequest.onreadystatechange = function() {
            if (ajaxRequest.readyState == 4) {
              var response = ajaxRequest.response;
              try {
                var responseBody = JSON.parse(response);
                var success = responseBody.success;
                var message = responseBody.message;
                var button = document.getElementById('block-button');

                if (success) {
                  if (message == "REMOVED") {
                    button.innerHTML = "Block";
                    blocked_user = false;
                    if (connectButton != null) {
                      connectButton.style.display = "block";
                      connectButton.innerHTML = "Connect";
                    }
                  } else if (message == "BLOCKED") {
                    button.innerHTML = "Blocked";
                    window.location.href = "teacher_profile.php";
                  }

                  update_progress("", false);
                } else {
                  alert(message);
                }
              } catch (e) {
                alert(e);
              }
            }
          }

          var url = "profile-action-ajax.php";
          var data = {};
          data['action'] = "block";
          data['sender'] = loggedin_username;
          data['destination'] = username;

          if (!own_profile) {
            if (!blocked_user) {
              data['action_param'] = "add";
            } else {
              data['action_param'] = "remove";
            }

            ajaxRequest.open("POST", url, true);
            var json = JSON.stringify(data);
            ajaxRequest.send(json);
          }
        }
      }

      /**
        * Handle a block request
        */
      function handleBlock() {
        var ajaxRequest = getAJAX();

        if (ajaxRequest != null) {
          ajaxRequest.onreadystatechange = function() {
            if (ajaxRequest.readyState == 4) {
              var response = ajaxRequest.response;
              try {
                var responseBody = JSON.parse(response);
                handleConnectionResponse(responseBody, blockCallback); // we have removed any connection between these users, so we can now block them
              } catch (e) {
                alert(e);
              }
            }
          }

          var url = "profile-action-ajax.php";
          var data = {};
          data['action'] = "connect";
          data['sender'] = connection_sender;
          data['destination'] = connection_receiver;
          data['action_param'] = "remove";

          if (!own_profile) {
            if (!blocked_user) {
              update_progress("Blocking user", true);
              ajaxRequest.open("POST", url, true);
              var json = JSON.stringify(data);
              ajaxRequest.send(json);
            } else {
              update_progress("Unblocking user", true);
              blockCallback();
            }
          }
        }
      }

      /**
        * Handles the edit button being pressed
        */
      function handleEdit() {
        window.location.href = window.location.href = `edit_teacher.php?username=${username}`;
      }

      /**
        * Add return url to the form
        */
      function addReturnURL(form) {
        var returnURL = document.createElement('input');
        returnURL.type = "hidden";
        returnURL.id = "return_url";
        returnURL.name = "return_url";
        returnURL.value = "teacher_profile.php?username=" + username;
        form.appendChild(returnURL);
      }

      /**
        * Ban the user
        */
      function ban() {
        var form = document.forms['ban_user_form'];
        form.action = "administration_action.php";
        form.method = "post";
        addReturnURL(form);

        var valid = true;
        if (!banned)
          valid = validateForm('ban_user_form');

        if (valid) {
          var action = document.createElement("input");
          action.type = "hidden";
          action.id = "action";
          action.name = "action";
          action.value = banned ? "unban":"ban";
          form.appendChild(action);

          var usernameField = document.getElementById('username');
          var adminField = document.getElementById('admin');
          if (banned) {
            usernameField.value = username;
            adminField.value = loggedin_username;
          }

          form.submit();
        }
      }

      /**
        * Blacklist the user
        */
      function blacklist() {
        var form = document.createElement('form');
        form.action = "administration_action.php";
        form.method = "post";
        addReturnURL(form);

        var usernameField = document.createElement('input');
        usernameField.id = "username";
        usernameField.name = "username";
        usernameField.value = username;
        usernameField.type = "hidden";
        form.appendChild(usernameField);

        var admin = document.createElement('input');
        admin.id = "admin";
        admin.name = "admin";
        admin.value = loggedin_username;
        admin.type = "hidden";
        form.appendChild(admin);

        var action = document.createElement('input');
        action.type = "hidden";
        action.id = "action";
        action.name = "action";
        action.value = blacklisted ? "unblacklist":"blacklist";
        form.appendChild(action);

        document.body.appendChild(form);
        form.submit();
      }

      /**
        * Handles the organisation invite using AJAX
        */
      function handleOrganisationInvite() {
        if (organisation_viewer) {
          var ajaxRequest = getAJAX();

          if (ajaxRequest != null) {
            ajaxRequest.onreadystatechange = function() {
              if (ajaxRequest.readyState == 4) {
                var response = ajaxRequest.response;
                try {
                  update_progress("Teacher invited", true);
                  update_progress("", false);
                } catch (e) {
                  alert(e);
                }
              }
            }

            var url = "profile-action-ajax.php";
            var data = {};
            data['action'] = "organisation_invite";
            data['sender'] = loggedin_username;
            data['destination'] = username;
            data['action_param'] = "add";

            update_progress("Sending invite to user", true);
            ajaxRequest.open("POST", url, true);
            var json = JSON.stringify(data);
            ajaxRequest.send(json);
          }
        }
      }

      function handlePostLike(post_id, creator_username) {
				var data = {};
				data['post_id'] = post_id;
				data['creator_username'] = creator_username;
				data['username'] = loggedin_username;
				data['edit_form'] = 'post_like';
        data['like_source'] = 'teacher_profile.php';

				var ajax = getAJAX();
				if (ajax != null) {
					ajax.onreadystatechange = function() {
							if (ajax.readyState == 4) {
								var response = ajax.response;

								try {
									var responseBody = JSON.parse(response);
									var success = responseBody.success;
									var message = responseBody.message;

									if (success) {
										var data = responseBody.data;
										var post_id = data['post_id'];
										var button = document.getElementById(`post-${post_id}`);

										if (message == "LIKED") {
											button.classList.remove('btn-primary');
											button.classList.add('btn-danger');
											button.innerHTML = "Unlike 👍";
										} else if (message == "REMOVED") {
											button.classList.remove('btn-danger');
											button.classList.add('btn-primary');
											button.innerHTML = "Like 👍";
										}
									} else {
										addAlertMessage(false, "An error occurred liking post: " + message, `post-card-${post_id}`);
									}
								} catch (e) {
									alert(e);
								}
							}
						}

						ajax.open("POST", "feed-action-ajax.php", true);
						ajax.send(JSON.stringify(data));
				}
			}

      function deletePost(post_id) {
				var data = {};
				data['post_id'] = post_id;
				data['username'] = loggedin_username;
				data['edit_form'] = 'post_delete';

				var ajax = getAJAX();
				if (ajax != null) {
					ajax.onreadystatechange = function() {
							if (ajax.readyState == 4) {
								var response = ajax.response;

								try {
									var responseBody = JSON.parse(response);
									var success = responseBody.success;
									var message = responseBody.message;

									if (success) {
										var data = responseBody.data;
										var post_id = data['post_id'];
										var element = document.getElementById(`post-card-${post_id}`);

										if (message == "REMOVED" && element != null) {
											element.remove();
										}
									} else {
										addAlertMessage(false, "An error occurred removing post: " + message, `post-card-${post_id}`);
									}
								} catch (e) {
									alert(e);
								}
							}
						}

						ajax.open("POST", "feed-action-ajax.php", true);
						ajax.send(JSON.stringify(data));
				}
			}
    </script>
  </body>
</html>
