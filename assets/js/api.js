const API_BASE = 'api/index.php';
const CACHE_KEYS = {
    events: 'cityevents_cache_events',
    notifications: 'cityevents_cache_notifications',
    favorites: 'cityevents_cache_favorites',
};

function buildQuery(params = {}) {
    const search = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') {
            search.append(key, value);
        }
    });
    return search.toString();
}

async function fetchJSON(url, options = {}) {
    const response = await fetch(url, {
        headers: { 'Content-Type': 'application/json' },
        ...options,
    });
    if (!response.ok) {
        const message = await response.text();
        throw new Error(message || 'Serverio klaida');
    }
    return response.json();
}

function readCache(key) {
    const value = localStorage.getItem(key);
    if (!value) return [];
    try {
        return JSON.parse(value);
    } catch (err) {
        console.warn('Nepavyko nuskaityti kešo', err);
        return [];
    }
}

function writeCache(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
}

function cacheKeyFor(resource) {
    return CACHE_KEYS[resource] || resource;
}

async function fetchWithCache(resource, params = {}) {
    const query = buildQuery({ resource, ...params });
    const cacheKey = cacheKeyFor(resource);
    const cached = readCache(cacheKey);
    try {
        const data = await fetchJSON(`${API_BASE}?${query}`);
        const payload = data[resource] || data.events || data.notifications || [];
        writeCache(cacheKey, payload);
        return { data: payload, error: null };
    } catch (error) {
        if (cached.length) {
            return { data: cached, error };
        }
        throw error;
    }
}

function getCachedEvents() {
    return readCache(CACHE_KEYS.events);
}

function getCachedNotifications() {
    return readCache(CACHE_KEYS.notifications);
}

const apiService = {
    getEvents: (params = {}) => fetchWithCache('events', params),
    getNotifications: (params = {}) => fetchWithCache('notifications', params),
    getFavorites: async (userId) => {
        if (!userId) return { data: [], error: new Error('Trūksta naudotojo ID') };
        try {
            const data = await fetchJSON(`${API_BASE}?${buildQuery({ resource: 'favorites', user_id: userId })}`);
            return { data: data.favorites || [], error: null };
        } catch (error) {
            return { data: [], error };
        }
    },
    toggleFavorite: async (payload) => {
        if (!payload?.user_id || !payload?.event_id) {
            throw new Error('Trūksta reikalingų laukų');
        }

        return fetchJSON(`${API_BASE}?${buildQuery({ resource: 'favorites' })}`, {
            method: 'POST',
            body: JSON.stringify(payload),
        });
    },
    getCachedEvents,
    getCachedNotifications,
};

// Make key variables globally accessible
window.API_BASE = API_BASE;
window.apiService = apiService;
window.buildQuery = buildQuery;
window.fetchJSON = fetchJSON;
