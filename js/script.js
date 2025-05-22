// Wait for the HTML document to be fully loaded before running script
document.addEventListener('DOMContentLoaded', () => {
    console.log("DOM fully loaded and parsed"); // DEBUG: Check if DOMContentLoaded fires

    // --- DOM References ---
    const searchForm = document.getElementById('search-form');
    console.log("Search Form Element:", searchForm); // DEBUG: Check if form is found

    const searchInput = document.getElementById('search-input');
    const resultsGrid = document.getElementById('results-grid');
    const loadingSpinner = document.getElementById('loading-spinner');
    const errorMessageDiv = document.getElementById('error-message');
    const imagePreviewModal = document.getElementById('image-preview-modal');
    const previewImage = document.getElementById('preview-image');
    const previewCaption = document.getElementById('preview-caption');
    const paginationNav = document.getElementById('pagination-nav');
    const prevPageItem = document.getElementById('prev-page-item');
    const nextPageItem = document.getElementById('next-page-item');
    const prevPageBtn = document.getElementById('prev-page-btn');
    const nextPageBtn = document.getElementById('next-page-btn');
    const pageInfoText = document.getElementById('page-info-text');
    const profilePicHeader = document.getElementById('profile-pic-header');
    const dpModal = document.getElementById('dp-modal');
    const enlargedDpImage = document.getElementById('enlarged-dp');


    // --- State Variables ---
    let currentQuery = '';
    let currentStartIndex = 1;
    let totalResults = 0;
    const resultsPerPage = 10;

    // --- Event Listeners ---

    // Search Form Submission
    if (searchForm) { // Check if searchForm was found before adding listener
        console.log("Adding submit listener to search form..."); // DEBUG
        searchForm.addEventListener('submit', (event) => {
            console.log("Search form submitted!"); // DEBUG: Check if listener fires
            event.preventDefault(); // Prevent page reload
            const searchTerm = searchInput.value.trim();
            if (searchTerm) {
                currentQuery = searchTerm;
                currentStartIndex = 1;
                console.log("Calling fetchImages with:", currentQuery, currentStartIndex); // DEBUG
                fetchImages(currentQuery, currentStartIndex);
            } else {
                displayError('Please enter a search term.');
                resultsGrid.innerHTML = '';
                paginationNav.classList.add('d-none');
            }
        });
    } else {
        console.error("Could not find search form element (#search-form) to attach listener."); // DEBUG
    }


    // Pagination Button Listeners
    if (prevPageBtn) {
        prevPageBtn.addEventListener('click', () => {
            console.log("Previous button clicked"); // DEBUG
            if (currentStartIndex > 1) {
                currentStartIndex -= resultsPerPage;
                fetchImages(currentQuery, currentStartIndex);
            }
        });
    } else {
        console.warn("Previous page button not found.");
    }

    if (nextPageBtn) {
        nextPageBtn.addEventListener('click', () => {
            console.log("Next button clicked"); // DEBUG
            const maxResults = Math.min(totalResults, 100);
            if (currentStartIndex + resultsPerPage <= maxResults) {
                currentStartIndex += resultsPerPage;
                fetchImages(currentQuery, currentStartIndex);
            } else {
                 console.log("Next button clicked, but no more pages."); // DEBUG
            }
        });
     } else {
        console.warn("Next page button not found.");
    }


    // --- Async function to fetch images ---
    async function fetchImages(query, startIndex = 1) {
        console.log(`Fetching images for query: ${query}, start index: ${startIndex}`); // DEBUG
        resultsGrid.innerHTML = '';
        errorMessageDiv.classList.add('d-none');
        errorMessageDiv.textContent = '';
        loadingSpinner.classList.remove('d-none');
        paginationNav.classList.add('d-none');

        try {
            const fetchUrl = `index.php?action=search&query=${encodeURIComponent(query)}&start=${startIndex}`;
            console.log("Fetching URL:", fetchUrl); // DEBUG
            const response = await fetch(fetchUrl, {
                method: 'GET', headers: { 'Accept': 'application/json' }
            });
            console.log("Fetch response status:", response.status); // DEBUG

            const data = await response.json();
            console.log('Raw API Response:', data); // DEBUG: Log the raw response

            if (!response.ok || data.error) {
                throw new Error(data.error || `Server responded with status ${response.status}`);
            }

            totalResults = parseInt(data.searchInfo?.totalResults || 0);
            currentStartIndex = parseInt(data.searchInfo?.startIndex || 1);
            displayResults(data.items || []);
            updatePaginationUI();

        } catch (error) {
            console.error('Error in fetchImages:', error); // DEBUG: Log the actual error object
            displayError(error.message || 'An unknown error occurred during fetch.');
            totalResults = 0;
            updatePaginationUI();
        } finally {
            loadingSpinner.classList.add('d-none');
            console.log("fetchImages finished."); // DEBUG
        }
    }

    // --- Function to display the results ---
    function displayResults(items) {
        console.log("Displaying results. Number of items:", items.length); // DEBUG
        resultsGrid.innerHTML = '';
        if (!Array.isArray(items) || items.length === 0) {
            if (totalResults <= 0 && errorMessageDiv.classList.contains('d-none')) {
                 resultsGrid.innerHTML = '<p class="text-center text-muted mt-4">No images found for your search term.</p>';
            }
            return;
        }

        items.forEach((item, index) => { // Added index for debug logging
            // console.log(`Creating card for item ${index}:`, item); // DEBUG: Log each item
            const colDiv = document.createElement('div'); colDiv.className = 'col fade-in';
            const cardDiv = document.createElement('div'); cardDiv.className = 'card shadow-sm h-100';
            const sourceLabel = document.createElement('span'); sourceLabel.className = 'image-source-label'; sourceLabel.textContent = item.source || 'Unknown'; cardDiv.appendChild(sourceLabel);
            const img = document.createElement('img');
            // Basic check for thumbnail URL
            if (!item.thumbnail_url) {
                 console.warn(`Item ${index} missing thumbnail_url:`, item);
                 img.src = 'https://placehold.co/600x400/eee/ccc?text=No+Thumb'; // Placeholder if missing
            } else {
                 img.src = item.thumbnail_url;
            }
            img.alt = item.title || 'Search result image';
            img.className = 'card-img-top result-image-trigger'; img.loading = 'lazy';
            img.onerror = function() { this.alt = 'Image failed to load'; colDiv.style.display = 'none'; };
            img.dataset.largeUrl = item.image_url; img.dataset.title = item.title || '';

            const cardBody = document.createElement('div'); cardBody.className = 'card-body d-flex flex-column justify-content-between';
            const cardText = document.createElement('div'); const sourceLink = document.createElement('a');
            sourceLink.href = item.context_url || '#'; const displayTitle = item.title ? (item.title.length > 40 ? item.title.substring(0, 37) + '...' : item.title) : `View on ${item.source || 'Source'}`;
            sourceLink.textContent = displayTitle; sourceLink.className = 'text-muted small text-decoration-none'; sourceLink.target = '_blank'; sourceLink.rel = 'noopener noreferrer'; sourceLink.title = item.title || `View on ${item.source || 'Source'}`;
            if (item.photographer) { const photographerSpan = document.createElement('span'); photographerSpan.className = 'text-muted small d-block mt-1'; photographerSpan.textContent = `by ${item.photographer}`; cardText.appendChild(photographerSpan); }
            cardText.insertBefore(sourceLink, cardText.firstChild);

            const cardActions = document.createElement('div'); cardActions.className = 'mt-2 download-options-container';
            const downloadOptionsBtn = document.createElement('button'); downloadOptionsBtn.textContent = 'Download'; downloadOptionsBtn.className = 'btn btn-sm btn-outline-primary download-options-btn'; downloadOptionsBtn.dataset.imageUrl = item.image_url;
            const sizeOptionsDiv = document.createElement('div'); sizeOptionsDiv.className = 'size-options mt-2 d-none';
            sizeOptionsDiv.innerHTML = `<button class="btn btn-sm btn-secondary size-btn" data-size="original">Original</button> <button class="btn btn-sm btn-secondary size-btn" data-size="1920">1920px</button> <button class="btn btn-sm btn-secondary size-btn" data-size="1280">1280px</button>`;
            cardActions.appendChild(downloadOptionsBtn); cardActions.appendChild(sizeOptionsDiv);
            cardBody.appendChild(cardText); cardBody.appendChild(cardActions);
            cardDiv.appendChild(img); cardDiv.appendChild(cardBody); colDiv.appendChild(cardDiv);
            resultsGrid.appendChild(colDiv);
        });
    }

    // --- Function to Update Pagination UI ---
    function updatePaginationUI() { /* ... (no changes needed) ... */ }
    // --- Function to display error messages ---
    function displayError(message) { /* ... (no changes needed) ... */ }
    // --- Modal Handling Functions ---
    function openModal(modalElement) { /* ... (no changes needed) ... */ }
    function closeModal(modalElement) { /* ... (no changes needed) ... */ }

    // --- Event Listeners for Modals, Download, Preview ---
    if (profilePicHeader) { profilePicHeader.addEventListener('click', () => { /* ... (no changes needed) ... */ }); }
    else { console.warn("Profile picture header element (#profile-pic-header) not found during listener setup."); }

    resultsGrid.addEventListener('click', (event) => { /* ... (no changes needed) ... */ });
    document.addEventListener('click', (event) => { /* ... (no changes needed) ... */ });
    document.querySelectorAll('.modal-close-button').forEach(button => { /* ... (no changes needed) ... */ });
    document.querySelectorAll('.modal-overlay').forEach(overlay => { /* ... (no changes needed) ... */ });


    // --- Helper function definitions (ensure included) ---
    function updatePaginationUI() { if (totalResults > resultsPerPage) { paginationNav.classList.remove('d-none'); const currentPage = Math.floor((currentStartIndex - 1) / resultsPerPage) + 1; const maxResults = Math.min(totalResults, 100); const totalPages = Math.ceil(maxResults / resultsPerPage); pageInfoText.textContent = `Page ${currentPage} of ${totalPages}`; if (currentStartIndex <= 1) { prevPageItem.classList.add('disabled'); prevPageBtn.disabled = true; } else { prevPageItem.classList.remove('disabled'); prevPageBtn.disabled = false; } if (currentStartIndex + resultsPerPage > maxResults) { nextPageItem.classList.add('disabled'); nextPageBtn.disabled = true; } else { nextPageItem.classList.remove('disabled'); nextPageBtn.disabled = false; } } else { paginationNav.classList.add('d-none'); } }
    function displayError(message) { const cleanMessage = message.replace(/</g, "&lt;").replace(/>/g, "&gt;"); errorMessageDiv.innerHTML = `<strong>Error:</strong> ${cleanMessage}`; errorMessageDiv.classList.remove('d-none'); }
    function openModal(modalElement) { if (!modalElement) return; if (modalElement.id === 'dp-modal') { if (profilePicHeader && enlargedDpImage) { const dpUrl = profilePicHeader.dataset.dpUrl || profilePicHeader.style.backgroundImage.slice(5, -2); enlargedDpImage.src = dpUrl || 'https://placehold.co/250x250/cccccc/FFFFFF?text=Error'; } else { console.error("Required elements for DP modal not found."); return; } } modalElement.classList.add('visible'); document.body.style.overflow = 'hidden'; }
    function closeModal(modalElement) { if (modalElement) { modalElement.classList.remove('visible'); document.body.style.overflow = ''; const previewImageEl = modalElement.querySelector('#preview-image'); const enlargedDpImageEl = modalElement.querySelector('#enlarged-dp'); if (previewImageEl) { setTimeout(() => { previewImageEl.src = ''; }, 300); } if (enlargedDpImageEl) { setTimeout(() => { enlargedDpImageEl.src = ''; }, 300); } } }
    resultsGrid.addEventListener('click', (event) => { const target = event.target; if (target.classList.contains('download-options-btn')) { const optionsDiv = target.nextElementSibling; if (optionsDiv && optionsDiv.classList.contains('size-options')) { document.querySelectorAll('.size-options').forEach(div => { if (div !== optionsDiv) { div.classList.add('d-none'); } }); optionsDiv.classList.toggle('d-none'); } } if (target.classList.contains('size-btn')) { const sizeButton = target; const optionsDiv = sizeButton.closest('.size-options'); const mainButton = optionsDiv.previousElementSibling; const imageUrl = mainButton.dataset.imageUrl; const requestedSize = sizeButton.dataset.size; if (imageUrl) { let downloadUrl = `index.php?action=download&url=${encodeURIComponent(imageUrl)}`; if (requestedSize !== 'original') { downloadUrl += `&width=${requestedSize}`; } window.open(downloadUrl, '_blank'); optionsDiv.classList.add('d-none'); mainButton.textContent = 'Download'; mainButton.disabled = false; } else { console.error('Size button clicked, but image URL not found.'); alert('Could not initiate download: Image URL missing.'); } } if (target.classList.contains('result-image-trigger')) { const largeImageUrl = target.dataset.largeUrl; const imageTitle = target.dataset.title; const previewImageEl = document.getElementById('preview-image'); const previewCaptionEl = document.getElementById('preview-caption'); if (largeImageUrl && imagePreviewModal && previewImageEl && previewCaptionEl) { previewImageEl.src = largeImageUrl; previewImageEl.alt = imageTitle || 'Image Preview'; previewCaptionEl.textContent = imageTitle || ''; openModal(imagePreviewModal); } else { console.error("Could not find elements needed for image preview."); } } });
    document.addEventListener('click', (event) => { if (!event.target.closest('.download-options-btn') && !event.target.closest('.size-options')) { document.querySelectorAll('.size-options').forEach(div => { div.classList.add('d-none'); }); } });
    document.querySelectorAll('.modal-close-button').forEach(button => { button.addEventListener('click', () => { closeModal(button.closest('.modal-overlay')); }); });
    document.querySelectorAll('.modal-overlay').forEach(overlay => { overlay.addEventListener('click', (event) => { if (event.target === overlay) { closeModal(overlay); } }); });


}); // End of DOMContentLoaded
