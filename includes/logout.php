<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
session_unset();
session_destroy();
header("Location: /dtr-web-app/index.php");
exit;
