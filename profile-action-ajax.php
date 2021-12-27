<?php
  /**
    * This file provides the "API" for handling profile actions (for both teachers and organisations).
    * It is designed to be called using an AJAX POST request with variables passed in as a JSON string produced by the
    * JavaScript function JSON.stringify(data).
    *
    * Items in uppercase without "" are constants in the constants.php file. On the right-hand side of => that is the string to pass in with javascript
    *
    * An action (ACTION => action) specifies the set of operations this is supposed to carry out.
    * You can specifiy the specific operation by passing in an action parameter (ACTION_PARAM => action_param).
    *
    * This responds with either an error message or a JSON object with the variables success (true if succeeded, false if not)
    * and a message (either the message representing the operation that succeeded or an error message);
    *
    * The following actions and action parameters are supported (with the success messages defined):
    *   CONNECT => connect - Process connections between two users
    *     ADD => add - Add a connection between these users. Returns "PENDING" if connection request is sent (with success true) or "ALREADY CONNECTED" (with success false) if users already have a connection (pending or accepted) (This is the default if no action_param provided)
    *     ACCEPT => accept - Updates an existing connection by updating the status pending to accepted. Returns "ACCEPTED" (with success true) if successful, or "NOT CONNECTED" if no connection entry exists
    *     REMOVE => remove - Removes a connection entry between these users. Returns "REMOVED" (with success true) when the connection is removed
    *   BLOCK => block - Processes blocking of users
    *     ADD => add - Add a new block for these users. Returns "BLOCKED" (with success true) when the block is added. Does not check if the blcok already exists. (This is the default if no action_param provided)
    *     REMOVE => remove - Removes an existing block if it exists. Returns "REMOVED" (with success true) when the block is removed.
    *   FOLLOW => follow - Processes following for a teacher to an organisation
    *     ADD => add - Adds a new follow entry for the teacher and organisation. Returns "FOLLOWED" (with success true) when the follow is added. Does not check if it already exists. (This is the default if no action_param provided)
    *     REMOVE => remove - Removes an existing follow if it exists. Returns "REMOVED" (with success true) when the follow is removed
    *
    * The other 2 parameters (SENDER => sender and DESTINATION => destination) determine the usernames of the 2 users that are interacting.
    * For a connection, the destination is the user the sender user wants to connect with (or remove/accept etc.).
    * For a block, the destination is the teacher to block and the sender is the teacher blocking them
    * For a follow, the destination is the id of the organisation to follow and the sender is the teacher username that wants to follow them.
    *
    * An example request is as follows:
    *    var url = "profile-action-ajax.php";
    *    var data = {};
    *    data['action'] = "connect";
    *    data['sender'] = "user-wanting-to-connect";
    *    data['destination'] = "user-to-send-connection-request-to";
    *    data['action_param'] = "add";
    *
    *    ajaxRequest.open("POST", url, true);
    *    var json = JSON.stringify(data);
    *    ajaxRequest.send(json);
    *
    * On success of that request, "PENDING" should be returned, meaning that the connection request was sent successfully
    */

  require "database.php";
  require "constants.php";
  require "ajax.php";
  require "notifications_utils.php";

  $sender = "";
  $destination = "";
  $action = "";
  $action_param = "";

  /**
    * Throw error
    */
  function throwError() {
    die("Script called with missing variables");
  }

  /**
    * Parses any post parameters
    */
  function profile_action_parsePOST() {
    global $sender;
    global $destination;
    global $action;
    global $action_param;

    if (isset($_POST[ACTION])) {
      $action = $_POST[ACTION];
    } else {
      throwError();
    }

    if (isset($_POST[ACTION_PARAM])) {
      $action_param = $_POST[ACTION_PARAM];
    }

    if (isset($_POST[SENDER])) {
      $sender = $_POST[SENDER];
    } else {
      throwError();
    }

    if (isset($_POST[DESTINATION])) {
      $destination = $_POST[DESTINATION];
    } else {
      throwError();
    }
  }

  /**
    * Checks if the users are already connected or not
    */
  function isConnected() {
    global $sender;
    global $destination;
    global $conn;

    $sql = "SELECT * FROM connections WHERE (destination = ? AND sender = ?) OR (destination = ? AND sender = ?) LIMIT 1;";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("ssss", $param_dest1, $param_send1, $param_dest2, $param_send2);
      $param_dest1 = $destination;
      $param_send1 = $sender;
      $param_dest2 = $sender;
      $param_send2 = $destination;

      $connected = false;
      if ($stmt->execute()) {
        $stmt->store_result();
        $connected = $stmt->num_rows == 1;
      } else {
        die("Database error: {$stmt->error}");
      }

      $stmt->close();
      return $connected;
    } else {
      die("Database error: {$conn->error}");
    }

    return false;
  }

  /**
    * Delete a connection
    */
  function deleteConnection() {
    global $conn;
    global $destination;
    global $sender;

    $sql = "DELETE FROM connections WHERE destination = ? AND sender = ?;";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("ss", $param_destination, $param_sender);
      $param_destination = $destination;
      $param_sender = $sender;

      if (!$stmt->execute()) {
        die("Database error: {$stmt->error}");
      }

      $stmt->close();
      respond(true, "REMOVED");
    } else {
      die("Database error: {$conn->error}");
    }
  }

  /**
    * Accept the provided connection
    */
  function acceptConnection() {
    if (isConnected()) {
      global $conn;
      global $destination;
      global $sender;

      $sql = "UPDATE connections SET status='accepted' WHERE destination = ? AND sender = ?;";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $param_destination, $param_sender);
        $param_destination = $destination;
        $param_sender = $sender;

        if (!$stmt->execute()) {
          die("Database error: {$stmt->error}");
        }

        $notification = new ConnectionAcceptedNotification($destination, $sender, false, "teacher_profile.php?username={$destination}", null);
        addNotification($notification);

        $stmt->close();
        respond(true, "ACCEPTED");
        exit;
      } else {
        die("Database error: {$conn->error}");
      }
    }

    respond(false, "NOT CONNECTED");
  }

  /**
    * Create the connection as a request
    */
  function createConnection() {
    if (!isConnected()) {
      global $conn;
      global $destination;
      global $sender;

      $sql = "INSERT INTO connections (destination, sender, status) VALUES (?, ?, 'pending');";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ss", $param_destination, $param_sender);
        $param_destination = $destination;
        $param_sender = $sender;

        if (!$stmt->execute()) {
          die("Database error: {$stmt->error}");
        }

        $stmt->close();

        $data = array('username' => $sender);
        $url = "teacher_profile.php?" . http_build_query($data);
        $notification = new ConnectionNotification($sender, $destination, false, $url, null);
        addNotification($notification);

        respond(true, "PENDING");
      } else {
        die("Database error: {$conn->error}");
      }
    }

    respond(false, "ALREADY CONNECTED");
  }

  /**
    * Processes the connection
    */
  function processConnection() {
    global $action_param;

    if ($action_param == REMOVE) {
      deleteConnection();
    } else if ($action_param == ADD || empty($action_param)) {
      createConnection();
    } else if ($action_param == ACCEPT) {
      acceptConnection();
    } else {
      die("Unsupported action_param provided: {$action_param}");
    }
  }

  /**
    * Deletes the block of this teacher
    */
  function deleteBlock() {
    global $conn;
    global $destination;
    global $sender;

    $sql = "DELETE FROM blocked_teachers WHERE blocked_teacher = ? AND blocker = ?;";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("ss", $param_destination, $param_sender);
      $param_destination = $destination;
      $param_sender = $sender;

      if (!$stmt->execute()) {
        die("Database error: {$stmt->error}");
      }

      $stmt->close();
      respond(true, "REMOVED");
    } else {
      die("Database error: {$conn->error}");
    }
  }

  /**
    * Adds the block to the databas
    */
  function addBlock() {
    global $conn;
    global $destination;
    global $sender;

    $sql = "INSERT INTO blocked_teachers (blocked_teacher, blocker) VALUES (?, ?);";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("ss", $param_destination, $param_sender);
      $param_destination = $destination;
      $param_sender = $sender;

      if (!$stmt->execute()) {
        die("Database error: {$stmt->error}");
      }

      $stmt->close();
      respond(true, "BLOCKED");
    } else {
      die("Database error: {$conn->error}");
    }
  }

  /**
    * Process blocking of a user
    */
  function processBlock() {
    global $action_param;

    if ($action_param == REMOVE) {
      deleteBlock();
    } else if ($action_param == ADD || empty($action_param)) {
      addBlock();
    } else {
      die("Unsupported action_param provided: {$action_param}");
    }
  }

  /**
    * Removes the follow
    */
  function removeFollow() {
    global $conn;
    global $destination;
    global $sender;

    $sql = "DELETE FROM followed_organisations WHERE teacher_username = ? AND organisation_id = ?;";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("si", $param_user, $param_id);
      $param_user = $sender;
      $param_id = $destination;

      if (!$stmt->execute()) {
        die("Database error: {$stmt->error}");
      }

      $stmt->close();
      respond(true, "REMOVED");
    } else {
      die("Database error: {$conn->error}");
    }
  }

  /**
    * Gets the teacher that followed the organisation
    */
  function getTeacherFollowing($teacher_username) {
    global $conn;

    $sql = "SELECT username, first_name, last_name, headline, profile_photo FROM teachers WHERE username = ?;";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("s", $param_username);
      $param_username = $teacher_username;

      $data = array();

      if ($stmt->execute()) {
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
          $data['username'] = $row['username'];
          $data['first_name'] = $row['first_name'];
          $data['last_name'] = $row['last_name'];
          $data['headline'] = $row['headline'];
          $data['profile_photo'] = $row['profile_photo'];
        }
      } else {
        die("Database error: {$stmt->error}");
      }

      $stmt->close();
      return $data;
    } else {
      die("Database error: {$conn->error}");
    }
  }

  /**
    * Get the ussername of the organisation
    */
  function getOrganisationUsername($organisation_id) {
    global $conn;

    $sql = "SELECT username FROM organisations WHERE organisation_id = ?;";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("i", $param_id);
      $param_id = $organisation_id;

      $username = null;
      if ($stmt->execute()) {
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
          $username = $row['username'];
        }
      } else {
        die("Database error: {$stmt->error}");
      }

      $stmt->close();
      return $username;
    } else {
      die("Database error: {$conn->error}");
    }
  }

  /**
    * Adds a new follow
    */
  function addFollow() {
    global $conn;
    global $destination;
    global $sender;

    $sql = "INSERT INTO followed_organisations (teacher_username, organisation_id) VALUES (?, ?);";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("si", $param_user, $param_id);
      $param_user = $sender;
      $param_id = $destination;

      if (!$stmt->execute()) {
        die("Database error: {$stmt->error}");
      }

      $org_username = getOrganisationUsername($destination);

      if ($org_username != null) {
        $notification = new FollowNotification($sender, $org_username, false, "teacher_profile.php?username={$sender}", null);
        addNotification($notification);
      }

      $stmt->close();
      $data = getTeacherFollowing($sender);
      respondData(true, "FOLLOWED", $data);
    } else {
      die("Database error: {$conn->error}");
    }
  }

  /**
    * Process the following of an organisation
    */
  function processFollow() {
    global $action_param;
    if ($action_param == REMOVE) {
      removeFollow();
    } else if ($action_param == ADD || empty($action_param)) {
      addFollow();
    } else {
      die("Unsupported action_param provided: {$action_param}");
    }
  }

  /**
    * After insert, we update the target url
    */
  function updateInviteTargetURL($invitation_id) {
    global $conn;

    $sql = "UPDATE notifications SET target_link = ? WHERE id = ?;";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("si", $param_link, $param_id);
      $data = array('invitation_id' => $invitation_id);
      $param_link = "organisation_invites.php?" . http_build_query($data);
      $param_id = $invitation_id;

      $stmt->execute();
      $stmt->close();
    }
  }

  /**
    * Process the addition of the invite to the database
    */
  function processAddOrganisationInvite() {
    global $sender;
    global $destination;
    global $conn;

    $notification = new OrgInviteNotification($sender, $destination, false, "organisation_invites.php", null);

    $sql = "INSERT INTO notifications (username, sender, type, target_link) VALUES (?, ?, ?, ?);";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("ssss", $param_username, $param_sender, $param_type, $param_target);
      $param_username = $notification->getReceiver();
      $param_sender = $notification->getSender();
      $param_type = $notification->getType();
      $param_target = $notification->getTarget_link();

      if (!$stmt->execute()) {
        die("Database error: {$stmt->error}");
      }

      $id = $stmt->insert_id;

      updateInviteTargetURL($id);

      $stmt->close();
    } else {
      die("Database error: {$conn->error}");
    }
    respond(true, "INVITED");
  }

  /**
    * Remove the invite from the database
    */
  function removeInvite() {
    global $conn;

    if (isset($_POST[INVITATION_ID])) {
      $invitation_id = $_POST[INVITATION_ID];

      $sql = "DELETE FROM notifications WHERE id = ?;";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $param_id);
        $param_id = $invitation_id;

        if (!$stmt->execute()) {
          die("Database error: {$stmt->error}");
        }
      } else {
        die("Database error: {$conn->error}");
      }

      $stmt->close();
    } else {
      die("When accepting/rejecting organisation invites, an INVITATION_ID needs to be provided");
    }
  }

  /**
    * Get the id of the organisation sender
    */
  function getOrganisationId($sender) {
    global $conn;

    $sql = "SELECT organisation_id FROM organisations WHERE username = ?;";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("s", $param_username);
      $param_username = $sender;

      $organisation_id = -1;
      if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
          $organisation_id = $row['organisation_id'];
        }
      } else {
        die("Database error: {$stmt->error}");
      }

      $stmt->close();
      return $organisation_id;
    } else {
      die("Database error: {$conn->error}");
    }
  }

  /**
    * If the user already has a current organisation, it is deleted
    */
  function deleteExistingOrganisation() {
    global $conn;
    global $destination;

    $sql = "DELETE FROM organisation_members WHERE teacher_username = ?;";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("s", $param_username);
      $param_username = $destination;

      if (!$stmt->execute()) {
        die("Database error: {$stmt->error}");
      }

      $stmt->close();
    } else {
        die("Database error: {$conn->error}");
    }
  }

  /**
    * Process the acceptance of an organisation invite
    */
  function processAcceptOrganisationInvite() {
    global $sender;
    global $destination;
    global $conn;

    $organisation_id = getOrganisationId($sender);

    if ($organisation_id != -1) {
      deleteExistingOrganisation();

      $sql = "INSERT INTO organisation_members (teacher_username, organisation_id) VALUES (?, ?);";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $param_username, $param_id);
        $param_username = $destination;
        $param_id = $organisation_id;

        if (!$stmt->execute()) {
          die("Database error: {$stmt->error}");
        }

        $stmt->close();
        removeInvite();
        respond(true, "ACCEPTED");
      } else {
        die("Database error: {$conn->error}");
      }
    } else {
      respond(false, "Organisation with username {$sender} does not exist");
    }
  }

  function processRejectOrganisationInvite() {
    removeInvite();
    respond(true, "REMOVED");
  }

  /**
    * This AJAX script has been modified to allow use with organisation invites to teachers.
    * While not strictly an action related to the profile, the invitation process starts at the teacher's
    * profile page. The result of the invitation is also displayed on the profile so, as well as the create
    * invitation functionality, the accepting and rejection of them have been added here too
    */

  /**
    * Send an organisation invite to the destination user
    */
  function processOrganisationInvite() {
    global $action_param;

    if ($action_param == ADD) {
      processAddOrganisationInvite();
    } else if ($action_param == ACCEPT) {
      processAcceptOrganisationInvite();
    } else if ($action_param == REMOVE) {
      processRejectOrganisationInvite();
    } else {
      die("Unsupported action_param provided: {$action_param}");
    }
  }

  /**
    * Process the requested action
    */
  function process() {
    global $action;

    if ($action == CONNECT) {
      processConnection();
    } else if ($action == BLOCK) {
      processBlock();
    } else if ($action == FOLLOW) {
      processFollow();
    } else if ($action == ORGANISATION_INVITE) {
      processOrganisationInvite();
    } else {
      die("Unsupported action provided: {$action}");
    }
  }

  if ($_SERVER["REQUEST_METHOD"] == "POST") {
    profile_action_parsePOST();
    process();
  }
 ?>
