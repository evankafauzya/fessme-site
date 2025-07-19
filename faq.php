<?php
session_start();
date_default_timezone_set('Asia/Jakarta');
include 'db.php';
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
include 'navbar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FAQ - FessMe</title>
  <link rel="stylesheet" href="css/style.css" />
  <style>
    .faq-title {
  font-size: 2.3rem;
  color: #375a9e;
  text-align: center;
  margin-bottom: 2rem;
}

.faq-heading {
  font-size: 1.5rem;
  color: #375a9e;
  margin-top: 2.5rem;
  margin-bottom: 0.5rem;
}

.faq-list {
  list-style-type: disc;
  padding-left: 1.5rem;
  margin-bottom: 2rem;
}

.faq-list li {
  margin-bottom: 10px;
  font-size: 1.05em;
}

.faq-text {
  font-size: 1.1em;
  color: #444;
  margin-bottom: 2rem;
}

.faq-link {
  color: #375a9e;
  text-decoration: underline;
  font-weight: 500;
}

.faq-back {
  display: inline-block;
  margin-top: 2rem;
  font-size: 1.05em;
  color: #375a9e;
  text-decoration: underline;
  font-weight: 500;
}

/* ANIMATIONS */
.animate {
  opacity: 0;
  transform: translateY(20px);
  transition: opacity 0.6s ease, transform 0.6s ease;
}

.animate.show {
  opacity: 1;
  transform: translateY(0);
}

/* RESPONSIVE */
@media (max-width: 600px) {
  body {
    padding: 1rem;
  }

  .faq-title {
    font-size: 1.8rem;
  }

  .faq-heading {
    font-size: 1.3rem;
  }
}

  </style>
</head>
<body>
  <main>
    <h2 class="faq-title">Frequently Asked Questions</h2>
    <ul class="faq-list animate">
      <li><strong>Is this anonymous?</strong> Yes, only you know who posted.</li>
      <li><strong>Can others see my identity?</strong> No, your username is hidden.</li>
      <li><strong>Can I delete messages?</strong> Yes, you can delete your own messages from your profile page.</li>
    </ul>

    <h3 class="faq-heading">Why I Made This Website</h3>
    <p class="faq-text animate">
      FessMe was created to provide a safe, anonymous space for people to share their thoughts, feelings, and confessions without fear of judgment. I believe everyone deserves a place to express themselves freely and connect with others who may feel the same.
    </p>

    <h3 class="faq-heading">Contact</h3>
    <p class="faq-text animate">
      If you have questions, feedback, or need support, please contact me at:<br />
      <a href="mailto:fessme.support@gmail.com" class="faq-link">fessme.support@gmail.com</a>
    </p>

    <h3 class="faq-heading">Rules & Regulations</h3>
    <ul class="faq-list animate">
      <li>No hate speech, harassment, or bullying.</li>
      <li>No sharing of personal information (yours or others).</li>
      <li>No spam, advertisements, or self-promotion.</li>
      <li>Respect others' opinions and experiences.</li>
      <li>Report inappropriate content using the report button.</li>
      <li>Admins reserve the right to remove posts/comments that violate these rules.</li>
    </ul>

    <a href="login.php" class="faq-back animate">&larr; Back to login</a>
  </main>

  <script>
    // Simple fade-in animation on scroll
    const animateElems = document.querySelectorAll('.animate');

    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('show');
        }
      });
    }, { threshold: 0.1 });

    animateElems.forEach(el => observer.observe(el));
  </script>
</body>
</html>
