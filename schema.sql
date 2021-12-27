/* --CS4116 Project DB Tables (SQL) */

/*
  Group 08

  Edward Lynch-Milner - 18222021
  Sean Barrett - 15124126
  Michael Donegan - 18235549
  Ayoub Jdair - 18266401
*/

/* Ayoub 1 - 5 Tables */

/*
  Table 1 accounts
  Each user (admin, teacher, organisation) will need an account to sign in. This table stores an account for each user.
  An account stores the credentials for login. A profile is then added on top of an account (see profiles tables).
  There can be multiple entries with the same email since we allow a user sign up as an organisation and a teacher
  if they so wish. Username will always be unique however, and it cannot be null, so we will choose it as the primary key.
  created_at should revert to the default CURRENT_TIMESTAMP
*/
CREATE TABLE IF NOT EXISTS accounts (
  username VARCHAR(32) NOT NULL,
  email VARCHAR(255),
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  type ENUM('admin' , 'teacher' , 'organisation'),
  PRIMARY KEY (username)
);

/*
  Table 2 Teachers
  This table stores profile information for a teacher. This is information specific to a teacher and cannot be included in
  the accounts table.
  Here, a teacher is required to have an account, so their username is the primary key as it uniquely identifies each entry,
  but it also references the accounts table
*/
CREATE TABLE IF NOT EXISTS teachers (
  username VARCHAR(32) NOT NULL,
  first_name VARCHAR(64),
  last_name VARCHAR(64),
  headline VARCHAR(255),
  about TEXT,
  location VARCHAR(64),
  profile_photo VARCHAR(255),
  PRIMARY KEY (username),
  FOREIGN KEY (username) REFERENCES accounts(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);

/*
  Table 3 organisations
  Like teachers, this stores organisation specific information. An organisation may not have an account,
  i.e., username will be null. This occurs when a user enters an organisation in their employment history,
  but no organisation has been signed up for that user.
  Therefore, username cannot be a primary key as it can be null. Therefore, we need a separate organisation_id
  to uniquely identify each record
*/
CREATE TABLE IF NOT EXISTS organisations (
  organisation_id INTEGER NOT NULL AUTO_INCREMENT,
  username VARCHAR(32),
  name VARCHAR(64),
  headline VARCHAR(255),
  about TEXT,
  location VARCHAR(64),
  profile_photo VARCHAR(255),
  PRIMARY KEY (organisation_id),
  FOREIGN KEY (username) REFERENCES accounts(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);

/*
  Table 4 blocked_teachers
  This table holds all the teachers that another teacher may have blocked. Organisations can’t block users,
  this functionality is only available for teachers.
  A teacher can’t block another teacher twice, so we have blocked_teacher and blocker as a composite primary key.
  These values are the teachers’ usernames, so they are foreign keys referencing the teacherss table username field
*/
CREATE TABLE IF NOT EXISTS blocked_teachers (
  blocked_teacher VARCHAR(32),
  blocker VARCHAR(32),
  PRIMARY KEY (blocked_teacher, blocker),
  FOREIGN KEY (blocked_teacher) REFERENCES teachers(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  FOREIGN KEY (blocker) REFERENCES teachers(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);

/*
  Table 5 organisation_members
  This table provides the means of adding teachers to an organisation.
  A teacher can only be part of one organisation at a time, hence why we have teacher_username as the primary key.
*/
CREATE TABLE IF NOT EXISTS organisation_members (
  teacher_username VARCHAR(32),
  organisation_id INTEGER,
  PRIMARY KEY (teacher_username),
  FOREIGN KEY (teacher_username) REFERENCES teachers(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  FOREIGN KEY (organisation_id) REFERENCES organisations(organisation_id)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);

/* Table 6 followed_organisations
  This table stores a list of organisations that a teacher may be following
*/
CREATE TABLE IF NOT EXISTS followed_organisations (
  teacher_username VARCHAR(32),
  organisation_id INTEGER,
  PRIMARY KEY (teacher_username, organisation_id),
  FOREIGN KEY (teacher_username) REFERENCES teachers(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  FOREIGN KEY (organisation_id) REFERENCES organisations(organisation_id)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);

/*Table 7 connections
  This table stores connections between at most two teachers (can only have 1 connection per 2 teachers).
  A connection can have 2 states:
    pending - the connection has been sent to the teacher identified by 'destination', but has not been accepted yet
    accepted - the connected has been accepted by the teacher identified by 'destination'
  If a connection is deleted or rejected, the connection entry should be deleted from here
*/
CREATE TABLE IF NOT EXISTS connections (
  destination VARCHAR(32),
  sender VARCHAR(32),
  status ENUM('pending', 'accepted'),
  PRIMARY KEY (destination, sender),
  FOREIGN KEY (destination) REFERENCES teachers(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  FOREIGN KEY (sender) REFERENCES teachers(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);

/*Table 8 skills
  This table stores a list of defined skills. A skill is created when a user enters
  a skill that has not been created before.
  A table of skills allows skills to be automatically recommended
*/
CREATE TABLE IF NOT EXISTS skills (
  skill_id INTEGER AUTO_INCREMENT,
  name VARCHAR(64),
  PRIMARY KEY (skill_id)
);

/*Table 9 vacancies
  This table stores vacancies posted by an organisation. A vacancy is composed of
  the basic fields, job_title (short name of the job), description (a large piece of text outlining
  job requirements), their type (full-time, part-time etc.) and the date and time they were posted at
*/
CREATE TABLE IF NOT EXISTS vacancies (
  vacancy_id INTEGER AUTO_INCREMENT,
  organisation_id INTEGER,
  job_title VARCHAR(64),
  description TEXT,
  type VARCHAR(64),
  posted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (vacancy_id),
  FOREIGN KEY (organisation_id) REFERENCES organisations(organisation_id)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);

/* Table 10 vacancy_skills
  This table stores a list of skills that an organisation may have specified on
  posting of the vacancy
*/
CREATE TABLE IF NOT EXISTS vacancy_skills (
  vacancy_id INTEGER,
  skill_id INTEGER,
  PRIMARY KEY (vacancy_id, skill_id),
  FOREIGN KEY (vacancy_id) REFERENCES vacancies(vacancy_id)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  FOREIGN KEY (skill_id) REFERENCES skills(skill_id)
      ON DELETE CASCADE
      ON UPDATE RESTRICT
);

/* Table 11 teacher_skills
  This table stores a list of skills that an teacher may have specified on
  their profile, this data can be used to help find vacancies which matches
  a teachers skills
*/

CREATE TABLE IF NOT EXISTS teacher_skills (
  username VARCHAR(32),
  skill_id INTEGER,
  PRIMARY KEY (username, skill_id),
  FOREIGN KEY (username) REFERENCES teachers(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  FOREIGN KEY (skill_id) REFERENCES skills(skill_id)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);

/* Table 12 employment_history
  This table stores any employment history that teacher inputs.
  This data will be displayed on a teachers profile
*/

CREATE TABLE IF NOT EXISTS employment_history (
  history_id INTEGER AUTO_INCREMENT,
  username VARCHAR(32),
  organisation_id INTEGER,
  dateFrom DATE,
  dateTo DATE,
  job_title VARCHAR(32),
  PRIMARY KEY (history_id),
  FOREIGN KEY (username) REFERENCES teachers(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  FOREIGN KEY (organisation_id) REFERENCES organisations(organisation_id)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);

/* Table 13 academic_degrees

  This table stores a list of academic degrees which a user can
  add to or choose from.
*/

CREATE TABLE IF NOT EXISTS academic_degrees (
  degree_id INTEGER AUTO_INCREMENT,
  title VARCHAR(32),
  type VARCHAR(32),
  school VARCHAR(32),
  description VARCHAR(255),
  level VARCHAR(32),
  PRIMARY KEY (degree_id)
);
/* Table 14 qualifications

This table allows us to access the academic degrees of individual teachers. degree_id references academic_degrees(degree_id). username references teachers.
*/
CREATE TABLE IF NOT EXISTS qualifications (
  username VARCHAR (32),
  degree_id INTEGER,
  date_obtained DATE,
  PRIMARY KEY (username, degree_id),
  FOREIGN KEY (username) REFERENCES teachers(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  FOREIGN KEY (degree_id) REFERENCES academic_degrees(degree_id)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);

/* Table 15 posts

This table contains all the posts made on the site. Posts will be saved under a primary key of post_id. Each post can only have one user
and must have a user.
*/

CREATE TABLE IF NOT EXISTS posts (
  post_id INTEGER AUTO_INCREMENT,
  username VARCHAR (32),
  content TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (post_id),
  FOREIGN KEY (username) REFERENCES accounts(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);

/* Table 16 post_likes

This table contains the likes for posts. post_id, username will be the primary key, as any user can only like a post once.
This will allow us to store all post likes for all posts.
*/

CREATE TABLE IF NOT EXISTS post_likes (
  post_id INTEGER,
  username VARCHAR (32),
  PRIMARY KEY (post_id, username),
  FOREIGN KEY (post_id) REFERENCES posts(post_id)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  FOREIGN KEY (username) REFERENCES accounts(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);

/*Table 17 tags

This table contains a complete list of tags. This is a list of things that teaches can be interested in.
This will be used in feed to filter posts depending on a teacher's individual interests.
*/

CREATE TABLE IF NOT EXISTS tags (
  tag_id INTEGER AUTO_INCREMENT,
  name VARCHAR(64),
  PRIMARY KEY (tag_id)
);

/* Table 18 post_tags

This table contains a list of post tags. Each post will contain a number of tags and those belonging to a post are found here.
This allows us to populate the feed according to a user's individual interests.
*/

CREATE TABLE IF NOT EXISTS post_tags (
  post_id INTEGER,
  tag_id INTEGER,
  PRIMARY KEY (post_id, tag_id),
  FOREIGN KEY (post_id) REFERENCES posts(post_id)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  FOREIGN KEY (tag_id) REFERENCES tags(tag_id)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);

/* Table 19 teacher_interests

This table contains a list of teacher interests. Teachers can have multiple interests and each of these will
be represented in the table in teacher-interest pairs. This will allow us to represent all teacher interests.
This will be used in the feed for filtering posts.
*/

CREATE TABLE IF NOT EXISTS teacher_interests (
  username VARCHAR (32),
  tag_id INTEGER,
  PRIMARY KEY (username, tag_id),
  FOREIGN KEY (username) REFERENCES teachers(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  FOREIGN KEY (tag_id) REFERENCES tags(tag_id)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);

/* Table 20 notifications

This table
*/

CREATE TABLE IF NOT EXISTS notifications (
  id INTEGER AUTO_INCREMENT,
  username VARCHAR (32),
  sender VARCHAR (32),
  type ENUM('view' , 'request' , 'accepted', 'like' , 'follow', 'admin', 'org_invite'),
  target_link VARCHAR (255),
  viewed BOOLEAN DEFAULT false,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  FOREIGN KEY (username) REFERENCES accounts(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  FOREIGN KEY (sender) REFERENCES accounts(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);

INSERT INTO `accounts` (`username`, `email`, `password`, `created_at`, `type`) VALUES ('Teachers Connect Bot', 'support@teachersconnect.ie', 'pass123', CURRENT_TIMESTAMP, 'admin');

/*Table 21 banned_users

This table contains a record of banned users. The banned user is saved under username. banned_by is the banning admin. If date_to > current date, a user is banned

*/

CREATE TABLE IF NOT EXISTS banned_users (
  username VARCHAR(32),
  banned_by VARCHAR(32),
  reason VARCHAR(64),
  date_from TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  date_to TIMESTAMP,
  PRIMARY KEY (username),
  FOREIGN KEY (username) REFERENCES accounts(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT,
  FOREIGN KEY (banned_by) REFERENCES accounts(username)
    ON DELETE CASCADE
    ON UPDATE RESTRICT
);

/* Table 22 email_blacklist

This table contains the emails of blacklisted users. Emails that appear on this table will not be accepted as valid emails at sign-up

*/

CREATE TABLE IF NOT EXISTS email_blacklist (
  email VARCHAR (255),
  PRIMARY KEY (email)
);

CREATE TABLE IF NOT EXISTS password_reset_keys (
  username VARCHAR(32),
  reset_key VARCHAR(255) NOT NULL,
  expiry TIMESTAMP NOT NULL,
  FOREIGN KEY (username) REFERENCES accounts(username)
    ON DELETE CASCADE
    ON UPDATE CASCADE
);
