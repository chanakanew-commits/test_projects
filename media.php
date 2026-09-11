<?php

require_once 'includes/database.php';
require_once 'includes/header.php';

$success = '';
$error   = '';

// ---- Handle the media uploads ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim(htmlspecialchars($_POST['title'] ?? ''));

    if ($title === '') {
        $error = 'Please enter a media title.';
    } elseif (!isset($_FILES['media_file']) || $_FILES['media_file']['error'] !== UPLOAD_ERR_OK) {
        $error = 'Please select a file to upload.';
    } else {
        $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'video/mp4', 'audio/mpeg'];
        $maxSize  = 50 * 1024 * 1024; // 50 MB
        $fileType = mime_content_type($_FILES['media_file']['tmp_name']);
        $fileSize = $_FILES['media_file']['size'];

        if (!in_array($fileType, $allowed)) {
            $error = 'Allowed types: JPG, PNG, MP4, MP3.';
        } elseif ($fileSize > $maxSize) {
            $error = 'File must be smaller than 50 MB.';
        } else {
            $ext      = pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION);
            $fileName = 'media_' . time() . '.' . strtolower($ext);
            $dest     = __DIR__ . '/uploads/' . $fileName;

            if (move_uploaded_file($_FILES['media_file']['tmp_name'], $dest)) {
                $stmt = $pdo->prepare("INSERT INTO media (title, file_name, file_type, uploaded_by) VALUES (:t, :f, :ft, :u)");
                $stmt->execute([':t' => $title, ':f' => $fileName, ':ft' => $fileType, ':u' => 'Student']);
                $success = 'Media uploaded successfully!';
            } else {
                $error = 'Upload failed. Check folder permissions.';
            }
        }
    }
}

// uploaded media
$media = $pdo->query("SELECT * FROM media ORDER BY uploaded_at DESC")->fetchAll();
?>

<h1>Media Gallery</h1>
<p style="color:#666;margin-bottom:20px">Photos, videos and multimedia content</p>

<?php if ($success): ?><div class="success-msg">&#10003; <?php echo $success; ?></div><?php endif; ?>
<?php if ($error):   ?><div class="error-box">&#10007; <?php echo $error; ?></div><?php endif; ?>

<!-- Upload Form -->
<div class="card">
    <h2>&#128247; Upload Media</h2>
    <form method="POST" action="media.php" enctype="multipart/form-data">
        <div class="form-group">
            <label>Media Title *</label>
            <input type="text" name="title" placeholder="e.g. Sports Day 2025 Highlights">
        </div>
        <div class="upload-zone" onclick="document.getElementById('media_file').click()">
            <div style="font-size:36px">&#127916;</div>
            <p>Click to select (JPG, PNG, MP4, MP3 &mdash; max 50 MB)</p>
            <p id="chosen-file" style="color:#1D9E75;font-size:13px;margin-top:6px"></p>
        </div>
        <input type="file" id="media_file" name="media_file"
            accept=".jpg,.jpeg,.png,.gif,.mp4,.mp3"
            style="display:none"
            onchange="document.getElementById('chosen-file').textContent = this.files[0]?.name || ''">
        <br>
        <button type="submit" class="btn btn-primary">&#8679; Upload</button>
    </form>
</div>

<!-- Multimedia: Audio & Video -->
<div class="card">
    <h2>&#127925; Multimedia Features</h2>
    <p style="font-size:13px;color:#666;margin-bottom:14px">
        CampusHub supports HTML5 audio and video elements for event coverage.
    </p>

    <h3>&#127897; Sample Audio (HTML5 &lt;audio&gt;)</h3>
    <audio controls style="width:100%;margin:10px 0 18px">
        <source src="uploads/sample.mp3" type="audio/mpeg">
        Your browser does not support the audio element.
    </audio>

    <h3>&#127916; Sample Video (HTML5 &lt;video&gt;)</h3>
    <video controls width="100%" style="border-radius:8px;margin-top:10px;background:#000;max-height:300px">
        <source src="uploads/sample.mp4" type="video/mp4">
        Your browser does not support the video element.
    </video>
    <p style="font-size:11px;color:#aaa;margin-top:6px">
        Place <code>sample.mp3</code> and <code>sample.mp4</code> in the <code>/uploads/</code> folder to enable playback.
    </p>
</div>

<!-- Uploaded Media Gallery -->
<div class="card">
    <h2>&#128444; Uploaded Gallery</h2>
    <?php if ($media): ?>
        <div class="media-grid">
            <?php foreach ($media as $m):
                $isImage = str_starts_with($m['file_type'], 'image/');
                $isVideo = str_starts_with($m['file_type'], 'video/');
                $isAudio = str_starts_with($m['file_type'], 'audio/');
                $path    = 'uploads/' . $m['file_name'];
            ?>
                <div class="media-thumb">
                    <?php if ($isImage): ?>
                        <img src="<?php echo htmlspecialchars($path); ?>"
                            alt="<?php echo htmlspecialchars($m['title']); ?>"
                            style="width:100%;height:100%;object-fit:cover;border-radius:10px">
                    <?php elseif ($isVideo): ?>
                        <div style="font-size:36px">&#127916;</div>
                        <span><?php echo htmlspecialchars($m['title']); ?></span>
                    <?php elseif ($isAudio): ?>
                        <div style="font-size:36px">&#127925;</div>
                        <span><?php echo htmlspecialchars($m['title']); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color:#888;font-size:13px">No media uploaded yet.</p>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>