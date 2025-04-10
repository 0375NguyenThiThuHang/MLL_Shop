<?php
session_start();
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

try {
  $accessToken = $helper->getAccessToken();
} catch(Facebook\Exceptions\FacebookResponseException $e) {
  echo 'Graph returned an error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  echo 'Facebook SDK returned an error: ' . $e->getMessage();
  exit;
}

if (!isset($accessToken)) {
  if ($helper->getError()) {
    header('HTTP/1.0 401 Unauthorized');
    echo "Lỗi: " . $helper->getError() . "\n";
  } else {
    header('HTTP/1.0 400 Bad Request');
    echo 'Bad request';
  }
  exit;
}

$oAuth2Client = $fb->getOAuth2Client();
$tokenMetadata = $oAuth2Client->debugToken($accessToken);
$tokenMetadata->validateAppId($_ENV['FACEBOOK_APP_ID']);
$tokenMetadata->validateExpiration();

if (!$accessToken->isLongLived()) {
  try {
    $accessToken = $oAuth2Client->getLongLivedAccessToken($accessToken);
  } catch (Facebook\Exceptions\FacebookSDKException $e) {
    echo "<p>Lỗi: " . $e->getMessage() . "</p>";
    exit;
  }
}

$_SESSION['fb_access_token'] = (string) $accessToken;

try {
  $response = $fb->get('/me?fields=id,name,email', $accessToken);
  $userInfo = $response->getGraphUser();

    include_once('lib/database.php');
    $db = new Database();
    // Example with MySQLi (adjust your connection as needed)
    $email = $userInfo['email'];
    $name = $userInfo['name'];
    $query = "SELECT * FROM tbl_user WHERE email = '$email' LIMIT 1";
    $result = $db->select($query);

    if (!$result) {
        // If user doesn't exist, insert the new user into the database
        $insertQuery = "INSERT INTO tbl_user (name, email) VALUES ('$name', '$email')";
        $db->insert($insertQuery);
    }

    // Store user email in session

    $query = "SELECT * FROM tbl_user WHERE email = '$email' LIMIT 1";
    $result = $db->select($query);

    if ($result) {
        $userData = $result->fetch_assoc();  // Lấy dòng kết quả đầu tiên
        $_SESSION['user_id'] = $userData['userId'];  // Gán userId vào session
    }

    $_SESSION['user_login'] = true;
    $_SESSION['user'] = $userInfo->email;  // Lưu email người dùng vào session
    $_SESSION['user_name'] = $userInfo->name;

    session_write_close(); // 🔑 Ghi session trước khi redirect

    // Redirect user to homepage after successful login
    header('Location: index.php');
  exit;

} catch(Facebook\Exceptions\FacebookResponseException $e) {
  echo 'Graph error: ' . $e->getMessage();
  exit;
} catch(Facebook\Exceptions\FacebookSDKException $e) {
  echo 'SDK error: ' . $e->getMessage();
  exit;
}
