<?php
require_once 'auth.php';
require_once 'config.php';

if (!isAdmin()) {
    header("Location: index.php");
    exit();
}

$member = [];
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM team_members WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $member = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $position = $_POST['position'];
    $facebook = $_POST['facebook'] ?? null;
    $twitter = $_POST['twitter'] ?? null;
    $linkedin = $_POST['linkedin'] ?? null;

    // Handle file upload if new photo is provided
    $photo_url = $member['photo_url'];
    if (!empty($_FILES['photo']['name'])) {
        $targetDir = "uploads/team/";
        $fileName = basename($_FILES["photo"]["name"]);
        $targetFile = $targetDir . uniqid() . '_' . $fileName;

        if (move_uploaded_file($_FILES["photo"]["tmp_name"], $targetFile)) {
            // Delete old photo if exists
            if ($photo_url && file_exists($photo_url)) {
                unlink($photo_url);
            }
            $photo_url = $targetFile;
        }
    }

    $stmt = $pdo->prepare("UPDATE team_members SET name = ?, position = ?, photo_url = ?, facebook = ?, twitter = ?, linkedin = ? WHERE id = ?");
    $stmt->execute([$name, $position, $photo_url, $facebook, $twitter, $linkedin, $id]);

    $_SESSION['message'] = "Team member updated successfully!";
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Team Member</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h2>Edit Team Member</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $member['id'] ?>">

            <div class="mb-3">
                <label class="form-label">Name</label>
                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($member['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Position</label>
                <input type="text" class="form-control" name="position" value="<?= htmlspecialchars($member['position']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Current Photo</label>
                <?php if ($member['photo_url']): ?>
                    <img src="<?= htmlspecialchars($member['photo_url']) ?>" class="img-thumbnail mb-2" style="max-height: 150px;">
                <?php endif; ?>
                <input type="file" class="form-control" name="photo" accept="image/*">
                <small class="text-muted">Leave blank to keep current photo</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Facebook URL</label>
                <input type="url" class="form-control" name="facebook" value="<?= htmlspecialchars($member['facebook']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Twitter URL</label>
                <input type="url" class="form-control" name="twitter" value="<?= htmlspecialchars($member['twitter']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">LinkedIn URL</label>
                <input type="url" class="form-control" name="linkedin" value="<?= htmlspecialchars($member['linkedin']) ?>">
            </div>

            <button type="submit" class="btn btn-primary">Update</button>
            <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>

</html>