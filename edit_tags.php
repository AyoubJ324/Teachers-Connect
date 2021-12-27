<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="UTF-8" />
    	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<Title>Home</Title>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
        <link type="text/css" rel="stylesheet" href="css/feed.css">
				<link type="text/css" rel="stylesheet" href="css/styles.css">
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    </head>

	<body>

        <?php
            require "database.php";
            require "error.php";
            require "navbar.php";
         ?>

        <?php
            generateNavBar(HOME);
            $username = $_SESSION['username'];



            $skills_options = array();

            function loadSkillsOptions() {
                global $username;
                global $conn;
                global $skills_options;

        $sql = "SELECT * FROM teacher_interests NATURAL JOIN tags WHERE username = ?;";

        if ($stmt = $conn->prepare($sql)) {
          $stmt->bind_param("s", $param_username);
          $param_username = $username;





          if ($stmt->execute()) {
            $results = $stmt->get_result();

            if ($results->num_rows > 0) {
              while ($row = $results->fetch_assoc()) {
                $value = $row['tag_id'];
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

      function getSkillsOptions() {
        global $skills_options;

        foreach ($skills_options as $key => $value) {
          echo $value;
        }
      }

      loadSkillsOptions();



        ?>

        <div class="container">
            <div class="row shadow card padding-1pcent mt-5">
                <div class="col padding-1pcent" id="add_tags">
                    <h4>Add Tags</h4>
                    <form id="add_tags_form">
                        <div class="form-group">
                            <label>Tags</label>
                            <input type="text" name="tags" id="tags" class="form-control" required>
                            <div class="form-text">
                                Enter tags to add in a comma-separated (,) list
                            </div>
                        </div>
                        <div class="row text-end mt-2">
                            <div class="col">
                                <button type="button" onClick="handleNewTags();" id="add_skills_button" class="btn btn-primary">Add</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="col padding-1pcent" id="remove_skill">
                    <h4>Remove Tags</h4>
                    <form id="remove_skill_form">
                        <div class="form-group">
                            <label>Choose tags to remove</label>
                            <select class="form-select" multiple id="skills_choice" onchange="onSkillChosen();" name="skills_choice">

                            <?php getSkillsOptions(); ?>

                            </select>
                            <div class="form-text">
                                Select tags from the list to remove
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
        </div>


    <script type="text/javascript" src="forms.js"></script>
    <script type="text/javascript" src="ajax.js"></script>
    <script>
    const username = <?php echo json_encode($username); ?>;
    function handleNewTags() {
        // var valid = validateForm('add_tags_form');
        // if (valid) {
          var data = serializeForm('add_tags', 'input');
          data['username'] = username;
          data['edit_form'] = "add_tags";

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
                    addAlertMessage(true, "Tags have been added successfully", "add_tags");
                  } else {
                    addAlertMessage(false, "An error occurred updating your tags: " + message, "add_tags");
                  }
                } catch (e) {
                  alert(response);
                }
              }
            }
          }

          ajax.open("POST", "add_tags_ajax.php", true);
          ajax.send(JSON.stringify(data));


      }


      function handleDeleteSkill() {
        var valid = validateForm('remove_skill_form');
        if (valid) {
          var data = serializeForm('remove_skill', 'select');
          data['username'] = username;
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

                    addAlertMessage(true, "Tags removed successfully", "remove_skill");
                  } else {
                    addAlertMessage(false, "An error occurred removing tags: " + message, "remove_skill");
                  }
                } catch (e) {
                  alert(response);
                }
              }
            }
          }

          ajax.open("POST", "add_tags_ajax.php", true);
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
