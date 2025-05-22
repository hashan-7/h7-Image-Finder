h7 Image Finder

Welcome to h7 Image Finder, a dynamic web application designed to help you effortlessly search for and download high-quality images from various sources. This project leverages powerful external APIs to provide a seamless image discovery experience.

Overview
h7 Image Finder acts as a user-friendly interface for querying image databases. It allows users to enter search terms, browse through results, and download images, with options for resizing. The application is built with a focus on clean code, responsive design, and secure handling of API credentials.

Features
Comprehensive Image Search: Search for images using keywords, powered by both Google Custom Search API and Pexels API.

Image Download: Download desired images directly from the application.

Dynamic Resizing: Option to download images at a specified width, with automatic aspect ratio preservation.

Responsive Design: A user interface that adapts gracefully to various screen sizes, from mobile devices to desktops.

Secure API Key Handling: API keys are stored in a separate, ignored configuration file (config.php) to prevent accidental exposure in version control.

Technologies Used
Backend: PHP

cURL: For making HTTP requests to external APIs.

GD Library: For image processing (resizing and format handling).

Frontend:

HTML5

CSS3 (with Bootstrap 5 for responsive styling)

JavaScript

Dependency Management: Composer (for phpdotenv library, if used for local environment variables)

Version Control: Git

Setup Instructions
To get this project up and running on your local machine, follow these steps:

Clone the Repository:
First, clone this repository to your local machine.

git clone https://github.com/YourGitHubUsername/h7-image-finder.git
cd h7-image-finder


(Replace YourGitHubUsername with your actual GitHub username and h7-image-finder with your repository name if it's different).

Install Composer (if not already installed):
If you don't have Composer, download and install it from getcomposer.org.

Install PHP Dependencies:
Navigate to the project's root directory in your terminal and run Composer to install the necessary PHP libraries (like phpdotenv for local environment variable loading).

composer install


This will create a vendor/ directory. This directory is automatically ignored by Git.

Configure API Keys:
You need to set up your API keys for Google Custom Search and Pexels.

Create config.php: In the root directory of your project, create a new file named config.php.

Add Your Keys: Open config.php and add your actual API keys using define() statements.

<?php
// config.php

// Get your Google Custom Search API Key from Google Cloud Console
// and your Programmable Search Engine ID (CX ID) from Programmable Search Engine.
define('GOOGLE_API_KEY', 'YOUR_ACTUAL_GOOGLE_API_KEY');
define('GOOGLE_CX_ID', 'YOUR_ACTUAL_GOOGLE_CX_ID');

// Get your Pexels API Key from pexels.com/api/
define('PEXELS_API_KEY', 'YOUR_ACTUAL_PEXELS_API_KEY');

?>


IMPORTANT: Replace YOUR_ACTUAL_... placeholders with your real API keys.
This config.php file is in your .gitignore and will NOT be uploaded to GitHub.

Set up a Local Web Server:
Ensure you have a local PHP development environment set up (e.g., XAMPP, WAMP, MAMP, or a Docker container). Place the entire h7-image-finder project folder in your web server's document root (e.g., htdocs for XAMPP).

Access the Application:
Open your web browser and navigate to the URL where your local server hosts the project (e.g., http://localhost/h7-image-finder/ or http://localhost/).

Usage
Search for Images: Enter a descriptive keyword or phrase into the search bar and click "Search."

Browse Results: Images from Google and Pexels will be displayed in a grid.

View Details & Download: Click on an image to view a larger preview. From the preview, you can choose to download the original image or a resized version.

Pagination: Use the "Previous" and "Next" buttons to navigate through search results.

This project is proudly presented under h7.