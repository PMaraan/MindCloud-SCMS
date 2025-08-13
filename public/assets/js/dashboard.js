document.body.addEventListener('click', function (e) {
    // Store the <a> element with data-page attribute as button
    const button = e.target.closest('[data-page]');
    if (!button) return;

    e.preventDefault(); // Prevent the default href behavior
    const page = button.getAttribute('data-page'); // Get the page value from the attribute

    // Create a new form
    const formData = new FormData();
    formData.append('page', page);

    // Fetch content from ContentController through AJAX
    fetch('/app/controllers/ContentController.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        // Replace HTML content
        document.querySelector('.main-content').innerHTML = data.html;

        // Remove old page-specific assets
        document.querySelectorAll('link[data-page-css], script[data-page-js]').forEach(el => el.remove());

        // Load page-specific CSS if provided
        if (data.css) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = data.css + `?t=${Date.now()}`;
            link.dataset.pageCss = 'true';
            document.head.appendChild(link);
        }

         // Load page-specific JS if provided
        if (data.js) {
            const script = document.createElement('script');
            script.src = data.js + `?t=${Date.now()}`;
            script.defer = true;
            script.dataset.pageJs = 'true';
            document.body.appendChild(script);
        }
    })
    .catch(err => console.error('Fetch error:', err));
});
