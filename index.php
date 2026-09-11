<?php

require_once 'includes/database.php';
require_once 'includes/header.php';

$stmt = $pdo->query("SELECT * FROM announcements ORDER BY published_at DESC LIMIT 3");
$announcements = $stmt->fetchAll();


$totalEvents = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalMembers = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$totalRegisters = $pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
$totalClubs = $pdo->query("SELECT COUNT(*) FROM events WHERE type='Club'")->fetchColumn();

?>

<!-- Hero Banner -->
<div class="hero">
    <h1>&#127979; Welcome to CampusHub</h1>
    <p>One platform for clubs, events, activities, and student community for all the students.</p>

    <div class="hero-btns">
        <a href="events.php"><button class="btn-white">Search Events</button></a>
        <a href="register.php"><button class="btn-ghost">Register Now</button></a>
    </div>
</div>

<!-- Metric Cards -->
<div class="metric-grid">
    <div class="metric-card">
        <div class="val"><?php echo $totalEvents; ?></div>
        <div class="lbl">Upcoming Events</div>
    </div>

    <div class="metric-card">
        <div class="val"><?php echo $totalMembers ?></div>
        <div class="lbl">Registered Members</div>
    </div>

    <div class="metric-card">
        <div class="val"><?php echo $totalRegisters ?></div>
        <div class="lbl"> Total Registrations</div>
    </div>

    <div class="metric-card">
        <div class="val">12</div>
        <div class="lbl">Active Clubs</div>
    </div>
</div>


<!--- Announcements -->
<div class="card">
    <h2>&#128226; Announcements</h2>
    <?php if ($announcements): ?>
        <?php foreach ($announcements as $a): ?>
            <div class="announce">
                <h4><?php echo htmlspecialchars($a['title']); ?></h4>
                <p><?php echo htmlspecialchars($a['body']); ?></p>
            </div>

        <?php endforeach; ?>
    <?php else: ?>
        <p style="color:#800;font-size:13px">No announcements at the moment.</p>
    <?php endif; ?>
</div>


<!-- Upcoming Events View -->
<div class="card">
    <div class="section-header" style="margin-bottom:12px">
        <h2 style="margin:0">Upcoming Events</h2>
        <a href="events.php" class="btn">View all &rarr;</a>
    </div>
    <?php
    $events = $pdo->query("SELECT * FROM events ORDER BY event_date ASC LIMIT 3")->fetchAll();
    foreach ($events as $e):
        $pct = $e['total_slots'] > 0 ? round($e['registered'] / $e['total_slots'] * 100) : 0;
    ?>
        <div class="event-card">
            <div class="event-date-box">
                <div class="day"><?php echo date('d', strtotime($e['event_date'])); ?></div>
                <div class="mon"><?php echo date('M', strtotime($e['event_date'])); ?></div>
            </div>
            <div class="event-info">
                <h3><?php echo htmlspecialchars($e['title']) ?>
                    <span class="tag tag-blue"><?php echo htmlspecialchars($e['type']); ?></span>
                </h3>
                <p><?php echo htmlspecialchars($e['description']); ?></p>
                <div class="progress-wrap">
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: <?php echo $pct; ?>%"></div>
                    </div>
                    <div class="progress-label">
                        <span><?php echo $e['registered']; ?> / <?php echo $e['total_slots']; ?> registered</span>
                        <span><?php echo $pct; ?>% full</span>
                    </div>
                </div>
            </div>
            <a href="register.php?event_id=<?php echo $e['id']; ?>" class="btn btn-primary btn-sm">Register &rarr;</a>
        </div>

</div>
<?php endforeach; ?>
</div>
<?php require_once 'includes/footer.php' ?>