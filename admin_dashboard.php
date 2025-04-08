<?php
require_once 'auth.php';

if (!isAdmin()) {
    header("Location: in.php");
    exit();
}

// Get all editable images
$fishTypes = $pdo->query("SELECT id, name, image_path FROM fish_types")->fetchAll();
$teamMembers = $pdo->query("SELECT id, name, photo_url FROM team_members")->fetchAll();
$partners = $pdo->query("SELECT id, name, logo_url FROM partners")->fetchAll();

// Display messages
if (isset($_SESSION['message'])) {
    echo '<div class="alert alert-success">' . $_SESSION['message'] . '</div>';
    unset($_SESSION['message']);
}
if (isset($_SESSION['error'])) {
    echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
    unset($_SESSION['error']);
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Image Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .image-card {
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .image-preview {
            height: 150px;
            object-fit: cover;
            width: 100%;
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-arrow-left"></i> Back to Site
            </a>
        </div>
    </nav>

    <div class="container mt-4">
        <h2>Image Management</h2>

        <div class="row mt-4">
            <div class="col-12">
                <h4>Fish Products</h4>
                <div class="row">
                    <?php foreach ($fishTypes as $fish): ?>
                        <div class="col-md-4">
                            <div class="card image-card">
                                <div class="card-body">
                                    <h5><?= htmlspecialchars($fish['name']) ?></h5>
                                    <?php if ($fish['image_path']): ?>
                                        <img src="<?= htmlspecialchars($fish['image_path']) ?>" class="image-preview mb-2">
                                    <?php endif; ?>
                                    <form action="update_image.php" method="POST" enctype="multipart/form-data">
                                        <input type="hidden" name="item_id" value="<?= $fish['id'] ?>">
                                        <input type="hidden" name="item_type" value="fish">
                                        <div class="mb-3">
                                            <input type="file" class="form-control" name="image" accept="image/*">
                                        </div>
                                        <button type="submit" class="btn btn-primary w-100">Update</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <!-- Add this to your admin.php file -->
        <div class="row mt-5">
            <!-- Team Member Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Add Team Member</h5>
                    </div>
                    <div class="card-body">
                        <form action="add_team_member.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Position</label>
                                <input type="text" class="form-control" name="position" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Photo</label>
                                <input type="file" class="form-control" name="photo" accept="image/*" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Facebook URL (optional)</label>
                                <input type="url" class="form-control" name="facebook">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Twitter URL (optional)</label>
                                <input type="url" class="form-control" name="twitter">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">LinkedIn URL (optional)</label>
                                <input type="url" class="form-control" name="linkedin">
                            </div>
                            <button type="submit" class="btn btn-primary">Add Team Member</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Partner Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Add Partner</h5>
                    </div>
                    <div class="card-body">
                        <form action="add_partner.php" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Organization Name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Logo</label>
                                <input type="file" class="form-control" name="logo" accept="image/*" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Website URL (optional)</label>
                                <input type="url" class="form-control" name="website">
                            </div>
                            <button type="submit" class="btn btn-primary">Add Partner</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <section class="py-5 team-section" id="team">
            <div class="container">
                <h2 class="text-center section-title">Our Team</h2>
                <p class="text-center mb-5 lead">Meet the dedicated professionals behind our success</p>
                <div class="row g-4">
                    <?php
                    $teamMembers = $pdo->query("SELECT * FROM team_members ORDER BY position_order")->fetchAll();
                    foreach ($teamMembers as $member): ?>
                        <div class="col-md-4">
                            <div class="team-card">
                                <img src="<?= htmlspecialchars($member['photo_url']) ?>" class="team-img" alt="<?= htmlspecialchars($member['name']) ?>">
                                <div class="team-overlay">
                                    <h4><?= htmlspecialchars($member['name']) ?></h4>
                                    <p class="mb-1"><?= htmlspecialchars($member['position']) ?></p>
                                    <div class="social-links mt-2">
                                        <?php if (!empty($member['facebook'])): ?>
                                            <a href="<?= htmlspecialchars($member['facebook']) ?>" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($member['twitter'])): ?>
                                            <a href="<?= htmlspecialchars($member['twitter']) ?>" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                                        <?php endif; ?>
                                        <?php if (!empty($member['linkedin'])): ?>
                                            <a href="<?= htmlspecialchars($member['linkedin']) ?>" class="text-white"><i class="fab fa-linkedin-in"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <section class="py-5" id="partners">
            <div class="container">
                <h2 class="text-center section-title">Our Partners</h2>
                <p class="text-center mb-5 lead">Trusted organizations we collaborate with</p>
                <div class="row g-4">
                    <?php
                    $partners = $pdo->query("SELECT * FROM partners ORDER BY name")->fetchAll();
                    foreach ($partners as $partner): ?>
                        <div class="col-md-3 col-6">
                            <div class="text-center p-3">
                                <?php if (!empty($partner['logo_url'])): ?>
                                    <img src="<?= htmlspecialchars($partner['logo_url']) ?>" class="partner-logo" alt="<?= htmlspecialchars($partner['name']) ?>">
                                <?php else: ?>
                                    <div class="partner-logo-placeholder bg-light d-flex align-items-center justify-content-center" style="height: 100px;">
                                        <span class="text-muted"><?= htmlspecialchars($partner['name']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <h5 class="mt-3 text-center"><?= htmlspecialchars($partner['name']) ?></h5>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <!-- Existing Team Members -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Current Team Members</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Photo</th>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($teamMembers as $member): ?>
                                        <tr>
                                            <td><img src="<?= htmlspecialchars($member['photo_url']) ?>" style="height:50px;"></td>
                                            <td><?= htmlspecialchars($member['name']) ?></td>
                                            <td><?= htmlspecialchars($member['position']) ?></td>
                                            <td>
                                            <td>
                                                <a href="edit_team_member.php?id=<?= $member['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <a href="delete_team_member.php?id=<?= $member['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this team member?')">Delete</a>
                                            </td>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Existing Partners -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5>Current Partners</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Logo</th>
                                        <th>Name</th>
                                        <th>Website</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($partners as $partner): ?>
                                        <tr>
                                            <td><img src="<?= htmlspecialchars($partner['logo_url']) ?>" style="height:50px;"></td>
                                            <td><?= htmlspecialchars($partner['name']) ?></td>
                                            <td><?= !empty($partner['website']) ? htmlspecialchars($partner['website']) : 'N/A' ?></td>
                                            <td>
                                                <a href="edit_partner.php?id=<?= $partner['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <a href="delete_partner.php?id=<?= $partner['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this partner?')">Delete</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Repeat similar sections for Team Members and Partners -->

    </div>
</body>

</html>