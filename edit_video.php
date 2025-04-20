<?php
require_once 'auth.php';
require_once 'functions.php';

if (!isAdmin()) {
    $_SESSION['error'] = "You don't have permission to access this page";
    header("Location: admin_dashboard.php");
    exit();
}

$videoId = $_GET['id'] ?? 0;
$video = $pdo->prepare("SELECT * FROM videos WHERE id = ?");
$video->execute([$videoId]);
$video = $video->fetch();

if (!$video) {
    $_SESSION['error'] = "Video not found";
    header("Location: admin_dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;

    if (empty($title)) {
        $_SESSION['error'] = "Title is required";
        header("Location: edit_video.php?id=" . $videoId);
        exit();
    }

    if (updateVideo($videoId, $title, $description, $isFeatured)) {
        $_SESSION['message'] = "Video updated successfully!";
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to update video";
        header("Location: edit_video.php?id=" . $videoId);
        exit();
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Video</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h2>Edit Video</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Title</label>
                <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($video['title']) ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3"><?= htmlspecialchars($video['description']) ?></textarea>
            </div>
            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" <?= $video['is_featured'] ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_featured">
                        Featured Video
                    </label>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Update Video</button>
            <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>

</html>