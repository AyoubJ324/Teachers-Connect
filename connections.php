<!DOCTYPE html>
<html>
  <head>
    <title>Network</title>
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
      require "database.php";
      require "teacher.php";
      require "organisation.php";

      $loggedin_username = $_SESSION[USERNAME];
      $user_type = $_SESSION[USER_TYPE];

      /**
        * Retrieve the number of followers for this organisation
        */
      function loadFriendRequests() {
        global $conn;
        global $countFriendRequests;


        global $friendRequests;
        $teacher_username = $_SESSION[USERNAME];

        //$sql = "SELECT * FROM connections where destination = '$teacher_username' and status = 'pending';";

        $query = mysqli_query($conn, "SELECT * FROM connections where destination = '$teacher_username' and status = 'pending';");


                while($row = mysqli_fetch_array($query)){

                            $query2 = mysqli_query($conn, "SELECT * FROM teachers where username = '".$row['sender']."';");
                            while($row = mysqli_fetch_array($query2)){
                            $friendRequests[] = new Teacher($row['username'], $row['first_name'],
                            $row['last_name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);
                            $countFriendRequests = $countFriendRequests + 1;





                }}

      }

      function loadFriends() {
        global $conn;

        global $friends;
        global $countFriends;
        $teacher_username = $_SESSION[USERNAME];

        //$sql = "SELECT * FROM connections where (destination = '$teacher_username' or destination = '$teacher_username') and status = 'accepted';";

        $query = mysqli_query($conn, "SELECT * FROM connections where (destination = '$teacher_username' or destination = '$teacher_username') and status = 'accepted';");


                while($row = mysqli_fetch_array($query)){

                            $friend = $row['sender'];
                            if($friend == $teacher_username) $friend = $row['destination'];


                            $query2 = mysqli_query($conn, "SELECT * FROM teachers where username = '".$friend."';");
                            while($row = mysqli_fetch_array($query2)){
                            $friends[] = new Teacher($row['username'], $row['first_name'],
                            $row['last_name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);
                            $countFriends++;

                }}

      }







      function displayRequests() {
        global $friendRequests;

        foreach ($friendRequests as $key => $value) {
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
      function displayFriends() {
        global $friends;

        foreach ($friends as $key => $value) {
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

      function displayOrgFriends($orgFriends) {


        foreach ($orgFriends as $key => $value) {
          $img_src = ($value->profile_photo() == null) ? DEFAULT_ORG_PROFILE_PIC:"{$value->profile_photo()}";
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



        function loadOrgsFollowed(){

          global $conn;
          global $orgsFollowed;
          global $countOrgs;
          $teacher_username = $_SESSION[USERNAME];




        $query = mysqli_query($conn, "SELECT * FROM organisations where organisation_id in (select organisation_id from followed_organisations where teacher_username = '".$teacher_username."');");


                while($row = mysqli_fetch_array($query)){
                    //echo $row['name'];
                    $orgsFollowed[] = new Organisation($row['organisation_id'], $row['username'],
                            $row['name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);
                            $orgsFollowed++;

                            //echo sizeof($orgsFollowed);


                }
        }







        function displayOrgsFollowed($theseOrgs) {

        //$global orgsFollowed;

        foreach ($theseOrgs as $key => $value) {

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
          //echo "<p class=\"card-text\">{$about}</p>";
          echo "<p class=\"card-text\">{$location}</p>";
          echo "</div><div class=\"text-center m-auto mb-2\">";
          echo "<a href=\"{$link}\" class=\"btn btn-primary\">View Profile</a>";
          echo "</div></div>";


        }
      }








      loadFriendRequests();
      loadFriends();
      loadOrgsFollowed();












     ?>

     <?php
        generateNavBar(CONNECTIONS);
        $query4 = mysqli_query($conn, "select * from organisations");
        echo'<div class="container main-background">



         <div class="d-flex" style ="margin-top: 20px">
         <form class="d-flex" autocomplete="off" action= "connections.php" method = "GET">

         <input class="form-control me-2"
         type="search"  name ="r" placeholder="Name"
         aria-label="Search">

         <input class="form-control me-2"
         type="search"  name ="s" placeholder="Skills - eg.: english, math, science"
         aria-label="Search">

         <input class="form-control me-2" list="orgs_list"
         type="search"  name ="t" placeholder="Search Organisations"
         aria-label="Search">
         <datalist id="orgs_list">';

         while($row = mysqli_fetch_array($query4)){
          $org_name = $row['name'];
         echo '<option>'.$org_name.'<option>';
         }
         echo '</datalist>

         <button class="btn btn" style="background-color:white; margin-right: 10px"
         type="submit">Search</button>

         </form>

         </div>';

      ?>

       <?php

         if(isset($_GET['r'])){
             if($_GET['r'] == ''){
                 if($_GET['s'] == ''){
                    if($_GET['t'] != ''){

                    $orgFriends;
                    $orgId;
                    $count;

                    $t = $_GET['t'];
                    $query1 = mysqli_query($conn, "select * from organisations where name = '$t'");
                      while($row = mysqli_fetch_array($query1)){
                          $orgId = $row['organisation_id'];
                      }
                      if($orgId != ''){
                      $query = mysqli_query($conn, "select * from teachers where username in (select username from employment_history where organisation_id = $orgId)");
                      while($row = mysqli_fetch_array($query)){


                        $orgFriends[] = new Teacher($row['username'], $row['first_name'],
                            $row['last_name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);
                            $count++;
                      }

                      if($count > 0){
                    echo '<div class="row shadow profile-card">
          <div class="row">
            <div class="col-2">
              <h4 class="underlined-header">Results</h4>
            </div>
            <div class="col-4 text-center">
              <p>Results for organisation: <i>'.$t.'</i></p>
            </div>
          </div>
          <div class="row">
            <div class="col pl-5">

            </div>
          </div>
          <div class="row" id="followers_view">';

                         displayOrgFriends($orgFriends);

                    echo '</div>
        </div>





        </div>';}
        else {
                    echo '<div class="row" id="followers_view">

                         <h1>No results</hi>

                         </div>';}
        }
                    else {
                    echo '<div class="row" id="followers_view">

                         <h1>No results</hi>

                         </div>';}}}}







                 if($_GET['r'] != ''){
                 if($_GET['s'] == ''){
                    if($_GET['t'] != ''){

                    $orgFriends;
                    $orgId;
                    $count;

                    $r = $_GET['r'];

                    $t = $_GET['t'];
                    $query1 = mysqli_query($conn, "select * from organisations where name = '$t'");
                      while($row = mysqli_fetch_array($query1)){
                          $orgId = $row['organisation_id'];
                      }







                      $query = mysqli_query($conn, "select * from teachers where username in (select teachers.username from employment_history join teachers on teachers.username = employment_history.username where organisation_id =                             $orgId and first_name like '%$r%')");

                      while($row = mysqli_fetch_array($query)){


                        $orgFriends[] = new Teacher($row['username'], $row['first_name'],
                            $row['last_name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);
                            $count++;
                      }

                      if($count > 0){
                    echo '<div class="row shadow profile-card">
          <div class="row">
            <div class="col-3">
              <h4 class="underlined-header">Results</h4>
            </div>
            <div class="col text-start">
              <p>Results for name: <i>'.$r.'</i>, organisation: <i>'.$t.'</i></p>
            </div>
          </div>
          <div class="row">
            <div class="col pl-5">

            </div>
          </div>
          <div class="row" id="followers_view">';

                         displayOrgFriends($orgFriends);

                    echo '</div>
        </div>





        </div>';}
                    else {
                    echo '<div class="row" id="followers_view">

                         <h1>No results</hi>

                         </div>';}}}}



                         if($_GET['r'] != ''){
                 if($_GET['s'] != ''){
                    if($_GET['t'] != ''){

                    $orgFriends;
                    $orgId;
                    $count;

                    $r = $_GET['r'];
                    $s = $_GET['s'];
                    $t = $_GET['t'];
                    $myArray = explode(',', $s);
                    $skillstring = '';
                    foreach ($myArray as $value){
                        $skillstring = $skillstring . " name = '$value' or";
                        }


                    $skillstring = substr($skillstring, 0, -3 );

                    $skillsIdString = '';
                     $query5 = mysqli_query($conn, "select * from skills where $skillstring");
                     while($row = mysqli_fetch_array($query5)){
                          $id = $row['skill_id'];
                          $skillsIdString = $skillsIdString . " skill_id = '$id' or";
                      }

                      $skillsIdString = substr($skillsIdString, 0, -3 );




                    if($skillsIdString != ''){





                    $query1 = mysqli_query($conn, "select * from organisations where name = '$t'");
                      while($row = mysqli_fetch_array($query1)){
                          $orgId = $row['organisation_id'];
                      }








                      $query = mysqli_query($conn, "select * from teachers where username in (select teachers.username from employment_history join teachers on teachers.username = employment_history.username where organisation_id =                             $orgId and first_name like '%$r%') and username in (select username from teacher_skills where $skillsIdString)");

                      while($row = mysqli_fetch_array($query)){


                        $orgFriends[] = new Teacher($row['username'], $row['first_name'],
                            $row['last_name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);
                            $count++;
                      }

                      if($count > 0){
                    echo '<div class="row shadow profile-card">
          <div class="row">
            <div class="col-2">
              <h4 class="underlined-header">Results</h4>
            </div>
            <div class="col-4 text-center">
              <p>Results for name: <i>'.$r.'</i>, skills: <i>'.$s.'</i>, and organisation: <i>'.$t.'</i></p>
            </div>
          </div>
          <div class="row">
            <div class="col pl-5">

            </div>
          </div>
          <div class="row" id="followers_view">';

                         displayOrgFriends($orgFriends);

                    echo '</div>
        </div>





        </div>';}
                    else {
                    echo '<div class="row" id="followers_view">

                         <h1>No results</hi>

                         </div>';}}


                         else {
                    echo '<div class="row" id="followers_view">

                         <h1>No results</hi>

                         </div>';}
                        }}
                         }

                         if($_GET['r'] == ''){
                         if($_GET['s'] != ''){
                         if($_GET['t'] != ''){
                             $orgFriends;
                    $orgId;
                    $count;


                    $s = $_GET['s'];
                    $t = $_GET['t'];
                    $myArray = explode(',', $s);
                    $skillstring = '';
                    foreach ($myArray as $value){
                        $skillstring = $skillstring . " name = '$value' or";
                        }


                    $skillstring = substr($skillstring, 0, -3 );

                    $skillsIdString = '';
                     $query5 = mysqli_query($conn, "select * from skills where $skillstring");
                     while($row = mysqli_fetch_array($query5)){
                          $id = $row['skill_id'];
                          $skillsIdString = $skillsIdString . " skill_id = '$id' or";
                      }

                      $skillsIdString = substr($skillsIdString, 0, -3 );




                    if($skillsIdString != ''){





                    $query1 = mysqli_query($conn, "select * from organisations where name = '$t'");
                      while($row = mysqli_fetch_array($query1)){
                          $orgId = $row['organisation_id'];
                      }








                      $query = mysqli_query($conn, "select * from teachers where username in (select teachers.username from employment_history join teachers on teachers.username = employment_history.username where organisation_id =                             $orgId ) and username in (select username from teacher_skills where $skillsIdString)");

                      while($row = mysqli_fetch_array($query)){


                        $orgFriends[] = new Teacher($row['username'], $row['first_name'],
                            $row['last_name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);
                            $count++;
                      }

                      if($count > 0){
                    echo '<div class="row shadow profile-card">
          <div class="row">
            <div class="col-2">
              <h4 class="underlined-header">Results</h4>
            </div>
            <div class="col-4 text-center">
              <p>Results for skills: <i>'.$s.', and organisation: <i>'.$t.'</i></p>
            </div>
          </div>
          <div class="row">
            <div class="col pl-5">

            </div>
          </div>
          <div class="row" id="followers_view">';

                         displayOrgFriends($orgFriends);

                    echo '</div>
        </div>





        </div>';}
                    else {
                    echo '<div class="row" id="followers_view">

                         <h1>No results</hi>

                         </div>';}}


                         else {
                    echo '<div class="row" id="followers_view">

                         <h1>No results</hi>

                         </div>';}

}}}


                         if($_GET['r'] != ''){
                         if($_GET['s'] == ''){
                         if($_GET['t'] == ''){

                            $r = $_GET['r'];

                             $query = mysqli_query($conn, "select * from teachers where first_name like '%$r%'");

                      while($row = mysqli_fetch_array($query)){


                        $orgFriends[] = new Teacher($row['username'], $row['first_name'],
                            $row['last_name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);
                            $count++;
                      }

                      if($count > 0){
                    echo '<div class="row shadow profile-card">
          <div class="row">
            <div class="col-2">
              <h4 class="underlined-header">Results</h4>
            </div>
            <div class="col-4 text-center">
              <p>Results for name: <i>'.$r.'</i></p>
            </div>
          </div>
          <div class="row">
            <div class="col pl-5">

            </div>
          </div>
          <div class="row" id="followers_view">';

                         displayOrgFriends($orgFriends);

                    echo '</div>
        </div>





        </div>';}
                    else {
                    echo '<div class="row" id="followers_view">

                         <h1>No results</hi>

                         </div>';}







                         }}}

                         if($_GET['r'] != ''){
                         if($_GET['s'] != ''){
                         if($_GET['t'] == ''){



                             $orgFriends;
                    $orgId;
                    $count;

                    $r = $_GET['r'];
                    $s = $_GET['s'];

                    $myArray = explode(',', $s);
                    $skillstring = '';
                    foreach ($myArray as $value){
                        $skillstring = $skillstring . " name = '$value' or";
                        }


                    $skillstring = substr($skillstring, 0, -3 );

                    $skillsIdString = '';
                     $query5 = mysqli_query($conn, "select * from skills where $skillstring");
                     while($row = mysqli_fetch_array($query5)){
                          $id = $row['skill_id'];
                          $skillsIdString = $skillsIdString . " skill_id = '$id' or";
                      }

                      $skillsIdString = substr($skillsIdString, 0, -3 );




                    if($skillsIdString != ''){



                      $query = mysqli_query($conn, "select * from teachers where username in (select username from teachers where first_name like '%$r%') and username in (select username from teacher_skills where $skillsIdString)");

                      while($row = mysqli_fetch_array($query)){


                        $orgFriends[] = new Teacher($row['username'], $row['first_name'],
                            $row['last_name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);
                            $count++;
                      }

                      if($count > 0){
                    echo '<div class="row shadow profile-card">
          <div class="row">
            <div class="col-2">
              <h4 class="underlined-header">Results</h4>
            </div>
            <div class="col-4 text-center">
              <p>Results name: <i>'.$r.'</i> and skills: <i>'.$s.'</i></p>
            </div>
          </div>
          <div class="row">
            <div class="col pl-5">

            </div>
          </div>
          <div class="row" id="followers_view">';

                         displayOrgFriends($orgFriends);

                    echo '</div>
        </div>





        </div>';}
                    else {
                    echo '<div class="row" id="followers_view">

                         <h1>No results</hi>

                         </div>';}}


                         else {
                    echo '<div class="row" id="followers_view">

                         <h1>No results</hi>

                         </div>';}

                         }}}

                        if($_GET['r'] == ''){
                         if($_GET['s'] != ''){
                         if($_GET['t'] == ''){

                                $orgFriends;
                    $orgId;
                    $count;


                    $s = $_GET['s'];

                    $myArray = explode(',', $s);
                    $skillstring = '';
                    foreach ($myArray as $value){
                        $skillstring = $skillstring . " name = '$value' or";
                        }


                    $skillstring = substr($skillstring, 0, -3 );

                    $skillsIdString = '';
                     $query5 = mysqli_query($conn, "select * from skills where $skillstring");
                     while($row = mysqli_fetch_array($query5)){
                          $id = $row['skill_id'];
                          $skillsIdString = $skillsIdString . " skill_id = '$id' or";
                      }

                      $skillsIdString = substr($skillsIdString, 0, -3 );




                    if($skillsIdString != ''){



                      $query = mysqli_query($conn, "select * from teachers where username in (select username from teacher_skills where $skillsIdString)");

                      while($row = mysqli_fetch_array($query)){


                        $orgFriends[] = new Teacher($row['username'], $row['first_name'],
                            $row['last_name'], $row['headline'], $row['about'], $row['location'], $row['profile_photo']);
                            $count++;
                      }

                      if($count > 0){
                    echo '<div class="row shadow profile-card">
          <div class="row">
            <div class="col-2">
              <h4 class="underlined-header">Results</h4>
            </div>
            <div class="col-4 text-center">
              <p>Results for skills: <i>'.$s.'</i></p>
            </div>
          </div>
          <div class="row">
            <div class="col pl-5">

            </div>
          </div>
          <div class="row" id="followers_view">';

                         displayOrgFriends($orgFriends);

                    echo '</div>
        </div>





        </div>';}
                    else {
                    echo '<div class="row" id="followers_view">

                         <h1>No results</hi>

                         </div>';}}


                         else {
                    echo '<div class="row" id="followers_view">

                         <h1>No results</hi>

                         </div>';}








                         }}}


                         }


                ?>







    <?php
    if(!isset($_GET['r'])){

    echo '<div class="container main-background">
      <div class="row shadow profile-card">
          <div class="row">
            <div class="col-3">
              <h4 class="underlined-header">Connection Requests</h4>
            </div>
            <div class="col text-start">
              <p>These people want to connect with you!</p>
            </div>
          </div>
          <div class="row">
            <div class="col pl-5">

            </div>
          </div>
          <div class="row" id="followers_view">';

          if($countFriendRequests >0){displayRequests();}

          echo '</div>
        </div>

        <div class="row shadow profile-card">
          <div class="row">
            <div class="col-3">
              <h4 class="underlined-header">Connections</h4>
            </div>
            <div class="col text-start">
              <p>These are your connections!</p>
            </div>
          </div>
          <div class="row">
            <div class="col pl-5">

            </div>
          </div>
          <div class="row" id="followers_view">';

          if($countFriends >0){displayFriends();}

         echo '</div>
        </div>

        <div class="row shadow profile-card">
          <div class="row">
            <div class="col-3">
              <h4 class="underlined-header">Organisations Followed</h4>
            </div>
            <div class="col text-start">
              <p>These are the organisations you follow!</p>
            </div>
          </div>
          <div class="row">
            <div class="col pl-5">

            </div>
          </div>
          <div class="row" id="followers_view">';

          if($orgsFollowed >0){displayOrgsFollowed($orgsFollowed);}
          //echo sizeof($orgsFollowed);

         echo '</div>
        </div>





        </div>

  </body>
</html>';
    }
    ?>
