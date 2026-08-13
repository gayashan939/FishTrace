import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

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

    main.querySelectorAll('[data-live-transport-map]').forEach((container) => {
        const trips = JSON.parse(container.dataset.trips || '[]');
        const destinationRadius = Number(container.dataset.destinationRadius || 500);
        const map = L.map(container, { preferCanvas: true });
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);
        const bounds = [];

        trips.forEach((trip) => {
            const route = Array.isArray(trip.route) ? trip.route : [];
            if (route.length > 1) {
                L.polyline(route, { color: '#075e63', weight: 4, opacity: 0.8 }).addTo(map);
                bounds.push(...route);
            }
            if (trip.origin_position) {
                L.circleMarker(trip.origin_position, { radius: 7, color: '#047857', fillOpacity: 1 })
                    .bindTooltip(`Origin: ${trip.origin}`)
                    .addTo(map);
                bounds.push(trip.origin_position);
            }
            if (trip.destination_position) {
                L.circle(trip.destination_position, {
                    radius: destinationRadius,
                    color: '#dc2626',
                    fillColor: '#fecaca',
                    fillOpacity: 0.2,
                }).bindTooltip(`Destination: ${trip.destination}`).addTo(map);
                bounds.push(trip.destination_position);
            }
            if (trip.current_position) {
                const popup = document.createElement('div');
                const title = document.createElement('strong');
                title.textContent = trip.code;
                const details = document.createElement('p');
                details.textContent = `${trip.vehicle || 'Vehicle'} · ${trip.temperature ?? '—'} °C · battery ${trip.battery ?? '—'}%`;
                const link = document.createElement('a');
                link.href = trip.url;
                link.textContent = 'Open trip';
                link.className = 'text-cyan-700 underline';
                popup.append(title, details, link);
                L.circleMarker(trip.current_position, {
                    radius: 9,
                    color: '#083344',
                    fillColor: '#06b6d4',
                    fillOpacity: 1,
                    weight: 3,
                }).bindPopup(popup).addTo(map);
                bounds.push(trip.current_position);
            }
        });

        if (bounds.length > 0) map.fitBounds(bounds, { padding: [32, 32], maxZoom: 14 });
        else map.setView([7.8731, 80.7718], 7);
    });
});
