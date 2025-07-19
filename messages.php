<?php
date_default_timezone_set('Asia/Jakarta');
include 'db.php';
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

// Handle comment submission
$comment_feedback = "";
// Handle comment like
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["like_comment_id"])) {
    $comment_id = intval($_POST["like_comment_id"]);
    $user_id = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : 0;
    $stmt = $conn->prepare("SELECT id FROM comment_likes WHERE user_id = ? AND comment_id = ?");
    $stmt->bind_param("ii", $user_id, $comment_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM comment_likes WHERE user_id = ? AND comment_id = ?");
        $stmt->bind_param("ii", $user_id, $comment_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO comment_likes (user_id, comment_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $comment_id);
        $stmt->execute();
        $stmt->close();
    }
    $comment_feedback = "<div class='success-message'>Comment like updated.</div>";
}
// Handle comment report
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["report_comment_id"])) {
    $comment_id = intval($_POST["report_comment_id"]);
    $report_reason = isset($_POST["report_comment_reason"]) ? trim($_POST["report_comment_reason"]) : '';
    if ($report_reason !== '') {
        $stmt = $conn->prepare("INSERT INTO comment_reports (comment_id, reason, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("is", $comment_id, $report_reason);
        $stmt->execute();
        $stmt->close();
        $comment_feedback = "<div class='success-message'>Comment report submitted. Thank you!</div>";
    } else {
        $comment_feedback = "<div class='error-message'>Please select or write a reason.</div>";
    }
}
$feedback = "";
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["comment_content"]) && isset($_POST["message_id"])) {
    $comment_content = trim($_POST["comment_content"]);
    $message_id = intval($_POST["message_id"]);
    $user_id = isset($_SESSION["user_id"]) ? intval($_SESSION["user_id"]) : 0;
    if ($comment_content !== "" && $message_id > 0 && $user_id > 0) {
        $stmt = $conn->prepare("INSERT INTO comments (message_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $message_id, $user_id, $comment_content);
        $stmt->execute();
        $stmt->close();
        $feedback = "<div class='success-message'>Comment posted!</div>";
    } else {
        $feedback = "<div class='error-message'>Comment cannot be empty or missing message reference.</div>";
    }
}
// Handle like/unlike
// Handle report submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["report_id"])) {
    $report_id = intval($_POST["report_id"]);
    $report_reason = isset($_POST["report_reason"]) ? trim($_POST["report_reason"]) : '';
    if ($report_reason !== '') {
        $stmt = $conn->prepare("INSERT INTO reports (message_id, reason, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param("is", $report_id, $report_reason);
        $stmt->execute();
        $stmt->close();
        $feedback = "<div class='success-message'>Report submitted. Thank you!</div>";
    } else {
        $feedback = "<div class='error-message'>Please select or write a reason.</div>";
    }
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["like_id"])) {
    $like_id = intval($_POST["like_id"]);
    $user_id = isset($_SESSION["user_id"]) ? $_SESSION["user_id"] : 0;
    $stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND message_id = ?");
    $stmt->bind_param("ii", $user_id, $like_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND message_id = ?");
        $stmt->bind_param("ii", $user_id, $like_id);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt->close();
        $stmt = $conn->prepare("INSERT INTO likes (user_id, message_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $like_id);
        $stmt->execute();
        $stmt->close();
    }
}

// Pagination
$limit = 5;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Search
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_sql = $search ? "WHERE content LIKE '%" . $conn->real_escape_string($search) . "%'" : "";

// Sort
 $sort = isset($_GET['sort']) ? $_GET['sort'] : 'desc';
 $order_sql = '';
 if ($sort === 'asc') {
     $order_sql = 'ORDER BY m.created_at ASC';
 } elseif ($sort === 'desc') {
     $order_sql = 'ORDER BY m.created_at DESC';
 } elseif ($sort === 'most_comments') {
     $order_sql = 'ORDER BY comment_count DESC, m.created_at DESC';
 } elseif ($sort === 'most_likes') {
     $order_sql = 'ORDER BY like_count DESC, m.created_at DESC';
 } else {
     $order_sql = 'ORDER BY m.created_at DESC';
 }

// Count total messages
$count_result = $conn->query("SELECT COUNT(*) as total FROM messages $search_sql");
$total_messages = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_messages / $limit);
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Custom Carousel -->
<div class="carousel-container">
  <div class="carousel-slide" id="carouselSlide">
    <div class="carousel-item">
      <img src="images/body1.JPG" alt="Slide 1">
      <div class="carousel-caption">Welcome to FessMe!</div>
    </div>
    <div class="carousel-item">
      <img src="images/body2.JPG" alt="Slide 2">
      <div class="carousel-caption">Share your thoughts anonymously</div>
    </div>
    <div class="carousel-item">
      <img src="images/body3.JPG" alt="Slide 3">
      <div class="carousel-caption">Read messages from others</div>
    </div>
  </div>
  <button class="carousel-btn prev-btn" onclick="prevSlide()">❮</button>
  <button class="carousel-btn next-btn" onclick="nextSlide()">❯</button>
</div>

<!-- Messages -->
<div class="container">
  <h2>All Anonymous Messages</h2>
  <form method="GET" class="search-form" style="display:flex; gap:10px; margin-bottom:1rem;">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search messages..." style="flex:1;">
     <select name="sort">
       <option value="desc" <?= $sort==='desc'?'selected':'' ?>>Newest</option>
       <option value="asc" <?= $sort==='asc'?'selected':'' ?>>Oldest</option>
       <option value="most_comments" <?= $sort==='most_comments'?'selected':'' ?>>Most Commented</option>
       <option value="most_likes" <?= $sort==='most_likes'?'selected':'' ?>>Most Liked</option>
     </select>
    </select>
    <button type="submit"><i class="fas fa-search"></i> &nbsp;Search</button>
  </form>
  <?php if (!empty($feedback)) echo $feedback; ?>
  <div id="messages-list">
    <?php
     $query = "SELECT m.id, m.content, m.created_at, 
         (SELECT COUNT(*) FROM comments c WHERE c.message_id = m.id) AS comment_count,
         (SELECT COUNT(*) FROM likes l WHERE l.message_id = m.id) AS like_count
         FROM messages m $search_sql $order_sql LIMIT $limit OFFSET $offset";
     $result = $conn->query($query);
    while ($row = $result->fetch_assoc()) {
      $msg_id = $row["id"];
      // Count comments
      $ccount = $conn->query("SELECT COUNT(*) as cnt FROM comments WHERE message_id = $msg_id")->fetch_assoc()['cnt'];
      // Like count
      $like_count = $conn->query("SELECT COUNT(*) as cnt FROM likes WHERE message_id = $msg_id")->fetch_assoc()['cnt'];
      // User liked?
      $liked = false;
      if (isset($_SESSION['user_id'])) {
        $check_like = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND message_id = ?");
        $user_id = $_SESSION['user_id'];
        $message_id = $msg_id;
        $check_like->bind_param("ii", $user_id, $message_id);
        $check_like->execute();
        $check_like->store_result();
        $liked = $check_like->num_rows > 0;
        $check_like->close();
      }
      // Random avatar
      $avatar = 'https://api.dicebear.com/7.x/identicon/svg?seed=' . $msg_id;
      ?>
      <div class="message-card" style="display:flex; gap:16px; align-items:flex-start;">
        <img src="<?= $avatar ?>" alt="avatar" style="width:40px; height:40px; border-radius:50%; background:#e3e3f7; margin-top:2px;">
        <div style="flex:1;">
          <p><?= htmlspecialchars($row["content"]) ?></p>
          <small><i class="fa-regular fa-clock"></i> <?= timeAgo($row["created_at"]) ?> &middot; <i class="fa-regular fa-comment"></i> <?= $ccount ?></small>
        </div>
        <form method="POST" style="margin-right:8px; display:inline;">
          <input type="hidden" name="like_id" value="<?= $msg_id ?>">
          <button type="submit" class="like-btn" title="Like" style="background:none; border:none; color:<?= $liked ? '#375a9e' : '#6e94c2' ?>; font-size:1.2em;"><i class="fa-regular fa-thumbs-up"></i> <?= $like_count ?></button>
        </form>
        <button type="button" class="report-btn" data-id="<?= $msg_id ?>" style="margin-left:10px; background:none; border:none; color:#e74c3c; font-size:1.2em; cursor:pointer;" title="Report"><i class="fa-solid fa-flag"></i></button>
      </div>
      <div class="comments-section">
        <?php
        $cresult = $conn->prepare("SELECT id, content, created_at FROM comments WHERE message_id = ? ORDER BY created_at ASC");
        $cresult->bind_param("i", $msg_id);
        $cresult->execute();
        $cresult->store_result();
        $cresult->bind_result($comment_id, $c_content, $c_created);
        while ($cresult->fetch()) {
          $cavatar = 'https://api.dicebear.com/7.x/identicon/svg?seed=' . md5($c_content . $c_created);
          // Like count for comment
          $clike_count = $conn->query("SELECT COUNT(*) as cnt FROM comment_likes WHERE comment_id = $comment_id")->fetch_assoc()['cnt'];
          // User liked?
          $cliked = false;
          if (isset($_SESSION['user_id'])) {
            $check_clike = $conn->prepare("SELECT id FROM comment_likes WHERE user_id = ? AND comment_id = ?");
            $user_id = $_SESSION['user_id'];
            $check_clike->bind_param("ii", $user_id, $comment_id);
            $check_clike->execute();
            $check_clike->store_result();
            $cliked = $check_clike->num_rows > 0;
            $check_clike->close();
          }
          ?>
          <div class="comment-card" style="display:flex; gap:10px; align-items:center; animation:fadeIn 0.7s;">
            <img src="<?= $cavatar ?>" alt="avatar" style="width:28px; height:28px; border-radius:50%; background:#e3e3f7;">
            <div style="flex:1;">
              <?= htmlspecialchars($c_content) ?><br><small><i class="fa-regular fa-clock"></i> <?= timeAgo($c_created) ?></small>
            </div>
            <form method="POST" style="margin-left:5px; display:inline;">
              <input type="hidden" name="like_comment_id" value="<?= $comment_id ?>">
              <button type="submit" class="like-btn" title="Like comment" style="background:none; border:none; color:<?= $cliked ? '#375a9e' : '#6e94c2' ?>; font-size:1em;"><i class="fa-regular fa-thumbs-up"></i> <?= $clike_count ?></button>
            </form>
            <button type="button" class="report-comment-btn" data-id="<?= $comment_id ?>" style="background:none; border:none; color:#e74c3c; font-size:1em; cursor:pointer; margin-left:5px;" title="Report"><i class="fa-solid fa-flag"></i></button>
          </div>
        <?php }
        $cresult->close();
        ?>
        <form method="POST" class="comment-form" style="margin-top:10px;">
          <input type="hidden" name="message_id" value="<?= $msg_id ?>">
          <input type="text" name="comment_content" maxlength="200" placeholder="Add an anonymous comment... " required>
          <button type="submit"><i class="fa-regular fa-paper-plane"></i>&nbsp; Comment</button>
        </form>
      </div>
    <?php }
    ?>
  </div>
  <!-- Pagination -->
<!-- Report Modal -->
<!-- Comment Report Modal -->
<div id="commentReportModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.25); z-index:999; align-items:center; justify-content:center;">
  <div style="background:#fff; padding:24px 20px; border-radius:10px; max-width:350px; width:100%; box-shadow:0 2px 16px rgba(0,0,0,0.18);">
    <h3 style="margin-top:0;">Report Comment</h3>
    <form method="POST" id="commentReportForm">
      <input type="hidden" name="report_comment_id" id="report_comment_id">
      <label for="report_comment_reason">Reason:</label><br>
      <select name="report_comment_reason" id="report_comment_reason" style="width:100%; margin-bottom:8px;">
        <option value="">-- Select reason --</option>
        <option value="Spam">Spam</option>
        <option value="Harassment">Harassment</option>
        <option value="Hate Speech">Hate Speech</option>
        <option value="Inappropriate Content">Inappropriate Content</option>
        <option value="Other">Other</option>
      </select>
      <textarea name="report_comment_reason_custom" id="report_comment_reason_custom" rows="2" placeholder="Write your reason..." style="width:100%; margin-bottom:8px; display:none;"></textarea>
      <button type="submit" style="background:#e74c3c; color:#fff; border:none; border-radius:6px; padding:8px 18px; font-weight:500; cursor:pointer;">Submit Report</button>
      <button type="button" id="closeCommentReportModal" style="background:#eee; color:#333; border:none; border-radius:6px; padding:8px 18px; margin-left:8px; cursor:pointer;">Cancel</button>
    </form>
  </div>
</div>
<div id="reportModal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; background:rgba(0,0,0,0.25); z-index:999; align-items:center; justify-content:center;">
  <div style="background:#fff; padding:24px 20px; border-radius:10px; max-width:350px; width:100%; box-shadow:0 2px 16px rgba(0,0,0,0.18);">
    <h3 style="margin-top:0;">Report Post</h3>
    <form method="POST" id="reportForm">
      <input type="hidden" name="report_id" id="report_id">
      <label for="report_reason">Reason:</label><br>
      <select name="report_reason" id="report_reason" style="width:100%; margin-bottom:8px;">
        <option value="">-- Select reason --</option>
        <option value="Spam">Spam</option>
        <option value="Harassment">Harassment</option>
        <option value="Hate Speech">Hate Speech</option>
        <option value="Inappropriate Content">Inappropriate Content</option>
        <option value="Other">Other</option>
      </select>
      <textarea name="report_reason_custom" id="report_reason_custom" rows="2" placeholder="Write your reason..." style="width:100%; margin-bottom:8px; display:none;"></textarea>
      <button type="submit" style="background:#e74c3c; color:#fff; border:none; border-radius:6px; padding:8px 18px; font-weight:500; cursor:pointer;">Submit Report</button>
      <button type="button" id="closeReportModal" style="background:#eee; color:#333; border:none; border-radius:6px; padding:8px 18px; margin-left:8px; cursor:pointer;">Cancel</button>
    </form>
  </div>
</div>
  <div style="display:flex; justify-content:center; gap:8px; margin-top:1.5rem;">
    <?php for ($i=1; $i<=$total_pages; $i++): ?>
      <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&sort=<?= $sort ?>" style="padding:6px 14px; border-radius:6px; background:<?= $i==$page?'#6e94c2':'#e3e3f7' ?>; color:<?= $i==$page?'#fff':'#333' ?>; text-decoration:none; font-weight:500;"> <?= $i ?> </a>
    <?php endfor; ?>
  </div>
</div>
</button>
<script>

  // Comment report modal logic
  const reportCommentBtns = document.querySelectorAll('.report-comment-btn');
  const commentReportModal = document.getElementById('commentReportModal');
  const commentReportForm = document.getElementById('commentReportForm');
  const reportCommentIdInput = document.getElementById('report_comment_id');
  const reportCommentReasonSelect = document.getElementById('report_comment_reason');
  const reportCommentReasonCustom = document.getElementById('report_comment_reason_custom');
  const closeCommentReportModal = document.getElementById('closeCommentReportModal');

  reportCommentBtns.forEach(btn => {
    btn.onclick = function() {
      reportCommentIdInput.value = btn.getAttribute('data-id');
      commentReportModal.style.display = 'flex';
      reportCommentReasonSelect.value = '';
      reportCommentReasonCustom.value = '';
      reportCommentReasonCustom.style.display = 'none';
    }
  });
  closeCommentReportModal.onclick = function() {
    commentReportModal.style.display = 'none';
  }
  reportCommentReasonSelect.onchange = function() {
    if (reportCommentReasonSelect.value === 'Other') {
      reportCommentReasonCustom.style.display = 'block';
    } else {
      reportCommentReasonCustom.style.display = 'none';
    }
  }
  commentReportForm.onsubmit = function(e) {
    if (reportCommentReasonSelect.value === '') {
      alert('Please select a reason.');
      e.preventDefault();
      return false;
    }
    if (reportCommentReasonSelect.value === 'Other') {
      if (reportCommentReasonCustom.value.trim() === '') {
        alert('Please write your reason.');
        e.preventDefault();
        return false;
      }
      reportCommentReasonSelect.removeAttribute('name');
      reportCommentReasonCustom.setAttribute('name', 'report_comment_reason');
    } else {
      reportCommentReasonSelect.setAttribute('name', 'report_comment_reason');
      reportCommentReasonCustom.removeAttribute('name');
    }
    commentReportModal.style.display = 'none';
    return true;
  }

  // Report modal logic
  const reportBtns = document.querySelectorAll('.report-btn');
  const reportModal = document.getElementById('reportModal');
  const reportForm = document.getElementById('reportForm');
  const reportIdInput = document.getElementById('report_id');
  const reportReasonSelect = document.getElementById('report_reason');
  const reportReasonCustom = document.getElementById('report_reason_custom');
  const closeReportModal = document.getElementById('closeReportModal');

  reportBtns.forEach(btn => {
    btn.onclick = function() {
      reportIdInput.value = btn.getAttribute('data-id');
      reportModal.style.display = 'flex';
      reportReasonSelect.value = '';
      reportReasonCustom.value = '';
      reportReasonCustom.style.display = 'none';
    }
  });
  closeReportModal.onclick = function() {
    reportModal.style.display = 'none';
  }
  reportReasonSelect.onchange = function() {
    if (reportReasonSelect.value === 'Other') {
      reportReasonCustom.style.display = 'block';
    } else {
      reportReasonCustom.style.display = 'none';
    }
  }
  reportForm.onsubmit = function(e) {
    if (reportReasonSelect.value === '') {
      alert('Please select a reason.');
      e.preventDefault();
      return false;
    }
    if (reportReasonSelect.value === 'Other') {
      if (reportReasonCustom.value.trim() === '') {
        alert('Please write your reason.');
        e.preventDefault();
        return false;
      }
      // Set a hidden input to submit the custom reason
      // Remove the select's name so only the custom reason is submitted
      reportReasonSelect.removeAttribute('name');
      reportReasonCustom.setAttribute('name', 'report_reason');
    } else {
      // Ensure select has name and textarea does not
      reportReasonSelect.setAttribute('name', 'report_reason');
      reportReasonCustom.removeAttribute('name');
    }
    reportModal.style.display = 'none';
    return true;
  }

  document.getElementById('messages-list').style.opacity = 0.5;
  window.onload = function() {
    document.getElementById('messages-list').style.opacity = 1;
  }

  const slide = document.getElementById("carouselSlide");
  const totalSlides = slide.children.length;
  let currentIndex = 0;

  function updateSlide() {
    slide.style.transform = `translateX(-${currentIndex * 100}%)`;
  }

  function nextSlide() {
    currentIndex = (currentIndex + 1) % totalSlides;
    updateSlide();
  }

  function prevSlide() {
    currentIndex = (currentIndex - 1 + totalSlides) % totalSlides;
    updateSlide();
  }

  // Auto slide every 5s
  setInterval(nextSlide, 5000);
</script>
</body>
