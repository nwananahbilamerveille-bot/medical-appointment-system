<?php
// Prevent SQL Injection
function prepared_query($sql, $params, $types = "") {
    $conn = getConnection();
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        if (empty($types)) {
            $types = str_repeat("s", count($params));
        }
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    return $stmt;
}

// CSRF Protection
function generate_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// Input Validation
function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validate_phone($phone) {
    return preg_match('/^[0-9]{10,15}$/', $phone);
}

function validate_date($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

// Role-based access control
function require_role($allowed_roles) {
    if (!in_array($_SESSION['role'] ?? '', $allowed_roles)) {
        header('HTTP/1.1 403 Forbidden');
        include('403.php');
        exit();
    }
}

// Rate limiting (simple version)
function check_rate_limit($key, $limit = 5, $timeout = 60) {
    $cache_file = sys_get_temp_dir() . "/rate_limit_$key";
    
    if (file_exists($cache_file)) {
        $data = json_decode(file_get_contents($cache_file), true);
        if (time() - $data['time'] < $timeout) {
            if ($data['count'] >= $limit) {
                return false;
            }
            $data['count']++;
        } else {
            $data = ['count' => 1, 'time' => time()];
        }
    } else {
        $data = ['count' => 1, 'time' => time()];
    }
    
    file_put_contents($cache_file, json_encode($data));
    return true;
}
?>