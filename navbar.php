<?php
  require "constants.php";
  require "database.php";
  require "admin_profile_utils.php";

  session_start();

  define("HOME", "home");
  define("CONNECTIONS", "connections");
  define("VACANCIES", "vacancies");
  define("NOTIFICATIONS", "notifications");
  define("SEARCH", "search");
  define("PROFILE", "profile");
  define("ADMINISTRATION", "administration");
  define("ACTIVE", "class=\"nav-link active\" aria-current=\"page\" ");
  define ("INACTIVE", "class=\"nav-link\" ");

   /**
    * The user name that has been set by the session check in this navigation php file
    */
   $username = "";
   /**
    * The type of user logged in
    */
   $user_type = "";

   /**
    * Goes to the login page
    */
   function goToLogin() {
     $query = parse_url($_SERVER["REQUEST_URI"], PHP_URL_QUERY);
     $script_name = htmlspecialchars($_SERVER["PHP_SELF"]);
     if ($query != null) {
       $script_name .= "?{$query}";
     }
     $data = array('goto' => $script_name);
     $url = "login.php?".http_build_query($data);

     header("Location: $url");
     exit;
   }

   if (empty($_SERVER["HTTPS"]) && $_SERVER["SERVER_NAME"] != "localhost") {
     header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"], true, 301);
     exit;
   }

   if (isset($_SESSION[LOGGED_IN]) && $_SESSION[LOGGED_IN] == true) {
     if (!isset($_SESSION[USERNAME]) || !isset($_SESSION[USER_TYPE])) {
        goToLogin();
      }

      $username = $_SESSION[USERNAME];
      $user_type = $_SESSION[USER_TYPE];
    } else {
      if (isset($_COOKIE[USERNAME]) && isset($_COOKIE[USER_TYPE])) {
        $username = $_SESSION[USERNAME] = $_COOKIE[USERNAME];
        $user_type = $_SESSION[USER_TYPE] = $_COOKIE[USER_TYPE];
        $_SESSION[LOGGED_IN] = true;
      } else {
        goToLogin();
      }
    }

    if ($user_type != ADMIN) {
      if (checkBanned($username) || checkBlacklist($username)) {
        header("Location: logout.php");
      }
    }

    /**
      * Checks if the page name provided to generateNavBar is valid
      */
    function checkPageName($page) {
      if (empty($page) || ($page != HOME && $page != CONNECTIONS && $page != VACANCIES
        && $page != NOTIFICATIONS && $page != PROFILE && $page != SEARCH && $page != ADMINISTRATION)) {
          die("Invalid page option given to navbar.php. Value: {$page}");
      }
    }

    /**
      * Generates the navigation bar. The page provided is the constant defined
      * for the selected page e.g. HOME for home page, CONNECTIONS for connections page etc.
      * If the name is not a defined constant, an error will be thrown
      */
    function generateNavBar($page) {
      global $user_type;

      checkPageName($page);

      echo "<script src=\"https://cdn.jsdelivr.net/npm/@popperjs/core@2.6.0/dist/umd/popper.min.js\" integrity=\"sha384-KsvD1yqQ1/1+IA7gi3P0tyJcT3vR+NdBTt13hSJ2lnve8agRGXTTyNaBYmCR/Nwi\" crossorigin=\"anonymous\"></script>
      <script src=\"https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/js/bootstrap.min.js\" integrity=\"sha384-nsg8ua9HAw1y0W1btsyWgBklPnCUAFLuTMS2G72MMONqmOymq585AcH49TLBQObG\" crossorigin=\"anonymous\"></script>
      <!-- style=\"border: 1px solid red;\" -->
      <nav class=\"navbar navbar-expand-lg navbar-light bg-light sticky-top\">
      <div class=\"container\">
      <a class=\"navbar-brand\" href=\"#\">
      <img src=\"images/logo.png\" alt=\"Logo\" style=\"width:125px; \">
      </a>
      <button class=\"navbar-toggler\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#navbarSupportedContent\" aria-controls=\"navbarSupportedContent\" aria-expanded=\"false\" aria-label=\"Toggle navigation\">
      <span class=\"navbar-toggler-icon\"></span>
      </button>
      <div class=\"collapse navbar-collapse\" id=\"navbarSupportedContent\">
      <ul class=\"navbar-nav me-auto mb-2 mb-lg-100\">";

      $class_name = ($page == HOME) ? ACTIVE:INACTIVE;
      echo "<li class=\"nav-item\">
      <a {$class_name} href=\"feed.php\">Home</a>
      </li>";

      if ($user_type == TEACHER) {
        $class_name = ($page == CONNECTIONS) ? ACTIVE:INACTIVE;
        echo "<li class=\"nav-item\">
        <a {$class_name} href=\"connections.php\">Network</a>
        </li>";
      }

      $class_name = ($page == VACANCIES) ? ACTIVE:INACTIVE;
      echo "<li class=\"nav-item\">
      <a {$class_name} href=\"vacancies.php\">Vacancies</a>
      </li>";

      $class_name = ($page == NOTIFICATIONS) ? ACTIVE:INACTIVE;
      echo "<li class=\"nav-item\">
      <a {$class_name} href=\"notifications.php\">Notifications</a>
      </li>";

      if ($user_type != ADMIN) {
        $class_name = ($page == PROFILE) ? ACTIVE:INACTIVE;
        $link = ($user_type == TEACHER) ? "teacher_profile.php":"organisation_profile.php";
        echo "<li class=\"nav-item\">
        <a {$class_name} href=\"{$link}\">Profile</a>
        </li>";
      }

       if($page != SEARCH) {
         echo "</ul><form class=\"d-flex\">
         <input class=\"form-control me-2\" name =\"q\" type=\"search\" placeholder=\"Search\" aria-label=\"Search\">
         <button class=\"btn btn-outline-success\" type=\"submit\">Search</button>
         </form>";

         if(isset($_GET['q'])) {
          $q = $_GET['q'];

          header('Location: '. 'search.php?q=' . $q);
        }
      } else {
        echo "</ul>";
      }

      echo "<a class=\"nav-link\" href=\"logout.php\">Logout</a>";

      if ($user_type == ADMIN) {
        $class_name = ($page == ADMINISTRATION) ? ACTIVE:INACTIVE;
        echo "<a {$class_name} href=\"administration.php\">Administration</a>";
      }

      echo "</div>
          </div>
          </nav>";
    }
?>
