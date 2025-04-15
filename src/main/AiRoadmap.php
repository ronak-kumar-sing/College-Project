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
        @apply px-4 py-2 rounded-md font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2;
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
            <a href="../auth//logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
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
          <button id="generate-roadmap" class="btn btn-primary">Generate Roadmap</button>
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
      const downloadRoadmapButton = document.getElementById('download-roadmap');
      const newRoadmapButton = document.getElementById('new-roadmap');
      const closeSuccessButton = document.getElementById('close-success');

      // Saved roadmaps elements
      const toggleSavedRoadmapsButton = document.getElementById('toggle-saved-roadmaps');
      const savedRoadmapsContainer = document.getElementById('saved-roadmaps-container');
      const toggleText = document.getElementById('toggle-text');
      const toggleIcon = document.getElementById('toggle-icon');

      // Add this after defining DOM elements
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
        hideRoadmapResults();
        showInputForm();
      });

      downloadRoadmapButton.addEventListener('click', () => {
        downloadRoadmap();
      });

      closeSuccessButton.addEventListener('click', () => {
        successMessage.classList.add('hidden');
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
        roadmapContainer.innerHTML = '';

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
          title.textContent = step.title;
          titleContainer.appendChild(title);

          const timeframe = document.createElement('span');
          timeframe.className = 'inline-flex items-center rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-medium text-blue-800';
          timeframe.textContent = step.timeframe;
          titleContainer.appendChild(timeframe);

          content.appendChild(titleContainer);

          // Description
          const description = document.createElement('p');
          description.className = 'text-gray-700';
          description.textContent = step.description;
          content.appendChild(description);

          // Resources
          const resourcesContainer = document.createElement('div');
          resourcesContainer.className = 'mt-2';

          const resourcesTitle = document.createElement('h4');
          resourcesTitle.className = 'text-sm font-medium mb-1';
          resourcesTitle.textContent = 'Recommended Resources:';
          resourcesContainer.appendChild(resourcesTitle);

          const resourcesList = document.createElement('ul');
          resourcesList.className = 'space-y-1';

          step.resources.forEach(resource => {
            const resourceItem = document.createElement('li');
            resourceItem.className = 'text-sm flex items-start';

            const bullet = document.createElement('span');
            bullet.className = 'text-primary mr-1';
            bullet.innerHTML = '&bull;';
            resourceItem.appendChild(bullet);

            const resourceText = document.createElement('span');
            resourceText.textContent = resource;
            resourceItem.appendChild(resourceText);

            resourcesList.appendChild(resourceItem);
          });

          resourcesContainer.appendChild(resourcesList);
          content.appendChild(resourcesContainer);

          stepElement.appendChild(content);
          roadmapContainer.appendChild(stepElement);
        });
      }

      function downloadRoadmap() {
        const filename = `Career_Roadmap_${new Date().toISOString().split('T')[0]}.txt`;

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

        content += '\n';

        state.roadmap.forEach((step, index) => {
          content += `STEP ${index + 1}: ${step.title} (${step.timeframe})\n`;
          content += `${step.description}\n\n`;
          content += 'Recommended Resources:\n';

          step.resources.forEach(resource => {
            content += `- ${resource}\n`;
          });

          content += '\n';
        });

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
        let contextPrompt = `Career Goal: ${state.careerGoal}\n\n`;

        if (state.currentSkills) {
          contextPrompt += `Current Skills: ${state.currentSkills}\n\n`;
        }

        if (state.interests) {
          contextPrompt += `Interests and Preferences: ${state.interests}\n\n`;
        }

        if (state.timeframe) {
          contextPrompt += `Desired Timeframe: ${state.timeframe}\n\n`;
        }

        // Roadmap generation prompt
        const roadmapPrompt = `Based on the information provided, create a detailed learning roadmap for my career development.

The roadmap should include 4-6 sequential steps, each with:
1. A clear title for the step
2. A concise description of what to learn or do
3. A realistic timeframe (e.g., "1-3 months")
4. 2-4 specific resources (courses, books, tools, or platforms)

Format your response as a JSON array that I can parse, following this structure:
[
  {
    "title": "Step Title",
    "description": "Description of what to learn/do",
    "timeframe": "Timeframe to complete",
    "resources": ["Resource 1", "Resource 2", "Resource 3"]
  }
]

Make the roadmap practical, actionable, and tailored to my specific career goals and current skill level.`;

        // Combine context with roadmap prompt
        const fullPrompt = `${contextPrompt}\n\n${roadmapPrompt}`;

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
              generationConfig: {
                temperature: 0.7,
                topP: 0.9,
                topK: 40,
                maxOutputTokens: 2048,
              }
            })
          });

          const data = await response.json();

          if (data.error) {
            throw new Error(data.error.message || "API Error");
          }

          const text = data.candidates[0].content.parts[0].text;
          console.log("Raw API response:", text);
          console.log("Roadmap generation response:", text);

          // Extract the roadmap from the response
          const roadmap = extractRoadmapFromResponse(text);

          if (roadmap.length === 0) {
            // Fallback roadmap if extraction fails
            return [{
                title: "Research and Exploration",
                description: "Explore the field and identify key skills needed",
                timeframe: "2-4 weeks",
                resources: ["Industry blogs", "Professional forums", "Informational interviews"],
              },
              {
                title: "Fundamental Skills",
                description: "Build the core skills required for entry-level positions",
                timeframe: "2-3 months",
                resources: ["Online courses", "Books", "Practice projects"],
              },
              {
                title: "Portfolio Development",
                description: "Create projects that demonstrate your abilities",
                timeframe: "1-2 months",
                resources: ["GitHub", "Personal website", "Project tutorials"],
              },
              {
                title: "Networking and Job Search",
                description: "Connect with professionals and apply for positions",
                timeframe: "Ongoing",
                resources: ["LinkedIn", "Industry events", "Job boards"],
              },
            ];
          }

          return roadmap;
        } catch (error) {
          console.error("Error generating roadmap:", error);
          throw error;
        }
      }

      // Helper function to extract roadmap data from the response
      function extractRoadmapFromResponse(text) {
        try {
          // Look for JSON-like structures in the text
          const jsonMatch = text.match(/\[\s*\{[\s\S]*\}\s*\]/);

          if (jsonMatch) {
            const jsonStr = jsonMatch[0];
            const roadmap = JSON.parse(jsonStr);

            // Validate the structure
            if (
              Array.isArray(roadmap) &&
              roadmap.length > 0 &&
              roadmap[0].title &&
              roadmap[0].description &&
              roadmap[0].timeframe &&
              Array.isArray(roadmap[0].resources)
            ) {
              return roadmap;
            }
          }

          // Fallback: Try to parse structured text into roadmap steps
          const steps = [];
          const lines = text.split('\n');

          let currentStep = null;

          for (const line of lines) {
            // Look for patterns like "1. **Title:**" or "Step 1: Title"
            const stepMatch = line.match(/(?:Step\s*)?(\d+)[:.]\s*(?:\*\*)?([^:*]+)(?:\*\*)?/i);

            if (stepMatch && !line.toLowerCase().includes("resource")) {
              // Save previous step if exists
              if (currentStep?.title && currentStep?.description) {
                steps.push(currentStep);
              }

              // Start new step
              currentStep = {
                title: stepMatch[2].trim(),
                description: "",
                timeframe: "Varies",
                resources: [],
              };
            }
            // Look for timeframe mentions
            else if (currentStep && line.toLowerCase().includes("month")) {
              currentStep.timeframe = line.trim();
            }
            // Look for resources
            else if (currentStep && (line.includes("•") || line.includes("-") || line.match(/\d+\./))) {
              const resource = line.replace(/^[•\-\d.]+\s*/, "").trim();
              if (resource && !currentStep.resources.includes(resource)) {
                currentStep.resources = [...(currentStep.resources || []), resource];
              }
            }
            // Add to description
            else if (currentStep && line.trim() && !currentStep.description) {
              currentStep.description = line.trim();
            }
          }

          // Add the last step if exists
          if (currentStep?.title && currentStep?.description) {
            steps.push(currentStep);
          }

          return steps.length > 0 ? steps : [];
        } catch (error) {
          console.error("Error extracting roadmap:", error);
          return [];
        }
      }
    </script>
  </div>
</body>

</html>