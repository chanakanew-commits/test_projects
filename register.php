<?php

require_once 'includes/database.php';
require_once 'includes/header.php';

$success = '';
$errors = [];
$bid = [];


// Pre-select event from URL parameter
$preEventId = (int)($_GET['event_id'] ?? 0);
$events = $pdo->query("SELECT id, title FROM events ORDER BY event_date ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $old['fname'] = $fname = trim(htmlspecialchars($_POST['fname'] ?? ''));
    $old['lname'] = $lname = trim(htmlspecialchars($_POST['lname'] ?? ''));
    $old['email'] = $email = trim(($_POST['email'] ?? ''));
    $old['sid'] = $sid = trim(htmlspecialchars($_POST['sid'] ?? ''));
    $old['event_id'] = $eventId = (int)($_POST['event_id'] ?? 0);
    $old['notes'] = $notes = trim(htmlspecialchars($_POST['notes'] ?? ''));

    if ($fname === '') $errors['fname'] = 'First name is required.';
    if ($lname === '') $errors['lname'] = 'Last name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email is required.';
    if ($sid === '') $errors['sid'] = 'Student ID is required.';
    if ($eventId <= 0) $errors['event_id'] = 'Please select an event.';

    // File upload validation
    $fileName = '';
    if (isset($_FILES['student_file']) && $_FILES['student_file']['error'] === UPLOAD_ERR_OK) {

        $allowed = ['image/jpeg', 'image/png', 'application.pdf'];
        $maxSize = 5 * 1024 * 1024; //5mb max
        $fileType = mime_content_type($_FILES['student_file']['tmp_name']);
        $fileSize = $_FILES['student_file']['size'];

        if (!in_array($fileType, $allowed)) {
            $errors['file'] = 'only JPG,PNG and PDF files are allowed';
        } elseif ($fileSize > $maxSize) {
            $errors['file'] = 'File must be smeller than 5 MB';
        } else {
            $ext = pathinfo($_FILES['student_file']['name'], PATHINFO_EXTENSION);
            $fileName = 'upload_' . time() . '_' . rand(100, 999) . '_' . strtolower($ext);
            $dest = __DIR__ . '/uploads/' . $fileName;
            if (!move_uploaded_file($_FILES['student_file']['tmp_name'], $dest)) {
                $errors['file'] = 'File upload failed. Please try again';
            }
        }
    }

    if (empty($errors)) {
        // Check for duplicate registration manually
        $check = $pdo->prepare("SELECT id FROM registrations WHERE student_id = :sid AND event_id = :eid");
        $check->execute([':sid' => $sid, ':eid' => $eventId]);
        if ($check->fetch()) {
            $errors['sid'] = 'This Student ID is already registered for this event.';
        }
    }

    if (empty($errors)) {

        try {
            $sql = "INSERT INTO `registrations` (`fname`, `lname`, `email`, `student_id`, `event_id`, `notes`, `file_name`) VALUES (:fname, :lname, :email, :sid, :event_id, :notes, :file_name)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':fname'     => $fname,
                ':lname'     => $lname,
                ':email'     => $email,
                ':sid'       => $sid,
                ':event_id'  => $eventId,
                ':notes'     => $notes,
                ':file_name' => $fileName,
            ]);

            // Update registered count
            $pdo->prepare("UPDATE events SET registered = registered + 1 WHERE id = :id")->execute([':id' => $eventId]);
            $success = "Registration submitted successfully! We will contact you at $email.";
            $old = []; // Clear the form
        } catch (PDOException $e) {
            $errors['db'] = 'You have already registered for this event with this Student ID/Email.';
        }
    }
}

?>

<div class="section-header">
    <div>
        <h1>Event Registration</h1>
        <p>Fill in the form to register for an event</p>
    </div>
</div>

<?php if ($success): ?>
    <div class="success-msg">&#10003; <?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($errors['db'])): ?>
    <div class="error-box"><?php echo $errors['db']; ?></div>
<?php elseif (!empty($errors)): ?>
    <div class="error-box">Please fix the errors highlighted below and try again.</div>
<?php endif; ?>


<form method="POST" action="register.php" enctype="multipart/form-data">

    <!-- Personal Details -->
    <div class="card">
        <h2>Personal Details</h2>
        <div class="form-row">
            <div class="form-group">
                <label for="fname">First Name *</label>
                <input type="text" id="fname" name="fname" placeholder="e.g. first name"
                    value="<?php echo $old['fname'] ?? ''; ?>">
                <?php if (isset($errors['fname'])): ?>
                    <div class="error-msg show"><?php echo $errors['fname']; ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group">
                <label for="lname">Last Name *</label>
                <input type="text" id="lname" name="lname" placeholder="last name"
                    value="<?php echo $old['lname'] ?? ''; ?>">
                <?php if (isset($errors['lname'])): ?>
                    <div class="error-msg show"><?php echo $errors['lname']; ?></div>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" placeholder="student@mail.com"
                value="<?php echo $old['email'] ?? ''; ?>">
            <?php if (isset($errors['email'])): ?>
                <div class="error-msg show"><?php echo $errors['email']; ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="sid">Student ID *</label>
            <input type="text" id="sid" name="sid" placeholder="e.g. SID-01234567"
                value="<?php echo $old['sid'] ?? ''; ?>">
            <?php if (isset($errors['sid'])): ?>
                <div class="error-msg show"><?php echo $errors['sid']; ?></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Event Selection -->
    <div class="card">
        <h2>Event Selection</h2>
        <div class="form-group">
            <label for="event_id">Select Event *</label>
            <select id="event_id" name="event_id">
                <option value="">— choose an event —</option>
                <?php foreach ($events as $e): ?>
                    <option value="<?php echo $e['id']; ?>"
                      <?php if (($old['event_id'] ?? $preEventId) == $e['id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($e['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (isset($errors['event_id'])): ?>
                <div class="error-msg show"><?php echo $errors['event_id']; ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="notes">Accessibility Requirements</label>
            <textarea id="notes" name="notes" rows="2"
                placeholder="Any special requirements? (optional)"><?php echo $old['notes'] ?? ''; ?></textarea>
        </div>
    </div>

    <!-- File Upload -->
    <div class="card">
        <h2>Upload Student ID (optional)</h2>
        <div class="upload-zone" onclick="document.getElementById('student_file').click()">
            <div style="font-size:32px">&#128196;</div>
            <p>Click to select file &nbsp;(JPG, PNG, PDF &mdash; max 2 MB)</p>
        </div>
        <input type="file" id="student_file" name="student_file"
            accept=".jpg,.jpeg,.png,.pdf"
            style="display:none"
            onchange="document.getElementById('file-name-display').textContent = this.files[0]?.name || ''">
        <p id="file-name-display" style="font-size:13px;color:#1D9E75;margin-top:6px"></p>
        <?php if (isset($errors['file'])): ?>
            <div class="error-msg show"><?php echo $errors['file']; ?></div>
        <?php endif; ?>
    </div>

    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:13px;font-size:15px">
        &#10003; Submit Registration
    </button>
</form>

<?php require_once 'includes/footer.php'; ?>