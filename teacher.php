<?php
  /**
    * This class provides a means of reading a teachers entry into an object
    */
  class Teacher {
    /**
     * The username of the teacher
     */
    private $username;
    /**
      * The first name of the teacher
      */
    private $first_name;
    /**
      * The last name of the teacher
      */
    private $last_name;
    /**
      * The teacher's headline
      */
    private $headline;
    /**
      * The teacher's about section
      */
    private $about;
    /**
      * The teacher's location
      */
    private $location;
    /**
      * The path to their profile photo
      */
    private $profile_photo;

    /**
      * Constructs a Teacher object with the provided parameters
      */
    function __construct($username, $first_name, $last_name, $headline, $about, $location, $profile_photo) {
      $this->username = $username;
      $this->first_name = $first_name;
      $this->last_name = $last_name;
      $this->headline = $headline;
      $this->about = $about;
      $this->location = $location;
      $this->profile_photo = $profile_photo;
    }

    /**
      * Retrieve the teacher's username
      */
    function username() {
      return $this->username;
    }

    /**
      * Retrieve the teacher's first name
      */
    function firstName() {
      return $this->first_name;
    }

    /**
      * Retrieve the teacher's last name
      */
    function lastName() {
      return $this->last_name;
    }

    /**
      * Retrieve the teacher's headline
      */
    function headline() {
      return $this->headline;
    }

    /**
      * Retrieve the teacher's about section
      */
    function about() {
      return $this->about;
    }

    /**
      * Retrieve the teacher's location
      */
    function location() {
      return $this->location;
    }

    /**
      * Retrieve the path to the teacher's profile picture
      */
    function profile_photo() {
      return $this->profile_photo;
    }
  }
 ?>
