<?php

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <title>CampusHub – Student Services</title>
    <link rel="stylesheet" href="/campushub/css/style.css" />
</head>

<body>
    <nav class="navbar">
        <div class="nav-container">
            <a class="nav-brand" href="/campushub/index.php">&#127979; CampusHub</a>
            <ul class="nav-links">
                <li><a href="/campushub/index.php">Home</a></li>
                <li><a href="/campushub/events.php">Events</a></li>
                <li><a href="/campushub/register.php">Register</a></li>
                <li><a href="/campushub/members.php">Members</a></li>
                <li><a href="/campushub/media.php">Media</a></li>
                <li><a href="/campushub/xml_view.php">XML</a></li>
                <li><a href="/campushub/admin/index.php" class="btn-admin">Admin</a></li>
            </ul>
            <button class="theme-toggle" id="theme-btn" title="Toggle Dark/Light Mode">🌓</button>
        </div>
    </nav>

    <script>
        const themeBtn = document.getElementById('theme-btn');
        const body = document.body;

        // Check for saved theme
        if (localStorage.getItem('theme') === 'dark') {
            body.classList.add('dark-mode');
        }

        themeBtn.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            localStorage.setItem('theme', body.classList.contains('dark-mode') ? 'dark' : 'light');
        });
    </script>
    <main class="main-content">