<?php
session_start();
include 'db.php';
include 'navbar.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
$user_id = $_SESSION['user_id'];

// Get user info
$user = $conn->query("SELECT username, email, registration_date FROM users WHERE id = $user_id")->fetch_assoc();

// Get user's posts
$posts = $conn->query("SELECT id, content, created_at FROM messages WHERE user_id = $user_id ORDER BY created_at DESC");

// Get user's comments
$comments = $conn->query("SELECT id, content, created_at, message_id FROM comments WHERE user_id = $user_id ORDER BY created_at DESC");

// Get user's likes on posts
$post_likes = $conn->query("SELECT l.message_id, m.content, m.created_at FROM likes l JOIN messages m ON l.message_id = m.id WHERE l.user_id = $user_id ORDER BY l.id DESC");

// Get user's likes on comments
$comment_likes = $conn->query("SELECT cl.comment_id, c.content, c.created_at FROM comment_likes cl JOIN comments c ON cl.comment_id = c.id WHERE cl.user_id = $user_id ORDER BY cl.id DESC");

// Handle delete post from profile
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_post_id"])) {
    $del_id = intval($_POST["delete_post_id"]);
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $del_id, $user_id);
    $stmt->execute();
    $stmt->close();
    echo "<div class='success-message'><i class='fa-solid fa-trash'></i> Post deleted!</div>";
}

// Handle delete comment from profile
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_comment_id"])) {
    $del_id = intval($_POST["delete_comment_id"]);
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->bind_param("i", $del_id);
    $stmt->execute();
    $stmt->close();
    echo "<div class='success-message'><i class='fa-solid fa-trash'></i> Comment deleted!</div>";
}
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="container">
    <h2>My Profile</h2>
    <div class="profile-info" style="margin-bottom:2rem;">
        <strong>Username:</strong> <?= htmlspecialchars($user['username']) ?><br>
        <strong>Email:</strong> <?= htmlspecialchars($user['email']) ?><br>
        <strong>Joined:</strong> <?= date('F j, Y', strtotime($user['registration_date'])) ?><br>
        <?php
            // Try to get join date from messages
            $join_date = null;
            $msg = $conn->query("SELECT created_at FROM messages WHERE user_id = $user_id ORDER BY created_at ASC LIMIT 1");
            if ($msg && $msg->num_rows > 0) {
                $join_date = $msg->fetch_assoc()['created_at'];
            }
        ?><br>
    </div>
    <h3>My Posts</h3>
    <div class="profile-cards">
    <?php while ($p = $posts->fetch_assoc()): ?>
        <div class="profile-card" style="background:#f7f8fc; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.07); padding:16px; margin-bottom:14px;">
            <div style="font-size:0.95em; color:#555; margin-bottom:6px;"><i class="fa-regular fa-clock"></i> <?= date('M d, Y H:i', strtotime($p['created_at'])) ?></div>
            <div style="font-size:1.08em; color:#222;"><?= htmlspecialchars($p['content']) ?></div>
            <form method="POST" style="margin-top:8px; text-align:right;">
                <input type="hidden" name="delete_post_id" value="<?= $p['id'] ?>">
                <button type="submit" onclick="return confirm('Delete this post?')" style="background:none; border:none; color:#e74c3c; font-size:1.1em; cursor:pointer;"><i class="fa-solid fa-trash"></i> Delete</button>
            </form>
        </div>
    <?php endwhile; ?>
    </div>
    <h3>My Comments</h3>
    <div class="profile-cards">
    <?php while ($c = $comments->fetch_assoc()):
        $msg_content = '';
        $msg_id = $c['message_id'];
        $msg_row = $conn->query("SELECT content FROM messages WHERE id = $msg_id");
        if ($msg_row && $msg_row->num_rows > 0) {
            $msg_content = $msg_row->fetch_assoc()['content'];
        }
    ?>
        <div class="profile-card" style="background:#f7f8fc; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.07); padding:16px; margin-bottom:14px;">
            <div style="font-size:0.95em; color:#555; margin-bottom:6px;"><i class="fa-regular fa-clock"></i> <?= date('M d, Y H:i', strtotime($c['created_at'])) ?></div>
            <div style="font-size:1.08em; color:#222;"><?= htmlspecialchars($c['content']) ?></div>
            <div style="font-size:0.92em; color:#888; margin-top:4px;"><em>on post:</em> <?= htmlspecialchars($msg_content) ?></div>
            <form method="POST" style="margin-top:8px; text-align:right;">
                <input type="hidden" name="delete_comment_id" value="<?= $c['id'] ?>">
                <button type="submit" onclick="return confirm('Delete this comment?')" style="background:none; border:none; color:#e74c3c; font-size:1.1em; cursor:pointer;"><i class="fa-solid fa-trash"></i> Delete</button>
            </form>
        </div>
    <?php endwhile; ?>
    </div>
    <h3>Posts I've Liked</h3>
    <div class="profile-cards">
    <?php while ($pl = $post_likes->fetch_assoc()): ?>
        <div class="profile-card" style="background:#f7f8fc; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.07); padding:16px; margin-bottom:14px;">
            <div style="font-size:0.95em; color:#555; margin-bottom:6px;"><i class="fa-regular fa-clock"></i> <?= date('M d, Y H:i', strtotime($pl['created_at'])) ?></div>
            <div style="font-size:1.08em; color:#222;"><?= htmlspecialchars($pl['content']) ?></div>
        </div>
    <?php endwhile; ?>
    </div>
    <h3>Comments I've Liked</h3>
    <div class="profile-cards">
    <?php while ($cl = $comment_likes->fetch_assoc()): ?>
        <div class="profile-card" style="background:#f7f8fc; border-radius:10px; box-shadow:0 2px 8px rgba(0,0,0,0.07); padding:16px; margin-bottom:14px;">
            <div style="font-size:0.95em; color:#555; margin-bottom:6px;"><i class="fa-regular fa-clock"></i> <?= date('M d, Y H:i', strtotime($cl['created_at'])) ?></div>
            <div style="font-size:1.08em; color:#222;"><?= htmlspecialchars($cl['content']) ?></div>
        </div>
    <?php endwhile; ?>
    </div>
</div>
</body>
