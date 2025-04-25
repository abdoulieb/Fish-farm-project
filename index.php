<?php
require_once 'config.php';

// Define $isAdmin based on your application's logic
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

// Get fish types for display
$stmt = $pdo->query("SELECT * FROM fish_types");
$fishTypes = $stmt->fetchAll();

// Get inventory for display
$stmt = $pdo->query("SELECT i.*, f.name FROM inventory i JOIN fish_types f ON i.fish_type_id = f.id");
$inventory = $stmt->fetchAll();

// Get team members
$teamMembers = $pdo->query("SELECT * FROM team_members ORDER BY position_order")->fetchAll();

// Get partners
$partners = $pdo->query("SELECT * FROM partners ORDER BY name")->fetchAll();


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Fish Farm - Bah & Brothers | Fresh Catfish & Tilapia</title>
    <meta name="description" content="Bah & Brothers Fish Farm - Premium quality catfish and tilapia from our eco-friendly ponds in The Gambia">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #198754;
            --accent-color: #ffc107;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            scroll-behavior: smooth;
        }

        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('images/fish2.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 150px 0;
            text-align: center;
            position: relative;
        }

        .hero-section::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: linear-gradient(to bottom, transparent, white);
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .nav-shadow {
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            position: relative;
            display: inline-block;
            margin-bottom: 40px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: var(--primary-color);
        }

        /* Add to the existing styles in index.php */
        .fish-card {
            transition: all 0.3s ease;
            height: 100%;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .fish-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .fish-card .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .fish-card .card-body p {
            flex: 1;
        }

        .fish-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 15px;
            transition: all 0.3s ease;
            text-align: center;
        }

        .contact-form {
            background-color: #f8f9fa;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        #map {
            height: 400px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 5px solid white;
        }

        .feature-box {
            text-align: center;
            padding: 30px 20px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-box:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .testimonial-card {
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            position: relative;
            margin-bottom: 30px;
        }

        .testimonial-card::before {
            content: '\201C';
            font-family: Georgia, serif;
            font-size: 60px;
            color: rgba(13, 110, 253, 0.1);
            position: absolute;
            top: 10px;
            left: 10px;
        }

        .testimonial-author {
            font-weight: bold;
            margin-top: 20px;
            color: var(--primary-color);
        }

        .social-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            margin-right: 10px;
            transition: all 0.3s ease;
        }

        .social-icon:hover {
            background: var(--accent-color);
            transform: translateY(-3px);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px 25px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .btn-outline-light {
            padding: 10px 25px;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .floating-action-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            box-shadow: 0 5px 20px rgba(13, 110, 253, 0.3);
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .floating-action-btn:hover {
            transform: scale(1.1);
            background: var(--secondary-color);
            color: white;
        }

        .back-to-top {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--secondary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            box-shadow: 0 5px 20px rgba(25, 135, 84, 0.3);
            z-index: 1000;
            transition: all 0.3s ease;
            opacity: 0;
            visibility: hidden;
        }

        .back-to-top.active {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            transform: translateY(-5px);
        }

        .team-section {
            background-color: #f8f9fa;
        }

        .team-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            position: relative;
        }

        .team-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .team-img {
            height: 250px;
            object-fit: cover;
            width: 100%;
        }

        .team-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.8), transparent);
            color: white;
            padding: 20px;
        }

        .partner-logo {
            height: 100px;
            object-fit: contain;
            margin: 0 auto;
            display: block;
            filter: grayscale(100%);
            transition: all 0.3s ease;
        }

        .partner-logo:hover {
            filter: grayscale(0%);
            transform: scale(1.1);
        }

        .edit-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .team-card:hover .edit-btn,
        .fish-card:hover .edit-btn {
            opacity: 1;
        }

        /* Modal styles for image upload */
        .image-upload-modal .modal-dialog {
            max-width: 800px;
        }

        .preview-image {
            max-height: 300px;
            width: auto;
            display: block;
            margin: 0 auto;
        }

        /* Add to the existing styles */
        .contact-form-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
        }

        .form-floating label {
            color: #6c757d;
            padding: 1rem 1.25rem;
        }

        .form-floating>.form-control:focus~label,
        .form-floating>.form-control:not(:placeholder-shown)~label,
        .form-floating>.form-select~label {
            opacity: 0.8;
            transform: scale(0.85) translateY(-0.7rem) translateX(0.15rem);
        }

        .form-control,
        .form-select {
            padding: 1rem 1.25rem;
            border: 1px solid #e9ecef;
            transition: all 0.3s;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top nav-shadow">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-fish me-2"></i> <strong>Bah & Brothers</strong>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#products">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#shop">shop Location</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#location">Farm Location</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>

                </ul>
                <a href="login.php" class="btn btn-outline-light me-2">Login</a>
                <a href="register.php" class="btn btn-light">Register</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="container hero-content">
            <h1 class="display-3 fw-bold mb-3 animate__animated animate__fadeInDown">Bah & Brothers Fish Farm</h1>
            <p class="lead mb-4 fs-4 animate__animated animate__fadeIn animate__delay-1s">Premium quality catfish and tilapia straight from our eco-friendly ponds</p>
            <div class="animate__animated animate__fadeIn animate__delay-2s">
                <a href="#products" class="btn btn-primary btn-lg me-3">Our Products</a>
                <a href="#contact" class="btn btn-outline-light btn-lg">Contact Us</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-box animate__animated animate__fadeInUp">
                        <div class="feature-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h3>Eco-Friendly</h3>
                        <p>Sustainably raised fish using environmentally responsible practices that protect our ecosystem.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box animate__animated animate__fadeInUp animate__delay-1s">
                        <div class="feature-icon">
                            <i class="fas fa-award"></i>
                        </div>
                        <h3>Premium Quality</h3>
                        <p>Highest quality fish with strict quality control measures from pond to plate.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-box animate__animated animate__fadeInUp animate__delay-2s">
                        <div class="feature-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <h3>Fast Delivery</h3>
                        <p>Fresh delivery to your doorstep within 24 hours of harvest for maximum freshness.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="py-5" id="about">
        <div class="container">
            <h2 class="text-center section-title animate__animated animate__fadeIn">About Our Farm</h2>
            <div class="row align-items-center">
                <div class="col-lg-6 mb-4 mb-lg-0 animate__animated animate__fadeInLeft">
                    <div class="pe-lg-5">
                        <h3 class="mb-4">Sustainable Fish Farming in The Gambia</h3>
                        <p class="lead">We've been raising healthy, sustainable fish for over 10 years using eco-friendly practices that prioritize animal welfare and environmental conservation.</p>
                        <p>Our 5-acre farm features state-of-the-art pond systems with optimal water quality monitoring, ensuring the best conditions for fish growth. We use natural feeding methods supplemented with scientifically formulated feeds to produce the healthiest fish.</p>
                        <p>As a family-owned business, we take pride in delivering the freshest products while supporting our local community through employment and sustainable practices.</p>
                        <div class="mt-4">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-check-circle text-primary me-3"></i>
                                <span>100% natural growth process</span>
                            </div>
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-check-circle text-primary me-3"></i>
                                <span>No harmful chemicals or antibiotics</span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-check-circle text-primary me-3"></i>
                                <span>Certified sustainable practices</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6 animate__animated animate__fadeInRight">
                    <div class="position-relative">
                        <img src="images/header_fishes_image.jpg" alt="Our Fish Farm" class="img-fluid rounded shadow-lg">
                        <div class="position-absolute bottom-0 start-0 bg-primary text-white p-3 m-3 rounded">
                            <h4 class="mb-0">10+ Years Experience</h4>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center section-title">What Our Customers Say</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <p>"The quality of Bah & Brothers fish is unmatched in The Gambia. I've been a regular customer for 3 years and their consistency is impressive."</p>
                        <div class="testimonial-author">
                            <i class="fas fa-user me-2"></i> Alieu Jallow, Restaurant Owner
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <p>"As a chef, I appreciate the freshness and texture of their fish. It's clear they prioritize quality at every step of their process."</p>
                        <div class="testimonial-author">
                            <i class="fas fa-user me-2"></i> Fatou Njie, Head Chef
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <p>"Their delivery is always on time and the fish arrives in perfect condition. The best supplier I've worked with in my 10 years in the business."</p>
                        <div class="testimonial-author">
                            <i class="fas fa-user me-2"></i> Modou Sarr, Hotel Manager
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Video Showcase Section -->
    <section class="py-5 bg-light" id="videos">
        <div class="container">
            <h2 class="text-center section-title">Our Work Showcase</h2>
            <p class="text-center mb-5 lead">See our fish farming process and operations</p>

            <?php
            function getFeaturedVideo()
            {
                global $pdo;
                $stmt = $pdo->query("SELECT * FROM videos WHERE is_featured = 1 LIMIT 1");
                return $stmt->fetch();
            }

            function getAllVideos()
            {
                global $pdo;
                $stmt = $pdo->query("SELECT * FROM videos");
                return $stmt->fetchAll();
            }

            $featuredVideo = getFeaturedVideo();
            $allVideos = getAllVideos();
            ?>

            <!-- Featured Video -->
            <?php if ($featuredVideo): ?>
                <div class="row mb-5">
                    <div class="col-lg-8 mx-auto">
                        <div class="card shadow">
                            <div class="card-body p-0">
                                <div class="ratio ratio-16x9">
                                    <iframe src="<?= htmlspecialchars($featuredVideo['video_url']) ?>"
                                        frameborder="0"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen></iframe>
                                </div>
                                <div class="p-4">
                                    <h3><?= htmlspecialchars($featuredVideo['title']) ?></h3>
                                    <p><?= htmlspecialchars($featuredVideo['description']) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Video Gallery -->
            <div class="row g-4">
                <?php foreach ($allVideos as $video):
                    if ($video['is_featured']) continue; // Skip featured video since it's already shown

                    // Extract YouTube video ID for thumbnail
                    $videoId = '';
                    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video['video_url'], $matches)) {
                        $videoId = $matches[1];
                    }
                ?>
                    <div class="col-md-4">
                        <div class="card h-100 shadow-sm">
                            <?php if ($videoId): ?>
                                <img src="https://img.youtube.com/vi/<?= $videoId ?>/mqdefault.jpg"
                                    class="card-img-top"
                                    alt="<?= htmlspecialchars($video['title']) ?>">
                            <?php elseif ($video['thumbnail_url']): ?>
                                <img src="<?= htmlspecialchars($video['thumbnail_url']) ?>"
                                    class="card-img-top"
                                    alt="<?= htmlspecialchars($video['title']) ?>">
                            <?php else: ?>
                                <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 180px;">
                                    <i class="fas fa-video text-white" style="font-size: 3rem;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($video['title']) ?></h5>
                                <p class="card-text text-muted"><?= htmlspecialchars(substr($video['description'], 0, 100)) ?>...</p>
                            </div>
                            <div class="card-footer bg-transparent">
                                <a href="<?= htmlspecialchars($video['video_url']) ?>"
                                    class="btn btn-primary w-100"
                                    target="_blank">
                                    Watch Video
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <!-- Products Section with edit buttons -->
    <section class="py-5" id="products">
        <div class="container">
            <h2 class="text-center section-title">Our Fish Products</h2>
            <p class="text-center mb-5 lead">Premium quality fish raised with care and delivered fresh</p>
            <div class="row g-4">
                <?php foreach ($fishTypes as $fish):
                    $available = 0;
                    foreach ($inventory as $item) {
                        if ($item['fish_type_id'] == $fish['id']) {
                            $available = $item['quantity_kg'];
                            break;
                        }
                    }
                ?>
                    <div class="col-md-4">
                        <div class="card fish-card">
                            <?php if ($isAdmin): ?>
                                <button class="btn btn-sm btn-warning edit-btn"
                                    data-bs-toggle="modal"
                                    data-bs-target="#imageUploadModal"
                                    data-id="<?= $fish['id'] ?>"
                                    data-type="fish"
                                    data-current-image="<?= htmlspecialchars($fish['image_path']) ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </button>
                            <?php endif; ?>

                            <?php if (!empty($fish['image_path'])): ?>
                                <img src="<?= htmlspecialchars($fish['image_path']) ?>" class="card-img-top" alt="<?= htmlspecialchars($fish['name']) ?>" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                                <div class="fish-icon p-4">
                                    <i class="fas fa-fish"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body text-center p-4">
                                <h3 class="mb-3"><?= htmlspecialchars($fish['name']) ?></h3>
                                <p class="text-muted mb-4"><?= htmlspecialchars($fish['description']) ?></p>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge bg-<?= $available > 0 ? 'success' : 'danger' ?> px-3 py-2">
                                        <?= $available > 0 ? number_format($available, 2) . ' kg available' : 'Out of Stock' ?>
                                    </span>
                                    <span class="fw-bold fs-5">D<?= number_format($fish['price_per_kg'], 2) ?>/kg</span>
                                </div>
                                <?php if ($available > 0): ?>
                                    <a href="login.php" class="btn btn-primary mt-4 w-100">Order Now</a>
                                <?php else: ?>
                                    <button class="btn btn-secondary mt-4 w-100" disabled>Out of Stock</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

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

    <!-- Stats Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-3 mb-4 mb-md-0">
                    <h2 class="display-4 fw-bold">5+</h2>
                    <p class="mb-0">Acres of Ponds</p>
                </div>
                <div class="col-md-3 mb-4 mb-md-0">
                    <h2 class="display-4 fw-bold">10+</h2>
                    <p class="mb-0">Years Experience</p>
                </div>
                <div class="col-md-3 mb-4 mb-md-0">
                    <h2 class="display-4 fw-bold">50+</h2>
                    <p class="mb-0">Happy Clients</p>
                </div>
                <div class="col-md-3">
                    <h2 class="display-4 fw-bold">24h</h2>
                    <p class="mb-0">Delivery Time</p>
                </div>
            </div>
        </div>
    </section>
    <!-- Shop Locations Section -->
    <!-- Shop Locations Section -->
    <section class="py-5 bg-light" id="shop">
        <div class="container">
            <h2 class="text-center section-title">Our Shop Locations</h2>
            <p class="text-center mb-5 lead">Visit us at one of our convenient locations</p>

            <div class="row g-4" id="shopLocationsContainer">
                <?php
                $shopLocations = $pdo->query("
                SELECT sl.*, u.username as employee_name 
                FROM shop_locations sl
                JOIN users u ON sl.employee_id = u.id
                WHERE sl.is_open = 1
                ORDER BY sl.region, sl.location_name
            ")->fetchAll();

                foreach ($shopLocations as $shop):
                    $currentTime = date('H:i:s');
                    $isCurrentlyOpen = ($currentTime >= $shop['opening_time'] && $currentTime <= $shop['closing_time']) && $shop['is_open'];

                    // Get inventory for this location using the correct columns
                    $inventoryStmt = $pdo->prepare("
                    SELECT li.quantity, ft.name, ft.price_per_kg 
                    FROM location_inventory li
                    JOIN fish_types ft ON li.fish_type_id = ft.id
                    WHERE li.location_id = ? AND li.status = 'accepted'
                ");
                    $inventoryStmt->execute([$shop['id']]);
                    $locationInventory = $inventoryStmt->fetchAll();

                    // Calculate total kg available using the quantity column
                    $totalKg = 0;
                    foreach ($locationInventory as $item) {
                        $totalKg += $item['quantity'];
                    }
                ?>
                    <div class="col-md-4">
                        <div class="feature-box">
                            <h2><?= htmlspecialchars($shop['location_name']) ?></h2>
                            <p class="text-muted mb-2">
                                <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                <?= htmlspecialchars($shop['region']) ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-user text-primary me-2"></i>
                                <?= htmlspecialchars($shop['employee_name']) ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-phone text-primary me-2"></i>
                                <?= htmlspecialchars($shop['contact_phone']) ?>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-clock text-primary me-2"></i>
                                <span class="shop-hours" data-shop-id="<?= $shop['id'] ?>">
                                    <?= date('g:i A', strtotime($shop['opening_time'])) ?> -
                                    <?= date('g:i A', strtotime($shop['closing_time'])) ?>
                                </span>
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-fish text-primary me-2"></i>
                                <strong>Total Available:</strong> <span class="fw-bold text-success"><?= number_format($totalKg, 2) ?> kg</span>
                            </p>
                            <?php if (!empty($locationInventory)): ?>
                                <div class="mt-2">
                                    <small class="text-muted">Available fish types:</small>
                                    <ul class="list-unstyled">
                                        <?php foreach ($locationInventory as $item): ?>
                                            <li>
                                                <?= htmlspecialchars($item['name']) ?>:
                                                <strong><?= number_format($item['quantity'], 2) ?> kg</strong>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                            <p class="mt-2">
                                <span class="badge bg-<?= $isCurrentlyOpen ? 'success' : 'danger' ?> shop-status"
                                    data-shop-id="<?= $shop['id'] ?>">
                                    <?= $isCurrentlyOpen ? 'Open Now' : 'Closed' ?>
                                </span>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <!-- Location Section -->
    <section class="py-5" id="location">
        <div class="container">
            <h2 class="text-center section-title">Our Location</h2>
            <div class="row">
                <div class="col-md-6 mb-4 mb-md-0">
                    <div id="map">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d426.3555215998874!2d-16.581948620765203!3d13.275459520868738!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xec2a1274709760f%3A0x8b68002e434944b8!2sKuloro!5e1!3m2!1sen!2sgm!4v1742995136730!5m2!1sen!2sgm" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="ps-md-4">
                        <h3 class="mb-4">Visit Our Farm</h3>
                        <div class="mb-4">
                            <h4><i class="fas fa-map-marker-alt text-primary me-2"></i> Farm Address</h4>
                            <p class="mb-4 fs-5">Kuloro Kombo East Village<br>Brikama, Gambia</p>
                        </div>

                        <div class="mb-4">
                            <h4><i class="fas fa-clock text-primary me-2"></i> Visiting Hours</h4>
                            <p class="mb-4 fs-5">Monday - Friday: 8:00 AM - 5:00 PM<br>Saturday: 9:00 AM - 2:00 PM</p>
                        </div>

                        <div class="mb-4">
                            <h4><i class="fas fa-phone-alt text-primary me-2"></i> Contact Info</h4>
                            <p class="mb-1 fs-5"><i class="fas fa-phone me-2"></i> +220 311481</p>
                            <p class="fs-5"><i class="fas fa-envelope me-2"></i> abliebah@gmail.com</p>
                        </div>

                        <div class="mt-5">
                            <h4>Follow Us</h4>
                            <div class="d-flex">
                                <a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                                <a href="#" class="social-icon"><i class="fab fa-whatsapp"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Replace the contact form section in index.php with this: -->
    <section class="py-5 bg-light" id="contact">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-5">
                        <h2 class="section-title">Contact Us</h2>
                        <p class="lead">Have questions? Get in touch with our team</p>
                    </div>

                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4 p-md-5">
                            <!-- Replace the form in your contact section with this -->
                            <form id="contactForm" action="submit_contact.php" method="POST">
                                <div class="row">
                                    <div class="col-md-6 mb-4">
                                        <div class="form-floating">
                                            <input type="text" class="form-control" id="name" name="name" placeholder="Your Name" required>
                                            <label for="name">Your Name</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-4">
                                        <div class="form-floating">
                                            <input type="email" class="form-control" id="email" name="email" placeholder="Email Address" required>
                                            <label for="email">Email Address</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-floating">
                                        <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone Number">
                                        <label for="phone">Phone Number</label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-floating">
                                        <select class="form-select" id="subject" name="subject" required>
                                            <option value="" selected disabled></option>
                                            <option value="order">Order Inquiry</option>
                                            <option value="visit">Farm Visit</option>
                                            <option value="wholesale">Wholesale Purchase</option>
                                            <option value="other">Other Question</option>
                                        </select>
                                        <label for="subject">Subject</label>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <div class="form-floating">
                                        <textarea class="form-control" id="message" name="message" placeholder="Your Message" style="height: 150px" required></textarea>
                                        <label for="message">Your Message</label>
                                    </div>
                                </div>

                                <div class="text-center">
                                    <button type="submit" class="btn btn-primary btn-lg px-5 py-3">
                                        <i class="fas fa-paper-plane me-2"></i> Send Message
                                    </button>
                                </div>

                                <!-- Add this hidden success message -->
                                <div id="formSuccess" class="alert alert-success mt-3 text-center" style="display: none;">
                                    Thank you! Your message has been sent successfully.
                                </div>

                                <!-- Add this hidden error message -->
                                <div id="formError" class="alert alert-danger mt-3 text-center" style="display: none;">
                                    Error submitting your message. Please try again or contact us directly.
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <p class="text-muted">Or contact us directly at <a href="mailto:ablienbah@gmail.com">ablienbah@gmail.com</a> or <a href="tel:+220311481">+220 311481</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4 mb-lg-0">
                    <h4 class="mb-4"><i class="fas fa-fish me-2"></i> Bah & Brothers</h4>
                    <p>Providing fresh, healthy fish to our community since 2025 through sustainable farming practices and commitment to quality.</p>
                    <div class="mt-4">
                        <a href="#" class="text-white me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4 mb-md-0">
                    <h5 class="mb-4">Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><a href="#home" class="text-white">Home</a></li>
                        <li class="mb-2"><a href="#about" class="text-white">About Us</a></li>
                        <li class="mb-2"><a href="#products" class="text-white">Products</a></li>
                        <li class="mb-2"><a href="#shop" class="text-white">Shop Location</a></li>
                        <li class="mb-2"><a href="#location" class="text-white">Location</a></li>
                        <li><a href="#contact" class="text-white">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h5 class="mb-4">Products</h5>
                    <ul class="list-unstyled">
                        <?php foreach ($fishTypes as $fish): ?>
                            <li class="mb-2"><a href="#products" class="text-white"><?= htmlspecialchars($fish['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h5 class="mb-4">Contact Info</h5>
                    <ul class="list-unstyled">
                        <li class="mb-3"><i class="fas fa-map-marker-alt me-2"></i> Kuloro Kombo East Village, Brikama</li>
                        <li class="mb-3"><i class="fas fa-phone me-2"></i> +220 311481</li>
                        <li class="mb-3"><i class="fas fa-envelope me-2"></i> abliebah@gmail.com</li>
                        <li><i class="fas fa-clock me-2"></i> Mon-Fri: 8AM-5PM, Sat: 9AM-2PM</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <p class="mb-0">&copy; <?= date('Y') ?> Bah & Brothers Fish Farm. All rights reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="#" class="text-white me-3">Privacy Policy</a>
                    <a href="#" class="text-white">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>
    <div class="modal fade image-upload-modal" id="imageUploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Image</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="imageUploadForm" action="update_image.php" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="itemId" name="item_id">
                        <input type="hidden" id="itemType" name="item_type">

                        <div class="mb-3 text-center">
                            <img id="currentImagePreview" class="preview-image mb-3" src="" alt="Current Image">
                            <div id="noImageMessage" class="alert alert-info" style="display: none;">
                                No image currently set
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="newImage" class="form-label">Upload New Image</label>
                            <input class="form-control" type="file" id="newImage" name="image" accept="image/*">
                        </div>

                        <div class="mb-3">
                            <label for="imageCaption" class="form-label">Caption (optional)</label>
                            <input type="text" class="form-control" id="imageCaption" name="caption">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <!-- Floating Action Button -->
    <a href="https://wa.me/220311481" class="floating-action-btn animate__animated animate__bounceIn">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Back to Top Button -->
    <a href="#" class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </a>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Replace the form handling code with this
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;

            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Sending...';

            // Hide any previous messages
            document.getElementById('formSuccess').style.display = 'none';
            document.getElementById('formError').style.display = 'none';

            // Submit form via AJAX
            fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form)
                })
                .then(response => {
                    if (response.ok) {
                        return response.text();
                    }
                    throw new Error('Network response was not ok');
                })
                .then(data => {
                    // Show success message
                    document.getElementById('formSuccess').style.display = 'block';
                    form.reset();

                    // Scroll to success message
                    document.getElementById('formSuccess').scrollIntoView({
                        behavior: 'smooth'
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('formError').style.display = 'block';
                })
                .finally(() => {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                });
        });

        // Back to top button
        const backToTopButton = document.querySelector('.back-to-top');
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.classList.add('active');
            } else {
                backToTopButton.classList.remove('active');
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Animation on scroll
        function animateOnScroll() {
            const elements = document.querySelectorAll('.animate__animated');
            elements.forEach(element => {
                const elementPosition = element.getBoundingClientRect().top;
                const screenPosition = window.innerHeight / 1.3;

                if (elementPosition < screenPosition) {
                    element.classList.add('animate__fadeInUp');
                }
            });
        }
        // Initialize image upload modal
        document.getElementById('imageUploadModal').addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var itemId = button.getAttribute('data-id');
            var itemType = button.getAttribute('data-type');
            var currentImage = button.getAttribute('data-current-image');

            var modal = this;
            modal.querySelector('#itemId').value = itemId;
            modal.querySelector('#itemType').value = itemType;

            var preview = modal.querySelector('#currentImagePreview');
            var noImageMessage = modal.querySelector('#noImageMessage');

            if (currentImage && currentImage !== '') {
                preview.src = currentImage;
                preview.style.display = 'block';
                noImageMessage.style.display = 'none';
            } else {
                preview.style.display = 'none';
                noImageMessage.style.display = 'block';
            }
        });

        // Preview image before upload
        document.getElementById('newImage').addEventListener('change', function(e) {
            var file = e.target.files[0];
            if (file) {
                var reader = new FileReader();
                reader.onload = function(event) {
                    document.getElementById('currentImagePreview').src = event.target.result;
                    document.getElementById('currentImagePreview').style.display = 'block';
                    document.getElementById('noImageMessage').style.display = 'none';
                }
                reader.readAsDataURL(file);
            }
        });

        // Form submission with feedback
        document.getElementById('imageUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();

            var formData = new FormData(this);

            fetch('update_image.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Image updated successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error uploading image: ' + error);
                });
        });

        window.addEventListener('scroll', animateOnScroll);
        animateOnScroll();


        // Function to update shop status in real-time
        function updateShopStatus() {
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

                        // Update the status badge
                        const statusBadge = document.querySelector(`.shop-status[data-shop-id="${shop.id}"]`);
                        if (statusBadge) {
                            statusBadge.textContent = isOpenNow ? 'Open Now' : 'Closed';
                            statusBadge.className = isOpenNow ? 'badge bg-success' : 'badge bg-danger';
                        }

                        // Update the hours display
                        const hoursDisplay = document.querySelector(`.shop-hours[data-shop-id="${shop.id}"]`);
                        if (hoursDisplay) {
                            hoursDisplay.textContent = `${formatTime(shop.opening_time)} - ${formatTime(shop.closing_time)}`;
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

        // Update status every minute
        setInterval(updateShopStatus, 60000);

        // Initial update
        updateShopStatus(); // Run once on page load
    </script>
</body>

</html>