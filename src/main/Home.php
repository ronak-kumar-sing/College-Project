<?php
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

// --- Ensure career_assessments table exists ---
// Moved this block outside the AJAX handler to run on every page load
try {
    $createTableSql = "CREATE TABLE IF NOT EXISTS career_assessments (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        career_interest VARCHAR(255) NOT NULL,
        skills TEXT NOT NULL, /* Storing JSON string of skills array */
        results TEXT, /* Storing the full AI result text */
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )";

    if ($conn->query($createTableSql) !== TRUE) {
        error_log('Error checking/creating assessment history table: ' . $conn->error);
    }
} catch (Exception $e) {
    error_log("Database error during table creation check: " . $e->getMessage());
}
// --- End Table Creation Check ---

// --- AJAX Handler for Saving Assessment to Session ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_results']) && isset($_POST['career_interest']) && isset($_POST['skills'])) {
    header('Content-Type: application/json'); // Ensure JSON response

    try {
        // Skills should be a JSON string representing an array
        $skills_json = $_POST['skills'];
        $skills_array = json_decode($skills_json, true);

        // Validate the decoded skills
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($skills_array)) {
            error_log("Invalid skills JSON received. Raw data: " . $_POST['skills']);
            throw new Exception('Invalid skills format received from client.');
        }

        // Ensure skills are strings and clean them up
        $cleaned_skills = array_map(function($skill) {
            return is_string($skill) ? trim($skill) : ''; // Trim strings, handle non-strings
        }, $skills_array);
        $cleaned_skills = array_filter($cleaned_skills); // Remove empty elements

        // Store the cleaned array in the session
        $_SESSION['assessment_results'] = [
            'career_interest' => $_POST['career_interest'],
            'skills' => $cleaned_skills, // Store the cleaned PHP array
            'timestamp' => time()
        ];

        // Save to database for history
        $career_interest = $_POST['career_interest'];
        $skills_to_save_json = json_encode($cleaned_skills);

        // Insert into database
        $sql = "INSERT INTO career_assessments (user_id, career_interest, skills, results) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            throw new Exception('Prepare failed: ' . $conn->error);
        }

        // Get the full results text if sent, otherwise use empty string
        $full_results_text = $_POST['full_results'] ?? '';

        if (!$stmt->bind_param("isss", $user_id, $career_interest, $skills_to_save_json, $full_results_text)) {
            $stmt->close();
            throw new Exception('Binding parameters failed: ' . $stmt->error);
        }

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception('Execute failed: ' . $error);
        }

        $stmt->close();

        echo json_encode(['success' => true]);
        exit;

    } catch (Exception $e) {
        http_response_code(400); // Bad request
        error_log("Assessment save error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}
// --- End AJAX Handler ---

// --- Ensure 'skills' and 'results' columns exist before fetching history ---
try {
    // Check and add 'skills' column if missing
    $checkSkillsSql = "SHOW COLUMNS FROM `career_assessments` LIKE 'skills'";
    $resultSkills = $conn->query($checkSkillsSql);
    if ($resultSkills && $resultSkills->num_rows == 0) {
        $alterSkillsSql = "ALTER TABLE `career_assessments` ADD COLUMN `skills` TEXT NOT NULL AFTER `career_interest`";
        if (!$conn->query($alterSkillsSql)) {
            error_log("Error adding skills column: " . $conn->error);
        }
    }

    // Check and add 'results' column if missing
    $checkResultsSql = "SHOW COLUMNS FROM `career_assessments` LIKE 'results'";
    $resultResults = $conn->query($checkResultsSql);
    if ($resultResults && $resultResults->num_rows == 0) {
        $alterResultsSql = "ALTER TABLE `career_assessments` ADD COLUMN `results` TEXT NULL AFTER `skills`";
        if (!$conn->query($alterResultsSql)) {
            error_log("Error adding results column: " . $conn->error);
        }
    }
} catch (mysqli_sql_exception $e) {
    error_log("Error during column check for career_assessments: " . $e->getMessage());
}
// --- End column check ---

// Fetch assessment history from database
$assessmentHistory = [];
$sql = "SELECT id, career_interest, skills, results, created_at FROM career_assessments
        WHERE user_id = ? ORDER BY created_at DESC LIMIT 10";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $skills_decoded = json_decode($row['skills'], true);
        $row['skills_array'] = is_array($skills_decoded) ? $skills_decoded : [];
        $assessmentHistory[] = $row;
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Career Assessment Questionnaire</title>
    <script src="https://cdn.tailwindcss.com"></script>
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
                        <a href="../auth/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
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

        <div id="intro-page" class="card mb-8">
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
                    <button id="start-assessment" class="btn btn-primary" disabled>Start Assessment</button>
                </div>
            </div>
        </div>

        <div class="card mb-8">
            <div class="p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-xl font-bold">Assessment History</h2>
                    <button id="toggle-history" class="text-sm text-primary hover:text-blue-700 flex items-center">
                        <span id="toggle-text">Show</span>
                        <i class="fas fa-chevron-down text-xs ml-1" id="toggle-icon"></i>
                    </button>
                </div>

                <div id="history-container" class="space-y-3 hidden">
                    <?php if (empty($assessmentHistory)): ?>
                        <p class="text-gray-500 text-sm italic">No assessment history found. Complete an assessment to see it here.</p>
                    <?php else: ?>
                        <?php foreach ($assessmentHistory as $assessment): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <h3 class="font-medium text-lg"><?php echo htmlspecialchars($assessment["career_interest"]); ?></h3>
                                        <p class="text-sm text-gray-500 mt-1">
                                            <?php echo date("F j, Y, g:i a", strtotime($assessment["created_at"])); ?>
                                        </p>

                                        <?php if (!empty($assessment['skills_array'])): ?>
                                            <div class="mt-2">
                                                <div class="flex flex-wrap gap-1 mt-1">
                                                    <?php foreach ($assessment['skills_array'] as $skill): ?>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                            <?php echo htmlspecialchars($skill); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="flex space-x-2">
                                        <button class="view-assessment-btn text-sm px-3 py-1 bg-primary text-white rounded-md hover:bg-blue-600"
                                            data-id="<?php echo $assessment["id"]; ?>"
                                            data-interest="<?php echo htmlspecialchars($assessment["career_interest"]); ?>"
                                            data-results="<?php echo htmlspecialchars($assessment["results"] ?? 'No detailed results available.'); ?>">
                                            View
                                        </button>
                                        <button class="use-assessment-btn text-sm px-3 py-1 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200"
                                            data-id="<?php echo $assessment["id"]; ?>"
                                            data-interest="<?php echo htmlspecialchars($assessment["career_interest"]); ?>"
                                            data-skills='<?php echo htmlspecialchars($assessment["skills"]); ?>'>
                                            Use for Jobs
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div id="questionnaire-container" class="card hidden">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">Career Assessment</h2>
                    <div class="text-sm text-gray-600">Page <span id="current-page">1</span> of <span id="total-pages">3</span>
                    </div>
                </div>

                <div class="w-full bg-gray-200 h-2 rounded-full mb-6">
                    <div id="progress-bar" class="bg-primary h-full rounded-full transition-all duration-300" style="width: 33%">
                    </div>
                </div>

                <div id="ai-explanation-container" class="bg-blue-50 p-4 rounded-lg mb-6 hidden">
                    <h4 class="text-sm font-medium mb-2">Why we're asking these questions:</h4>
                    <p id="ai-explanation" class="text-sm text-gray-600"></p>
                </div>

                <div id="questions-container" class="space-y-6 mb-6">
                </div>

                <div id="loading-container" class="py-8 text-center hidden">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-primary mb-4"></div>
                    <p class="text-gray-600">Generating personalized questions based on your answers...</p>
                </div>

                <div class="flex justify-between">
                    <button id="prev-button" class="btn btn-outline hidden">Previous</button>
                    <div class="flex-1"></div>
                    <button id="next-button" class="btn btn-primary" disabled>Next</button>
                </div>
            </div>
        </div>

        <div id="processing-page" class="card hidden">
            <div class="p-8 text-center">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-primary mb-6"></div>
                <h2 class="text-2xl font-bold mb-2">Analyzing Your Responses</h2>
                <p class="text-gray-600 max-w-md mx-auto">
                    Our AI is analyzing your answers to create personalized career guidance tailored to your unique profile.
                </p>
            </div>
        </div>

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

        <div id="success-message" class="fixed inset-0 flex items-center justify-center z-50 hidden">
            <div class="absolute inset-0 bg-black opacity-50"></div>
            <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4 z-10">
                <div class="flex items-center justify-center text-green-500 mb-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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
            const state = {
                apiKey: 'AIzaSyBasaBU3srwcOqVQoyT7uZmtXPa4NRi6gU',
                careerInterest: '',
                currentPage: 0,
                totalPages: 3,
                allPages: [],
                allAnswers: [],
                currentAnswers: {},
                aiExplanation: '',
                results: '',
                extractedSkills: [],
                isLoading: false,
                userId: <?php echo $user_id; ?>,
                userName: "<?php echo htmlspecialchars($fullname); ?>"
            };

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

            const toggleHistoryButton = document.getElementById('toggle-history');
            const historyContainer = document.getElementById('history-container');
            const toggleText = document.getElementById('toggle-text');
            const toggleIcon = document.getElementById('toggle-icon');

            startAssessmentButton.disabled = true;

            const initialQuestions = [
                {
                    question: "What subjects or topics do you enjoy learning about the most?",
                    options: ["Technology & Programming", "Science & Research", "Arts & Design", "Business & Finance", "Helping Others"]
                },
                {
                    question: "Which skills do you believe you are naturally good at?",
                    options: ["Problem Solving", "Creativity", "Communication", "Organization", "Technical Aptitude"]
                },
                {
                    question: "How do you prefer to work?",
                    options: ["Mostly alone", "In small teams", "In large groups", "A mix depending on the task"]
                }
            ];

            careerInterestInput.addEventListener('input', () => {
                startAssessmentButton.disabled = !careerInterestInput.value.trim();
            });

            startAssessmentButton.addEventListener('click', () => {
                if (startAssessmentButton.disabled) return;

                state.careerInterest = careerInterestInput.value.trim();
                state.allPages = [initialQuestions];
                state.allAnswers = [{}];
                state.currentPage = 0;
                state.currentAnswers = {};

                introPage.classList.add('hidden');
                questionnaireContainer.classList.remove('hidden');

                renderCurrentPage();
            });

            toggleHistoryButton.addEventListener('click', () => {
                historyContainer.classList.toggle('hidden');
                if (historyContainer.classList.contains('hidden')) {
                    toggleText.textContent = 'Show';
                    toggleIcon.classList.remove('fa-chevron-up');
                    toggleIcon.classList.add('fa-chevron-down');
                } else {
                    toggleText.textContent = 'Hide';
                    toggleIcon.classList.remove('fa-chevron-down');
                    toggleIcon.classList.add('fa-chevron-up');
                }
            });

            document.querySelectorAll('.view-assessment-btn').forEach(button => {
                button.addEventListener('click', () => {
                    const interest = button.getAttribute('data-interest');
                    const resultsText = button.getAttribute('data-results');

                    state.results = resultsText;
                    state.careerInterest = interest;
                    state.extractedSkills = [];

                    introPage.classList.add('hidden');
                    questionnaireContainer.classList.add('hidden');
                    processingPage.classList.add('hidden');

                    resultsContent.innerHTML = formatText(state.results);
                    resultsPage.classList.remove('hidden');
                    saveResultsButton.classList.add('hidden');
                });
            });

            document.querySelectorAll('.use-assessment-btn').forEach(button => {
                button.addEventListener('click', async () => {
                    const interest = button.getAttribute('data-interest');
                    const skillsJsonString = button.getAttribute('data-skills');

                    try {
                        const response = await fetch('Home.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `save_results=true&career_interest=${encodeURIComponent(interest)}&skills=${encodeURIComponent(skillsJsonString)}&full_results=`
                        });

                        const data = await response.json();

                        if (data.success) {
                            successMessageText.textContent = 'Assessment loaded for job recommendations. Redirecting to jobs page...';
                            successMessage.classList.remove('hidden');

                            setTimeout(() => {
                                window.location.href = 'Jobsections.php';
                            }, 1500);
                        } else {
                            alert('Error: ' + (data.message || 'Could not load assessment for jobs'));
                        }
                    } catch (error) {
                        console.error('Error using assessment for jobs:', error);
                        alert('Could not load assessment for jobs');
                    }
                });
            });

            prevButton.addEventListener('click', () => {
                if (state.currentPage > 0) {
                    state.allAnswers[state.currentPage] = {...state.currentAnswers};
                    state.currentPage--;
                    state.currentAnswers = {...state.allAnswers[state.currentPage]};
                    renderCurrentPage();
                }
            });

            nextButton.addEventListener('click', async () => {
                state.allAnswers[state.currentPage] = {...state.currentAnswers};

                if (state.currentPage === state.totalPages - 1) {
                    completeQuestionnaire();
                    return;
                }

                if (state.allPages.length > state.currentPage + 1) {
                    state.currentPage++;
                    state.currentAnswers = state.allAnswers[state.currentPage] || {};
                    renderCurrentPage();
                    return;
                }

                try {
                    state.isLoading = true;
                    renderLoadingState();

                    const answersContext = [];
                    for (let i = 0; i <= state.currentPage; i++) {
                        const pageQuestions = state.allPages[i] || [];
                        const pageAnswers = state.allAnswers[i] || {};

                        pageQuestions.forEach(qData => {
                            const answer = pageAnswers[qData.question];
                            if (answer && answer.trim()) {
                                answersContext.push(`Q: ${qData.question}\nA: ${answer}`);
                            }
                        });
                    }

                    const { questions, explanation } = await generateQuestionsBasedOnAnswers(
                        state.careerInterest,
                        answersContext,
                        3
                    );

                    state.allPages.push(questions);
                    state.aiExplanation = explanation;
                    state.currentPage++;
                    state.currentAnswers = {};
                    state.isLoading = false;
                    renderCurrentPage();
                } catch (error) {
                    console.error("Error generating questions:", error);

                    const fallbackQuestions = [
                        {
                            question: `What specific skills related to ${state.careerInterest} would you like to develop most?`,
                            options: ["Technical Skill A", "Technical Skill B", "Soft Skill C", "Not Sure Yet"]
                        },
                        {
                            question: `What kind of work environment do you prefer in the ${state.careerInterest} field?`,
                            options: ["Fast-paced startup", "Large established company", "Collaborative team", "Independent work", "Remote"]
                        },
                        {
                            question: `What are your primary long-term career goals in ${state.careerInterest}?`,
                            options: ["Leadership/Management", "Technical Specialization", "Starting my own venture", "Work-life balance", "Still exploring"]
                        }
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
                const viewJobsBtn = document.querySelector('#success-message .btn-primary.mt-4');
                if (viewJobsBtn) {
                    viewJobsBtn.remove();
                }
            });

            function renderCurrentPage() {
                currentPageText.textContent = state.currentPage + 1;
                progressBar.style.width = `${((state.currentPage + 1) / state.totalPages) * 100}%`;

                if (state.currentPage > 0) {
                    prevButton.classList.remove('hidden');
                } else {
                    prevButton.classList.add('hidden');
                }

                nextButton.textContent = state.currentPage === state.totalPages - 1 ? 'Complete' : 'Next';

                if (state.aiExplanation && state.currentPage > 0) {
                    aiExplanationContainer.classList.remove('hidden');
                    aiExplanationText.textContent = state.aiExplanation;
                } else {
                    aiExplanationContainer.classList.add('hidden');
                }

                renderQuestions();
                checkPageCompletion();
            }

            function renderQuestions() {
                questionsContainer.innerHTML = '';
                loadingContainer.classList.add('hidden');

                const currentQuestions = state.allPages[state.currentPage] || [];

                currentQuestions.forEach((qData, questionIndex) => {
                    const questionDiv = document.createElement('div');
                    questionDiv.className = 'space-y-3 border border-gray-200 p-4 rounded-md';

                    const label = document.createElement('label');
                    label.className = 'label font-semibold';
                    label.textContent = qData.question;
                    questionDiv.appendChild(label);

                    const optionsContainer = document.createElement('div');
                    optionsContainer.className = 'space-y-2';

                    qData.options.forEach((option, optionIndex) => {
                        const optionId = `q${questionIndex}-option${optionIndex}`;

                        const optionDiv = document.createElement('div');
                        optionDiv.className = 'flex items-center';

                        const radioInput = document.createElement('input');
                        radioInput.setAttribute('type', 'radio');
                        radioInput.setAttribute('id', optionId);
                        radioInput.setAttribute('name', `question-${questionIndex}`);
                        radioInput.setAttribute('value', option);
                        radioInput.className = 'h-4 w-4 text-primary border-gray-300 focus:ring-primary mr-2';

                        if (state.currentAnswers[qData.question] === option) {
                            radioInput.checked = true;
                        }

                        radioInput.addEventListener('change', (e) => {
                            if (e.target.checked) {
                                state.currentAnswers[qData.question] = e.target.value;
                                checkPageCompletion();
                            }
                        });

                        const optionLabel = document.createElement('label');
                        optionLabel.setAttribute('for', optionId);
                        optionLabel.className = 'text-sm text-gray-700';
                        optionLabel.textContent = option;

                        optionDiv.appendChild(radioInput);
                        optionDiv.appendChild(optionLabel);
                        optionsContainer.appendChild(optionDiv);
                    });

                    questionDiv.appendChild(optionsContainer);
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
                const isComplete = currentQuestions.every(qData =>
                    state.currentAnswers[qData.question] !== undefined && state.currentAnswers[qData.question] !== null
                );

                nextButton.disabled = !isComplete;
            }

            async function completeQuestionnaire() {
                questionnaireContainer.classList.add('hidden');
                processingPage.classList.remove('hidden');

                try {
                    const formattedAnswers = [];
                    for (let i = 0; i < state.allPages.length; i++) {
                        const pageQuestions = state.allPages[i] || [];
                        const pageAnswers = state.allAnswers[i] || {};

                        pageQuestions.forEach(qData => {
                            const answer = pageAnswers[qData.question];
                            if (answer !== undefined && answer !== null) {
                                formattedAnswers.push(`Q: ${qData.question}\nA: ${answer}`);
                            }
                        });
                    }

                    const prompt = `I'm interested in a career in ${state.careerInterest}. Here are my answers to your assessment questions:

${formattedAnswers.join('\n\n')}

Based on this information, please provide:
1. A personalized career path recommendation
2. Key skills I should develop (list 5-10 specific, actionable skills as bullet points or a numbered list under a clear heading like "Key Skills to Develop")
3. Educational or training recommendations
4. Potential challenges I might face and how to overcome them
5. Next steps I should take

Please format your response with clear sections and bullet points where appropriate. Ensure the skills list is clearly identifiable.`;

                    const response = await generateResponse(prompt);
                    state.results = response;
                    state.extractedSkills = extractSkillsFromResults(state.results);
                    renderResults();
                } catch (error) {
                    console.error("Error generating career assessment:", error);
                    state.results = "I'm sorry, I encountered an error processing your assessment. Please try again later.";
                    state.extractedSkills = [];
                    renderResults();
                }
            }

            function renderResults() {
                processingPage.classList.add('hidden');
                resultsPage.classList.remove('hidden');
                saveResultsButton.classList.remove('hidden');
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
                state.extractedSkills = [];

                careerInterestInput.value = '';
                startAssessmentButton.disabled = true;

                resultsPage.classList.add('hidden');
                introPage.classList.remove('hidden');
            }

            function extractSkillsFromResults(resultsText) {
                if (!resultsText) return [];

                const skills = [];

                const skillsSectionRegex = /(?:Key Skills|Skills to Develop|Skills I should develop|Recommended Skills|Focus on developing|Key Skills to Develop)[:\s]*([\s\S]*?)(?:\n\n|\n##|\n#|$)/i;
                const match = resultsText.match(skillsSectionRegex);

                if (match && match[1]) {
                    const skillListText = match[1];
                    const potentialSkills = skillListText.split(/\n\s*(?:[*•\-–]|\d+[\.\)])\s*/);

                    potentialSkills.forEach(skill => {
                        let cleanedSkill = skill.trim();

                        if (cleanedSkill.length < 3) return;

                        cleanedSkill = cleanedSkill.replace(/\([^)]*\)/g, '').trim();
                        cleanedSkill = cleanedSkill.replace(/:[^,]*(?:,|$)/, '').trim();
                        cleanedSkill = cleanedSkill.replace(/[,.;:]$/, '').trim();
                        cleanedSkill = cleanedSkill.replace(/\b(to develop|skill to|should develop)\b/gi, '').trim();
                        cleanedSkill = cleanedSkill.replace(/\b(proficiency in|knowledge of|ability to|understanding of|learn|master|develop)\b/gi, '').trim();
                        cleanedSkill = cleanedSkill.replace(/^[-*•]/, '').trim();
                        cleanedSkill = cleanedSkill.replace(/^[-\s]+|[-\s]+$/g, '').trim();

                        if (cleanedSkill &&
                            cleanedSkill.length > 2 &&
                            cleanedSkill.length < 50 &&
                            /[a-zA-Z]/.test(cleanedSkill) &&
                            !/^(key|skill|develop|focus)/i.test(cleanedSkill) &&
                            !/\b(such as|including|for example|e\.g\.|i\.e\.)\b/i.test(cleanedSkill)) {

                            if (!skills.includes(cleanedSkill)) {
                                skills.push(cleanedSkill);
                            }
                        }
                    });
                } else {
                    const skillMatches = resultsText.match(/\b(?:JavaScript|Python|React|Angular|SQL|HTML|CSS|Node\.js|AWS|Docker|Git|machine learning|data analysis|leadership|communication|problem[\- ]solving|project management|UI\/UX|design|marketing|sales|teamwork|critical thinking|creativity)\b/gi);

                    if (skillMatches) {
                        const uniqueSkills = [...new Set(skillMatches.map(s => s.trim()))];
                        skills.push(...uniqueSkills);
                    }
                }

                return skills;
            }

            async function saveResults() {
                try {
                    state.extractedSkills = extractSkillsFromResults(state.results);

                    if (state.extractedSkills.length === 0) {
                        state.extractedSkills = ["General " + state.careerInterest + " skills"];
                    }

                    saveResultsButton.disabled = true;
                    saveResultsButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Saving...';

                    const skillsJson = JSON.stringify(state.extractedSkills);
                    const fullResultsText = state.results;

                    const response = await fetch('Home.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `save_results=true&career_interest=${encodeURIComponent(state.careerInterest)}&skills=${encodeURIComponent(skillsJson)}&full_results=${encodeURIComponent(fullResultsText)}`
                    });

                    if (!response.ok) {
                        let errorData;
                        try {
                            errorData = await response.json();
                        } catch (e) {
                            const text = await response.text();
                            throw new Error(text || `HTTP error! status: ${response.status}`);
                        }
                        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
                    }

                    const data = await response.json();

                    if (data.success) {
                        showSuccessMessage('Your assessment results have been saved. You can now view personalized job recommendations.');

                        const existingViewJobsBtn = document.querySelector('#success-message .view-jobs-btn');
                        if (!existingViewJobsBtn) {
                            const viewJobsBtn = document.createElement('button');
                            viewJobsBtn.className = 'btn btn-primary mt-4 view-jobs-btn';
                            viewJobsBtn.textContent = 'View Recommended Jobs';
                            viewJobsBtn.onclick = () => {
                                window.location.href = 'Jobsections.php';
                            };

                            const closeButtonContainer = document.querySelector('#success-message .flex.justify-center');
                            if (closeButtonContainer) {
                                closeButtonContainer.before(viewJobsBtn);
                            }
                        }

                        saveResultsButton.innerHTML = '<i class="fas fa-check mr-1"></i> Saved';
                        saveResultsButton.classList.add('opacity-70', 'cursor-not-allowed');
                    } else {
                        throw new Error(data.message || 'Failed to save results');
                    }
                } catch (error) {
                    const cleanErrorMessage = error.message.replace(/<[^>]*>/g, '').trim();
                    showSuccessMessage('There was an error saving your results: ' + cleanErrorMessage, true);

                    saveResultsButton.disabled = false;
                    saveResultsButton.innerHTML = '<i class="fas fa-save mr-1"></i> Save Results';
                    saveResultsButton.classList.remove('opacity-70', 'cursor-not-allowed');
                }
            }

            function downloadResults() {
                const filename = `Career_Assessment_${state.careerInterest.replace(/\s+/g, '_')}_${new Date().toISOString().split('T')[0]}.txt`;
                const text = `Career Assessment Results for: ${state.careerInterest}\n\n${state.results}`;

                const element = document.createElement('a');
                element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
                element.setAttribute('download', filename);
                element.style.display = 'none';

                document.body.appendChild(element);
                element.click();
                document.body.removeChild(element);
            }

            function formatText(text) {
                if (!text) return '';

                let formattedText = text;

                const codeBlocks = [];
                formattedText = formattedText.replace(/```([\s\S]*?)```/g, (match, code) => {
                    codeBlocks.push(`<pre class="bg-gray-100 p-2 rounded text-sm overflow-x-auto"><code>${htmlspecialchars(code.trim())}</code></pre>`);
                    return `%%CODEBLOCK_${codeBlocks.length - 1}%%`;
                });

                formattedText = formattedText.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                formattedText = formattedText.replace(/\*(.*?)\*/g, '<em>$1</em>');
                formattedText = formattedText.replace(/^# (.*?)$/gm, '<h3 class="text-lg font-bold my-3">$1</h3>');
                formattedText = formattedText.replace(/^## (.*?)$/gm, '<h4 class="text-md font-semibold my-2">$1</h4>');

                formattedText = formattedText.replace(/^([ \t]*)[*\-•] (.*?)$/gm, (match, indent, item) => {
                    const padding = indent.length * 10;
                    return `<li style="margin-left: ${padding}px;" class="mb-1 list-disc list-inside">${item}</li>`;
                });
                formattedText = formattedText.replace(/(<li.*?>.*?<\/li>\s*)+/gs, (match) => {
                    if (match.trim().startsWith('<ul') || match.trim().startsWith('<ol')) return match;
                    return `<ul class="my-3">${match}</ul>`;
                });

                formattedText = formattedText.replace(/^([ \t]*)\d+\. (.*?)$/gm, (match, indent, item) => {
                    const padding = indent.length * 10;
                    return `<li style="margin-left: ${padding}px;" class="mb-1 list-decimal list-inside">${item}</li>`;
                });
                formattedText = formattedText.replace(/(<li.*?>.*?<\/li>\s*)+/gs, (match) => {
                    if (match.trim().startsWith('<ul') || match.trim().startsWith('<ol')) return match;
                    return `<ol class="my-3">${match}</ol>`;
                });

                formattedText = formattedText.split('\n\n').map(paragraph => {
                    if (paragraph.trim().startsWith('<') || paragraph.trim() === '') {
                        return paragraph;
                    }
                    if (paragraph.trim().startsWith('<ul') || paragraph.trim().startsWith('<ol')) {
                        return paragraph;
                    }
                    return `<p class="my-2">${paragraph.replace(/\n/g, '<br>')}</p>`;
                }).join('');

                formattedText = formattedText.replace(/%%CODEBLOCK_(\d+)%%/g, (match, index) => {
                    return codeBlocks[parseInt(index)];
                });

                function htmlspecialchars(str) {
                    if (typeof str !== 'string') return '';
                    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
                    return str.replace(/[&<>"']/g, m => map[m]);
                }

                return formattedText;
            }

            async function generateQuestionsBasedOnAnswers(careerInterest, previousAnswers, questionCount = 3) {
                const prompt = `You are a career guidance AI assistant helping a user interested in ${careerInterest}.

The user has already answered these questions:
${previousAnswers.join('\n\n')}

Based on these answers, generate ${questionCount} new multiple-choice questions that will help you better understand their career goals, skills, and preferences. For each question:
1. Make it specific and directly related to their previous answers.
2. Provide 3-5 relevant and distinct options.
3. Ensure one option allows for "Other" or "None of the above" if appropriate.
4. Be conversational and friendly in tone.

Also provide a brief explanation of why you're asking these specific questions and how they relate to the user's previous answers.

Format your response as a JSON object with two fields:
1. "questions": An array of objects, where each object has a "question" (string) and "options" (array of strings).
2. "explanation": A brief paragraph explaining why you're asking these questions.

Example format:
{
  "questions": [
    {
      "question": "Regarding your interest in [Previous Answer Topic], which aspect excites you most?",
      "options": ["Option A", "Option B", "Option C", "Other"]
    },
    {
      "question": "You mentioned [Skill]. How would you rate your proficiency?",
      "options": ["Beginner", "Intermediate", "Advanced", "Expert"]
    }
  ],
  "explanation": "Based on your interest in X and experience with Y, I'd like to understand more about Z..."
}

Do not include any text outside of this JSON object.`;

                try {
                    const text = await callGeminiAPI(prompt, {
                        temperature: 0.7,
                        topP: 0.9,
                        topK: 40,
                        maxOutputTokens: 1536
                    });

                    try {
                        const jsonMatch = text.match(/\{[\s\S]*\}/);
                        if (jsonMatch) {
                            const parsedResponse = JSON.parse(jsonMatch[0]);
                            if (
                                parsedResponse.questions &&
                                Array.isArray(parsedResponse.questions) &&
                                parsedResponse.questions.length > 0 &&
                                parsedResponse.questions.every(q => q.question && Array.isArray(q.options) && q.options.length > 1) &&
                                parsedResponse.explanation
                            ) {
                                return {
                                    questions: parsedResponse.questions,
                                    explanation: parsedResponse.explanation,
                                };
                            } else {
                                console.warn("Parsed response structure is invalid:", parsedResponse);
                            }
                        } else {
                            console.warn("No JSON object found in API response:", text);
                        }
                    } catch (parseError) {
                        console.error("Error parsing questions JSON:", parseError, "Response text:", text);
                    }

                    console.log("Using fallback questions.");
                    return {
                        questions: [
                            { question: `What specific skills related to ${careerInterest} would you like to develop most?`, options: ["Technical Skill A", "Technical Skill B", "Soft Skill C", "Not Sure Yet"] },
                            { question: `What kind of work environment do you prefer in the ${careerInterest} field?`, options: ["Fast-paced startup", "Large established company", "Collaborative team", "Independent work", "Remote"] },
                            { question: `What are your primary long-term career goals in ${careerInterest}?`, options: ["Leadership/Management", "Technical Specialization", "Starting my own venture", "Work-life balance", "Still exploring"] },
                        ],
                        explanation: `These questions will help me understand your specific interests and goals in the ${careerInterest} field, allowing me to provide more tailored guidance.`,
                    };
                } catch (error) {
                    console.error("API Error generating questions:", error);
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

            async function callGeminiAPI(prompt, config, retries = 2) {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 30000);

                try {
                    for (let attempt = 0; attempt <= retries; attempt++) {
                        try {
                            const response = await fetch('https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' + state.apiKey, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    contents: [{ parts: [{ text: prompt }] }],
                                    generationConfig: config
                                }),
                                signal: controller.signal
                            });

                            clearTimeout(timeoutId);

                            if (!response.ok) {
                                let errorData = {};
                                try {
                                    errorData = await response.json();
                                } catch (e) { }
                                throw new Error(errorData.error?.message || `API returned status ${response.status}`);
                            }

                            const data = await response.json();

                            if (data.error) {
                                throw new Error(data.error.message || "Unknown API Error");
                            }

                            if (!data.candidates || !data.candidates[0]?.content?.parts?.[0]?.text) {
                                if (data.candidates && data.candidates[0]?.finishReason === 'SAFETY') {
                                     throw new Error("Content blocked due to safety concerns.");
                                }
                                throw new Error("Invalid response format from API");
                            }

                            return data.candidates[0].content.parts[0].text;
                        } catch (err) {
                            clearTimeout(timeoutId);

                            if (attempt < retries && (err.name === 'AbortError' || err.name === 'TypeError' || err.message.includes('status 5'))) {
                                console.warn(`API call failed, retrying (${attempt + 1}/${retries})...`, err);
                                await new Promise(r => setTimeout(r, 1000 * Math.pow(2, attempt)));
                                const newTimeoutId = setTimeout(() => controller.abort(), 30000);
                                timeoutId = newTimeoutId;
                                continue;
                            }
                            throw err;
                        }
                    }
                } finally {
                    clearTimeout(timeoutId);
                }
                 throw new Error("API call failed after multiple retries.");
            }

            function showSuccessMessage(message, isError = false) {
                successMessageText.textContent = message;
                const iconContainer = successMessage.querySelector('.flex.items-center.justify-center');
                const titleElement = successMessage.querySelector('h3');

                iconContainer.classList.remove('text-green-500', 'text-red-500');
                iconContainer.innerHTML = '';

                if (isError) {
                    iconContainer.classList.add('text-red-500');
                    iconContainer.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>`;
                    titleElement.textContent = 'Error!';
                } else {
                    iconContainer.classList.add('text-green-500');
                    iconContainer.innerHTML = `
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>`;
                    titleElement.textContent = 'Success!';
                }
                successMessage.classList.remove('hidden');
            }
        </script>
    </div>
</body>

</html>