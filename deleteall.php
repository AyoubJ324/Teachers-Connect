<?php
    session_start();
    include "database.php";

    $username = $_SESSION['username'];

    $deleteallquery = "DELETE FROM `notifications` WHERE username = '{$username}'";

    $query = mysqli_query($conn, $deleteallquery);

    header('location: notifications.php')

?>
