<?php

// --- Enable Error Reporting for Debugging ---
// !!! IMPORTANT: Remove or comment out these two lines before deploying to a live server !!!
error_reporting(E_ALL);
ini_set('display_errors', 1);
// --- End Error Reporting ---

// --- Configuration ---
require_once 'config.php'; // <-- Include config.php

// --- Request Routing ---
$action = $_GET['action'] ?? null;

// --- API Search Request Handling ---
if ($action === 'search' && isset($_GET['query'])) {
    // ... (Search logic - No changes from V15/V13) ...
    header('Content-Type: application/json'); header('Cache-Control: no-cache, must-revalidate'); header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    $output_data = [ 'items' => [], 'searchInfo' => ['totalResults' => 0, 'startIndex' => 1], 'error' => null ]; $query = trim($_GET['query']); $startIndex = isset($_GET['start']) ? max(1, (int)$_GET['start']) : 1; $output_data['searchInfo']['startIndex'] = $startIndex;
    if (empty($query)) { http_response_code(400); $output_data['error'] = 'Search query cannot be empty.'; echo json_encode($output_data); exit; } if (!defined('GOOGLE_API_KEY') || !defined('GOOGLE_CX_ID') || !defined('PEXELS_API_KEY') || empty(GOOGLE_API_KEY) || empty(GOOGLE_CX_ID) || empty(PEXELS_API_KEY)) { http_response_code(500); $output_data['error'] = 'API Keys/IDs are not configured correctly in config.php or config.php is missing.'; echo json_encode($output_data); exit; }
    function makeApiRequest($url, $headers = []) { $ch = curl_init(); if ($ch === false) { return ['error' => 'Failed to initialize cURL session.', 'http_code' => 500, 'body' => null]; } curl_setopt($ch, CURLOPT_URL, $url); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_TIMEOUT, 15); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); if (!empty($headers)) { curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); } $response_body = curl_exec($ch); $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $curl_error_num = curl_errno($ch); $curl_error_msg = curl_error($ch); curl_close($ch); if ($curl_error_num > 0) { return ['error' => 'cURL Error (' . $curl_error_num . '): ' . $curl_error_msg, 'http_code' => 500, 'body' => null]; } return ['error' => null, 'http_code' => $http_code, 'body' => $response_body]; }
    $google_results = []; $google_total_results = 0; $google_error = null; $google_api_url = 'https://www.googleapis.com/customsearch/v1'; $google_params = [ 'key' => GOOGLE_API_KEY, 'cx' => GOOGLE_CX_ID, 'q' => $query, 'searchType' => 'image', 'num' => 10, 'start' => $startIndex ]; $google_request_url = $google_api_url . '?' . http_build_query($google_params); $google_response = makeApiRequest($google_request_url);
    if ($google_response['error']) { $google_error = $google_response['error']; } elseif ($google_response['http_code'] === 200) { $google_data = json_decode($google_response['body'], true); if (json_last_error() === JSON_ERROR_NONE) { if (isset($google_data['error'])) { $google_error = 'Google API Error: ' . ($google_data['error']['message'] ?? 'Unknown'); } elseif (isset($google_data['items'])) { $google_total_results = $google_data['searchInformation']['totalResults'] ?? 0; foreach ($google_data['items'] as $item) { if (isset($item['link']) && isset($item['image']['thumbnailLink'])) { $google_results[] = [ 'id' => 'g_' . ($item['cacheId'] ?? uniqid()), 'title' => $item['title'] ?? '', 'image_url' => $item['link'], 'thumbnail_url' => $item['image']['thumbnailLink'], 'context_url' => $item['image']['contextLink'] ?? '#', 'source' => 'Google', 'photographer' => $item['displayLink'] ?? '' ]; } } } } else { $google_error = 'Invalid JSON response from Google API.'; } } else { $google_error = 'Google API request failed with HTTP status ' . $google_response['http_code']; }
    $pexels_results = []; $pexels_error = null; if ($startIndex === 1) { $pexels_api_url = 'https://api.pexels.com/v1/search'; $pexels_params = ['query' => $query, 'per_page' => 15, 'page' => 1]; $pexels_request_url = $pexels_api_url . '?' . http_build_query($pexels_params); $pexels_headers = ['Authorization: ' . PEXELS_API_KEY]; $pexels_response = makeApiRequest($pexels_request_url, $pexels_headers); if ($pexels_response['error']) { $pexels_error = $pexels_response['error']; } elseif ($pexels_response['http_code'] === 200) { $pexels_data = json_decode($pexels_response['body'], true); if (json_last_error() === JSON_ERROR_NONE) { if (isset($pexels_data['error'])) { $pexels_error = 'Pexels API Error: ' . $pexels_data['error']; } elseif (isset($pexels_data['photos'])) { foreach ($pexels_data['photos'] as $photo) { if (isset($photo['src']['medium']) && isset($photo['src']['original'])) { $pexels_results[] = [ 'id' => 'p_' . $photo['id'], 'title' => $photo['alt'] ?? ('Photo by ' . $photo['photographer']), 'image_url' => $photo['src']['original'], 'thumbnail_url' => $photo['src']['medium'], 'context_url' => $photo['url'] ?? '#', 'source' => 'Pexels', 'photographer' => $photo['photographer'] ?? '' ]; } } } } else { $pexels_error = 'Invalid JSON response from Pexels API.'; } } else { $pexels_error = 'Pexels API request failed with HTTP status ' . $pexels_response['http_code']; } }
    $output_data['items'] = array_merge($google_results, $pexels_results); $output_data['searchInfo']['totalResults'] = $google_total_results; $output_data['searchInfo']['sourcesAttempted'] = ($startIndex === 1) ? ['Google', 'Pexels'] : ['Google']; $combined_error = null; if ($google_error && $pexels_error) { $combined_error = "Multiple API Errors: [Google: " . $google_error . "] [Pexels: " . $pexels_error . "]"; http_response_code(500); } elseif ($google_error) { $combined_error = $google_error; if($google_response['http_code'] !== 200) http_response_code($google_response['http_code']); else http_response_code(500); } elseif ($pexels_error) { $combined_error = $pexels_error; if($pexels_response['http_code'] !== 200) http_response_code($pexels_response['http_code']); else http_response_code(500); } elseif (empty($output_data['items']) && $startIndex === 1) { $combined_error = null; } elseif (empty($output_data['items']) && $startIndex > 1 && !$google_error) { $combined_error = "Failed to fetch Google results for page."; http_response_code(500); } $output_data['error'] = $combined_error;
    echo json_encode($output_data); exit;

}
// --- Image Download Request Handling (REVISED with SVG Check) ---
elseif ($action === 'download' && isset($_GET['url'])) {

    // Function to gracefully handle errors and exit
    function downloadError($message, $httpCode = 500) {
        if (!headers_sent()) { header("Content-Type: text/plain"); http_response_code($httpCode); }
        die("Download Error: " . $message);
    }

    // Check GD library (needed for non-SVG)
    if (!extension_loaded('gd')) { downloadError('GD image processing library is not enabled on the server.', 500); }

    $imageUrl = trim($_GET['url']);
    $requestedWidth = isset($_GET['width']) ? $_GET['width'] : 'original';

    // Validate URL
    if (empty($imageUrl) || !filter_var($imageUrl, FILTER_VALIDATE_URL)) { downloadError('Invalid Image URL provided.', 400); }
    // Validate width
    $targetWidth = null; if ($requestedWidth !== 'original') { if (!ctype_digit($requestedWidth) || (int)$requestedWidth <= 0) { downloadError('Invalid width parameter provided.', 400); } $targetWidth = (int)$requestedWidth; }

    // --- Fetch the image content using cURL ---
    $ch = curl_init(); if ($ch === false) { downloadError('Failed to initialize cURL session for download.', 500); }
    curl_setopt($ch, CURLOPT_URL, $imageUrl); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); curl_setopt($ch, CURLOPT_TIMEOUT, 30); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $imageContent = curl_exec($ch); $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE); $curl_error_num = curl_errno($ch); $curl_error_msg = curl_error($ch); curl_close($ch);

    // Check for errors during image fetch
    if ($curl_error_num > 0) { downloadError('cURL Error fetching image (' . $curl_error_num . '): ' . $curl_error_msg, 500); }
    if ($http_code !== 200) { downloadError('Failed to download image. Source server returned HTTP ' . $http_code, $http_code); }
    if (empty($imageContent)) { downloadError('Downloaded image content is empty.', 500); }

    if ($contentType && strpos(strtolower($contentType), 'image/svg') !== false) {

        $filename = basename(parse_url($imageUrl, PHP_URL_PATH));
        if (empty($filename)) { $filename = 'downloaded_image'; }
        $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', pathinfo($filename, PATHINFO_FILENAME)); // Sanitize
        // Ensure .svg extension
        if (strtolower(pathinfo($filename, PATHINFO_EXTENSION)) !== 'svg') {
             $filename .= '.svg';
        }

        // Send SVG headers and content
        header('Content-Type: image/svg+xml');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($imageContent));
        header('Cache-Control: private');
        echo $imageContent;
        exit; 
    }


    $sourceImage = imagecreatefromstring($imageContent);
    if ($sourceImage === false) { $gd_error = error_get_last(); downloadError('Failed to process image data with GD library. Unsupported format or corrupt data. ' . ($gd_error['message'] ?? ''), 500); }

    $sourceWidth = imagesx($sourceImage); $sourceHeight = imagesy($sourceImage);
    $outputImageResource = $sourceImage; $didResize = false;

    // Resize Image if requested
    if ($targetWidth !== null && $targetWidth < $sourceWidth) {
        $aspectRatio = $sourceHeight / $sourceWidth; $targetHeight = floor($targetWidth * $aspectRatio);
        $resizedCanvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if ($resizedCanvas === false) { imagedestroy($sourceImage); downloadError('GD Error: Failed to create true color image canvas.', 500); }
        $isPng = (strpos($contentType, 'image/png') !== false); $isGif = (strpos($contentType, 'image/gif') !== false);
        if ($isPng) { imagealphablending($resizedCanvas, false); imagesavealpha($resizedCanvas, true); $transparent = imagecolorallocatealpha($resizedCanvas, 255, 255, 255, 127); imagefilledrectangle($resizedCanvas, 0, 0, $targetWidth, $targetHeight, $transparent); }
        elseif ($isGif) { $transparent_index = imagecolortransparent($sourceImage); if ($transparent_index >= 0) { $transparent_color = imagecolorsforindex($sourceImage, $transparent_index); $transparent_new = imagecolorallocate($resizedCanvas, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']); if ($transparent_new !== false) { imagefill($resizedCanvas, 0, 0, $transparent_new); imagecolortransparent($resizedCanvas, $transparent_new); } } }
        if (imagecopyresampled($resizedCanvas, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight)) { imagedestroy($sourceImage); $outputImageResource = $resizedCanvas; $didResize = true; }
        else { imagedestroy($resizedCanvas); $didResize = false; /* Use original - already in $outputImageResource */ }
    }

    // Prepare Filename and Output
    $outputFormat = 'jpeg'; $extension = 'jpg';
    $filename = basename(parse_url($imageUrl, PHP_URL_PATH)); if (empty($filename)) { $filename = 'downloaded_image'; }
    $filename = preg_replace('/[^a-zA-Z0-9_.-]/', '_', pathinfo($filename, PATHINFO_FILENAME));
    if ($isPng) { $outputFormat = 'png'; $extension = 'png'; } elseif ($isGif && !$didResize) { $outputFormat = 'gif'; $extension = 'gif'; }
    $final_filename = $filename; if ($didResize && isset($targetWidth)) { $final_filename .= '_' . $targetWidth . 'px'; } $final_filename .= '.' . $extension;

    // Send Headers and Output Image
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Disposition: attachment; filename="' . $final_filename . '"'); header('Cache-Control: private');
    ob_start(); $outputSuccess = false;
    if ($outputFormat === 'png') { header('Content-Type: image/png'); $outputSuccess = imagepng($outputImageResource); }
    elseif ($outputFormat === 'gif') { header('Content-Type: image/gif'); $outputSuccess = imagegif($outputImageResource); }
    else { header('Content-Type: image/jpeg'); $outputSuccess = imagejpeg($outputImageResource, null, 90); }
    $imageData = ob_get_clean();
    imagedestroy($outputImageResource);

    if ($outputSuccess && !empty($imageData)) { header('Content-Length: ' . strlen($imageData)); echo $imageData; }
    else { downloadError('GD Error: Failed to generate final image output.', 500); }
    exit;

} // End of download action handling

?>
<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>h7 image finder - Image Search</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { padding-top: 1rem; background-color: #f8f9fa; }
        .results-container { min-height: 300px; }
        .loading-spinner, .error-message { margin-top: 2rem; }
        #results-grid .card img { cursor: pointer; }
        .size-options { display: flex; gap: 0.5rem; flex-wrap: wrap; }
        .size-options .btn { font-size: 0.75rem; padding: 0.2rem 0.4rem; }
        .pagination-controls { margin-top: 2rem; margin-bottom: 1rem; }
        #error-message { word-break: break-word; }
        .image-source-label { position: absolute; top: 5px; right: 5px; background-color: rgba(0, 0, 0, 0.6); color: white; font-size: 0.65rem; padding: 2px 5px; border-radius: 3px; z-index: 1; }
        .card { position: relative; }
        /* Image Preview Modal Styles */
        .modal-overlay { position: fixed; inset: 0; background-color: rgba(0,0,0,0.85); display: flex; justify-content: center; align-items: center; opacity: 0; visibility: hidden; transition: opacity 0.3s ease, visibility 0.3s ease; z-index: 1050; padding: 1rem; }
        .modal-overlay.visible { opacity: 1; visibility: visible; }
        .image-preview-content { position: relative; max-width: 85vw; max-height: 85vh; display: flex; flex-direction: column; align-items: center; justify-content: center; transform: scale(0.9); opacity: 0; transition: transform 0.3s ease, opacity 0.3s ease; }
        .modal-overlay.visible .image-preview-content { transform: scale(1); opacity: 1; }
        #preview-image { display: block; max-width: 100%; max-height: calc(85vh - 60px); object-fit: contain; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.5); }
        .preview-caption { color: #eee; text-align: center; margin-top: 0.75rem; font-size: 0.9rem; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; background-color: rgba(0,0,0,0.5); padding: 0.25rem 0.5rem; border-radius: 4px;}
        .modal-close-button { position: absolute; top: -10px; right: -10px; background-color: rgba(40, 40, 40, 0.8); color: white; border: none; border-radius: 50%; width: 35px; height: 35px; font-size: 1.5rem; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; z-index: 1051; transition: background-color 0.2s ease; }
        #image-preview-modal .modal-close-button:hover { background-color: rgba(0, 0, 0, 0.9); }
        @media (max-width: 767.98px) { .image-preview-content { max-width: 95vw; max-height: 80vh; } #preview-image { max-height: calc(80vh - 50px); } #image-preview-modal .modal-close-button { top: 5px; right: 5px; width: 30px; height: 30px; font-size: 1.2rem;} .preview-caption { font-size: 0.8rem; margin-top: 0.5rem;} }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="my-4 text-center">h7 image finder</h1>
        <form id="search-form" class="row g-3 mb-4 justify-content-center"> <div class="col-md-8 col-lg-7"> <label for="search-input" class="visually-hidden">Search Term</label> <input type="text" class="form-control form-control-lg" id="search-input" placeholder="Enter image description (e.g., beautiful sunset)" required> </div> <div class="col-auto"> <button type="submit" class="btn btn-primary btn-lg">Search</button> </div> </form>
        <div id="loading-spinner" class="d-none text-center"> <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"> <span class="visually-hidden">Loading...</span> </div> </div>
        <div id="error-message" class="alert alert-danger d-none mt-4" role="alert"> </div>
        <div id="results-container" class="results-container mt-5"> <div id="results-grid" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4"> </div> <nav aria-label="Search results navigation" class="pagination-controls d-none" id="pagination-nav"> <ul class="pagination justify-content-center"> <li class="page-item" id="prev-page-item"> <button class="page-link" id="prev-page-btn" aria-label="Previous"> <span aria-hidden="true">&laquo;</span> Previous </button> </li> <li class="page-item disabled" id="page-info-item"> <span class="page-link" id="page-info-text">Page 1</span> </li> <li class="page-item" id="next-page-item"> <button class="page-link" id="next-page-btn" aria-label="Next"> Next <span aria-hidden="true">&raquo;</span> </button> </li> </ul> </nav> </div>
    </div>
    <div class="modal-overlay" id="image-preview-modal">
        <div class="image-preview-content">
            <button class="modal-close-button" aria-label="Close modal">&times;</button>
            <img alt="Image Preview" id="preview-image"> <div id="preview-caption" class="preview-caption"></div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="js/script.js"></script>
</body>
</html>
