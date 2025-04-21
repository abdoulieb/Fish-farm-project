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
            <a class="navbar-brand" href="dashboard.php">
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
        <!-- Add Video Form -->
        <div class="mb-5">
            <h6>Add New Video</h6>
            <form action="add_video.php" method="POST" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Title*</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Video URL (YouTube or direct link)</label>
                        <input type="url" class="form-control" name="video_url">
                        <small class="text-muted">OR upload a video file below</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Upload Video File</label>
                        <input type="file" class="form-control" name="video_file" accept="video/*">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Thumbnail URL (optional)</label>
                        <input type="url" class="form-control" name="thumbnail_url">
                        <small class="text-muted">OR upload a thumbnail image below</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Upload Thumbnail Image</label>
                        <input type="file" class="form-control" name="thumbnail_file" accept="image/*">
                    </div>
                    <div class="col-md-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mt-4 pt-2">
                            <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured">
                            <label class="form-check-label" for="is_featured">
                                Set as featured video
                            </label>
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Add Video</button>
                    </div>
                </div>
            </form>
        </div>
        <!-- Current Videos -->
        <h6>Current Videos</h6>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Thumbnail</th>
                        <th>Title</th>
                        <th>Featured</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    function getAllVideos()
                    {
                        global $pdo;
                        return $pdo->query("SELECT * FROM videos ORDER BY created_at DESC")->fetchAll();
                    }
                    $videos = getAllVideos();
                    foreach ($videos as $video):
                        // Extract YouTube video ID for thumbnail
                        $videoId = '';
                        if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video['video_url'], $matches)) {
                            $videoId = $matches[1];
                        }
                    ?>
                        <tr>
                            <td>
                                <?php if ($videoId): ?>
                                    <img src="https://img.youtube.com/vi/<?= $videoId ?>/mqdefault.jpg" width="80">
                                <?php elseif ($video['thumbnail_url']): ?>
                                    <img src="<?= htmlspecialchars($video['thumbnail_url']) ?>" width="80">
                                <?php else: ?>
                                    <i class="fas fa-video text-muted"></i>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($video['title']) ?></td>
                            <td>
                                <?php if ($video['is_featured']): ?>
                                    <span class="badge bg-success">Featured</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Regular</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_video.php?id=<?= $video['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="delete_video.php?id=<?= $video['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this video?')">Delete</a>
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

    <div class="tab-pane fade" id="shops" role="tabpanel">
        <div class="card mt-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5>All Shop Locations</h5>
                <button id="refreshShops" class="btn btn-sm btn-light">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <div class="card-body">
                <table class="table table-striped" id="shopsTable">
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th>Region</th>
                            <th>Employee</th>
                            <th>Contact</th>
                            <th>Hours</th>
                            <th>Status</th>
                            <th>Last Updated</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $allShops = $pdo->query("
                        SELECT sl.*, u.username as employee_name 
                        FROM shop_locations sl
                        JOIN users u ON sl.employee_id = u.id
                        ORDER BY sl.region, sl.location_name
                    ")->fetchAll();

                        foreach ($allShops as $shop):
                            $currentTime = date('H:i:s');
                            $isOpenNow = ($currentTime >= $shop['opening_time'] && $currentTime <= $shop['closing_time']) && $shop['is_open'];
                        ?>
                            <tr data-shop-id="<?= $shop['id'] ?>">
                                <td><?= htmlspecialchars($shop['location_name']) ?></td>
                                <td><?= htmlspecialchars($shop['region']) ?></td>
                                <td><?= htmlspecialchars($shop['employee_name']) ?></td>
                                <td><?= htmlspecialchars($shop['contact_phone']) ?></td>
                                <td class="shop-hours">
                                    <?= date('g:i A', strtotime($shop['opening_time'])) ?> -
                                    <?= date('g:i A', strtotime($shop['closing_time'])) ?>
                                </td>
                                <td>
                                    <span class="badge <?= $isOpenNow ? 'bg-success' : 'bg-danger' ?> shop-status">
                                        <?= $isOpenNow ? 'Open' : 'Closed' ?>
                                    </span>
                                </td>
                                <td><?= date('M j, g:i a', strtotime($shop['updated_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Function to refresh shop status in admin panel
        function refreshShopStatus() {
            fetch('get_shop_status.php')
                .then(response => response.json())
                .then(data => {
                    data.forEach(shop => {
                        const currentTime = new Date();
                        const shopOpenTime = new Date();
                        const shopCloseTime = new Date();

                        // Set open and close times
                        const [openHours, openMinutes] = shop.opening_time.split(':');
                        const [closeHours, closeMinutes] = shop.closing_time.split(':');

                        shopOpenTime.setHours(openHours, openMinutes, 0);
                        shopCloseTime.setHours(closeHours, closeMinutes, 0);

                        // Check if currently open
                        const isOpenNow = shop.is_open &&
                            currentTime >= shopOpenTime &&
                            currentTime <= shopCloseTime;

                        // Update the table row
                        const row = document.querySelector(`tr[data-shop-id="${shop.id}"]`);
                        if (row) {
                            const statusCell = row.querySelector('.shop-status');
                            if (statusCell) {
                                statusCell.textContent = isOpenNow ? 'Open' : 'Closed';
                                statusCell.className = isOpenNow ? 'badge bg-success' : 'badge bg-danger';
                            }

                            const hoursCell = row.querySelector('.shop-hours');
                            if (hoursCell) {
                                hoursCell.textContent = `${formatTime(shop.opening_time)} - ${formatTime(shop.closing_time)}`;
                            }
                        }
                    });
                })
                .catch(error => console.error('Error fetching shop status:', error));
        }

        // Helper function to format time
        function formatTime(timeString) {
            const [hours, minutes] = timeString.split(':');
            const hour = parseInt(hours);
            const ampm = hour >= 12 ? 'PM' : 'AM';
            const displayHour = hour % 12 || 12;
            return `${displayHour}:${minutes} ${ampm}`;
        }

        // Manual refresh button
        document.getElementById('refreshShops').addEventListener('click', refreshShopStatus);

        // Auto-refresh every 5 minutes
        setInterval(refreshShopStatus, 300000);
    </script>
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