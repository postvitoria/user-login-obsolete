<?php
$target_dir = "img/sendedImgs/";
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
?>