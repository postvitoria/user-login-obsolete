<?php
    require 'database.php';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'];
    
        if ($action === 'send') {
            $senderId = $_POST['sender'];
            $recieverId = $_POST['reciever'];
            $content = $_POST['content'];
            $filename = $_POST['filename'];
            $type = $_POST['type'];
            $timestamp = $_POST['time'];

            $sql = "INSERT INTO messages (sender, reciever, content, filename, type, timestamp) VALUES (:sender, :reciever, :content, :filename, :type, :timestamp)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':sender', $senderId);
            $stmt->bindParam(':reciever', $recieverId);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':filename', $filename);
            $stmt->bindParam(':type', $type);
            $stmt->bindParam(':timestamp', $timestamp);

            $stmt->execute();
        } elseif ($action === 'load') {
            $senderId = $_POST['sender'];
            $recieverId = $_POST['reciever'];
    
            $sql = 'SELECT * FROM messages WHERE (sender = :sender_id AND reciever = :reciever_id) OR (sender = :reciever_id AND reciever = :sender_id) ORDER BY date_created ASC LIMIT 100';
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':sender_id', $senderId);
            $stmt->bindParam(':reciever_id', $recieverId);
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo json_encode($row);
            }
        } elseif ($action === 'check') { 
            $senderId = $_POST['sender'];
    
            $sql = 'SELECT * FROM messages WHERE ((sender = :sender_id) OR (reciever = :sender_id)) AND read_status = 0';
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':sender_id', $senderId);
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo json_encode($row);
            }
        } elseif ($action === 'read') { 
            $id = $_POST['id'];

            $sql = 'UPDATE messages SET read_status = 1 WHERE id = :id';
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
        } elseif ($action === 'last_message') { 
            $sender = $_POST['sender'];
            $reciever = $_POST['reciever'];
            $content = $_POST['message'];

            $sql = 'UPDATE user_contacts SET last_message = :content WHERE ((user_id = :sender AND contact_id = :reciever) OR (user_id = :reciever AND contact_id = :sender))';
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':sender', $sender);
            $stmt->bindParam(':reciever', $reciever);
            $stmt->bindParam(':content', $content);
            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo json_encode($row);
            }
        } elseif ($action === 'send_image') {
            $target_dir = "resources/img/sendedImgs/";
            $file_name = $_POST["imageName"];
            $target_file = $target_dir . $file_name;
            $uploadOk = 1;
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
            
            // Verificar si el archivo es una imagen real o una imagen falsa
            $check = getimagesize($_FILES["image"]["tmp_name"]);
            if ($check === false) {
                echo "El archivo no es una imagen.";
                $uploadOk = 0;
            }
            
            // Verificar si $uploadOk está configurado en 0 por un error
            if ($uploadOk == 0) {
                echo "Lo siento, tu archivo no fue cargado.";
            // Si todo está bien, intenta subir el archivo
            } else {
                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    echo "El archivo " . htmlspecialchars(basename($file_name)) . " ha sido cargado.";
                } else {
                    echo "Lo siento, hubo un error al subir tu archivo.";
                }
            }
        } elseif ($action === 'load_contacts_requests') {
            $senderId = $_POST['sender'];
    
            $sql = 'SELECT id, username, image_url FROM users INNER JOIN contacts_requests ON users.id = contacts_requests.reciever WHERE contacts_requests.sender = :sender_id ORDER BY created_date DESC LIMIT 100';
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':sender_id', $senderId);
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo json_encode($row);
            }
        } elseif ($action === 'exist_contact') {
            $username = $_POST['username'];
            $sender = $_POST['sender'];

            $sql = "SELECT u.id, u.username FROM users u LEFT JOIN user_contacts uc ON u.id = uc.user_id WHERE u.username = :username AND u.id <> :sender AND NOT EXISTS (SELECT 1 FROM user_contacts uc_check WHERE (uc_check.user_id = u.id AND uc_check.contact_id = :sender) OR (uc_check.user_id = :sender AND uc_check.contact_id = u.id));";

            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':sender', $sender);

            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo json_encode($row);
            }
        } elseif ($action === 'send_request') {
            $sender = $_POST['sender'];
            $reciever = $_POST['reciever'];

            $sql = "INSERT INTO contacts_requests (sender, reciever) VALUES (:reciever, :sender)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':sender', $sender);
            $stmt->bindParam(':reciever', $reciever);

            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo json_encode($row);
            }
        } elseif ($action === 'accept_request') {
            $sender = $_POST['sender'];
            $reciever = $_POST['reciever'];

            $sql = "INSERT INTO user_contacts (user_id, contact_id) VALUES (:sender, :reciever); INSERT INTO user_contacts (user_id, contact_id) VALUES (:reciever, :sender); DELETE FROM contacts_requests WHERE sender = :sender AND reciever = :reciever";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':sender', $sender);
            $stmt->bindParam(':reciever', $reciever);

            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo json_encode($row);
            }
        }
    }
?>