<!DOCTYPE html>
<html>
  <head>
    <title>Organisation Invites</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
    <link type="text/css" href="css/styles.css" rel="stylesheet">
    <link type="text/css" href="css/feed.css" rel="stylesheet">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body>
    <?php
      require "navbar.php";
      require "error.php";
      require "notifications_utils.php";

      $invites = array();

      $invitation_id = -1;
      $specified_invitation = null;

      /**
        * Parse the get url for the invitation id if provided
        */
      function parseGET() {
        global $invitation_id;

        if ($_SERVER["REQUEST_METHOD"] == "GET") {
          $parsed_url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY);
          $params = array();
          parse_str($parsed_url, $params);

          if (isset($params['invitation_id'])) {
            $invitation_id = $params['invitation_id'];
          }
        }
      }

      /**
        * Load the invitation with the specified id as the first one
        */
      function loadSpecifiedInvitation() {
        global $specified_invitation;
        global $conn;
        global $username;
        global $invitation_id;

        $sql = "SELECT * FROM notifications WHERE type = 'org_invite' AND username = ? AND id = ?;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("si", $param_username, $param_id);
          $param_username = $username;
          $param_id = $invitation_id;

          if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
              $specified_invitation = new OrgInviteNotification($row['sender'], $row['username'], $row['viewed']
              , $row['target_link'], $row['created_at']);
            }
          } else {
            doSQLError($conn->error);
          }

          $stmt->close();
        } else {
          doSQLError($conn->error);
        }
      }

      /**
        * Loads the invites into the array
        */
      function loadInvites() {
        global $invites;
        global $conn;
        global $username;
        global $invitation_id;
        global $specified_invitation;

        if ($invitation_id != -1) {
          loadSpecifiedInvitation();
        }

        $sql = "SELECT * FROM notifications WHERE type = 'org_invite' AND username = ?  AND id != ? ORDER BY created_at DESC;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("si", $param_username, $param_id);
          $param_username = $username;
          $param_id = $invitation_id;

          if ($stmt->execute()) {
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
              $invite = new OrgInviteNotification($row['sender'], $row['username'], $row['viewed'],
              $row['target_link'], $row['created_at']);

              $invites[$row['id']] = $invite;
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
        * Retrieve the profile photo for the user
        */
      function getSenderProfilePhoto($sender) {
        global $conn;

        $sql = "SELECT profile_photo FROM organisations WHERE username = ?;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("s", $param_user);
          $param_user = $sender;

          $profile_photo = DEFAULT_ORG_PROFILE_PIC;
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

      /**
        * Displays the selected invitation in its own div
        */
      function displaySelectedInvitation() {
        global $specified_invitation;
        global $invitation_id;

        if ($invitation_id != -1) {
          if ($specified_invitation != null) {
            $value = $specified_invitation;
            $sender = $value->getSender();
            $profile_photo = getSenderProfilePhoto($sender);
            $name = getSenderName($sender, ORGANISATION);
            echo "<div class=\"row align-items-center bg-light m-auto\" id=\"invite-{$invitation_id}\">";
            echo "<div class=\"col\">";
            echo "<img class=\"img-fluid rounded-circle\" src=\"{$profile_photo}\"></div>";
            echo "<div class=\"col\">";
            echo "<h4>{$name}</h4></div>";
            echo "<div class=\"col text-end\">";
            echo '<button class="btn btn-primary" type="button" onclick="handleInviteAccept(' . $invitation_id . ', \'' . $value->getReceiver() . '\', \'' . $sender . '\');">Accept</button>';
            echo '<button class="btn btn-secondary" style="margin-left: 5%;" type="button" onclick="handleInviteRejection(' . $invitation_id . ');">Reject</button></div>';
            echo "<div class=\"col text-end\">";
            echo "<h6 class=\"subtitle\">" . format_time($value->getCreated_at()) . "</h6></div>";
            echo "</div>";
          } else {
            echo "<div class=\"row text-center bg-light m-auto\">";
            echo "The selected invitation no longer exists";
            echo "</div>";
          }
        }
      }

      /**
        * Displays the invites that were loaded
        */
      function displayInvites() {
        global $invites;

        $number_invites = count($invites);

        if ($number_invites > 0) {
          $i = 0;
          foreach ($invites as $key => $value) {
            $sender = $value->getSender();
            $profile_photo = getSenderProfilePhoto($sender);
            $name = getSenderName($sender, ORGANISATION);
            echo "<div class=\"row align-items-center m-auto\" id=\"invite-{$key}\">";
            echo "<div class=\"col\">";
            echo "<img class=\"img-fluid rounded-circle\" src=\"{$profile_photo}\"></div>";
            echo "<div class=\"col\">";
            echo "<h4>{$name}</h4></div>";
            echo "<div class=\"col text-end\">";
            echo '<button class="btn btn-primary" type="button" onclick="handleInviteAccept(' . $key . ', \'' . $value->getReceiver() . '\', \'' . $sender . '\');">Accept</button>';
            echo '<button class="btn btn-secondary" style="margin-left: 5%;" type="button" onclick="handleInviteRejection(' . $key . ');">Reject</button></div>';
            echo "<div class=\"col text-end\">";
            echo "<h6 class=\"subtitle\">" . format_time($value->getCreated_at()) . "</h6></div>";
            echo "</div>";

            if ($i < ($number_invites - 1)) {
              echo "<hr class=\"mt-2\">";
            }

            $i++;
          }
        }
      }

      parseGET();
      loadInvites();
     ?>

    <?php
       generateNavBar(NOTIFICATIONS);
       $error_occurred = displayError();

       if (!$error_occurred): // only display the profile if an error didn't occur
     ?>
    <div class="container main-background">
      <div class="row mt-2">
        <h4 class="underlined-header">Organisation Invites</h4>
        <p>Any invites from organisations who wish for you to join them will appear here</p>
      </div>
      <div class="row">
        <div class="alert alert-info" id="ajax-progress"></div>
      </div>
      <?php if ($invitation_id != -1): ?>
      <div class="row card" id="invites_box">
        <h5 class="underlined-header">Selected Invitation</h5>
        <?php displaySelectedInvitation(); ?>
      </div>
      <?php endif; ?>
      <?php if (count($invites) > 0): ?>
      <div class="row card" id="<?php echo ($invitation_id == -1) ? 'invites_box':'secondary_invites_box'; ?>">
        <?php displayInvites(); ?>
      </div>
      <?php endif; ?>
      <div class="row card text-center" id="no_invites">
        <?php echo ($specified_invitation != null) ? "No other organisation invites available":"No organisation invites available"; ?>
      </div>
    </div>
    <?php endif; ?>

    <script type="text/javascript" src="ajax.js"></script>
    <script>
      const username = <?php echo json_encode($username); ?>;

      var ajax_progress = document.getElementById('ajax-progress');
      ajax_progress.style.display = "none";
      var invites_box = document.getElementById('invites_box');

      var no_invites = document.getElementById('no_invites');

      var number_invites = <?php echo json_encode(count($invites)); ?>;

      var invitation_id = <?php echo json_encode($invitation_id); ?>;
      var secondary_invites_box = document.getElementById('secondary_invites_box');

      updateNoInvitesMessage();

      /**
        * Reset invite boxes after the selected one has been removed
        */
      function resetInviteBoxes() {
        if (invitation_id != -1 && secondary_invites_box != null) {
          invitation_id = -1;
          invites_box.remove();
          secondary_invites_box.id = 'invites_box';
          invites_box = secondary_invites_box;
        }
      }

      /**
        * Updates the display value for the no invites message
        */
      function updateNoInvitesMessage() {
        if (invitation_id == -1) {
          if (number_invites > 0) {
            invites_box.style.display = "block";
            no_invites.style.display = "none";
          } else {
            invites_box.style.display = "none";
            no_invites.style.display = "block";
          }
        } else {
          if (number_invites > 0) {
            secondary_invites_box.style.display = "block";
            no_invites.style.display = "none";
          } else {
            secondary_invites_box.style.display = "none";
            no_invites.style.display = "block";
          }
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
            invites_box.style.marginTop = "2%";}, 1000);
        } else {
          ajax_progress.style.display = "block";
          ajax_progress.innerHTML = progress_message;
          ajax_progress.style.marginTop = "2%";
          ajax_progress.style.marginBottom = "2%";
          invites_box.style.marginTop = "0%";
        }
      }

      /**
        * Removes the invite with the given id
        */
      function removeInvite(inviteId) {
        var element = document.getElementById(`invite-${inviteId}`);
        if (element != null) {
          element.remove();
          number_invites--;

          if (inviteId == invitation_id) {
            resetInviteBoxes();
            invitation_id = -1;
          }
          updateNoInvitesMessage();
        }
      }

      /**
        * Handles the acceptance of the provided invitation
        */
      function handleInviteAccept(invitation_id, destination, sender) {
        var ajaxRequest = getAJAX();

        if (ajaxRequest != null) {
          ajaxRequest.onreadystatechange = function() {
            if (ajaxRequest.readyState == 4) {
              var response = ajaxRequest.response;
              try {
                var responseBody = JSON.parse(response);
                var message = responseBody.message;
                var success = responseBody.message;

                if (success) {
                  if (message == "ACCEPTED") {
                    update_progress("You have now joined the organisation", true);
                    removeInvite(invitation_id);
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
          data['action'] = "organisation_invite";
          data['sender'] = sender;
          data['destination'] = destination;
          data['action_param'] = "accept";
          data['invitation_id'] = invitation_id;

          update_progress("Accepting invite", true);
          ajaxRequest.open("POST", url, true);
          var json = JSON.stringify(data);
          ajaxRequest.send(json);
        }
      }

      /**
        * Handles the rejection of the invite
        */
      function handleInviteRejection(invitation_id) {
        var ajaxRequest = getAJAX();

        if (ajaxRequest != null) {
          ajaxRequest.onreadystatechange = function() {
            if (ajaxRequest.readyState == 4) {
              var response = ajaxRequest.response;
              try {
                var responseBody = JSON.parse(response);
                var message = responseBody.message;
                var success = responseBody.message;

                if (success) {
                  if (message == "REMOVED") {
                    removeInvite(invitation_id);
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
          data['action'] = "organisation_invite";
          data['sender'] = username;
          data['destination'] = "n/a";
          data['action_param'] = "remove";
          data['invitation_id'] = invitation_id;

          update_progress("Rejecting invite", true);
          ajaxRequest.open("POST", url, true);
          var json = JSON.stringify(data);
          ajaxRequest.send(json);
        }
      }
    </script>
  </body>
</html>
