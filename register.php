<?php
include 'db.php';
include 'navbar.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $phone = trim($_POST["phone"]);
    $password = $_POST["password"];

    // Email domain check
    if (!preg_match("/^[a-zA-Z0-9._%+-]+@president\.ac\.id$/", $email)) {
        $error = "You must use a president.ac.id email address.";
    } elseif (!preg_match("/^[0-9]{10,15}$/", $phone)) {
        $error = "Phone number must be 10–15 digits.";
    } elseif (!preg_match("/^[a-zA-Z0-9._%+-]{8,}$/", $password)) {
        $error = "Password must be minimum 8 alphabets or digits.";
    } else {
        // Check for existing user with same email or username
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR username = ? LIMIT 1");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $error = "A user with this email or username already exists.";
        } else {
            $stmt->close();
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (username, email, phone, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $username, $email, $phone, $hashed_password);
            if ($stmt->execute()) {
                $success = "Registered successfully. <a href='login.php'>Login here</a>";
            } else {
                $error = "Error: " . $stmt->error;
            }
        }
    }
}
?>

<link rel="stylesheet" href="css/style.css">
<div class="container">
    <h2>Register</h2>

    <?php if (!empty($error)): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php elseif (!empty($success)): ?>
        <div class="success-message"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="username" placeholder="Username" required><br>
        <input type="email" name="email" placeholder="Email (@president.ac.id)" required><br>
        <input type="text" name="phone" placeholder="Phone Number" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Register</button>
    </form>
    <a href="login.php">Already have an account?</a>
</div>

