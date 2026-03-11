<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Video Consultation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<div class="container mt-5 text-center">
    <div class="card shadow p-5">
        <h2 class="text-primary">Online Video Consultation</h2>
        <p class="lead">
            Click the button below to start your medical consultation.
        </p>

        <?php
        $room = "MedAppointRoom_" . rand(1000,9999);
        ?>

        <a href="https://meet.jit.si/<?php echo $room; ?>"
           target="_blank"
           class="btn btn-success btn-lg mt-3">
           🎥 Start Video Consultation
        </a>

    </div>
</div>

</body>
</html>
