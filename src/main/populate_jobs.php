<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
set_time_limit(120); // Increase execution time limit if needed

echo "<pre>"; // Use preformatted text for better output readability

// Include database configuration
require_once "../auth/config.php";

// Include the sample job data
require_once "configJob.php"; // Contains the $sampleJobs array

// Ensure the job_listings table exists (optional, but good practice)
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
)";

if ($conn->query($sql) !== TRUE) {
  die("Error creating job_listings table: " . $conn->error);
} else {
  echo "Checked/Created job_listings table successfully.\n";
}


// Prepare the insert statement
$sql = "INSERT INTO job_listings (user_id, title, company, logo, location, job_type, salary, description, skills, created_at, expires_at, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);

if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}

$insertedCount = 0;
$skippedCount = 0;

// Loop through the sample jobs and insert them
foreach ($sampleJobs as $job) {
    // Basic check to avoid inserting duplicates based on title and company (optional)
    $checkSql = "SELECT id FROM job_listings WHERE title = ? AND company = ? LIMIT 1";
    $checkStmt = $conn->prepare($checkSql);
    if ($checkStmt) {
        $checkStmt->bind_param("ss", $job['title'], $job['company']);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            echo "Skipping duplicate: " . htmlspecialchars($job['title']) . " at " . htmlspecialchars($job['company']) . "\n";
            $skippedCount++;
            $checkStmt->close();
            continue; // Skip to the next job
        }
        $checkStmt->close();
    } else {
        echo "Warning: Could not prepare duplicate check statement: " . $conn->error . "\n";
    }


    // Bind parameters
    // Ensure all keys exist, provide defaults if necessary
    $userId = $job['user_id'] ?? 1; // Default to user 1 if not set
    $title = $job['title'] ?? 'N/A';
    $company = $job['company'] ?? 'N/A';
    $logo = $job['logo'] ?? null;
    $location = $job['location'] ?? 'N/A';
    $jobType = $job['job_type'] ?? 'N/A';
    $salary = $job['salary'] ?? null;
    $description = $job['description'] ?? '';
    $skills = $job['skills'] ?? '[]'; // Keep as JSON string
    $createdAt = $job['created_at'] ?? date('Y-m-d H:i:s');
    $expiresAt = $job['expires_at'] ?? null;
    $isActive = $job['is_active'] ?? 1;

    $stmt->bind_param(
        "issssssssssi", // i, s, s, s, s, s, s, s, s, s, s, i
        $userId,
        $title,
        $company,
        $logo,
        $location,
        $jobType,
        $salary,
        $description,
        $skills,
        $createdAt,
        $expiresAt,
        $isActive
    );

    // Execute the statement
    if ($stmt->execute()) {
        echo "Inserted: " . htmlspecialchars($title) . " at " . htmlspecialchars($company) . "\n";
        $insertedCount++;
    } else {
        echo "Error inserting " . htmlspecialchars($title) . ": " . $stmt->error . "\n";
    }
}

// Close statement and connection
$stmt->close();
$conn->close();

echo "\n------------------------------------\n";
echo "Job Population Complete.\n";
echo "Inserted: " . $insertedCount . " jobs.\n";
echo "Skipped (duplicates): " . $skippedCount . " jobs.\n";
echo "</pre>";

?>