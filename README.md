# CareerCompass - Career Development Platform

## Project Overview

CareerCompass is a comprehensive career development platform designed to help users navigate their professional journey. The platform offers career assessment tools, personalized roadmap generation, job listings, and application tracking in one integrated solution.

<div style="width: 10%;">

<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path fill="#74C0FC" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm50.7-186.9L162.4 380.6c-19.4 7.5-38.5-11.6-31-31l55.5-144.3c3.3-8.5 9.9-15.1 18.4-18.4l144.3-55.5c19.4-7.5 38.5 11.6 31 31L325.1 306.7c-3.2 8.5-9.9 15.1-18.4 18.4zM288 256a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"/></svg>
</div>

Link of Site:- http://careercompassss.infinityfreeapp.com/


## Features

### User Authentication
- Secure signup and login system
- Password hashing for security
- Session management

### Career Assessment
- Interactive questionnaire to identify career interests and strengths
- AI-powered analysis of responses
- Personalized career recommendations
- Save and review past assessments

### AI Roadmap Generator
- Create customized learning and career development roadmaps
- Tailored to user's goals, skills, and timeframe
- Step-by-step guidance with resource recommendations
- Save and download roadmaps for future reference

### Job Listings
- Browse trending jobs across various industries
- Search and filter functionality
- Save jobs to bookmarks
- Apply directly through the platform
- Post your own job listings

### User Profile
- View and update personal information
- Change password securely
- Track job applications, saved jobs, and posted jobs
- View activity statistics

## Technologies Used

- **Frontend**: HTML, CSS, JavaScript, Tailwind CSS
- **Backend**: PHP, MySQL
- **AI Integration**: Google Gemini API
- **Server**: XAMPP (Apache, MySQL)

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
   - Extract the files to the `htdocs` folder in your XAMPP installation directory
     - Windows: `C:\xampp\htdocs\careercompass`
     - macOS: `/Applications/XAMPP/htdocs/careercompass`
     - Linux: `/opt/lampp/htdocs/careercompass`

4. **Create the Database**
   - Open your web browser and navigate to `http://localhost/phpmyadmin`
   - Create a new database named `careercompass`
   - The application will automatically create the necessary tables on first run

5. **Configure Database Connection**
   - Open `config.php` in the project root directory
   - Update the database connection details if needed:
     \`\`\`php
     $servername = "localhost";
     $username = "root";  // Default XAMPP username
     $password = "";      // Default XAMPP password is empty
     $dbname = "careercompass";
     \`\`\`

6. **API Configuration (Optional)**
   - For AI features to work, you need a Google Gemini API key
   - Replace the placeholder API key in the following files:
     - `src/app/main/Home.php`
     - `src/app/main/AiRoadmap.php`
   - Look for: `apiKey: 'YOUR_API_KEY_HERE'`

7. **Access the Application**
   - Open your web browser and navigate to `http://localhost/careercompass`
   - The application should now be running

## Usage Guide

### First-Time Setup

1. **Create an Account**
   - Click "Sign Up" on the homepage
   - Fill in your details and create an account
   - Verify your email (if implemented)

2. **Login**
   - Use your email and password to log in
   - You'll be redirected to the dashboard

### Career Assessment

1. Navigate to the "Career Assessment" section
2. Enter your career interest and start the assessment
3. Answer all questions thoughtfully
4. Review your personalized career recommendations
5. Save or download your results

### Roadmap Generator

1. Navigate to the "Roadmap Generator" section
2. Enter your career goal, current skills, and timeframe
3. Generate your personalized roadmap
4. Review the step-by-step guidance and resources
5. Save or download your roadmap

### Job Listings

1. Navigate to the "Jobs" section
2. Browse available jobs or use search/filters
3. Save interesting jobs to your bookmarks
4. Apply directly through the platform
5. Post your own job listings (if applicable)

### Profile Management

1. Click on your name in the navigation bar
2. Select "Profile" from the dropdown
3. Update your personal information
4. Change your password if needed
5. View your activity (applications, saved jobs, posted jobs)

## Project Structure

\`\`\`
careercompass/
├── config.php                 # Database configuration
├── index.php                  # Main entry point
├── login.php                  # User login
├── logout.php                 # User logout
├── profile.php                # User profile management
├── signup.php                 # User registration
├── src/
│   └── app/
│       └── main/
│           ├── Home.php       # Career assessment
│           ├── AiRoadmap.php  # Roadmap generator
│           └── Jobsections.php # Job listings
\`\`\`

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Ensure MySQL service is running in XAMPP
   - Verify database credentials in `config.php`
   - Check if the `careercompass` database exists

2. **404 Page Not Found**
   - Ensure the project is in the correct directory
   - Check if Apache service is running
   - Verify URL path is correct

3. **API Features Not Working**
   - Verify your API key is correctly set
   - Check internet connection
   - Look for error messages in the browser console

### Getting Help

If you encounter any issues not covered here, please:
1. Check the error logs in XAMPP (`logs` directory)
2. Search for similar issues online
3. Contact the project maintainer

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgments

- Tailwind CSS for the UI components
- Google Gemini API for AI capabilities
- XAMPP for the local development environment
- Font Awesome for icons

---

© 2025 CareerCompass. All rights reserved.
