<?php

require_once '../includes/database.php';
require_once '../includes/header.php';

$pdo = $pdo ?? getConnection();

$success = '';
$errors  = [];
$editing = null;

// ---- DELETE event ----
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM events WHERE id=:id")->execute([':id' => (int)$_GET['delete']]);
    $success = 'Event deleted.';
}

// ---- EDIT event ----
if (isset($_GET['edit'])) {
    $editing = $pdo->prepare("SELECT * FROM events WHERE id=:id");
    $editing->execute([':id' => (int)$_GET['edit']]);
    $editing = $editing->fetch();
}

// ---- INSERT or UPDATE ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id    = (int)($_POST['id'] ?? 0);
    $title = trim(htmlspecialchars($_POST['title']       ?? ''));
    $type  = trim(htmlspecialchars($_POST['type']        ?? ''));
    $desc  = trim(htmlspecialchars($_POST['description'] ?? ''));
    $date  = $_POST['event_date'] ?? '';
    $loc   = trim(htmlspecialchars($_POST['location']    ?? ''));
    $slots = (int)($_POST['total_slots'] ?? 0);

    if ($title === '') $errors[] = 'Event title is required.';
    if ($date  === '') $errors[] = 'Event date is required.';
    if ($slots <= 0)   $errors[] = 'Total slots must be greater than 0.';

    if (empty($errors)) {
        if ($id > 0) {
            // UPDATE
            $sql = "UPDATE events SET title=:t, type=:tp, description=:d, event_date=:dt, location=:l, total_slots=:s WHERE id=:id";
            $pdo->prepare($sql)->execute([':t' => $title, ':tp' => $type, ':d' => $desc, ':dt' => $date, ':l' => $loc, ':s' => $slots, ':id' => $id]);
            $success = 'Event updated successfully.';
        } else {
            // INSERT
            $sql = "INSERT INTO events (title, type, description, event_date, location, total_slots) VALUES (:t,:tp,:d,:dt,:l,:s)";
            $pdo->prepare($sql)->execute([':t' => $title, ':tp' => $type, ':d' => $desc, ':dt' => $date, ':l' => $loc, ':s' => $slots]);
            $success = 'New event created successfully.';
        }
        $editing = null;
    }
}

$events = $pdo->query("SELECT * FROM events ORDER BY event_date ASC")->fetchAll();
?>

<div class="section-header">
    <div>
        <h1>Manage Events</h1>
        <p>Create, edit and delete events</p>
    </div>
    <a href="index.php" class="btn">&larr; Admin Home</a>
</div>

<?php if ($success): ?><div class="success-msg">&#10003; <?php echo $success; ?></div><?php endif; ?>
<?php if ($errors):  ?><div class="error-box"><?php echo implode('<br>', $errors); ?></div><?php endif; ?>

<!-- Event Form -->
<div class="card">
    <h2><?php echo $editing ? 'Edit Event' : 'Add New Event'; ?></h2>
    <form method="POST" action="manage_events.php">
        <?php if ($editing): ?>
            <input type="hidden" name="id" value="<?php echo $editing['id']; ?>">
        <?php endif; ?>

        <div class="form-row">
            <div class="form-group">
                <label>Event Title *</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($editing['title'] ?? ''); ?>" placeholder="Event title">
            </div>
            <div class="form-group">
                <label>Type</label>
                <select name="type">
                    <?php foreach (['Workshop', 'Sports', 'Competition', 'Club', 'Other'] as $t): ?>
                        <option <?php if (($editing['type'] ?? '') === $t) echo 'selected'; ?>><?php echo $t; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="2"><?php echo htmlspecialchars($editing['description'] ?? ''); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Date *</label>
                <input type="date" name="event_date" value="<?php echo $editing['event_date'] ?? ''; ?>">
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" value="<?php echo htmlspecialchars($editing['location'] ?? ''); ?>" placeholder="e.g. Main Hall">
            </div>
        </div>

        <div class="form-group" style="max-width:200px">
            <label>Total Slots *</label>
            <input type="number" name="total_slots" value="<?php echo $editing['total_slots'] ?? 50; ?>" min="1">
        </div>

        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary">
                <?php echo $editing ? 'Update Event' : 'Create Event'; ?>
            </button>
            <?php if ($editing): ?>
                <a href="manage_events.php" class="btn">Cancel</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Events Table -->
<div class="card" style="padding:0">
    <div style="padding:16px 20px;border-bottom:1px solid #dde8e3">
        <h2 style="margin:0">All Events</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Slots</th>
                    <th>Registered</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $e): ?>
                    <tr>
                        <td><?php echo $e['id']; ?></td>
                        <td><?php echo htmlspecialchars($e['title']); ?></td>
                        <td><span class="tag tag-blue"><?php echo $e['type']; ?></span></td>
                        <td><?php echo date('d M Y', strtotime($e['event_date'])); ?></td>
                        <td><?php echo htmlspecialchars($e['location']); ?></td>
                        <td><?php echo $e['total_slots']; ?></td>
                        <td><?php echo $e['registered']; ?></td>
                        <td style="display:flex;gap:4px">
                            <a href="?edit=<?php echo $e['id']; ?>" class="btn btn-sm">Edit</a>
                            <a href="?delete=<?php echo $e['id']; ?>" class="btn btn-sm btn-danger"
                                onclick="return confirm('Delete this event?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>