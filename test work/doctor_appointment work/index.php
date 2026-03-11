<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>MedAppoint - Doctor Appointment System</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Animation -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            scroll-behavior: smooth;
        }

        .hero {
            background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)),
                        url('images/docmi.jpg');
            background-size: cover;
            background-position: center;
            height: 100vh;
            color: white;
            display: flex;
            align-items: center;
            text-align: center;
        }

        .navbar {
            background: rgba(0, 0, 0, 0.9) !important;
        }

        .section-padding {
            padding: 80px 0;
        }

        .contact-section {
            background: linear-gradient(to right, #0d6efd, #0dcaf0);
            color: white;
        }

        .contact-card {
            background: white;
            color: black;
            border-radius: 15px;
            box-shadow: 0px 10px 25px rgba(0,0,0,0.2);
        }

        .footer {
            background: #0d6efd;
            color: white;
            padding: 20px 0;
            text-align: center;
        }

        .btn-custom {
            padding: 10px 25px;
            font-size: 18px;
        }
    </style>
</head>

<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">MedAppoint</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <div class="navbar-nav ms-auto">
                <a class="nav-link text-white" href="#">Home</a>
                <a class="nav-link text-white" href="#contact">Contact</a>
                <a class="nav-link text-white" href="video.php">Video Consultation</a>
                <a class="nav-link text-white" href="auth/login.php">Login</a>
                <a class="nav-link text-white" href="auth/register.php">Register</a>
                <a class="nav-link text-white" href="home_remedies.php">Home Remedies</a>
            </div>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<section class="hero">
    <div class="container animate__animated animate__fadeInDown">
        <h1 class="display-4 fw-bold">Your Health Is Our Priority</h1>
        <p class="lead mt-3">Book appointments with qualified doctors quickly and easily.</p>

        <div class="mt-4 animate__animated animate__fadeInUp">
            <a href="auth/register.php?role=patient" class="btn btn-primary btn-lg btn-custom mx-2">
                👤 Patient Registration
            </a>
            <a href="auth/register.php?role=doctor" class="btn btn-success btn-lg btn-custom mx-2">
                👨‍⚕️ Doctor Registration
            </a>
        </div>
    </div>
</section>

<!-- About -->
<section class="section-padding bg-light text-center">
    <div class="container">
        <h2 class="text-primary mb-4">About MedAppoint</h2>
        <p class="lead">
            A modern online doctor appointment system designed to simplify healthcare access.
        </p>
    </div>
</section>

<!-- CONTACT SECTION -->
<section id="contact" class="contact-section section-padding">
    <div class="container">
        <div class="row align-items-center">

            <!-- Contact Info -->
            <div class="col-md-6 mb-4">
                <h2 class="fw-bold">Get In Touch</h2>
                <p class="mt-3">📧 Email: medappoint@gmail.com</p>
                <p>📞 Phone: +237 6XX XXX XXX</p>
                <p>📍 Location: Bamenda, Cameroon</p>
            </div>

            <!-- Contact Form -->
            <div class="col-md-6">
                <div class="contact-card p-4">
                    <h4 class="text-center mb-3 text-primary">Send Us a Message</h4>

                    <form>
                        <div class="mb-3">
                            <input type="text" class="form-control" placeholder="Your Name" required>
                        </div>

                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Your Email" required>
                        </div>

                        <div class="mb-3">
                            <textarea class="form-control" rows="4" placeholder="Your Message" required></textarea>
                        </div>

                        <button class="btn btn-primary w-100">
                            Send Message
                        </button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- Footer -->
<footer class="footer">
    <p>&copy; <?php echo date("Y"); ?> MedAppoint | Bamenda, Cameroon</p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>