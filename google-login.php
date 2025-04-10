<?php
require_once 'vendor/autoload.php';

use Google\Client;
use Dotenv\Dotenv;

// Load biến môi trường từ .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Khởi tạo Google Client
$client = new Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
$client->addScope("email");
$client->addScope("profile");

// Tạo URL đăng nhập và chuyển hướng
$authUrl = $client->createAuthUrl();
header("Location: $authUrl");
exit();
?>
