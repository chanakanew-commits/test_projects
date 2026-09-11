<?php

require_once 'includes/database.php';
require_once 'includes/header.php';

$events = $pdo->query("SELECT * FROM events ORDER BY event_date ASC")->fetchAll();

$dom  = new DOMDocument('1.0', 'UTF-8');
$dom->formatOutput = true;

$root   = $dom->createElement('campushub');
$dom->appendChild($root);

$eventsEl = $dom->createElement('events');
$root->appendChild($eventsEl);

foreach ($events as $e) {
    $eventEl = $dom->createElement('event');
    $eventEl->setAttribute('id', $e['id']);

    $fields = ['title', 'type', 'description', 'event_date', 'location', 'total_slots', 'registered'];
    foreach ($fields as $f) {
        $child = $dom->createElement($f, htmlspecialchars($e[$f]));
        $eventEl->appendChild($child);
    }
    $eventsEl->appendChild($eventEl);
}

$xmlString = $dom->saveXML();

// Save XML file
file_put_contents(__DIR__ . '/xml/events.xml', $xmlString);
?>

<h1>XML Implementation</h1>
<p style="color:#666;margin-bottom:20px">CampusHub generates and validates XML data for system integration.</p>

<!-- XML Output -->
<div class="card">
    <h2>&#128196; Generated XML — Events Feed</h2>
    <p style="font-size:13px;color:#666;margin-bottom:12px">
        Built dynamically from the database using PHP's <code>DOMDocument</code>. Saved to <code>/xml/events.xml</code>.
    </p>
    <pre class="code-block"><?php echo htmlspecialchars($xmlString); ?></pre>
    <br>
    <a href="xml/events.xml" class="btn" target="_blank">&#128196; View Raw XML File</a>
</div>

<!-- PHP Code for XML -->
<div class="card">
    <h2>&#128196; PHP Code — XML Generation</h2>
    <pre class="code-block"><?php echo htmlspecialchars('<?php
$dom  = new DOMDocument("1.0", "UTF-8");
$dom->formatOutput = true;

$root     = $dom->createElement("campushub");
$eventsEl = $dom->createElement("events");
$root->appendChild($eventsEl);
$dom->appendChild($root);

foreach ($events as $e) {
    $eventEl = $dom->createElement("event");
    $eventEl->setAttribute("id", $e["id"]);

    $titleEl = $dom->createElement("title", $e["title"]);
    $typeEl  = $dom->createElement("type",  $e["type"]);
    $eventEl->appendChild($titleEl);
    $eventEl->appendChild($typeEl);
    $eventsEl->appendChild($eventEl);
}

$xml = $dom->saveXML();
file_put_contents("events.xml", $xml);
?>'); ?></pre>
</div>

<!-- DB Connection -->
<div class="card">
    <h2>&#128279; PHP Database Connectivity (PDO)</h2>
    <pre class="code-block"><?php echo htmlspecialchars('<?php
// Connection
$pdo = new PDO("mysql:host=localhost;dbname=campushub_db;charset=utf8", "root", "MySQL@1908");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// SELECT
$stmt = $pdo->query("SELECT * FROM events ORDER BY event_date ASC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// INSERT (prepared statement — prevents SQL injection)
$sql  = "INSERT INTO registrations (fname, lname, email, student_id, event_id)
         VALUES (:fname, :lname, :email, :sid, :event_id)";
$stmt = $pdo->prepare($sql);
$stmt->execute([":fname"=>$fname, ":lname"=>$lname,
                ":email"=>$email, ":sid"=>$sid, ":event_id"=>$eventId]);

// UPDATE
$pdo->prepare("UPDATE members SET status=:s WHERE id=:id")
    ->execute([":s"=>"Active", ":id"=>$id]);

// DELETE
$pdo->prepare("DELETE FROM registrations WHERE id=:id")
    ->execute([":id"=>$delId]);
?>'); ?></pre>
</div>


<div class="card">
    <h2>&#128683; Error Handling &amp; Validation</h2>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
        <div>
            <h3 style="color:#3b6d11;margin-bottom:8px">&#10003; Valid Examples</h3>
            <pre class="code-block" style="font-size:11px"><?php echo htmlspecialchars(
                
'// Check required field
if (empty($_POST["fname"])) {
    $errors[] = "First name required.";
}

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email.";
}

// Sanitize output
echo htmlspecialchars($userInput);

// File type check
$allowed = ["image/jpeg","image/png"];
if (!in_array($type, $allowed)) {
    $errors[] = "File type not allowed.";
}'
 ); ?></pre>
        </div>
        <div>
            <h3 style="color:#a32d2d;margin-bottom:8px">&#10007; Caught Errors</h3>
            <pre class="code-block" style="font-size:11px"><?php echo htmlspecialchars(

'// PDO exception handling
try {
    $pdo = new PDO($dsn, $user, $pass);
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

// Upload error check
if ($_FILES["file"]["error"] !== UPLOAD_ERR_OK) {
    $error = "Upload failed.";
}

// File size limit
if ($_FILES["file"]["size"] > 2097152) {
    $error = "Max file size is 2 MB.";
}'
 ); ?></pre>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>