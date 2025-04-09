<?php

// --- Configuration ---
define('GOOGLE_API_KEY', 'AIzaSyBx-oAVneVeXU_kycKwaL2lhgvL7_63HkQ');
define('GOOGLE_CX_ID', '84d077dd92b12425c');
define('PEXELS_API_KEY', 'NZhCnHsHFIGkxhySBY19KFgoIHOxQ3VJMQtHI4i2BWFhRdjLEkmNPvAn'); 

// --- Request Routing ---
$action = $_GET['action'] ?? null;

// --- API Search Request Handling ---
if ($action === 'search' && isset($_GET['query'])) {

    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');

    $output_data = [ 'items' => [], 'searchInfo' => ['totalResults' => 0, 'startIndex' => 1], 'error' => null ];
    $query = trim($_GET['query']);
    $startIndex = isset($_GET['start']) ? max(1, (int)$_GET['start']) : 1;
    $output_data['searchInfo']['startIndex'] = $startIndex;

    // --- Input and Config Validation ---
    if (empty($query)) { http_response_code(400); $output_data['error'] = 'Search query cannot be empty.'; echo json_encode($output_data); exit; }
    if (empty(GOOGLE_API_KEY) || GOOGLE_API_KEY === 'YOUR_GOOGLE_API_KEY' || empty(GOOGLE_CX_ID) || GOOGLE_CX_ID === 'YOUR_GOOGLE_CX_ID' || empty(PEXELS_API_KEY) || PEXELS_API_KEY === 'YOUR_PEXELS_API_KEY') { http_response_code(500); $output_data['error'] = 'One or more API Keys/IDs are not configured correctly.'; echo json_encode($output_data); exit; }

    // --- Helper function for cURL requests ---
    function makeApiRequest($url, $headers = []) {
        $ch = curl_init(); if ($ch === false) { return ['error' => 'Failed to initialize cURL session.', 'http_code' => 500, 'body' => null]; }
        curl_setopt($ch, CURLOPT_URL, $url); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_TIMEOUT, 15); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); if (!empty($headers)) { curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); }
        $response_body = curl_exec($ch); $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $curl_error_num = curl_errno($ch); $curl_error_msg = curl_error($ch); curl_close($ch);
        if ($curl_error_num > 0) { return ['error' => 'cURL Error (' . $curl_error_num . '): ' . $curl_error_msg, 'http_code' => 500, 'body' => null]; }
        return ['error' => null, 'http_code' => $http_code, 'body' => $response_body];
    }

    // --- Call Google Custom Search API (Always call for pagination info) ---
    $google_results = [];
    $google_total_results = 0;
    $google_error = null;
    $google_api_url = 'https://www.googleapis.com/customsearch/v1';
    $google_params = [ 'key' => GOOGLE_API_KEY, 'cx' => GOOGLE_CX_ID, 'q' => $query, 'searchType' => 'image', 'num' => 10, 'start' => $startIndex ];
    $google_request_url = $google_api_url . '?' . http_build_query($google_params);
    $google_response = makeApiRequest($google_request_url);

    if ($google_response['error']) { $google_error = $google_response['error']; }
    elseif ($google_response['http_code'] === 200) {
        $google_data = json_decode($google_response['body'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if (isset($google_data['error'])) { $google_error = 'Google API Error: ' . ($google_data['error']['message'] ?? 'Unknown'); }
            elseif (isset($google_data['items'])) {
                $google_total_results = $google_data['searchInformation']['totalResults'] ?? 0;
                foreach ($google_data['items'] as $item) {
                    if (isset($item['link']) && isset($item['image']['thumbnailLink'])) {
                        $google_results[] = [ 'id' => 'g_' . ($item['cacheId'] ?? uniqid()), 'title' => $item['title'] ?? '', 'image_url' => $item['link'], 'thumbnail_url' => $item['image']['thumbnailLink'], 'context_url' => $item['image']['contextLink'] ?? '#', 'source' => 'Google', 'photographer' => $item['displayLink'] ?? '' ];
                    }
                }
            } // If 'items' is not set, google_results remains empty, which is fine
        } else { $google_error = 'Invalid JSON response from Google API.'; }
    } else { $google_error = 'Google API request failed with HTTP status ' . $google_response['http_code']; }


    $pexels_results = [];
    $pexels_error = null;
    if ($startIndex === 1) { // Only call Pexels for the first page
        $pexels_api_url = 'https://api.pexels.com/v1/search';
        $pexels_params = ['query' => $query, 'per_page' => 15, 'page' => 1]; // Fetch 15 Pexels results for page 1
        $pexels_request_url = $pexels_api_url . '?' . http_build_query($pexels_params);
        $pexels_headers = ['Authorization: ' . PEXELS_API_KEY];
        $pexels_response = makeApiRequest($pexels_request_url, $pexels_headers);

        if ($pexels_response['error']) { $pexels_error = $pexels_response['error']; }
        elseif ($pexels_response['http_code'] === 200) {
            $pexels_data = json_decode($pexels_response['body'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($pexels_data['error'])) { $pexels_error = 'Pexels API Error: ' . $pexels_data['error']; }
                elseif (isset($pexels_data['photos'])) {
                    foreach ($pexels_data['photos'] as $photo) {
                         if (isset($photo['src']['medium']) && isset($photo['src']['original'])) {
                            $pexels_results[] = [ 'id' => 'p_' . $photo['id'], 'title' => $photo['alt'] ?? ('Photo by ' . $photo['photographer']), 'image_url' => $photo['src']['original'], 'thumbnail_url' => $photo['src']['medium'], 'context_url' => $photo['url'] ?? '#', 'source' => 'Pexels', 'photographer' => $photo['photographer'] ?? '' ];
                         }
                    }
                }
            } else { $pexels_error = 'Invalid JSON response from Pexels API.'; }
        } else { $pexels_error = 'Pexels API request failed with HTTP status ' . $pexels_response['http_code']; }
    } // End if startIndex === 1

    // --- Combine and Finalize Output ---
    // Combine results (Google first, then Pexels if fetched)
    $output_data['items'] = array_merge($google_results, $pexels_results);
    $output_data['searchInfo']['totalResults'] = $google_total_results; // Base pagination on Google's total
    $output_data['searchInfo']['sourcesAttempted'] = ($startIndex === 1) ? ['Google', 'Pexels'] : ['Google'];

    // Combine errors if needed
    $combined_error = null;
    if ($google_error && $pexels_error) { $combined_error = "Multiple API Errors: [Google: " . $google_error . "] [Pexels: " . $pexels_error . "]"; http_response_code(500); }
    elseif ($google_error) { $combined_error = $google_error; if($google_response['http_code'] !== 200) http_response_code($google_response['http_code']); else http_response_code(500); }
    elseif ($pexels_error) { $combined_error = $pexels_error; if($pexels_response['http_code'] !== 200) http_response_code($pexels_response['http_code']); else http_response_code(500); }
    elseif (empty($output_data['items']) && $startIndex === 1) { $combined_error = null; } // No error if no results found on page 1
    // If subsequent Google page fails but had no specific error message
    elseif (empty($output_data['items']) && $startIndex > 1 && !$google_error) { $combined_error = "Failed to fetch Google results for page."; http_response_code(500); }


    $output_data['error'] = $combined_error;

    echo json_encode($output_data);
    exit;

}
// --- Image Download Request Handling ---
elseif ($action === 'download' && isset($_GET['url'])) {
    // ... (Download/Resize logic - No changes) ...
     if (!extension_loaded('gd')) { http_response_code(500); die('Error: GD library is not enabled on the server.'); } $imageUrl = trim($_GET['url']); $requestedWidth = isset($_GET['width']) ? (int)$_GET['width'] : 'original'; if (empty($imageUrl) || !filter_var($imageUrl, FILTER_VALIDATE_URL)) { http_response_code(400); die('Invalid Image URL provided.'); } if ($requestedWidth !== 'original' && $requestedWidth <= 0) { http_response_code(400); die('Invalid width parameter provided.'); } $ch = curl_init(); if ($ch === false) { http_response_code(500); die('Failed to initialize cURL session for download.'); } curl_setopt($ch, CURLOPT_URL, $imageUrl); curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); curl_setopt($ch, CURLOPT_TIMEOUT, 30); curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); $imageContent = curl_exec($ch); $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE); $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE); $curl_error_num = curl_errno($ch); $curl_error_msg = curl_error($ch); curl_close($ch); if ($curl_error_num > 0) { http_response_code(500); die('cURL Error fetching image (' . $curl_error_num . '): ' . $curl_error_msg); } if ($http_code !== 200) { http_response_code($http_code); die('Failed to download image. Server returned HTTP ' . $http_code); } if (empty($imageContent)) { http_response_code(500); die('Downloaded image content is empty.'); } $sourceImage = @imagecreatefromstring($imageContent); if ($sourceImage === false) { http_response_code(500); die('Failed to process image data. Unsupported format or corrupt data.'); } $sourceWidth = imagesx($sourceImage); $sourceHeight = imagesy($sourceImage); $resizedImage = null; $targetWidth = null; if ($requestedWidth !== 'original' && $requestedWidth < $sourceWidth) { $targetWidth = $requestedWidth; $aspectRatio = $sourceHeight / $sourceWidth; $targetHeight = floor($targetWidth * $aspectRatio); $resizedImage = imagecreatetruecolor($targetWidth, $targetHeight); if ($contentType == 'image/png') { imagealphablending($resizedImage, false); imagesavealpha($resizedImage, true); $transparent = imagecolorallocatealpha($resizedImage, 255, 255, 255, 127); imagefilledrectangle($resizedImage, 0, 0, $targetWidth, $targetHeight, $transparent); } elseif ($contentType == 'image/gif') { $transparent_index = imagecolortransparent($sourceImage); if ($transparent_index >= 0) { $transparent_color = imagecolorsforindex($sourceImage, $transparent_index); $transparent_new = imagecolorallocate($resizedImage, $transparent_color['red'], $transparent_color['green'], $transparent_color['blue']); imagefill($resizedImage, 0, 0, $transparent_new); imagecolortransparent($resizedImage, $transparent_new); } } if (imagecopyresampled($resizedImage, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $sourceWidth, $sourceHeight)) { imagedestroy($sourceImage); } else { imagedestroy($resizedImage); $resizedImage = $sourceImage; $targetWidth = null; } } else { $resizedImage = $sourceImage; } $outputFormat = 'jpeg'; $filename = basename(parse_url($imageUrl, PHP_URL_PATH)); if (empty($filename)) { $filename = 'downloaded_image'; } if (strpos($contentType, 'image/png') !== false) { $outputFormat = 'png'; $filename = pathinfo($filename, PATHINFO_FILENAME) . '.png'; } elseif (strpos($contentType, 'image/gif') !== false) { $outputFormat = 'gif'; $filename = pathinfo($filename, PATHINFO_FILENAME) . '.gif'; } else { $outputFormat = 'jpeg'; $filename = pathinfo($filename, PATHINFO_FILENAME) . '.jpg'; } if ($requestedWidth !== 'original' && $resizedImage !== $sourceImage && isset($targetWidth)) { $filename = pathinfo($filename, PATHINFO_FILENAME) . '_' . $targetWidth . 'px.' . pathinfo($filename, PATHINFO_EXTENSION); } header('Content-Disposition: attachment; filename="' . $filename . '"'); header('Cache-Control: private'); if ($outputFormat === 'png') { header('Content-Type: image/png'); imagepng($resizedImage); } elseif ($outputFormat === 'gif') { header('Content-Type: image/gif'); imagegif($resizedImage); } else { header('Content-Type: image/jpeg'); imagejpeg($resizedImage, null, 90); } imagedestroy($resizedImage); exit;
}

?>
<!DOCTYPE html>
<html lang="si">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>h7 finder - Image Search</title>
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
    </style>
</head>
<body>
    <div class="container">
        <h1 class="my-4 text-center">Image finder</h1>
        <form id="search-form" class="row g-3 mb-4 justify-content-center"> 
            <div class="col-md-8 col-lg-7"> <label for="search-input" class="visually-hidden">Search Term</label> <input type="text" class="form-control form-control-lg" id="search-input" placeholder="Enter image description (e.g., beautiful sunset)" required> 
            </div> 
            <div class="col-auto"> <button type="submit" class="btn btn-primary btn-lg">Search</button> 
            </div> 
        </form>
        <div id="loading-spinner" class="d-none text-center"> 
            <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;" role="status"> <span class="visually-hidden">Loading...</span> 
            </div> 
        </div>
        <div id="error-message" class="alert alert-danger d-none mt-4" role="alert"> </div>
        <div id="results-container" class="results-container mt-5"> 
            <div id="results-grid" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4"> </div> 
            <nav aria-label="Search results navigation" class="pagination-controls d-none" id="pagination-nav"> 
                <ul class="pagination justify-content-center"> 
                    <li class="page-item" id="prev-page-item"> 
                        <button class="page-link" id="prev-page-btn" aria-label="Previous"> 
                            <span aria-hidden="true">&laquo;</span> Previous 
                        </button> 
                    </li>
                     <li class="page-item disabled" id="page-info-item"> 
                        <span class="page-link" id="page-info-text">Page 1</span> 
                    </li> 
                    <li class="page-item" id="next-page-item"> 
                        <button class="page-link" id="next-page-btn" aria-label="Next"> Next 
                            <span aria-hidden="true">&raquo;</span> 
                        </button> 
                    </li> 
                </ul> 
            </nav> 
        </div>
    </div>
    <div class="modal-overlay" id="image-preview-modal"> 
        <div class="image-preview-content"> 
            <button class="modal-close-button" aria-label="Close modal">&times;</button> 
            <img src="" alt="Image Preview" id="preview-image"> 
            <div id="preview-caption" class="preview-caption"></div> 
        </div> 
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="js/script.js"></script>
</body>
</html>
