<?php

require_once 'includes/database.php';
require_once 'includes/header.php';

$success = '';
$errors  = [];

// ---- DELETE member ----
if (isset($_GET['delete'])) {
    $delId = (int)$_GET['delete'];
    
    // Get email BEFORE deleting to clear registrations
    $stmt = $pdo->prepare("SELECT email FROM members WHERE id = :id");
    $stmt->execute([':id' => $delId]);
    $email = $stmt->fetchColumn();
    
    if ($email) {
        $pdo->prepare("DELETE FROM registrations WHERE email = :email")->execute([':email' => $email]);
    }
    
    $pdo->prepare("DELETE FROM members WHERE id = :id")->execute([':id' => $delId]);
    $success = 'Member record and associated registrations deleted.';
}

// ---- ADD member ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name  = trim(htmlspecialchars($_POST['full_name']  ?? ''));
    $sid   = trim(htmlspecialchars($_POST['student_id'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    $club  = trim(htmlspecialchars($_POST['club']       ?? ''));

    if ($name  === '') $errors['full_name']  = 'Full name is required.';
    if ($sid   === '') $errors['student_id'] = 'Student ID is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email required.';

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO members (student_id, full_name, email, club) VALUES (:sid, :name, :email, :club)");
            $stmt->execute([':sid' => $sid, ':name' => $name, ':email' => $email, ':club' => $club]);
            $success = 'New member added successfully.';
        } catch (PDOException $e) {
            $errors['db'] = 'Student ID or email already exists.';
        }
    }
}

// ---- Search ----
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql    = "SELECT * FROM members WHERE 1=1";
$params = [];
if ($search !== '') {
    $sql .= " AND (full_name LIKE :s OR student_id LIKE :s2 OR club LIKE :s3)";
    $params[':s']  = "%$search%";
    $params[':s2'] = "%$search%";
    $params[':s3'] = "%$search%";
}
$sql .= " ORDER BY full_name ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

function initials(string $name): string
{
    $words = explode(' ', $name);
    return strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
}
?>

<div class="section-header">
    <div>
        <h1>Member Directory</h1>
        <p>Manage student member profiles</p>
    </div>
    <button class="btn btn-primary" onclick="toggleForm()">+ Add Member</button>
</div>

<?php if ($success): ?><div class="success-msg">&#10003; <?php echo $success; ?></div><?php endif; ?>
<?php if (isset($errors['db'])): ?><div class="error-box"><?php echo $errors['db']; ?></div><?php endif; ?>

<!-- Add Member Form (toggled) -->
<div class="card" id="add-form" style="display:<?php echo !empty($errors) ? 'block' : 'none'; ?>">

    <h2>Add New Member</h2>
    <form method="POST" action="members.php">
        <input type="hidden" name="action" value="add">
        <div class="form-row">
            <div class="form-group">
                <label>Full Name *</label>
                <input type="text" name="full_name" placeholder="Full name" value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                <?php if (isset($errors['full_name'])): ?><div class="error-msg show"><?php echo $errors['full_name']; ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Student ID *</label>
                <input type="text" name="student_id" placeholder="SID-XXXX" value="<?php echo htmlspecialchars($_POST['student_id'] ?? ''); ?>">
                <?php if (isset($errors['student_id'])): ?><div class="error-msg show"><?php echo $errors['student_id']; ?></div><?php endif; ?>
            </div>

        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" placeholder="email@campus.edu" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                <?php if (isset($errors['email'])): ?><div class="error-msg show"><?php echo $errors['email']; ?></div><?php endif; ?>
            </div>
            <div class="form-group">
                <label>Club</label>
                <select name="club">
                    <?php foreach (['Photography', 'Debate', 'Coding', 'Sports', 'Drama'] as $c): ?>
                        <option <?php echo ($_POST['club'] ?? '') === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>
        <div style="display:flex;gap:8px">
            <button type="submit" class="btn btn-primary">Save Member</button>
            <button type="button" class="btn" onclick="toggleForm()">Cancel</button>
        </div>
    </form>
</div>

<!-- Search -->
<form method="GET" action="members.php">
    <div class="search-bar">
        <input type="text" name="search" placeholder="Search by name, ID or club…" value="<?php echo htmlspecialchars($search); ?>">
        <button type="submit" class="btn btn-primary">Search</button>
        <a href="members.php" class="btn">Clear</a>
    </div>
</form>

<!-- Members Table -->
<div class="card" style="padding:0">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Student ID</th>
                    <th>Email</th>
                    <th>Club</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($members): ?>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td>
                                <div style="display:flex;align-items:center;gap:10px">
                                    <div style="width:34px;height:34px;border-radius:50%;background:#E1F5EE;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:600;color:#0F6E56">
                                        <?php echo initials($m['full_name']); ?>
                                    </div>
                                    <?php echo htmlspecialchars($m['full_name']); ?>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($m['student_id']); ?></td>
                            <td><?php echo htmlspecialchars($m['email']); ?></td>
                            <td><span class="tag tag-green"><?php echo htmlspecialchars($m['club']); ?></span></td>
                            <td>
                                <span class="tag <?php echo $m['status'] === 'Active' ? 'tag-green' : 'tag-amber'; ?>">
                                    <?php echo $m['status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="members.php?delete=<?php echo $m['id']; ?>"
                                    class="btn btn-danger btn-sm"
                                    onclick="return confirm('Delete this member?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align:center;color:#888;padding:24px">No members found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleForm() {
        const f = document.getElementById('add-form');
        f.style.display = f.style.display === 'none' ? 'block' : 'none';
    }
</script>

<?php require_once 'includes/footer.php'; ?>