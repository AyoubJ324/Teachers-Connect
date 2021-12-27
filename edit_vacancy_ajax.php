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

    define('ID','id');
    //define('EDIT_FORM','edit_form');

    $id = "";
    $edit_type = "";
    $edit_form = "";
    $admin_override = false;

    /**
      * Throw error
      */
    function throwError() {
      die("Script called with missing variables");
    }

    /**
      * Parses any post parameters
      */
    function edit_vacancy_parsePOST() {
      global $id;
      global $edit_form;

      if (isset($_POST[ID])) {
        $id = $_POST[ID];
      } else {
        throwError();
      }

    if (isset($_POST[EDIT_FORM])) {
         $edit_form = $_POST[EDIT_FORM];
       } else {
         throwError();
       }

    }


    function processVacancyUpdate() {
      global $id;
      global $conn;

      $job_title = (isset($_POST['job_title'])) ? $_POST['job_title']:null;

      if ($job_title == null) {
        respond(false, "Job title is a mandatory field");
      }

      $description = (isset($_POST['description'])) ? $_POST['description']:null;
      $type = (isset($_POST['type'])) ? $_POST['type']:null;


      $sql = "UPDATE vacancies SET job_title = ?, description = ?, type = ?
      WHERE vacancy_id = ?;";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("sssi", $param_job_title, $param_description, $param_type, $param_id);
        $param_job_title = $job_title;
        $param_description = $description;
        $param_type = $type;
        $param_id = $id;


        if (!$stmt->execute()) {
          die("Database error: {$stmt->error}");
        }

        processAddSkills();

        respond(true, "UPDATED");

      } else {
        die("Database error: {$conn->error}");
      }
    }

    function processAddSkills() {
      global $conn;
      global $id;

      $response_data = array(); // this will hold the new ids and names of any created skills that did not already exist
      $skills_value = (isset($_POST['skills'])) ? $_POST['skills']:null;

      if ($skills_value == null) {
        return;
      }

      $sql = "INSERT INTO vacancy_skills (vacancy_id, skill_id) VALUES (?, ?);";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $param_vacancy_id, $param_id);
        $param_vacancy_id = $id;
        $skills = explode(',', $skills_value);

        foreach ($skills as $key => $value) {
          $param_id = getSkill(trim($value), $response_data);

          if (!skillAlreadyExists($param_id)) {
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

    function getSkill($skill_name, &$data) {
      global $conn;

      $sql = "SELECT * FROM skills WHERE name = ?;";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $param_name);
        $param_name = $skill_name;

        if ($stmt->execute()) {
          $result = $stmt->get_result();

          $id = 0;
          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              $id = $row['skill_id'];
            }
          } else {
            $id = createSkill($skill_name);
          }

          $stmt->close();
          $data[$id] = $skill_name;
          return $id;
        } else {
          die("Database error: {$stmt->error}");
        }
      } else {
        die("Database error: {$conn->error}");
      }
    }

    function createSkill($skill_name) {
      global $conn;

      $sql = "INSERT INTO skills (name) VALUES (?);";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("s", $param_name);
        $param_name = $skill_name;

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

    function skillAlreadyExists($skill_id) {
      global $conn;
      global $id;

      $sql = "SELECT * FROM vacancy_skills WHERE vacancy_id = ? AND skill_id = ?;";

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $param_vacancy_id, $param_id);
        $param_vacancy_id = $id;
        $param_id = $skill_id;

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
      global $id;

      $skill_ids = (isset($_POST['skills_choice'])) ? $_POST['skills_choice']:null;

      if ($skill_ids == null) {
        respond(false, "Remove Skill is a mandatory field");
      }

      $sql = "DELETE FROM vacancy_skills WHERE vacancy_id = ? AND skill_id = ?;";
      $data = array();

      if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $param_vacancy_id, $param_id);
        $param_vacancy_id = $id;

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


    function processDeleteVacancy() {
      global $id;
      global $conn;


      $password = $_POST['delete_password'];

      if ($password != 'CONFIRM') {
        respond(false, "Incorrect confirmation");
      }


        $sql = "DELETE FROM vacancies WHERE vacancy_id = ?";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("i", $param_vacancy_id);
          $param_vacancy_id = $id;

          if (!$stmt->execute()) {
            die("Database error: {$stmt->error}");
          }

          $stmt->close();
          respond(true, "DELETED");
        } else {
          die("Database error: {$conn->error}");
        }

    }



    function process() {
     global $edit_form;


        switch ($edit_form) {
          case UPDATE_VACANCY: processVacancyUpdate();
                                break;

          case REMOVE_SKILL: processSkillRemoval();
                                break;

          case DELETE_VACANCY: processDeleteVacancy();
                                break;

        }

      //processVacancyUpdate();
     //}


    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
      edit_vacancy_parsePOST();
      process();
    }
 ?>
