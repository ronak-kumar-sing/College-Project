<?php
// If this is a confirmation page after logout
session_start();
$message = "You have been successfully logged out.";

// Get the base URL for links
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'];
$baseUrl = $protocol . '://' . $host . '/College-Project/';
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Logged Out</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <meta http-equiv="refresh" content="3;url=<?php echo $baseUrl; ?>">
</head>

<body class="bg-gray-50 min-h-screen flex items-center justify-center">
  <div class="bg-white p-8 rounded-lg shadow-md max-w-md w-full text-center">
    <div class="mb-4 text-green-500">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
      </svg>
    </div>
    <h1 class="text-2xl font-bold text-gray-800 mb-3">Logged Out</h1>
    <p class="text-gray-600 mb-6"><?php echo $message; ?></p>
    <p class="text-gray-500 text-sm mb-4">You will be redirected in 3 seconds...</p>
    <a href="<?php echo $baseUrl; ?>" class="block w-full bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 transition">
      Return to Home
    </a>
    <a href="<?php
              echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') .
                '://' . $_SERVER['HTTP_HOST'] . '/College-Project/src/auth/logout.php';
              ?>" class="block w-full bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 transition mt-4">Logout</a>
  </div>
</body>

</html>