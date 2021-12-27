<!DOCTYPE html>
<html>
  <head>
    <title>Vacancy Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
    <link type="text/css" href="css/styles.css" rel="stylesheet">
    <link type="text/css" href="css/feed.css" rel="stylesheet">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>
  <body>
    <?php

      require "navbar.php";
      //require "edit_vacancy.php";


      $user_type = $_SESSION[USER_TYPE];
      $username = $_SESSION[USERNAME];
      $org;
      $id;
      $org_id;
      $contact_button;

      if($user_type == 'organisation'){

          $sql = "SELECT * FROM organisations where username = ?;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("s", $param_username);
          $param_username = $username;

          if ($stmt->execute()) {
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $org = $row['organisation_id'];


              } }}}

function parseURL() {
        global $id;

        if ($_SERVER["REQUEST_METHOD"] == "GET") {
          $parsed_url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY);
          $params = array();
          parse_str($parsed_url, $params);

          if (isset($params['id'])) {
            $id = $params['id'];
          }
        }
      }


    function loadContactButton() {

        global $conn;
        global $contact_button;
        global $org_id;
        global $id;



          $sql = "SELECT email FROM accounts join organisations on accounts.username = organisations.username WHERE organisation_id = ?;";

          if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $param_org_id);
            $param_org_id = $org_id;

            if ($stmt->execute()) {
              $row = $stmt->get_result()->fetch_assoc();

              if ($row) {
                $loggedin_username = $_SESSION[USERNAME];
                $name;
                $email = $row['email'];

                $query = mysqli_query($conn, "select * from teachers where username = '".$loggedin_username."'");

                while($row = mysqli_fetch_array($query)){
                        $name = $row['first_name'] . " " . $row['last_name'];

                        $queryA = mysqli_query($conn, "select * from vacancies where vacancy_id = '".$id."'");


                            while($row = mysqli_fetch_array($queryA)){
                                    $job = $row['job_title'];
                            }


                }

                $contact_button = '<a href="mailto:'. $email . '?subject=Vacancy Application: '.$job.' - ' . $name . '" class="btn btn-primary" style="margin-right: 1vw;">Apply!</a>';
              }
            }

            $stmt->close();
          }

      }











      /**
       * Parses the URL for any GET parameters
       */



      function loadSkills() {
        global $id;
        global $conn;

        $sql = "SELECT * FROM vacancy_skills JOIN skills on vacancy_skills.skill_id = skills.skill_id WHERE vacancy_id = ?;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("i", $param_vacancy_id);
          $param_vacancy_id = $id;

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

      function getVacancyOrg(){

          global $id;
          global $org_id;
        global $conn;

        $sql = "SELECT * FROM vacancies where vacancy_id = ?;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("i", $param_vacancy_id);
          $param_vacancy_id = $id;

          if ($stmt->execute()) {
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {

              while ($row = $result->fetch_assoc()) {
                $org_id = $row['organisation_id'];
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

      $loggedin_username = $_SESSION[USERNAME];
      $user_type = $_SESSION[USER_TYPE];

     parseURL();
     getVacancyOrg();
     loadContactButton();




     ?>

     <?php
        generateNavBar(VACANCIES);



        function getEditVacancy($org_id,$organisation_id) {


        if($organisation_id == $org_id){

        $btn_class = "\"btn btn-primary\"";
        $btn_target = "onclick=\"handleEdit();\"";
        $btn = "<button class={$btn_class} style=\"margin-right: 1vw;\" id=\"connect-button\" {$btn_target}>";

          return "{$btn}Edit</button></a>";
          }
          return false;

          "<button class=\"btn btn-primary\" style=\"margin-right: 1vw;\" id=\"connect-button\" onclick=\"handleEdit();\">Edit</button></a>";






      }








          $query = mysqli_query($conn, "SELECT * FROM vacancies JOIN organisations ON vacancies.organisation_id = organisations.organisation_id WHERE vacancy_id = '$id'");
          while($row = mysqli_fetch_array($query)){
            $organisation_id = $row['organisation_id'];
            $job_title = $row['job_title'];

            $description = $row['description'];
            $type = $row['type'];

            $org_name = $row['name'];
            $profile_photo = $row['profile_photo'];
            $profile_photo = ($profile_photo == null) ? DEFAULT_ORG_PROFILE_PIC:$profile_photo;
            $org_username = $row['username'];

            $posted_at = $row['posted_at'];

            $timestamp = strtotime($posted_at);
            $posted_at = date("H:i", $timestamp);
            $posted_at2 = date("d/m/Y", $timestamp);
          }
      ?>
     <div class="container main-background">

       <div class="row shadow profile-card padding-1pcent" id="profile-header">
        <div class="row">
          <h4 class="underlined-header">Vacancy</h4>
        </div>
        <div class="col-3">
          <img class="img-fluid rounded-circle" src=<?php echo "'".$profile_photo."'";?> alt="profile-picture">
        </div>
        <div class="col-9">
          <a href="organisation_profile.php?username=<?php echo $org_username; ?>"><h3><?php echo $org_name; ?></h3></a>
          <h4 class="subtitle"><?php echo $job_title;?></h4>
          <h5><?php echo $type; ?></h5>
          <h5 class="card-title">Posted at <?php echo $posted_at ?> on the <?php echo $posted_at2 ?></h5>

        </div>
        <div class="row mt-2">
          <div class="btn-toolbar">


        <?php
        if(($user_type == ORGANISATION && $org_id == $org) || $user_type == 'admin'){

        echo "<button class=\"btn btn-primary\" style=\"margin-right: 1vw;\" id=\"connect-button\" onclick=\"handleEdit();\">Edit</button></a>";

        }
        if($user_type == 'teacher'){
        echo $contact_button;
        }


        //echo getEditVacancy();


        ?>
        </div>
        </div>
        </div>

        <div class="row shadow profile-card">
        <div class="row">
          <h4 class="underlined-header">Job Description</h4>
        </div>

        <p class="about-me-text new-line-text"><?php echo $description; ?></p>
      </div>

        <div class="row shadow profile-card">
        <div class="row">
          <h4 class="underlined-header">Skills</h4>
        </div>
        <?php loadSkills(); ?>

      </div>





      </div>

      <script>
      const id = <?php echo json_encode($id); ?>;
      function handleEdit() {

          //header('Location: '. 'edit_vacancy.php?id=' . $id);


          //window.location.href = window.location.href = 'edit_vacancy.php?id=${id}';

          window.location.href = window.location.href = `edit_vacancy.php?id=${id}`
          //window.location.href = window.location.href = 'connections.php';
          return false;

      }

      </script>
  </body>
</html>
