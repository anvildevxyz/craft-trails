// Add CSRF token to all htmx requests
document.addEventListener('htmx:configRequest', function(event) {
    var csrfToken = window.Craft && window.Craft.csrfTokenValue;
    if (csrfToken) {
        event.detail.headers['X-CSRF-Token'] = csrfToken;
    }
});

// Highlight new rows on htmx swap
document.addEventListener('htmx:afterSwap', function(event) {
    if (event.detail.target.id === 'trails-log-table') {
        var rows = event.detail.target.querySelectorAll('tr[data-new]');
        rows.forEach(function(row) {
            row.classList.add('trails-row-new');
            setTimeout(function() {
                row.classList.remove('trails-row-new');
                row.removeAttribute('data-new');
            }, 3000);
        });
    }
});
