<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");
echo json_encode([
    "ok"   => isset($_SESSION["staff_id"]),
    "name" => $_SESSION["staff_name"] ?? null,
    "role" => $_SESSION["role"]       ?? null,
]);
