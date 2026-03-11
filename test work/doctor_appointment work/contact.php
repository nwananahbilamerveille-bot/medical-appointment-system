<?php session_start(); ?>
<!DOCTYPE html>
<html>
<head>
    <title>Contact - MedAppoint</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="index.php">MedAppoint</a>
    </div>
</nav>

<div class="container mt-5">
    <div class="row">
        <div class="col-md-6">
            <h2 class="text-primary">Get In Touch</h2>
            <p>Email: medappoint@gmail.com</p>
            <p>Phone: +237 6XX XXX XXX</p>
            <p>Location: Bamenda, Cameroon</p>
        </div>

        <div class="col-md-6">
            <div class="card shadow p-4">
                <form>
                    <div class="mb-3">
                        <input type="text" class="form-control" placeholder="Your Name">
                    </div>
                    <div class="mb-3">
                        <input type="email" class="form-control" placeholder="Your Email">
                    </div>
                    <div class="mb-3">
                        <textarea class="form-control" rows="4" placeholder="Your Message"></textarea>
                    </div>
                    <button class="btn btn-primary w-100">Send Message</button>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>
