<?php
session_start();
require_once 'config.php';

// DEBUG: show environment variables
var_dump($host, $db, $user, $pass);

// DEBUG: show posted data
var_dump($_POST);
exit;
