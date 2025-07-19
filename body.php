<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include 'db.php';
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
include 'navbar.php';

// Helper: time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff/60) . ' min ago';
    if ($diff < 86400) return floor($diff/3600) . ' hr ago';
    if ($diff < 2592000) return floor($diff/86400) . ' day ago';
    if ($diff < 31536000) return floor($diff/2592000) . ' mo ago';
    return floor($diff/31536000) . ' yr ago';
}

// Cooldown timer (60s)
$can_post = true;
if (isset($_SESSION['last_post_time'])) {
    $can_post = (time() - $_SESSION['last_post_time']) > 5;
}

// Success message
$success = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["content"])) {
    if (!$can_post) {
        $success = "<div class='error-message'>Please wait before posting again.</div>";
    } else {
        $content = trim($_POST["content"]);
        if (!empty($content)) {
            $stmt = $conn->prepare("INSERT INTO messages (content, user_id) VALUES (?, ?)");
            $stmt->bind_param("si", $content, $_SESSION["user_id"]);
            $stmt->execute();
            $stmt->close();
            $_SESSION['last_post_time'] = time();
            $success = "<div class='success-message'><i class='fa-solid fa-circle-check'></i> Message posted!</div>";
        }
    }
}

// Like/unlike post
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["like_id"])) {
    $like_id = intval($_POST["like_id"]);
    // Check if already liked
    $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND message_id = ?");
    $stmt->bind_param("ii", $_SESSION["user_id"], $like_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        // Unlike
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND message_id = ?");
        $stmt->bind_param("ii", $_SESSION["user_id"], $like_id);
        $stmt->execute();
        $stmt->close();
    } else {
        // Like
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO likes (user_id, message_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $_SESSION["user_id"], $like_id);
        $stmt->execute();
        $stmt->close();
    }
}
// Delete post
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_id"])) {
    $del_id = intval($_POST["delete_id"]);
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $del_id, $_SESSION["user_id"]);
    $stmt->execute();
    $stmt->close();
    $success = "<div class='success-message'><i class='fa-solid fa-trash'></i> Message deleted!</div>";
    unset($_SESSION['last_post_time']); // Allow posting again after delete
}
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Main</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h2><i class="fa-solid fa-feather"></i>&nbsp; Post a New Anonymous Message</h2>
        <?php if (!empty($success)) echo $success; ?>
        <form method="POST" id="postForm" onsubmit="return confirmPost();">
            <textarea name="content" id="contentArea" maxlength="300" placeholder="Your message..." required></textarea>
            <div class="char-counter" id="charCounter">0 / 300</div>
            <div style="display: flex; justify-content: center;">
                <button type="submit" id="postBtn" <?= !$can_post ? 'disabled' : '' ?>><i class="fa-solid fa-paper-plane"></i> &nbsp;&nbsp;Post</button>
                <span id="loadingSpinner" style="display:none; margin-left:10px;"><i class="fa fa-spinner fa-spin"></i></span>
            </div>
        </form>

        <h3><i class="fa-solid fa-message"></i>&nbsp; Recent Messages</h3>
        <div class="scroll-messages">
        <?php
        $result = $conn->query("SELECT id, content, created_at, user_id FROM messages ORDER BY created_at DESC LIMIT 6");
        while ($row = $result->fetch_assoc()) {
            $msg_id = $row['id'];
            $avatar = 'https://api.dicebear.com/7.x/identicon/svg?seed=' . $msg_id;
            // Like count
            $like_count = $conn->query("SELECT COUNT(*) as cnt FROM likes WHERE message_id = $msg_id")->fetch_assoc()['cnt'];
            // User liked?
            $liked = false;
            if (isset($_SESSION['user_id'])) {
                $check_like = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND message_id = ?");
                $check_like->bind_param("ii", $_SESSION['user_id'], $msg_id);
                $check_like->execute();
                $check_like->store_result();
                $liked = $check_like->num_rows > 0;
                $check_like->close();
            }
            echo "<div class='message-card' style='display:flex; gap:16px; align-items:flex-start;'>
                    <img src='$avatar' alt='avatar' style='width:40px; height:40px; border-radius:50%; background:#e3e3f7; margin-top:2px;'>
                    <div style='flex:1;'>
                        <p>" . htmlspecialchars($row["content"]) . "</p>
                        <small><i class='fa-regular fa-clock'></i> " . timeAgo($row["created_at"]) . "</small>
                    </div>";
            // Like button
            echo "<form method='POST' style='margin-right:8px; display:inline;'><input type='hidden' name='like_id' value='$msg_id'><button type='submit' class='like-btn' title='Like' style='background:none; border:none; color:" . ($liked ? '#375a9e' : '#6e94c2') . "; font-size:1.2em;'><i class='fa-regular fa-thumbs-up'></i> $like_count</button></form>";
            // Report button
            echo "<button class='report-btn' style='background:none; border:none; color:#e74c3c; font-size:1.2em; margin-left:8px;' onclick='reportPost($msg_id)'><i class='fa-solid fa-flag'></i></button>";
            echo "</div>";
        }
        ?>
        </div>
    </div>
    <script>
    // Character counter for textarea with color change
    const contentArea = document.getElementById('contentArea');
    const charCounter = document.getElementById('charCounter');
    contentArea.addEventListener('input', function() {
        charCounter.textContent = contentArea.value.length + ' / 300';
        if (contentArea.value.length > 250) {
            charCounter.style.color = '#e74c3c';
        } else {
            charCounter.style.color = '#888';
        }
    });
    charCounter.textContent = contentArea.value.length + ' / 300';

    // Confirmation modal before posting
    function confirmPost() {
        if (!contentArea.value.trim()) return false;
        return confirm('Are you sure you want to post this message?');
    }
    // Confirmation modal before deleting
    function confirmDelete() {
        return confirm('Are you sure you want to delete this message?');
    }
    // Like post (demo only)
    function likePost(id) {
        alert('You liked post #' + id + ' (demo only)');
    }
    // Report post (demo only)
    function reportPost(id) {
        alert('You reported post #' + id + ' (demo only)');
    }
    // Emoji picker (simple)
    contentArea.addEventListener('keydown', function(e) {
        if (e.ctrlKey && e.key === 'e') {
            contentArea.value += '😊';
            charCounter.textContent = contentArea.value.length + ' / 300';
            e.preventDefault();
        }
    });
    // Loading spinner on submit
    document.getElementById('postForm').addEventListener('submit', function() {
        document.getElementById('postBtn').style.display = 'none';
        document.getElementById('loadingSpinner').style.display = 'inline-block';
    });
    </script>
</body>
