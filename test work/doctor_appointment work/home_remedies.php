<?php
session_start();

$search = $_GET['search'] ?? '';

if (!isset($_SESSION['history'])) {
    $_SESSION['history'] = [];
}

if ($search) {
    $_SESSION['history'][] = $search;
}

// Remedies array with keywords, images, and recommendations
$remedies = [
    ["title"=>"Common Cold","desc"=>"Drink warm fluids, rest well, inhale steam, and take vitamin C.","img"=>"https://images.unsplash.com/photo-1582719478250-c89cae4dc85b","keywords"=>["cold","runny nose","congestion","sore throat"]],
    ["title"=>"Fever","desc"=>"Stay hydrated, monitor temperature, and rest adequately.","img"=>"https://images.unsplash.com/photo-1581594693702-fbdc51b2763b","keywords"=>["fever","high temperature","chills"]],
    ["title"=>"Headache","desc"=>"Rest in a quiet place, drink water, and avoid bright lights.","img"=>"https://images.unsplash.com/photo-1550831107-1553da8c8464","keywords"=>["headache","migraine","dizziness","eye strain"]],
    ["title"=>"Cough","desc"=>"Honey and lemon drink helps soothe throat; stay hydrated.","img"=>"https://images.unsplash.com/photo-1580281657527-47f249e1c46f","keywords"=>["cough","sore throat","cold","phlegm"]],
    ["title"=>"Stomach Ache","desc"=>"Drink ginger tea, avoid heavy meals, and rest.","img"=>"https://images.unsplash.com/photo-1588776814546-6a582287d5b6","keywords"=>["stomach pain","nausea","vomiting","indigestion"]],
    ["title"=>"Burns First Aid","desc"=>"Cool the burn, remove jewelry, and apply antibiotic ointment.","img"=>"https://images.unsplash.com/photo-1626549485945-8aebd6a7c427","keywords"=>["burn","sunburn","scald","blister"]],
    ["title"=>"Malaria Prevention","desc"=>"Use insecticide-treated nets, wear protective clothing, and apply repellents.","img"=>"https://images.unsplash.com/photo-1618773928121-c3227d18c3e7","keywords"=>["malaria","mosquito","fever","chills"]],
    ["title"=>"Back Pain Relief","desc"=>"Maintain proper posture, do gentle stretches, and avoid heavy lifting.","img"=>"https://images.unsplash.com/photo-1587502536263-96278d223c47","keywords"=>["back pain","muscle pain","spine","fatigue"]]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Smart Remedies Search</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
body {
    background: linear-gradient(to right, #eef2f3, #8ec5fc);
    transition: background 0.3s, color 0.3s;
}
body.dark-mode {
    background: #121212;
    color: #f1f1f1;
}
.card {
    border-radius: 15px;
    transition: transform 0.3s;
}
.card:hover {
    transform: scale(1.03);
}
img {
    height: 180px;
    object-fit: cover;
    border-radius: 10px;
}
.btn-primary {
    transition: transform 0.2s;
}
.btn-primary:hover {
    transform: scale(1.05);
}
.mic-btn {
    cursor: pointer;
}
.toggle-dark {
    position: fixed;
    top: 10px;
    right: 20px;
}
</style>
</head>
<body>

<div class="toggle-dark form-check form-switch">
  <input class="form-check-input" type="checkbox" id="darkModeToggle">
  <label class="form-check-label" for="darkModeToggle">Dark Mode</label>
</div>

<div class="container mt-5">
<h2 class="text-center text-primary mb-4 animate__animated animate__fadeInDown">
Smart Home Remedies Finder
</h2>

<form method="GET" class="mb-4">

<div class="input-group">
<input type="text" id="symptomInput"
       name="search"
       class="form-control"
       placeholder="Describe your symptoms..."
       value="<?= htmlspecialchars($search) ?>">

<button class="btn btn-primary" type="submit">Search Now</button>
<span class="btn btn-outline-secondary mic-btn" onclick="startDictation()">🎤</span>
</div>
</form>

<?php if ($search): ?>
<h4 class="mb-3">Results for: "<?= htmlspecialchars($search) ?>"</h4>
<div class="row">
<?php
$found = false;
foreach ($remedies as $r) {
    foreach ($r['keywords'] as $kw) {
        if (stripos($search, $kw) !== false) {
            $found = true;
?>
<div class="col-md-6 mb-3 animate__animated animate__fadeInUp animate__faster">
<div class="card shadow p-3">
<img src="<?= $r['img'] ?>" class="w-100 mb-3">
<h5><?= $r['title'] ?></h5>
<p><?= $r['desc'] ?></p>
<div class="alert alert-info mt-2">
👨‍⚕️ Recommended: Consult a General Physician
</div>
</div>
</div>
<?php
            break;
        }
    }
}
if (!$found) {
    echo "<div class='alert alert-warning animate__animated animate__shakeX'>No remedy match — please consult a doctor.</div>";
}
?>
</div>
<?php endif; ?>

<hr>
<h5>Search History</h5>
<ul>
<?php foreach ($_SESSION['history'] as $h): ?>
<li><?= htmlspecialchars($h) ?></li>
<?php endforeach; ?>
</ul>
</div>

<script>
// Dark mode toggle
const toggle = document.getElementById('darkModeToggle');
toggle.addEventListener('change', () => {
    document.body.classList.toggle('dark-mode', toggle.checked);
});

// Voice input
function startDictation() {
    if (window.hasOwnProperty('webkitSpeechRecognition')) {
        const recognition = new webkitSpeechRecognition();
        recognition.lang = 'en-US';
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;
        recognition.start();

        recognition.onresult = function(event) {
            document.getElementById('symptomInput').value = event.results[0][0].transcript;
        }
        recognition.onerror = function(event) {
            alert('Voice recognition error: ' + event.error);
        }
    } else {
        alert('Your browser does not support voice input.');
    }
}
</script>

</body>
</html>