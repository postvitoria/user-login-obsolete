<?php
session_start();
require 'database.php';

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Obtener datos del usuario
    $userStmt = $conn->prepare('SELECT * FROM users WHERE id = :id');
    $userStmt->bindParam(':id', $user_id);
    $userStmt->execute();
    $user = $userStmt->fetch(PDO::FETCH_ASSOC);

    if ($user == null) {
        http_response_code(404);
        include('my_404.php');
        die();
    }

    // Obtener lista de amigos
    $friendsStmt = $conn->prepare('SELECT id, username, image_url, last_message FROM users INNER JOIN user_contacts ON users.id = user_contacts.contact_id WHERE user_contacts.user_id = :id');
    $friendsStmt->bindParam(':id', $user['id']);
    $friendsStmt->execute();
    $friendsList = $friendsStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    http_response_code(404);
    include('my_404.php');
    die();
}
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" type="text/css" href="css/style.css">

        <title>ph0sbit_landpage</title>
    </head>

    <body>        
        <div class="navigation">
            <ul>
                <li class="list">
                    <a href="#">
                        <span class="icon">
                            <ion-icon name="home-outline"></ion-icon>
                        </span>
                        <span class="text">Home</span>
                    </a>
                </li>
                <li class="list active">
                    <a href="#">
                        <span class="icon">
                            <ion-icon name="chatbox-outline"></ion-icon>
                        </span>
                        <span class="text">Message</span>
                    </a>
                </li>
                <li class="list">
                    <a href="#">
                        <span class="icon">
                            <ion-icon name="person-outline"></ion-icon>
                        </span>
                        <span class="text">Profile</span>
                    </a>
                </li>
                <li class="list">
                    <a href="#">
                        <span class="icon">
                            <ion-icon name="basket-outline"></ion-icon>
                        </span>
                        <span class="text">Shop</span>
                    </a>
                </li>
                <li class="list">
                    <a href="#">
                        <span class="icon">
                            <ion-icon name="settings-outline"></ion-icon>
                        </span>
                        <span class="text">Settings</span>
                    </a>
                </li>
                <div class="indicator">
            </ul>
        </div>

        <script>            
            const list = document.querySelectorAll('.list');

            var recieverId = null;
            var user_id = "<?= $user["id"] ?>";
            user_id = parseInt(user_id);
            
            var userImg = "<?= $user["image_url"] ?>";
            var userName = "<?= $user["username"] ?>";

            var selectedContact = null;
            var contactNotify = null;
            var audio = new Audio('default.mp3');

            setInterval(checkMessages, 500);

            function delay(ms) {
                return new Promise(resolve => setTimeout(resolve, ms));
            }

            function maximizeWindow(object) {
                var element = object.firstElementChild.lastElementChild;
                
                if (element.textContent == "Message") {
                    const messages = document.querySelector(".messages-container");
                    messages.style.transition = 'transform 2s, opacity 0.5s';
                    messages.style.opacity = '1';

                    const contacts = document.querySelector('.social-container');
                    contacts.style.transition = 'transform 2s, opacity 0.5s';
                    contacts.style.opacity = '1';
    
                } else if (element.textContent == "Shop") { 
                    const shop = document.querySelector(".shop-container");
                    shop.style.transition = 'transform 2s, opacity 0.5s';
                    shop.style.opacity = '1';
                    shop.style.display = 'grid';
                }
            }

            function minimizeWindow(object) {
                var element = object.firstElementChild.lastElementChild;
                
                if (element.textContent != "Message") {
                    const messages = document.querySelector('.messages-container');
                    messages.style.transition = 'transform 2s, opacity 0.5s';
                    messages.style.opacity = '0';

                    const contacts = document.querySelector('.social-container');
                    contacts.style.transition = 'transform 2s, opacity 0.5s';
                    contacts.style.opacity = '0';
                }

                if (element.textContent != "Shop") {
                    const shop = document.querySelector('.shop-container');
                    shop.style.transition = 'transform 2s, opacity 0.5s';
                    shop.style.opacity = '0';
                    shop.style.display = 'none';
                }
            }

            function activeLink() {
                const MsgNotify = list[1].querySelectorAll('.message-notify');

                list.forEach((item) => item.classList.remove('active'));
                this.classList.add('active');

                minimizeWindow(this);
                maximizeWindow(this);

                if (MsgNotify.length > 0 && list[1].classList.contains("active")) {
                    console.log("He hecho click sobre un item que tiene mensaje");
                    
                    MsgNotify.forEach(notify => {
                        notify.remove();
                    });
                }
            }

            list.forEach((item) =>
            item.addEventListener('click', activeLink));

            function loadMessages(sender_id, reciever_id) {
                var chatBox = document.querySelector('.chat-container');
                var contactImg = selectedContact.querySelector("img");
                var contactUsername = selectedContact.querySelector(".contact-name").textContent;

                console.log("Messages loaded");

                $.post('backend.php', { action: 'load', sender: sender_id, reciever: reciever_id}, function(response) {
                    var arregloJSON = '[' + response.replace(/}{/g, '},{') + ']';
                    var objetoJavaScript = JSON.parse(arregloJSON);
                    
                    objetoJavaScript.forEach(message => {
                        if (message != null) {
                            if (message["read_status"] == 0) {
                                $.post('backend.php', { action: 'read', id:message["id"]});
                            }

                            var messageContainer = document.createElement("div");
                            messageContainer.classList.add('message-container');
                            
                            var userIcon = document.createElement("img");
                            userIcon.classList.add("message-user-icon");
                            userIcon.src = contactImg.src;

                            var messageName = document.createElement("span");
                            messageName.classList.add("message-user-name");
                            messageName.textContent = contactUsername;

                            if (message["sender"] === user_id) {
                                //messageContainer.classList.add('yours');
                                userIcon.src = userImg;
                                messageName.textContent = userName;
                            }

                            var newmessage = document.createElement("div");
                            newmessage.classList.add('message');

                            var parag = document.createElement("p");
                            parag.textContent = message["content"];
                            
                            newmessage.appendChild(messageName);
                            newmessage.appendChild(parag);
                            
                            if (message["type"] == "image") {
                                var image = document.createElement("img");
                                image.classList.add("message-img");

                                image.src = "resources/img/sendedImgs/" + message["filename"];
                                message["content"] = "Image 📷";
                                newmessage.appendChild(image);
                                newmessage.removeChild(parag);

                                image.addEventListener('click', function() {
                                    window.open(image.src);
                                });
                            }

                            var span = document.createElement("span");
                            span.classList.add("timestamp");
                            span.textContent = message["timestamp"];
                            newmessage.appendChild(span);

                            messageContainer.appendChild(newmessage);
                            messageContainer.appendChild(userIcon);

                            chatBox.appendChild(messageContainer);
                            chatBox.scrollTop = chatBox.scrollHeight;

                            if (message["content"][0] == "*" && message["content"][message["content"].length - 1] == "*") {
                                parag.textContent = parag.textContent.replace(/\*/g, '');
                                parag.style.fontWeight = 'bold';
                            }
                        }
                    });
                });
            }

            function checkMessages() {                
                console.log("Messages checked");
                var chatBox = document.querySelector('.chat-container');
                
                $.post('backend.php', { action: 'check', sender: user_id}, function(response) {
                    var arregloJSON = '[' + response.replace(/}{/g, '},{') + ']';
                    var objetoJavaScript = JSON.parse(arregloJSON);

                    objetoJavaScript.forEach(message => {
                        if (message["sender"] != user_id && message["sender"] == recieverId) { //condicion rota
                            var actualContact = document.getElementById(recieverId.toString());
                            var lastMessage = actualContact.querySelector(".contact-lastmessage");

                            var messageContainer = document.createElement("div");
                            messageContainer.classList.add('message-container');
                            
                            var userIcon = document.createElement("img");
                            userIcon.classList.add("message-user-icon");
                            userIcon.src = actualContact.querySelector("img").src;

                            var messageName = document.createElement("span");
                            messageName.classList.add("message-user-name");
                            messageName.textContent = actualContact.querySelector(".contact-name").textContent;

                            if (message["sender"] === user_id) {
                                //messageContainer.classList.add('yours');
                                userIcon.src = userImg;
                            }

                            var newmessage = document.createElement("div");
                            newmessage.classList.add('message');

                            var parag = document.createElement("p");
                            parag.textContent = message["content"];
                            
                            newmessage.appendChild(messageName);
                            newmessage.appendChild(parag);

                            if (message["type"] == "image") {
                                var image = document.createElement("img");
                                image.classList.add("message-img");

                                image.src = "resources/img/sendedImgs/" + message["filename"];
                                message["content"] = "Image 📷";
                                newmessage.appendChild(image);
                                newmessage.removeChild(parag);

                                image.addEventListener('click', function() {
                                    window.open(image.src);                                
                                });

                                delay(500).then(() => {
                                    chatBox.scrollTop = chatBox.scrollHeight;
                                });
                            }

                            var span = document.createElement("span");
                            span.classList.add("timestamp");
                            span.textContent = message["timestamp"];

                            newmessage.appendChild(span);
                            messageContainer.appendChild(newmessage);

                            chatBox.appendChild(messageContainer);
                            messageContainer.appendChild(userIcon);

                            chatBox.scrollTop = chatBox.scrollHeight;

                            lastMessage.textContent = message["content"];

                            if (message["content"][0] == "*" && message["content"][message["content"].length - 1] == "*") {
                                parag.textContent = parag.textContent.replace(/\*/g, '');
                                parag.style.fontWeight = 'bold';

                                lastMessage.textContent = lastMessage.textContent.replace(/\*/g, '');
                                lastMessage.style.fontWeight = 'bold';
                            } else {
                                lastMessage.style.fontWeight = 'normal';
                            }

                            $.post('backend.php', { action: 'read', id:message["id"]});
                            $.post('backend.php', { action: 'last_message', message: message["content"], sender: message["sender"], reciever: message["reciever"]});

                        } else if (message["sender"] != user_id && message["sender"] != recieverId) {
                            var contactSender = document.getElementById(message["sender"].toString());
                            var menuNotify = document.querySelector(".message-notify");
                            contactNotify = contactSender.querySelector(".contact-message-notify");

                            if (contactNotify === null) {
                                var lastMessage = contactSender.querySelector(".contact-lastmessage");
                                lastMessage.textContent = message["content"];

                                if (message["content"][0] == "*" && message["content"][message["content"].length - 1] == "*") {
                                    lastMessage.textContent = lastMessage.textContent.replace(/\*/g, '');
                                    lastMessage.style.fontWeight = 'bold';
                                } else {
                                    lastMessage.style.fontWeight = 'normal';
                                }

                                contactNotify = document.createElement("div");
                                contactNotify.classList.add("contact-message-notify");
                                contactSender.appendChild(contactNotify);

                                console.log("Añadida notificación al contacto");
                                audio.play()
                                
                                console.log("te ha llegado un mensaje mientras no estás en el chat");
                                $.post('backend.php', { action: 'last_message', message: message["content"], sender: message["sender"], reciever: message["reciever"]});
                            }

                            if (menuNotify === null) {
                                const list = document.querySelectorAll('.list');

                                if (list[1]["className"] !== "list active") {
                                    const msgIcon = list[1].querySelectorAll('.icon');

                                    console.log("Añadida notificación al menú");

                                    menuNotify = document.createElement("div");
                                    menuNotify.classList.add("message-notify");

                                    msgIcon[0].appendChild(menuNotify);

                                    console.log("te ha llegado un mensaje mientras no estás en el chat");
                                    $.post('backend.php', { action: 'last_message', message: message["content"], sender: message["sender"], reciever: message["reciever"]});
                                }
                            }
                        }
                    });

                    var lastElement = objetoJavaScript.pop();
                    if ((objetoJavaScript.length > 0) && (lastElement["sender"] != user_id && lastElement["sender"] != recieverId)) {
                        
                        var contactSender = document.getElementById(lastElement["sender"].toString());
                        var lastMessage = contactSender.querySelector(".contact-lastmessage");

                        if (lastElement["content"] !== lastMessage.textContent) {
                            $.post('backend.php', { action: 'last_message', message: lastElement["content"], sender: lastElement["sender"], reciever: lastElement["reciever"]});
                            lastMessage.textContent = lastElement["content"];
                            audio.play()
                        }
                    }
                });
            }

            function openFileSelector() {
                const input = document.createElement('input');
                input.type = 'file';
                input.accept = 'image/*';

                input.addEventListener('change', function(event) {
                    const file = event.target.files[0];
                    // Limpia el valor del input para permitir seleccionar el mismo archivo nuevamente
                    event.target.value = '';

                    sendMessage("image", file);
                });

                // Dispara el evento de clic en el nuevo input para abrir el selector de archivos
                input.click();
            }

            function generarRandomString(longitud) {
                const caracteresValidos = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                let randomString = '';

                for (let i = 0; i < longitud; i++) {
                    const indiceAleatorio = Math.floor(Math.random() * caracteresValidos.length);
                    randomString += caracteresValidos.charAt(indiceAleatorio);
                }

                return randomString;
            }
        </script>

        <div class="social-container">
            <div class="self-profile">
                <img src="<?= $user["image_url"] ?>">
                <ion-icon name="person-add-outline" onclick="openFriendsList()"></ion-icon>
                <!--<ion-icon name="ellipsis-vertical-outline"></ion-icon>-->

                <div class="search-bar">
                    <input type="text" name="text" id="search-input" class="search-input" placeholder="Search contact by name">
                </div>
            </div>
            
            <div class="contacts-container">
                <!--<div class="contact">
                    <img src="https://i.pinimg.com/originals/c7/4e/2c/c74e2c5de04a34ab740297829ce545d5.jpg">
                    <div class="message-notify"></div>
                    <span class="contact-name">Kawasaki</span>
                    <span class="contact-lastmessage">Hermano un 2pa?</span>
                    <ion-icon name="ellipsis-vertical-outline"></ion-icon>
                </div>-->
                <!--<div class="contact">
                    <img src="https://i.pinimg.com/originals/c7/4e/2c/c74e2c5de04a34ab740297829ce545d5.jpg">
                    <span class="contact-name">Kawasaki</span>
                    <span class="contact-lastmessage">Hermano un 2pa?</span>
                    <ion-icon name="ellipsis-vertical-outline"></ion-icon>
                </div>
                <div class="contact">
                    <img src="https://styles.redditmedia.com/t5_h1zup/styles/communityIcon_k27sd824s62c1.png">
                    <span class="contact-name">Estriper</span>
                    <span class="contact-lastmessage">Pasa la merca compai</span>
                    <ion-icon name="ellipsis-vertical-outline"></ion-icon>
                </div>-->
            </div>
        </div>
        
        <script>
            document.getElementById('search-input').addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const contacts = document.querySelectorAll('.contact');

                contacts.forEach(contact => {
                    const scontactName = contact.querySelector('.contact-name').textContent.toLowerCase();
                    if (scontactName.includes(searchTerm)) {
                        contact.style.display = 'block';
                    } else {
                        contact.style.display = 'none';
                    }
                });
            });
        </script>

        <div class="messages-container">
            <!--<div class ="chat-info-container">
                <img class="chat-img" src="https://styles.redditmedia.com/t5_h1zup/styles/communityIcon_k27sd824s62c1.png">
                <span class="chat-name">Estriper</span>
                <ion-icon name="search-outline"></ion-icon>
            </div>-->

            <ion-icon name="chatbox-ellipses-outline" class="chatbox-ellipses-outline"></ion-icon>
            <span class="start-chat"><b>Start</b> chatting with your <br>contacts by <b>clicking</b> on their <b>chat</b>.</span>

            <!--<div class="chat-container">
                <div class="message-container yours"><div class="message"><p>siiç</p><span class="timestamp">0:45 AM<ion-icon class="checkmark-done-outline md hydrated" name="checkmark-done-outline" role="img" aria-label="checkmark done outline"></ion-icon></span></div></div>
            </div>-->

            <!--<div class="inputs-container">
                <ion-icon name="happy-outline" class="happy-outline"></ion-icon>
                <ion-icon name="add-outline" class="add-outline" onclick ="openFileSelector()"></ion-icon>
                <input type="text" id="write-input" class="message-input" placeholder="Write a message">
                <ion-icon name="send-outline" class="send-outline" onclick="sendMessage('text', null)"></ion-icon>
            <div>-->
        </div>

        <script>
            var friends = <?php echo json_encode($friendsList); ?>;
            var container = document.querySelector('.contacts-container');

            friends.forEach(friend => {
                var friendContainer = document.createElement("div");
                friendContainer.classList.add('contact');
                friendContainer.setAttribute("id", friend["id"]);

                var friendImg = document.createElement("img");
                friendImg.src = friend["image_url"];
                
                var friendName = document.createElement("span");
                friendName.classList.add('contact-name');
                friendName.textContent = friend["username"];

                var lastMessage = document.createElement("p");
                lastMessage.classList.add('contact-lastmessage');
                lastMessage.textContent = friend["last_message"];

                if (friend["last_message"][0] == "*" && friend["last_message"][friend["last_message"].length - 1] == "*") {
                    lastMessage.textContent = lastMessage.textContent.replace(/\*/g, '');
                    lastMessage.style.fontWeight = 'bold';
                } else {
                    lastMessage.style.fontWeight = 'normal';
                }

                var ionicon = document.createElement("ion-icon");
                ionicon.setAttribute("name", "ellipsis-vertical-outline");

                friendContainer.appendChild(ionicon);
                friendContainer.appendChild(lastMessage);
                friendContainer.appendChild(friendName);
                friendContainer.appendChild(friendImg);
                container.appendChild(friendContainer);
            });

            const contacts = document.querySelectorAll('.contact');
            var aux_contact_id = "";

            contacts.forEach((contact) => contact.addEventListener('click', function() {
                var contactName = contact.querySelector(".contact-name");
                var contactImg = contact.querySelector("img");

                var contactId = contact.getAttribute('id');

                var contactMsgNotify = contact.querySelectorAll(".contact-message-notify");
                
                if (contactId == recieverId) {
                    return;
                }

                //var contactChatName = document.querySelector(".chat-name");
                //var contactChatImg = document.querySelector(".chat-img");
                var messagesContainer = document.querySelector(".messages-container");

                var messageInput = document.getElementById("write-input");
                
                var chatinfoContainer = document.querySelector(".chat-info-container");
                var chatContainer = document.querySelector(".chat-container");
                var inputsContainer = document.querySelector(".inputs-container");
                //var inputContainer = document.querySelector(".inputs-container");

                var spanStartChat = document.querySelector(".start-chat");
                var chatboxIcon  = document.querySelector(".chatbox-ellipses-outline");

                selectedContact = contact;
                    
                if (messageInput !== null) {
                    messageInput.remove();
                }

                if (chatinfoContainer !== null && recieverId !== null) {
                    chatinfoContainer.remove();
                }

                if (chatContainer !== null) {
                    chatContainer.remove();
                }

                if (inputsContainer !== null) {
                    inputsContainer.remove();
                }

                var chatInfoContainer = document.createElement("div");
                chatInfoContainer.classList.add("chat-info-container");

                var inputsContainer = document.createElement("div");
                inputsContainer.classList.add("inputs-container");

                messagesContainer.appendChild(chatInfoContainer);
                messagesContainer.appendChild(inputsContainer);

                var chatImg = document.createElement("img");
                chatImg.classList.add("chat-img");
                chatImg.src = contactImg.src;

                var chatName = document.createElement("span");
                chatName.classList.add("chat-name");
                chatName.textContent = contactName.textContent;

                var chatIcon = document.createElement("ion-icon");
                chatIcon.setAttribute("name", "search-outline");

                chatInfoContainer.appendChild(chatImg);
                chatInfoContainer.appendChild(chatName);
                chatInfoContainer.appendChild(chatIcon);

                var happyOutline = document.createElement("ion-icon");
                happyOutline.classList.add("happy-outline");
                happyOutline.setAttribute("name", "happy-outline");

                var addOutline = document.createElement("ion-icon");
                addOutline.classList.add("add-outline");
                addOutline.setAttribute("name", "add-outline");
                addOutline.setAttribute('onclick','openFileSelector();');

                var chatInput = document.createElement("input");
                chatInput.classList.add("message-input");
                chatInput.setAttribute("type", "text");
                chatInput.setAttribute("id", "write-input");
                chatInput.setAttribute("placeholder", "Write a message");

                var sendOutline = document.createElement("ion-icon");
                sendOutline.classList.add("send-outline");
                sendOutline.setAttribute("name", "send-outline");
                sendOutline.setAttribute('onclick',"sendMessage('text', null);");

                inputsContainer.appendChild(happyOutline);
                inputsContainer.appendChild(addOutline);
                inputsContainer.appendChild(chatInput);
                inputsContainer.appendChild(sendOutline);

                enableWriting();
                //chatinfoContainer.style.opacity = 1;
                inputsContainer.style.opacity = 1;

                //estos son los textos de bienvenida

                if (spanStartChat != null) {
                    spanStartChat.remove();
                    chatboxIcon.remove();
                }

                //messageInput.value = "";

                recieverId = parseInt(contactId);

                if (contactMsgNotify != null) {
                    contactMsgNotify.forEach( notify => {
                        notify.remove();
                    });
                }

                var contactsRequestsContainer = document.querySelector(".contacts-requests-container");
                var chatInfoContainer = document.querySelector(".chat-info-container");
                var contactRequests = document.getElementsByClassName("contact-request");
                
                if (contactsRequestsContainer !== null) {
                    contactsRequestsContainer.remove();
                    chatInfoContainer.remove();
                    
                    while(contactRequests.length > 0){
                        contactRequests[0].parentNode.removeChild(contactRequests[0]);
                    }
                }

                if (aux_contact_id != recieverId) {
                    //Aqui hay que crear el chat
                    var chatContainer = document.createElement("div");
                    chatContainer.classList.add("chat-container");
                    messagesContainer.appendChild(chatContainer);
                    

                    //Aqui hay que eliminar los anteriores mensajes y recoger los del usuario pertinente (sql)
                    var actualMessages = document.getElementsByClassName('message-container');

                    while(actualMessages.length > 0){
                        actualMessages[0].parentNode.removeChild(actualMessages[0]);
                    }

                    loadMessages(user_id, recieverId);
                    chatContainer.scrollTop = chatContainer.scrollHeight;                    
                }

                aux_contact_id = recieverId;
                contactNotify = null;
            }));

            function openFriendsList() {
                var contactsRequestsContainer = document.querySelector(".contacts-requests-container");

                if (contactsRequestsContainer !== null) {
                    return;
                }

                var chatInfoContainer = document.querySelector(".chat-info-container");
                var messagesContainer = document.querySelector(".messages-container");
                var inputsContainer = document.querySelector(".inputs-container");
                var chatContainer = document.querySelector(".chat-container");

                var chatboxIcon  = document.querySelector(".chatbox-ellipses-outline");
                var spanStartChat = document.querySelector(".start-chat");
                

                if (chatInfoContainer != null) {
                    chatInfoContainer.remove();
                    inputsContainer.remove();
                    chatContainer.remove();
                } else {
                    chatboxIcon.remove();
                    spanStartChat.remove();
                }


                var chatInfoContainer = document.createElement("div");
                chatInfoContainer.classList.add("chat-info-container");
                messagesContainer.appendChild(chatInfoContainer);
                
                var contactsInput = document.createElement("input");
                contactsInput.classList.add("contacts-input");
                contactsInput.setAttribute("type", "text");
                contactsInput.setAttribute("id", "contacts-input");
                contactsInput.setAttribute("placeholder", "Search contacts to add");
                chatInfoContainer.appendChild(contactsInput);

                var addContanctBtn = document.createElement("div");
                addContanctBtn.classList.add("add-contact-btn");
                addContanctBtn.textContent = "Add contact";
                chatInfoContainer.appendChild(addContanctBtn);

                var requestsContainer = document.createElement("div");
                requestsContainer.classList.add("contacts-requests-container");
                messagesContainer.appendChild(requestsContainer);


                $.post('backend.php', {action: 'load_contacts_requests', sender: user_id}, function(response) {
                    var arregloJSON = '[' + response.replace(/}{/g, '},{') + ']';
                    var requestsList = JSON.parse(arregloJSON);

                    requestsList.forEach(request => {
                        var contactRequest = document.createElement("div");
                        contactRequest.classList.add("contact-request");
                        
                        var requestImg = document.createElement("img");
                        requestImg.src = request["image_url"];
                        contactRequest.appendChild(requestImg);

                        var requestName = document.createElement("p");
                        requestName.textContent = request["username"];
                        contactRequest.appendChild(requestName);

                        var requestCheck = document.createElement("ion-icon");
                        requestCheck.setAttribute("name", "checkmark-circle-outline");
                        requestCheck.classList.add("checkmark-circle-outline");
                        contactRequest.appendChild(requestCheck);

                        var requestCross = document.createElement("ion-icon");
                        requestCross.setAttribute("name", "close-circle-outline");
                        requestCross.classList.add("close-circle-outline");
                        contactRequest.appendChild(requestCross);

                        requestsContainer.appendChild(contactRequest);

                        requestCheck.addEventListener('click', function() {
                            $.post('backend.php', {action: 'accept_request', sender: user_id, reciever: request["id"]});
                            contactRequest.remove();
                        });

                        requestCross.addEventListener('click', function() {
                            $.post('backend.php', {action: 'deny_request', sender: user_id, reciever: request["id"]});
                            contactRequest.remove();
                        });
                    });
                });

                document.querySelector('.add-contact-btn').addEventListener('click', function() {
                    const searchContactInput = document.getElementById('contacts-input');
                    const searchTerm = searchContactInput.value;

                    console.log(searchTerm);
                    console.log(user_id);

                    $.post('backend.php', {action: 'exist_contact', sender: user_id, username: searchTerm}, function(response) {
                        if (response === "") {
                            console.log("Este usuario no existe o lo tienes agregado");
                            return;
                        }

                        var arregloJSON = '[' + response.replace(/}{/g, '},{') + ']';
                        var response = JSON.parse(arregloJSON);

                        $.post('backend.php', {action: 'send_request', sender: user_id, reciever: response[0]["id"]});
                    });
                });

                recieverId = null;
                aux_contact_id = null;
            }
            document.addEventListener('paste', function (event) {
                // Obtén el contenido del portapapeles
                const clipboardData = (event.clipboardData || window.clipboardData);
                if (!clipboardData) {
                    return;
                }

                // Verifica si hay archivos en el portapapeles
                const items = clipboardData.items;
                if (!items) {
                    return;
                }

                // Busca un tipo de archivo que sea una imagen
                for (const item of items) {
                    if (item.type.indexOf('image') !== -1) {
                        // El elemento es una imagen
                        const file = item.getAsFile();

                        // Puedes realizar acciones adicionales con la imagen, como subirla al servidor, etc.
                        sendMessage("image", file);
                        
                        // Evita que el navegador maneje el pegado por defecto
                        event.preventDefault();
                        break;  // Detén la búsqueda después de encontrar la primera imagen
                    }
                }
            });

            function enableWriting() {
                var messageInput = document.getElementById("write-input");
                var lastMessage = selectedContact.querySelector(".contact-lastmessage");
                
                function sendMessage(type, file) {
                    const currentDate = new Date();

                    var chatBox = document.querySelector('.chat-container');
                    var message = messageInput.value;                    
                    var hora = currentDate.getHours();
                    var periodo = (hora < 12) ? "AM" : "PM";
                    
                    let day = currentDate.getDate();
                    let month = currentDate.getMonth() + 1; // Los meses van de 0 a 11, por eso se suma 1
                    let year = currentDate.getFullYear();

                    // Formatear la fecha
                    if (day < 10) {
                        day = '0' + day;
                    }

                    if (month < 10) {
                        month = '0' + month;
                    }

                    var timestamp = day + "/" + month + "/" + year + " " + currentDate.getHours() + ":" + currentDate.getMinutes() + " " + periodo;
                    var milisecods = currentDate.getMilliseconds()

                    if (type == "text") {
                        if (message === "") {
                            return
                        }

                        var messageContainer = document.createElement("div");
                        messageContainer.classList.add('message-container');
                        //messageContainer.classList.add('yours');
                        
                        var userIcon = document.createElement("img");
                        userIcon.classList.add("message-user-icon");
                        userIcon.src = userImg;

                        var messageName = document.createElement("span");
                        messageName.classList.add("message-user-name");
                        messageName.textContent = userName;

                        var newmessage = document.createElement("div");
                        newmessage.classList.add('message');

                        var parag = document.createElement("p");
                        parag.textContent = message;
                        
                        var span = document.createElement("span");
                        span.classList.add("timestamp");
                        span.textContent = timestamp;

                        newmessage.appendChild(messageName);
                        newmessage.appendChild(parag);
                        newmessage.appendChild(span);
                        
                        messageContainer.appendChild(newmessage);
                        messageContainer.appendChild(userIcon);

                        chatBox.appendChild(messageContainer);
                        chatBox.scrollTop = chatBox.scrollHeight;
                    
                        messageInput.value = "";
                        lastMessage.textContent = message;
                        
                        if (message[0] == "*" && message[message.length - 1] == "*") {
                            parag.textContent = parag.textContent.replace(/\*/g, '');
                            parag.style.fontWeight = 'bold';

                            lastMessage.textContent = lastMessage.textContent.replace(/\*/g, '');
                            lastMessage.style.fontWeight = 'bold';
                        } else {
                            lastMessage.style.fontWeight = 'normal';
                        }

                        // Puedes enviar el mensaje al servidor o realizar otras acciones aquí
                        $.post('backend.php', { action: 'send', sender: user_id, reciever: recieverId, content: message, time: timestamp, type: "text"});
                        $.post('backend.php', { action: 'last_message', message: message, sender: user_id, reciever: recieverId});
                    } else {
                        const fileName = generarRandomString(20);
                        const formData = new FormData();
                        
                        var fileExt = file["name"].substr(file["name"].length - 4);

                        formData.append('image', file);
                        formData.append('imageName', fileName + fileExt);
                        formData.append('action', 'send_image');

                        // Aquí puedes manejar el archivo seleccionado (por ejemplo, enviar a un servidor)
                        const xhr = new XMLHttpRequest();
                        xhr.open('POST', 'backend.php', true);
                        xhr.send(formData);

                        setTimeout(() => {
                            var messageContainer = document.createElement("div");
                            messageContainer.classList.add('message-container');
                            //messageContainer.classList.add('yours');
                            
                            var newmessage = document.createElement("div");
                            newmessage.classList.add('message');
                            
                            var userIcon = document.createElement("img");
                            userIcon.classList.add("message-user-icon");
                            userIcon.src = userImg;

                            var messageName = document.createElement("span");
                            messageName.classList.add("message-user-name");
                            messageName.textContent = userName;

                            var span = document.createElement("span");
                            span.classList.add("timestamp");
                            span.textContent = timestamp;

                            var image = document.createElement("img");
                            image.classList.add("message-img");
                            image.src = "resources/img/sendedImgs/" + fileName + fileExt;

                            newmessage.append(image);
                            newmessage.appendChild(span);

                            messageContainer.appendChild(messageName);
                            messageContainer.appendChild(newmessage);
                            messageContainer.appendChild(userIcon);

                            chatBox.appendChild(messageContainer);

                            delay(500).then(() => {
                                chatBox.scrollTop = chatBox.scrollHeight;
                            });

                            lastMessage.textContent = "Image 📷";
                            $.post('backend.php', { action: 'send', sender: user_id, reciever: recieverId, content: "Image 📷", time: timestamp, filename: fileName + fileExt, type: "image"});
                            $.post('backend.php', { action: 'last_message', message: message, sender: user_id, reciever: recieverId});
                        }, 500);
                    }
                }

                // Manejar el envío de mensajes cuando se presiona Enter
                messageInput.addEventListener("keyup", function (event) {
                    if (event.key === "Enter") {
                        event.preventDefault();
                        sendMessage("text", null);
                    }
                });

                // Esta función está disponible globalmente para que pueda ser llamada desde el botón
                window.sendMessage = sendMessage;
            }
        </script>
        
        <!-- <div class="settings-container">
            <ul>
                <li>
                    My account
                </li>
                <li>
                    Privacity & security
                </li>
                <li>
                    Text & images
                </li>
                <li>
                    Connections
                </li>
                <li>
                    Notifications
                </li>
                <li>
                    Purchases
                </li>
                <li>
                    Advanced settings
                </li>
            </ul>
        </div> -->

        <div class="shop-container">
            <div class="shop-item" data-tilt>
                <h2 class="item-name">Boost</h2>
                <img src="resources/img/rocket.png" class="product">
                <a href="#" class="buy">Buy Now</a>
            </div>
            <div class="shop-item" data-tilt>
                <h2 class="item-name">Premium</h2>
                <img src="resources/img/star.png" class="product"> 
                <a href="#" class="buy">Buy Now</a>
            </div>
            <div class="shop-item" data-tilt>
                <h2 class="item-name">Verified</h2>
                <img src="resources/img/verified.png" class="product">
                <a href="#" class="buy">Buy Now</a>
            </div>
        </div>

        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js"></script>
        <script type="module" src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.esm.js"></script>
        <script nomodule src="https://unpkg.com/ionicons@5.5.2/dist/ionicons/ionicons.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.1/vanilla-tilt.min.js" integrity="sha512-wC/cunGGDjXSl9OHUH0RuqSyW4YNLlsPwhcLxwWW1CR4OeC2E1xpcdZz2DeQkEmums41laI+eGMw95IJ15SS3g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    </body>
</html>