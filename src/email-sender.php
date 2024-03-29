<?php

declare(strict_types=1);

include 'functions.php';

// Get the interval argument from the command line
$interval = $argv[1] ?? 1;
$startUserId = $argv[2] ?? 0;
$limitUserId = $argv[3] ?? PHP_INT_MAX;

// Database connection
$db = new PDO('mysql:host=db;dbname=subscription_service;charset=utf8', 'username', 'password');

log_message('Running script');
send((int) $interval, $db, (int) $startUserId, (int) $limitUserId);