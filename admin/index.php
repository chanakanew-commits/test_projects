<?php

require_once '../includes/database.php';
require_once '../includes/header.php';

$pdo = $pdo ?? getConnection();

// Stats
$totalEvents  = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$totalMembers = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$totalRegs    = $pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
$pendingRegs  = $pdo->query("SELECT COUNT(*) FROM registrations WHERE status='Pending'")->fetchColumn();

// approve / reject / delete registration
$msg = '';
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    
    // Fetch registration details
    $stmt = $pdo->prepare("SELECT r.*, e.type as event_type FROM registrations r JOIN events e ON r.event_id = e.id WHERE r.id = :id");
    $stmt->execute([':id' => $id]);
    $reg = $stmt->fetch();
    
    if ($reg) {
        // Update status
        $pdo->prepare("UPDATE registrations SET status='Approved' WHERE id=:id")->execute([':id' => $id]);
        
        // Add to members table
        try {
            $addMember = $pdo->prepare("INSERT INTO members (student_id, full_name, email, club) VALUES (:sid, :name, :email, :club)");
            $addMember->execute([
                ':sid'   => $reg['student_id'],
                ':name'  => $reg['fname'] . ' ' . $reg['lname'],
                ':email' => $reg['email'],
                ':club'  => $reg['event_type'] // Use event type as club
            ]);
            $msg = 'Registration approved and student added to members.';
        } catch (PDOException $e) {
            $msg = 'Registration approved (Student already exists in members).';
        }
    }
}
if (isset($_GET['reject'])) {
    $pdo->prepare("UPDATE registrations SET status='Rejected' WHERE id=:id")->execute([':id' => (int)$_GET['reject']]);
    $msg = 'Registration rejected.';
}
if (isset($_GET['delete_reg'])) {
    $id = (int)$_GET['delete_reg'];
    
    // Get email of the registration to delete them from members too
    $stmt = $pdo->prepare("SELECT email FROM registrations WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $email = $stmt->fetchColumn();
    
    if ($email) {
        $pdo->prepare("DELETE FROM members WHERE email = :email")->execute([':email' => $email]);
    }
    
    $pdo->prepare("DELETE FROM registrations WHERE id=:id")->execute([':id' => $id]);
    $msg = 'Registration and associated member record deleted.';
}

// registrations with event title
$regs = $pdo->query("
    SELECT r.*, e.title AS event_title
    FROM registrations r
    JOIN events e ON r.event_id = e.id
    ORDER BY r.registered_at DESC
")->fetchAll();
?>

<h1>&#128187; Admin Panel</h1>
<p style="color:#666;margin-bottom:20px">Manage registrations, members, and content</p>

<?php if ($msg): ?><div class="success-msg">&#10003; <?php echo $msg; ?></div><?php endif; ?>

<!-- Stats -->
<div class="metrics-grid">
    <div class="metric-card">
        <div class="val"><?php echo $totalEvents; ?></div>
        <div class="lbl">Total Events</div>
    </div>
    <div class="metric-card">
        <div class="val"><?php echo $totalMembers; ?></div>
        <div class="lbl">Total Members</div>
    </div>
    <div class="metric-card">
        <div class="val"><?php echo $totalRegs; ?></div>
        <div class="lbl">Registrations</div>
    </div>
    <div class="metric-card">
        <div class="val"><?php echo $pendingRegs; ?></div>
        <div class="lbl">Pending</div>
    </div>
</div>

<!-- Admin Navigation -->
<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
    <a href="manage_events.php" class="btn btn-primary">Manage Events</a>
    <a href="../members.php" class="btn">Manage Members</a>
    <a href="../xml_view.php" class="btn">XML &amp; DB</a>
    <a href="../index.php" class="btn">&larr; Back to Site</a>
</div>

<!-- Registrations Table -->
<div class="card" style="padding:0">
    <div style="padding:16px 20px;border-bottom:1px solid #dde8e3">
        <h2 style="margin:0">Registration Records</h2>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Student ID</th>
                    <th>Email</th>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>File</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($regs): ?>
                    <?php foreach ($regs as $r):
                        $tagClass = match ($r['status']) {
                            'Approved' => 'tag-green',
                            'Rejected' => 'tag-coral',
                            default    => 'tag-amber'
                        };
                    ?>
                        <tr>
                            <td><?php echo $r['id']; ?></td>
                            <td><?php echo htmlspecialchars($r['fname'] . ' ' . $r['lname']); ?></td>
                            <td><?php echo htmlspecialchars($r['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($r['email']); ?></td>
                            <td><?php echo htmlspecialchars($r['event_title']); ?></td>
                            <td><?php echo date('d M Y', strtotime($r['registered_at'])); ?></td>
                            <td><span class="tag <?php echo $tagClass; ?>"><?php echo $r['status']; ?></span></td>
                            <td>
                                <?php if ($r['file_name']): ?>
                                    <a href="../uploads/<?php echo htmlspecialchars($r['file_name']); ?>" target="_blank" class="btn btn-sm">View</a>
                                <?php else: ?>
                                    <span style="color:#aaa;font-size:12px">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="display:flex;gap:4px">
                                <?php if ($r['status'] === 'Pending'): ?>
                                    <a href="?approve=<?php echo $r['id']; ?>" class="btn btn-sm btn-primary">&#10003;</a>
                                    <a href="?reject=<?php echo $r['id']; ?>" class="btn btn-sm">&#10007;</a>
                                <?php endif; ?>
                                <a href="?delete_reg=<?php echo $r['id']; ?>" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Delete this registration?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align:center;color:#888;padding:24px">No registrations yet.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>