<?php
session_start();
    require_once 'vendor/autoload.php';

    use Google\Client;
    use Google\Service\Oauth2;
    use Dotenv\Dotenv;

    // Load biến môi trường từ file .env
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    // Khởi tạo Google Client
    $client = new Client();
    $client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
    $client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
    $client->setRedirectUri($_ENV['GOOGLE_REDIRECT_URI']);
    $client->addScope("email");
    $client->addScope("profile");

if (isset($_GET['code'])) {
    try {
        // Fetch the access token
        $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
        
        var_dump($token);
        
        if (isset($token['error'])) {
            throw new Exception('Error fetching token: ' . $token['error']);
        }

        // Set the access token for the client
        $client->setAccessToken($token);
        
        // Get user information
        $oauth = new Oauth2($client);
        $userInfo = $oauth->userinfo->get();

        
        // Example with MySQLi (adjust your connection as needed)
        include_once('lib/database.php');
        $db = new Database();

        // Check if user exists in the database
        $email = $userInfo->email;
        $name = $userInfo->name;
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
        exit();
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
} else {
    echo "Could not retrieve authentication code from Google!";
}
?>
