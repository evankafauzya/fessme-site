<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<link rel="stylesheet" href="style.css">
<div class="navbar">
    <div class="navbar-brand">
        <a href="body.php" style="text-decoration:none; color:white; font-weight:700; font-size:1.3rem; letter-spacing:1px;">
            <i class="fas fa-feather-alt" style="margin-right:7px;"></i> FessMe
        </a>
    </div>
    <div class="navbar-links">
        <a href="body.php"><i class="fas fa-home" style="margin-right:5px;"></i> Home</a>
        <a href="messages.php"><i class="fas fa-envelope-open-text" style="margin-right:5px;"></i> All Messages</a>
        <a href="faq.php"><i class="fas fa-question-circle" style="margin-right:5px;"></i> FAQ</a>
    </div>
    <div class="navbar-user">
        <?php if (isset($_SESSION["user_id"])): ?>
            <a href="profile.php" style="margin-right:10px; font-weight:500; text-decoration:none; color:inherit; cursor:pointer;">
                <i class="fas fa-user-circle" style="margin-right:5px;"></i><?= htmlspecialchars($_SESSION["username"] ?? 'User') ?>
            </a>
            <a href="logout.php"><i class="fas fa-sign-out-alt" style="margin-right:5px;"></i>Logout</a>
        <?php else: ?>
            <a href="login.php"><i class="fas fa-sign-in-alt" style="margin-right:5px;"></i>Login</a>
        <?php endif; ?>
    </div>
</div>
