<?php
// Include database configuration
require_once 'config.php';

// Initialize variables
$fullname = $email = $password = $confirm_password = $role = ""; // Add $role
$fullname_err = $email_err = $password_err = $confirm_password_err = $role_err = $terms_err = ""; // Add $role_err and $terms_err
$success_message = "";

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

  // Validate fullname
  if (empty(trim($_POST["fullname"]))) {
    $fullname_err = "Please enter your full name.";
  } else {
    $fullname = trim($_POST["fullname"]);
  }

  // Validate email
  if (empty(trim($_POST["email"]))) {
    $email_err = "Please enter your email.";
  } else {
    // Prepare a select statement
    $sql = "SELECT id FROM users WHERE email = ?";

    if ($stmt = $conn->prepare($sql)) {
      // Bind variables to the prepared statement as parameters
      $stmt->bind_param("s", $param_email);

      // Set parameters
      $param_email = trim($_POST["email"]);

      // Attempt to execute the prepared statement
      if ($stmt->execute()) {
        // Store result
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
          $email_err = "This email is already registered.";
        } else {
          $email = trim($_POST["email"]);
        }
      } else {
        echo "Oops! Something went wrong. Please try again later.";
      }

      // Close statement
      $stmt->close();
    }
  }

  // Validate password
  if (empty(trim($_POST["password"]))) {
    $password_err = "Please enter a password.";
  } elseif (strlen(trim($_POST["password"])) < 8) {
    $password_err = "Password must have at least 8 characters.";
  } else {
    $password = trim($_POST["password"]);
  }

  // Validate confirm password
  if (empty(trim($_POST["confirm-password"]))) {
    $confirm_password_err = "Please confirm password.";
  } else {
    $confirm_password = trim($_POST["confirm-password"]);
    if (empty($password_err) && ($password != $confirm_password)) {
      $confirm_password_err = "Passwords do not match.";
    }
  }

  // Validate role selection
  if (empty($_POST["role"])) {
      $role_err = "Please select a role.";
  } elseif (!in_array($_POST["role"], ['user', 'hr'])) { // Only allow 'user' or 'hr'
      $role_err = "Invalid role selected.";
  } else {
      $role = $_POST["role"];
  }

  // Check if terms checkbox is checked
  if (!isset($_POST["terms"]) || $_POST["terms"] != "on") {
    $terms_err = "You must agree to the Terms of Service and Privacy Policy.";
  }

  // Check input errors before inserting in database
  if (empty($fullname_err) && empty($email_err) && empty($password_err) && empty($confirm_password_err) && empty($role_err) && empty($terms_err)) {

    // Prepare an insert statement
    $sql = "INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
      // Bind variables to the prepared statement as parameters
      $stmt->bind_param("ssss", $param_fullname, $param_email, $param_password, $param_role);

      // Set parameters
      $param_fullname = $fullname;
      $param_email = $email;
      $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
      $param_role = $role;

      // Attempt to execute the prepared statement
      if ($stmt->execute()) {
        // Redirect to login page
        $success_message = "Registration successful! You can now log in.";
        // Clear form fields after success
        $fullname = $email = $password = $confirm_password = $role = "";
        // Uncomment the line below to redirect to login page instead of showing success message
        // header("location: login.php");
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
  <title>Sign Up - CareerCompass</title>
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
      <!-- Signup Card -->
      <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <!-- Card Header -->
        <div class="p-6 border-b">
          <h1 class="text-2xl font-bold text-center text-gray-900">Create an Account</h1>
          <p class="text-center text-gray-600 mt-1">Join CareerCompass to discover your ideal career path</p>
        </div>

        <?php if (!empty($success_message)): ?>
          <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
            <?php echo $success_message; ?>
          </div>
        <?php endif; ?>

        <!-- Signup Form -->
        <div class="p-6">
          <form id="signup-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-4">
            <!-- Full Name Field -->
            <div>
              <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i class="fas fa-user text-gray-400"></i>
                </div>
                <input type="text" id="fullname" name="fullname"
                  class="pl-10 w-full px-4 py-2 border <?php echo (!empty($fullname_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  placeholder="John Doe" value="<?php echo $fullname; ?>" required>
              </div>
              <?php if (!empty($fullname_err)): ?>
                <p class="mt-1 text-sm text-red-600"><?php echo $fullname_err; ?></p>
              <?php endif; ?>
            </div>

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
              <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Password</label>
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
              <p class="mt-1 text-xs text-gray-500">Password must be at least 8 characters long</p>
              <?php if (!empty($password_err)): ?>
                <p class="mt-1 text-sm text-red-600"><?php echo $password_err; ?></p>
              <?php endif; ?>
            </div>

            <!-- Confirm Password Field -->
            <div>
              <label for="confirm-password" class="block text-sm font-medium text-gray-700 mb-1">Confirm Password</label>
              <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <i class="fas fa-lock text-gray-400"></i>
                </div>
                <input type="password" id="confirm-password" name="confirm-password"
                  class="pl-10 w-full px-4 py-2 border <?php echo (!empty($confirm_password_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                  placeholder="••••••••" required>
              </div>
              <?php if (!empty($confirm_password_err)): ?>
                <p class="mt-1 text-sm text-red-600"><?php echo $confirm_password_err; ?></p>
              <?php endif; ?>
            </div>

            <!-- Role Selection Field -->
            <div>
              <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Select Role</label>
              <div class="relative">
                 <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                   <i class="fas fa-user-tag text-gray-400"></i>
                 </div>
                <select id="role" name="role" required
                  class="pl-10 w-full px-4 py-2 border <?php echo (!empty($role_err)) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 appearance-none">
                  <option value="" disabled <?php echo (empty($role)) ? 'selected' : ''; ?>>-- Select your role --</option>
                  <option value="user" <?php echo ($role == 'user') ? 'selected' : ''; ?>>Job Seeker</option>
                  <option value="hr" <?php echo ($role == 'hr') ? 'selected' : ''; ?>>HR / Recruiter</option>
                  <!-- Note: 'admin' role is intentionally omitted for public signup -->
                </select>
                 <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                   <i class="fas fa-chevron-down text-gray-400"></i>
                 </div>
              </div>
              <?php if (!empty($role_err)): ?>
                <p class="mt-1 text-sm text-red-600"><?php echo $role_err; ?></p>
              <?php endif; ?>
            </div>

            <!-- Terms and Conditions -->
            <div class="flex items-start">
              <div class="flex items-center h-5">
                <input type="checkbox" id="terms" name="terms"
                  class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500" required>
              </div>
              <div class="ml-3 text-sm">
                <label for="terms" class="text-gray-700">
                  I agree to the
                  <a href="#" class="text-primary-600 hover:text-primary-500">Terms of Service</a>
                  and
                  <a href="#" class="text-primary-600 hover:text-primary-500">Privacy Policy</a>
                </label>
                <?php if (!empty($terms_err)): ?>
                  <p class="mt-1 text-sm text-red-600"><?php echo $terms_err; ?></p>
                <?php endif; ?>
              </div>
            </div>

            <!-- Signup Button -->
            <div>
              <button type="submit"
                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Create Account
              </button>
            </div>
          </form>

          <!-- Social Signup -->
          <div class="mt-6">
            <div class="relative">
              <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
              </div>
              <div class="relative flex justify-center text-sm">
                <span class="px-2 bg-white text-gray-500">Or sign up with</span>
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
            Already have an account?
            <a href="login.php" class="font-medium text-primary-600 hover:text-primary-500">
              Log in
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