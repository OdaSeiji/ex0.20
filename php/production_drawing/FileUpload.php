<?php
// アップロード先は diereport 側の共有フォルダを使用
$updir = "../../../diereport/upload/Production_drawing";

if ($_FILES['file']['error'] > 0) {
    echo 'Error: ' . $_FILES['file']['error'];
} else {
    $filename = str_replace('#', '', $_FILES['file']['name']);
    move_uploaded_file($_FILES['file']['tmp_name'], "$updir/$filename");
}
