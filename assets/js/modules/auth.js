import { fetchJSON, buildQuery } from '../api.js';

const USER_STORAGE_KEY = 'cityevents_user';

function getStoredUser() {
    const user = parseStoredJSON(USER_STORAGE_KEY);
    if (!user || typeof user !== 'object') return null;

    if (!user.id || !user.role) {
        console.warn('Removing incomplete user profile from storage');
        localStorage.removeItem(USER_STORAGE_KEY);
        return null;
    }

    return user;
}

function saveUser(user) {
    if (!user || typeof user !== 'object') {
        console.warn('Attempted to store an invalid user object', user);
        return;
    }

    localStorage.setItem(USER_STORAGE_KEY, JSON.stringify(user));
}

function parseStoredJSON(key, fallback = null) {
    const raw = localStorage.getItem(key);
    if (!raw) return fallback;

    try {
        return JSON.parse(raw);
    } catch (err) {
        console.warn('Removing corrupted value from storage for key', key, err);
        localStorage.removeItem(key);
        return fallback;
    }
}

let toggleLoginModal = () => {};
let toggleSignupModal = () => {};

function initLoginModal() {
    if (document.getElementById('loginModal')) return;

    const modalWrapper = document.createElement('div');
    modalWrapper.innerHTML = `
        <div class="login-modal" id="loginModal" aria-hidden="true">
            <div class="login-backdrop" data-login-close></div>
            <div class="login-dialog" role="dialog" aria-modal="true" aria-labelledby="loginTitle">
                <button class="login-close" type="button" data-login-close aria-label="Uždaryti">×</button>
                <h3 id="loginTitle">Prisijungimas</h3>
                <p>Įveskite savo el. paštą ir slaptažodį, kad tęstumėte.</p>
                <form class="login-form" id="loginForm">
                    <div class="form-group">
                        <label for="loginEmail">El. paštas</label>
                        <input type="email" id="loginEmail" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="loginPassword">Slaptažodis</label>
                        <input type="password" id="loginPassword" name="password" required>
                    </div>
                    <div class="login-actions">
                        <button type="button" class="btn-secondary" data-login-close>Atšaukti</button>
                        <button type="submit" class="btn btn-primary">Prisijungti</button>
                    </div>
                    <div class="login-message" id="loginMessage"></div>
                </form>
            </div>
        </div>
    `.trim();

    const modal = modalWrapper.firstElementChild;
    document.body.appendChild(modal);

    const form = modal.querySelector('#loginForm');
    const message = modal.querySelector('#loginMessage');

    function toggleLogin(open) {
        modal.classList.toggle('open', open);
        modal.setAttribute('aria-hidden', open ? 'false' : 'true');
        if (open) {
            message.textContent = '';
            modal.querySelector('#loginEmail')?.focus();
        }
    }

    toggleLoginModal = toggleLogin;

    modal.querySelectorAll('[data-login-close]').forEach(btn => {
        btn.addEventListener('click', () => toggleLogin(false));
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        const email = formData.get('email');
        const password = formData.get('password');
        const submitBtn = form.querySelector('button[type="submit"]');

        message.textContent = '';
        message.style.color = '#374151';

        try {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Jungiama...';
            }

            const data = await fetchJSON(`${API_BASE}?resource=auth`, {
                method: 'POST',
                body: JSON.stringify({ email, password }),
            });

            saveUser(data.user);
            message.textContent = `Prisijungta kaip ${data.user.name}`;
            message.style.color = '#15803d';
            form.reset();
            renderAuthActions();
            setTimeout(() => toggleLogin(false), 800);
        } catch (err) {
            message.textContent = err.message || 'Nepavyko prisijungti.';
            message.style.color = '#dc2626';
        } finally {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Prisijungti';
            }
        }
    });
}

function initSignupModal() {
    if (document.getElementById('signupModal')) return;

    const modalWrapper = document.createElement('div');
    modalWrapper.innerHTML = `
        <div class="login-modal signup-modal" id="signupModal" aria-hidden="true">
            <div class="login-backdrop" data-signup-close></div>
            <div class="login-dialog" role="dialog" aria-modal="true" aria-labelledby="signupTitle">
                <button class="login-close" type="button" data-signup-close aria-label="Uždaryti">×</button>
                <div class="registration-container">
                    <div id="signupSuccess" class="success-message">Paskyra sukurta! Galite prisijungti ir registruoti renginius.</div>
                    <div id="signupError" class="error-message">Nepavyko sukurti paskyros.</div>
                    <div class="form-container">
                        <div class="form-header">
                            <h2 id="signupTitle">Sukurti paskyrą</h2>
                            <p>Pasirinkite ar esate dalyvis ar organizatorius</p>
                        </div>

                        <form id="signupForm">
                            <div class="form-group">
                                <label class="form-label" for="name">Vardas ir pavardė</label>
                                <input class="form-input" type="text" id="name" name="name" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="email">El. paštas</label>
                                <input class="form-input" type="email" id="email" name="email" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label" for="password">Slaptažodis</label>
                                <input class="form-input" type="password" id="password" name="password" minlength="6" required>
                            </div>

                            <div class="form-group">
                                <span class="form-label">Pasirinkite paskyros tipą</span>
                                <div class="radio-group">
                                    <label class="radio-option">
                                        <input type="radio" name="role" value="user" checked>
                                        <span class="radio-label">Dalyvis</span>
                                    </label>
                                    <label class="radio-option">
                                        <input type="radio" name="role" value="organizer">
                                        <span class="radio-label">Organizatorius</span>
                                    </label>
                                </div>
                            </div>

                            <button class="submit-btn" type="submit">Registruotis</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    `.trim();

    const modal = modalWrapper.firstElementChild;
    document.body.appendChild(modal);

    const form = modal.querySelector('#signupForm');
    const success = modal.querySelector('#signupSuccess');
    const error = modal.querySelector('#signupError');

    function toggleSignup(open) {
        modal.classList.toggle('open', open);
        modal.setAttribute('aria-hidden', open ? 'false' : 'true');
        if (open) {
            success.style.display = 'none';
            error.style.display = 'none';
            modal.querySelector('#name')?.focus();
        }
    }

    toggleSignupModal = toggleSignup;

    modal.querySelectorAll('[data-signup-close]').forEach(btn => {
        btn.addEventListener('click', () => toggleSignup(false));
    });

    handleSignup();
}

function handleSignup() {
    const form = document.getElementById('signupForm');
    if (!form) return;

    const success = document.getElementById('signupSuccess');
    const error = document.getElementById('signupError');

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        success.style.display = 'none';
        error.style.display = 'none';

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        try {
            const data = await fetchJSON(`${API_BASE}?resource=users`, {
                method: 'POST',
                body: JSON.stringify(payload),
            });
            saveUser(data.user);
            success.style.display = 'block';
            success.textContent = 'Paskyra sukurta! Jūsų ID: ' + data.user.id;
            form.reset();
            renderAuthActions();
            const modal = form.closest('.login-modal');
            if (modal) {
                setTimeout(() => toggleSignupModal(false), 800);
            }
        } catch (err) {
            error.style.display = 'block';
            error.textContent = err.message || 'Nepavyko užsiregistruoti.';
        }
    });
}

function bindLoginTriggers() {
    document.querySelectorAll('.js-login-trigger').forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            toggleLoginModal(true);
        });
    });
}

function bindSignupTriggers() {
    document.querySelectorAll('.js-signup-trigger').forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            toggleSignupModal(true);
        });
    });
}

function ensureAuthContainer() {
    const headerContent = document.querySelector('.header .header-content');
    if (!headerContent) return null;

    // Cleanup legacy buttons if any
    headerContent.querySelectorAll('button[onclick*="Login feature"], button[onclick*="Sign up feature"]').forEach(btn => {
        const parent = btn.closest('div');
        parent?.remove();
    });

    let container = document.getElementById('authActions');
    if (!container) {
        container = document.createElement('div');
        container.id = 'authActions';
        container.className = 'header-actions';
        
        const headerRight = headerContent.querySelector('.header-right');
        if (headerRight) {
            headerRight.appendChild(container);
        } else {
            headerContent.appendChild(container);
        }
    }
    return container;
}


function renderAuthActions() {
    const container = ensureAuthContainer();
    if (!container) return;

    const user = getStoredUser();
    syncNavRoleLinks(user);

    if (!user) {
        container.innerHTML = `
            <a class="btn btn-outline js-login-trigger" href="#login">Prisijungti</a>
            <a class="btn btn-primary js-signup-trigger" href="#signup">Registruotis</a>
        `;
        bindLoginTriggers();
        bindSignupTriggers();
        return;
    }

    const roleLabel = user.role === 'organizer'
        ? 'Organizatorius'
        : user.role === 'admin'
            ? 'Administratorius'
            : 'Dalyvis';

    const name = user.name || 'Vartotojas';
    const initial = name.charAt(0).toUpperCase();

    const organizerLink = user.role === 'organizer' || user.role === 'admin'
        ? '<a class="btn btn-outline" href="organizer-dashboard.html">Organizatoriaus zona</a>'
        : '';

    const adminLink = user.role === 'admin'
        ? '<a class="btn btn-outline" href="admin-panel.html">Admin panelis</a>'
        : '';

    container.innerHTML = `
        <div class="user-chip" aria-label="Prisijungęs naudotojas">
            <span class="user-initial">${initial}</span>
            <div>
                <div class="user-name">${name}</div>
                <div class="user-role">${roleLabel}</div>
            </div>
        </div>
        <a class="btn btn-outline" href="edit-profile.html">Redaguoti profilį</a>
        ${organizerLink}
        ${adminLink}
        <button class="btn btn-primary" id="logoutBtn" type="button">Atsijungti</button>
    `;

    const logoutBtn = container.querySelector('#logoutBtn');
    logoutBtn?.addEventListener('click', () => {
        localStorage.removeItem(USER_STORAGE_KEY);
        renderAuthActions();
        if (window.location.pathname.includes('attender-panel') ||
            window.location.pathname.includes('organizer') ||
            window.location.pathname.includes('admin-panel')) {
            window.location.href = 'index.html';
        }
    });
}

function syncNavRoleLinks(user) {
    const rolePanels = {
        admin: {
            href: 'admin-panel.html',
            label: 'Admin panelis',
        },
        organizer: {
            href: 'organizer-dashboard.html',
            label: 'Organizatoriaus panelis',
        },
        user: {
            href: 'attender-panel.html',
            label: 'Dalyvio panelis',
        },
        attender: {
            href: 'attender-panel.html',
            label: 'Dalyvio panelis',
        },
    };

    document.querySelectorAll('.nav').forEach(nav => {
        nav.querySelector('[data-nav-admin]')?.remove();

        let panelLink = nav.querySelector('[data-nav-panel]');
        const roleConfig = user ? rolePanels[user.role] : null;

        if (roleConfig) {
            if (!panelLink) {
                panelLink = document.createElement('a');
                panelLink.className = 'nav-link';
                panelLink.setAttribute('data-nav-panel', 'true');
                nav.appendChild(panelLink);
            }
            panelLink.href = roleConfig.href;
            panelLink.textContent = roleConfig.label;
        } else if (panelLink) {
            panelLink.remove();
        }
    });
}

function promptLogin(message = '') {
    if (message) {
        const createMessage = document.getElementById('createEventMessage');
        if (createMessage && !createMessage.textContent) {
            createMessage.textContent = message;
            createMessage.style.color = '#dc2626';
        }
    }

    if (typeof toggleLoginModal === 'function') {
        toggleLoginModal(true);
    } else {
        window.location.href = 'signup.html';
    }
}

export {
    getStoredUser,
    saveUser,
    initLoginModal,
    initSignupModal,
    bindLoginTriggers,
    bindSignupTriggers,
    renderAuthActions,
    handleSignup,
    promptLogin
};
