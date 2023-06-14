<?php

declare(strict_types=1);

// Include the file containing function definitions
include 'functions.php';

$interval = $argv[1] ?? 1;
$countParts = $argv[2] ?? 1;

// Database connection
$db = new PDO('mysql:host=db;dbname=subscription_service;charset=utf8', 'username', 'password');

run_senders($db, $countParts, $interval);

