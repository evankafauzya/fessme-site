# FessMe - Anonymous Messaging Web App

FessMe is a PHP & MySQL-based web application that allows users to post anonymous messages, comment, like, and report content. It features user registration, login, profile management, and a modern, responsive UI.

## Features
- Anonymous message posting
- User registration & login
- Commenting on messages
- Like/unlike messages and comments
- Report inappropriate messages/comments
- Profile page showing user activity
- Delete own posts/comments
- Cooldown timer to prevent spam
- Emoji picker for posts
- Trending and search functionality
- Responsive design & dark mode
- Admin dashboard (optional)

## Technologies Used
- PHP (mysqli, sessions, prepared statements)
- MySQL (users, messages, comments, likes, reports, comment_likes tables)
- HTML5, CSS3, JavaScript
- FontAwesome icons
- DiceBear avatars

## Getting Started
1. Clone the repository:
   ```bash
   git clone https://github.com/evankafauzya/fessme-site.git
   ```
2. Import the SQL schema (see `/db.sql` or your own setup) into your MySQL database.
3. Configure your database connection in `db.php`.
4. Start a local server (e.g., XAMPP) and open the site in your browser.

## Folder Structure
```
body.php
messages.php
profile.php
register.php
login.php
logout.php
navbar.php
faq.php
db.php
css/
  style.css
images/
  body1.JPG
  body2.JPG
  body3.JPG
```

## Rules & Regulations
- No hate speech, harassment, or bullying
- No sharing of personal information
- No spam or advertisements
- Respect others' opinions
- Use the report button for inappropriate content

## Contact
For questions or support, email: [fessme.support@gmail.com](mailto:fessme.support@gmail.com)

## License
MIT
