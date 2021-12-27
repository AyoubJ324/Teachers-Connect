<?php
    $name = $_Post['name'];
    $email = $_Post['email'];
    $message = $_Post['message'];

    $subject = "Teachers Connect Query";
    $content = "User Name: $name
                ./n.".
                "User Email: $email
                ./n".
                "Message: $message
                ./n"
                ;

    $send_to = "ayoubjdair00@gmail.com";

    $headers = 'From:' .$email;

    if( mail($send_to, $subject, $content)!==true)
    {
        //die('');
        header("Location: landingpage.php");
    }else{
        echo "Success";
        header("Location: landingpage.php");
    }
    
    // if( mail($send_to, $subject, $content, $headers))
    // {
    //     echo "sucsess";
    //     header("Location: landingpage.php");

    // }else{
    //     echo "failed";
    // }
?> 
