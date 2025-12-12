<?php
$host = getenv('POSTGRES_HOST');
$port = getenv('POSTGRES_PORT');
$db   = getenv('POSTGRES_DB');
$user = getenv('POSTGRES_USER');
$pass = getenv('POSTGRES_PASSWORD');

$dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
$pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
]);
