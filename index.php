<?php
// Start session for user authentication
session_start();

// Read the content of the index.html file
$html = file_get_contents('index.html');
if ($html === false) {
  die("Error: Unable to read index.html file");
}

// If user is logged in, update the navigation to show user menu
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
  $fullname = $_SESSION["fullname"];

  // Create user menu HTML with transition delay
  $userMenu = '<div class="relative group">
            <button class="flex items-center text-gray-600 hover:text-primary-600">
              <span class="mr-1">' . htmlspecialchars($fullname) . '</span>
              <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 opacity-0 invisible
                 group-hover:opacity-100 group-hover:visible transition-all duration-300 ease-in-out transform
                 origin-top-right scale-95 group-hover:scale-100" style="transition-delay: 150ms;">
              <a href="src/main/Home.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Dashboard</a>
              <a href="src/auth/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
              <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
              <div class="border-t border-gray-100"></div>
              <a href="src/auth/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                <i class="fas fa-sign-out-alt mr-1"></i> Logout
              </a>
            </div>
          </div>';

  // Replace desktop navigation login/signup
  $html = str_replace(
    '<a href="src/auth/login.php" class="text-gray-600 hover:text-primary-600 transition">Login</a>
        <a href="src/auth/signup.php"
          class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 transition">Get Started</a>',
    $userMenu,
    $html
  );

  // Replace mobile menu login/signup
  $html = str_replace(
    '<a href="src/auth/login.php" class="text-gray-600 hover:text-primary-600 py-2 text-left transition">Login</a>
        <a href="src/auth/signup.php"
          class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 transition">Get Started</a>',
    $userMenu,
    $html
  );

  // Update all Get Started buttons to point to dashboard
  $html = str_replace('href="src/auth/signup.php"', 'href="src/main/Home.php"', $html);
}

// Output the modified HTML
echo $html;
