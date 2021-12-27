<?php
  /**
    * This script updates a profile using ajax. It allows processing of both
    * teachers and organisation edits.
    * Pass in the USERNAME of the username being edited also.
    * Pass in the constants EDIT_TYPE which has the type of profile (TEACHER/ORGANISATION) to edit.
    * Then pass in the EDIT_FORM constant with the id of the form constant (should match the id in the html)
    * This assumes all the relevant data is passed in
    */
    require "database.php";
    require "ajax.php";
    require "constants.php";

    $username = "";
    $edit_form = "";
    $admin_override = false;


    function throwError() {
      die("Script called with missing variables");
    }




    function add_tags_parsePOST() {
      global $username;
      global $edit_form;

      if (isset($_POST['username'])) {
        $username = $_POST['username'];
      } else {
        throwError();
      }

    if (isset($_POST['edit_form'])) {
         $edit_form = $_POST['edit_form'];
       } else {
         throwError();
       }

    }




    function processTagsUpdate() {
      global $conn;
      global $username;

      $response_data = array();
      $tags_value = (isset($_POST['tags'])) ? $_POST['tags']:null;



      if ($tags_value == null) {
        return;
      }

      $sql = "INSERT INTO teacher_interests (username, tag_id) VALUES (?, ?);";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $param_username, $param_id);
        $param_username = $username;
        $tags = explode(',', $tags_value);

        foreach ($tags as $key => $value) {
          $param_id = getTag(trim($value), $response_data);

          if (!tagAlreadyExists($param_id)) {
            if (!$stmt->execute()) {
              die("Database error : {$stmt->error}");
            }
          } else {
            unset($response_data[$param_id]);
          }
        }

        $stmt->close();
        respondData(true, "UPDATED", $response_data);
      } else {
        die("Database error2: {$conn->error}");
      }
    }

    function getTag($tag_name, &$data) {
      global $conn;

      $sql = "SELECT * FROM tags WHERE name = ?;";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $param_name);
        $param_name = $tag_name;

        if ($stmt->execute()) {
          $result = $stmt->get_result();

          $id = 0;
          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              $id = $row['tag_id'];
            }
          } else {
            $id = createTag($tag_name);
          }

          $stmt->close();
          $data[$id] = $tag_name;
          return $id;
        } else {
          die("Database error: {$stmt->error}");
        }
      } else {
        die("Database error: {$conn->error}");
      }
    }

    function createTag($tag_name) {
      global $conn;

      $sql = "INSERT INTO tags (name) VALUES (?);";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $param_name);
        $param_name = $tag_name;

        if (!$stmt->execute()) {
          die("Database error: {$stmt->error}");
        }

        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
      } else {
        die("Database error: {$conn->error}");
      }
    }

    function tagAlreadyExists($tag_id) {
      global $conn;
      global $username;

      $sql = "SELECT * FROM teacher_interests WHERE username = ? AND tag_id = ?;";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("si", $param_username, $param_id);
        $param_username = $username;
        $param_id = $tag_id;

        if ($stmt->execute()) {
          $result = $stmt->get_result();
          $exists = $result->num_rows > 0;
          $stmt->close();

          return $exists;
        } else {
          die("Database error: {$stmt->error}");
        }
      } else {
        die("Database error: {$conn->error}");
      }
    }

    function processSkillRemoval() {
      global $conn;
      global $username;

      $skill_ids = (isset($_POST['skills_choice'])) ? $_POST['skills_choice']:null;

      if ($skill_ids == null) {
        respond(false, "Remove Tag is a mandatory field");
      }

      $sql = "DELETE FROM teacher_interests WHERE username = ? AND tag_id = ?;";
      $data = array();

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $param_username, $param_id);
        $param_username = $username;

        foreach ($skill_ids as $key => $value) {
          $param_id = $value;
          if (!$stmt->execute()) {
            die("Database error: {$stmt->error}");
          }

          $data[] = $value;
        }

        $stmt->close();
        respondData(true, "UPDATED", $data);
      } else {
        die("Database error: {$conn->error}");
      }
    }

    function process() {
            // if($edit_form == "add_tags"){
            // processTagsUpdate();
            // }
            // if($edit_form == "remove_skill"){

            // processSkillRemoval();
            // }

            if($_POST['edit_form'] == "add_tags"){

            processTagsUpdate();

            }

            if($_POST['edit_form'] == "remove_skill"){

            processSkillRemoval();

            }

    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      add_tags_parsePOST();



      process();
    }
 ?>
