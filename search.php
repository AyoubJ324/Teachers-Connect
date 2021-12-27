

<!DOCTYPE html>
<html>
   <head>
      <title>Search</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
      <link type="text/css" href="css/styles.css" rel="stylesheet">
      <link type="text/css" href="css/feed.css" rel="stylesheet">
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <script type = "text/javascript">
         function active(){
             var searchBar = document.getElementById('searchBar');
             if(searchBar.value == 'Search...'){
                 searchBar.value = ''
                 searchBar.placeholder = 'Search...'
             }
         }

         function inactive(){
             var searchBar = document.getElementById('searchBar');
             if(searchBar.value == ''){
                 searchBar.value = ''
                 searchBar.placeholder = 'Search...'
             }

         }
      </script>
   </head>
   <body>
      <?php
         require "database.php";
         require "error.php";
         require "navbar.php";
         require "teacher.php";
         require "organisation.php";



         function displayTeachers() {
        global $teachers;

        foreach ($teachers as $key => $value) {
          $img_src = ($value->profile_photo() == null) ? DEFAULT_TEACHER_PROFILE_PIC:"{$value->profile_photo()}";
          $name = "{$value->firstName()} {$value->lastName()}";
          $headline = "{$value->headline()}";
          $link = "teacher_profile.php?username={$value->username()}";
          echo "<div class=\"card m-2 align-items-center person-card\" id=\"follower-{$value->username()}\">";
          echo "<img class=\"card-img-top rounded-circle\" src=\"{$img_src}\" alt=\"Profile image\">";
          echo "<div class=\"card-body\">";
          echo "<h5 class=\"card-title\">{$name}</h5>";
          echo "<p class=\"card-text\">{$headline}</p>";
          echo "</div><div class=\"text-center m-auto mb-2\">";
          echo "<a href=\"{$link}\" class=\"btn btn-primary\">View Profile</a>";
          echo "</div></div>";
        }
      }

      function displayOrganisations() {
        global $organisations;

        foreach ($organisations as $key => $value) {
          $img_src = ($value->profile_photo() == null) ? DEFAULT_ORG_PROFILE_PIC:"{$value->profile_photo()}";
          $name = "{$value->name()}";
          $about = "{$value->about()}";
          $headline = "{$value->headline()}";
          $location = "{$value->location()}";
          $link = "organisation_profile.php?username={$value->username()}";
          echo "<div class=\"card m-2 align-items-center person-card\" id=\"follower-{$value->username()}\">";
          echo "<img class=\"card-img-top rounded-circle\" src=\"{$img_src}\" alt=\"Profile image\">";
          echo "<div class=\"card-body\">";
          echo "<h5 class=\"card-title\">{$name}</h5>";
          echo "<p class=\"card-text\">{$headline}</p>";
          echo "<p class=\"card-text\">{$location}</p>";
          echo "</div><div class=\"text-center m-auto mb-2\">";
          echo "<a href=\"{$link}\" class=\"btn btn-primary\">View Profile</a>";
          echo "</div></div>";
        }
      }

         ?>
      <?php
         generateNavBar(SEARCH);

         if(isset($_GET['q'])){

                $q =$_GET['q'];}

          if(isset($_GET['r'])){

                $r =$_GET['r'];
               }



     echo "<div class='container main-background'>
         <form class='d-flex' action= 'search.php' method = 'GET' style ='margin-top: 20px'>
            <input class='form-control me-2'
               type='search'  name ='q' placeholder='$q'
               aria-label='Search'>
                <input type='hidden' name='r' value='$r' />
            <button class='btn btn' style='background-color:white'
               type='submit'>Search</button>
         </form>";

          ?>

         <?php

            $query = mysqli_query($conn, "select * from teachers where first_name like '$q%' or concat(first_name, \" \", last_name) like '$q%' or last_name like '$q%'");

            while($row = mysqli_fetch_array($query)){


                        $teachers[] = new Teacher($row['username'], $row['first_name'],
                            $row['last_name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);



                      }

            $rowCount = mysqli_num_rows($query);

            $query2 = mysqli_query($conn, "select * from organisations where name like '%$q%' AND username IS NOT NULL;");

            while($row = mysqli_fetch_array($query2)){


                        $organisations[] = new Organisation($row['organisation_id'], $row['username'],
                            $row['name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);






                      }

            $rowCount2 = mysqli_num_rows($query2);




        if($rowCount > 0){
            echo '<div class="row shadow profile-card">
          <div class="row">
            <div class="col-2">
              <h4 class="underlined-header">Results</h4>
            </div>';

            echo '<div class="col-4 text-center">';
            if (!empty($q)) {
              echo '<p>Teachers returned for name: <i>'.$q.'</i></p>';
            } else {
              echo '<p>Teachers</p>';
            }
            echo '</div>';

          echo '</div><div class="row">
            <div class="col pl-5">

            </div>
          </div>
          <div class="row" id="followers_view">';

                         displayTeachers();

                    echo '</div></div>';}


                            if($rowCount2 > 0){
            echo '<div class="row shadow profile-card">
          <div class="row">
            <div class="col-2">
              <h4 class="underlined-header">Results</h4>
            </div>';

            echo '<div class="col-4 text-center">';
            if (!empty($q)) {
              echo '<p>Organisations returned for name: <i>'.$q.'</i></p>';
            } else {
              echo '<p>Organisations</p>';
            }
            echo '</div>';

          echo '</div><div class="row">
            <div class="col pl-5">

            </div>
          </div>
          <div class="row" id="followers_view">';

                         displayOrganisations();

                    echo '</div>';}

            ?>
      </div>
      </script>
   </body>
</html>
