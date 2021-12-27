<?php
  define('LOGGED_IN', 'loggedin');
  define('USERNAME', 'username');
  define('USER_TYPE', 'usertype');
  define('ADMIN', 'admin');
  define('TEACHER', 'teacher');
  define('ORGANISATION', 'organisation');
  define('PROFILE_GET_USERNAME', 'username');
  define('SENDER', 'sender');
  define('DESTINATION', 'destination');
  define('ACTION', 'action');
  define('CONNECT', 'connect');
  define('FOLLOW', 'follow');
  define('BLOCK', 'block');
  define('ORGANISATION_INVITE', 'organisation_invite');
  define('INVITATION_ID', 'invitation_id');
  define('ACTION_PARAM', 'action_param');
  define('REMOVE', 'remove');
  define('ADD', 'add');
  define('ACCEPT', 'accept');
  define('EDIT_TYPE', 'edit_type');
  define('EDIT_FORM', 'edit_form');
  define('UPDATE_PROFILE', 'update_profile');
  define('UPDATE_PASSWORD', 'update_password');
  define('UPDATE_VACANCY', 'update_vacancy');
  define('DELETE_VACANCY', 'delete_vacancy');
  define('JOIN_ORGANISATION', 'join_organisation');
  define('EDUCATION_HISTORY', 'education_history');
  define('DELETE_EDUCATION', 'delete_education');
  define('ADD_EMPLOYMENT_HISTORY', 'add_employment_history');
  define('REMOVE_EMPLOYMENT_HISTORY', 'remove_employment_history');
  define('ADD_SKILLS', 'add_skills');
  define('REMOVE_SKILL', 'remove_skill');
  define('DELETE_ACCOUNT', 'delete_account');
  define('DEFAULT_TEACHER_PROFILE_PIC', 'images/teacher_profile.png');
  define('DEFAULT_ORG_PROFILE_PIC', 'images/org.png');

  /**
    * Format a date object
    */
  function formatDate($date) {
    $date = strtotime($date);
    $date = date("d/m/Y", $date);

    return $date;
  }
 ?>
