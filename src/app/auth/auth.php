<?php
// Start session
session_start();

// Database connection details (replace with your actual database credentials)
$db_host = "localhost";
$db_user = "username";
$db_pass = "password";
$db_name = "career_compass";

// Connect to database
function connectDB() {
    global $db_host, $db_user, $db_pass, $db_name;
    
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Register a new user
function registerUser($fullName, $email, $password, $age, $grade, $interests, $newsletter) {
    $conn = connectDB();
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        return ["success" => false, "message" => "Email already registered"];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, password, age, grade, newsletter) VALUES (?, ?, ?, ?, ?, ?)");
    $newsletter = $newsletter ? 1 : 0;
    $stmt->bind_param("sssisi", $fullName, $email, $hashedPassword, $age, $grade, $newsletter);
    
    if ($stmt->execute()) {
        $userId = $stmt->insert_id;
        $stmt->close();
        
        // Insert interests
        if (!empty($interests)) {
            $stmt = $conn->prepare("INSERT INTO user_interests (user_id, interest) VALUES (?, ?)");
            
            foreach ($interests as $interest) {
                $stmt->bind_param("is", $userId, $interest);
                $stmt->execute();
            }
            
            $stmt->close();
        }
        
        $conn->close();
        return ["success" => true, "message" => "Registration successful"];
    } else {
        $stmt->close();
        $conn->close();
        return ["success" => false, "message" => "Registration failed: " . $conn->error];
    }
}

// Login user
function loginUser($email, $password, $remember) {
    $conn = connectDB();
    
    $stmt = $conn->prepare("SELECT id, full_name, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $email;
            
            // Set remember me cookie if requested
            if ($remember) {
                $token = bin2hex(random_bytes(32));
                $expires = time() + (30 * 24 * 60 * 60); // 30 days
                
                // Store token in database
                $stmt = $conn->prepare("UPDATE users SET remember_token = ?, token_expires = ? WHERE id = ?");
                $stmt->bind_param("ssi", $token, date('Y-m-d H:i:s', $expires), $user['id']);
                $stmt->execute();
                
                // Set cookie
                setcookie('remember_token', $token, $expires, '/', '', true, true);
            }
            
            $stmt->close();
            $conn->close();
            return ["success" => true, "message" => "Login successful"];
        }
    }
    
    $stmt->close();
    $conn->close();
    return ["success" => false, "message" => "Invalid email or password"];
}

// Check if user is logged in
function isLoggedIn() {
    if (isset($_SESSION['user_id'])) {
        return true;
    }
    
    // Check for remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        $conn = connectDB();
        $token = $_COOKIE['remember_token'];
        
        $stmt = $conn->prepare("SELECT id, full_name, email FROM users WHERE remember_token = ? AND token_expires > NOW()");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            
            $stmt->close();
            $conn->close();
            return true;
        }
        
        $stmt->close();
        $conn->close();
    }
    
    return false;
}

// Logout user
function logoutUser() {
    // Unset all session variables
    $_SESSION = [];
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Delete remember me cookie
    if (isset($_COOKIE['remember_token'])) {
        setcookie('remember_token', '', time() - 3600, '/');
    }
}

// Get user data
function getUserData($userId) {
    $conn = connectDB();
    
    $stmt = $conn->prepare("SELECT id, full_name, email, age, grade, newsletter FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Get user interests
        $interests = [];
        $stmt = $conn->prepare("SELECT interest FROM user_interests WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $interestResult = $stmt->get_result();
        
        while ($row = $interestResult->fetch_assoc()) {
            $interests[] = $row['interest'];
        }
        
        $user['interests'] = $interests;
        
        $stmt->close();
        $conn->close();
        return $user;
    }
    
    $stmt->close();
    $conn->close();
    return null;
}

// Reset password
function resetPassword($email) {
    $conn = connectDB();
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Generate reset token
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + (24 * 60 * 60)); // 24 hours
        
        // Store token in database
        $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
        $stmt->bind_param("ssi", $token, $expires, $user['id']);
        $stmt->execute();
        
        $stmt->close();
        $conn->close();
        
        // In a real application, you would send an email with the reset link
        // For this example, we'll just return the token
        return ["success" => true, "token" => $token];
    }
    
    $stmt->close();
    $conn->close();
    return ["success" => false, "message" => "Email not found"];
}

// Update password with reset token
function updatePasswordWithToken($token, $newPassword) {
    $conn = connectDB();
    
    // Check if token is valid
    $stmt = $conn->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password and clear token
        $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->bind_param("si", $hashedPassword, $user['id']);
        $stmt->execute();
        
        $stmt->close();
        $conn->close();
        return ["success" => true, "message" => "Password updated successfully"];
    }
    
    $stmt->close();
    $conn->close();
    return ["success" => false, "message" => "Invalid or expired token"];
}
?>