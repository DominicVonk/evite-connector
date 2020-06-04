<?php
require('vendor/autoload.php');
require('db.php');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db = new Database('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT').';dbname='.getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASS'));

if (!isset($_GET['code']) || $_GET['code'] !== getenv('SELF_CODE')) {
    http_response_code(403);
    die();
}