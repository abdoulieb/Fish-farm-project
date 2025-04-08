<?php
require_once 'auth.php';
require_once 'config.php';

if (!isAdmin()) {
    header("Location: index.php");
    exit();
}

$partner = [];
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM partners WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $partner = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $website = $_POST['website'] ?? null;

    // Handle file upload if new logo is provided
    $logo_url = $partner['logo_url'];
    if (!empty($_FILES['logo']['name'])) {
        $targetDir = "uploads/partners/";
        $fileName = basename($_FILES["logo"]["name"]);
        $targetFile = $targetDir . uniqid() . '_' . $fileName;

        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $targetFile)) {
            // Delete old logo if exists
            if ($logo_url && file_exists($logo_url)) {
                unlink($logo_url);
            }
            $logo_url = $targetFile;
        }
    }

    $stmt = $pdo->prepare("UPDATE partners SET name = ?, logo_url = ?, website = ? WHERE id = ?");
    $stmt->execute([$name, $logo_url, $website, $id]);

    $_SESSION['message'] = "Partner updated successfully!";
    header("Location: admin_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Partner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <h2>Edit Partner</h2>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?= $partner['id'] ?>">

            <div class="mb-3">
                <label class="form-label">Organization Name</label>
                <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($partner['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label class="form-label">Current Logo</label>
                <?php if ($partner['logo_url']): ?>
                    <img src="<?= htmlspecialchars($partner['logo_url']) ?>" class="img-thumbnail mb-2" style="max-height: 150px;">
                <?php endif; ?>
                <input type="file" class="form-control" name="logo" accept="image/*">
                <small class="text-muted">Leave blank to keep current logo</small>
            </div>

            <div class="mb-3">
                <label class="form-label">Website URL</label>
                <input type="url" class="form-control" name="website" value="<?= htmlspecialchars($partner['website']) ?>">
            </div>

            <button type="submit" class="btn btn-primary">Update</button>
            <a href="admin_dashboard.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>

</html>