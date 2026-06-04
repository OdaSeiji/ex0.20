<?php
session_name('ex021');
session_start();
session_destroy();
header("Content-Type: application/json; charset=UTF-8");
echo json_encode(["status" => "ok"]);
