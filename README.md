# CareerCompass - Career Development Platform

## Project Overview

CareerCompass is a comprehensive career development platform designed to help users navigate their professional journey. The platform offers career assessment tools, personalized roadmap generation, job listings with skill matching, application tracking, and role-based features in one integrated solution.

<div style="width: 10%;">

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#74C0FC" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm50.7-186.9L162.4 380.6c-19.4 7.5-38.5-11.6-31-31l55.5-144.3c3.3-8.5 9.9-15.1 18.4-18.4l144.3-55.5c19.4-7.5 38.5 11.6 31 31L325.1 306.7c-3.2 8.5-9.9 15.1-18.4 18.4zM288 256a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>
</div>

Link of Site:- http://careercompassss.infinityfreeapp.com/


## Features

### User Authentication
- Secure signup and login system
- Password hashing for security
- Session management
- Role-based access (User, HR, Admin)

### Career Assessment (`Home.php`)
- Interactive questionnaire to identify career interests and strengths
- AI-powered analysis of responses using Google Gemini
- Personalized career recommendations and skill identification
- Save and review past assessment history

### AI Roadmap Generator (`AiRoadmap.php`)
- Create customized learning and career development roadmaps using AI
- Tailored to user's goals, current skills, interests, and timeframe
- Step-by-step guidance with resource recommendations
- Save, view history, and download roadmaps for future reference

### Job Listings (`Jobsections.php`)
- Browse job listings from the database
- **Skill Matching**: Jobs are sorted based on match with user's assessed skills
- Search and filter functionality (keyword, location, job type)
- **Job Application**: Apply directly through the platform with resume upload
- **Job Bookmarking**: Save jobs for later viewing
- **Job Posting**: Users with 'HR' or 'Admin' roles can post new job listings
- View application history and saved job history
- View jobs posted by the user (for HR/Admin)

### User Profile (`profile.php` - *not provided, inferred*)
- View and update personal information
- Change password securely
- Track job applications, saved jobs, and assessment/roadmap history

## Technologies Used

- **Frontend**: HTML, CSS, JavaScript, Tailwind CSS
- **Backend**: PHP, MySQL
- **AI Integration**: Google Gemini API (via REST calls)
- **Server**: XAMPP (Apache, MySQL) or any PHP/MySQL compatible server

## Installation on XAMPP

### Prerequisites
- [XAMPP](https://www.apachefriends.org/download.html) installed on your system
- Web browser (Chrome, Firefox, etc.)
- Text editor or IDE (VS Code, PHPStorm, etc.)

### Installation Steps

1. **Download and Install XAMPP**
   - Download XAMPP from [https://www.apachefriends.org/download.html](https://www.apachefriends.org/download.html)
   - Install XAMPP following the installation wizard instructions

2. **Start XAMPP Services**
   - Open XAMPP Control Panel
   - Start Apache and MySQL services by clicking the "Start" buttons

3. **Clone or Download the Project**
   - Clone this repository or download the ZIP file
   - Extract the files to the `htdocs` folder in your XAMPP installation directory, naming the project folder (e.g., `College-Project`)
     - Windows: `C:\xampp\htdocs\College-Project`
     - macOS: `/Applications/XAMPP/xamppfiles/htdocs/College-Project`
     - Linux: `/opt/lampp/htdocs/College-Project`

4. **Create the Database**
   - Open your web browser and navigate to `http://localhost/phpmyadmin`
   - Create a new database named `careercompass` (or match the name in `config.php`)
   - The application will automatically create the necessary tables (`users`, `career_assessments`, `career_roadmaps`, `job_listings`, `job_applications`, `job_bookmarks`) on first run or when features requiring them are accessed.

5. **Configure Database Connection**
   - Open `src/auth/config.php`
   - Update the database connection details if needed (defaults are usually fine for XAMPP):
     ```php
     // filepath: /Applications/XAMPP/xamppfiles/htdocs/College-Project/src/auth/config.php
     $servername = "localhost";
     $username = "root";  // Default XAMPP username
     $password = "";      // Default XAMPP password is empty
     $dbname = "careercompass"; // Make sure this matches the database you created
     ```

6. **API Configuration**
   - For AI features (Assessment Analysis, Roadmap Generation) to work, you need a Google Gemini API key.
   - Get a key from [Google AI Studio](https://aistudio.google.com/app/apikey).
   - Replace the placeholder API key `AIzaSyBasaBU3srwcOqVQoyT7uZmtXPa4NRi6gU` in the following files:
     - `src/main/Home.php` (JavaScript section)
     - `src/main/AiRoadmap.php` (JavaScript section)
   - Look for the line: `apiKey: 'AIzaSyBasaBU3srwcOqVQoyT7uZmtXPa4NRi6gU', // Replace this with your actual API key`

7. **Access the Application**
   - Open your web browser and navigate to `http://localhost/College-Project/` (or the folder name you used)
   - The application should now be running. You might be redirected to the login page (`src/auth/login.php`).

## Usage Guide

### First-Time Setup

1. **Create an Account**
   - Navigate to the signup page (likely linked from the login page).
   - Fill in your details and create an account.

2. **Login**
   - Use your email and password to log in.
   - You'll be redirected to the main dashboard or assessment page (`src/main/Home.php`).

### Career Assessment

1. Navigate to the "Career Assessment" section (`Home.php`).
2. Enter your career interest and start the assessment.
3. Answer the multi-page questionnaire.
4. Review your personalized career recommendations generated by the AI.
5. Save your results to view later in the "Assessment History" section and enable personalized job recommendations.

### Roadmap Generator

1. Navigate to the "Roadmap Generator" section (`AiRoadmap.php`).
2. Enter your career goal, current skills (optional), interests (optional), and timeframe (optional).
3. Generate your personalized roadmap using AI.
4. Review the step-by-step guidance and resources.
5. Save your roadmap to view later in the "Roadmap History" section.

### Job Listings

1. Navigate to the "Job Listings" section (`Jobsections.php`).
2. If you have saved assessment results, jobs will be sorted by relevance to your skills.
3. Browse available jobs or use search/filters.
4. Save interesting jobs to your bookmarks (viewable in "Saved Jobs" history).
5. Click "Apply" or "Easy Apply" to open the application modal.
6. Upload your resume and optionally add a cover letter to apply.
7. View your past applications in the "Application History" section.
8. If your role is 'HR' or 'Admin', use the "Post a Job" button to add new listings.

### Profile Management

1. Click on your name in the navigation bar.
2. Select "Profile" from the dropdown (navigates to `src/auth/profile.php`).
3. Update your personal information.
4. Change your password if needed.

## Project Structure

```plaintext
careercompass/
├── src/
│   └── auth/
│       ├── config.php          # Database configuration
│       ├── login.php           # User login
│       ├── logout.php          # User logout
│       ├── profile.php         # User profile management
│       ├── signup.php          # User registration
│   └── main/
│       ├── Home.php            # Career assessment
│       ├── AiRoadmap.php       # Roadmap generator
│       ├── Jobsections.php     # Job listings
├── index.php                   # Main entry point
```

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Ensure MySQL service is running in XAMPP.
   - Verify database credentials in `config.php`.
   - Check if the `careercompass` database exists.

2. **404 Page Not Found**
   - Ensure the project is in the correct directory.
   - Check if Apache service is running.
   - Verify URL path is correct.

3. **API Features Not Working**
   - Verify your API key is correctly set.
   - Check internet connection.
   - Look for error messages in the browser console.

### Getting Help

If you encounter any issues not covered here, please:
1. Check the error logs in XAMPP (`logs` directory).
2. Search for similar issues online.
3. Contact the project maintainer.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- Tailwind CSS for the UI components
- Google Gemini API for AI capabilities
- XAMPP for the local development environment
- Font Awesome for icons

---

© 2025 CareerCompass. All rights reserved.
