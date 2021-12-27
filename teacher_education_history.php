<!DOCTYPE html>
<html>
  <head>
    <title>Education History</title>
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
      $education_histories = array();

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
      function loadQualifications() {
        global $username;
        global $conn;
        global $education;
        global $teacher;
        global $education_histories;

        $sql = "SELECT * FROM qualifications NATURAL JOIN academic_degrees WHERE username = ?
          ORDER BY date_obtained DESC";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("s", $param_user);
          $param_user = $username;

          if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
              while ($row = $result->fetch_assoc()) {
                $academic_degree = new AcademicDegree($row['degree_id'],
                  $row['title'], $row['type'], $row['school'], $row['description'], $row['level']);
                $education_histories[] = new Qualification($teacher, $academic_degree, $row['date_obtained']);
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
        * Display the qualifications for the user
        */
      function displayQualifications() {
        global $username;
        global $education_histories;

        $num_items = count($education_histories);

        if ($num_items > 0) {
          $i = 0;
          foreach ($education_histories as $qualification) {
            echo "<div class=\"row\">";
            echo "<div class=\"col profile-card right-margin-1pcent\">";
            echo "<div class=\"row align-items-center\">";
            echo "<div class=\"col\">";
            echo "<h4 class=\"underlined-header\">{$qualification->degree()->title()}</h4>";
            echo "<h6>{$qualification->degree()->type()}</h6>";
            echo "<h6>{$qualification->degree()->school()}</h6>";
            $date = formatDate($qualification->date_obtained());
            echo "<h5 class=\"subtitle\">{$date}</h5>";
            echo "</div><div class=\"col\">";
            echo "<p>{$qualification->degree()->description()}</p></div></div>";

            if ($i < $num_items - 1) {
              echo "<hr>";
            }

            $i++;
          }
        } else {
          $loggedin_username = $_SESSION[USERNAME];

          $message = "";
          if ($loggedin_username == $username) {
            $message = "You have no qualifications. Add them from your profile by going to Edit";
          } else {
            $message = "This user has no qualifications on their profile";
          }

          echo "<div class=\"row m-auto text-center\">{$message}</div>";
        }
      }

      parseURL();

      $own_profile = $_SESSION[USERNAME] == $username || $_SESSION[USER_TYPE] == ADMIN;

      loadTeacher($username);

      if (empty($error_message)) {
        loadQualifications();
      }
    ?>

    <?php
       generateNavBar(PROFILE);
       $error_occurred = displayError();

       if (!$error_occurred): // only display the profile if an error didn't occur
     ?>
     <div class="container main-background">
       <div class="row mt-2">
         <h4 class="underlined-header"><?php echo "{$teacher->firstName()} {$teacher->lastName()}'s Education History"; ?></h4>
         <?php
            if ($own_profile) {
              $url = "edit_teacher.php?";
              $data = array('username' => $username, 'scroll_to_id' => "edit_education_history");
              $url = $url . http_build_query($data);
              echo "<p><a href=\"{$url}\">Click here</a> to edit</p>";
            }
          ?>
       </div>
       <div class="row card">
         <?php displayQualifications(); ?>
       </div>
     </div>
    <?php endif; ?>
  </body>
</html>
