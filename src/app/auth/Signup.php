<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Career Compass - Student Signup</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: {
              50: '#edfaff',
              100: '#d6f1ff',
              200: '#b5e8ff',
              300: '#83daff',
              400: '#48c2ff',
              500: '#1ea6ff',
              600: '#0085ff',
              700: '#006be0',
              800: '#0058b9',
              900: '#064a8d',
            },
            secondary: {
              50: '#effef7',
              100: '#dafeef',
              200: '#b8f9de',
              300: '#82f2c7',
              400: '#40e5a7',
              500: '#1ad18c',
              600: '#0ca772',
              700: '#0d855c',
              800: '#11694b',
              900: '#10563f',
            }
          }
        }
      }
    }
  </script>
  <style>
    .career-bg {
      background-image: url('https://images.unsplash.com/photo-1523240795612-9a054b0db644?ixlib=rb-4.0.3&auto=format&fit=crop&w=1950&q=80');
      background-size: cover;
      background-position: center;
    }

    .floating-label {
      transition: all 0.2s ease-in-out;
    }

    .input-field:focus+.floating-label,
    .input-field:not(:placeholder-shown)+.floating-label {
      transform: translateY(-1.5rem);
      font-size: 0.75rem;
      color: #4f46e5;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .fade-in {
      animation: fadeIn 0.6s ease-out forwards;
    }

    .bg-gradient-custom {
      background: linear-gradient(135deg, #1ea6ff 0%, #1ad18c 100%);
    }
  </style>
</head>

<body class="bg-gray-50 min-h-screen">
  <div class="flex flex-col md:flex-row min-h-screen">
    <!-- Left side - Decorative area with image and inspirational text -->
    <div class="career-bg hidden md:flex md:w-1/2 relative">
      <div class="absolute inset-0 bg-indigo-900 opacity-70"></div>
      <div class="relative z-10 flex flex-col justify-center items-center text-white p-10">
        <h1 class="text-4xl font-bold mb-6 fade-in">Career Compass</h1>
        <p class="text-xl mb-4 text-center fade-in" style="animation-delay: 0.2s">
          Navigating your future, one decision at a time
        </p>
        <div class="mt-8 space-y-4 fade-in" style="animation-delay: 0.4s">
          <div class="flex items-center">
            <span class="bg-white rounded-full p-2 mr-4">
              <i class="fas fa-compass text-indigo-600"></i>
            </span>
            <p>Discover your perfect career path</p>
          </div>
          <div class="flex items-center">
            <span class="bg-white rounded-full p-2 mr-4">
              <i class="fas fa-graduation-cap text-indigo-600"></i>
            </span>
            <p>Get personalized education guidance</p>
          </div>
          <div class="flex items-center">
            <span class="bg-white rounded-full p-2 mr-4">
              <i class="fas fa-users text-indigo-600"></i>
            </span>
            <p>Connect with industry professionals</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Right side - Signup form -->
    <div class="w-full md:w-1/2 py-8 px-4 sm:px-12 lg:px-20 flex flex-col justify-center">
      <div class="text-center md:hidden mb-6">
        <h1 class="text-3xl font-bold text-indigo-600">Career Compass</h1>
        <p class="text-gray-600">Navigating your future</p>
      </div>

      <?php
      // Simple PHP to handle form submission
      if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $fullName = $_POST["fullName"] ?? "";
        $email = $_POST["email"] ?? "";
        $password = $_POST["password"] ?? "";
        $confirmPassword = $_POST["confirmPassword"] ?? "";
        $age = $_POST["age"] ?? "";
        $grade = $_POST["grade"] ?? "";
        $interests = $_POST["interests"] ?? [];
        $newsletter = isset($_POST["newsletter"]) ? true : false;
        $terms = isset($_POST["terms"]) ? true : false;

        // This is where you would validate and save user data
        // For demo purposes, we'll just show a message
        $error = "";

        if (empty($fullName) || empty($email) || empty($password) || empty($age) || empty($grade)) {
          $error = "Please fill in all required fields.";
        } elseif ($password !== $confirmPassword) {
          $error = "Passwords do not match.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $error = "Please enter a valid email address.";
        } elseif (!$terms) {
          $error = "You must agree to the terms and conditions.";
        } elseif (empty($interests)) {
          $error = "Please select at least one career interest.";
        } else {
          // Success! In a real app, you would save to database here
          // For demo, just show success message
          echo '<div class="mb-6 p-4 rounded-md bg-secondary-100 text-secondary-800">
                      <p>Registration successful! Redirecting to login page...</p>
                    </div>';
          // In a real app, you would redirect to login or dashboard
          // header("Location: login.php");
          // exit;
        }

        if (!empty($error)) {
          echo '<div class="mb-6 p-4 rounded-md bg-red-100 text-red-800" id="phpError">
                      <p>' . htmlspecialchars($error) . '</p>
                    </div>';
        }
      }
      ?>

      <form id="signupForm" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="space-y-6">
        <h2 class="text-2xl font-bold text-center mb-8 text-gray-800">Create Your Account</h2>

        <!-- Progress indicators -->
        <div class="flex justify-between mb-6">
          <span id="step1" class="inline-block w-3 h-3 rounded-full bg-indigo-600"></span>
          <span id="step2" class="inline-block w-3 h-3 rounded-full bg-gray-300"></span>
          <span id="step3" class="inline-block w-3 h-3 rounded-full bg-gray-300"></span>
        </div>

        <!-- Step 1: Basic Info -->
        <div id="stepOne" class="space-y-4">
          <!-- Full Name -->
          <div class="relative">
            <input type="text" id="fullName" name="fullName"
              class="input-field peer w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent placeholder-transparent"
              placeholder=" " required>
            <label for="fullName"
              class="floating-label absolute left-4 top-3 text-gray-500 pointer-events-none peer-focus:-top-0.5 peer-focus:text-xs peer-focus:text-indigo-600">Full
              Name</label>
          </div>

          <!-- Email -->
          <div class="relative">
            <input type="email" id="email" name="email"
              class="input-field w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent placeholder-transparent"
              placeholder=" " required>
            <label for="email"
              class="floating-label absolute left-4 top-3 text-gray-500 pointer-events-none peer-focus:-top-0.5 peer-focus:text-xs peer-focus:text-indigo-600">Email
              Address</label>
          </div>

          <!-- Password -->
          <div class="relative">
            <input type="password" id="password" name="password"
              class="input-field w-full border border-gray-300 rounded-lg px-4 py-3 pr-10 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent placeholder-transparent"
              placeholder=" " required>
            <label for="password"
              class="floating-label absolute left-4 top-3 text-gray-500 pointer-events-none peer-focus:-top-0.5 peer-focus:text-xs peer-focus:text-indigo-600">Password</label>
            <span class="absolute right-3 top-3 cursor-pointer toggle-password">
              <i class="far fa-eye text-gray-400"></i>
            </span>
          </div>

          <!-- Confirm Password -->
          <div class="relative">
            <input type="password" id="confirmPassword" name="confirmPassword"
              class="input-field w-full border border-gray-300 rounded-lg px-4 py-3 pr-10 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent placeholder-transparent"
              placeholder=" " required>
            <label for="confirmPassword"
              class="floating-label absolute left-4 top-3 text-gray-500 pointer-events-none peer-focus:-top-0.5 peer-focus:text-xs peer-focus:text-indigo-600">Confirm
              Password</label>
            <span class="absolute right-3 top-3 cursor-pointer toggle-password">
              <i class="far fa-eye text-gray-400"></i>
            </span>
          </div>

          <!-- Next Button -->
          <button type="button" id="toStepTwo"
            class="w-full bg-gradient-custom hover:opacity-90 text-white font-semibold py-3 px-6 rounded-lg transition-all shadow-lg hover:shadow-xl flex justify-center items-center">
            <span>Next</span>
            <i class="fas fa-arrow-right ml-2"></i>
          </button>
        </div>

        <!-- Step 2: Personal Details -->
        <div id="stepTwo" class="space-y-4 hidden">
          <!-- Age -->
          <div class="relative">
            <input type="number" id="age" name="age" min="13" max="100"
              class="input-field w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent placeholder-transparent"
              placeholder=" " required>
            <label for="age"
              class="floating-label absolute left-4 top-3 text-gray-500 pointer-events-none peer-focus:-top-0.5 peer-focus:text-xs peer-focus:text-indigo-600">Age</label>
          </div>

          <!-- Grade/Class -->
          <div class="relative">
            <select id="grade" name="grade"
              class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent appearance-none text-gray-700"
              required>
              <option value="" disabled selected>Select your grade</option>
              <option value="9">9th Grade</option>
              <option value="10">10th Grade</option>
              <option value="11">11th Grade</option>
              <option value="12">12th Grade</option>
              <option value="undergraduate">Undergraduate</option>
              <option value="postgraduate">Postgraduate</option>
              <option value="other">Other</option>
            </select>
            <div class="absolute right-3 top-3 pointer-events-none">
              <i class="fas fa-chevron-down text-gray-400"></i>
            </div>
          </div>

          <!-- Back & Next Buttons -->
          <div class="flex space-x-4">
            <button type="button" id="backToStepOne"
              class="w-1/2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-6 rounded-lg transition-all flex justify-center items-center">
              <i class="fas fa-arrow-left mr-2"></i>
              <span>Back</span>
            </button>
            <button type="button" id="toStepThree"
              class="w-1/2 bg-gradient-custom hover:opacity-90 text-white font-semibold py-3 px-6 rounded-lg transition-all shadow-lg hover:shadow-xl flex justify-center items-center">
              <span>Next</span>
              <i class="fas fa-arrow-right ml-2"></i>
            </button>
          </div>
        </div>

        <!-- Step 3: Career Interests -->
        <div id="stepThree" class="space-y-4 hidden">
          <div class="mb-6">
            <label for="interests" class="block text-gray-700 text-sm font-medium mb-2">Select your career interests
              (multiple choices allowed)</label>
            <div class="grid grid-cols-2 gap-2">
              <div class="flex items-start">
                <input type="checkbox" id="interest_tech" name="interests[]" value="technology" class="mt-1 mr-2">
                <label for="interest_tech" class="text-gray-700">Technology</label>
              </div>
              <div class="flex items-start">
                <input type="checkbox" id="interest_health" name="interests[]" value="healthcare" class="mt-1 mr-2">
                <label for="interest_health" class="text-gray-700">Healthcare</label>
              </div>
              <div class="flex items-start">
                <input type="checkbox" id="interest_business" name="interests[]" value="business" class="mt-1 mr-2">
                <label for="interest_business" class="text-gray-700">Business</label>
              </div>
              <div class="flex items-start">
                <input type="checkbox" id="interest_arts" name="interests[]" value="arts" class="mt-1 mr-2">
                <label for="interest_arts" class="text-gray-700">Arts</label>
              </div>
              <div class="flex items-start">
                <input type="checkbox" id="interest_science" name="interests[]" value="science" class="mt-1 mr-2">
                <label for="interest_science" class="text-gray-700">Science</label>
              </div>
              <div class="flex items-start">
                <input type="checkbox" id="interest_engineering" name="interests[]" value="engineering"
                  class="mt-1 mr-2">
                <label for="interest_engineering" class="text-gray-700">Engineering</label>
              </div>
              <div class="flex items-start">
                <input type="checkbox" id="interest_education" name="interests[]" value="education" class="mt-1 mr-2">
                <label for="interest_education" class="text-gray-700">Education</label>
              </div>
              <div class="flex items-start">
                <input type="checkbox" id="interest_other" name="interests[]" value="other" class="mt-1 mr-2">
                <label for="interest_other" class="text-gray-700">Other</label>
              </div>
            </div>
          </div>

          <!-- Newsletter subscription -->
          <div class="flex items-start mb-6">
            <input type="checkbox" id="newsletter" name="newsletter" class="mt-1 mr-2">
            <label for="newsletter" class="text-gray-700">Keep me updated with career tips and opportunities</label>
          </div>

          <!-- Terms and conditions -->
          <div class="flex items-start">
            <input type="checkbox" id="terms" name="terms" class="mt-1 mr-2" required>
            <label for="terms" class="text-gray-700">I agree to the <a href="#"
                class="text-indigo-600 hover:underline">Terms of Service</a> and <a href="#"
                class="text-indigo-600 hover:underline">Privacy Policy</a></label>
          </div>

          <!-- Back & Submit Buttons -->
          <div class="flex space-x-4">
            <button type="button" id="backToStepTwo"
              class="w-1/2 bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold py-3 px-6 rounded-lg transition-all flex justify-center items-center">
              <i class="fas fa-arrow-left mr-2"></i>
              <span>Back</span>
            </button>
            <button type="submit"
              class="w-1/2 bg-gradient-custom hover:opacity-90 text-white font-semibold py-3 px-6 rounded-lg transition-all shadow-lg hover:shadow-xl flex justify-center items-center">
              <span>Sign Up</span>
              <i class="fas fa-check-circle ml-2"></i>
            </button>
          </div>
        </div>

        <!-- Error display area -->
        <div id="errorDisplay" class="hidden bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md"></div>
      </form>

      <div class="mt-8 text-center">
        <p class="text-gray-600">Already have an account? <a href="login.php"
            class="text-indigo-600 font-semibold hover:underline">Log in here</a></p>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Check for PHP errors and display them in the JS error area too
      const phpError = document.getElementById('phpError');
      const errorDisplay = document.getElementById('errorDisplay');

      if (phpError) {
        errorDisplay.textContent = phpError.textContent.trim();
        errorDisplay.classList.remove('hidden');
        // Hide the PHP error since we're showing it in the JS error area
        phpError.classList.add('hidden');
      }

      // Password visibility toggle
      const togglePasswords = document.querySelectorAll('.toggle-password');
      togglePasswords.forEach(toggle => {
        toggle.addEventListener('click', function() {
          const input = this.parentElement.querySelector('input');
          const icon = this.querySelector('i');

          if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
          } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
          }
        });
      });

      // Multi-step form navigation
      const stepOne = document.getElementById('stepOne');
      const stepTwo = document.getElementById('stepTwo');
      const stepThree = document.getElementById('stepThree');
      const stepIndicator1 = document.getElementById('step1');
      const stepIndicator2 = document.getElementById('step2');
      const stepIndicator3 = document.getElementById('step3');

      // To Step 2
      document.getElementById('toStepTwo').addEventListener('click', function() {
        // Validate Step 1
        const fullName = document.getElementById('fullName').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        errorDisplay.classList.add('hidden');

        if (!fullName) {
          showError("Please enter your full name");
          return;
        }

        if (!email || !isValidEmail(email)) {
          showError("Please enter a valid email address");
          return;
        }

        if (!password || password.length < 8) {
          showError("Password must be at least 8 characters long");
          return;
        }

        if (password !== confirmPassword) {
          showError("Passwords do not match");
          return;
        }

        // Show Step 2
        stepOne.classList.add('hidden');
        stepTwo.classList.remove('hidden');
        stepThree.classList.add('hidden');

        // Update indicators
        stepIndicator1.classList.remove('bg-indigo-600');
        stepIndicator1.classList.add('bg-indigo-400');
        stepIndicator2.classList.add('bg-indigo-600');
        stepIndicator2.classList.remove('bg-gray-300');
      });

      // Back to Step 1
      document.getElementById('backToStepOne').addEventListener('click', function() {
        stepOne.classList.remove('hidden');
        stepTwo.classList.add('hidden');
        stepThree.classList.add('hidden');

        // Update indicators
        stepIndicator1.classList.add('bg-indigo-600');
        stepIndicator1.classList.remove('bg-indigo-400');
        stepIndicator2.classList.remove('bg-indigo-600');
        stepIndicator2.classList.add('bg-gray-300');
      });

      // To Step 3
      document.getElementById('toStepThree').addEventListener('click', function() {
        // Validate Step 2
        const age = document.getElementById('age').value;
        const grade = document.getElementById('grade').value;

        errorDisplay.classList.add('hidden');

        if (!age || age < 13 || age > 100) {
          showError("Please enter a valid age between 13 and 100");
          return;
        }

        if (!grade) {
          showError("Please select your grade/class");
          return;
        }

        // Show Step 3
        stepOne.classList.add('hidden');
        stepTwo.classList.add('hidden');
        stepThree.classList.remove('hidden');

        // Update indicators
        stepIndicator2.classList.remove('bg-indigo-600');
        stepIndicator2.classList.add('bg-indigo-400');
        stepIndicator3.classList.add('bg-indigo-600');
        stepIndicator3.classList.remove('bg-gray-300');
      });

      // Back to Step 2
      document.getElementById('backToStepTwo').addEventListener('click', function() {
        stepOne.classList.add('hidden');
        stepTwo.classList.remove('hidden');
        stepThree.classList.add('hidden');

        // Update indicators
        stepIndicator2.classList.add('bg-indigo-600');
        stepIndicator2.classList.remove('bg-indigo-400');
        stepIndicator3.classList.remove('bg-indigo-600');
        stepIndicator3.classList.add('bg-gray-300');
      });

      // Form submission
      document.getElementById('signupForm').addEventListener('submit', function(e) {
        // Client-side validation before submission
        const fullName = document.getElementById('fullName').value;
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const age = document.getElementById('age').value;
        const grade = document.getElementById('grade').value;
        const terms = document.getElementById('terms').checked;

        // Interest checkboxes
        const interestCheckboxes = document.querySelectorAll('input[name="interests[]"]:checked');

        errorDisplay.classList.add('hidden');

        if (!fullName || !email || !password || !confirmPassword || !age || !grade) {
          e.preventDefault();
          showError("Please fill in all required fields");
          return;
        }

        if (!isValidEmail(email)) {
          e.preventDefault();
          showError("Please enter a valid email address");
          return;
        }

        if (password !== confirmPassword) {
          e.preventDefault();
          showError("Passwords do not match");
          return;
        }

        if (interestCheckboxes.length === 0) {
          e.preventDefault();
          showError("Please select at least one career interest");
          return;
        }

        if (!terms) {
          e.preventDefault();
          showError("You must agree to the terms and conditions");
          return;
        }

        // If all validations pass, the form will submit naturally
      });

      // Helper functions
      function showError(message) {
        errorDisplay.textContent = message;
        errorDisplay.classList.remove('hidden');
        // Scroll to error
        errorDisplay.scrollIntoView({
          behavior: 'smooth',
          block: 'center'
        });
      }

      function isValidEmail(email) {
        const re = /^(([^<>()\[\]\\.,;:\s@"]+(\.[^<>()\[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
        return re.test(String(email).toLowerCase());
      }
    });
  </script>
</body>

</html>