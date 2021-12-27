<?php
  /**
    * Use this global variable to display an error on the page that has included this file
    */
  $error_message = "";

  /**
   * Sets the error_message variable to an error that has been caused by SQL
   * issues
   */
  function doSQLError($error) {
    global $error_message;
    $error = (isset($error)) ? "Error: {$error}" : '';
    $error_message = "Oops! Something went wrong. Please try again later. {$error}";
  }

  /**
   * Sets the error_message to the provided message
   */
  function doError($error) {
    global $error_message;
    $error_message = $error;
  }

  /**
    * Call this method in the position in the html you want it to be displayed in.
    * The error will be displayed if error_message is not empty.
    * It returns true if an error has been recorded, false if not
    */
  function displayError() {
    global $error_message;
    if (!empty($error_message)) {
      echo "<div class=\"col align-self-center alert alert-danger\"> {$error_message} </div>";
      return true;
    }

    return false;
  }
 ?>
