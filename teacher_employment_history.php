<!DOCTYPE html>
<html>
  <head>
    <title>Employment History</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
    <link type="text/css" href="css/styles.css" rel="stylesheet">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body>
    <?php
      require "teacher_variables.php";
      require "navbar.php";
      require "notifications_utils.php";

      $username = $_SESSION[USERNAME];
      $employment_histories = array();

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

      /**
        * Load all the user's qualifications
        */
      function loadEmployments() {
        global $username;
        global $teacher;
        global $conn;
        global $employment_histories;

        $sql = "SELECT * FROM employment_history JOIN organisations ON employment_history.organisation_id = organisations.organisation_id
        WHERE employment_history.username = ?;";

          if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_user);
            $param_user = $username;

            if ($stmt->execute()) {
              $result = $stmt->get_result();

              if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                  $date_to = $row['dateTo'];
                  $organisation = new Organisation($row['organisation_id'],
                    $row['username'], $row['name'], $row['headline'],
                    $row['about'], $row['location'], $row['profile_photo']);
                  $employment_histories[] = new EmploymentHistory($row['history_id'],
                    $teacher, $organisation, $row['dateFrom'], $date_to, $row['job_title']);
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
        * Retrieve the dates of the recent organisation if not null
        */
      function getEmploymentDates($employment_history) {
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
        * Print the employment history
        */
      function displayEmploymentHistory($employment_history) {
        echo "<div class=\"row m-auto align-items-center\">";
        echo "<div class=\"col-4\">";
        $photo = $employment_history->organisation()->profile_photo();
        $photo = ($photo == null) ? DEFAULT_ORG_PROFILE_PIC:$photo;
        echo "<img class=\"img-fluid rounded-circle\" src=\"{$photo}\" alt=\"org_photo\"></div>";
        echo "<div class=\"col-8\">";
        $url = "";
        $username = $employment_history->organisation()->username();
        if ($username != null) {
          echo "<a href=\"organisation_profile.php?username={$username}\">";
          echo "<h4>{$employment_history->organisation()->name()}</h4></a>";
        } else {
          $organisation = $employment_history->organisation();
          echo "<h4>{$organisation->name()}, {$organisation->location()}</h4>"; //TODO consider how location is displayed here
        }

        echo "<h5>{$employment_history->job_title()}</h5>";
        $dates = getEmploymentDates($employment_history);
        echo "<h5 class=\"subtitle\">{$dates}</h5></div></div>";
      }

      /**
        * Display the qualifications for the user
        */
      function displayEmployments() {
        global $username;
        global $employment_histories;

        $num_items = count($employment_histories);

        if ($num_items > 0) {
          $i = 0;

          foreach ($employment_histories as $employment_history) {
            if ($employment_history->dateTo() == null) {
              displayEmploymentHistory($employment_history);

              if ($num_items > 1) {
                print "<hr>";
              }

              unset($employment_histories[$i]);
              $num_items--;
              break;
            }

            $i++;
          }

          $i = 0;

          foreach ($employment_histories as $employment_history) {
            echo "<div class=\"row m-auto align-items-center\">";
            echo "<div class=\"col-4\">";
            $photo = $employment_history->organisation()->profile_photo();
            $photo = ($photo == null) ? DEFAULT_ORG_PROFILE_PIC:$photo;
            echo "<img class=\"img-fluid rounded-circle\" src=\"{$photo}\" alt=\"org_photo\"></div>";
            echo "<div class=\"col-8\">";
            $url = "";
            $username = $employment_history->organisation()->username();
            if ($username != null) {
              echo "<a href=\"organisation_profile.php?username={$username}\">";
              echo "<h4>{$employment_history->organisation()->name()}</h4></a>";
            } else {
              $organisation = $employment_history->organisation();
              echo "<h4>{$organisation->name()}, {$organisation->location()}</h4>"; //TODO consider how location is displayed here
            }

            echo "<h5>{$employment_history->job_title()}</h5>";
            $dates = getEmploymentDates($employment_history);
            echo "<h5 class=\"subtitle\">{$dates}</h5></div></div>";

            if ($i < $num_items - 1) {
              echo "<hr>";
            }

            $i++;
          }
        } else {
          $loggedin_username = $_SESSION[USERNAME];

          $message = "";
          if ($loggedin_username == $username) {
            $message = "You have no employment history. Add some to your profile by going to Edit";
          } else {
            $message = "This user has no employment history on their profile";
          }

          echo "<div class=\"row m-auto text-center\">{$message}</div>";
        }
      }

      parseURL();

      $own_profile = $_SESSION[USERNAME] == $username || $_SESSION[USER_TYPE] == ADMIN;

      loadTeacher($username);

      if (empty($error_message)) {
        loadEmployments();
      }
    ?>

    <?php
       generateNavBar(PROFILE);
       $error_occurred = displayError();

       if (!$error_occurred): // only display the profile if an error didn't occur
     ?>
     <div class="container main-background">
       <div class="row mt-2">
         <h4 class="underlined-header"><?php echo "{$teacher->firstName()} {$teacher->lastName()}'s Employment History"; ?></h4>
         <?php
            if ($own_profile) {
              $url = "edit_teacher.php?";
              $data = array('username' => $username, 'scroll_to_id' => "edit_employment_history");
              $url = $url . http_build_query($data);
              echo "<p><a href=\"{$url}\">Click here</a> to edit</p>";
            }
          ?>
       </div>
       <div class="row card">
         <?php displayEmployments(); ?>
       </div>
     </div>
    <?php endif; ?>
  </body>
</html>
