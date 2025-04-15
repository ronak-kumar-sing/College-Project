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

// Set empty array instead of calling database
$pastAssessments = [];
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Career Assessment Questionnaire</title>
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
        <a href="Home.php" class="text-primary font-medium">Career Assessment</a>
        <a href="AiRoadmap.php" class="text-gray-600 hover:text-primary">Roadmap Generator</a>
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
            <a href="../auth/login.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
              <i class="fas fa-sign-out-alt mr-1"></i> Logout
            </a>
          </div>
        </div>
      </nav>
    </div>
  </header>

  <div class="container mx-auto max-w-3xl py-8 px-4">
    <header class="mb-8 text-center">
      <div class="flex items-center justify-center gap-2 mb-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-primary" fill="none" viewBox="0 0 24 24"
          stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round"
            d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
        <h1 class="text-2xl font-bold">Career Compass</h1>
      </div>
      <p class="text-gray-600">Complete this assessment to receive personalized career guidance</p>
    </header>

    <!-- Past Assessments Section -->
    <?php if (!empty($pastAssessments)): ?>
      <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
          <h2 class="text-lg font-semibold">Your Past Assessments</h2>
          <button id="toggle-past-assessments" class="text-sm text-primary hover:text-blue-700">
            <span id="toggle-text">Show</span> <i class="fas fa-chevron-down text-xs ml-1" id="toggle-icon"></i>
          </button>
        </div>
        <div id="past-assessments-container" class="space-y-3 hidden">
          <?php foreach ($pastAssessments as $assessment): ?>
            <div class="card p-4 hover:shadow-md transition-shadow">
              <div class="flex justify-between items-start">
                <div>
                  <h3 class="font-medium">
                    <?php echo htmlspecialchars($assessment["career_interest"]); ?>
                  </h3>
                  <p class="text-sm text-gray-500">
                    <?php echo date("F j, Y", strtotime($assessment["created_at"])); ?>
                  </p>
                </div>
                <button class="view-assessment-btn text-primary hover:text-blue-700 text-sm"
                  data-id="<?php echo $assessment["id"]; ?>"
                  data-results="<?php echo htmlspecialchars($assessment["results"]); ?>"
                  data-interest="<?php echo htmlspecialchars($assessment["career_interest"]); ?>">
                  View Results
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Intro Page -->
    <div id="intro-page" class="card">
      <div class="p-6">
        <h2 class="text-xl font-bold mb-4">Career Assessment</h2>
        <p class="text-gray-600 mb-6">Answer a series of questions to get personalized career guidance</p>

        <div class="mb-6">
          <label for="career-interest" class="label">What career field are you interested in?</label>
          <input type="text" id="career-interest" class="input"
            placeholder="e.g., Software Development, Healthcare, Finance">
        </div>

        <div class="bg-blue-50 p-4 rounded-lg mb-6">
          <h3 class="font-medium mb-2">What to expect:</h3>
          <ul class="space-y-2 list-disc pl-5">
            <li>A series of questions across 3 pages</li>
            <li>Each new page's questions will be based on your previous answers</li>
            <li>Your responses will be used to generate personalized career guidance</li>
            <li>The assessment takes approximately 5-10 minutes to complete</li>
          </ul>
        </div>

        <div class="flex justify-end">
          <button id="start-assessment" class="btn btn-primary">Start Assessment</button>
        </div>
      </div>
    </div>

    <!-- Questionnaire Pages -->
    <div id="questionnaire-container" class="card hidden">
      <div class="p-6">
        <div class="flex justify-between items-center mb-4">
          <h2 class="text-xl font-bold">Career Assessment</h2>
          <div class="text-sm text-gray-600">Page <span id="current-page">1</span> of <span id="total-pages">3</span>
          </div>
        </div>

        <!-- Progress Bar -->
        <div class="w-full bg-gray-200 h-2 rounded-full mb-6">
          <div id="progress-bar" class="bg-primary h-full rounded-full transition-all duration-300" style="width: 33%">
          </div>
        </div>

        <!-- AI Explanation -->
        <div id="ai-explanation-container" class="bg-blue-50 p-4 rounded-lg mb-6 hidden">
          <h4 class="text-sm font-medium mb-2">Why we're asking these questions:</h4>
          <p id="ai-explanation" class="text-sm text-gray-600"></p>
        </div>

        <!-- Questions Container -->
        <div id="questions-container" class="space-y-6 mb-6">
          <!-- Questions will be dynamically inserted here -->
        </div>

        <!-- Loading Indicator -->
        <div id="loading-container" class="py-8 text-center hidden">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary mb-4"></div>
          <p class="text-gray-600">Generating personalized questions based on your answers...</p>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex justify-between">
          <button id="prev-button" class="btn btn-outline hidden">Previous</button>
          <div class="flex-1"></div>
          <button id="next-button" class="btn btn-primary" disabled>Next</button>
        </div>
      </div>
    </div>

    <!-- Processing Page -->
    <div id="processing-page" class="card hidden">
      <div class="p-8 text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary mb-6"></div>
        <h2 class="text-2xl font-bold mb-2">Analyzing Your Responses</h2>
        <p class="text-gray-600 max-w-md mx-auto">
          Our AI is analyzing your answers to create personalized career guidance tailored to your unique profile.
        </p>
      </div>
    </div>

    <!-- Results Page -->
    <div id="results-page" class="card hidden">
      <div class="p-6">
        <div class="flex items-center gap-2 mb-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24"
            stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <h2 class="text-xl font-bold">Your Career Assessment Results</h2>
        </div>
        <p class="text-gray-600 mb-6">Personalized guidance based on your responses</p>

        <div id="results-content" class="prose max-w-none mb-6">
          <!-- Results will be inserted here -->
        </div>

        <div class="flex justify-between">
          <button id="new-assessment" class="btn btn-outline">Start New Assessment</button>
          <div class="flex gap-2">
            <button id="save-results" class="btn btn-outline">Save Results</button>
            <button id="download-results" class="btn btn-primary">Download Results</button>
          </div>
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
        <p class="text-gray-600 text-center mb-6" id="success-message-text">Your assessment results have been saved
          successfully.</p>
        <div class="flex justify-center">
          <button id="close-success" class="btn btn-primary">Close</button>
        </div>
      </div>
    </div>

    <script>
      // State management
      const state = {
        apiKey: 'AIzaSyBasaBU3srwcOqVQoyT7uZmtXPa4NRi6gU', // Replace this with your actual API key
        careerInterest: '',
        currentPage: 0,
        totalPages: 3,
        allPages: [],
        allAnswers: [],
        currentAnswers: {},
        aiExplanation: '',
        results: '',
        isLoading: false,
        userId: <?php echo $user_id; ?>,
        userName: "<?php echo htmlspecialchars($fullname); ?>"
      };

      // DOM Elements
      const introPage = document.getElementById('intro-page');
      const questionnaireContainer = document.getElementById('questionnaire-container');
      const processingPage = document.getElementById('processing-page');
      const resultsPage = document.getElementById('results-page');
      const successMessage = document.getElementById('success-message');
      const successMessageText = document.getElementById('success-message-text');

      const careerInterestInput = document.getElementById('career-interest');
      const startAssessmentButton = document.getElementById('start-assessment');
      const questionsContainer = document.getElementById('questions-container');
      const loadingContainer = document.getElementById('loading-container');
      const aiExplanationContainer = document.getElementById('ai-explanation-container');
      const aiExplanationText = document.getElementById('ai-explanation');
      const prevButton = document.getElementById('prev-button');
      const nextButton = document.getElementById('next-button');
      const currentPageText = document.getElementById('current-page');
      const progressBar = document.getElementById('progress-bar');
      const resultsContent = document.getElementById('results-content');
      const newAssessmentButton = document.getElementById('new-assessment');
      const saveResultsButton = document.getElementById('save-results');
      const downloadResultsButton = document.getElementById('download-results');
      const closeSuccessButton = document.getElementById('close-success');

      // Past assessments elements
      const togglePastAssessmentsButton = document.getElementById('toggle-past-assessments');
      const pastAssessmentsContainer = document.getElementById('past-assessments-container');
      const toggleText = document.getElementById('toggle-text');
      const toggleIcon = document.getElementById('toggle-icon');

      // Initialize button state right after DOM elements are defined
      startAssessmentButton.disabled = !careerInterestInput.value.trim();

      // Initial questions
      const initialQuestions = [
        "What subjects or topics do you enjoy learning about?",
        "What skills do you believe you're good at?",
        "Do you prefer working alone or in teams?"
      ];

      // Event Listeners
      careerInterestInput.addEventListener('input', () => {
        startAssessmentButton.disabled = !careerInterestInput.value.trim();
      });

      startAssessmentButton.addEventListener('click', () => {
        state.careerInterest = careerInterestInput.value.trim();
        state.allPages = [initialQuestions];
        state.allAnswers = [{}];
        state.currentPage = 0;
        state.currentAnswers = {};

        introPage.classList.add('hidden');
        questionnaireContainer.classList.remove('hidden');

        renderCurrentPage();
      });

      prevButton.addEventListener('click', () => {
        if (state.currentPage > 0) {
          // Save current answers before going back
          state.allAnswers[state.currentPage] = {
            ...state.currentAnswers
          };

          // Go to previous page
          state.currentPage--;
          state.currentAnswers = {
            ...state.allAnswers[state.currentPage]
          };

          renderCurrentPage();
        }
      });

      nextButton.addEventListener('click', async () => {
        // Save current page answers
        state.allAnswers[state.currentPage] = {
          ...state.currentAnswers
        };

        // If we're on the last page, complete the questionnaire
        if (state.currentPage === state.totalPages - 1) {
          completeQuestionnaire();
          return;
        }

        // If we already have questions for the next page, just navigate
        if (state.allPages.length > state.currentPage + 1) {
          state.currentPage++;
          state.currentAnswers = state.allAnswers[state.currentPage] || {};
          renderCurrentPage();
          return;
        }

        // Generate questions for the next page
        try {
          state.isLoading = true;
          renderLoadingState();

          // Flatten all answers so far into a format for the API
          const answersContext = [];
          for (let i = 0; i <= state.currentPage; i++) {
            const pageQuestions = state.allPages[i] || [];
            const pageAnswers = state.allAnswers[i] || {};

            pageQuestions.forEach(question => {
              const answer = pageAnswers[question];
              if (answer && answer.trim()) {
                answersContext.push(`Q: ${question}\nA: ${answer}`);
              }
            });
          }

          // Generate new questions based on previous answers
          const {
            questions,
            explanation
          } = await generateQuestionsBasedOnAnswers(
            state.careerInterest,
            answersContext,
            3 // 3 questions per page
          );

          // Add new questions to allPages
          state.allPages.push(questions);
          state.aiExplanation = explanation;

          // Move to next page
          state.currentPage++;
          state.currentAnswers = {};

          state.isLoading = false;
          renderCurrentPage();
        } catch (error) {
          console.error("Error generating questions:", error);

          // Fallback questions if generation fails
          const fallbackQuestions = [
            "What specific skills would you like to develop?",
            "What work environment do you prefer?",
            "What are your long-term career goals?"
          ];

          state.allPages.push(fallbackQuestions);
          state.aiExplanation = `These questions will help me understand your specific interests and goals in the ${state.careerInterest} field, allowing me to provide more tailored guidance.`;

          state.currentPage++;
          state.currentAnswers = {};

          state.isLoading = false;
          renderCurrentPage();
        }
      });

      newAssessmentButton.addEventListener('click', () => {
        resetAssessment();
      });

      saveResultsButton.addEventListener('click', () => {
        saveResults();
      });

      downloadResultsButton.addEventListener('click', () => {
        downloadResults();
      });

      closeSuccessButton.addEventListener('click', () => {
        successMessage.classList.add('hidden');
      });

      // Toggle past assessments
      if (togglePastAssessmentsButton) {
        togglePastAssessmentsButton.addEventListener('click', () => {
          pastAssessmentsContainer.classList.toggle('hidden');
          if (pastAssessmentsContainer.classList.contains('hidden')) {
            toggleText.textContent = 'Show';
            toggleIcon.classList.remove('fa-chevron-up');
            toggleIcon.classList.add('fa-chevron-down');
          } else {
            toggleText.textContent = 'Hide';
            toggleIcon.classList.remove('fa-chevron-down');
            toggleIcon.classList.add('fa-chevron-up');
          }
        });
      }

      // Add event listeners to view assessment buttons
      document.querySelectorAll('.view-assessment-btn').forEach(button => {
        button.addEventListener('click', () => {
          const results = button.getAttribute('data-results');
          const interest = button.getAttribute('data-interest');

          // Set state values
          state.results = results;
          state.careerInterest = interest;

          // Hide other pages and show results
          introPage.classList.add('hidden');
          questionnaireContainer.classList.add('hidden');
          processingPage.classList.add('hidden');

          // Format and display results
          resultsContent.innerHTML = formatText(results);
          resultsPage.classList.remove('hidden');

          // Hide save button since it's already saved
          saveResultsButton.classList.add('hidden');
        });
      });

      // Functions
      function renderCurrentPage() {
        // Update progress indicators
        currentPageText.textContent = state.currentPage + 1;
        progressBar.style.width = `${((state.currentPage + 1) / state.totalPages) * 100}%`;

        // Show/hide previous button
        if (state.currentPage > 0) {
          prevButton.classList.remove('hidden');
        } else {
          prevButton.classList.add('hidden');
        }

        // Update next button text
        nextButton.textContent = state.currentPage === state.totalPages - 1 ? 'Complete' : 'Next';

        // Show AI explanation if available
        if (state.aiExplanation && state.currentPage > 0) {
          aiExplanationContainer.classList.remove('hidden');
          aiExplanationText.textContent = state.aiExplanation;
        } else {
          aiExplanationContainer.classList.add('hidden');
        }

        // Render questions
        renderQuestions();

        // Check if current page is complete
        checkPageCompletion();
      }

      function renderQuestions() {
        questionsContainer.innerHTML = '';
        loadingContainer.classList.add('hidden');

        const currentQuestions = state.allPages[state.currentPage] || [];

        currentQuestions.forEach((question, index) => {
          const questionDiv = document.createElement('div');
          questionDiv.className = 'space-y-2';

          const label = document.createElement('label');
          label.setAttribute('for', `question-${index}`);
          label.className = 'label';
          label.textContent = question;

          const input = document.createElement('input');
          input.setAttribute('type', 'text');
          input.setAttribute('id', `question-${index}`);
          input.className = 'input';
          input.setAttribute('placeholder', 'Your answer');
          input.value = state.currentAnswers[question] || '';

          input.addEventListener('input', (e) => {
            state.currentAnswers[question] = e.target.value;
            checkPageCompletion();
          });

          questionDiv.appendChild(label);
          questionDiv.appendChild(input);
          questionsContainer.appendChild(questionDiv);
        });
      }

      function renderLoadingState() {
        questionsContainer.innerHTML = '';
        loadingContainer.classList.remove('hidden');
        nextButton.disabled = true;
      }

      function checkPageCompletion() {
        const currentQuestions = state.allPages[state.currentPage] || [];
        const isComplete = currentQuestions.every(question =>
          state.currentAnswers[question]?.trim().length > 0
        );

        nextButton.disabled = !isComplete;
      }

      async function completeQuestionnaire() {
        questionnaireContainer.classList.add('hidden');
        processingPage.classList.remove('hidden');

        try {
          // Format all answers for the API
          const formattedAnswers = [];
          for (let i = 0; i < state.allPages.length; i++) {
            const pageQuestions = state.allPages[i] || [];
            const pageAnswers = state.allAnswers[i] || {};

            pageQuestions.forEach(question => {
              const answer = pageAnswers[question];
              if (answer && answer.trim()) {
                formattedAnswers.push(`Q: ${question}\nA: ${answer}`);
              }
            });
          }

          // Create a comprehensive prompt for the AI
          const prompt = `I'm interested in a career in ${state.careerInterest}. Here are my answers to your assessment questions:

${formattedAnswers.join('\n\n')}

Based on this information, please provide:
1. A personalized career path recommendation
2. Key skills I should develop
3. Educational or training recommendations
4. Potential challenges I might face and how to overcome them
5. Next steps I should take

Please format your response with clear sections and bullet points where appropriate.`;

          // Call the Gemini API
          const response = await generateResponse(prompt);
          state.results = response;

          // Render results
          renderResults();
        } catch (error) {
          console.error("Error generating career assessment:", error);
          state.results = "I'm sorry, I encountered an error processing your assessment. Please try again later.";
          renderResults();
        }
      }

      function renderResults() {
        processingPage.classList.add('hidden');
        resultsPage.classList.remove('hidden');

        // Show save button for new assessments
        saveResultsButton.classList.remove('hidden');

        // Format and display results
        resultsContent.innerHTML = formatText(state.results);
      }

      function resetAssessment() {
        state.careerInterest = '';
        state.currentPage = 0;
        state.allPages = [];
        state.allAnswers = [];
        state.currentAnswers = {};
        state.aiExplanation = '';
        state.results = '';

        careerInterestInput.value = '';
        startAssessmentButton.disabled = true;

        resultsPage.classList.add('hidden');
        introPage.classList.remove('hidden');
      }

      async function saveResults() {
        try {
          // Instead of saving to database, we'll just show success message
          successMessageText.textContent = 'Your assessment results cannot be saved (database disabled).';
          successMessage.classList.remove('hidden');

          // For demonstration purposes only
          console.log("Assessment data (not saved to database):", {
            careerInterest: state.careerInterest,
            questions: state.allPages,
            answers: state.allAnswers,
            results: state.results
          });
        } catch (error) {
          console.error('Error:', error);
          successMessageText.textContent = 'Feature disabled: database saving removed.';
          successMessage.classList.remove('hidden');
        }
      }

      function downloadResults() {
        const filename = `Career_Assessment_${new Date().toISOString().split('T')[0]}.txt`;
        const text = `Career Assessment Results for ${state.careerInterest}\n\n${state.results}`;

        const element = document.createElement('a');
        element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
        element.setAttribute('download', filename);
        element.style.display = 'none';

        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
      }

      // Helper function to format text with markdown-like syntax
      function formatText(text) {
        if (!text) return '';

        // Replace **bold** with <strong>bold</strong>
        text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

        // Replace *italic* with <em>italic</em>
        text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');

        // Replace # Heading with <h3>Heading</h3>
        text = text.replace(/^# (.*?)$/gm, '<h3 class="text-lg font-bold my-3">$1</h3>');

        // Replace ## Subheading with <h4>Subheading</h4>
        text = text.replace(/^## (.*?)$/gm, '<h4 class="text-md font-semibold my-2">$1</h4>');

        // Convert bullet lists
        const bulletListRegex = /^[*\-•] (.*?)$/gm;
        if (text.match(bulletListRegex)) {
          // Find all bullet list sections
          const sections = text.split(/\n\n+/);

          for (let i = 0; i < sections.length; i++) {
            if (sections[i].match(bulletListRegex)) {
              const items = sections[i].match(bulletListRegex).map(item =>
                item.replace(bulletListRegex, '<li class="ml-5 mb-1">$1</li>')
              );
              sections[i] = `<ul class="list-disc my-3">${items.join('')}</ul>`;
            }
          }

          text = sections.join('\n\n');
        }

        // Convert numbered lists
        const numberedListRegex = /^\d+\. (.*?)$/gm;
        if (text.match(numberedListRegex)) {
          // Find all numbered list sections
          const sections = text.split(/\n\n+/);

          for (let i = 0; i < sections.length; i++) {
            if (sections[i].match(numberedListRegex)) {
              const items = sections[i].match(numberedListRegex).map(item =>
                item.replace(numberedListRegex, '<li class="ml-5 mb-1">$1</li>')
              );
              sections[i] = `<ol class="list-decimal my-3">${items.join('')}</ol>`;
            }
          }

          text = sections.join('\n\n');
        }

        // Replace newlines with <br> tags
        text = text.replace(/\n\n+/g, '</p><p class="my-2">');
        text = `<p class="my-2">${text}</p>`;

        return text;
      }

      // API Functions
      async function generateQuestionsBasedOnAnswers(careerInterest, previousAnswers, questionCount = 3) {
        // Create a prompt for generating contextual questions
        const prompt = `You are a career guidance AI assistant helping a user interested in ${careerInterest}.

The user has already answered these questions:
${previousAnswers.join('\n\n')}

Based on these answers, generate ${questionCount} new questions that will help you better understand their career goals, skills, and preferences. The questions should:
1. Be specific and directly related to their previous answers
2. Dig deeper into areas they've mentioned
3. Help clarify any ambiguities in their responses
4. Be open-ended to encourage detailed responses
5. Be conversational and friendly in tone

Also provide a brief explanation of why you're asking these specific questions and how they relate to the user's previous answers.

Format your response as a JSON object with two fields:
1. "questions": An array of ${questionCount} questions
2. "explanation": A brief paragraph explaining why you're asking these questions

Example format:
{
  "questions": [
    "Question 1?",
    "Question 2?",
    "Question 3?"
  ],
  "explanation": "Based on your interest in X and experience with Y, I'd like to understand more about Z..."
}

Do not include any text outside of this JSON object.`;

        try {
          const text = await callGeminiAPI(prompt, {
            temperature: 0.7,
            topP: 0.9,
            topK: 40,
            maxOutputTokens: 1024
          });

          // Try to parse the JSON response
          try {
            // Find JSON object in the response
            const jsonMatch = text.match(/\{[\s\S]*\}/);
            if (jsonMatch) {
              const parsedResponse = JSON.parse(jsonMatch[0]);
              if (
                parsedResponse.questions &&
                Array.isArray(parsedResponse.questions) &&
                parsedResponse.questions.length > 0 &&
                parsedResponse.explanation
              ) {
                return {
                  questions: parsedResponse.questions,
                  explanation: parsedResponse.explanation,
                };
              }
            }
          } catch (parseError) {
            console.error("Error parsing questions:", parseError);
          }

          // Fallback questions if parsing fails
          return {
            questions: [
              `What specific skills related to ${careerInterest} would you like to develop?`,
              `What work environment do you prefer in the ${careerInterest} field?`,
              `What are your long-term career goals in ${careerInterest}?`,
            ],
            explanation: `These questions will help me understand your specific interests and goals in the ${careerInterest} field, allowing me to provide more tailored guidance.`,
          };
        } catch (error) {
          console.error("API Error:", error);
          throw error;
        }
      }

      async function generateResponse(prompt) {
        try {
          return await callGeminiAPI(prompt, {
            temperature: 0.7,
            topP: 0.8,
            topK: 40,
            maxOutputTokens: 2048
          });
        } catch (error) {
          console.error("API Error:", error);
          throw error;
        }
      }

      // Reusable function for API calls with retry logic
      async function callGeminiAPI(prompt, config, retries = 2) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 30000); // 30 second timeout

        try {
          for (let attempt = 0; attempt <= retries; attempt++) {
            try {
              const response = await fetch('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' + state.apiKey, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                  contents: [{
                    parts: [{
                      text: prompt
                    }]
                  }],
                  generationConfig: config
                }),
                signal: controller.signal
              });

              const data = await response.json();

              // Clear timeout since request completed
              clearTimeout(timeoutId);

              if (!response.ok) {
                throw new Error(data.error?.message || `API returned status ${response.status}`);
              }

              if (data.error) {
                throw new Error(data.error.message || "Unknown API Error");
              }

              if (!data.candidates || !data.candidates[0]?.content?.parts?.[0]?.text) {
                throw new Error("Invalid response format from API");
              }

              return data.candidates[0].content.parts[0].text;
            } catch (err) {
              // Only retry on network errors or 5xx server errors
              if (attempt < retries && (err.name === 'AbortError' ||
                  err.name === 'TypeError' ||
                  err.message.includes('status 5'))) {
                console.warn(`API call failed, retrying (${attempt + 1}/${retries})...`, err);
                // Exponential backoff: wait 1s, then 2s, then 4s, etc.
                await new Promise(r => setTimeout(r, 1000 * Math.pow(2, attempt)));
                continue;
              }
              throw err;
            }
          }
        } finally {
          clearTimeout(timeoutId);
        }
      }
    </script>
  </div>
</body>

</html>