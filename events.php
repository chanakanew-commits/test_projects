<?php

require_once 'includes/database.php';
require_once 'includes/header.php';

//searching
$search = isset($_GET['search']) ? trim($_GET['search']) : "";
$type = isset($_GET['type']) ? trim($_GET['type']) : "";

$sql = "SELECT * FROM `events` WHERE '1=1'";
$params = [];


if ($search !== '') {
    $sql .= "AND (`title` LIKE :search OR description LIKE :search2)";
    $params[':search'] = "%$search%";
    $params[':search2'] = "%$search%";
}

if ($type !== '') {
    $sql .= "AND type = :type";
    $params[':type'] = $type;
}
$sql .= " ORDER BY `event_date` ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$events = $stmt->fetchAll();

?>

<div class="section-header">
    <div>
        <h1>Events &amp Activites</h1>
        <p>Browse and register for upcoming events</p>
    </div>
    <a href="register.php" class="btn btn-primary">Register</a>
</div>

<!-- Search -->
<form method="GET" action="events.php">
    <div class="search-bar">
        <input type="text" name="search" placeholder="Search events..." value="<?php echo htmlspecialchars($search); ?>" />
    </div>
</form>

<!-- Events List -->
<?php if ($events): ?>
    <?php foreach ($events as $e):
        $pct = $e['total_slots'] > 0 ? round($e['registered'] / $e['total_slots'] * 100) : 0;
        $tagClass = match ($e['type']) {
            'Workshop'    => 'tag-blue',
            'Sports'      => 'tag-green',
            'Competition' => 'tag-coral',
            'Club'        => 'tag-amber',
            default       => 'tag-gray'
        };
    ?>

        <div class="event-card">
            <div class="event-date-box">
                <div class="day"><?php echo date('d', strtotime($e['event_date'])); ?></div>
                <div class="mon"><?php echo date('M', strtotime($e['event_date'])); ?></div>
            </div>
        </div>
        <div class="event-info">
            <h3>
                <?php echo htmlspecialchars($e['title']); ?>
                <span class="tag <?php echo $tagClass; ?>"><?php echo htmlspecialchars($e['type']); ?></span>
            </h3>
            <p><?php echo htmlspecialchars($e['description']); ?></p>
            <p style="font-size: 12px; color: #888; mergin-bottom:8px;"> &#128205; <?php echo htmlspecialchars($e['location']); ?></p>
            <div class="progress-wrap">
                <div class="progress-bar-bg">
                    <div class="[progress-bar-bg-fill" style="width:<?php echo $pct; ?>%"></div>
                    <div class="progress-label"><?php echo $e['registered']; ?> /<?php echo $e['total_slots']; ?> registered(<?php echo $pct; ?>%)</div>
                    <a href="register.php?event_id=<?php echo $e['id']; ?>" class="btn btn-primary btn-sm">Register &rarr;</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php else: ?>
    <div class="card" style="text-align:center;color:#888;padding:40px">
        No events found. <a href="events.php">Clear filters</a>
    </div>

<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>