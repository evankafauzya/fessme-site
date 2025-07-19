<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];
    $token = bin2hex(random_bytes(16));

    $stmt = $conn->prepare("UPDATE users SET reset_token=? WHERE email=?");
    $stmt->bind_param("ss", $token, $email);
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo "Reset token: <b>$token</b> (use this manually in DB or create reset.php)";
    } else {
        echo "Email not found.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
   <form method="POST">
    Enter your email: <input type="email" name="email" required><br>
    <button type="submit">Send Reset Token</button>
</form> 
</body>
</html>