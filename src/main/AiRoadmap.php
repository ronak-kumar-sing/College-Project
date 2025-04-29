<?php
// Enable error reporting for debugging (remove or adjust for production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// --- Ensure career_roadmaps table exists ---
try {
    $createTableSql = "CREATE TABLE IF NOT EXISTS career_roadmaps (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        career_goal VARCHAR(255) NOT NULL,
        roadmap_data TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    if ($conn->query($createTableSql) !== TRUE) {
        // Log error instead of echoing JSON during page load
        error_log("Error creating career_roadmaps table: " . $conn->error);
        // Optionally, display a user-friendly error message or die
        // die("Database setup error. Please contact support.");
    }
} catch (mysqli_sql_exception $e) {
    error_log("Error checking/creating career_roadmaps table: " . $e->getMessage());
    // Optionally, display a user-friendly error message or die
    // die("Database setup error. Please contact support.");
}
// --- End table check ---

// Handle AJAX request to save roadmap
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_roadmap'])) {
    header('Content-Type: application/json'); // Ensure JSON response

    // Get data from POST
    $career_goal = isset($_POST['career_goal']) ? trim($_POST['career_goal']) : '';
    $roadmap_data = isset($_POST['roadmap_data']) ? trim($_POST['roadmap_data']) : '';

    if (empty($career_goal) || empty($roadmap_data)) {
        echo json_encode(['success' => false, 'message' => 'Missing required data']);
        exit;
    }

    // Insert into database
    $sql = "INSERT INTO career_roadmaps (user_id, career_goal, roadmap_data) VALUES (?, ?, ?)";
    $stmt = null; // Initialize stmt
    try {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        if (!$stmt->bind_param("iss", $user_id, $career_goal, $roadmap_data)) {
             throw new Exception('Binding parameters failed: ' . $stmt->error);
        }

        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }

        $stmt->close();
        echo json_encode(['success' => true, 'message' => 'Roadmap saved successfully']);
        exit;

    } catch (Exception $e) {
         if ($stmt) {
            $stmt->close();
         }
         error_log("Roadmap save error: " . $e->getMessage());
         http_response_code(500); // Internal Server Error for AJAX
         echo json_encode(['success' => false, 'message' => 'Database error while saving roadmap.']);
         exit;
    }
}

// Fetch roadmap history from database
$roadmapHistory = [];
$sql = "SELECT id, career_goal, roadmap_data, created_at FROM career_roadmaps
        WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";

$stmt = null; // Initialize stmt
try {
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }

    if (!$stmt->bind_param("i", $user_id)) {
        throw new Exception('Binding parameters failed: ' . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result === false) {
         throw new Exception('Getting result set failed: ' . $stmt->error);
    }

    while ($row = $result->fetch_assoc()) {
        $roadmapHistory[] = $row;
    }

    $stmt->close();

} catch (Exception $e) {
    if ($stmt) {
        $stmt->close();
    }
    error_log("Error fetching roadmap history: " . $e->getMessage());
    // You might want to display an error on the page or just log it
    // echo "<p>Error loading roadmap history.</p>";
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Career Roadmap Generator</title>
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
              DEFAULT: '#3b82f6',
              foreground: '#ffffff',
            },
            muted: {
              DEFAULT: '#f3f4f6',
              foreground: '#6b7280',
            },
          }
        }
      }
    }
  </script>
  <style type="text/tailwindcss">
    @layer components {
      .btn {
        @apply px-4 py-2 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed;
      }
      .btn-primary {
        @apply bg-primary text-white hover:bg-blue-600 focus:ring-blue-500;
      }
      .btn-outline {
        @apply border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 focus:ring-blue-500;
      }
      .card {
        @apply bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden;
      }
      .input {
        @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
      }
      .label {
        @apply block text-sm font-medium text-gray-700 mb-1;
      }
      .textarea {
        @apply w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500;
      }
    }
  </style>
</head>

<body class="bg-gray-50 min-h-screen">
  <!-- Navigation Header -->
  <header class="bg-white shadow-sm py-4 px-6 mb-8">
    <div class="container mx-auto flex justify-between items-center">
      <a href="../../index.php" class="flex items-center space-x-2">
        <i class="fas fa-compass text-primary text-xl"></i>
        <span class="text-xl font-bold">CareerCompass</span>
      </a>
      <nav class="flex space-x-6">
        <a href="Home.php" class="text-gray-600 hover:text-primary">Career Assessment</a>
        <a href="AiRoadmap.php" class="text-primary font-medium">Roadmap Generator</a>
        <a href="Jobsections.php" class="text-gray-600 hover:text-primary">Job Listings</a>
        <div class="relative group">
          <button class="flex items-center text-gray-600 hover:text-primary">
            <span class="mr-1">
              <?php echo htmlspecialchars($fullname); ?>
            </span>
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
      </nav>
    </div>
  </header>

  <div class="container mx-auto max-w-4xl py-8 px-4">
    <header class="mb-8 text-center">
      <div class="flex items-center justify-center gap-2 mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24"
          stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M9 12l3 3m0 0l3-3m-3 3V8m0 13a9 9 0 110-18 9 9 0 010 18z" />
        </svg>
        <h1 class="text-2xl font-bold">Career Roadmap Generator</h1>
      </div>
      <p class="text-gray-600">Generate a personalized learning roadmap for your career goals using AI</p>
    </header>

    <!-- Input Form -->
    <div id="input-form" class="card mb-8">
      <div class="p-6">
        <h2 class="text-xl font-bold mb-4">Generate Your Career Roadmap</h2>

        <div class="space-y-4 mb-6">
          <div>
            <label for="career-goal" class="label">What is your career goal?</label>
            <input type="text" id="career-goal" class="input"
              placeholder="e.g., Become a Full-Stack Developer, Data Scientist, UX Designer">
          </div>

          <div>
            <label for="current-skills" class="label">What skills do you already have? (Optional)</label>
            <textarea id="current-skills" class="textarea" rows="3"
              placeholder="e.g., HTML/CSS, Basic Python, Project Management"></textarea>
          </div>

          <div>
            <label for="interests" class="label">What are your interests or preferences? (Optional)</label>
            <textarea id="interests" class="textarea" rows="3"
              placeholder="e.g., I enjoy working with data, I prefer remote work, I'm interested in AI"></textarea>
          </div>

          <div>
            <label for="timeframe" class="label">What is your timeframe for achieving this goal? (Optional)</label>
            <select id="timeframe" class="input">
              <option value="">Select a timeframe</option>
              <option value="3-6 months">3-6 months</option>
              <option value="6-12 months">6-12 months</option>
              <option value="1-2 years">1-2 years</option>
              <option value="2+ years">2+ years</option>
            </select>
          </div>
        </div>

        <div class="flex justify-end">
          <button id="generate-roadmap" class="btn btn-primary" disabled>Generate Roadmap</button>
        </div>
      </div>
    </div>

    <!-- Roadmap History Section -->
    <div class="card mb-8">
      <div class="p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold">Roadmap History</h2>
          <button id="toggle-roadmap-history" class="text-sm text-primary hover:text-blue-700 flex items-center">
            <span id="toggle-history-text">Show</span>
            <i class="fas fa-chevron-down text-xs ml-1" id="toggle-history-icon"></i>
          </button>
        </div>

        <div id="roadmap-history-container" class="space-y-3 hidden">
          <?php if (empty($roadmapHistory)): ?>
            <p class="text-gray-500 text-sm italic">No roadmap history found. Generate a roadmap to see it here.</p>
          <?php else: ?>
            <?php foreach ($roadmapHistory as $roadmap): ?>
              <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start">
                  <div>
                    <h3 class="font-medium text-lg"><?php echo htmlspecialchars($roadmap["career_goal"]); ?></h3>
                    <p class="text-sm text-gray-500 mt-1">
                      <?php echo date("F j, Y, g:i a", strtotime($roadmap["created_at"])); ?>
                    </p>
                  </div>

                  <div class="flex space-x-2">
                    <button class="view-roadmap-btn text-sm px-3 py-1 bg-primary text-white rounded-md hover:bg-blue-600"
                      data-id="<?php echo $roadmap["id"]; ?>"
                      data-goal="<?php echo htmlspecialchars($roadmap["career_goal"]); ?>"
                      data-roadmap='<?php echo htmlspecialchars($roadmap["roadmap_data"]); ?>'>
                      View
                    </button>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Loading State -->
    <div id="loading-state" class="card mb-8 hidden">
      <div class="p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary mb-6"></div>
        <h2 class="text-2xl font-bold mb-2">Generating Your Roadmap</h2>
        <p class="text-gray-600 max-w-md mx-auto">
          Our AI is creating a personalized learning path based on your career goals and background.
        </p>
      </div>
    </div>

    <!-- Error State -->
    <div id="error-state" class="card mb-8 hidden">
      <div class="p-6 text-center">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-red-500 mx-auto mb-4" fill="none"
          viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        <h2 class="text-xl font-bold mb-2">Something Went Wrong</h2>
        <p id="error-message" class="text-gray-600 mb-4">Unable to generate roadmap. Please try again.</p>
        <button id="try-again" class="btn btn-primary">Try Again</button>
      </div>
    </div>

    <!-- Roadmap Results -->
    <div id="roadmap-results" class="card hidden">
      <div class="p-6">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-xl font-bold">Your Career Roadmap</h2>
          <div class="flex gap-2">
            <button id="save-roadmap" class="btn btn-outline flex items-center gap-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
              </svg>
              Save
            </button>
            <button id="download-roadmap" class="btn btn-outline flex items-center gap-1">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round"
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
              </svg>
              Download
            </button>
            <button id="new-roadmap" class="btn btn-primary">Create New Roadmap</button>
          </div>
        </div>

        <div id="roadmap-goal" class="mb-6">
          <h3 class="text-lg font-semibold mb-2">Goal:</h3>
          <p id="goal-text" class="text-gray-700"></p>
        </div>

        <div id="roadmap-container" class="space-y-6">
          <!-- Roadmap steps will be inserted here -->
        </div>
      </div>
    </div>

    <!-- Success Message -->
    <div id="success-message" class="fixed inset-0 flex items-center justify-center z-50 hidden">
      <div class="absolute inset-0 bg-black opacity-50"></div>
      <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 z-10">
        <div class="flex items-center justify-center text-green-500 mb-4">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24"
            stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h3 class="text-xl font-bold text-center mb-2">Success!</h3>
        <p class="text-gray-600 text-center mb-6" id="success-message-text">Your roadmap has been saved successfully.
        </p>
        <div class="flex justify-center">
          <button id="close-success" class="btn btn-primary">Close</button>
        </div>
      </div>
    </div>

    <script>
      // State management
      const state = {
        apiKey: 'AIzaSyBasaBU3srwcOqVQoyT7uZmtXPa4NRi6gU', // Replace this with your actual API key
        careerGoal: '',
        currentSkills: '',
        interests: '',
        timeframe: '',
        roadmap: [],
        isLoading: false,
        error: null,
        userId: <?php echo $user_id; ?>,
        userName: "<?php echo htmlspecialchars($fullname); ?>"
      };

      // DOM Elements
      const inputForm = document.getElementById('input-form');
      const loadingState = document.getElementById('loading-state');
      const errorState = document.getElementById('error-state');
      const roadmapResults = document.getElementById('roadmap-results');
      const successMessage = document.getElementById('success-message');
      const successMessageText = document.getElementById('success-message-text');

      const careerGoalInput = document.getElementById('career-goal');
      const currentSkillsInput = document.getElementById('current-skills');
      const interestsInput = document.getElementById('interests');
      const timeframeInput = document.getElementById('timeframe');
      const generateRoadmapButton = document.getElementById('generate-roadmap');

      const errorMessage = document.getElementById('error-message');
      const tryAgainButton = document.getElementById('try-again');

      const goalText = document.getElementById('goal-text');
      const roadmapContainer = document.getElementById('roadmap-container');
      const saveRoadmapButton = document.getElementById('save-roadmap');
      const downloadRoadmapButton = document.getElementById('download-roadmap');
      const newRoadmapButton = document.getElementById('new-roadmap');
      const closeSuccessButton = document.getElementById('close-success');

      // Roadmap history elements
      const toggleRoadmapHistoryButton = document.getElementById('toggle-roadmap-history');
      const roadmapHistoryContainer = document.getElementById('roadmap-history-container');
      const toggleHistoryText = document.getElementById('toggle-history-text');
      const toggleHistoryIcon = document.getElementById('toggle-history-icon');

      // Initial setup
      generateRoadmapButton.disabled = !careerGoalInput.value.trim();

      // Event Listeners
      careerGoalInput.addEventListener('input', () => {
        generateRoadmapButton.disabled = !careerGoalInput.value.trim();
      });

      generateRoadmapButton.addEventListener('click', async () => {
        state.careerGoal = careerGoalInput.value.trim();
        state.currentSkills = currentSkillsInput.value.trim();
        state.interests = interestsInput.value.trim();
        state.timeframe = timeframeInput.value;

        if (!state.careerGoal) {
          alert('Please enter your career goal');
          return;
        }

        // Reset save button state when generating a new roadmap
        saveRoadmapButton.disabled = false;
        saveRoadmapButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg> Save';
        saveRoadmapButton.classList.remove('opacity-70');


        try {
          showLoadingState();
          const roadmap = await generateRoadmap();
          state.roadmap = roadmap;
          showRoadmapResults();
        } catch (error) {
          console.error('Error generating roadmap:', error);
          state.error = error.message || 'Unable to generate roadmap. Please try again.';
          showErrorState();
        }
      });

      tryAgainButton.addEventListener('click', () => {
        hideErrorState();
        showInputForm();
      });

      newRoadmapButton.addEventListener('click', () => {
        // Reset input fields
        careerGoalInput.value = '';
        currentSkillsInput.value = '';
        interestsInput.value = '';
        timeframeInput.value = '';
        generateRoadmapButton.disabled = true; // Disable button as goal is cleared

        // Reset state related to the current roadmap
        state.careerGoal = '';
        state.currentSkills = '';
        state.interests = '';
        state.timeframe = '';
        state.roadmap = [];

        hideRoadmapResults();
        showInputForm();
      });

      downloadRoadmapButton.addEventListener('click', () => {
        downloadRoadmap();
      });

      saveRoadmapButton.addEventListener('click', async () => {
        if (saveRoadmapButton.disabled) return; // Prevent saving if already saved/saving

        try {
          // Disable button to prevent multiple clicks
          saveRoadmapButton.disabled = true;
          saveRoadmapButton.innerHTML = '<svg class="animate-spin h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Saving...';

          // Prepare data for saving
          const roadmapData = JSON.stringify(state.roadmap);

          // Send data to server
          const formData = new FormData();
          formData.append('save_roadmap', 'true');
          formData.append('career_goal', state.careerGoal);
          formData.append('roadmap_data', roadmapData);

          const response = await fetch('AiRoadmap.php', {
            method: 'POST',
            body: formData
          });

          // Check if response is ok and content type is JSON before parsing
          if (!response.ok) {
             let errorMsg = `HTTP error! status: ${response.status}`;
             try {
                 const errorData = await response.json();
                 errorMsg = errorData.message || errorMsg;
             } catch (e) {
                 // If response is not JSON, use the status text
                 errorMsg = response.statusText || errorMsg;
             }
             throw new Error(errorMsg);
          }

          const result = await response.json();

          if (result.success) {
            // Show success message
            successMessageText.textContent = 'Your roadmap has been saved successfully.';
            successMessage.classList.remove('hidden');

            // Update button to show saved state
            saveRoadmapButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Saved';
            saveRoadmapButton.classList.add('opacity-70');
            // Keep button disabled after successful save
            saveRoadmapButton.disabled = true;

            // Optionally refresh history section or add the new item dynamically
            // For simplicity, we'll just note that a refresh might be needed
            // Or add a small delay then reload the page: setTimeout(() => location.reload(), 1500);

          } else {
            throw new Error(result.message || 'Failed to save roadmap');
          }
        } catch (error) {
          console.error('Error saving roadmap:', error);
          alert('Error saving roadmap: ' + error.message);

          // Reset button state only on error
          saveRoadmapButton.disabled = false;
          saveRoadmapButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg> Save';
          saveRoadmapButton.classList.remove('opacity-70');
        }
      });

      closeSuccessButton.addEventListener('click', () => {
        successMessage.classList.add('hidden');
      });

      // Toggle roadmap history visibility
      toggleRoadmapHistoryButton.addEventListener('click', () => {
        roadmapHistoryContainer.classList.toggle('hidden');
        if (roadmapHistoryContainer.classList.contains('hidden')) {
          toggleHistoryText.textContent = 'Show';
          toggleHistoryIcon.classList.remove('fa-chevron-up');
          toggleHistoryIcon.classList.add('fa-chevron-down');
        } else {
          toggleHistoryText.textContent = 'Hide';
          toggleHistoryIcon.classList.remove('fa-chevron-down');
          toggleHistoryIcon.classList.add('fa-chevron-up');
        }
      });

      // Add event listeners to view roadmap buttons
      document.querySelectorAll('.view-roadmap-btn').forEach(button => {
        button.addEventListener('click', () => {
          const roadmapGoal = button.getAttribute('data-goal');
          const roadmapData = button.getAttribute('data-roadmap');

          try {
            // Parse the roadmap data
            const roadmap = JSON.parse(roadmapData);

            // Set state values
            state.careerGoal = roadmapGoal;
            state.roadmap = roadmap;

            // Update input fields to reflect loaded roadmap (optional)
            careerGoalInput.value = state.careerGoal;
            // Clear other fields as they weren't saved with the history item
            currentSkillsInput.value = '';
            interestsInput.value = '';
            timeframeInput.value = '';
            generateRoadmapButton.disabled = false; // Enable generate button

            // Show the roadmap
            showRoadmapResults();

            // Scroll to the roadmap results
            roadmapResults.scrollIntoView({ behavior: 'smooth' });

            // Disable save button since it's already saved
            saveRoadmapButton.disabled = true;
            saveRoadmapButton.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg> Saved';
            saveRoadmapButton.classList.add('opacity-70');
          } catch (error) {
            console.error('Error parsing roadmap data:', error);
            alert('Could not load roadmap data');
          }
        });
      });

      // Functions
      function showLoadingState() {
        inputForm.classList.add('hidden');
        errorState.classList.add('hidden');
        roadmapResults.classList.add('hidden');
        loadingState.classList.remove('hidden');
        state.isLoading = true;
      }

      function hideLoadingState() {
        loadingState.classList.add('hidden');
        state.isLoading = false;
      }

      function showErrorState() {
        hideLoadingState();
        inputForm.classList.add('hidden');
        roadmapResults.classList.add('hidden');
        errorMessage.textContent = state.error;
        errorState.classList.remove('hidden');
      }

      function hideErrorState() {
        errorState.classList.add('hidden');
      }

      function showInputForm() {
        hideLoadingState();
        hideErrorState();
        roadmapResults.classList.add('hidden');
        inputForm.classList.remove('hidden');
      }

      function showRoadmapResults() {
        hideLoadingState();
        hideErrorState();
        inputForm.classList.add('hidden');

        // Set goal text
        goalText.textContent = state.careerGoal;

        // Render roadmap steps
        renderRoadmap();

        roadmapResults.classList.remove('hidden');
      }

      function hideRoadmapResults() {
        roadmapResults.classList.add('hidden');
      }

      function renderRoadmap() {
        roadmapContainer.innerHTML = ''; // Clear previous roadmap

        if (!Array.isArray(state.roadmap) || state.roadmap.length === 0) {
             roadmapContainer.innerHTML = '<p class="text-gray-500 italic">No roadmap steps available.</p>';
             return;
        }


        state.roadmap.forEach((step, index) => {
          const stepElement = document.createElement('div');
          stepElement.className = 'relative pl-8 pb-8';

          // Add timeline connector
          if (index < state.roadmap.length - 1) {
            const connector = document.createElement('div');
            connector.className = 'absolute left-3 top-3 h-full w-px bg-gray-300';
            stepElement.appendChild(connector);
          }

          // Add step number circle
          const stepNumber = document.createElement('div');
          stepNumber.className = 'absolute left-0 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-medium';
          stepNumber.textContent = (index + 1).toString();
          stepElement.appendChild(stepNumber);

          // Add step content
          const content = document.createElement('div');
          content.className = 'space-y-2';

          // Title with timeframe
          const titleContainer = document.createElement('div');
          titleContainer.className = 'flex items-center flex-wrap gap-2';

          const title = document.createElement('h3');
          title.className = 'text-lg font-medium';
          title.textContent = step.title || 'Untitled Step'; // Add fallback
          titleContainer.appendChild(title);

          if (step.timeframe) {
            const timeframe = document.createElement('span');
            timeframe.className = 'inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800';
            timeframe.textContent = step.timeframe;
            titleContainer.appendChild(timeframe);
          }

          content.appendChild(titleContainer);

          // Description
          if (step.description) {
            const description = document.createElement('p');
            description.className = 'text-gray-700';
            description.textContent = step.description;
            content.appendChild(description);
          }

          // Resources
          if (Array.isArray(step.resources) && step.resources.length > 0) {
            const resourcesContainer = document.createElement('div');
            resourcesContainer.className = 'mt-2';

            const resourcesTitle = document.createElement('h4');
            resourcesTitle.className = 'text-sm font-medium mb-1';
            resourcesTitle.textContent = 'Recommended Resources:';
            resourcesContainer.appendChild(resourcesTitle);

            const resourcesList = document.createElement('ul');
            resourcesList.className = 'space-y-1 list-none pl-0'; // Use list-none and pl-0

            step.resources.forEach(resource => {
              const resourceItem = document.createElement('li');
              resourceItem.className = 'text-sm flex items-start';

              const bullet = document.createElement('span');
              bullet.className = 'text-primary mr-2 flex-shrink-0'; // Added flex-shrink-0
              bullet.innerHTML = '&bull;';
              resourceItem.appendChild(bullet);

              const resourceText = document.createElement('span');
              resourceText.textContent = resource;
              resourceItem.appendChild(resourceText);

              resourcesList.appendChild(resourceItem);
            });

            resourcesContainer.appendChild(resourcesList);
            content.appendChild(resourcesContainer);
          }

          stepElement.appendChild(content);
          roadmapContainer.appendChild(stepElement);
        });
      }

      function downloadRoadmap() {
        const filename = `Career_Roadmap_${state.careerGoal.replace(/[^a-z0-9]/gi, '_')}_${new Date().toISOString().split('T')[0]}.txt`;

        let content = `CAREER ROADMAP: ${state.careerGoal}\n\n`;

        if (state.currentSkills) {
          content += `Current Skills: ${state.currentSkills}\n`;
        }

        if (state.interests) {
          content += `Interests: ${state.interests}\n`;
        }

        if (state.timeframe) {
          content += `Timeframe: ${state.timeframe}\n`;
        }

        content += '\n---\n\n';

        if (Array.isArray(state.roadmap)) {
            state.roadmap.forEach((step, index) => {
              content += `STEP ${index + 1}: ${step.title || 'Untitled'} (${step.timeframe || 'N/A'})\n`;
              content += `${step.description || 'No description.'}\n\n`;

              if (Array.isArray(step.resources) && step.resources.length > 0) {
                  content += 'Recommended Resources:\n';
                  step.resources.forEach(resource => {
                    content += `- ${resource}\n`;
                  });
              } else {
                  content += 'Recommended Resources: None listed.\n';
              }
              content += '\n---\n\n';
            });
        } else {
             content += 'No roadmap steps available.\n';
        }


        const element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(content));
        element.setAttribute('download', filename);
        element.style.display = 'none';

        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
      }

      async function generateRoadmap() {
        // Create a context summary from the user inputs
        let contextPrompt = `Generate a career roadmap based on the following details:\n\n`;
        contextPrompt += `Career Goal: ${state.careerGoal}\n`;

        if (state.currentSkills) {
          contextPrompt += `Current Skills: ${state.currentSkills}\n`;
        }

        if (state.interests) {
          contextPrompt += `Interests and Preferences: ${state.interests}\n`;
        }

        if (state.timeframe) {
          contextPrompt += `Desired Timeframe: ${state.timeframe}\n`;
        }

        // Roadmap generation prompt
        const roadmapPrompt = `\nPlease create a detailed learning and development roadmap.

The roadmap should consist of 4 to 6 sequential steps. For each step, provide:
1.  A clear "title".
2.  A concise "description" of the objective or tasks for that step.
3.  A realistic "timeframe" (e.g., "1-2 weeks", "1 month", "2-3 months").
4.  An array of 2-4 specific "resources" (like online courses, books, tools, platforms, or types of projects).

Format the entire response strictly as a JSON array of objects, like this example:
[
  {
    "title": "Foundational Knowledge",
    "description": "Understand the core concepts of [Field]. Focus on terminology and basic principles.",
    "timeframe": "2-4 weeks",
    "resources": ["Specific Course Name on Coursera", "Introductory Book Title", "Official Documentation Website", "YouTube Channel Name"]
  },
  {
    "title": "Skill Development",
    "description": "Practice core skill X and Y through hands-on exercises.",
    "timeframe": "1-2 months",
    "resources": ["Interactive Platform (e.g., LeetCode, Kaggle)", "Project Tutorial Website", "Specific Tool Name", "Online Community Forum"]
  }
]

Ensure the output is only the JSON array, starting with '[' and ending with ']'. Make the roadmap practical, actionable, and tailored to the provided goal and context.`;

        // Combine context with roadmap prompt
        const fullPrompt = `${contextPrompt}\n${roadmapPrompt}`;

        // Use a timeout for the API call
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 seconds timeout

        try {
          const response = await fetch('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' + state.apiKey, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              contents: [{
                parts: [{
                  text: fullPrompt
                }]
              }],
              // Optional: Adjust generation config if needed
              // generationConfig: {
              //   temperature: 0.7,
              //   topP: 0.9,
              //   topK: 40,
              //   maxOutputTokens: 2048, // Adjust if needed
              //   responseMimeType: "application/json" // Request JSON directly if supported
              // }
            }),
            signal: controller.signal // Add abort signal
          });

          clearTimeout(timeoutId); // Clear timeout if fetch completes

          if (!response.ok) {
             let errorData = { message: `API request failed with status ${response.status}` };
             try {
                 errorData = await response.json();
             } catch(e) { /* Ignore if response is not JSON */ }
             throw new Error(errorData.error?.message || errorData.message || `HTTP error ${response.status}`);
          }


          const data = await response.json();

          // Check for API-level errors in the response body
          if (data.error) {
            throw new Error(data.error.message || "API returned an error");
          }

          // Check for safety blocks or missing content
           if (!data.candidates || data.candidates.length === 0) {
                if (data.promptFeedback?.blockReason) {
                    throw new Error(`Content blocked due to: ${data.promptFeedback.blockReason}`);
                }
                throw new Error("API returned no candidates.");
            }

            const candidate = data.candidates[0];

            if (candidate.finishReason && candidate.finishReason !== 'STOP') {
                 console.warn(`API finish reason: ${candidate.finishReason}`);
                 if (candidate.finishReason === 'SAFETY') {
                     throw new Error("Content generation stopped due to safety settings.");
                 }
                 // Handle other reasons like MAX_TOKENS if necessary
            }


          if (!candidate.content?.parts?.[0]?.text) {
            throw new Error("Invalid response format from API: Missing content text.");
          }

          const text = candidate.content.parts[0].text;
          console.log("Raw API response text:", text);

          // Extract the roadmap from the response
          const roadmap = extractRoadmapFromResponse(text);

          if (!Array.isArray(roadmap) || roadmap.length === 0) {
             console.warn("Failed to extract structured roadmap, using fallback.");
             // Fallback roadmap if extraction fails or returns empty
             return [{
                title: "Research & Planning",
                description: `Deep dive into the ${state.careerGoal} field. Identify key skills, roles, and companies.`,
                timeframe: "1-2 weeks",
                resources: ["Industry blogs/news sites", "LinkedIn profiles of people in the role", "Job descriptions analysis", "Informational interviews"],
              },
              {
                title: "Core Skill Acquisition",
                description: "Focus on learning the fundamental technical and soft skills required.",
                timeframe: "2-4 months",
                resources: ["Relevant online courses (Coursera, Udemy, edX)", "Key textbooks or documentation", "Beginner-friendly project tutorials", "Practice platforms (e.g., Codewars, HackerRank if applicable)"],
              },
              {
                title: "Build Initial Portfolio",
                description: "Apply learned skills by completing 1-2 small projects. Document your process.",
                timeframe: "1-2 months",
                resources: ["GitHub or similar platform", "Personal blog or website", "Find project ideas online", "Contribute to small open-source projects"],
              },
              {
                title: "Network & Refine",
                description: "Connect with professionals in the field. Get feedback on your projects. Refine your resume and online presence.",
                timeframe: "Ongoing",
                resources: ["LinkedIn", "Meetup groups or virtual events", "Industry conferences (if possible)", "Mentorship platforms"],
              },
               {
                title: "Job Application & Interview Prep",
                description: "Start applying for relevant roles. Practice common interview questions and techniques.",
                timeframe: "Ongoing",
                resources: ["Job boards (LinkedIn, Indeed, specialized boards)", "Company career pages", "Interview prep websites (Glassdoor, LeetCode)", "Mock interview practice"],
              }
            ];
          }

          return roadmap; // Return the successfully extracted roadmap
        } catch (error) {
          clearTimeout(timeoutId); // Ensure timeout is cleared on error
          console.error("Error in generateRoadmap API call:", error);
          // Re-throw the error to be caught by the calling function
          throw new Error(`Failed to generate roadmap: ${error.message}`);
        }
      }

      // Helper function to extract roadmap data from the response
      function extractRoadmapFromResponse(text) {
        try {
          // Trim whitespace and remove potential markdown code fences
          const cleanedText = text.trim().replace(/^```json\s*|```\s*$/g, '');

          // Attempt to parse directly as JSON
          const roadmap = JSON.parse(cleanedText);

          // Basic validation of the parsed structure
          if (
            Array.isArray(roadmap) &&
            roadmap.length > 0 &&
            roadmap.every(step =>
              typeof step === 'object' &&
              step !== null &&
              typeof step.title === 'string' &&
              typeof step.description === 'string' &&
              typeof step.timeframe === 'string' &&
              Array.isArray(step.resources) &&
              step.resources.every(res => typeof res === 'string')
            )
          ) {
            console.log("Successfully parsed JSON roadmap.");
            return roadmap;
          } else {
             console.warn("Parsed JSON does not match expected roadmap structure.");
             return []; // Return empty if structure is invalid
          }
        } catch (parseError) {
          console.error("Failed to parse API response as JSON:", parseError);
          // If JSON parsing fails, return empty array.
          // The fallback logic previously here was less reliable than the JSON request.
          return [];
        }
      }

      // Initialize on page load
      // (No specific initialization needed beyond variable declarations and event listeners)

    </script>
  </div>
</body>

</html>