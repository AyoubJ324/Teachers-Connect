<?php 
    include "database.php";

    $id = $_GET['id'];

    $deletequery = "DELETE FROM `notifications` WHERE id = $id";

    $query = mysqli_query($conn, $deletequery);

    header('location: notifications.php')

?>