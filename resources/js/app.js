document.addEventListener('DOMContentLoaded', () => {
    const main = document.querySelector('.admin-main');
    if (!main) return;

    const query = new URLSearchParams(window.location.search);
    main.querySelectorAll('input, select, textarea').forEach((control) => {
        const name = control.getAttribute('name');
        if (name && query.has(name) && control.type !== 'hidden') control.value = query.get(name) ?? '';
        if (control.matches('[aria-label], [aria-labelledby]') || control.closest('label')) return;
        const firstOption = control instanceof HTMLSelectElement ? control.options[0]?.textContent?.trim() : '';
        const label = control.getAttribute('placeholder') || firstOption || control.getAttribute('name')?.replaceAll('_', ' ');
        if (label) control.setAttribute('aria-label', label);
    });

    main.querySelectorAll('table').forEach((table) => {
        let region = table.parentElement;
        if (!region?.classList.contains('admin-table-region')) {
            region = document.createElement('div');
            region.className = 'admin-table-region';
            table.before(region);
            region.append(table);
        }
        region.tabIndex = 0;
        const heading = table.closest('section')?.querySelector('h2')?.textContent?.trim()
            || main.querySelector('h1')?.textContent?.trim()
            || 'Data table';
        region.setAttribute('aria-label', `${heading} table; scroll horizontally to view all columns`);
    });

    if (window.location.search) {
        main.querySelectorAll('form[method="get"], form:not([method])').forEach((form) => {
            if (form.querySelector('[data-clear-filters]')) return;
            const reset = document.createElement('a');
            reset.href = window.location.pathname;
            reset.textContent = 'Clear filters';
            reset.className = 'inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium';
            reset.dataset.clearFilters = 'true';
            form.append(reset);
        });
    }
});
