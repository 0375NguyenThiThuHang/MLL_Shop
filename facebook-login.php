<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load biến môi trường
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Khởi tạo Facebook SDK
$fb = new \Facebook\Facebook([
  'app_id' => $_ENV['FACEBOOK_APP_ID'],
  'app_secret' => $_ENV['FACEBOOK_APP_SECRET'],
  'default_graph_version' => 'v18.0',
]);

$helper = $fb->getRedirectLoginHelper();
$permissions = ['email']; // Các quyền bạn muốn

$loginUrl = $helper->getLoginUrl($_ENV['FACEBOOK_REDIRECT_URI'], $permissions);
