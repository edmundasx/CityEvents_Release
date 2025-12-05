let map = null;
let markers = [];

function initMap() {
    const mapContainer = document.getElementById('map');
    if (!mapContainer || map) return;
    if (typeof L === 'undefined') {
        console.warn('Leaflet biblioteka neįkelta – žemėlapis nebus rodomas.');
        return;
    }
    map = L.map('map').setView([54.6872, 25.2797], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
    }).addTo(map);
}

function updateMap(events = []) {
    if (!document.getElementById('map')) return;
    if (typeof L === 'undefined') return;
    initMap();
    markers.forEach(marker => marker.remove());
    markers = [];

    const points = events.filter(e => e.lat && e.lng);
    points.forEach(event => {
        const marker = L.marker([event.lat, event.lng]).addTo(map);
        marker.bindPopup(`<strong>${event.title}</strong><br>${event.location}`);
        markers.push(marker);
    });

    if (points.length) {
        const group = L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.2));
    }
}

export { initMap, updateMap };
