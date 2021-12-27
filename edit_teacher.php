<!DOCTYPE html>
<html>
  <head>
    <title>Edit Teacher Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
    <link type="text/css" href="css/styles.css" rel="stylesheet">
    <link type="text/css" href="css/feed.css" rel="stylesheet">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body>
    <?php
      require "navbar.php";
      require "teacher_variables.php";
      require "profile_picture_upload.php";

      $current_organisation = null;
      $available_organisations = array();
      $available_degrees = array();
      $user_qualifications = array();
      $skills_options = array();
      $employment_histories = array();

      $scroll_to_id = null;

      /**
       * Parses the URL for any GET parameters
       */
      function parseURL() {
        global $username;
        global $scroll_to_id;

        if ($_SERVER["REQUEST_METHOD"] == "GET") {
          $parsed_url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY);
          $params = array();
          parse_str($parsed_url, $params);

          if (isset($params['username'])) {
            $username = $params['username'];
          }

          if (isset($params['scroll_to_id'])) {
            $scroll_to_id = $params['scroll_to_id'];
          }
        }
      }

      /**
        * Load all organisations into the $available_organisations array
        */
      function loadOrganisations() {
        global $available_organisations;
        global $conn;

        $sql = "SELECT organisation_id, name FROM organisations;";

        if ($stmt = $conn->prepare($sql)) {
          if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                $available_organisations[$row['organisation_id']] = $row['name'];
              }
            }
          } else {
            doSQLError($stmt);
          }

          $stmt->close();
        } else {
          doSQLError($conn->error);
        }
      }

      /**
        * Gets list of organisations for the user to join
        */
      function getOrganisationOptions($join_organisation) {
        global $available_organisations;
        global $current_organisation;

        if ($join_organisation) {
          $curr_org_id = ($current_organisation != null) ? $current_organisation->organisation_id():-1;
          $selected = ($current_organisation == null) ? "selected":"";
          echo "<option {$selected}>Choose an organisation</option>";
          echo "<option value=\"-1\">No Organisation</option>";
          foreach ($available_organisations as $key => $value) {
            $current_org = $key == $curr_org_id;
            $selected = ($current_org) ? "selected":"";
            $current_msg = ($current_org) ? " (Current)":"";
            $id = ($current_org) ? "id=\"organisation-{$key}-current\"":"id=\"organisation-{$key}\"";
            echo "<option value=\"{$key}\" {$selected} {$id}>{$value}{$current_msg}</option>";
          }
        } else {
          echo "<option selected>Choose an organisation</option>";
          echo "<option>Enter organisation details</option>";
          foreach ($available_organisations as $key => $value) {
            echo "<option value=\"{$key}\">{$value}</option>";
          }
        }
      }

      /**
        * Load academic degrees
        */
      function loadDegrees() {
        global $available_degrees;
        global $conn;

        $sql = "SELECT * FROM academic_degrees;";

        if ($stmt = $conn->prepare($sql)) {
          if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                $degree_id = $row['degree_id'];
                $available_degrees[$degree_id] = new AcademicDegree($degree_id,
                $row['title'], $row['type'], $row['school'], $row['description'], $row['level']);
              }
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
        * Get the options for a degree options select
        */
      function getDegreeOptions() {
        global $available_degrees;

        echo "<option selected>Choose a degree</option>";
        echo "<option>New degree</option>";
        foreach ($available_degrees as $key => $value) {
          $title = $value->title();
          $school = $value->school();
          $text = "{$title} - {$school}";
          echo "<option value=\"{$key}\">{$text}</option>";
        }
      }

      /**
        * Loads the user's qualifictions
        */
      function loadUserQualifications() {
        global $available_degrees;
        global $user_qualifications;
        global $teacher;
        global $conn;

        $sql = "SELECT * FROM qualifications WHERE username = ?;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("s", $param_username);
          $param_username = $teacher->username();

          if ($stmt->execute()) {
            $results = $stmt->get_result();

            if ($results->num_rows > 0) {
              while ($row = $results->fetch_assoc()) {
                $timestamp = formatDate($row['date_obtained']);
                $user_qualifications[] = new Qualification($teacher, $available_degrees[$row['degree_id']], $timestamp);
              }
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
        * Retrieve the options for the user's qualifications
        */
      function getQualificationsOptions() {
        global $user_qualifications;

        echo "<option>Choose a qualification</option>";
        foreach ($user_qualifications as $key => $value) {
          $degree = $value->degree();
          $text = "{$degree->title()} - {$value->date_obtained()} - {$degree->school()}";
          echo "<option value=\"{$degree->degree_id()}-{$value->teacher()->username()}\">{$text}</option>";
        }
      }

      /**
        * Loads teacher's skills
        */
      function loadSkillsOptions() {
        global $username;
        global $conn;
        global $skills_options;

        $sql = "SELECT * FROM teacher_skills NATURAL JOIN skills WHERE username = ?;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("s", $param_user);
          $param_user = $username;

          if ($stmt->execute()) {
            $results = $stmt->get_result();

            if ($results->num_rows > 0) {
              while ($row = $results->fetch_assoc()) {
                $value = $row['skill_id'];
                $text = $row['name'];
                $skills_options[] = "<option value=\"{$value}\">{$text}</option>";
              }
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
        * Get the skills options for the teacher to choose from
        */
      function getSkillsOptions() {
        global $skills_options;

        foreach ($skills_options as $key => $value) {
          echo $value;
        }
      }

      /**
        * Loads employment history options
        */
      function loadEmploymentHistory() {
        global $teacher;
        global $conn;
        global $employment_histories;

        $sql = "SELECT * FROM employment_history JOIN organisations ON employment_history.organisation_id = organisations.organisation_id  WHERE employment_history.username = ?;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("s", $param_username);
          $param_username = $teacher->username();

          if ($stmt->execute()) {
            $results = $stmt->get_result();

            if ($results->num_rows > 0) {
              while ($row = $results->fetch_assoc()) {
                $organisation = new Organisation($row['organisation_id'], $row['username'], $row['name'],
                $row['headline'], $row['about'], $row['location'], $row['profile_photo']);

                $history_id = $row['history_id'];
                $dateFrom = formatDate($row['dateFrom']);
                $dateTo = $row['dateTo'];
                $dateTo = ($dateTo == null) ? 'Present':formatDate($dateTo);
                $job_title = $row['job_title'];

                $employment_histories[$history_id] = new EmploymentHistory($history_id, $teacher, $organisation, $dateFrom, $dateTo, $job_title);
              }
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
        * Retrieve the employment history options to remove
        */
      function getEmploymentHistoryOptions() {
        global $employment_histories;

        echo "<option selected>Choose an employment history</option>";
        foreach ($employment_histories as $key => $value) {
          $id = $value->history_id();
          $text = "{$value->job_title()} - {$value->organisation()->name()} - ({$value->dateFrom()} - {$value->dateTo()})";
          echo "<option value=\"{$id}\">{$text}</option>";
        }
      }

      parseURL();

      $loggedin_username = $_SESSION[USERNAME];
      $user_type = $_SESSION[USER_TYPE];

      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST["profile_type"])) {
          uploadProfilePicture($_POST["profile_type"], $username);
        }
      }

      if ($user_type != ADMIN && $loggedin_username != $username) {
        doError("You can only edit a profile that is your own");
      } else {
        loadTeacher($username);
        if (empty($error_message)) {
          $first_name = $teacher->firstName();
          $last_name = $teacher->lastName();
          $headline = $teacher->headline();
          $about = $teacher->about();
          $location = $teacher->location();

          loadOrganisations();
          if (empty($error_message)) {
            loadDegrees();
            if (empty($error_message)) {
              loadUserQualifications();
              if (empty($error_message)) {
                loadSkillsOptions();
                if (empty($error_message)) {
                  loadEmploymentHistory();
                }
              }
            }
          }
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
            <div class="row">
              <div class="col">
                <div class="form-group">
                  <label>First Name</label>
                  <input type="text" pattern="[A-Za-z\-]*" name="first_name" id="first_name" title="Please enter alphabetical characters only" class="form-control" placeholder="John" value="<?php echo $first_name; ?>" required>
                </div>
              </div>
              <div class="col">
                <div class="form-group">
                  <label>Last Name</label>
                  <input type="text" pattern="[A-Za-z\-]*" name="last_name" id="last_name" title="Please enter alphabetical characters only" class="form-control" placeholder="Doe" value="<?php echo $last_name; ?>" required>
                </div>
              </div>
            </div>
            <div class="row">
              <div class="col-9">
                <div class="form-group">
                  <label>Headline</label>
                  <input type="text" name="headline" id="headline" class="form-control" maxlength="255" placeholder="Headline" value="<?php echo $headline; ?>">
                  <div class="form-text">
                    Enter a short summary (up to 255 characters) outlining your current position, job you're seeking etc.
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
              <textarea name="about" id="about" class="form-control" rows="5"><?php echo $teacher->about(); ?></textarea>
              <div class="form-text">
                Enter a more detailed piece of information about yourself here
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
          getProfilePictureForm(true);
        ?>
        <div class="row mt-5 shadow card padding-1pcent" id="update_password">
          <h4>Update Password</h4>
          <form id="update_password_form">
            <div class="row">
              <div class="col">
                <div class="form-group">
                  <label>Password</label>
                  <input type="password" name="password" id="password" oninput="onPasswordInput();" minlength="8" class="form-control" required>
                  <div class="form-text">
                    Enter your new password
                  </div>
                </div>
              </div>
              <div class="col">
                <div class="form-group">
                  <label>Confirm Password</label>
                  <input type="password" name="confirm_password" id="confirm_password" oninput="onConfirmPasswordInput();" minlength="8" class="form-control" required>
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
        <div class="row mt-5 shadow card padding-1pcent" id="join_organisation">
          <h4>Join Organisation</h4>
          <form id="join_organisation_form">
            <div class="form-group">
              <select class="form-select" id="organisation_choice" onchange="onOrganisationChosen();" name="current_organisation">
                <?php getOrganisationOptions(true); ?>
              </select>
              <div class="form-text">
                Choose an existing organisation to join. Choose "No Organisation" to remove your current one if any
              </div>
            </div>
            <div class="row text-end">
              <div class="col">
                <button type="button" onclick="handleJoinOrganisation();" id="join_button" class="btn btn-primary">Join Organisation</button>
              </div>
            </div>
          </form>
        </div>
        <div class="row mt-5 shadow card padding-1pcent" id="edit_education_history">
          <div class="col padding-1pcent" id="education_history">
            <h4>Add Education History</h4>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" id="new_education_form">
              <div class="row">
                <div class="col">
                  <div class="form-group">
                    <label>Degree</label>
                    <select class="form-select" id="degree_choice" onchange="onDegreeChosen();" name="education_choice">
                      <?php getDegreeOptions(); ?>
                    </select>
                    <div class="form-text">
                      Select the degree obtained. If you can't find it, choose New degree
                    </div>
                  </div>
                </div>
                <div class="col">
                  <div class="form-group">
                    <label>Date Obtained</label>
                    <input type="date" class="form-control" name="date_obtained" id="date_obtained" required>
                    <div class="form-text">
                      Enter the date at which you got your degree
                    </div>
                  </div>
                </div>
              </div>
              <div id="new_degree">
                <div class="row">
                  <div class="col">
                    <div class="form-group">
                      <label>Title</label>
                      <input type="text" class="form-control" name="degree_title" id="degree_title" required>
                      <div class="form-text">
                        Enter the title of the degree
                      </div>
                    </div>
                  </div>
                  <div class="col">
                    <div class="form-group">
                      <label>Type</label>
                      <input type="text" class="form-control" name="degree_type" id="degree_type" required>
                      <div class="form-text">
                        Enter the type of your degree (e.g. Bsc or Bachelors of Science etc.)
                      </div>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col">
                    <div class="form-group">
                      <label>School</label>
                      <input type="text" class="form-control" name="school" id="school" required>
                      <div class="form-text">
                        Enter the school you got your degree from
                      </div>
                    </div>
                  </div>
                  <div class="col">
                    <div class="form-group">
                      <label>Level</label>
                      <input type="text" class="form-control" name="level" id="level" required>
                      <div class="form-text">
                        Enter the level of this degree (e.g. High School, University etc.)
                      </div>
                    </div>
                  </div>
                </div>
                <div class="form-group">
                  <label>Description</label>
                  <textarea name="description" id="description" maxlength="255" rows="3" class="form-control"></textarea>
                  <div class="form-text">
                    Enter a description of the degree (topics covered etc.) in max 255 characters
                  </div>
                </div>
              </div>
              <div class="row text-end">
                <div class="col">
                  <button type="button" onclick="handleNewEducationHistory();" class="btn btn-primary" id="degree_button">Save</button>
                </div>
              </div>
            </form>
          </div>
          <div class="col padding-1pcent" id="delete_education">
            <h4>Remove Education History</h4>
            <form id="delete_education_form">
              <select class="form-select" id="remove_qualification_choice" onchange="onQualificationChosen();" name="chosen_qualification">
                <?php getQualificationsOptions(); ?>
              </select>
              <div class="row text-end mt-2">
                <div class="col">
                  <button type="button" onclick="handleRemoveQualification();" id="remove_button" class="btn btn-primary">Remove</button>
                </div>
              </div>
            </form>
          </div>
        </div>
        <div class="row shadow card padding-1pcent mt-5" id="edit_employment_history">
          <div class="col padding-1pcent" id="add_employment_history">
            <h4>Add Employment History</h4>
            <form id="add_employment_history_form">
              <div class="form-group">
                <label>Organisation</label>
                <select class="form-select" id="organisation_emp_choice" name="organisation_emp_choice" onchange="onOrganisationEmployerChosen();">
                  <?php getOrganisationOptions(false); ?>
                </select>
                <div class="form-text">
                  Choose the organisation that gave you your employment. If you can't find it, choose Enter organisation details
                </div>
              </div>
              <div class="row">
                <div class="col">
                  <div class="form-group">
                    <label>Job Title</label>
                    <input type="text" id="job_title" name="job_title" maxlength="32" class="form-control" required>
                    <div class="form-text">
                      Enter the job title (maximum 32 characters)
                    </div>
                  </div>
                </div>
                <div class="col">
                  <div class="form-group">
                    <label>Started</label>
                    <input type="date" id="start_date" name="start_date" class="form-control" required>
                    <div class="form-text">
                      Enter the date the employment started
                    </div>
                  </div>
                </div>
                <div class="col">
                  <div class="form-group">
                    <label>Ended</label>
                    <input type="date" id="end_date" name="end_date" class="form-control">
                    <div class="form-text">
                      Enter the date the employment ended. Leave empty to set as present
                    </div>
                  </div>
                </div>
              </div>
              <div id="new_organisation">
                <div class="row">
                  <div class="col">
                    <div class="form-group">
                      <label>Organisation Name</label>
                      <input type="text" id="organisation_name" name="organisation_name" maxlength="32" class="form-control" required>
                      <div class="form-text">
                        Enter the name of the organisation (max. 32 characters)
                      </div>
                    </div>
                  </div>
                  <div class="col">
                    <div class="form-group">
                      <label>Location</label>
                      <input type="text" id="organisation_location" name="organisation_location" maxlength="32" class="form-control" required>
                      <div class="form-text">
                        Enter the location of the organisation (max. 32 characters)
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row text-end">
                <div class="col">
                  <button type="button" onclick="handleNewEmploymentHistory();" class="btn btn-primary" id="employment_button">Save</button>
                </div>
              </div>
            </form>
          </div>
          <div class="col padding-1pcent" id="remove_employment_history">
            <h4>Remove Employment History</h4>
            <form id="remove_employment_history_form">
              <select class="form-select" id="chosen_employment_history" onchange="onEmploymentHistoryChosen();" name="chosen_employment_history">
                <?php getEmploymentHistoryOptions(); ?>
              </select>
              <div class="row text-end mt-2">
                <div class="col">
                  <button type="button" onclick="handleRemoveEmploymentHistory();" id="remove_emp_button" class="btn btn-primary">Remove</button>
                </div>
              </div>
            </form>
          </div>
        </div>
        <div class="row shadow card padding-1pcent mt-5">
          <div class="col padding-1pcent" id="add_skills">
            <h4>Add Skills</h4>
            <form id="add_skills_form">
              <div class="form-group">
                <label>Skills</label>
                <input type="text" name="skills" id="skills" oninput="onSkillsInput();" class="form-control" required>
                <div class="form-text">
                  Enter skills to add in a comma-separated (,) list
                </div>
              </div>
              <div class="row text-end mt-2">
                <div class="col">
                  <button type="button" onClick="handleNewSkills();" id="add_skills_button" class="btn btn-primary">Add</button>
                </div>
              </div>
            </form>
          </div>
          <div class="col padding-1pcent" id="remove_skill">
            <h4>Remove Skills</h4>
            <form id="remove_skill_form">
              <div class="form-group">
                <label>Choose skills to remove</label>
                <select class="form-select" multiple id="skills_choice" onchange="onSkillChosen();" name="skills_choice">
                  <?php getSkillsOptions(); ?>
                </select>
                <div class="form-text">
                  Select the skill to remove from the list
                </div>
              </div>
              <div class="row text-end mt-2">
                <div class="col">
                  <button type="button" onClick="handleDeleteSkill();" id="remove_skill_button" class="btn btn-primary">Remove</button>
                </div>
              </div>
            </form>
          </div>
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
      const org_choose_message = "Choose an organisation";
      const leave_org_message = "-1";
      const degree_choose_message = "Choose a degree";
      const new_degree_message = "New degree";
      const remove_degree_message = "Choose a qualification";
      const new_org_message = "Enter organisation details";
      const remove_emp_message = "Choose an employment history";
      const choose_skill_message = "Choose skills";

      const join_button = document.getElementById('join_button');
      const organisation_choice = document.getElementById('organisation_choice');
      join_button.disabled = organisation_choice.value == org_choose_message;

      var current_organisation = <?php echo json_encode(($current_organisation != null) ? $current_organisation->organisation_id():null); ?>;

      const degree_button = document.getElementById('degree_button');
      const degree_choice = document.getElementById('degree_choice');
      degree_button.disabled = degree_choice.value == degree_choose_message;

      var visible;

      const new_degree = document.getElementById('new_degree');
      handleAddDegreeDisplay();

      const password = document.getElementById('password');
      const confirm_password = document.getElementById('confirm_password');
      confirm_password.disabled = true;
      const password_button = document.getElementById('password_button');
      password_button.disabled = true;

      const remove_button = document.getElementById('remove_button');
      const remove_qualification_choise = document.getElementById('remove_qualification_choice');
      remove_button.disabled = remove_qualification_choice.value == remove_degree_message;

      const add_skills_button = document.getElementById('add_skills_button');
      const skills = document.getElementById('skills');
      add_skills_button.disabled = true;

      const remove_skill_button = document.getElementById('remove_skill_button');
      const skills_choice = document.getElementById('skills_choice');
      remove_skill_button.disabled = true;

      const organisation_emp_choice = document.getElementById('organisation_emp_choice');
      const employment_button = document.getElementById('employment_button');
      employment_button.disabled = organisation_emp_choice.value == org_choose_message;

      var newOrgVisible;

      const new_organisation = document.getElementById('new_organisation');
      handleNewOrganisationDisplay();

      const remove_emp_button = document.getElementById('remove_emp_button');
      const chosen_employment_history = document.getElementById('chosen_employment_history');
      remove_emp_button.disabled = true;

      var scroll_to_id = <?php echo json_encode($scroll_to_id); ?>;

      if (scroll_to_id != null) {
        var element = document.getElementById(scroll_to_id);

        if (element != null) {
          element.scrollIntoView();
        }
      }

      /**
        * Handles the update of the profile
        */
      function handleUpdateProfile() {
        var valid = validateForm('update_profile_form');
        if (valid) {
          var data = serializeForm('update_profile', 'input,textarea');
          data['username'] = username;
          data['edit_type'] = "teacher";
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
          data['edit_type'] = "teacher";
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
        * Handles the request to join an organisation
        */
      function handleJoinOrganisation() {
        var valid = validateForm('join_organisation_form');
        if (valid) {
          var data = serializeForm('join_organisation', 'select');
          data['username'] = username;
          data['edit_type'] = "teacher";
          data['edit_form'] = "join_organisation";

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
                    addAlertMessage(true, "You have successfully joined the organisation", "join_organisation");
                    if (current_organisation != null) {
                      var oldSelected = document.getElementById(`organisation-${current_organisation}-current`);

                      if (oldSelected != null) {
                        var name = oldSelected.innerHTML;
                        var current_index = name.indexOf(" (Current)");

                        if (current_index != -1) {
                          name = name.substr(0, current_index);
                          oldSelected.innerHTML = name;
                          oldSelected.id = `organisation-${current_organisation}`;
                        }
                      }
                    }

                    var data = responseBody.data;
                    var selectedId = data['selected_id'];

                    if (selectedId != -1) {
                      var joined_name = data['organisation_name'];
                      var option = document.getElementById(`organisation-${selectedId}`);
                      option.innerHTML = joined_name + " (Current)";
                      option.id = `organisation-${selectedId}-current`;
                      current_organisation = selectedId;
                    } else {
                      current_organisation = null;
                    }
                  } else {
                    addAlertMessage(false, "An error has occurred joining organisation: " + message, "join_organisation");
                  }

                  clearValidation('join_organisation_form');
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
        * Handles when an organisation is chosen
        */
      function onOrganisationChosen() {
        join_button.disabled = organisation_choice.value == org_choose_message;

        if (organisation_choice.value == leave_org_message) {
          join_button.innerHTML = "Leave Current Organisation";
          join_button.classList.remove('btn-primary');
          join_button.classList.add('btn-danger');
        } else {
          join_button.innerHTML = "Join Organisation";
          join_button.classList.remove('btn-danger');
          join_button.classList.add('btn-primary');
        }
      }

      /**
        * Handles when a degree is chosen
        */
      function onDegreeChosen() {
        degree_button.disabled = degree_choice.value == degree_choose_message;
        handleAddDegreeDisplay();
      }

      /**
        * Handle the display/hiding of the new degree form
        */
      function handleAddDegreeDisplay() {
        visible = degree_choice.value == new_degree_message ? "block":"none";
        new_degree.style.display = visible;

        var required;
        if (visible == "block") {
          new_degree.classList.remove("hidden-form");
          required = true;
        } else {
          new_degree.classList.add("hidden-form");
          required = false;
        }

        var inputs = new_degree.querySelectorAll('input,textarea');
        for (var item of inputs) {
          item.required = required;
        }
      }

      /**
        * Handles the save of new education history
        */
      function handleNewEducationHistory() {
        var valid = validateForm('new_education_form');
        if (valid) {
          var data = serializeForm('education_history', 'input,textarea,select');
          data['username'] = username;
          data['edit_type'] = "teacher";
          data['edit_form'] = "education_history";

          if (visible == "none") {
            delete data.degree_title;
            delete data.degree_type;
            delete data.school;
            delete data.level;
            delete data.description;
          }

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
                    var data = responseBody.data;
                    addToSelect('degree_choice', data['add_value'], data['add_text']);
                    addToSelect('remove_qualification_choice', data['remove_value'], data['remove_text']);
                    addAlertMessage(true, "The qualification has been added to your profile successfully", "education_history");
                  } else {
                    addAlertMessage(false, "An error occurred adding the qualification: " + message, "education_history");
                  }

                  clearValidation('new_education_form');
                  setTimeout(function() {
                    document.getElementById('new_education_form').reset();
                    onDegreeChosen();}, 2000);
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

      /**
        * Handles a qualification choosen to be removed
        */
      function onQualificationChosen() {
        remove_button.disabled = remove_qualification_choice.value == remove_degree_message;
      }

      /**
        * Handles the removal of a qualification
        */
      function handleRemoveQualification() {
        var valid = validateForm('delete_education_form');
        if (valid) {
          var data = serializeForm('delete_education', 'select');
          data['username'] = username;
          data['edit_type'] = "teacher";
          data['edit_form'] = "delete_education";

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
                    removeFromSelectByValue('remove_qualification_choice', data['remove_qualification_choice']);
                    addAlertMessage(true, "The qualification has been successfully removed from your profile", "delete_education");
                  } else {
                    addAlertMessage(false, "An error occurred removing the qualification: ", "delete_education");
                  }

                  clearValidation('delete_education_form');
                  onQualificationChosen();
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

      /**
        * Handle the display/hiding of the new organisation form
        */
      function handleNewOrganisationDisplay() {
        newOrgVisible = organisation_emp_choice.value == new_org_message ? "block":"none";
        new_organisation.style.display = newOrgVisible;

        var required;
        if (newOrgVisible == "block") {
          new_organisation.classList.remove("hidden-form");
          required = true;
        } else {
          new_organisation.classList.add("hidden-form");
          required = false;
        }

        var inputs = new_organisation.querySelectorAll('input');
        for (var item of inputs) {
          item.required = required;
        }
      }

      /**
        * Handles when an organisation is chosen
        */
      function onOrganisationEmployerChosen() {
        employment_button.disabled = organisation_emp_choice.value == org_choose_message;
        handleNewOrganisationDisplay();
      }

      /**
        * Handles when new employment history is clicked
        */
      function handleNewEmploymentHistory() {
        var valid = validateForm('add_employment_history_form');
        if (valid) {
          var data = serializeForm('add_employment_history', 'input,select');
          data['username'] = username;
          data['edit_type'] = "teacher";
          data['edit_form'] = "add_employment_history";

          if (newOrgVisible == "none") {
            delete data.organisation_name;
            delete data.organisation_location;
          }

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
                    var data = responseBody.data;

                    if (data['organisation_value'] != null && data['organisation_name'] != null) {
                      addToSelect('organisation_emp_choice', data['organisation_value'], data['organisation_name']);
                    }

                    addToSelect('chosen_employment_history', data['value'], data['text']);
                    addAlertMessage(true, "Employment History successfully added to profile", "add_employment_history");
                  } else {
                    addAlertMessage(false, "An error has occurred adding employment history to your profile: " + message, "add_employment_history");
                  }

                  clearValidation('add_employment_history_form');
                  setTimeout(function() {
                    document.getElementById('add_employment_history_form').reset();
                    onOrganisationEmployerChosen();}, 2000);
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

      /**
        * Handles when an employment history is chosen
        */
      function onEmploymentHistoryChosen() {
        remove_emp_button.disabled = chosen_employment_history.value == remove_emp_message;
      }

      /**
        * Handles the removal of employment history
        */
      function handleRemoveEmploymentHistory() {
        var valid = validateForm('remove_employment_history_form');
        if (valid) {
          var data = serializeForm('remove_employment_history', 'select');
          data['username'] = username;
          data['edit_type'] = "teacher";
          data['edit_form'] = "remove_employment_history";

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
                    removeFromSelectByValue('chosen_employment_history', data['chosen_employment_history']);
                    addAlertMessage(true, "Employment History successfully removed from your profile", "remove_employment_history");
                  } else {
                    addAlertMessage(false, "An error occurred removing employment history from your profile: " + message, "remove_employment_history");
                  }

                  clearValidation('remove_employment_history_form');
                  remove_emp_button.disabled = true;
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

      /**
        * Handles input in the skills input
        */
      function onSkillsInput() {
        add_skills_button.disabled = skills.value.length == 0;
      }

      /**
        * Handles adding new skills to the user's profile
        */
      function handleNewSkills() {
        var valid = validateForm('add_skills_form');
        if (valid) {
          var data = serializeForm('add_skills', 'input');
          data['username'] = username;
          data['edit_type'] = "teacher";
          data['edit_form'] = "add_skills";

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
                    var data = responseBody.data;

                    for (var property in data) {
                      addToSelect('skills_choice', property, data[property]);
                    }
                    addAlertMessage(true, "Skills successfully added to your profile", "add_skills");
                  } else {
                    addAlertMessage(false, "An error occurred adding skills to your profile: " + message, "add_skills");
                  }

                  clearValidation('add_skills_form');
                  setTimeout(function() {
                    document.getElementById('add_skills_form').reset();
                    add_skills_button.disabled = true;}, 2000);
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
        * Get the number of skills selected to be removed
        */
      function getNumberSkillsSelected() {
        var numberSelected = 0;
        for (var i = 0; i < skills_choice.options.length; i++) {
          if (skills_choice.options[i].selected) {
            numberSelected++;
          }
        }

        return numberSelected;
      }

      /**
        * Handles the selection of a skill to remove
        */
      function onSkillChosen() {
        remove_skill_button.disabled = getNumberSkillsSelected() == 0;
      }

      /**
        * Handles the removal of a skill from the user's profile
        */
      function handleDeleteSkill() {
        var valid = validateForm('remove_skill_form');
        if (valid) {
          var data = serializeForm('remove_skill', 'select');
          data['username'] = username;
          data['edit_type'] = "teacher";
          data['edit_form'] = "remove_skill";

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
                    var data = responseBody.data;

                    for (var property in data) {
                      removeFromSelectByValue('skills_choice', data[property]);
                    }

                    addAlertMessage(true, "Skills removed from your profile successfully", "remove_skill");
                  } else {
                    addAlertMessage(false, "An error occurred removing skills from your profile: " + message, "remove_skill");
                  }

                  clearValidation('remove_skill_form');
                  document.getElementById('remove_skill_form').reset();
                  remove_skill_button.disabled = true;
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
