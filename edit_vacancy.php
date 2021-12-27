<!DOCTYPE html>
<html>



    <head>
    <title>Edit Vacancy</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
    <link type="text/css" href="css/styles.css" rel="stylesheet">
    <link type="text/css" href="css/feed.css" rel="stylesheet">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
  </head>

  <body>
    <?php

  require "navbar.php";

  $skills_options = array();


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

      function loadSkillsOptions() {
        global $id;
        global $conn;
        global $skills_options;

        $sql = "SELECT * FROM vacancy_skills NATURAL JOIN skills WHERE vacancy_id = ?;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("i", $param_id);
          $param_id = $id;



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


        function loadRestOfData(){

         global $id;
         global $conn;
         global $vacancy_job_title;
         global $vacancy_description;
         global $vacancy_type;


             $sql = "SELECT * FROM vacancies where vacancy_id = ?;";

             if ($stmt = $conn->prepare($sql)) {
           $stmt->bind_param("i", $param_id);
           $param_id = $id;



          if ($stmt->execute()) {
            $results = $stmt->get_result();
                while ($row = $results->fetch_assoc()) {
                $vacancy_job_title = $row['job_title'];
                $vacancy_description = $row['description'];
                $vacancy_type = $row['type'];


               }
             }

           $stmt->close();
         }
 }












      function getSkillsOptions() {
        global $skills_options;

        foreach ($skills_options as $key => $value) {
          echo $value;
        }
      }
      generateNavBar(VACANCIES);
      parseURL();
      loadSkillsOptions();
      loadRestOfData();

      ?>

      <div class="container main-background">
        <div class="row mt-5 shadow card padding-1pcent" id="update_vacancy">
          <h4>Edit Vacancy</h4>
          <form id="update_vacancy_form">
            <div class="row">
              <div class="col-6">
                <div class="form-group">
                  <label>Job Title</label>
                  <input type="text" pattern="[A-Za-z\-  ]*" name="job_title" id="job_title" title="Please enter alphabetical characters only" class="form-control" placeholder="Fighter Pilot" value="<?php echo $vacancy_job_title; ?>"  required>
                </div>
                </div>
        <div class="col-6">
                <div class="form-group">
                  <label>Job Type</label>
                  <select class="form-select" id="type" name="type">
                  <option value="Full-time" <?php if($vacancy_type == 'Full-time') echo 'selected' ?>>Full-time</option>
                  <option value="Part-time" <?php if($vacancy_type == 'Part-time') echo 'selected' ?>>Part-time</option>
                </select>
                  <div class="form-text">
                    Tell us the hours here
                  </div>
                </div>
              </div>
                </div>

              <div class="row">
            <div class="form-group">
              <label>Description</label>
              <textarea name="description" id="description" class="form-control" rows="5" placeholder="Nyeeeeeerrrrmmmmmmmm"><?php echo $vacancy_description; ?></textarea>
              <div class="form-text">
                Enter a detailed piece of information about the position here
              </div>
            </div>
            <div class="row" id="update_vacancy_message_block">
              <div class="col-12">
                <div class="form-group">
                  <label>Skills</label>
                  <input type="text" name="skills" id="skills" class="form-control" placeholder="Shooting, Flying, Barrel Roll" >
                  <div class="form-text">
                    Enter relevant skills in a comma-separated (,) list
                  </div>
                </div>
            <div class="row text-end">
              <div class="col">
                <button type="button" onclick="handleUpdateVacancy();" class="btn btn-primary">Save</button>
              </div>
            </div>
          </form>
        </div>

        </div>
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



        <div class="row mt-5 shadow card padding-1pcent" id="delete_vacancy">
          <h4>Delete Vacancy</h4>

          <p>This form is used to delete your vacancy. To delete it, you will need to type CONFIRM in capital letters. Please note that
            this action is irreversible</p>
          <form id="delete_vacancy_form">
            <div class="form-group">
              <label>CONFIRM</label>
              <input type="text" class="form-control" name="delete_password" id="delete_password" required>
              <div class="form-text">
                Type here
              </div>
            </div>
            <div class="row text-end mt-2">
              <div class="col">
                <button type="button" onclick="handleDeleteVacancy();" id="remove_button" class="btn btn-danger">Delete</button>
              </div>
            </div>
          </form>

        </div>
      </div>







    <script type="text/javascript" src="forms.js"></script>
    <script type="text/javascript" src="ajax.js"></script>
    <script>
    const id = <?php echo json_encode($id); ?>;
    function handleUpdateVacancy() {
        var valid = validateForm('update_vacancy_form');
        if (valid) {
          var data = serializeForm('update_vacancy', 'input,textarea,select');
          data['id'] = id;
          data['edit_type'] = "organisation";
          data['edit_form'] = "update_vacancy";

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
                    addAlertMessage(true, "Vacancy has been updated successfully", "update_vacancy_message_block");
                  } else {
                    addAlertMessage(false, "An error occurred updating your vacancy: " + message, "update_vacancy_message_block");
                  }

                  clearValidation('update_vacancy_form');
                } catch (e) {
                  alert(e);
                }
              }
            }
          }

          ajax.open("POST", "edit_vacancy_ajax.php", true);
          ajax.send(JSON.stringify(data));
      }
    }

    function handleDeleteVacancy() {


        var valid = true;
        if (valid) {
          var data = serializeForm('delete_vacancy', 'input');



          data['id'] = id;
          data['edit_form'] = "delete_vacancy";


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
                    window.location.href = "vacancies.php";
                  } else {
                    addAlertMessage(false, "An error occurred deleting your vacancy: " + message, "delete_vacancy");
                  }
                } catch (e) {
                  alert(response);
                }
              }
            }

            ajax.open("POST", "edit_vacancy_ajax.php", true);
            ajax.send(JSON.stringify(data));
          }
        }

        return false;
      }




    function handleDeleteSkill() {
        var valid = validateForm('remove_skill_form');
        if (valid) {
          var data = serializeForm('remove_skill', 'select');
          data['id'] = id;
          data['edit_type'] = "organisation";
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

                    addAlertMessage(true, "Skills removed from your vacancy successfully", "remove_skill");
                  } else {
                    addAlertMessage(false, "An error occurred removing skills from your vacancy: " + message, "remove_skill");
                  }

                  clearValidation('remove_skill_form');
                } catch (e) {
                  alert(response);
                }
              }
            }
          }

          ajax.open("POST", "edit_vacancy_ajax.php", true);
          ajax.send(JSON.stringify(data));
        }

        return false;
      }

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
    </script>
  </body>
</html>
