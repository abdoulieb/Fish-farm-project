<?php
require_once 'config.php';

// Get fish types for display
$stmt = $pdo->query("SELECT * FROM fish_types");
$fishTypes = $stmt->fetchAll();

// Get inventory for display
$stmt = $pdo->query("SELECT i.*, f.name FROM inventory i JOIN fish_types f ON i.fish_type_id = f.id");
$inventory = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Our Fish Farm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('fish-farm-bg.jpg');
            background-size: cover;
            background-position: center;
            color: white;
            padding: 100px 0;
            text-align: center;
        }

        .fish-card {
            transition: transform 0.3s;
            height: 100%;
        }

        .fish-card:hover {
            transform: translateY(-5px);
        }

        .contact-form {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 30px;
        }

        #map {
            height: 400px;
            width: 100%;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="welcome.php">
                <i class="fas fa-fish"></i> Bah & Brothers Fish Farm
            </a>
            <div class="ml-auto">
                <a href="login.php" class="btn btn-outline-light me-2">Login</a>
                <a href="register.php" class="btn btn-light">Register</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section mb-5">
        <div class="container">
            <h1 class="display-4 fw-bold mb-3">Bah and Brothers Fish Farm</h1>
            <p class="lead mb-4">Premium quality catfish and tilapia straight from our ponds</p>
            <a href="#products" class="btn btn-primary btn-lg me-2">Our Products</a>
            <a href="#contact" class="btn btn-outline-light btn-lg">Contact Us</a>
        </div>
    </section>

    <!-- About Section -->
    <section class="container mb-5">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="mb-4">About Our Farm</h2>
                <p class="lead">We've been raising healthy, sustainable fish for over 10 years using eco-friendly practices.</p>
                <p>Our farm spans 5 acres with state-of-the-art pond systems that ensure optimal growth conditions for our fish. We take pride in delivering the freshest products to our customers.</p>
            </div>
            <div class="col-md-6">
                <img src="farm-photo.jpg" alt="Our Fish Farm" class="img-fluid rounded">
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="container mb-5 py-5 bg-light">
        <h2 class="text-center mb-5">Our Fish Products</h2>
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
                        <div class="card-body text-center">
                            <div class="mb-3" style="font-size: 3rem; color: #0d6efd;">
                                <i class="fas fa-fish"></i>
                            </div>
                            <h3><?= htmlspecialchars($fish['name']) ?></h3>
                            <p><?= htmlspecialchars($fish['description']) ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="badge bg-<?= $available > 0 ? 'success' : 'danger' ?>">
                                    <?= $available > 0 ? 'Available' : 'Out of Stock' ?>
                                </span>
                                <span class="fw-bold">D<?= number_format($fish['price_per_kg'], 2) ?>/kg</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Location Section -->
    <section class="container mb-5">
        <h2 class="text-center mb-4">Our Location</h2>
        <div class="row">
            <div class="col-md-6 mb-4 mb-md-0">
                <div id="map"></div>
            </div>
            <div class="col-md-6">
                <h4><i class="fas fa-map-marker-alt text-primary me-2"></i> Farm Address</h4>
                <p class="mb-4">Kuloro Kombo East Village<br>Brikama, Gambia</p>

                <h4><i class="fas fa-clock text-primary me-2"></i> Visiting Hours</h4>
                <p class="mb-4">Monday - Friday: 8:00 AM - 5:00 PM<br>Saturday: 9:00 AM - 2:00 PM</p>

                <h4><i class="fas fa-phone-alt text-primary me-2"></i> Contact Info</h4>
                <p class="mb-1"><i class="fas fa-phone me-2"></i> +220 311481</p>
                <p><i class="fas fa-envelope me-2"></i>abliebah@gmail.com</p>
            </div>
        </div>
    </section>

    <!-- Contact Form -->
    <section id="contact" class="container mb-5">
        <h2 class="text-center mb-4">Contact Us</h2>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="contact-form">
                    <form id="contactForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Your Name</label>
                                <input type="text" class="form-control" id="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Bah & Brothers Fish farm</h5>
                    <p>Providing fresh, healthy fish to our community since 2025.</p>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="#products" class="text-white">Our Products</a></li>
                        <li><a href="#contact" class="text-white">Contact Us</a></li>
                        <li><a href="login.php" class="text-white">Customer Login</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Connect With Us</h5>
                    <div class="social-links">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; <?= date('Y') ?> Green Valley Fish Farm. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=YOUR_API_KEY&callback=initMap" async defer></script>
    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d426.3555215998874!2d-16.581948620765203!3d13.275459520868738!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xec2a1274709760f%3A0x8b68002e434944b8!2sKuloro!5e1!3m2!1sen!2sgm!4v1742995136730!5m2!1sen!2sgm" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    <script>
        // Initialize Google Map
        function initMap() {
            const farmLocation = {


                lat: 13.275488306734001,
                lng: -16.58196087225515

            }; // Replace with your farm's coordinates
            const map = new google.maps.Map(document.getElementById("map"), {
                zoom: 15,
                center: farmLocation,
            });
            new google.maps.Marker({
                position: farmLocation,
                map: map,
                title: "Green Valley Fish Farm",
            });
        }

        // Simple form handling
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert('Thank you for your message! We will contact you soon.');
            this.reset();
        });
    </script>
</body>

</html>