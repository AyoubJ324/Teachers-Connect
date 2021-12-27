<?php
  require_once "teacher.php";
  require_once "organisation.php";
  require_once "database.php";
  require_once "error.php";
  require_once "notifications_utils.php";

  $posts = array();
  $vacancies = array();

  function alreadyLiked($post_id, $username) {
    global $conn;

    $sql = "SELECT * FROM post_likes WHERE post_id = ? AND username = ?;";

    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("is", $param_id, $param_username);
        $param_id = $post_id;
        $param_username = $username;

        if ($stmt->execute()) {
      $result = $stmt->get_result();

      $exists = $result->num_rows > 0;
      $stmt->close();

      return $exists;
        } else {
      die("Database Error: {$stmt->error}");
        }
    } else {
        die("Database Error: {$conn->error}");
    }
  }

  /**
    * Creates the post string from the row
    */
  function createPost($post_row, $name, $profile_photo) {
    global $conn;
    global $user_type;

    $content = $post_row['content'];
    $post_id = $post_row['post_id'];
    $username = $post_row['username'];
    $time_created = $post_row['created_at'];

    $time_created_original = strtotime($time_created);
    $time_created = date("H:i", $time_created_original);
    $time_created2 = date("d/m/Y", $time_created_original);

    $liked = alreadyLiked($post_id, $_SESSION['username']);
    $post = '<div class="border mb-2" id="post-card-'. $post_id . '">
      <div class="row">
        <div class="col-3">
          <img class="img-fluid rounded-circle" src="'. $profile_photo . '" alt="Profile image">
        </div>
        <div class="col-7">
          <h3 class="card-title"><b>'. $name .'</b></h3>
          <p class="card-text new-line-text">'. $content .'</p>';
    if ($liked) {
      $post .= '<button type="button" class="btn btn-danger btn-sm" id="post-'. $post_id . '" onclick="handlePostLike('. $post_id . ', \''. $username . '\');">Unlike 👍</button>';
    } else {
      $post .= '<button type="button" class="btn btn-primary btn-sm" id="post-'. $post_id . '" onclick="handlePostLike('. $post_id . ', \''. $username . '\');">Like 👍</button>';
    }

    $tag_query = mysqli_query($conn, "select tags.name from post_tags inner join tags on post_tags.tag_id = tags.tag_id where post_id = '". $post_id ."';");
    if (mysqli_num_rows($tag_query) > 0) {
      $post .= '<p style="margin-top:10px;"><b>Tags:</b> ';
      while($connection_row = mysqli_fetch_array($tag_query)){
        $post .= $connection_row['name'] . " ";
      }

     $post .= '</p>';
    }

    $post .= '</div>
    <div class="col-2">
      <p><b>'. $time_created .'</b></p>
      <p><b>'. $time_created2 .'</b></p>'
      ;

    if($_SESSION['username'] == $username or $user_type == ADMIN){
      $post .= '<button type="button" class="btn btn-outline-danger btn-sm" onclick="deletePost('.$post_id.');">Delete</button>';
    }

    $post .= '</div>
        </div>
    </div>';

    return $post;
  }

  /**
    * Load teacher's posts
    */
  function loadTeacherPosts(Teacher $teacher) {
    global $posts;
    global $conn;

    $username = $teacher->username();
    $profile_photo = $teacher->profile_photo();
    $profile_photo = ($profile_photo == null || empty($profile_photo)) ? DEFAULT_TEACHER_PROFILE_PIC:$profile_photo;

    $sql = "SELECT * FROM posts WHERE username = ? ORDER BY created_at DESC;";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("s", $param_username);
      $param_username = $username;

      if ($stmt->execute()) {
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
          $name = "{$teacher->firstName()} {$teacher->lastName()}";
          $posts[] = createPost($row, $name, $profile_photo);
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
    * Load organisations posts
    */
  function loadOrganisationPosts(Organisation $organisation) {
    global $posts;
    global $conn;

    $username = $organisation->username();
    $profile_photo = $organisation->profile_photo();
    $profile_photo = ($profile_photo == null || empty($profile_photo)) ? DEFAULT_ORG_PROFILE_PIC:$profile_photo;

    $sql = "SELECT * FROM posts WHERE username = ? ORDER BY created_at DESC;";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("s", $param_username);
      $param_username = $username;

      if ($stmt->execute()) {
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
          $name = $organisation->name();
          $posts[] = createPost($row, $name, $profile_photo);
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
    * Load the organisation's vacancies
    */
  function loadOrganisationVacancies(Organisation $organisation) {
    global $vacancies;
    global $conn;

    $profile_photo = $organisation->profile_photo();
    $profile_photo = ($profile_photo == null || empty($profile_photo)) ? DEFAULT_ORG_PROFILE_PIC:$profile_photo;

    $sql = "SELECT * FROM vacancies WHERE organisation_id = ? ORDER BY posted_at LIMIT 4";

    if ($stmt = $conn->prepare($sql)) {
      $stmt->bind_param("i", $param_organisation);
      $param_organisation = $organisation->organisation_id();

      if ($stmt->execute()) {
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
          $vacancy_id = $row['vacancy_id'];
          $job_title = $row['job_title'];

          $type = $row['type'];

          $posted_at = $row['posted_at'];

          $timestamp = strtotime($posted_at . "+ 5 hours");
          $posted_at = date("H:i", $timestamp);
          $posted_at2 = date("d/m/Y", $timestamp);

          $vacancies[] = '<div class="border mb-2 hoverable" onclick="window.location.href=\'vacancy_profile.php?id='.$vacancy_id.'\'">
                            <div class="row">
                              <div class="col-4">
                                <img class="img-fluid rounded-circle" src='.$profile_photo.' alt="Card image">
                              </div>
                              <div class="col-8">
                                <h4>' . $organisation->name() . '</h4>
                                <h5 class="card-title">'. $job_title .'</h5>
                                <h5 class="card-title">'. $type .'</h5>
                                <h5 class="card-title">Posted at '. $posted_at .' on '. $posted_at2 .'</h5>
                              </div>
                          </div>
                        </div>';
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
    * Display this user's posts
    */
  function displayPosts() {
    global $posts;

    foreach ($posts as $value) {
      echo $value;
    }
  }

  /**
    * Display the organisations vacancies
    */
  function displayVacancies(Organisation $organisation) {
    global $vacancies;

    $display_see_more = count($vacancies) == 4;

    foreach ($vacancies as $value) {
      echo $value;
    }

    if ($display_see_more) {
      $data = array('organisation' => $organisation->organisation_id());
      $query = http_build_query($data);
      $url = "vacancies.php?".$query;
      echo '<div class="row justify-content-center text-center">
        <div class="col">
           <a href="'.$url.'">See more</a>'.
        '</div>
      </div>';
    }
  }
 ?>
