<?php
  /**
    * This class represents an entry in the organisations table
    */
  class Organisation {
    /**
      * The id of this organisation
      */
    private $organisation_id;
    /**
      * The username of the account belonging to this organisation if any
      */
    private $username;
    /**
      * The name of this organisation
      */
    private $name;
    /**
      * The headline of the organisation
      */
    private $headline;
    /**
      * The about section of the organisation
      */
    private $about;
    /**
      * The location of the organisation
      */
    private $location;
    /**
      * The path to the organisation's profile photo
      */
    private $profile_photo;

    /**
      * Construct a new instance of an organisation
      */
    function __construct($organisation_id, $username, $name, $headline, $about, $location, $profile_photo) {
      $this->organisation_id = $organisation_id;
      $this->username = $username;
      $this->name = $name;
      $this->headline = $headline;
      $this->about = $about;
      $this->location = $location;
      $this->profile_photo = $profile_photo;
    }

    /**
      * Returns the id of this organisation
      */
    function organisation_id() {
      return $this->organisation_id;
    }

    /**
      * Returns the username of the organisation if they have an account
      */
    function username() {
      return $this->username;
    }

    /**
      * Returns the name of this organisation
      */
    function name() {
      return $this->name;
    }

    /**
      * Return the headline of this organisation
      */
    function headline() {
      return $this->headline;
    }

    /**
      * Return the about text for this organisation
      */
    function about() {
      return $this->about;
    }

    /**
      * Return the location of the organisation
      */
    function location() {
      return $this->location;
    }

    /**
      * Return the path to the organisation's profile photo
      */
    function profile_photo() {
      return $this->profile_photo;
    }
  }
 ?>
