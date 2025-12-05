import { fetchJSON, buildQuery } from '../api.js';
import { getStoredUser, promptLogin } from './auth.js';
import { updateMap } from './map.js';

let events = [];
let favorites = [1, 3];

function getKnownEvents(filters = {}) {
    let filteredEvents = events.length ? events : [];

    if (filters.category) {
        filteredEvents = filteredEvents.filter(e => e.category === filters.category);
    }

    return filteredEvents;
}

async function ensureEventsLoaded(params = {}) {
    const hasFilters = Object.keys(params).length > 0;
    if (!hasFilters && events.length) {
        return events;
    }

    try {
        const { data } = await fetchJSON(`${API_BASE}?${buildQuery({ resource: 'events', ...params })}`);
        const fetchedEvents = data || [];
        if (!hasFilters) {
            events = fetchedEvents;
        }
        return fetchedEvents;
    } catch (error) {
        if (!hasFilters && !events.length) {
            events = [];
        }
        return [];
    }
}

function formatStatus(status = '') {
    const map = {
        pending: 'Laukiama',
        approved: 'Patvirtinta',
        rejected: 'Atmesta',
        update_pending: 'Atnaujinimas laukia',
    };
    return map[status] || status || '—';
}

function renderEvents(events = []) {
    const container = document.getElementById('eventsGrid');
    if (!container) return;

    if (!events.length) {
        container.innerHTML = '<div class="loading">Šiuo metu renginių nėra.</div>';
        return;
    }

    container.innerHTML = events.map(event => {
        const price = event.price && Number(event.price) > 0 ? `${Number(event.price).toFixed(2)} €` : 'Nemokama';
        const statusClass = event.status ? `status-${event.status}` : 'status-approved';
        return `
            <article class="event-card" data-id="${event.id}">
                <div class="event-card-wrapper">
                    <div class="event-price">${price}</div>
                    <img class="event-image" src="${event.cover_image || 'https://images.unsplash.com/photo-1521737604893-d14cc237f11d?auto=format&fit=crop&w=800&q=80'}" alt="${event.title}">
                </div>
                <div class="event-content">
                    <div class="event-card-footer">
                        <span class="tag">${event.category || 'Kita'}</span>
                        <span class="status-badge ${statusClass}">${formatStatus(event.status || 'approved')}</span>
                    </div>
                    <h3 class="event-title">${event.title}</h3>
                    <div class="event-detail">📍 ${event.location}</div>
                    <div class="event-detail">📅 ${new Date(event.event_date).toLocaleString('lt-LT')}</div>
                    <div class="event-card-footer">
                        <button class="btn-ghost" data-favorite="${event.id}">❤ Įsiminti</button>
                        <a class="btn btn-outline" href="event-details.html?id=${event.id}">Daugiau</a>
                    </div>
                </div>
            </article>
        `;
    }).join('');

    container.querySelectorAll('[data-favorite]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleFavorite(btn.dataset.favorite, 'favorite');
        });
    });

    container.querySelectorAll('.event-card').forEach(card => {
        card.addEventListener('click', (e) => {
            if (e.target.closest('[data-favorite]') || e.target.tagName === 'A') return;
            window.location.href = `event-details.html?id=${card.dataset.id}`;
        });
    });
}

async function loadEvents(filters = {}) {
    const container = document.getElementById('eventsGrid');
    if (container) {
        container.innerHTML = '<div class="loading">Kraunama...</div>';
    }

    try {
        const events = await ensureEventsLoaded(filters);
        renderEvents(events);
        if (events.length) {
            updateMap(events);
        }
    } catch (err) {
        console.error(err);
        const fallback = getKnownEvents(filters);
        renderEvents(fallback);
        updateMap(fallback);
        if (container) {
            container.insertAdjacentHTML('afterbegin', `<div class="loading">${err.message || 'Nepavyko gauti naujausių duomenų. Rodomi paskutiniai pasiekti renginiai.'}</div>`);
        }
    }
}

function initSearch() {
    const searchInput = document.getElementById('searchInput');
    const locationInput = document.getElementById('locationInput');
    const searchButton = document.getElementById('searchButton');
    const resetBtn = document.getElementById('resetFilters');

    if (searchButton) {
        searchButton.addEventListener('click', () => {
            loadEvents({
                search: searchInput?.value,
                location: locationInput?.value,
            });
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', () => {
            if (searchInput) searchInput.value = '';
            if (locationInput) locationInput.value = '';
            document.querySelectorAll('.category').forEach(c => c.classList.remove('active'));
            loadEvents();
        });
    }

    document.querySelectorAll('.category').forEach(category => {
        category.addEventListener('click', () => {
            document.querySelectorAll('.category').forEach(c => c.classList.remove('active'));
            category.classList.add('active');
            loadEvents({ category: category.dataset.category });
        });
    });
}

async function toggleFavorite(eventId, tag = 'favorite') {
    const user = getStoredUser();
    if (!user) {
        promptLogin('Prisijunkite, kad pažymėtumėte renginius.');
        return;
    }

    try {
        await fetchJSON(`${API_BASE}?resource=favorites`, {
            method: 'POST',
            body: JSON.stringify({ event_id: eventId, user_id: user.id, tag }),
        });
        // loadRecommendations();
    } catch (err) {
        console.error(err);
    }
}

async function loadEventDetails() {
    const params = new URLSearchParams(window.location.search);
    const eventId = params.get('id');
    if (!eventId) return;

    try {
        const data = await fetchJSON(`${API_BASE}?${buildQuery({ resource: 'events', id: eventId, include_all: 1 })}`);
        const event = data.event || data.events?.[0];
        if (!event) throw new Error('Renginys nerastas');

        document.title = `${event.title} - CityEvents`;
        document.getElementById('crumbTitle').textContent = event.title;
        document.getElementById('eventTitle').textContent = event.title;
        document.getElementById('eventDescription').textContent = event.description || '';
        document.getElementById('eventCategory').textContent = event.category;
        document.getElementById('eventHero').src = event.cover_image || 'https://images.unsplash.com/photo-1459749411175-04bf5292ceea?w=800&h=450&fit=crop';
        document.getElementById('eventHero').alt = event.title || 'Renginys';
        document.getElementById('eventDate').textContent = new Date(event.event_date).toLocaleDateString('lt-LT');
        document.getElementById('eventTime').textContent = new Date(event.event_date).toLocaleTimeString('lt-LT');
        document.getElementById('eventLocation').textContent = event.location;
        document.getElementById('eventPrice').textContent = event.price && Number(event.price) > 0 ? `${Number(event.price).toFixed(2)} €` : 'Nemokama';
        document.getElementById('eventOrganizer').textContent = event.organizer_name || 'Organizatorius';
        document.getElementById('eventStatus').textContent = formatStatus(event.status);
        document.getElementById('organizerAvatar').textContent = (event.organizer_name || 'CE').slice(0, 2).toUpperCase();

        const features = document.getElementById('eventFeatures');
        features.innerHTML = '';
        ['Nauja patirtis', 'Įdomūs pranešėjai', 'Daugiau veiklų mieste'].forEach(text => {
            const li = document.createElement('li');
            li.textContent = text;
            features.appendChild(li);
        });

        const ticketBtn = document.getElementById('btnAttend');
        if (ticketBtn) {
            ticketBtn.onclick = () => {
                window.open('https://www.tiketa.lt', '_blank', 'noopener');
            };
        }
        document.getElementById('btnFavorite').onclick = () => toggleFavorite(event.id, 'favorite');
    } catch (err) {
        const container = document.getElementById('eventContainer');
        if (container) container.innerHTML = `<div class="loading">${err.message}</div>`;
    }
}

export {
    ensureEventsLoaded,
    renderEvents,
    loadEvents,
    initSearch,
    loadEventDetails,
    getKnownEvents,
    formatStatus
};
