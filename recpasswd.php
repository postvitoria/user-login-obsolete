<?php
    session_start();

    use PHPMailer\PHPMailer\PHPMailer;
    use PHPMailer\PHPMailer\Exception;
    
    require 'phpmailer/src/Exception.php';
    require 'phpmailer/src/PHPMailer.php';
    require 'phpmailer/src/SMTP.php';
    require 'database.php';

    function createRandomPassword() { 

        $chars = "abcdefghijkmnopqrstuvwxyz023456789"; 
        srand((double)microtime()*1000000); 
        $i = 0; 
        $pass = '' ; 
    
        while ($i <= 7) { 
            $num = rand() % 33; 
            $tmp = substr($chars, $num, 1); 
            $pass = $pass . $tmp; 
            $i++; 
        } 
    
        return strtoupper($pass); 
    }     

    if (!empty($_POST['email'])) {
        $records = $conn->prepare('SELECT email FROM users WHERE email = :email');
        $records->bindParam(':email', $_POST['email']);
        $records->execute();
        $results = $records->fetch(PDO::FETCH_ASSOC);
        $message = '';
       
        $mail = new PHPMailer(true);
        $code = createRandomPassword();

        try {
            if ($results) {
                $message = 'We have sent a recovery link to your email';

                $mail->isSMTP();                                            //Send using SMTP
                $mail->Host       = 'smtp.gmail.com';                     //Set the SMTP server to send through
                $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
                $mail->Username   = 'gpostigovi@gmail.com';                     //SMTP username
                $mail->Password   = 'ghxoykhnneqgsgkb';                               //SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
                $mail->Port       = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`
        
                $mail-> setFrom('gpostigovi@gmail.com');
                $mail-> addAddress($_POST['email']);
                $mail-> isHTML(true);

                $mail-> Subject   = "Email Verification test";
                $mail-> Body      = "Your verification code is <b>{$code}</b>";

                $mail-> send();
                $already_sent = True;
                
            } else {
                $message = 'That account does not exist';
            }
        } catch (Exception $e) {
            $message = 'There was a problem recovering the password';
        }
    }

    if ($_POST['code'] == $code && $_POST['passwd'] == $_POST['rpasswd']){
        $sql = "UPDATE users SET password = :password WHERE email = :email";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':email', $_POST['email']);
        $stmt->bindParam(':password', $password);

        //$password = password_hash($_POST['passwd'], PASSWORD_BCRYPT);
    } else {
        $message = 'Invalid code or password, please try again'; 
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href="css/style-recpasswd.css">

        <title>ph0sbit_recovery</title>
    </head>

    <body>
        <section>
            <div class="signin">
                <div class="content">
                    <h2>Identify your account</h2>
                    <form class="form" method="post">
                        <p class="message">Which Ph0sbit account are you having trouble accessing?</p>

                        <?php if(!empty($message)): ?>
                            <p class="message"> <?= $message ?></p>
                        <?php endif; ?>

                        <div class="inputBx" id="correo">
                            <input type="text" name="email" required>
                            <i>Email</i>
                        </div>
                        
                        <?php if($_SERVER['REQUEST_METHOD'] == 'POST' && $results): ?>
                            <style type="text/css">#correo{display:none;}</style>

                            <div class="inputBx">
                                <input type="password" name="passwd" required>
                                <i>Password</i>
                            </div>

                            <div class="inputBx">
                                <input type="password" name="rpasswd" required>
                                <i>Repeat password</i>
                            </div>

                            <div class="inputBx">
                                <input type="text" name="code" required>
                                <i>Code</i>
                            </div>
                        <?php endif; ?>

                        <div class="links">
                            <a href="login.php">Login</a>
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