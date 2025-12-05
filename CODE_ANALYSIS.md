# CityEvents Codebase Overview

## Front-end structure
- The project is a static multi-page site (e.g., `index.html`, `city-map.html`, `for-organizers.html`) styled via shared CSS in `assets/css` and driven by modular JavaScript in `assets/js`.
- Core client logic lives in `assets/js/app.js`, which maintains UI state, caches events/notifications, and falls back to built-in demo data when the API is unavailable. It exposes helper functions for loading events, formatting statuses, and generating organizer notifications.【F:assets/js/app.js†L1-L107】【F:assets/js/app.js†L108-L164】
- Feature-specific modules under `assets/js/modules` handle authentication dialogs, event rendering/favoriting, map updates, and shared UI behaviors. For example, `auth.js` builds login/signup modals, persists the active user in `localStorage`, and wires form submissions to the API.【F:assets/js/modules/auth.js†L1-L97】 `events.js` fetches and renders event cards, applying filters, status badges, and favorite toggles for the events grid.【F:assets/js/modules/events.js†L1-L71】【F:assets/js/modules/events.js†L72-L79】

## Back-end API layer
- The `/api` directory contains lightweight PHP endpoints organized by controller (e.g., `EventsController.php`, `AuthController.php`) that route HTTP methods to model operations and return JSON responses via helper functions in `api/helpers.php`.
- `api/models/Event.php` encapsulates database access for events, including filtered queries (by ID, organizer, category, search, location, and approval status), creation with default `pending` status, updates with whitelisted fields, and deletion handlers.【F:api/models/Event.php†L1-L99】【F:api/models/Event.php†L100-L120】
- Controllers such as `EventsController.php` validate request payloads and delegate to models for CRUD operations, returning appropriate status codes for common failure cases (missing fields, bad price, or missing IDs).【F:api/controllers/EventsController.php†L1-L75】

## Data and fallbacks
- `assets/js/app.js` seeds the UI with `demoEvents` and a lightweight `apiClient` shim so pages can render without a live backend; this data is used when the API cache is empty or fetches fail.【F:assets/js/app.js†L1-L39】【F:assets/js/app.js†L40-L64】
- User session information is stored in `localStorage` under the `cityevents_user` key, enabling login state persistence across page visits in both the app shell and auth module paths.【F:assets/js/app.js†L66-L73】【F:assets/js/modules/auth.js†L1-L24】
