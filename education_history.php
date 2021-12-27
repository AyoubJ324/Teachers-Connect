<?php
  /**
    * This class provides a way to represent an entry of the academic_degrees table
    */
  class AcademicDegree {
    /**
      * The id of this degree
      */
    private $degree_id;
    /**
      * The title of this degree
      */
    private $title;
    /**
      * The type of the degree
      */
    private $type;
    /**
      * The school this degree was obtained from
      */
    private $school;
    /**
      * The description for this degree
      */
    private $description;
    /**
      * The level at which this degree is offered
      */
    private $level;

    /**
      * Construct an academic degree object
      */
    function __construct($degree_id, $title, $type, $school, $description, $level) {
      $this->degree_id = $degree_id;
      $this->title = $title;
      $this->type = $type;
      $this->school = $school;
      $this->description = $description;
      $this->level = $level;
    }

    /**
      * Returns this degree's id
      */
    function degree_id() {
      return $this->degree_id;
    }

    /**
      * Returns this degree's title
      */
    function title() {
      return $this->title;
    }

    /**
      * The school this degree was obtained in
      */
    function school() {
      return $this->school;
    }

    /**
      * Returns this degree's type
      */
    function type() {
      return $this->type;
    }

    /**
      * Returns this degree's description
      */
    function description() {
      return $this->description;
    }

    /**
      * Returns this degree's level
      */
    function level() {
      return $this->level;
    }
  }

  /**
    * This class provides a way to represent an entry of the qualifications table
    */
  class Qualification {
    /**
      * The teacher this qualification belongs to
      */
    private $teacher;
    /**
      * The degree for this qualification
      */
    private $degree;
    /**
      * The date the qualification was obtained
      */
    private $date_obtained;

    /**
      * Construct a teacher object
      */
    function __construct($teacher, $degree, $date_obtained) {
      $this->teacher = $teacher;
      $this->degree = $degree;
      $this->date_obtained = $date_obtained;
    }

    /**
      * Returns this qualification's teacher
      */
    function teacher() {
      return $this->teacher;
    }

    /**
      * Returns this qualification's degree
      */
    function degree() {
      return $this->degree;
    }

    /**
      * Returns this degree's date_obtained
      */
    function date_obtained() {
      return $this->date_obtained;
    }
  }
 ?>
