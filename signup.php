<?php 
    require 'database.php';
    
    $message = '';

    if (!empty($_POST['email']) && !empty($_POST['password']) && !empty($_POST['rpassword']) && !empty($_POST['username'])) {
        if ($_POST['password'] == $_POST['rpassword']) {
            if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
                try {
                    $sql = "INSERT INTO users (username, email, password) VALUES (:username, :email, :password)";
                    $stmt = $conn->prepare($sql);
                    
                    $stmt->bindParam(':username', $_POST['username']);
                    $stmt->bindParam(':email', $_POST['email']);
                    
                    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    //$password = password_hash($_POST['password'], PASSWORD_BCRYPT);
                    $stmt->bindParam(':password', $hashed_password);
    
                    if ($stmt->execute()) {
                        header("location: logout.php");
                    } else {
                        $message = 'Sorry there must have been an issue creating your account';
                    }
                } catch (Exception $e) {
                    $message = 'Something went wrong. Try again';
                }
                
            } else {
                $message = 'This email is invalid';
            }
        } else {
            $message = 'Both passwords must be the same';
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href="css/style-signup.css">

        <title>ph0sbit_signup</title>
    </head>

    <body>
        <section>
            <div class="signin">
                <div class="content">
                    <h2>signup</h2>
                    <form class="form" method="post">
                        <?php if(!empty($message)): ?>
                            <p class="message"> <?= $message ?></p>
                        <?php endif; ?>
                        
                        <div class="inputBx">
                            <input type="text" name="username" required>
                            <i>Username</i>
                        </div>
                        <div class="inputBx">
                            <input type="text" name="email" required>
                            <i>Email</i>
                        </div>
                        <div class="inputBx">
                            <input type="password" name="password" required>
                            <i>Password</i>
                        </div>
                        <div class="inputBx">
                            <input type="password" name="rpassword" required>
                            <i>Repeat Password</i>
                        </div>
                        <div class="links">
                            <a href="#">Support</a>
                            <a href="login.php">Login</a>
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