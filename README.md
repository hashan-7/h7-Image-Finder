# h7 Image Finder 🔍

<p align="center">
  <strong>Your ultimate tool for discovering and downloading high-quality images. This web application provides a powerful, unified search experience across multiple APIs.</strong>
</p>

---

## 🚀 Core Features

* **🌐 Multi-API Search:** Simultaneously search for images using both the **Google Custom Search API** and the **Pexels API** to get a wide variety of results.
* **📥 Flexible Downloading:** Download images in their original, high-quality format with a single click.
* **✂️ Dynamic Resizing:** Need a specific size? Resize images to a custom width before downloading, while automatically preserving the aspect ratio.
* **📱 Fully Responsive Interface:** Built with Bootstrap 5, the user interface provides a seamless experience on desktops, tablets, and mobile devices.
* **🛡️ Secure by Design:** Your sensitive API keys are kept safe and out of version control in a separate `config.php` file, which is ignored by Git.
* **📄 Easy Pagination:** Effortlessly navigate through multiple pages of search results.

---

## 🛠️ Technology Stack

This project is built with a reliable and powerful set of technologies:

* **Backend:** PHP
* **API Communication:** cURL
* **Image Processing:** GD Library
* **Frontend:** HTML5, CSS3, JavaScript
* **UI Framework:** Bootstrap 5
* **APIs:** Google Custom Search & Pexels API

---

## ⚙️ Getting Started

To get a local copy up and running, please follow these steps carefully.

### Prerequisites

* A local web server environment like [XAMPP](https://www.apachefriends.org/index.html) or [WAMP](https://www.wampserver.com/en/).
* [Composer](https://getcomposer.org/) (optional, but recommended for managing dependencies).

### Installation & Configuration

1.  **Clone the Repository** into your web server's root directory (e.g., `htdocs` for XAMPP, `www` for WAMP):
    ```sh
    git clone [https://github.com/YOUR_USERNAME/h7-image-finder.git](https://github.com/YOUR_USERNAME/h7-image-finder.git)
    cd h7-image-finder
    ```
    *(Remember to replace `YOUR_USERNAME` with your actual GitHub username)*

2.  **(Optional) Install PHP Dependencies:**
    If the project uses libraries like `phpdotenv`, run Composer to install them.
    ```sh
    composer install
    ```

3.  **Create and Configure `config.php`:**

    **⚠️ This is the most important step.** This file is intentionally ignored by Git to protect your secret API keys. You must create it manually.

    * In the project's root directory, create a new file named `config.php`.
    * Copy the code below into this new file and **replace the placeholder values** with your actual credentials.

    ```php
    <?php

    // Pexels API Key
    define('PEXELS_API_KEY', 'YOUR_PEXELS_API_KEY');

    // Google Custom Search API Credentials
    define('GOOGLE_API_KEY', 'YOUR_GOOGLE_API_KEY');
    define('GOOGLE_CX', 'YOUR_GOOGLE_CUSTOM_SEARCH_ENGINE_ID');

    ?>
    ```

4.  **Access the Application:**
    Start your WAMP/XAMPP server. Open your web browser and navigate to `http://localhost/h7-image-finder/`.

---

## 📄 License

This project is licensed under the MIT License.

---

<p align="center">
  Made with ❤️ by h7
</p>
