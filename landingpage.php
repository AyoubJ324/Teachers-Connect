<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link rel="stylesheet" href="css/landingpage.css">
        <title>
            Welcome to Teachers Connect!
        </title>
        <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="HandheldFriendly" content="true">
    </head>

    <body class="landing-page">
        <section>
            <header class="landing-navbar-header">
                <h2> <img class="landing-logo" src="./images/logo.png"></h2>
                <div class = "landing-navbar">
                    <a href="#landing-about">About Teachers Connect</a>
                    <a href="#landing-contact">Contact Us</a>
                </div>
                <div class="landing-buttons">
                    <button class="landing-btn1"  onclick="window.location.href='login.php'">Sign In</button>
                    <button class="landing-btn2" onclick="window.location.href='teacher_signup.php'">Sign Up</button>
                </div>

            </header>
            
            <div class="landing-content">
                <div class="landing-info">
                    <img class="landing-image" src="./images/LandingPageimg.jpg">
                    <h2>Welcome to your professional education community</h2>
                    <button class="landing-btn3" onclick="window.location.href='teacher_signup.php'">Continue</button>
                </div>
            </div>

            <div id="landing-about">
                <h2>About Us</h2>
                <p>
                   Teachers Connect is a social network specifically for the educators of Ireland that focuses on professional networking and career development. You can use Teachers Connect to display your resume, search for jobs, and enhance your professional reputation by posting updates and interacting with other people.
                </p>
            </div>
            <h2 id="landing-contact">Whats on your mind?</h2>
            <div id="landing-contact">
                <form id = "landing-contact-form" method="Post" action="contact-from-handler.php">
                    <input name="name" type="text" class="landing-form-control" placeholder="Enter your name" required>
                    <br>
                    <br>
                    <input name="email" type="text" class="landing-form-control" placeholder="Enter your email" required>
                    <br>
                    <br>
                    <textarea name="message" class="landing-form-control" placeholder="Enter your message" required></textarea>
                    <br>
                    <br>
                    <input type="submit" class="landing-btn4" value="Send" onclick="alert('Message Sent! A member of our tream will respond shortly')">
            </form>
            <img class="landing-image-mail" src="./images/mail.png">
            </div>
        </section>
    </body>
</html>
