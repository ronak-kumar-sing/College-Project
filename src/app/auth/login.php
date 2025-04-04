<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | Career Compass</title>
  <!-- Tailwind CSS via CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
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
          },
          fontFamily: {
            sans: ['Inter', 'sans-serif'],
          },
        }
      }
    }
  </script>
  <style type="text/tailwindcss">
    @layer utilities {
            .bg-gradient-custom {
                background: linear-gradient(135deg, #1ea6ff 0%, #1ad18c 100%);
            }
            .text-gradient-custom {
                background-clip: text;
                -webkit-background-clip: text;
                -webkit-text-fill-color: transparent;
                background-image: linear-gradient(135deg, #1ea6ff 0%, #1ad18c 100%);
            }
        }
    </style>
  <!-- Inter font -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>

<body class="bg-gray-50 font-sans">
  <div class="min-h-screen flex flex-col md:flex-row">
    <!-- Left side - Branding -->
    <div class="md:w-1/2 bg-gradient-custom p-8 md:p-12 flex flex-col justify-between text-white">
      <div>
        <h1 class="text-3xl md:text-4xl font-bold mb-2">Career Compass</h1>
        <p class="text-white/80">Navigate your future with confidence</p>
      </div>

      <div class="hidden md:block">
        <div class="mb-8">
          <svg class="w-full max-w-md mx-auto" viewBox="0 0 500 400" fill="none" xmlns="http://www.w3.org/2000/svg">
            <!-- Simple illustration of students and career paths -->
            <path d="M100,300 Q150,250 200,300 T300,300 T400,300" stroke="white" stroke-width="3" fill="none"
              stroke-linecap="round" />
            <circle cx="150" cy="200" r="40" fill="white" fill-opacity="0.2" />
            <circle cx="250" cy="150" r="50" fill="white" fill-opacity="0.2" />
            <circle cx="350" cy="200" r="40" fill="white" fill-opacity="0.2" />
            <path d="M150,180 L150,220 M140,190 L160,210 M140,210 L160,190" stroke="white" stroke-width="2" />
            <path d="M250,130 L250,170 M240,140 L260,160 M240,160 L260,140" stroke="white" stroke-width="2" />
            <path d="M350,180 L350,220 M340,190 L360,210 M340,210 L360,190" stroke="white" stroke-width="2" />
          </svg>
        </div>

        <div class="space-y-4">
          <div class="flex items-start space-x-3">
            <div class="bg-white/20 rounded-full p-2 mt-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                  clip-rule="evenodd" />
              </svg>
            </div>
            <div>
              <h3 class="font-semibold">Personalized Guidance</h3>
              <p class="text-sm text-white/70">Get career recommendations tailored to your skills and interests</p>
            </div>
          </div>

          <div class="flex items-start space-x-3">
            <div class="bg-white/20 rounded-full p-2 mt-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path
                  d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z" />
              </svg>
            </div>
            <div>
              <h3 class="font-semibold">Learning Resources</h3>
              <p class="text-sm text-white/70">Access courses, tutorials, and materials to build your skills</p>
            </div>
          </div>

          <div class="flex items-start space-x-3">
            <div class="bg-white/20 rounded-full p-2 mt-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                  d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z"
                  clip-rule="evenodd" />
              </svg>
            </div>
            <div>
              <h3 class="font-semibold">Mentorship Opportunities</h3>
              <p class="text-sm text-white/70">Connect with professionals in your field of interest</p>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-8 md:mt-0">
        <p class="text-sm text-white/60">&copy; 2025 Career Compass. All rights reserved.</p>
      </div>
    </div>

    <!-- Right side - Login Form -->
    <div class="md:w-1/2 p-8 md:p-12 flex items-center justify-center">
      <div class="w-full max-w-md">
        <div class="text-center mb-10">
          <h2 class="text-3xl font-bold text-gray-800">Welcome Back!</h2>
          <p class="text-gray-600 mt-2">Sign in to continue your career journey</p>
        </div>

        <?php
        // Simple PHP to handle form submission
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $email = $_POST["email"] ?? "";
            $password = $_POST["password"] ?? "";
            $remember = isset($_POST["remember"]) ? true : false;

            // This is where you would validate credentials
            // For demo purposes, we'll just show a message
            $error = "";

            if (empty($email) || empty($password)) {
                $error = "Please enter both email and password.";
            } else {
                // In a real application, you would check credentials against a database
                // For demo, we'll assume login is successful if email contains "@"
                if (strpos($email, "@") !== false) {
                    // Success! Redirect to dashboard (in a real app)
                    // header("Location: dashboard.php");
                    // exit;

                    // For demo, just show success message
                    echo '<div class="mb-6 p-4 rounded-md bg-secondary-100 text-secondary-800">
                            <p>Login successful! Redirecting to dashboard...</p>
                          </div>';
                } else {
                    $error = "Invalid email or password.";
                }
            }

            if (!empty($error)) {
                echo '<div class="mb-6 p-4 rounded-md bg-red-100 text-red-800">
                        <p>' . htmlspecialchars($error) . '</p>
                      </div>';
            }
        }
        ?>

        <form method="POST" action="<?php echo htmlspecialchars($_SERVER[" PHP_SELF"]); ?>" class="space-y-6">
          <!-- Email Field -->
          <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20"
                  fill="currentColor">
                  <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                  <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                </svg>
              </div>
              <input type="email" id="email" name="email"
                class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="your.email@example.com" required>
            </div>
          </div>

          <!-- Password Field -->
          <div>
            <div class="flex items-center justify-between mb-1">
              <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
              <a href="#" class="text-sm text-primary-600 hover:text-primary-500">Forgot password?</a>
            </div>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20"
                  fill="currentColor">
                  <path fill-rule="evenodd"
                    d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                    clip-rule="evenodd" />
                </svg>
              </div>
              <input type="password" id="password" name="password"
                class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500"
                placeholder="••••••••" required>
              <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                <button type="button" id="togglePassword" class="text-gray-400 hover:text-gray-500 focus:outline-none">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"
                    id="eyeIcon">
                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                    <path fill-rule="evenodd"
                      d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z"
                      clip-rule="evenodd" />
                  </svg>
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 hidden" viewBox="0 0 20 20" fill="currentColor"
                    id="eyeOffIcon">
                    <path fill-rule="evenodd"
                      d="M3.707 2.293a1 1 0 00-1.414 1.414l14 14a1 1 0 001.414-1.414l-1.473-1.473A10.014 10.014 0 0019.542 10C18.268 5.943 14.478 3 10 3a9.958 9.958 0 00-4.512 1.074l-1.78-1.781zm4.261 4.26l1.514 1.515a2.003 2.003 0 012.45 2.45l1.514 1.514a4 4 0 00-5.478-5.478z"
                      clip-rule="evenodd" />
                    <path
                      d="M12.454 16.697L9.75 13.992a4 4 0 01-3.742-3.741L2.335 6.578A9.98 9.98 0 00.458 10c1.274 4.057 5.065 7 9.542 7 .847 0 1.669-.105 2.454-.303z" />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center">
            <input type="checkbox" id="remember" name="remember"
              class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
            <label for="remember" class="ml-2 block text-sm text-gray-700">Remember me</label>
          </div>

          <!-- Login Button -->
          <div>
            <button type="submit"
              class="w-full flex justify-center py-3 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-gradient-custom hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-150 ease-in-out">
              Sign In
            </button>
          </div>

          <!-- Divider -->
          <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
              <div class="w-full border-t border-gray-300"></div>
            </div>
            <div class="relative flex justify-center text-sm">
              <span class="px-2 bg-white text-gray-500">Or continue with</span>
            </div>
          </div>

          <!-- Social Login -->
          <div>
            <button type="button"
              class="w-full flex items-center justify-center py-2.5 px-4 border border-gray-300 rounded-lg shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-all duration-150 ease-in-out">
              <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path
                  d="M6 12C6 8.68629 8.68629 6 12 6C13.6569 6 15.1569 6.67157 16.2426 7.75736L19.0711 4.92893C17.1823 3.04019 14.6838 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22C17.5228 22 22 17.5228 22 12V10H12V14H18C17.3453 16.1652 15.8061 17.932 13.8126 18.6873L16.2426 21.1213C18.5858 19.4142 20 15.9142 20 12H6Z"
                  fill="#4285F4" />
                <path
                  d="M4 12C4 7.58172 7.58172 4 12 4C14.0484 4 15.8841 4.80447 17.2308 6.11673L19.6155 3.73205C17.6974 1.85149 15.0105 0.666667 12 0.666667C5.8483 0.666667 0.666667 5.8483 0.666667 12C0.666667 18.1517 5.8483 23.3333 12 23.3333C18.1517 23.3333 23.3333 18.1517 23.3333 12V8.66667H12V12.6667H19.0087C18.2216 15.4249 16.2122 17.5454 13.6667 18.3927L16.6667 21.3927C19.0609 19.6022 20.6667 16.0355 20.6667 12H4Z"
                  fill="#EA4335" />
                <path
                  d="M4 12C4 7.58172 7.58172 4 12 4V0.666667C5.8483 0.666667 0.666667 5.8483 0.666667 12C0.666667 18.1517 5.8483 23.3333 12 23.3333V20C7.58172 20 4 16.4183 4 12Z"
                  fill="#34A853" />
                <path
                  d="M12 23.3333C15.0105 23.3333 17.6974 22.1485 19.6155 20.268L16.6667 17.268C15.3289 18.5296 13.7453 19.3333 12 19.3333C8.71867 19.3333 5.95718 16.9404 5.17825 13.8142L2.19139 16.6767C3.92653 20.6524 7.61386 23.3333 12 23.3333Z"
                  fill="#FBBC05" />
              </svg>
              Sign in with Google
            </button>
          </div>

          <!-- Sign Up Link -->
          <div class="text-center mt-8">
            <p class="text-sm text-gray-600">
              Don't have an account?
              <a href="#" class="font-medium text-primary-600 hover:text-primary-500">Sign up</a>
            </p>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script>
    // Toggle password visibility
    document.getElementById('togglePassword').addEventListener('click', function () {
      const passwordInput = document.getElementById('password');
      const eyeIcon = document.getElementById('eyeIcon');
      const eyeOffIcon = document.getElementById('eyeOffIcon');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.add('hidden');
        eyeOffIcon.classList.remove('hidden');
      } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('hidden');
        eyeOffIcon.classList.add('hidden');
      }
    });

    // Simple form validation
    document.querySelector('form').addEventListener('submit', function (e) {
      const email = document.getElementById('email').value;
      const password = document.getElementById('password').value;
      let isValid = true;

      // Very basic validation
      if (!email.includes('@')) {
        isValid = false;
        // In a real app, you would show an error message
      }

      if (password.length < 6) {
        isValid = false;
        // In a real app, you would show an error message
      }

      // For demo purposes, we'll let the form submit anyway
      // In a real app, you might prevent submission: if (!isValid) e.preventDefault();
    });
  </script>
</body>

</html>