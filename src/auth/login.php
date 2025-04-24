<?php
// Initialize the session
session_start();

// Check if the user is already logged in, if yes then redirect to home page
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
  header("location: ../main/Home.php");
  exit;
}

// Include database configuration
require_once "config.php";

// Define variables and initialize with empty values
$email = $password = "";
$email_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

  // Check if email is empty
  if (empty(trim($_POST["email"]))) {
    $email_err = "Please enter your email.";
  } else {
    $email = trim($_POST["email"]);
  }

  // Check if password is empty
  if (empty(trim($_POST["password"]))) {
    $password_err = "Please enter your password.";
  } else {
    $password = trim($_POST["password"]);
  }

  // Validate credentials
  if (empty($email_err) && empty($password_err)) {
    // Prepare a select statement
    // Fetch the role as well
    $sql = "SELECT id, fullname, email, password, role FROM users WHERE email = ?";

    if ($stmt = $conn->prepare($sql)) {
      // Bind variables to the prepared statement as parameters
      $stmt->bind_param("s", $param_email);

      // Set parameters
      $param_email = $email;

      // Attempt to execute the prepared statement
      if ($stmt->execute()) {
        // Store result
        $stmt->store_result();

        // Check if email exists, if yes then verify password
        if ($stmt->num_rows == 1) {
          // Bind result variables (add $role)
          $stmt->bind_result($id, $fullname, $email, $hashed_password, $role);
          if ($stmt->fetch()) {
            if (password_verify($password, $hashed_password)) {
              // Password is correct, so start a new session
              // session_start(); // Already started at the top

              // Store data in session variables
              $_SESSION["loggedin"] = true;
              $_SESSION["id"] = $id;
              $_SESSION["fullname"] = $fullname;
              $_SESSION["email"] = $email;
              $_SESSION["role"] = $role; // Store the user's role

              // Redirect user to home page
              header("location: ../main/Home.php");
              exit; // Add exit after header redirect
            } else {
              // Password is not valid, display a generic error message
              $login_err = "Invalid email or password.";
            }
          }
        } else {
          // Email doesn't exist, display a generic error message
          $login_err = "Invalid email or password.";
        }
      } else {
        echo "Oops! Something went wrong. Please try again later.";
      }

      // Close statement
      $stmt->close();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - CareerCompass</title>
  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Font Awesome for icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#eff6ff',
              100: '#dbeafe',
              200: '#bfdbfe',
              300: '#93c5fd',
              400: '#60a5fa',
              500: '#3b82f6',
              600: '#2563eb',
              700: '#1d4ed8',
              800: '#1e40af',
              900: '#1e3a8a',
            }
          }
        }
      }
    }
  </script>
</head>

<body class="bg-gray-50 font-sans min-h-screen flex flex-col">
  <!-- Header with logo -->
  <header class="py-4 px-6 bg-white shadow-sm">
    <div class="container mx-auto">
      <a href="../../index.html" class="flex items-center space-x-2">
        <i class="fas fa-compass text-primary-600 text-xl"></i>
        <span class="text-xl font-bold">CareerCompass</span>
      </a>
    </div>
  </header>

  <!-- Main content -->
  <main class="flex-grow flex items-center justify-center px-4 py-12">
    <div class="w-full max-w-md">
      <!-- Login Card -->
      <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <!-- Card Header -->
        <div class="p-6 border-b">
          <h1 class="text-2xl font-bold text-center text-gray-900">Welcome Back</h1>
          <p class="text-center text-gray-600 mt-1">Log in to continue your career journey</p>
        </div>

        <?php if (!empty($login_err)): ?>
          <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
            <?php echo $login_err; ?>
          </div>
        <?php endif; ?>

        <!-- Login Form -->
        <div class="p-6">
          <form id="login-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-4">
            <!-- Email Field -->
            <div>
              <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i class="fas fa-envelope text-gray-400"></i>
                </div>
                <input type="email" id="email" name="email"
                  class="pl-10 w-full px-4 py-2 border <?php echo (!empty($email_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  placeholder="your@email.com" value="<?php echo $email; ?>" required>
              </div>
              <?php if (!empty($email_err)): ?>
                <p class="mt-1 text-sm text-red-600"><?php echo $email_err; ?></p>
              <?php endif; ?>
            </div>

            <!-- Password Field -->
            <div>
              <div class="flex items-center justify-between mb-1">
                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                <a href="#" class="text-sm text-primary-600 hover:text-primary-500">Forgot password?</a>
              </div>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i class="fas fa-lock text-gray-400"></i>
                </div>
                <input type="password" id="password" name="password"
                  class="pl-10 w-full px-4 py-2 border <?php echo (!empty($password_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  placeholder="••••••••" required>
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                  <button type="button" id="toggle-password"
                    class="text-gray-400 hover:text-gray-500 focus:outline-none">
                    <i class="fas fa-eye"></i>
                  </button>
                </div>
              </div>
              <?php if (!empty($password_err)): ?>
                <p class="mt-1 text-sm text-red-600"><?php echo $password_err; ?></p>
              <?php endif; ?>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center">
              <input type="checkbox" id="remember-me" name="remember-me"
                class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
              <label for="remember-me" class="ml-2 block text-sm text-gray-700">
                Remember me
              </label>
            </div>

            <!-- Login Button -->
            <div>
              <button type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Log In
              </button>
            </div>
          </form>

          <!-- Social Login -->
          <div class="mt-6">
            <div class="relative">
              <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
              </div>
              <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-white text-gray-500">Or continue with</span>
              </div>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3">
              <button type="button"
                class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <i class="fab fa-google mr-2"></i>
                Google
              </button>
              <button type="button"
                class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                <i class="fab fa-linkedin mr-2"></i>
                LinkedIn
              </button>
            </div>
          </div>
        </div>

        <!-- Card Footer -->
        <div class="px-6 py-4 bg-gray-50 border-t text-center">
          <p class="text-sm text-gray-600">
            Don't have an account?
            <a href="signup.php" class="font-medium text-primary-600 hover:text-primary-500">
              Sign up
            </a>
          </p>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="py-4 px-6 bg-white border-t">
    <div class="container mx-auto">
      <div class="flex flex-col md:flex-row justify-between items-center">
        <p class="text-sm text-gray-600 mb-4 md:mb-0">
          © 2025 CareerCompass. All rights reserved.
        </p>
        <div class="flex space-x-6">
          <a href="#" class="text-sm text-gray-600 hover:text-primary-600">Privacy Policy</a>
          <a href="#" class="text-sm text-gray-600 hover:text-primary-600">Terms of Service</a>
          <a href="#" class="text-sm text-gray-600 hover:text-primary-600">Contact Us</a>
        </div>
      </div>
    </div>
  </footer>

  <script>
    // Toggle password visibility
    const togglePassword = document.getElementById('toggle-password');
    const passwordInput = document.getElementById('password');

    togglePassword.addEventListener('click', function() {
      const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
      passwordInput.setAttribute('type', type);

      // Toggle icon
      const icon = this.querySelector('i');
      icon.classList.toggle('fa-eye');
      icon.classList.toggle('fa-eye-slash');
    });
  </script>
</body>

</html>