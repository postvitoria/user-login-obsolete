<?php
    session_start();
    require 'database.php';

    $message = '';

    if (!empty($_POST['email']) && !empty($_POST['passwd'])) {
        $records = $conn->prepare('SELECT id, email, password FROM users WHERE email = :email');
        $records->bindParam(':email', $_POST['email']);
        $records->execute();
        $results = $records->fetch(PDO::FETCH_ASSOC);
        
        // Verificar si se encontró el usuario
        if ($results && password_verify($_POST['passwd'], $results["password"])) {
            $_SESSION['user_id'] = $results['id'];
            header("Location: /user-login/index.php");
        } else {
            $message = 'Sorry, those credentials do not match';
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href="css/style-login.css">

        <title>ph0sbit_login</title>
    </head>

    <body>
        <section>
            <div class="signin">
                <div class="content">
                    <h2>login</h2>
                    <form class="form" method="post">
                        <?php if(!empty($message)): ?>
                            <p class="message"> <?= $message ?></p>
                        <?php endif; ?>

                        <div class="inputBx">
                            <input type="text" name="email" required>
                            <i>Email</i>
                        </div>
                        <div class="inputBx">
                            <input type="password" name="passwd" required>
                            <i>Password</i>
                        </div>
                        <div class="links">
                            <a href="recpasswd.php">Forgot passwd</a>
                            <a href="signup.php">Signup</a>
                        </div>
                        <div class="inputBx">
                            <input type="submit" value="Submit">
                        </div>
                    </form>
                </div>
            </div>
        </section>
    </body>
</html>