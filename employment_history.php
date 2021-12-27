<?php
  /**
    * This class is used to represent an employment_history entry
    */
    class EmploymentHistory {
      /**
        * The is of the database entry
        */
      private $history_id;
      /**
        * The teacher that the history belongs to
        */
      private $teacher;
      /**
        * The organisation entry that this history is for
        */
      private $organisation;
      /**
        * The start date of the history
        */
      private $dateFrom;
      /**
        * The end date of the history
        */
      private $dateTo;
      /**
        * The tile of the job
        */
      private $job_title;

      /**
        * Construct an employment history object
        */
      function __construct($history_id, $teacher, $organisation, $dateFrom, $dateTo, $job_title) {
        $this->history_id = $history_id;
        $this->teacher = $teacher;
        $this->organisation = $organisation;
        $this->dateFrom = $dateFrom;
        $this->dateTo = $dateTo;
        $this->job_title = $job_title;
      }

      /**
        * Return this history's id
        */
      function history_id() {
        return $this->history_id;
      }

      /**
        * Return this history's teacher
        */
      function teacher() {
        return $this->teacher;
      }

      /**
        * Return this history's organisation
        */
      function organisation() {
        return $this->organisation;
      }

      /**
        * Return this history's start date
        */
      function dateFrom() {
        return $this->dateFrom;
      }

      /**
        * Return this history's end date
        */
      function dateTo() {
        return $this->dateTo;
      }

      /**
        * Return this history's job title
        */
      function job_title() {
        return $this->job_title;
      }
    }
 ?>
