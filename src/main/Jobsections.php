<?php
// Start session for user authentication
session_start();

// Include database configuration
require_once "../auth/config.php";

// Include the sample job data
require_once "configJob.php"; // Make sure the path is correct

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
  header("location: ../auth/login.php");
  exit;
}

// Get user information including role
$user_id = $_SESSION["id"];
$fullname = $_SESSION["fullname"];
$email = $_SESSION["email"];
$user_role = $_SESSION["role"] ?? 'user'; // Default to 'user' if role is not set

// Handle clearing assessment results
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['clear_assessment'])) {
    unset($_SESSION['assessment_results']);
    header("Location: Jobsections.php");
    exit;
}

// Get assessment results from session
$assessmentResults = $_SESSION['assessment_results'] ?? null;
$userSkills = $assessmentResults['skills'] ?? []; // Default to empty array
$careerInterest = $assessmentResults['career_interest'] ?? ''; // Get interest too

// Function to check if user has HR or Admin privileges
function canPostJobs($role) {
  return $role === 'hr' || $role === 'admin';
}

// Function to save job application
function saveJobApplication($userId, $jobId, $jobTitle, $company, $resumePath, $coverLetter, $status = 'Applied')
{
  global $conn;

  // Prepare an insert statement
  $sql = "INSERT INTO job_applications (user_id, job_id, job_title, company, status, resume_path, cover_letter, applied_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";

  if ($stmt = $conn->prepare($sql)) {
    // Bind variables to the prepared statement as parameters
    $stmt->bind_param("iisssss", $userId, $jobId, $jobTitle, $company, $status, $resumePath, $coverLetter);

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

// Function to get jobs with skill matching
function getUserPostedJobs($userId = null, $userSkills = []) {
    global $conn;

    $jobs = array();

    // Prepare a select statement
    $sql = "SELECT id, user_id, title, company, logo, location, job_type, salary, description, skills,
            created_at, expires_at, is_active FROM job_listings";

    $params = [];
    $types = '';

    // Build WHERE clause
    $whereClauses = [];
    if ($userId) {
        $whereClauses[] = "user_id = ?";
        $params[] = $userId;
        $types .= 'i';
    } else {
        // Only show active and non-expired jobs in the general list
        $whereClauses[] = "is_active = 1";
        $whereClauses[] = "(expires_at IS NULL OR expires_at >= CURDATE())";
    }

    if (!empty($whereClauses)) {
        $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }


    $sql .= " ORDER BY created_at DESC"; // Initial sort

    if ($stmt = $conn->prepare($sql)) {
        // Bind parameters if any
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        // Execute the statement
        $stmt->execute();

        // Get result
        $result = $stmt->get_result();

        // Fetch results
        while ($row = $result->fetch_assoc()) {
            // Convert skills from JSON to array, handle errors
            $jobSkillsDecoded = json_decode($row['skills'], true);
            $row['skills'] = is_array($jobSkillsDecoded) ? $jobSkillsDecoded : [];

            // Calculate skill match score if user has skills and job has skills
            if (!empty($userSkills) && !empty($row['skills'])) {
                $jobSkillsLower = array_map('strtolower', $row['skills']);
                $userSkillsLower = array_map('strtolower', $userSkills);
                $matchedSkills = array_intersect($jobSkillsLower, $userSkillsLower);
                // Score based on matched skills / total job skills
                $row['match_score'] = count($matchedSkills) / count($row['skills']);
                $row['matched_skills'] = array_values($matchedSkills); // Store matched skills (re-indexed)
            } else {
                $row['match_score'] = 0;
                $row['matched_skills'] = [];
            }

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

        $stmt->close();
    } else {
        // Handle prepare error - log it
        error_log("Error preparing statement for getUserPostedJobs: " . $conn->error);
    }

    // Sort by match score (descending) then by date (descending) if user has skills
    if (!empty($userSkills)) {
        usort($jobs, function($a, $b) {
            if ($b['match_score'] == $a['match_score']) {
                // If scores are equal, sort by creation date (newest first)
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            }
            // Otherwise, sort by match score (highest first)
            return $b['match_score'] <=> $a['match_score']; // Use spaceship operator for float comparison
        });
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
    resume_path VARCHAR(255),
    cover_letter TEXT,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)"; // Added ON DELETE CASCADE

if ($conn->query($sql) !== TRUE) {
  die("Error creating job_applications table: " . $conn->error);
}

// Alter job_applications table if resume_path and cover_letter columns don't exist
$sql = "SHOW COLUMNS FROM `job_applications` LIKE 'resume_path'";
$result = $conn->query($sql);
if ($result && $result->num_rows == 0) {
  $alterSql = "ALTER TABLE `job_applications`
               ADD COLUMN `resume_path` VARCHAR(255) NULL,
               ADD COLUMN `cover_letter` TEXT NULL";
  if (!$conn->query($alterSql)) {
    error_log("Error adding columns to job_applications table: " . $conn->error);
  }
}

// Create job bookmarks table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS job_bookmarks (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    job_id INT(11) NOT NULL,
    job_title VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY user_job_bookmark (user_id, job_id)
)"; // Added ON DELETE CASCADE and UNIQUE constraint

if ($conn->query($sql) !== TRUE) {
  // Ignore "duplicate key" error if table already exists with the constraint
  if ($conn->errno != 1061) {
      die("Error creating/altering job_bookmarks table: " . $conn->error);
  }
}


// Create job listings table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS job_listings (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    company VARCHAR(255) NOT NULL,
    logo VARCHAR(255),
    location VARCHAR(100) NOT NULL,
    job_type VARCHAR(50) NOT NULL,
    salary VARCHAR(100),
    description TEXT NOT NULL,
    skills TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at DATE,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)"; // Added ON DELETE CASCADE, made logo/salary/skills nullable

if ($conn->query($sql) !== TRUE) {
  die("Error creating job_listings table: " . $conn->error);
}

// Handle AJAX requests
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {
  header("Content-Type: application/json");

  if ($_POST["action"] == "apply_job") {
    header("Content-Type: application/json");

    try {
      // Validate inputs
      $jobId = isset($_POST["job_id"]) ? (int)$_POST["job_id"] : 0;
      $jobTitle = isset($_POST["job_title"]) ? trim($_POST["job_title"]) : '';
      $company = isset($_POST["company"]) ? trim($_POST["company"]) : '';

      if (!$jobId || empty($jobTitle) || empty($company)) {
        throw new Exception("Missing job details");
      }

      // File upload handling
      $resumePath = '';
      if (isset($_FILES['resume']) && $_FILES['resume']['error'] == UPLOAD_ERR_OK) {
        $allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        $fileInfo = finfo_open(FILEINFO_MIME_TYPE);
        $detectedType = finfo_file($fileInfo, $_FILES['resume']['tmp_name']);
        finfo_close($fileInfo);

        $maxSize = 5 * 1024 * 1024; // 5MB

        // Check file type
        if (!in_array($detectedType, $allowedTypes)) {
          throw new Exception('Only PDF and Word documents are allowed');
        }

        // Check file size
        if ($_FILES['resume']['size'] > $maxSize) {
          throw new Exception('File size exceeds 5MB limit');
        }

        // Create upload directory if it doesn't exist
        $uploadDir = __DIR__ . '/../../uploads/resumes/';
        if (!is_dir($uploadDir)) {
          if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
          }
        }

        // Generate a safe filename
        $extension = pathinfo($_FILES['resume']['name'], PATHINFO_EXTENSION);
        $safeName = 'resume_' . $user_id . '_' . time() . '.' . $extension;
        $resumePath = $uploadDir . $safeName;

        // Move the file
        if (!move_uploaded_file($_FILES['resume']['tmp_name'], $resumePath)) {
          throw new Exception('Failed to save resume file');
        }

        // Store relative path in database
        $dbResumePath = 'uploads/resumes/' . $safeName;
      } else {
        if ($_FILES['resume']['error'] != UPLOAD_ERR_NO_FILE) {
          // Handle different upload errors
          $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
          ];
          $errorMessage = isset($uploadErrors[$_FILES['resume']['error']]) ?
                          $uploadErrors[$_FILES['resume']['error']] :
                          'Unknown upload error';
          throw new Exception($errorMessage);
        }
        throw new Exception('Resume file is required');
      }

      // Get cover letter if provided
      $coverLetter = isset($_POST['cover_letter']) ? trim($_POST['cover_letter']) : '';

      // Save application
      $success = saveJobApplication(
        $user_id,
        $jobId,
        $jobTitle,
        $company,
        $dbResumePath,
        $coverLetter
      );

      if (!$success) {
        throw new Exception("Database error: " . $conn->error);
      }

      echo json_encode([
        'success' => true,
        'message' => 'Application submitted successfully!'
      ]);

    } catch (Exception $e) {
      http_response_code(400);
      error_log("Application error: " . $e->getMessage());
      echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
      ]);
    }
    exit;
  } elseif ($_POST["action"] == "save_job") {
    // Get data from POST request
    $jobId = $_POST["jobId"] ?? null;
    $jobTitle = $_POST["jobTitle"] ?? '';
    $company = $_POST["company"] ?? '';

     if (!$jobId || !$jobTitle || !$company) {
        echo json_encode(["success" => false, "message" => "Missing job details."]);
        exit;
    }

    // Save job bookmark
    $success = saveJobBookmark($user_id, $jobId, $jobTitle, $company);

    // Return response
    echo json_encode(["success" => $success, "message" => $success ? "Job saved." : "Failed to save job (maybe already saved?)."]);
    exit;
  } elseif ($_POST["action"] == "get_bookmarks") {
    // Get saved job bookmarks
    $bookmarks = getSavedJobBookmarks($user_id);

    // Return response
    echo json_encode(["bookmarks" => $bookmarks]);
    exit;
  } elseif ($_POST["action"] == "post_job") {
    // Check if the user has permission to post jobs
    if (!canPostJobs($user_role)) {
      echo json_encode(["success" => false, "message" => "Permission denied. Only HR or Admin can post jobs."]);
      exit;
    }

    // Get data from POST request and validate
    $title = trim($_POST["title"] ?? '');
    $company = trim($_POST["company"] ?? '');
    $location = trim($_POST["location"] ?? '');
    $jobType = trim($_POST["jobType"] ?? '');
    $salary = trim($_POST["salary"] ?? ''); // Salary is optional
    $description = trim($_POST["description"] ?? '');
    $skillsInput = trim($_POST["skills"] ?? '');
    $expiresAt = trim($_POST["expiresAt"] ?? '');

    // Basic validation
    if (empty($title) || empty($company) || empty($location) || empty($jobType) || empty($description) || empty($expiresAt)) {
         echo json_encode(["success" => false, "message" => "Please fill in all required fields."]);
         exit;
    }

    // Process skills
    $skillsArray = !empty($skillsInput) ? array_map('trim', explode(',', $skillsInput)) : [];
    $skillsJson = json_encode($skillsArray);


    // Generate logo from company name
    $companyInitials = '';
    $words = explode(' ', $company);
    if (count($words) >= 2) {
        $companyInitials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
    } elseif (!empty($words[0])) {
        $companyInitials = strtoupper(substr($words[0], 0, 2));
    } else {
        $companyInitials = '??';
    }

    // Generate random background color for logo
    $colors = ['0D8ABC', '4C1D95', '065F46', '9D174D', '1E3A8A', '7C2D12', '5B21B6', '1E40AF'];
    $randomColor = $colors[array_rand($colors)];

    $logo = "https://ui-avatars.com/api/?name=" . urlencode($companyInitials) . "&background=" . $randomColor . "&color=fff&size=128";

    // Prepare an insert statement
    $sql = "INSERT INTO job_listings (user_id, title, company, logo, location, job_type, salary, description, skills, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
      // Bind variables to the prepared statement as parameters
      $stmt->bind_param("isssssssss", $user_id, $title, $company, $logo, $location, $jobType, $salary, $description, $skillsJson, $expiresAt);

      // Execute the statement
      $success = $stmt->execute();

      if ($success) {
          $jobId = $conn->insert_id;
          echo json_encode(["success" => true, "jobId" => $jobId, "message" => "Job posted successfully."]);
      } else {
          error_log("Error executing post_job statement: " . $stmt->error);
          echo json_encode(["success" => false, "message" => "Database error posting job."]);
      }

      // Close statement
      $stmt->close();
      exit;
    } else {
        error_log("Error preparing post_job statement: " . $conn->error);
        echo json_encode(["success" => false, "message" => "Error preparing job listing."]);
        exit;
    }
  }
}

// Get saved job bookmarks for display
$savedJobBookmarks = getSavedJobBookmarks($user_id);

// Get jobs with skill matching
$userPostedJobs = getUserPostedJobs($user_id, $userSkills); // Pass skills for user's own jobs if needed for display
$allJobs = getUserPostedJobs(null, $userSkills); // Pass skills for sorting/matching all jobs

// Get job application history
$applicationHistory = [];
$sql = "SELECT id, job_title, company, status, applied_at FROM job_applications
        WHERE user_id = ? ORDER BY applied_at DESC LIMIT 10";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $applicationHistory[] = $row;
    }

    $stmt->close();
}

// Get job bookmark history
$bookmarkHistory = [];
$sql = "SELECT id, job_title, company, saved_at FROM job_bookmarks
        WHERE user_id = ? ORDER BY saved_at DESC LIMIT 10";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $bookmarkHistory[] = $row;
    }

    $stmt->close();
}
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
      .loading {
        display: inline-flex;
        align-items: center;
      }
      .file-input::file-selector-button {
        @apply btn btn-outline;
        margin-right: 1rem;
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
                d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2z" />
            </svg>
            Jobs
          </a>
        </div>
      </div>
      <div class="flex items-center space-x-4">
        <?php if (canPostJobs($user_role)): // Conditionally show Post Job button ?>
        <button id="post-job-button" class="btn btn-secondary hidden md:flex items-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
          </svg>
          Post a Job
        </button>
        <?php endif; ?>
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
    <!-- Assessment-based recommendations section -->
    <?php if (!empty($userSkills)): ?>
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6 border border-blue-200">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
        <div>
          <h2 class="text-lg font-bold text-primary flex items-center gap-2">
            <i class="fas fa-star text-yellow-500"></i>
            Personalized Job Recommendations
          </h2>
          <p class="text-sm text-gray-600 mt-1 mb-2">
            Based on your assessed skills:
          </p>
          <div class="flex flex-wrap gap-2">
            <?php foreach ($userSkills as $skill): ?>
              <span class="badge badge-blue">
                <?php echo htmlspecialchars($skill); ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>
        <form method="post" action="Jobsections.php" class="mt-2 sm:mt-0 self-start sm:self-center">
          <button type="submit" name="clear_assessment" class="text-sm text-blue-600 hover:text-blue-800 flex items-center gap-1 px-2 py-1 rounded hover:bg-blue-50">
            <i class="fas fa-times text-xs"></i> Clear recommendations
          </button>
        </form>
      </div>
    </div>
    <?php endif; ?>

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
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold">My Posted Jobs</h2>
          <button id="toggle-posted-jobs" class="text-sm text-primary hover:text-blue-700 flex items-center">
            <span id="toggle-posted-text">Hide</span>
            <i class="fas fa-chevron-up text-xs ml-1" id="toggle-posted-icon"></i>
          </button>
        </div>
        <div id="posted-jobs-container" class="space-y-4">
          <?php foreach ($userPostedJobs as $job): ?>
            <div class="card p-4 hover:shadow-md transition-all duration-200">
              <div class="flex items-start">
                <img src="<?php echo htmlspecialchars($job['logo'] ?? ''); ?>" alt="<?php echo htmlspecialchars($job['company']); ?>"
                     class="w-12 h-12 rounded-lg mr-4 object-contain border border-gray-100 shadow-sm">
                <div class="flex-1">
                  <div class="flex justify-between">
                    <h3 class="font-semibold text-lg text-gray-900 hover:text-blue-600"><?php echo htmlspecialchars($job['title']); ?></h3>
                    <div class="text-sm text-gray-500">Posted <?php echo htmlspecialchars($job['posted']); ?></div>
                  </div>
                  <div class="text-gray-600 text-sm flex items-center mt-1">
                    <span><?php echo htmlspecialchars($job['company']); ?></span>
                    <span class="mx-2">•</span>
                    <span class="flex items-center">
                      <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                      </svg>
                      <?php echo htmlspecialchars($job['location']); ?>
                    </span>
                  </div>
                  <div class="mt-3 text-gray-700 text-sm leading-relaxed">
                    <?php echo nl2br(htmlspecialchars(substr($job['description'], 0, 150))) . (strlen($job['description']) > 150 ? '...' : ''); ?>
                  </div>

                  <!-- Skills Badges -->
                  <div class="mt-3 flex flex-wrap gap-2">
                    <?php if (!empty($job['skills'])): ?>
                      <?php foreach ($job['skills'] as $skill): ?>
                        <span class="badge badge-blue"><?php echo htmlspecialchars($skill); ?></span>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </div>
                  <!-- End Skills Badges -->

                  <div class="mt-4 flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                      <span><?php echo htmlspecialchars($job['applicants']); ?> applicants</span>
                      <?php if ($job['expires_at']): ?>
                        <span class="ml-2">· Expires <?php echo date("M j, Y", strtotime($job['expires_at'])); ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="flex gap-2">
                      <button class="btn btn-outline text-sm py-1 px-3 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">Edit</button>
                      <?php if ($job['is_active']): ?>
                        <button class="btn btn-outline text-sm py-1 px-3 text-red-600 border-red-300 hover:bg-red-50">Deactivate</button>
                      <?php else: ?>
                        <button class="btn btn-outline text-sm py-1 px-3 text-green-600 border-green-300 hover:bg-green-50">Activate</button>
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
        <h2 class="text-xl font-bold mb-4">
            <?php echo !empty($userSkills) ? 'All Job Listings' : 'Trending Jobs'; ?>
        </h2>

        <!-- Job Listings -->
        <div id="job-listings" class="space-y-4">
          <?php if (empty($allJobs)): ?>
            <div class="text-center py-8 bg-white rounded-lg border border-dashed border-gray-300">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h3 class="text-lg font-medium text-gray-900">No jobs found</h3>
              <p class="text-gray-500 mt-2">Try adjusting your search filters or check back later.</p>
              <?php if (canPostJobs($user_role)): ?>
                 <button id="post-job-link" class="mt-4 text-primary hover:underline">Be the first to post a job!</button>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <?php foreach ($allJobs as $job): ?>
              <div class="card p-4 hover:shadow-md transition-all duration-200">
                <div class="flex items-start">
                  <img src="<?php echo htmlspecialchars($job['logo'] ?? ''); ?>" alt="<?php echo htmlspecialchars($job['company']); ?>"
                       class="w-12 h-12 rounded-lg mr-4 object-contain border border-gray-100 shadow-sm">
                  <div class="flex-1">
                    <div class="flex justify-between">
                      <h3 class="font-semibold text-lg text-gray-900 hover:text-blue-600"><?php echo htmlspecialchars($job['title']); ?></h3>
                      <button class="save-job-btn text-gray-400 hover:text-blue-600 transition-colors"
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
                    <div class="text-gray-600 text-sm flex items-center mt-1">
                      <span><?php echo htmlspecialchars($job['company']); ?></span>
                      <span class="mx-2">•</span>
                      <span class="flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <?php echo htmlspecialchars($job['location']); ?>
                      </span>
                    </div>
                    <div class="mt-3 text-gray-700 text-sm leading-relaxed">
                      <?php echo nl2br(htmlspecialchars(substr($job['description'], 0, 150))) . (strlen($job['description']) > 150 ? '...' : ''); ?>
                    </div>

                    <!-- Skills Badges -->
                    <div class="mt-3 flex flex-wrap gap-2">
                      <?php if (!empty($job['skills'])): ?>
                        <?php
                          $userSkillsLower = array_map('strtolower', $userSkills); // Lowercase user skills once
                        ?>
                        <?php foreach ($job['skills'] as $skill): ?>
                          <?php
                            $skillLower = strtolower($skill);
                            $isMatched = !empty($userSkills) && in_array($skillLower, $userSkillsLower);
                          ?>
                          <span class="badge <?php echo $isMatched ? 'badge-green' : 'badge-blue'; ?> inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                            <?php echo htmlspecialchars($skill); ?>
                            <?php if ($isMatched): ?>
                              <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                              </svg>
                            <?php endif; ?>
                          </span>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </div>
                    <!-- End Skills Badges -->

                    <!-- Match Score Display -->
                    <?php if (!empty($userSkills) && isset($job['match_score']) && $job['match_score'] > 0): ?>
                    <div class="mt-3 pt-2 border-t border-gray-100">
                      <div class="flex justify-between items-center text-xs text-gray-500 mb-1">
                        <span class="font-medium text-blue-700">Match: <?php echo round($job['match_score'] * 100) ?>%</span>
                        <?php if (!empty($job['matched_skills'])): ?>
                          <span class="truncate" title="Matching skills: <?php echo htmlspecialchars(implode(', ', $job['matched_skills'])); ?>">
                            Matching: <?php echo htmlspecialchars(implode(', ', array_slice($job['matched_skills'], 0, 3))) . (count($job['matched_skills']) > 3 ? '...' : ''); ?>
                          </span>
                        <?php endif; ?>
                      </div>
                      <div class="w-full bg-gray-200 rounded-full h-1.5">
                        <div class="bg-blue-600 h-1.5 rounded-full" style="width: <?php echo round($job['match_score'] * 100) ?>%"></div>
                      </div>
                    </div>
                    <?php endif; ?>
                    <!-- End Match Score Display -->

                    <div class="mt-4 flex items-center justify-between">
                      <div class="text-sm text-gray-500 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span><?php echo htmlspecialchars($job['posted']); ?></span>
                        <span class="mx-2">•</span>
                        <span><?php echo htmlspecialchars($job['applicants']); ?> applicants</span>
                      </div>
                      <div class="flex gap-2">
                        <button class="easy-apply-btn btn btn-outline text-sm py-1 px-3 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
                          data-id="<?php echo $job['id']; ?>"
                          data-title="<?php echo htmlspecialchars($job['title']); ?>"
                          data-company="<?php echo htmlspecialchars($job['company']); ?>">
                          Easy Apply
                        </button>
                        <button class="apply-now-btn btn btn-secondary text-sm py-1 px-3 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors"
                          data-id="<?php echo $job['id']; ?>"
                          data-title="<?php echo htmlspecialchars($job['title']); ?>"
                          data-company="<?php echo htmlspecialchars($job['company']); ?>">
                          Apply Now
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- Loading More -->
        <?php if (count($allJobs) > 5): // Adjust this number based on initial load count ?>
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
          <label for="job-title" class="block text-sm font-medium text-gray-700 mb-1">Job Title <span class="text-red-500">*</span></label>
          <input type="text" id="job-title" name="title" class="input" required>
        </div>

        <div>
          <label for="company-name" class="block text-sm font-medium text-gray-700 mb-1">Company Name <span class="text-red-500">*</span></label>
          <input type="text" id="company-name" name="company" class="input" required>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label for="job-location" class="block text-sm font-medium text-gray-700 mb-1">Location <span class="text-red-500">*</span></label>
            <select id="job-location" name="location" class="input" required>
              <option value="">Select Location</option>
              <option value="remote">Remote</option>
              <option value="new-york">New York</option>
              <option value="san-francisco">San Francisco</option>
              <option value="london">London</option>
              <option value="berlin">Berlin</option>
              <option value="other">Other (Specify in description)</option>
            </select>
          </div>

          <div>
            <label for="job-type" class="block text-sm font-medium text-gray-700 mb-1">Job Type <span class="text-red-500">*</span></label>
            <select id="job-type" name="jobType" class="input" required>
              <option value="">Select Job Type</option>
              <option value="full-time">Full-time</option>
              <option value="part-time">Part-time</option>
              <option value="contract">Contract</option>
              <option value="internship">Internship</option>
            </select>
          </div>
        </div>

        <div>
          <label for="salary-range" class="block text-sm font-medium text-gray-700 mb-1">Salary Range (Optional)</label>
          <input type="text" id="salary-range" name="salary" class="input" placeholder="e.g., $80,000 - $100,000">
        </div>

        <div>
          <label for="job-description" class="block text-sm font-medium text-gray-700 mb-1">Job Description <span class="text-red-500">*</span></label>
          <textarea id="job-description" name="description" rows="5" class="input" required></textarea>
        </div>

        <div>
          <label for="required-skills" class="block text-sm font-medium text-gray-700 mb-1">Required Skills (comma separated)</label>
          <input type="text" id="required-skills" name="skills" class="input" placeholder="e.g., JavaScript, React, Node.js">
        </div>

        <div>
          <label for="expires-at" class="block text-sm font-medium text-gray-700 mb-1">Listing Expires <span class="text-red-500">*</span></label>
          <input type="date" id="expires-at" name="expiresAt" class="input" required>
        </div>

        <div class="pt-4">
          <button type="submit" id="submit-post-job" class="btn btn-primary w-full">Post Job</button>
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

  <!-- Success/Error Message Modal -->
  <div id="message-modal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
    <div class="absolute inset-0 bg-black opacity-50"></div>
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 z-10">
      <div class="flex items-center justify-center mb-4">
        <svg id="message-icon" xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <!-- Icon will be set by JS -->
        </svg>
      </div>
      <h3 id="message-title" class="text-xl font-bold text-center mb-2"></h3>
      <p class="text-gray-600 text-center mb-6" id="message-text"></p>
      <div class="flex justify-center">
        <button id="close-message" class="btn btn-primary">Close</button>
      </div>
    </div>
  </div>

  <!-- Application History Section -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-primary flex items-center gap-2">
          <i class="fas fa-history text-primary"></i>
          Application History
        </h2>
        <button id="toggle-application-history" class="text-sm text-primary hover:text-blue-700 flex items-center">
          <span id="toggle-app-text">Show</span>
          <i class="fas fa-chevron-down text-xs ml-1" id="toggle-app-icon"></i>
        </button>
      </div>

      <div id="application-history-container" class="space-y-3 hidden">
        <?php if (empty($applicationHistory)): ?>
          <p class="text-gray-500 text-sm italic">No application history found. Apply to jobs to see them here.</p>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied On</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($applicationHistory as $application): ?>
                  <tr>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($application['job_title']); ?></td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($application['company']); ?></td>
                    <td class="px-4 py-3 whitespace-nowrap">
                      <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                        <?php echo htmlspecialchars($application['status']); ?>
                      </span>
                    </td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y', strtotime($application['applied_at'])); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Saved Jobs History Section -->
    <div class="bg-white rounded-lg shadow-sm p-4 mb-6">
      <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-bold text-primary flex items-center gap-2">
          <i class="fas fa-bookmark text-primary"></i>
          Saved Jobs
        </h2>
        <button id="toggle-bookmark-history" class="text-sm text-primary hover:text-blue-700 flex items-center">
          <span id="toggle-bookmark-text">Show</span>
          <i class="fas fa-chevron-down text-xs ml-1" id="toggle-bookmark-icon"></i>
        </button>
      </div>

      <div id="bookmark-history-container" class="space-y-3 hidden">
        <?php if (empty($bookmarkHistory)): ?>
          <p class="text-gray-500 text-sm italic">No saved jobs found. Save jobs to see them here.</p>
        <?php else: ?>
          <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
              <thead class="bg-gray-50">
                <tr>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Job Title</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Company</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Saved On</th>
                  <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($bookmarkHistory as $bookmark): ?>
                  <tr>
                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($bookmark['job_title']); ?></td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($bookmark['company']); ?></td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500"><?php echo date('M d, Y', strtotime($bookmark['saved_at'])); ?></td>
                    <td class="px-4 py-3 whitespace-nowrap text-sm">
                      <button class="text-primary hover:text-primary-dark">
                        <i class="fas fa-search mr-1"></i> View
                      </button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

  <!-- Application Form Modal -->
  <div id="application-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Apply for <span id="job-title-header"></span></h2>
        <button id="close-application" class="text-gray-500 hover:text-gray-700">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <form id="application-form" enctype="multipart/form-data">
        <input type="hidden" id="apply-job-id" name="job_id">
        <input type="hidden" id="apply-job-title" name="job_title">
        <input type="hidden" id="apply-company" name="company">

        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Resume (PDF/DOCX) <span class="text-red-500">*</span></label>
            <div class="mt-1 flex items-center">
              <input type="file" name="resume" id="resume" accept=".pdf,.doc,.docx"
                     class="input" required>
            </div>
          </div>

          <div>
            <label for="cover-letter" class="block text-sm font-medium text-gray-700 mb-1">Cover Letter</label>
            <textarea id="cover-letter" name="cover_letter" rows="4"
                      class="input" placeholder="Explain why you're a good fit..."></textarea>
          </div>

          <div class="mt-6">
            <button type="submit" class="btn btn-primary w-full">
              <span class="submit-text">Submit Application</span>
              <span class="loading hidden">
                <i class="fas fa-spinner fa-spin mr-1"></i> Processing...
              </span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <script>
    // State management
    const state = {
      jobs: <?php echo json_encode($allJobs); ?>,
      filteredJobs: <?php echo json_encode($allJobs); ?>,
      suggestedJobs: [], // Placeholder for suggested jobs logic
      trendingSkills: [{ name: 'React', count: 1243, class: 'badge-blue' }, { name: 'Python', count: 982, class: 'badge-green' }, { name: 'Machine Learning', count: 876, class: 'badge-purple' }, { name: 'AWS', count: 754, class: 'badge-orange' }, { name: 'TypeScript', count: 621, class: 'badge-blue' }, { name: 'Docker', count: 543, class: 'badge-green' }, { name: 'Node.js', count: 498, class: 'badge-purple' }, { name: 'SQL', count: 432, class: 'badge-orange' }],
      filters: { search: '', location: '', jobType: '' },
      isLoading: false,
      page: 1, // For potential pagination
      itemsPerPage: 10, // Number of items per page
      savedJobs: <?php echo json_encode($savedJobBookmarks); ?>,
      userRole: "<?php echo $user_role; ?>",
      userId: <?php echo $user_id; ?>,
      userName: "<?php echo htmlspecialchars($fullname); ?>"
    };

    // DOM Elements
    const searchInput = document.getElementById('search-input');
    const locationFilter = document.getElementById('location-filter');
    const jobTypeFilter = document.getElementById('job-type-filter');
    const filterTags = document.getElementById('filter-tags');
    const loadMoreButton = document.getElementById('load-more');
    const jobListingsContainer = document.getElementById('job-listings');

    const postJobButton = document.getElementById('post-job-button');
    const postJobModal = document.getElementById('post-job-modal');
    const closePostJobButton = document.getElementById('close-post-job');
    const postJobForm = document.getElementById('post-job-form');
    const submitPostJobButton = document.getElementById('submit-post-job');
    const postJobLink = document.getElementById('post-job-link'); // Link in empty state

    const insightsButton = document.getElementById('insights-button');
    const insightsModal = document.getElementById('insights-modal');
    const closeInsightsButton = document.getElementById('close-insights');

    const messageModal = document.getElementById('message-modal');
    const messageIcon = document.getElementById('message-icon');
    const messageTitle = document.getElementById('message-title');
    const messageText = document.getElementById('message-text');
    const closeMessageButton = document.getElementById('close-message');

    const applicationModal = document.getElementById('application-modal');
    const applicationForm = document.getElementById('application-form');
    const closeApplicationButton = document.getElementById('close-application');
    let currentJob = null;

    // --- Utility Functions ---
    function showMessage(title, text, isError = false) {
        messageTitle.textContent = title;
        messageText.textContent = text;
        messageIcon.classList.remove('text-green-500', 'text-red-500');

        if (isError) {
            messageIcon.classList.add('text-red-500');
            messageIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />`;
        } else {
            messageIcon.classList.add('text-green-500');
            messageIcon.innerHTML = `<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />`;
        }
        messageModal.classList.remove('hidden');
    }

    // --- Event Listeners ---
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
      renderJobs(true); // Append jobs
    });

    // Open Post Job Modal
    function openPostJobModal() {
        postJobModal.classList.remove('hidden');
        postJobForm.reset(); // Clear form on open

        // Set default expiration date to 30 days from now
        const expiresAtInput = document.getElementById('expires-at');
        const thirtyDaysFromNow = new Date();
        thirtyDaysFromNow.setDate(thirtyDaysFromNow.getDate() + 30);
        expiresAtInput.value = thirtyDaysFromNow.toISOString().split('T')[0];
        expiresAtInput.min = new Date().toISOString().split('T')[0]; // Prevent past dates
    }

    if (postJobButton) {
        postJobButton.addEventListener('click', openPostJobModal);
    }
    if (postJobLink) { // Listener for the link in empty state
        postJobLink.addEventListener('click', (e) => {
            e.preventDefault();
            openPostJobModal();
        });
    }


    closePostJobButton.addEventListener('click', () => {
      postJobModal.classList.add('hidden');
    });

    postJobForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      submitPostJobButton.disabled = true;
      submitPostJobButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Posting...';

      try {
        const formData = new FormData(postJobForm);
        formData.append('action', 'post_job');

        // Send data to server
        const response = await fetch('Jobsections.php', {
          method: 'POST',
          body: formData // Use FormData directly
        });

        const result = await response.json();

        if (result.success) {
          postJobModal.classList.add('hidden');
          showMessage('Success!', result.message || 'Your job has been posted successfully.');
          // Optionally reload or add job dynamically
          setTimeout(() => { window.location.reload(); }, 2000);
        } else {
          throw new Error(result.message || 'Failed to post job');
        }
      } catch (error) {
        console.error('Error posting job:', error);
        showMessage('Error!', error.message || 'There was an error posting your job.', true);
      } finally {
          submitPostJobButton.disabled = false;
          submitPostJobButton.innerHTML = 'Post Job';
      }
    });

    insightsButton.addEventListener('click', () => {
      insightsModal.classList.remove('hidden');
    });

    closeInsightsButton.addEventListener('click', () => {
      insightsModal.classList.add('hidden');
    });

    closeMessageButton.addEventListener('click', () => {
      messageModal.classList.add('hidden');
    });

    // Application Form Handling
    function openApplicationForm(jobId, jobTitle, company) {
      currentJob = { id: jobId, title: jobTitle, company: company };
      document.getElementById('job-title-header').textContent = jobTitle;
      document.getElementById('apply-job-id').value = jobId;
      document.getElementById('apply-job-title').value = jobTitle;
      document.getElementById('apply-company').value = company;
      applicationModal.classList.remove('hidden');
    }

    closeApplicationButton.addEventListener('click', () => {
      applicationModal.classList.add('hidden');
      applicationForm.reset(); // Clear form
    });

    window.addEventListener('click', (e) => {
      if (e.target === applicationModal) {
        applicationModal.classList.add('hidden');
        applicationForm.reset(); // Clear form
      }
    });

    applicationForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new FormData(applicationForm);
      const submitBtn = applicationForm.querySelector('button[type="submit"]');
      const submitText = submitBtn.querySelector('.submit-text');
      const loading = submitBtn.querySelector('.loading');

      const resumeInput = document.getElementById('resume');
      if (!resumeInput.files || resumeInput.files.length === 0) {
        showMessage('Error!', 'Please select a resume file', true);
        return;
      }

      submitText.classList.add('hidden');
      loading.classList.remove('hidden');
      submitBtn.disabled = true;

      try {
        formData.append('action', 'apply_job');

        const response = await fetch('Jobsections.php', {
          method: 'POST',
          body: formData
        });

        const result = await response.json();

        if (result.success) {
          showMessage('Success!', result.message || 'Application submitted successfully!');
          applicationModal.classList.add('hidden');
          applicationForm.reset();

          document.querySelectorAll(`.easy-apply-btn[data-id="${currentJob.id}"], .apply-now-btn[data-id="${currentJob.id}"]`).forEach(btn => {
            btn.disabled = true;
            btn.innerHTML = 'Applied';
            btn.classList.add('opacity-70', 'cursor-not-allowed');
          });
        } else {
          throw new Error(result.message || 'Failed to submit application');
        }
      } catch (error) {
        console.error('Application error:', error);
        showMessage('Error!', error.message || 'An error occurred while submitting your application', true);
      } finally {
        submitText.classList.remove('hidden');
        loading.classList.add('hidden');
        submitBtn.disabled = false;
      }
    });

    // Add event listeners to dynamically added buttons (using event delegation)
    jobListingsContainer.addEventListener('click', async (e) => {
        const saveButton = e.target.closest('.save-job-btn');
        const applyButton = e.target.closest('.easy-apply-btn, .apply-now-btn');
        const clearFiltersEmptyButton = e.target.closest('#clear-filters-empty'); // Check for the clear button

        if (saveButton) {
            const jobId = parseInt(saveButton.dataset.id);
            const jobTitle = saveButton.dataset.title;
            const company = saveButton.dataset.company;
            const svgIcon = saveButton.querySelector('svg');

            // Prevent double clicks
            if (saveButton.disabled) return;
            saveButton.disabled = true;
            svgIcon.classList.add('animate-pulse');

            try {
                const data = new FormData();
                data.append('action', 'save_job');
                data.append('jobId', jobId);
                data.append('jobTitle', jobTitle);
                data.append('company', company);

                const response = await fetch('Jobsections.php', { method: 'POST', body: data });
                const result = await response.json();

                if (result.success) {
                    if (!state.savedJobs.includes(jobId)) { state.savedJobs.push(jobId); }
                    svgIcon.setAttribute('fill', 'currentColor');
                    showMessage('Success!', result.message || 'Job saved to your bookmarks.');
                } else {
                    // Maybe it was already saved, still show success visually
                    if (result.message && result.message.includes('already saved')) {
                         svgIcon.setAttribute('fill', 'currentColor');
                         showMessage('Info', 'This job is already in your bookmarks.');
                    } else {
                        throw new Error(result.message || 'Failed to save job');
                    }
                }
            } catch (error) {
                console.error('Error saving job:', error);
                showMessage('Error!', error.message || 'Could not save job.', true);
            } finally {
                saveButton.disabled = false;
                svgIcon.classList.remove('animate-pulse');
            }
        }

        if (applyButton) {
            const jobId = parseInt(applyButton.dataset.id);
            const jobTitle = applyButton.dataset.title;
            const company = applyButton.dataset.company;

            // Prevent double clicks
            if (applyButton.disabled) return;

            openApplicationForm(jobId, jobTitle, company);

            e.preventDefault();
            e.stopPropagation();
            return false;
        }

        // NEW: Handle click on the clear filters button within the container
        if (clearFiltersEmptyButton) {
            clearAllFilters();
        }
    });


    // --- Rendering Functions ---
    function applyFilters() {
      // Get and normalize search term(s)
      const searchTerms = state.filters.search ?
        state.filters.search.toLowerCase().split(/\s+/).filter(term => term.length > 0) :
        [];

      state.filteredJobs = state.jobs.filter(job => {
        // Skip search filtering if no search terms
        if (searchTerms.length === 0) return true;

        // Prepare job data for searching - safely convert all searchable fields to lowercase strings
        const title = (job.title || '').toLowerCase();
        const company = (job.company || '').toLowerCase();
        const description = (job.description || '').toLowerCase();
        const location = (job.location || '').toLowerCase();

        // Prepare skills array (with error handling)
        const skills = Array.isArray(job.skills) ?
          job.skills.map(skill => (skill || '').toLowerCase()) :
          [];

        // Check if ALL search terms match at least one field
        return searchTerms.every(term => {
          return title.includes(term) ||
                 company.includes(term) ||
                 description.includes(term) ||
                 location.includes(term) ||
                 skills.some(skill => skill.includes(term));
        });
      });

      // Handle other filters (unchanged)
      if (state.filters.location && state.filters.location !== 'remote' &&
          job.location.toLowerCase() !== state.filters.location.replace('-', ' ')) {
        return false;
      }

      if (state.filters.location === 'remote' && job.location.toLowerCase() !== 'remote') {
        return false;
      }

      if (state.filters.jobType && job.job_type.toLowerCase() !== state.filters.jobType.toLowerCase()) {
        return false;
      }

      // Reset pagination and render
      state.page = 1;
      renderJobs();
    }

    function renderJobs(append = false) {
        const startIndex = (state.page - 1) * state.itemsPerPage;
        const endIndex = startIndex + state.itemsPerPage;
        const jobsToRender = state.filteredJobs.slice(startIndex, endIndex);

        if (!append) {
            jobListingsContainer.innerHTML = ''; // Clear previous listings only if not appending
        }

        if (state.filteredJobs.length === 0 && !append) {
            // MODIFIED: Added a "Clear Filters" button
            jobListingsContainer.innerHTML = `
                <div class="text-center py-8 bg-white rounded-lg border border-dashed border-gray-300">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <h3 class="text-lg font-medium text-gray-900">No jobs match your filters</h3>
                  <p class="text-gray-500 mt-2">Try adjusting your search or filters.</p>
                  <button id="clear-filters-empty" class="mt-4 btn btn-outline text-sm">Clear Filters</button>
                </div>`;
        } else {
            jobsToRender.forEach(job => {
                const jobElement = createJobCard(job);
                jobListingsContainer.appendChild(jobElement);
            });
        }

        // Show/hide load more button
        if (loadMoreButton) {
            if (endIndex >= state.filteredJobs.length) {
                loadMoreButton.classList.add('hidden');
            } else {
                loadMoreButton.classList.remove('hidden');
            }
        }
    }

    // NEW FUNCTION: To clear all filters
    function clearAllFilters() {
        // Clear state
        state.filters.search = '';
        state.filters.location = '';
        state.filters.jobType = '';

        // Clear input elements
        searchInput.value = '';
        locationFilter.value = '';
        jobTypeFilter.value = '';

        // Update UI
        updateFilterTags();
        applyFilters(); // Re-apply filters (which are now empty) to show all jobs
    }

    function createJobCard(job) {
        const div = document.createElement('div');
        div.className = 'card p-4 hover:shadow-md transition-all duration-200';

        const isSaved = state.savedJobs.includes(job.id);
        const userSkillsLower = <?php echo json_encode(array_map('strtolower', $userSkills)); ?>; // Get lowercase user skills

        let skillsHtml = '';
        if (job.skills && Array.isArray(job.skills)) {
            skillsHtml = job.skills.map(skill => {
                const skillLower = skill.toLowerCase();
                const isMatched = userSkillsLower.length > 0 && userSkillsLower.includes(skillLower);
                const badgeClass = isMatched ? 'badge-green' : 'badge-blue';
                const iconHtml = isMatched ? '<svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 ml-1 text-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>' : '';
                return `<span class="badge ${badgeClass} inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">${htmlspecialchars(skill)}${iconHtml}</span>`;
            }).join('');
        }

        let matchScoreHtml = '';
        if (userSkillsLower.length > 0 && job.match_score > 0) {
            const matchedSkillsText = job.matched_skills && job.matched_skills.length > 0
                ? `Matching: ${htmlspecialchars(job.matched_skills.slice(0, 3).join(', '))}${job.matched_skills.length > 3 ? '...' : ''}`
                : '';
            matchScoreHtml = `
                <div class="mt-3 pt-2 border-t border-gray-100">
                  <div class="flex justify-between items-center text-xs text-gray-500 mb-1">
                    <span class="font-medium text-blue-700">Match: ${Math.round(job.match_score * 100)}%</span>
                    ${matchedSkillsText ? `<span class="truncate" title="Matching skills: ${htmlspecialchars(job.matched_skills.join(', '))}">${matchedSkillsText}</span>` : ''}
                  </div>
                  <div class="w-full bg-gray-200 rounded-full h-1.5">
                    <div class="bg-blue-600 h-1.5 rounded-full" style="width: ${Math.round(job.match_score * 100)}%"></div>
                  </div>
                </div>`;
        }


        div.innerHTML = `
            <div class="flex items-start">
              <img src="${htmlspecialchars(job.logo || '')}" alt="${htmlspecialchars(job.company)}" class="w-12 h-12 rounded-lg mr-4 object-contain border border-gray-100 shadow-sm">
              <div class="flex-1">
                <div class="flex justify-between">
                  <h3 class="font-semibold text-lg text-gray-900 hover:text-blue-600">${htmlspecialchars(job.title)}</h3>
                  <button class="save-job-btn text-gray-400 hover:text-blue-600 transition-colors"
                    data-id="${job.id}"
                    data-title="${htmlspecialchars(job.title)}"
                    data-company="${htmlspecialchars(job.company)}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5"
                      fill="${isSaved ? 'currentColor' : 'none'}"
                      viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
                    </svg>
                  </button>
                </div>
                <div class="text-gray-600 text-sm flex items-center mt-1">
                  <span>${htmlspecialchars(job.company)}</span>
                  <span class="mx-2">•</span>
                  <span class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    ${htmlspecialchars(job.location)}
                  </span>
                </div>
                <div class="mt-3 text-gray-700 text-sm leading-relaxed">
                  ${nl2br(htmlspecialchars(job.description.substring(0, 150)))}${job.description.length > 150 ? '...' : ''}
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                  ${skillsHtml}
                </div>
                ${matchScoreHtml}
                <div class="mt-4 flex items-center justify-between">
                  <div class="text-sm text-gray-500 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>${htmlspecialchars(job.posted)}</span>
                    <span class="mx-2">•</span>
                    <span>${htmlspecialchars(job.applicants)} applicants</span>
                  </div>
                  <div class="flex gap-2">
                    <button class="easy-apply-btn btn btn-outline text-sm py-1 px-3 border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
                      data-id="${job.id}"
                      data-title="${htmlspecialchars(job.title)}"
                      data-company="${htmlspecialchars(job.company)}">
                      Easy Apply
                    </button>
                    <button class="apply-now-btn btn btn-secondary text-sm py-1 px-3 bg-green-600 text-white rounded-md hover:bg-green-700 transition-colors"
                      data-id="${job.id}"
                      data-title="${htmlspecialchars(job.title)}"
                      data-company="${htmlspecialchars(job.company)}">
                      Apply Now
                    </button>
                  </div>
                </div>
              </div>
            </div>`;
        return div;
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

    // --- Utility for HTML escaping in JS ---
    function htmlspecialchars(str) {
        if (typeof str !== 'string') return '';
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return str.replace(/[&<>"']/g, m => map[m]);
    }
    function nl2br(str) {
        return str.replace(/\r\n|\r|\n/g, '<br>');
    }


    // --- Initial Render ---
    document.addEventListener('DOMContentLoaded', () => {
        renderJobs(); // Render initial page load
        updateFilterTags(); // Render initial filter tags if any filters are pre-selected

        // Add listeners for history toggles
        const toggleApplicationHistoryButton = document.getElementById('toggle-application-history');
        if (toggleApplicationHistoryButton) {
            toggleApplicationHistoryButton.addEventListener('click', handleToggleApplicationHistory);
        }

        const toggleBookmarkHistoryButton = document.getElementById('toggle-bookmark-history');
        if (toggleBookmarkHistoryButton) {
            toggleBookmarkHistoryButton.addEventListener('click', handleToggleBookmarkHistory);
        }

        // Add listener for My Posted Jobs toggle
        const togglePostedJobsButton = document.getElementById('toggle-posted-jobs');
        if (togglePostedJobsButton) {
            togglePostedJobsButton.addEventListener('click', handleTogglePostedJobs);
        }
    });

    // --- Separate Handler Functions ---
    function handleToggleApplicationHistory() {
        const applicationHistoryContainer = document.getElementById('application-history-container');
        const toggleAppText = document.getElementById('toggle-app-text');
        const toggleAppIcon = document.getElementById('toggle-app-icon');
        if (!applicationHistoryContainer || !toggleAppText || !toggleAppIcon) return;

        applicationHistoryContainer.classList.toggle('hidden');
        const isHidden = applicationHistoryContainer.classList.contains('hidden');
        toggleAppText.textContent = isHidden ? 'Show' : 'Hide';
        toggleAppIcon.classList.toggle('fa-chevron-down', isHidden);
        toggleAppIcon.classList.toggle('fa-chevron-up', !isHidden);
    }

    function handleToggleBookmarkHistory() {
        const bookmarkHistoryContainer = document.getElementById('bookmark-history-container');
        const toggleBookmarkText = document.getElementById('toggle-bookmark-text');
        const toggleBookmarkIcon = document.getElementById('toggle-bookmark-icon');
        if (!bookmarkHistoryContainer || !toggleBookmarkText || !toggleBookmarkIcon) return;

        bookmarkHistoryContainer.classList.toggle('hidden');
        const isHidden = bookmarkHistoryContainer.classList.contains('hidden');
        toggleBookmarkText.textContent = isHidden ? 'Show' : 'Hide';
        toggleBookmarkIcon.classList.toggle('fa-chevron-down', isHidden);
        toggleBookmarkIcon.classList.toggle('fa-chevron-up', !isHidden);
    }

    // New handler for My Posted Jobs toggle
    function handleTogglePostedJobs() {
        const postedJobsContainer = document.getElementById('posted-jobs-container');
        const togglePostedText = document.getElementById('toggle-posted-text');
        const togglePostedIcon = document.getElementById('toggle-posted-icon');
        if (!postedJobsContainer || !togglePostedText || !togglePostedIcon) return;

        postedJobsContainer.classList.toggle('hidden');
        const isHidden = postedJobsContainer.classList.contains('hidden');
        togglePostedText.textContent = isHidden ? 'Show' : 'Hide';
        togglePostedIcon.classList.toggle('fa-chevron-down', isHidden);
        togglePostedIcon.classList.toggle('fa-chevron-up', !isHidden);
    }

    // Close modals when clicking outside
    window.addEventListener('click', (e) => {
      if (e.target === postJobModal) {
        postJobModal.classList.add('hidden');
      }
      if (e.target === insightsModal) {
        insightsModal.classList.add('hidden');
      }
      if (e.target === messageModal) {
        messageModal.classList.add('hidden');
      }
    });
  </script>
</body>

</html>