<?php
  require_once "database.php";

  abstract class Notification {
            public $sender;
            public $receiver;
            public $type;
            public $viewed;
            public $target_link;
            public $created_at;

            public function messageForNotification(Notification $notification) : string{
            }

            //_SET Methods
            function setSender($sender){
                $this->sender = $sender;
            }

            function setCreated_at($created_at){
                $this->created_at = $created_at;

                if ($this->created_at == null) {
                  $this->created = date('Y-m-d H:i:s');
                }
            }

            function setTarget_link($target_link){
                $this->target_link = $target_link;
            }

            function setViewed($viewed){
                $this->viewed = $viewed;
            }

            function setType($type){
                $this->type = $type;
            }

            function setReceiver($receiver){
                $this->receiver = $receiver;
            }

            //_Notifications_Constructor
            function __construct($sender, $receiver, $type, $viewed, $target_link, $created_at) {
                $this->setSender($sender);
                $this->setReceiver($receiver);
                $this->setType($type);
                $this->setViewed($viewed);
                $this->setTarget_link($target_link);
                $this->setCreated_at($created_at);
              }


            function message() : string{
                return $this->notificationMessage($this);
            }

            //_GET Methods
            function getSender() : string{
                return $this->sender;
            }

            function getReceiver() : string{
                return $this->receiver;
            }

            function getType() : string{
                return $this->type;
            }

            function getViewed() : bool{
                return $this->viewed;
            }

            function getTarget_link() : string{
                return $this->target_link;
            }

            function getCreated_at() { //??
                return $this->created_at;
            }
        }

        class LikeNotification extends Notification{
            function __construct($sender, $receiver, $viewed, $target_link, $created_at) {
              parent::__construct($sender, $receiver, 'like', $viewed, $target_link, $created_at);
            }

            public function messageForNotification(Notification $notification) : string{
                return $this->sender->getSender() . 'has liked your post:';
            }
        }

        class ConnectionNotification extends Notification{
          function __construct($sender, $receiver, $viewed, $target_link, $created_at) {
            parent::__construct($sender, $receiver, 'request', $viewed, $target_link, $created_at);
          }

            public function messageForNotification(Notification $notification) : string{
                return $this->sender->getSender() . 'wants to connect with you';
            }
        }

        class ConnectionAcceptedNotification extends Notification {
          function __construct($sender, $receiver, $viewed, $target_link, $created_at) {
            parent::__construct($sender, $receiver, 'accepted', $viewed, $target_link, $created_at);
          }

            public function messageForNotification(Notification $notification) : string{
                return $this->sender->getSender() . 'has accepted your connection request';
            }
        }

        class ViewNotification extends Notification{
          function __construct($sender, $receiver, $viewed, $target_link, $created_at) {
            parent::__construct($sender, $receiver, 'view', $viewed, $target_link, $created_at);
          }

            public function messageForNotification(Notification $notification) : string{
                return $this->sender->getSender() . 'is viewing your profile';
            }
        }

        class FollowNotification extends Notification {
          function __construct($sender, $receiver, $viewed, $target_link, $created_at) {
            parent::__construct($sender, $receiver, 'follow', $viewed, $target_link, $created_at);
          }

            public function messageForNotification(Notification $notification) : string{
                return $this->sender->getSender() . 'has followed you';
            }
        }

        class AdminNotification extends Notification{
          function __construct($sender, $receiver, $viewed, $target_link, $created_at) {
            parent::__construct($sender, $receiver, 'admin', $viewed, $target_link, $created_at);
          }

          public function messageForNotification(Notification $notification) : string{
              return "Welcome to Teachers Connect!";
          }
      }

      class OrgInviteNotification extends Notification {
        function __construct($sender, $receiver, $viewed, $target_link, $created_at) {
          parent::__construct($sender, $receiver, 'org_invite', $viewed, $target_link, $created_at);
        }

        public function messageForNotification(Notification $notification) : string{
            return $this->sender . 'wants to invite you to join their organisation';
        }
      }

        function addNotification(Notification $notification){
          global $conn;

          $sql = "INSERT INTO notifications (username, sender, type, target_link, created_at) VALUES (?, ?, ?, ?, ?);";

          if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssss", $param_username, $param_sender, $param_type, $param_target, $param_created_at);
            $param_username = $notification->getReceiver();
            $param_sender = $notification->getSender();
            $param_type = $notification->getType();
            $param_target = $notification->getTarget_link();
            $param_created_at = $notification->getCreated_at();

            if (!$stmt->execute()) {
              die("Database error: {$stmt->error}");
            }

            $stmt->close();
          } else {
            die("Database error: {$conn->error}");
          }
        }

        function AddAdminNotification(Notification $notification){
          global $conn;

          $sql = "INSERT INTO notifications (username, sender, type, target_link) VALUES (?, ?, 'admin', ?);";

          if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sss", $param_username, $param_sender, $param_target);
            $param_username = $notification->getReceiver();
            $param_sender = "Teachers Connect Bot";
            $param_target = $notification->getTarget_link();

            if (!$stmt->execute()) {
              die("Database error: {$stmt->error}");
            }

            $stmt->close();
          } else {
            die("Database error: {$conn->error}");
          }
        }

        /**
          * Get the name of the sender depending on the type
          */
        function getSenderName($sender, $type) {
          global $conn;

          $sql = ($type == TEACHER) ? "SELECT first_name, last_name FROM teachers WHERE username = ?;"
          :"SELECT name FROM organisations WHERE username = ?;";

          if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $sender;

            $name = "";
            if ($stmt->execute()) {
              $result = $stmt->get_result();

              if ($row = $result->fetch_assoc()) {
                if ($type == TEACHER) {
                  $name = $row['first_name'] . " " . $row['last_name'];
                } else {
                  $name = $row['name'];
                }
              }
            } else {
              die("Database error: {$stmt->error}");
            }

            $stmt->close();
            return $name;
          } else {
            die("Database error: {$conn->error}");
          }
        }

        function format_time($timestamp)
        {
             $time_ago = strtotime($timestamp);
             $current_time = time();
             $time_difference = $current_time - $time_ago;
             $seconds = $time_difference;
             $minutes = round($seconds / 60 );
             $hours   = round($seconds / 3600);
             $days    = round($seconds / 86400);
             $weeks   = round($seconds / 604800);
             $months  = round($seconds / 2629440);
             $years   = round($seconds / 31553280);
             if($seconds <= 60)
             {
            return "Just Now";
          }
             else if($minutes <=60)
             {
            if($minutes==1)
                  {
              return "1 minute ago";
            }
            else
                  {
              return "$minutes min ago";
            }
          }
             else if($hours <=24)
             {
            if($hours==1)
                  {
              return "1 hour ago";
            }
                  else
                  {
              return "$hours hrs ago";
            }
          }
             else if($days <= 7)
             {
            if($days==1)
                  {
              return "Yesterday";
            }
                  else
                  {
              return "$days days ago";
            }
          }
             else if($weeks <= 4.3)
             {
            if($weeks==1)
                  {
              return "1 week ago";
            }
                  else
                  {
              return "$weeks weeks ago";
            }
          }
              else if($months <=12)
             {
            if($months==1)
                  {
              return "1 month ago";
            }
                  else
                  {
              return "$months months ago";
            }
          }
             else
             {
            if($years==1)
                  {
              return "1 year ago";
            }
                  else
                  {
              return "$years years ago";
            }
          }
        }
 ?>
