<?php
// Start session for user authentication
session_start();

// Include database configuration
require_once "config.php";

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  header("location: login.php");
  exit;
}

// Get user information
$user_id = $_SESSION["id"];
$fullname = $_SESSION["fullname"];
$email = $_SESSION["email"];

// Initialize variables for profile update
$fullname_err = $email_err = $current_password_err = $new_password_err = $confirm_password_err = "";
$success_message = $error_message = "";

// Process profile update form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_profile"])) {
  // Validate fullname
  if (empty(trim($_POST["fullname"]))) {
    $fullname_err = "Please enter your full name.";
  } else {
    $new_fullname = trim($_POST["fullname"]);
  }

  // Validate email
  if (empty(trim($_POST["email"]))) {
    $email_err = "Please enter your email.";
  } else {
    // Check if email is different from current email
    if (trim($_POST["email"]) !== $email) {
      // Prepare a select statement to check if email already exists
      $sql = "SELECT id FROM users WHERE email = ? AND id != ?";

      if ($stmt = $conn->prepare($sql)) {
        // Bind variables to the prepared statement as parameters
        $stmt->bind_param("si", $param_email, $user_id);

        // Set parameters
        $param_email = trim($_POST["email"]);

        // Attempt to execute the prepared statement
        if ($stmt->execute()) {
          // Store result
          $stmt->store_result();

          if ($stmt->num_rows > 0) {
            $email_err = "This email is already taken.";
          } else {
            $new_email = trim($_POST["email"]);
          }
        } else {
          $error_message = "Oops! Something went wrong. Please try again later.";
        }

        // Close statement
        $stmt->close();
      }
    } else {
      $new_email = $email; // Email unchanged
    }
  }

  // Check if there are no errors
  if (empty($fullname_err) && empty($email_err)) {
    // Prepare an update statement
    $sql = "UPDATE users SET fullname = ?, email = ? WHERE id = ?";

    if ($stmt = $conn->prepare($sql)) {
      // Bind variables to the prepared statement as parameters
      $stmt->bind_param("ssi", $param_fullname, $param_email, $param_id);

      // Set parameters
      $param_fullname = isset($new_fullname) ? $new_fullname : $fullname;
      $param_email = isset($new_email) ? $new_email : $email;
      $param_id = $user_id;

      // Attempt to execute the prepared statement
      if ($stmt->execute()) {
        // Update session variables
        $_SESSION["fullname"] = $param_fullname;
        $_SESSION["email"] = $param_email;

        // Refresh user information
        $fullname = $param_fullname;
        $email = $param_email;

        $success_message = "Profile updated successfully.";
      } else {
        $error_message = "Oops! Something went wrong. Please try again later.";
      }

      // Close statement
      $stmt->close();
    }
  }
}

// Process password change form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["change_password"])) {
  // Validate current password
  if (empty(trim($_POST["current_password"]))) {
    $current_password_err = "Please enter your current password.";
  } else {
    // Verify current password
    $sql = "SELECT password FROM users WHERE id = ?";

    if ($stmt = $conn->prepare($sql)) {
      // Bind variables to the prepared statement as parameters
      $stmt->bind_param("i", $param_id);

      // Set parameters
      $param_id = $user_id;

      // Attempt to execute the prepared statement
      if ($stmt->execute()) {
        // Store result
        $stmt->store_result();

        // Check if user exists
        if ($stmt->num_rows == 1) {
          // Bind result variables
          $stmt->bind_result($hashed_password);
          if ($stmt->fetch()) {
            if (!password_verify($_POST["current_password"], $hashed_password)) {
              $current_password_err = "The current password is incorrect.";
            }
          }
        } else {
          $error_message = "Oops! Something went wrong. Please try again later.";
        }
      } else {
        $error_message = "Oops! Something went wrong. Please try again later.";
      }

      // Close statement
      $stmt->close();
    }
  }

  // Validate new password
  if (empty(trim($_POST["new_password"]))) {
    $new_password_err = "Please enter a new password.";
  } elseif (strlen(trim($_POST["new_password"])) < 8) {
    $new_password_err = "Password must have at least 8 characters.";
  } else {
    $new_password = trim($_POST["new_password"]);
  }

  // Validate confirm password
  if (empty(trim($_POST["confirm_password"]))) {
    $confirm_password_err = "Please confirm the password.";
  } else {
    $confirm_password = trim($_POST["confirm_password"]);
    if (empty($new_password_err) && ($new_password != $confirm_password)) {
      $confirm_password_err = "Passwords did not match.";
    }
  }

  // Check if there are no errors
  if (empty($current_password_err) && empty($new_password_err) && empty($confirm_password_err)) {
    // Prepare an update statement
    $sql = "UPDATE users SET password = ? WHERE id = ?";

    if ($stmt = $conn->prepare($sql)) {
      // Bind variables to the prepared statement as parameters
      $stmt->bind_param("si", $param_password, $param_id);

      // Set parameters
      $param_password = password_hash($new_password, PASSWORD_DEFAULT);
      $param_id = $user_id;

      // Attempt to execute the prepared statement
      if ($stmt->execute()) {
        $success_message = "Password changed successfully.";
      } else {
        $error_message = "Oops! Something went wrong. Please try again later.";
      }

      // Close statement
      $stmt->close();
    }
  }
}

// Get user activity data
// 1. Job applications
$job_applications = array();
$sql = "SELECT ja.id, ja.job_title, ja.company, ja.status, ja.applied_at
        FROM job_applications ja
        WHERE ja.user_id = ?
        ORDER BY ja.applied_at DESC
        LIMIT 5";

if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $job_applications[] = $row;
  }

  $stmt->close();
}

// 2. Job bookmarks
$job_bookmarks = array();
$sql = "SELECT jb.id, jb.job_title, jb.company, jb.saved_at
        FROM job_bookmarks jb
        WHERE jb.user_id = ?
        ORDER BY jb.saved_at DESC
        LIMIT 5";

if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $job_bookmarks[] = $row;
  }

  $stmt->close();
}

// 3. Posted jobs
$posted_jobs = array();
$sql = "SELECT jl.id, jl.title, jl.company, jl.created_at, jl.is_active
        FROM job_listings jl
        WHERE jl.user_id = ?
        ORDER BY jl.created_at DESC
        LIMIT 5";

if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();

  while ($row = $result->fetch_assoc()) {
    $posted_jobs[] = $row;
  }

  $stmt->close();
}

// 4. Count total applications, bookmarks, and posted jobs
$counts = array(
  'applications' => 0,
  'bookmarks' => 0,
  'posted_jobs' => 0
);

$sql = "SELECT
        (SELECT COUNT(*) FROM job_applications WHERE user_id = ?) as application_count,
        (SELECT COUNT(*) FROM job_bookmarks WHERE user_id = ?) as bookmark_count,
        (SELECT COUNT(*) FROM job_listings WHERE user_id = ?) as posted_job_count";

if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param("iii", $user_id, $user_id, $user_id);
  $stmt->execute();
  $stmt->bind_result($counts['applications'], $counts['bookmarks'], $counts['posted_jobs']);
  $stmt->fetch();
  $stmt->close();
}

// Get user registration date
$registration_date = "";
$sql = "SELECT created_at FROM users WHERE id = ?";
if ($stmt = $conn->prepare($sql)) {
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $stmt->bind_result($registration_date);
  $stmt->fetch();
  $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>User Profile - CareerCompass</title>
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
      <div class="flex justify-between items-center">
        <a href="../../index.html" class="flex items-center space-x-2">
          <i class="fas fa-compass text-primary-600 text-xl"></i>
          <span class="text-xl font-bold">CareerCompass</span>
        </a>
        <nav class="flex space-x-6">
          <a href="../main/Home.php" class="text-gray-600 hover:text-primary-600">Career Assessment</a>
          <a href="../main/AiRoadmap.php" class="text-gray-600 hover:text-primary-600">Roadmap Generator</a>
          <a href="../main/Jobsections.php" class="text-gray-600 hover:text-primary-600">Job Listings</a>
          <div class="relative group">
            <button class="flex items-center text-primary-600 font-medium">
              <span class="mr-1"><?php echo htmlspecialchars($fullname); ?></span>
              <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
              <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
              <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
              <div class="border-t border-gray-100"></div>
              <a href="logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                <i class="fas fa-sign-out-alt mr-1"></i> Logout
              </a>
            </div>
          </div>
        </nav>
      </div>
    </div>
  </header>

  <!-- Main content -->
  <main class="flex-grow container mx-auto px-4 py-8">
    <div class="max-w-5xl mx-auto">
      <!-- Success/Error Messages -->
      <?php if (!empty($success_message)): ?>
        <div class="mb-6 p-4 bg-green-100 border border-green-200 text-green-700 rounded-md">
          <?php echo $success_message; ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($error_message)): ?>
        <div class="mb-6 p-4 bg-red-100 border border-red-200 text-red-700 rounded-md">
          <?php echo $error_message; ?>
        </div>
      <?php endif; ?>

      <!-- Profile Header -->
      <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
          <div class="w-24 h-24 bg-primary-100 rounded-full flex items-center justify-center text-primary-600 text-3xl font-bold">
            <?php
            // Display initials
            $initials = '';
            $words = explode(' ', $fullname);
            foreach ($words as $word) {
              if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
                if (strlen($initials) >= 2) break;
              }
            }
            echo $initials;
            ?>
          </div>
          <div class="flex-1 text-center md:text-left">
            <h1 class="text-2xl font-bold"><?php echo htmlspecialchars($fullname); ?></h1>
            <p class="text-gray-600"><?php echo htmlspecialchars($email); ?></p>
            <p class="text-sm text-gray-500 mt-1">Member since <?php echo date('F Y', strtotime($registration_date)); ?></p>

            <div class="mt-4 flex flex-wrap justify-center md:justify-start gap-4">
              <div class="text-center px-4 py-2 bg-gray-50 rounded-md">
                <div class="text-xl font-bold text-primary-600"><?php echo $counts['applications']; ?></div>
                <div class="text-sm text-gray-600">Applications</div>
              </div>
              <div class="text-center px-4 py-2 bg-gray-50 rounded-md">
                <div class="text-xl font-bold text-primary-600"><?php echo $counts['bookmarks']; ?></div>
                <div class="text-sm text-gray-600">Saved Jobs</div>
              </div>
              <div class="text-center px-4 py-2 bg-gray-50 rounded-md">
                <div class="text-xl font-bold text-primary-600"><?php echo $counts['posted_jobs']; ?></div>
                <div class="text-sm text-gray-600">Posted Jobs</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabs -->
      <div class="mb-6">
        <div class="border-b border-gray-200">
          <nav class="-mb-px flex space-x-8">
            <button id="tab-profile" class="tab-button border-primary-500 text-primary-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
              Profile Information
            </button>
            <button id="tab-password" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
              Change Password
            </button>
            <button id="tab-activity" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm">
              Activity
            </button>
          </nav>
        </div>
      </div>

      <!-- Tab Content -->
      <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <!-- Profile Information Tab -->
        <div id="content-profile" class="tab-content p-6">
          <h2 class="text-lg font-medium mb-4">Update Profile Information</h2>
          <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="space-y-4">
              <div>
                <label for="fullname" class="block text-sm font-medium text-gray-700 mb-1">Full Name</label>
                <input type="text" id="fullname" name="fullname" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500" value="<?php echo htmlspecialchars($fullname); ?>" required>
                <?php if (!empty($fullname_err)): ?>
                  <p class="mt-1 text-sm text-red-600"><?php echo $fullname_err; ?></p>
                <?php endif; ?>
              </div>

              <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                <input type="email" id="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500" value="<?php echo htmlspecialchars($email); ?>" required>
                <?php if (!empty($email_err)): ?>
                  <p class="mt-1 text-sm text-red-600"><?php echo $email_err; ?></p>
                <?php endif; ?>
              </div>

              <div class="pt-4">
                <button type="submit" name="update_profile" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                  Update Profile
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- Change Password Tab -->
        <div id="content-password" class="tab-content p-6 hidden">
          <h2 class="text-lg font-medium mb-4">Change Password</h2>
          <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="space-y-4">
              <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                <input type="password" id="current_password" name="current_password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500" required>
                <?php if (!empty($current_password_err)): ?>
                  <p class="mt-1 text-sm text-red-600"><?php echo $current_password_err; ?></p>
                <?php endif; ?>
              </div>

              <div>
                <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                <input type="password" id="new_password" name="new_password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500" required>
                <p class="mt-1 text-xs text-gray-500">Password must be at least 8 characters long</p>
                <?php if (!empty($new_password_err)): ?>
                  <p class="mt-1 text-sm text-red-600"><?php echo $new_password_err; ?></p>
                <?php endif; ?>
              </div>

              <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500" required>
                <?php if (!empty($confirm_password_err)): ?>
                  <p class="mt-1 text-sm text-red-600"><?php echo $confirm_password_err; ?></p>
                <?php endif; ?>
              </div>

              <div class="pt-4">
                <button type="submit" name="change_password" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                  Change Password
                </button>
              </div>
            </div>
          </form>
        </div>

        <!-- Activity Tab -->
        <div id="content-activity" class="tab-content p-6 hidden">
          <h2 class="text-lg font-medium mb-4">Recent Activity</h2>

          <!-- Job Applications -->
          <div class="mb-6">
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-medium">Job Applications</h3>
              <a href="../main/Jobsections.php" class="text-sm text-primary-600 hover:text-primary-500">View All</a>
            </div>

            <?php if (empty($job_applications)): ?>
              <p class="text-gray-500 text-sm italic">No job applications yet.</p>
            <?php else: ?>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied On</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($job_applications as $application): ?>
                      <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($application['job_title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($application['company']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                            <?php echo htmlspecialchars($application['status']); ?>
                          </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y', strtotime($application['applied_at'])); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <!-- Saved Jobs -->
          <div class="mb-6">
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-medium">Saved Jobs</h3>
              <a href="../main/Jobsections.php" class="text-sm text-primary-600 hover:text-primary-500">View All</a>
            </div>

            <?php if (empty($job_bookmarks)): ?>
              <p class="text-gray-500 text-sm italic">No saved jobs yet.</p>
            <?php else: ?>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saved On</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($job_bookmarks as $bookmark): ?>
                      <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($bookmark['job_title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($bookmark['company']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y', strtotime($bookmark['saved_at'])); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>

          <!-- Posted Jobs -->
          <div>
            <div class="flex items-center justify-between mb-3">
              <h3 class="font-medium">Posted Jobs</h3>
              <a href="../main/Jobsections.php" class="text-sm text-primary-600 hover:text-primary-500">View All</a>
            </div>

            <?php if (empty($posted_jobs)): ?>
              <p class="text-gray-500 text-sm italic">No posted jobs yet.</p>
            <?php else: ?>
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Posted On</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($posted_jobs as $job): ?>
                      <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($job['title']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($job['company']); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                          <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $job['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo $job['is_active'] ?: 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo $job['is_active'] ? 'Active' : 'Inactive'; ?>
                          </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Footer -->
  <footer class="py-4 px-6 bg-white border-t mt-8">
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
    // Tab functionality
    const tabButtons = document.querySelectorAll('.tab-button');
    const tabContents = document.querySelectorAll('.tab-content');

    tabButtons.forEach(button => {
      button.addEventListener('click', () => {
        // Remove active class from all buttons and contents
        tabButtons.forEach(btn => {
          btn.classList.remove('border-primary-500', 'text-primary-600');
          btn.classList.add('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        });

        tabContents.forEach(content => {
          content.classList.add('hidden');
        });

        // Add active class to clicked button
        button.classList.remove('border-transparent', 'text-gray-500', 'hover:text-gray-700', 'hover:border-gray-300');
        button.classList.add('border-primary-500', 'text-primary-600');

        // Show corresponding content
        const contentId = 'content-' + button.id.split('-')[1];
        document.getElementById(contentId).classList.remove('hidden');
      });
    });
  </script>
</body>

</html>