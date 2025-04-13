<?php
// Start session for user authentication
session_start();

// Include database configuration
require_once "../auth/config.php";

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  header("location: ../auth/login.php");
  exit;
}

// Get user information
$user_id = $_SESSION["id"];
$fullname = $_SESSION["fullname"];
$email = $_SESSION["email"];

// Function to save job application
function saveJobApplication($userId, $jobId, $jobTitle, $company, $status = 'Applied')
{
  global $conn;

  // Prepare an insert statement
  $sql = "INSERT INTO job_applications (user_id, job_id, job_title, company, status, applied_at)
            VALUES (?, ?, ?, ?, ?, NOW())";

  if ($stmt = $conn->prepare($sql)) {
    // Bind variables to the prepared statement as parameters
    $stmt->bind_param("iisss", $userId, $jobId, $jobTitle, $company, $status);

    // Execute the statement
    $success = $stmt->execute();

    // Close statement
    $stmt->close();

    return $success;
  }

  return false;
}

// Function to save job bookmark
function saveJobBookmark($userId, $jobId, $jobTitle, $company)
{
  global $conn;

  // Prepare an insert statement
  $sql = "INSERT INTO job_bookmarks (user_id, job_id, job_title, company, saved_at)
            VALUES (?, ?, ?, ?, NOW())";

  if ($stmt = $conn->prepare($sql)) {
    // Bind variables to the prepared statement as parameters
    $stmt->bind_param("iiss", $userId, $jobId, $jobTitle, $company);

    // Execute the statement
    $success = $stmt->execute();

    // Close statement
    $stmt->close();

    return $success;
  }

  return false;
}

// Function to get saved job bookmarks
function getSavedJobBookmarks($userId)
{
  global $conn;

  $bookmarks = array();

  // Prepare a select statement
  $sql = "SELECT job_id FROM job_bookmarks WHERE user_id = ?";

  if ($stmt = $conn->prepare($sql)) {
    // Bind variables to the prepared statement as parameters
    $stmt->bind_param("i", $userId);

    // Execute the statement
    $stmt->execute();

    // Bind result variables
    $stmt->bind_result($jobId);

    // Fetch results
    while ($stmt->fetch()) {
      $bookmarks[] = $jobId;
    }

    // Close statement
    $stmt->close();
  }

  return $bookmarks;
}

// Function to get user posted jobs
function getUserPostedJobs($userId = null)
{
  global $conn;

  $jobs = array();

  // Prepare a select statement
  $sql = "SELECT id, user_id, title, company, logo, location, job_type, salary, description, skills,
            created_at, expires_at, is_active FROM job_listings";

  // If userId is provided, filter by user
  if ($userId) {
    $sql .= " WHERE user_id = ?";
  }

  $sql .= " ORDER BY created_at DESC";

  if ($stmt = $conn->prepare($sql)) {
    // Bind variables if filtering by user
    if ($userId) {
      $stmt->bind_param("i", $userId);
    }

    // Execute the statement
    $stmt->execute();

    // Bind result variables
    $result = $stmt->get_result();

    // Fetch results
    while ($row = $result->fetch_assoc()) {
      // Convert skills from JSON to array
      $row['skills'] = json_decode($row['skills'], true);

      // Calculate days ago for posting date
      $postedDate = new DateTime($row['created_at']);
      $now = new DateTime();
      $interval = $postedDate->diff($now);

      if ($interval->days == 0) {
        $row['posted'] = 'Today';
      } elseif ($interval->days == 1) {
        $row['posted'] = '1 day ago';
      } else {
        $row['posted'] = $interval->days . ' days ago';
      }

      // Add random applicants count for display purposes
      $row['applicants'] = rand(5, 60);

      $jobs[] = $row;
    }

    // Close statement
    $stmt->close();
  }

  return $jobs;
}

// Create job applications table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS job_applications (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    job_id INT(11) NOT NULL,
    job_title VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,
    status VARCHAR(50) NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) !== TRUE) {
  die("Error creating job_applications table: " . $conn->error);
}

// Create job bookmarks table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS job_bookmarks (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    job_id INT(11) NOT NULL,
    job_title VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) !== TRUE) {
  die("Error creating job_bookmarks table: " . $conn->error);
}

// Create job listings table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS job_listings (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,
    logo VARCHAR(255) NOT NULL,
    location VARCHAR(100) NOT NULL,
    job_type VARCHAR(50) NOT NULL,
    salary VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    skills TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATE,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";

if ($conn->query($sql) !== TRUE) {
  die("Error creating job_listings table: " . $conn->error);
}

// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {
  header("Content-Type: application/json");

  if ($_POST["action"] == "apply_job") {
    // Get data from POST request
    $jobId = $_POST["jobId"];
    $jobTitle = $_POST["jobTitle"];
    $company = $_POST["company"];

    // Save job application
    $success = saveJobApplication($user_id, $jobId, $jobTitle, $company);

    // Return response
    echo json_encode(array("success" => $success));
    exit;
  } elseif ($_POST["action"] == "save_job") {
    // Get data from POST request
    $jobId = $_POST["jobId"];
    $jobTitle = $_POST["jobTitle"];
    $company = $_POST["company"];

    // Save job bookmark
    $success = saveJobBookmark($user_id, $jobId, $jobTitle, $company);

    // Return response
    echo json_encode(array("success" => $success));
    exit;
  } elseif ($_POST["action"] == "get_bookmarks") {
    // Get saved job bookmarks
    $bookmarks = getSavedJobBookmarks($user_id);

    // Return response
    echo json_encode(array("bookmarks" => $bookmarks));
    exit;
  } elseif ($_POST["action"] == "post_job") {
    // Get data from POST request
    $title = $_POST["title"];
    $company = $_POST["company"];
    $location = $_POST["location"];
    $jobType = $_POST["jobType"];
    $salary = $_POST["salary"];
    $description = $_POST["description"];
    $skills = json_encode(explode(',', $_POST["skills"]));
    $expiresAt = $_POST["expiresAt"];

    // Generate logo from company name
    $companyInitials = '';
    $words = explode(' ', $company);
    foreach ($words as $word) {
      if (!empty($word)) {
        $companyInitials .= strtoupper(substr($word, 0, 1));
      }
    }

    // Generate random background color for logo
    $colors = ['0D8ABC', '4C1D95', '065F46', '9D174D', '1E3A8A', '7C2D12', '5B21B6', '1E40AF'];
    $randomColor = $colors[array_rand($colors)];

    $logo = "https://ui-avatars.com/api/?name=" . urlencode($companyInitials) . "&background=" . $randomColor . "&color=fff";

    // Prepare an insert statement
    $sql = "INSERT INTO job_listings (user_id, title, company, logo, location, job_type, salary, description, skills, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
      // Bind variables to the prepared statement as parameters
      $stmt->bind_param("isssssssss", $user_id, $title, $company, $logo, $location, $jobType, $salary, $description, $skills, $expiresAt);

      // Execute the statement
      $success = $stmt->execute();

      // Get the ID of the newly inserted job
      $jobId = $conn->insert_id;

      // Close statement
      $stmt->close();

      // Return response
      echo json_encode(array("success" => $success, "jobId" => $jobId));
      exit;
    }

    echo json_encode(array("success" => false, "message" => "Error creating job listing"));
    exit;
  }
}

// Get saved job bookmarks for display
$savedJobBookmarks = getSavedJobBookmarks($user_id);

// Get user posted jobs
$userPostedJobs = getUserPostedJobs($user_id);

// Get all jobs
$allJobs = getUserPostedJobs();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>JobPulse - Trending Jobs Platform</title>
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
              DEFAULT: '#0a66c2', // LinkedIn blue
              light: '#e8f0fe',
              dark: '#004182',
            },
            secondary: {
              DEFAULT: '#057642', // Green for "Apply" buttons
            },
            neutral: {
              DEFAULT: '#f3f2ef', // LinkedIn background
              dark: '#666666',
            }
          },
          fontFamily: {
            sans: ['Inter', 'system-ui', 'sans-serif'],
          },
        }
      }
    }
  </script>
  <style type="text/tailwindcss">
    @layer components {
      .btn {
        @apply px-4 py-2 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2;
      }
      .btn-primary {
        @apply bg-primary text-white hover:bg-primary-dark focus:ring-primary;
      }
      .btn-outline {
        @apply border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-primary;
      }
      .btn-secondary {
        @apply bg-secondary text-white hover:bg-green-700 focus:ring-green-500;
      }
      .card {
        @apply bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow;
      }
      .input {
        @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-primary focus:border-primary;
      }
      .badge {
        @apply inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium;
      }
      .badge-blue {
        @apply bg-blue-100 text-blue-800;
      }
      .badge-green {
        @apply bg-green-100 text-green-800;
      }
      .badge-purple {
        @apply bg-purple-100 text-purple-800;
      }
      .badge-orange {
        @apply bg-orange-100 text-orange-800;
      }
    }
  </style>
  <!-- Inter font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
</head>

<body class="bg-neutral min-h-screen">
  <!-- Header -->
  <header class="sticky top-0 z-10 bg-white border-b border-gray-200">
    <div class="container mx-auto px-4 py-3 flex justify-between items-center">
      <div class="flex items-center">
        <a href="../../index.php" class="flex items-center space-x-2">
          <i class="fas fa-compass text-primary text-xl"></i>
          <span class="text-xl font-bold">CareerCompass</span>
        </a>
        <div class="hidden md:flex space-x-6 ml-8">
          <a href="Home.php" class="flex items-center text-gray-600 hover:text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Assessment
          </a>
          <a href="AiRoadmap.php" class="flex items-center text-gray-600 hover:text-primary">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
            </svg>
            Roadmap
          </a>
          <a href="Jobsections.php" class="flex items-center text-primary font-medium">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24"
              stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
            Jobs
          </a>
        </div>
      </div>
      <div class="flex items-center space-x-4">
        <button id="post-job-button" class="btn btn-secondary hidden md:flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Post a Job
        </button>
        <button id="insights-button" class="btn btn-outline hidden md:block">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1 inline" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
          </svg>
          Job Insights
        </button>
        <div class="relative group">
          <button class="flex items-center text-gray-600 hover:text-primary">
            <span class="mr-1"><?php echo htmlspecialchars($fullname); ?></span>
            <i class="fas fa-chevron-down text-xs"></i>
          </button>
          <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 hidden group-hover:block">
            <a href="../auth/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
            <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Settings</a>
            <div class="border-t border-gray-100"></div>
            <a href="../auth/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
              <i class="fas fa-sign-out-alt mr-1"></i> Logout
            </a>
          </div>
        </div>
      </div>
    </div>
  </header>

  <main class="container mx-auto px-4 py-6">
    <!-- Search and Filters -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
      <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1">
          <div class="relative">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
              </svg>
            </div>
            <input type="text" id="search-input" class="input pl-10" placeholder="Search jobs, skills, companies">
          </div>
        </div>
        <div class="flex gap-2">
          <select id="location-filter" class="input">
            <option value="">All Locations</option>
            <option value="remote">Remote</option>
            <option value="new-york">New York</option>
            <option value="san-francisco">San Francisco</option>
            <option value="london">London</option>
            <option value="berlin">Berlin</option>
          </select>
          <select id="job-type-filter" class="input">
            <option value="">All Job Types</option>
            <option value="full-time">Full-time</option>
            <option value="part-time">Part-time</option>
            <option value="contract">Contract</option>
            <option value="internship">Internship</option>
          </select>
        </div>
      </div>
      <div class="mt-4 flex flex-wrap gap-2" id="filter-tags">
        <!-- Filter tags will be added here -->
      </div>
    </div>

    <!-- My Posted Jobs Section (if user has posted jobs) -->
    <?php if (!empty($userPostedJobs)): ?>
      <div class="mb-8">
        <h2 class="text-xl font-bold mb-4">My Posted Jobs</h2>
        <div class="space-y-4">
          <?php foreach ($userPostedJobs as $job): ?>
            <div class="card p-4">
              <div class="flex items-start">
                <img src="<?php echo htmlspecialchars($job['logo']); ?>" alt="<?php echo htmlspecialchars($job['company']); ?>" class="w-12 h-12 rounded mr-4">
                <div class="flex-1">
                  <div class="flex justify-between">
                    <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($job['title']); ?></h3>
                    <div class="text-sm text-gray-500">Posted <?php echo htmlspecialchars($job['posted']); ?></div>
                  </div>
                  <div class="text-gray-600"><?php echo htmlspecialchars($job['company']); ?> · <?php echo htmlspecialchars($job['location']); ?></div>
                  <div class="mt-2 text-gray-700"><?php echo htmlspecialchars($job['description']); ?></div>
                  <div class="mt-3 flex flex-wrap gap-2">
                    <?php foreach ($job['skills'] as $skill): ?>
                      <span class="badge badge-blue"><?php echo htmlspecialchars($skill); ?></span>
                    <?php endforeach; ?>
                  </div>
                  <div class="mt-4 flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                      <span><?php echo htmlspecialchars($job['applicants']); ?> applicants</span>
                    </div>
                    <div class="flex gap-2">
                      <button class="btn btn-outline text-sm py-1">Edit</button>
                      <?php if ($job['is_active']): ?>
                        <button class="btn btn-outline text-sm py-1 text-red-600 border-red-300 hover:bg-red-50">Deactivate</button>
                      <?php else: ?>
                        <button class="btn btn-outline text-sm py-1 text-green-600 border-green-300 hover:bg-green-50">Activate</button>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main Content -->
      <div class="lg:col-span-2">
        <h2 class="text-xl font-bold mb-4">Trending Jobs</h2>

        <!-- Job Listings -->
        <div id="job-listings" class="space-y-4">
          <?php if (empty($allJobs)): ?>
            <div class="text-center py-8">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h3 class="text-lg font-medium text-gray-900">No jobs found</h3>
              <p class="text-gray-500 mt-2">Be the first to post a job!</p>
            </div>
          <?php else: ?>
            <?php foreach ($allJobs as $job): ?>
              <div class="card p-4">
                <div class="flex items-start">
                  <img src="<?php echo htmlspecialchars($job['logo']); ?>" alt="<?php echo htmlspecialchars($job['company']); ?>" class="w-12 h-12 rounded mr-4">
                  <div class="flex-1">
                    <div class="flex justify-between">
                      <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($job['title']); ?></h3>
                      <button class="save-job-btn text-gray-400 hover:text-primary"
                        data-id="<?php echo $job['id']; ?>"
                        data-title="<?php echo htmlspecialchars($job['title']); ?>"
                        data-company="<?php echo htmlspecialchars($job['company']); ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                          fill="<?php echo in_array($job['id'], $savedJobBookmarks) ? 'currentColor' : 'none'; ?>"
                          viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                        </svg>
                      </button>
                    </div>
                    <div class="text-gray-600"><?php echo htmlspecialchars($job['company']); ?> · <?php echo htmlspecialchars($job['location']); ?></div>
                    <div class="mt-2 text-gray-700"><?php echo htmlspecialchars($job['description']); ?></div>
                    <div class="mt-3 flex flex-wrap gap-2">
                      <?php foreach ($job['skills'] as $skill): ?>
                        <span class="badge badge-blue"><?php echo htmlspecialchars($skill); ?></span>
                      <?php endforeach; ?>
                    </div>
                    <div class="mt-4 flex items-center justify-between">
                      <div class="text-sm text-gray-500">
                        <span><?php echo htmlspecialchars($job['posted']); ?></span> · <span><?php echo htmlspecialchars($job['applicants']); ?> applicants</span>
                      </div>
                      <div class="flex gap-2">
                        <button class="easy-apply-btn btn btn-outline text-sm py-1"
                          data-id="<?php echo $job['id']; ?>"
                          data-title="<?php echo htmlspecialchars($job['title']); ?>"
                          data-company="<?php echo htmlspecialchars($job['company']); ?>">Easy Apply</button>
                        <button class="apply-now-btn btn btn-secondary text-sm py-1"
                          data-id="<?php echo $job['id']; ?>"
                          data-title="<?php echo htmlspecialchars($job['title']); ?>"
                          data-company="<?php echo htmlspecialchars($job['company']); ?>">Apply Now</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Loading More -->
        <?php if (count($allJobs) > 5): ?>
          <div class="text-center mt-8">
            <button id="load-more" class="btn btn-outline">Load More Jobs</button>
          </div>
        <?php endif; ?>
      </div>

      <!-- Sidebar -->
      <div class="space-y-6">
        <!-- Job Insights -->
        <div class="bg-white rounded-lg shadow-sm p-4">
          <h3 class="font-bold text-lg mb-3">Job Market Insights</h3>
          <div id="job-insights" class="text-gray-600 text-sm space-y-3">
            <p>Remote work opportunities have increased by 43% in the tech sector over the past year.</p>
            <p>Data science and AI roles command 15-20% higher salaries than traditional software development positions.</p>
            <p>Companies are increasingly valuing soft skills like communication and teamwork alongside technical expertise.</p>
          </div>
        </div>

        <!-- Trending Skills -->
        <div class="bg-white rounded-lg shadow-sm p-4">
          <h3 class="font-bold text-lg mb-3">Trending Skills</h3>
          <div id="trending-skills" class="flex flex-wrap gap-2">
            <span class="badge badge-blue cursor-pointer">React</span>
            <span class="badge badge-green cursor-pointer">Python</span>
            <span class="badge badge-purple cursor-pointer">Machine Learning</span>
            <span class="badge badge-orange cursor-pointer">AWS</span>
            <span class="badge badge-blue cursor-pointer">TypeScript</span>
            <span class="badge badge-green cursor-pointer">Docker</span>
            <span class="badge badge-purple cursor-pointer">Node.js</span>
            <span class="badge badge-orange cursor-pointer">SQL</span>
          </div>
        </div>

        <!-- Suggested Jobs -->
        <div class="bg-white rounded-lg shadow-sm p-4">
          <h3 class="font-bold text-lg mb-3">Suggested For You</h3>
          <div id="suggested-jobs" class="space-y-4">
            <!-- Suggested jobs will be dynamically inserted here -->
            <div class="flex items-center p-2 hover:bg-gray-50 rounded">
              <img src="https://ui-avatars.com/api/?name=TC&background=0D8ABC&color=fff" alt="TechCorp" class="w-10 h-10 rounded mr-3">
              <div class="flex-1">
                <h4 class="font-medium text-sm">Senior Frontend Developer</h4>
                <div class="text-xs text-gray-500">TechCorp · Remote</div>
              </div>
            </div>
            <div class="flex items-center p-2 hover:bg-gray-50 rounded">
              <img src="https://ui-avatars.com/api/?name=AP&background=4C1D95&color=fff" alt="AnalyticsPro" class="w-10 h-10 rounded mr-3">
              <div class="flex-1">
                <h4 class="font-medium text-sm">Data Scientist</h4>
                <div class="text-xs text-gray-500">AnalyticsPro · New York</div>
              </div>
            </div>
            <div class="flex items-center p-2 hover:bg-gray-50 rounded">
              <img src="https://ui-avatars.com/api/?name=CS&background=065F46&color=fff" alt="CloudSystems" class="w-10 h-10 rounded mr-3">
              <div class="flex-1">
                <h4 class="font-medium text-sm">DevOps Engineer</h4>
                <div class="text-xs text-gray-500">CloudSystems · San Francisco</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <!-- Post Job Modal -->
  <div id="post-job-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Post a New Job</h2>
        <button id="close-post-job" class="text-gray-500 hover:text-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form id="post-job-form" class="space-y-4">
        <div>
          <label for="job-title" class="block text-sm font-medium text-gray-700 mb-1">Job Title</label>
          <input type="text" id="job-title" name="job-title" class="input" required>
        </div>

        <div>
          <label for="company-name" class="block text-sm font-medium text-gray-700 mb-1">Company Name</label>
          <input type="text" id="company-name" name="company-name" class="input" required>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="job-location" class="block text-sm font-medium text-gray-700 mb-1">Location</label>
            <select id="job-location" name="job-location" class="input" required>
              <option value="">Select Location</option>
              <option value="remote">Remote</option>
              <option value="new-york">New York</option>
              <option value="san-francisco">San Francisco</option>
              <option value="london">London</option>
              <option value="berlin">Berlin</option>
              <option value="other">Other</option>
            </select>
          </div>

          <div>
            <label for="job-type" class="block text-sm font-medium text-gray-700 mb-1">Job Type</label>
            <select id="job-type" name="job-type" class="input" required>
              <option value="">Select Job Type</option>
              <option value="full-time">Full-time</option>
              <option value="part-time">Part-time</option>
              <option value="contract">Contract</option>
              <option value="internship">Internship</option>
            </select>
          </div>
        </div>

        <div>
          <label for="salary-range" class="block text-sm font-medium text-gray-700 mb-1">Salary Range</label>
          <input type="text" id="salary-range" name="salary-range" class="input" placeholder="e.g., $80,000 - $100,000" required>
        </div>

        <div>
          <label for="job-description" class="block text-sm font-medium text-gray-700 mb-1">Job Description</label>
          <textarea id="job-description" name="job-description" rows="5" class="input" required></textarea>
        </div>

        <div>
          <label for="required-skills" class="block text-sm font-medium text-gray-700 mb-1">Required Skills (comma separated)</label>
          <input type="text" id="required-skills" name="required-skills" class="input" placeholder="e.g., JavaScript, React, Node.js" required>
        </div>

        <div>
          <label for="expires-at" class="block text-sm font-medium text-gray-700 mb-1">Listing Expires</label>
          <input type="date" id="expires-at" name="expires-at" class="input" required>
        </div>

        <div class="pt-4">
          <button type="submit" class="btn btn-primary w-full">Post Job</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Job Insights Modal -->
  <div id="insights-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[80vh] overflow-y-auto">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Job Market Insights</h2>
        <button id="close-insights" class="text-gray-500 hover:text-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>
      <div id="insights-content" class="prose max-w-none">
        <h3>Current Job Market Trends</h3>
        <p>The job market continues to evolve rapidly with technology driving significant changes across industries. Here are some key insights:</p>

        <h4>Remote Work Revolution</h4>
        <p>Remote work opportunities have increased by 43% in the tech sector over the past year. Companies are embracing distributed teams and flexible work arrangements as a permanent strategy rather than a temporary solution.</p>

        <h4>AI and Data Science Demand</h4>
        <p>Roles in artificial intelligence and data science command 15-20% higher salaries than traditional software development positions. The demand for professionals who can build and manage machine learning models continues to outpace supply.</p>

        <h4>Soft Skills Premium</h4>
        <p>While technical expertise remains crucial, employers are increasingly valuing soft skills like communication, teamwork, and adaptability. Job listings mentioning these skills have increased by 35% in the past two years.</p>

        <h4>Emerging Technologies</h4>
        <p>Blockchain, extended reality (XR), and quantum computing are creating new job categories with specialized skill requirements. Early adopters of these technologies can command premium compensation packages.</p>

        <h4>Industry Shifts</h4>
        <p>Healthcare, finance, and education sectors are rapidly digitizing, creating new opportunities for technology professionals who understand these domains. Cross-industry expertise is becoming increasingly valuable.</p>
      </div>
    </div>
  </div>

  <!-- Success Message -->
  <div id="success-message" class="fixed inset-0 flex items-center justify-center z-50 hidden">
    <div class="absolute inset-0 bg-black opacity-50"></div>
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 z-10">
      <div class="flex items-center justify-center text-green-500 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
      </div>
      <h3 class="text-xl font-bold text-center mb-2">Success!</h3>
      <p class="text-gray-600 text-center mb-6" id="success-message-text">Your action was completed successfully.</p>
      <div class="flex justify-center">
        <button id="close-success" class="btn btn-primary">Close</button>
      </div>
    </div>
  </div>

  <script>
    // State management
    const state = {
      jobs: <?php echo json_encode($allJobs); ?>,
      filteredJobs: <?php echo json_encode($allJobs); ?>,
      suggestedJobs: [],
      trendingSkills: [{
          name: 'React',
          count: 1243,
          class: 'badge-blue'
        },
        {
          name: 'Python',
          count: 982,
          class: 'badge-green'
        },
        {
          name: 'Machine Learning',
          count: 876,
          class: 'badge-purple'
        },
        {
          name: 'AWS',
          count: 754,
          class: 'badge-orange'
        },
        {
          name: 'TypeScript',
          count: 621,
          class: 'badge-blue'
        },
        {
          name: 'Docker',
          count: 543,
          class: 'badge-green'
        },
        {
          name: 'Node.js',
          count: 498,
          class: 'badge-purple'
        },
        {
          name: 'SQL',
          count: 432,
          class: 'badge-orange'
        }
      ],
      filters: {
        search: '',
        location: '',
        jobType: ''
      },
      isLoading: false,
      page: 1,
      savedJobs: <?php echo json_encode($savedJobBookmarks); ?>,
      userId: <?php echo $user_id; ?>,
      userName: "<?php echo htmlspecialchars($fullname); ?>"
    };

    // DOM Elements
    const searchInput = document.getElementById('search-input');
    const locationFilter = document.getElementById('location-filter');
    const jobTypeFilter = document.getElementById('job-type-filter');
    const filterTags = document.getElementById('filter-tags');
    const loadMoreButton = document.getElementById('load-more');

    const postJobButton = document.getElementById('post-job-button');
    const postJobModal = document.getElementById('post-job-modal');
    const closePostJobButton = document.getElementById('close-post-job');
    const postJobForm = document.getElementById('post-job-form');

    const insightsButton = document.getElementById('insights-button');
    const insightsModal = document.getElementById('insights-modal');
    const closeInsightsButton = document.getElementById('close-insights');

    const successMessage = document.getElementById('success-message');
    const successMessageText = document.getElementById('success-message-text');
    const closeSuccessButton = document.getElementById('close-success');

    // Event Listeners
    searchInput.addEventListener('input', (e) => {
      state.filters.search = e.target.value.trim().toLowerCase();
      applyFilters();
    });

    locationFilter.addEventListener('change', (e) => {
      state.filters.location = e.target.value;
      updateFilterTags();
      applyFilters();
    });

    jobTypeFilter.addEventListener('change', (e) => {
      state.filters.jobType = e.target.value;
      updateFilterTags();
      applyFilters();
    });

    loadMoreButton?.addEventListener('click', () => {
      state.page++;
      renderJobs();
    });

    postJobButton.addEventListener('click', () => {
      postJobModal.classList.remove('hidden');

      // Set default expiration date to 30 days from now
      const expiresAtInput = document.getElementById('expires-at');
      const thirtyDaysFromNow = new Date();
      thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30);
      expiresAtInput.value = thirtyDaysFromNow.toISOString().split('T')[0];
    });

    closePostJobButton.addEventListener('click', () => {
      postJobModal.classList.add('hidden');
    });

    postJobForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      try {
        // Get form data
        const title = document.getElementById('job-title').value;
        const company = document.getElementById('company-name').value;
        const location = document.getElementById('job-location').value;
        const jobType = document.getElementById('job-type').value;
        const salary = document.getElementById('salary-range').value;
        const description = document.getElementById('job-description').value;
        const skills = document.getElementById('required-skills').value;
        const expiresAt = document.getElementById('expires-at').value;

        // Prepare data for saving
        const data = new FormData();
        data.append('action', 'post_job');
        data.append('title', title);
        data.append('company', company);
        data.append('location', location);
        data.append('jobType', jobType);
        data.append('salary', salary);
        data.append('description', description);
        data.append('skills', skills);
        data.append('expiresAt', expiresAt);

        // Send data to server
        const response = await fetch('Jobsections.php', {
          method: 'POST',
          body: data
        });

        const result = await response.json();

        if (result.success) {
          // Close modal
          postJobModal.classList.add('hidden');

          // Show success message
          successMessageText.textContent = 'Your job has been posted successfully.';
          successMessage.classList.remove('hidden');

          // Reload page to show new job
          setTimeout(() => {
            window.location.reload();
          }, 2000);
        } else {
          throw new Error(result.message || 'Failed to post job');
        }
      } catch (error) {
        console.error('Error posting job:', error);
        successMessageText.textContent = 'There was an error posting your job. Please try again.';
        successMessage.classList.remove('hidden');
      }
    });

    insightsButton.addEventListener('click', () => {
      insightsModal.classList.remove('hidden');
    });

    closeInsightsButton.addEventListener('click', () => {
      insightsModal.classList.add('hidden');
    });

    closeSuccessButton.addEventListener('click', () => {
      successMessage.classList.add('hidden');
    });

    // Add event listeners to save job buttons
    document.querySelectorAll('.save-job-btn').forEach(button => {
      button.addEventListener('click', async (e) => {
        const jobId = parseInt(e.currentTarget.dataset.id);
        const jobTitle = e.currentTarget.dataset.title;
        const company = e.currentTarget.dataset.company;

        try {
          // Prepare data for saving
          const data = new FormData();
          data.append('action', 'save_job');
          data.append('jobId', jobId);
          data.append('jobTitle', jobTitle);
          data.append('company', company);

          // Send data to server
          const response = await fetch('Jobsections.php', {
            method: 'POST',
            body: data
          });

          const result = await response.json();

          if (result.success) {
            // Update state
            if (!state.savedJobs.includes(jobId)) {
              state.savedJobs.push(jobId);
            }

            // Update the button appearance
            e.currentTarget.querySelector('svg').setAttribute('fill', 'currentColor');

            // Show success message
            successMessageText.textContent = 'Job saved to your bookmarks.';
            successMessage.classList.remove('hidden');
          } else {
            throw new Error('Failed to save job');
          }
        } catch (error) {
          console.error('Error saving job:', error);
          successMessageText.textContent = 'There was an error saving this job. Please try again.';
          successMessage.classList.remove('hidden');
        }
      });
    });

    // Add event listeners to apply buttons
    document.querySelectorAll('.easy-apply-btn, .apply-now-btn').forEach(button => {
      button.addEventListener('click', async (e) => {
        const jobId = parseInt(e.currentTarget.dataset.id);
        const jobTitle = e.currentTarget.dataset.title;
        const company = e.currentTarget.dataset.company;

        try {
          // Prepare data for saving
          const data = new FormData();
          data.append('action', 'apply_job');
          data.append('jobId', jobId);
          data.append('jobTitle', jobTitle);
          data.append('company', company);

          // Send data to server
          const response = await fetch('Jobsections.php', {
            method: 'POST',
            body: data
          });

          const result = await response.json();

          if (result.success) {
            // Show success message
            successMessageText.textContent = 'Your application has been submitted successfully.';
            successMessage.classList.remove('hidden');
          } else {
            throw new Error('Failed to submit application');
          }
        } catch (error) {
          console.error('Error applying for job:', error);
          successMessageText.textContent = 'There was an error submitting your application. Please try again.';
          successMessage.classList.remove('hidden');
        }
      });
    });

    // Functions
    function applyFilters() {
      state.filteredJobs = state.jobs.filter(job => {
        // Search filter
        if (state.filters.search && !jobMatchesSearch(job, state.filters.search)) {
          return false;
        }

        // Location filter
        if (state.filters.location && job.location.toLowerCase() !== state.filters.location.replace('-', ' ')) {
          return false;
        }

        // Job type filter
        if (state.filters.jobType && job.job_type !== state.filters.jobType) {
          return false;
        }

        return true;
      });

      // Reset pagination
      state.page = 1;

      // Render filtered jobs
      renderJobs();
    }

    function jobMatchesSearch(job, searchTerm) {
      searchTerm = searchTerm.toLowerCase();
      return (
        job.title.toLowerCase().includes(searchTerm) ||
        job.company.toLowerCase().includes(searchTerm) ||
        job.description.toLowerCase().includes(searchTerm) ||
        (job.skills && job.skills.some(skill => skill.toLowerCase().includes(searchTerm)))
      );
    }

    function updateFilterTags() {
      filterTags.innerHTML = '';

      if (state.filters.location) {
        addFilterTag('Location: ' + state.filters.location.replace('-', ' '), () => {
          locationFilter.value = '';
          state.filters.location = '';
          updateFilterTags();
          applyFilters();
        });
      }

      if (state.filters.jobType) {
        addFilterTag('Type: ' + state.filters.jobType.replace('-', ' '), () => {
          jobTypeFilter.value = '';
          state.filters.jobType = '';
          updateFilterTags();
          applyFilters();
        });
      }
    }

    function addFilterTag(text, removeCallback) {
      const tag = document.createElement('div');
      tag.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800';
      tag.innerHTML = `
        ${text}
        <button type="button" class="ml-1.5 inline-flex items-center justify-center h-4 w-4 rounded-full text-blue-400 hover:text-blue-500 focus:outline-none">
          <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
          </svg>
        </button>
      `;

      tag.querySelector('button').addEventListener('click', removeCallback);
      filterTags.appendChild(tag);
    }

    // Close modals when clicking outside
    window.addEventListener('click', (e) => {
      if (e.target === postJobModal) {
        postJobModal.classList.add('hidden');
      }
      if (e.target === insightsModal) {
        insightsModal.classList.add('hidden');
      }
      if (e.target === successMessage) {
        successMessage.classList.add('hidden');
      }
    });
  </script>
</body>

</html>