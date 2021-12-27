<?php
  require "constants.php";

  session_start();

  unset($_SESSION[LOGGED_IN]);
  if (isset($_COOKIE[USERNAME])) {
    setcookie(USERNAME, $_COOKIE[USERNAME], time() - 3600);
  }

  if (isset($_COOKIE[USER_TYPE])) {
    setcookie(USER_TYPE, $_COOKIE[USER_TYPE], time() - 3600);
  }
  header("Location: login.php");
?>
