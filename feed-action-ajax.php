<?php
    require "database.php";
    require "ajax.php";
    require "constants.php";
    require "admin_profile_utils.php";
    require "notifications_utils.php";

    define('POST_CREATION', 'post_creation');
    define('POST_LIKE', 'post_like');
    define('POST_DELETE', 'post_delete');

    $username = "";
    $edit_form = "";

    /**
     * Parses the POST request variables
     */
    function parsePOST() {
        global $username;
        global $edit_form;

        if (isset($_POST[USERNAME])) {
            $username = $_POST[USERNAME];
        } else {
            die("You need to provide a username to the script");
        }

        if (isset($_POST[EDIT_FORM])) {
            $edit_form = $_POST[EDIT_FORM];
        } else {
            die("You need to specify the action for the script");
        }
    }

    function getProfilePhoto($username, $type) {
        global $conn;

        if ($type == ADMIN) {
            respond(false, "Administrators cannot create posts");
        }

        $table = ($type == TEACHER) ? "teachers":"organisations";

        $sql = "SELECT profile_photo FROM {$table} WHERE username = ?;";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;

            if ($stmt->execute()) {
                $row = $stmt->get_result()->fetch_assoc();

                $profile_photo = ($type == TEACHER) ? DEFAULT_TEACHER_PROFILE_PIC:DEFAULT_ORG_PROFILE_PIC;

                if ($row) {
                    $profile_photo = $row['profile_photo'];

                    if ($profile_photo == null) {
                    	$profile_photo = ($type == TEACHER) ? DEFAULT_TEACHER_PROFILE_PIC:DEFAULT_ORG_PROFILE_PIC;
                    }
                }

                $stmt->close();
                return $profile_photo;
            } else {
                respond(false, "Database Error: {$stmt->error}");
            }
        } else {
            respond(false, "Database Error: {$conn->error}");
        }
    }

    function processTags($post_id) {
      global $conn;

      $added_tags = null;
      if(!empty($_POST['tags'])) {
        $added_tags = array();
        $tags = $_POST['tags'];
        $myArray = explode(',', $tags);

        $sql = "SELECT * FROM tags WHERE name = ?;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("s", $param_name);

          foreach ($myArray as $value) {
            $value = trim($value);
            $param_name = $value;

            if ($stmt->execute()) {
              $result = $stmt->get_result();
              while ($row = $result->fetch_assoc()) {
                $tag_id = $row['tag_id'];
              }

              if(isset($tag_id)){
                $sql = "INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?);";

                if ($stmt1 = $conn->prepare($sql)) {
                  $stmt1->bind_param("ii", $param_post, $param_tag);
                  $param_post = $post_id;
                  $param_tag = $tag_id;

                  if (!$stmt1->execute()) {
                    respond(false, "Database Error: {$stmt1->error}");
                  }

                  $stmt1->close();

                  $added_tags[$tag_id] = $value;
                  unset($tag_id);
                }
              } else {
                $sql = "INSERT INTO tags (name) VALUES (?);";

                if ($stmt1 = $conn->prepare($sql)) {
                  $stmt1->bind_param("s", $param_name);
                  $param_name = $value;

                  if (!$stmt1->execute()) {
                    respond(false, "Database Error: {$stmt1->error}");
                  }

                  $tag_id = $stmt1->insert_id;
                  $stmt1->close();

                  $sql = "INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?);";

                  if ($stmt1 = $conn->prepare($sql)) {
                    $stmt1->bind_param("ii", $param_post, $param_tag);
                    $param_post = $post_id;
                    $param_tag = $tag_id;

                    if (!$stmt1->execute()) {
                      respond(false, "Database Error: {$stmt1->error}");
                    }

                    $added_tags[$tag_id] = $value;
                    $stmt1->close();

                    unset($tag_id);
                  } else {
                    respond(false, "Database Error: {$stmt1->error}");
                  }
                } else {
                  respond(false, "Database Error: {$conn->error}");
                }
              }
            } else {
              respond(false, "Database Error: {$stmt->error}");
            }
          }
        }
      }

      return $added_tags;
    }

    function processPostCreation() {
        global $conn;
        global $username;

        if(isset($_POST['content'])){
            $content = $_POST['content'];

            $timestamp = date('Y-m-d H:i:s');
            $sql = "INSERT INTO posts (`username`, `content`, `created_at`) VALUES (?, ?, ?);";

            if ($stmt = $conn->prepare($sql)) {
              $stmt->bind_param("sss", $param_username, $param_content, $param_created_at);
              $param_username = $username;
              $param_content = $content;
              $param_created_at = $timestamp;

              if (!$stmt->execute()) {
                respond(false, "Database Error: {$stmt->error}");
              }

              $post_id = $stmt->insert_id;
              $stmt->close();

              $timestamp = strtotime($timestamp);
              $time = date("H:i", $timestamp);
              $date = date("d/m/Y", $timestamp);

              $added_tags = processTags($post_id);

              $type = getUserType($username);
              $profile_url = ($type == TEACHER) ? "teacher_profile.php?username={$username}":"organisation_profile.php?username={$username}";

              $data = array();
              $data['post_tags'] = $added_tags;
              $data['time_created'] = $time;
              $data['date_created'] = $date;
              $data['content'] = $content;
              $data['post_id'] = $post_id;
              $data['profile_photo'] = getProfilePhoto($username, $type);
              $data['post_name'] = getSenderName($username, $type);
              $data['username'] = $username;
              $data['profile_url'] = $profile_url;

              respondData(true, "CREATED", $data);
            } else {
              respond(false, "Database Error: {$conn->error}");
            }
        } else {
            respond(false, "You need to enter post content");
        }
    }

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
                respond(false, "Database Error: {$stmt->error}");
            }
        } else {
            respond(false, "Database Error: {$conn->error}");
        }
    }

    function removeLike($post_id, $username) {
        global $conn;

        $sql = "DELETE FROM post_likes WHERE post_id = ? AND username = ?;";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("is", $param_id, $param_username);
            $param_id = $post_id;
            $param_username = $username;

            if (!$stmt->execute()) {
                respond(false, "Database Error: {$stmt->error}");
            }

            $stmt->close();
            $data = array('post_id' => $post_id);
            respondData(true, "REMOVED", $data);
        } else {
            respond(false, "Database Error: {$conn->error}");
        }
    }

    function addLike($post_id, $username, $creator_username, $like_source) {
        global $conn;

        $sql = "INSERT INTO post_likes (post_id, username) VALUES (?, ?);";

        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("is", $param_id, $param_username);
            $param_id = $post_id;
            $param_username = $username;

            if (!$stmt->execute()) {
                respond(false, "Database Error: {$stmt->error}");
            }

            $stmt->close();

            $notification = new LikeNotification($username, $creator_username, false, "{$like_source}?post_id={$post_id}", null);
            addNotification($notification);

            $data = array('post_id' => $post_id);
            respondData(true, "LIKED", $data);
        } else {
            respond(false, "Database Error: {$conn->error}");
        }
    }

    function processPostLike() {
        global $conn;
        global $username;

        if (isset($_POST['post_id'])) {
            $post_id = $_POST['post_id'];
        } else {
            respond(false, "Post ID is a mandatory field");
        }

        if (isset($_POST['creator_username'])) {
            $creator_username = $_POST['creator_username'];
        } else {
            respond(false, "creator_username is a mandatory field");
        }

        if (isset($_POST['like_source'])) {
          $like_source = $_POST['like_source'];
        } else {
          respond(false, "like_source is a mandatory field");
        }

        if (alreadyLiked($post_id, $username)) {
            removeLike($post_id, $username);
        } else {
            addLike($post_id, $username, $creator_username, $like_source);
        }
    }

    function processPostDeletion() {
      global $conn;

      if (isset($_POST['post_id'])) {
        $post_id = $_POST['post_id'];
      } else {
        respond(false, "Post ID is a mandatory field");
      }

      $sql = "DELETE FROM posts WHERE post_id = ?;";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $param_post);
        $param_post = $post_id;

        if (!$stmt->execute()) {
          respond(false, "Database Error: {$stmt->error}");
        }

        $stmt->close();
        respondData(true, "REMOVED", array('post_id' => $post_id));
      } else {
        respond(false, "Database Error: {$conn->error}");
      }
    }

    /**
     * Carries out the post action
     */
    function process() {
        global $edit_form;

        if ($edit_form == POST_CREATION) {
            processPostCreation();
        } else if ($edit_form == POST_LIKE) {
            processPostLike();
        } else if ($edit_form == POST_DELETE) {
          processPostDeletion();
        } else {
            die("Invalid EDIT_FORM provided: {$edit_form}");
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        parsePOST();
        process();
    }
?>
