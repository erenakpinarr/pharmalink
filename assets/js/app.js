'use strict';
class ApiService {
    static async post(url, data = {}) {
        const formData = new FormData();
        for (const [key, value] of Object.entries(data)) {
            formData.append(key, value);
        }
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || window.CSRF_TOKEN;
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
        }
        const baseUrl = window.APP_URL || '';
        try {
            const response = await fetch(`${baseUrl}${url}`, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken || ''
                },
                body: formData
            });
            if (!response.ok) throw new Error(`HTTP Hata: ${response.status}`);
            return await response.json();
        } catch (error) {
            console.error('API İsteği Başarısız:', error);
            throw error;
        }
    }
}
class NotificationManager {
    constructor() {
        this.baseApi = (window.APP_URL || '') + '/api/notifications.php';
        this.bellBtn = document.getElementById('bildirim-can');
        this.badge = document.getElementById('bildirim-sayac');
        this.dropdown = document.getElementById('bildirim-pencere');
        this.listEl = document.getElementById('bildirim-liste');
        this.tabs = document.querySelectorAll('.bildirim-tab');
        this.readAllBtn = document.getElementById('bildirim-hepsini-oku');
        this.clearBtn = document.getElementById('bildirim-temizle');

        this.currentCategory = 'hepsi';
        this.isOpen = false;

        if (this.bellBtn && this.dropdown) {
            this.init();
        }
    }

    init() {
        this.fetchNotifications();

        this.bellBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleDropdown();
        });

        document.addEventListener('click', (e) => {
            if (this.isOpen && !this.dropdown.contains(e.target) && !this.bellBtn.contains(e.target)) {
                this.closeDropdown();
            }
        });

        this.tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                this.tabs.forEach(t => t.classList.remove('aktif'));
                tab.classList.add('aktif');
                this.currentCategory = tab.dataset.kat;
                this.fetchNotifications();
            });
        });

        if (this.readAllBtn) {
            this.readAllBtn.addEventListener('click', () => this.markRead(0));
        }

        if (this.clearBtn) {
            this.clearBtn.addEventListener('click', () => this.clearRead());
        }

        // Fetch periodically (every 1 minute)
        setInterval(() => this.fetchNotifications(), 60000);
    }

    toggleDropdown() {
        this.isOpen = !this.isOpen;
        if (this.isOpen) {
            this.dropdown.classList.add('acik');
            this.fetchNotifications();
        } else {
            this.dropdown.classList.remove('acik');
        }
    }

    closeDropdown() {
        this.isOpen = false;
        this.dropdown.classList.remove('acik');
    }

    async fetchNotifications() {
        try {
            const response = await fetch(`${this.baseApi}?kategori=${this.currentCategory}`);
            const data = await response.json();
            this.renderList(data);
        } catch (err) {
            console.error('Bildirimler alınamadı', err);
        }
    }

    renderList(data) {
        if (!this.listEl) return;

        this.listEl.innerHTML = '';
        let unreadCount = 0;

        if (data.length === 0) {
            this.listEl.innerHTML = `
                <div class="bildirim-bos">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                    <span>Gösterilecek bildirim yok</span>
                </div>
            `;
        } else {
            data.forEach(item => {
                if (item.okundu == 0) unreadCount++;

                const iconMap = {
                    'info': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>',
                    'basari': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>',
                    'uyari': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
                    'tehlike': '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
                };

                const html = `
                    <div class="bildirim-item ${item.okundu == 0 ? 'okunmamis' : ''}" data-id="${item.id}">
                        <div class="bildirim-item-ikon ${item.tip}">
                            ${iconMap[item.tip] || iconMap['info']}
                        </div>
                        <div class="bildirim-icerik">
                            <div class="bildirim-item-metadata">
                                <span class="bildirim-item-kategori">${item.kategori}</span>
                            </div>
                            <div class="bildirim-item-baslik">${item.baslik}</div>
                            <div class="bildirim-item-metin">${item.mesaj}</div>
                            <div class="bildirim-alt">
                                <span class="bildirim-zaman">${item.zaman_insan}</span>
                                ${item.okundu == 0 ? `<button class="bildirim-footer-btn" onclick="window.pharmaNotifications.markRead(${item.id})">Okundu İşaretle</button>` : ''}
                            </div>
                            <div class="bildirim-eylemler-kapsayici">
                                ${item.baglanti_url ? `<a href="${item.baglanti_url}" class="btn btn-birincil btn-sm">Görüntüle</a>` : ''}
                                ${this.renderActions(item)}
                            </div>
                        </div>
                    </div>
                `;
                this.listEl.insertAdjacentHTML('beforeend', html);
            });
        }

        this.updateBadge(unreadCount);
    }

    updateBadge(count) {
        if (!this.badge) return;
        this.badge.textContent = count;
        this.badge.style.display = count > 0 ? 'flex' : 'none';
    }

    renderActions(n) {
        if (!n.eylemler) return '';
        try {
            const eylemler = typeof n.eylemler === 'string' ? JSON.parse(n.eylemler) : n.eylemler;
            if (!Array.isArray(eylemler)) return '';
            return eylemler.map(e => `
                <button class="btn btn-birincil btn-sm" onclick="event.stopPropagation(); window.pharmaNotifications.executeAction('${e.url}', '${e.method}', ${n.id})">
                    ${e.label}
                </button>
            `).join('');
        } catch (e) {
            return '';
        }
    }

    async executeAction(url, method, notifId) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || window.CSRF_TOKEN;
            await fetch((window.APP_URL || '') + '/' + url, {
                method: method,
                headers: { 'X-CSRF-TOKEN': csrfToken || '' }
            });
            this.markRead(notifId);
        } catch (e) {
            console.error('Aksiyon hatası:', e);
        }
    }

    async markRead(id) {
        try {
            const formData = new FormData();
            formData.append('id', id);
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) formData.append('csrf_token', csrfToken);

            await fetch(`${this.baseApi}?action=mark_read`, {
                method: 'POST',
                body: formData
            });
            this.fetchNotifications();
        } catch (e) {
            console.error('Okundu işaretlenemedi', e);
        }
    }

    async clearRead() {
        try {
            const formData = new FormData();
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (csrfToken) formData.append('csrf_token', csrfToken);

            await fetch(`${this.baseApi}?action=clear`, {
                method: 'POST',
                body: formData
            });
            this.fetchNotifications();
        } catch (e) {
            console.error('Temizlenemedi', e);
        }
    }
}

/* Notifications will be initialized in the main App.init() */
let pharmaNotifications;

class SearchAutocomplete {
    constructor(inputSelector, listSelector, formSelector) {
        this.input = document.querySelector(inputSelector);
        this.list = document.querySelector(listSelector);
        this.form = document.querySelector(formSelector);
        this.debounceTimer = null;
        this.activeIndex = -1;
        this.init();
    }
    init() {
        if (!this.input || !this.list) return;
        this.input.addEventListener('input', () => this.handleInput());
        this.input.addEventListener('keydown', (e) => this.handleKeydown(e));
        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target) && !this.list.contains(e.target)) {
                this.close();
            }
        });
        if (this.form) {
            this.form.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }
    }
    handleInput() {
        clearTimeout(this.debounceTimer);
        const val = this.input.value.trim();
        this.activeIndex = -1;
        if (val.length < 2) {
            this.close();
            return;
        }
        this.debounceTimer = setTimeout(() => this.fetchSuggestions(val), 300);
    }
    async fetchSuggestions(query) {
        if (this.list.style.display !== 'block' || this.list.innerHTML === '') {
            this.list.innerHTML = `<div class="autocomplete-item loading">Aranıyor...</div>`;
            this.list.style.display = 'block';
        }
        const baseUrl = window.APP_URL || '';
        const currentPath = window.location.pathname;
        const searchPath = 'api/search_drug.php';
        try {
            const response = await fetch(`${baseUrl}/${searchPath}?q=${encodeURIComponent(query)}`);
            if (!response.ok) throw new Error('API hatası');
            const data = await response.json();
            this.renderSuggestions(data);
        } catch (error) {
            console.error('Autocomplete hatası:', error);
            this.close();
        }
    }
    renderSuggestions(data) {
        this.list.innerHTML = '';
        if (data.length === 0) {
            this.close();
            return;
        }
        const icon = `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>`;
        data.forEach((item, index) => {
            const div = document.createElement('div');
            div.className = 'autocomplete-item';
            div.innerHTML = `${icon} <span>${item}</span>`;
            div.addEventListener('mousedown', (e) => {
                e.preventDefault();
                this.input.value = item;
                this.close();
                this.submitWithLocation();
            });
            this.list.appendChild(div);
        });
        this.list.style.display = 'block';
    }
    handleKeydown(e) {
        const items = this.list.querySelectorAll('.autocomplete-item');
        if (!items.length || this.list.style.display === 'none') return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this.activeIndex = (this.activeIndex + 1) % items.length;
            this.updateActiveItem(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            this.activeIndex = (this.activeIndex - 1 + items.length) % items.length;
            this.updateActiveItem(items);
        } else if (e.key === 'Enter') {
            if (this.activeIndex > -1) {
                e.preventDefault();
                this.input.value = items[this.activeIndex].querySelector('span').innerText;
                this.submitWithLocation();
            }
        } else if (e.key === 'Escape') {
            this.close();
        }
    }
    updateActiveItem(items) {
        items.forEach((item, index) => {
            item.classList.toggle('aktif', index === this.activeIndex);
            if (index === this.activeIndex) {
                item.scrollIntoView({ block: 'nearest' });
            }
        });
    }
    close() {
        this.list.style.display = 'none';
        this.list.innerHTML = '';
        this.activeIndex = -1;
    }
    handleFormSubmit(e) {
        const lat = this.form.querySelector('input[name="lat"]')?.value;
        const lng = this.form.querySelector('input[name="lng"]')?.value;
        if ((lat && lng) || !this.input.value.trim()) {
            return;
        }
        e.preventDefault();
        this.submitWithLocation();
    }
    submitWithLocation() {
        this.close();
        const loadingUI = document.getElementById('locLoading');
        if (loadingUI) loadingUI.style.display = 'flex';
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const latInput = this.form.querySelector('input[name="lat"]');
                    const lngInput = this.form.querySelector('input[name="lng"]');
                    if (latInput) latInput.value = pos.coords.latitude;
                    if (lngInput) lngInput.value = pos.coords.longitude;
                    this.form.submit();
                },
                () => {
                    this.form.submit();
                },
                { timeout: 3000 }
            );
        } else {
            this.form.submit();
        }
    }
}
class SidebarManager {
    constructor() {
        this.sidebar = document.querySelector('.app-sidebar');
        this.toggleBtn = document.getElementById('sidebar-toggle');
        this.overlay = document.querySelector('.sidebar-overlay');
        this.isMobile = window.innerWidth <= 768;
        this.init();
    }
    init() {
        if (!this.sidebar || !this.toggleBtn) return;
        if (!this.isMobile && localStorage.getItem('sidebar-collapsed') === 'true') {
            this.sidebar.classList.add('collapsed');
        }
        this.toggleBtn.addEventListener('click', () => {
            if (window.innerWidth <= 768) {
                this.sidebar.classList.toggle('mobil-acik');
                if (this.overlay) this.overlay.classList.toggle('aktif');
            } else {
                this.sidebar.classList.toggle('collapsed');
                const isCollapsed = this.sidebar.classList.contains('collapsed');
                localStorage.setItem('sidebar-collapsed', isCollapsed);
            }
        });
        if (this.overlay) {
            this.overlay.addEventListener('click', () => {
                this.sidebar.classList.remove('mobil-acik');
                this.overlay.classList.remove('aktif');
            });
        }
        window.addEventListener('resize', () => {
            if (window.innerWidth <= 768) {
                this.sidebar.classList.remove('collapsed');
            } else {
                this.sidebar.classList.remove('mobil-acik');
                if (localStorage.getItem('sidebar-collapsed') === 'true') {
                    this.sidebar.classList.add('collapsed');
                }
            }
        });
    }
}
class ToastManager {
    static show(message, type = 'bilgi') {
        const dmap = {
            'basari': 'success',
            'tehlike': 'error',
            'uyari': 'warning',
            'bilgi': 'info'
        };
        const swalType = dmap[type] || 'info';
        Swal.fire({
            text: message,
            icon: swalType,
            confirmButtonText: 'Tamam',
            confirmButtonColor: 'var(--renk-ikincil)',
            background: 'var(--arkaplan-kart)',
            color: 'var(--metin-birincil)',
            timer: 2000,
            timerProgressBar: false,
            backdrop: `rgba(0,0,0,0.5)`,
            customClass: {
                popup: 'kart',
                title: 'sayfa-baslik',
                confirmButton: 'btn btn-ikincil'
            },
            showClass: { popup: '' },
            hideClass: { popup: '' }
        });
    }
}
class ModalManager {
    static open(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.add('acik');
            document.body.style.overflow = 'hidden';
        }
    }
    static close(id) {
        const el = document.getElementById(id);
        if (el) {
            el.classList.remove('acik');
            document.body.style.overflow = '';
        }
    }
    static initEvents() {
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-arkaplan.acik').forEach(m => {
                    m.classList.remove('acik');
                    document.body.style.overflow = '';
                });
            }
        });
        document.querySelectorAll('.modal-arkaplan').forEach(m => {
            m.addEventListener('click', e => {
                if (e.target === m) {
                    m.classList.remove('acik');
                    document.body.style.overflow = '';
                }
            });
        });
    }
}
class DropdownManager {
    constructor() {
        this.toggleBtn = document.getElementById('avatar-toggle');
        this.menu = document.getElementById('avatar-menu');
        this.init();
    }
    init() {
        if (!this.toggleBtn || !this.menu) return;
        this.toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isShowing = this.menu.classList.contains('goster');
            if (isShowing) {
                this.close();
            } else {
                this.open();
            }
        });
        document.addEventListener('click', (e) => {
            if (this.menu.classList.contains('goster')) {
                if (!this.menu.contains(e.target) && e.target !== this.toggleBtn) {
                    this.close();
                }
            }
        });
        this.menu.querySelectorAll('.dropdown-item').forEach(item => {
            item.addEventListener('click', () => this.close());
        });
    }
    open() {
        this.menu.classList.add('goster');
        this.toggleBtn.setAttribute('aria-expanded', 'true');
    }
    close() {
        this.menu.classList.remove('goster');
        this.toggleBtn.setAttribute('aria-expanded', 'false');
    }
}
class RegisterManager {
    constructor() {
        this.form = document.getElementById('kayitForm');
        this.tipInput = document.getElementById('kayitTipi');
        this.eczaneBolumu = document.getElementById('eczaneBolumu');
        this.tabButonlar = document.querySelectorAll('.tab-buton');
        this.init();
    }
    init() {
        if (!this.form || !this.tipInput || !this.eczaneBolumu) return;
        this.tabButonlar.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tip = btn.dataset.tip;
                if (!tip) return;
                this.tipInput.value = tip;
                this.eczaneBolumu.style.display = (tip === 'eczane') ? 'block' : 'none';
                this.tabButonlar.forEach(b => b.classList.remove('aktif'));
                btn.classList.add('aktif');
            });
        });
    }
}
window.modalAc = ModalManager.open;
window.modalKapat = ModalManager.close;
window.gostermeBildirim = ToastManager.show;
window.tabloFiltrele = function (aramaInputId, tabloId) {
    const input = document.getElementById(aramaInputId);
    const tablo = document.getElementById(tabloId);
    if (!input || !tablo) return;
    input.addEventListener('input', function () {
        const q = this.value.toLowerCase().trim();
        tablo.querySelectorAll('tbody tr').forEach(row => {
            const metin = row.textContent.toLowerCase();
            row.style.display = metin.includes(q) ? '' : 'none';
        });
    });
};
window.stokGuncelle = async function (stokId, yeniDurum, btn) {
    btn.disabled = true;
    try {
        const data = await ApiService.post('/api/inventory.php', {
            islem: 'stok_guncelle',
            stok_id: stokId,
            durum: yeniDurum
        });
        if (data.basari) {
            const satir = btn.closest('tr');
            if (satir) {
                const rozet = satir.querySelector('.stok-rozet');
                if (rozet) {
                    rozet.className = 'rozet stok-rozet';
                    const harita = { mevcut: 'stok-mevcut', az_stok: 'stok-az', tukendi: 'stok-tukendi' };
                    const metinler = { mevcut: 'Mevcut', az_stok: 'Az Stok', tukendi: 'Tükendi' };
                    rozet.classList.add(harita[yeniDurum] || 'stok-mevcut');
                    rozet.textContent = metinler[yeniDurum] || yeniDurum;
                }
            }
            ToastManager.show('Stok durumu güncellendi.', 'basari');
        } else {
            ToastManager.show(data.mesaj || 'Hata oluştu.', 'tehlike');
        }
    } catch (err) {
        ToastManager.show('İstek başarısız oldu.', 'tehlike');
    } finally {
        btn.disabled = false;
    }
};
window.nobetciToggle = async function (checkbox) {
    const isChecked = checkbox.checked ? 1 : 0;
    const label = checkbox.closest('label').querySelector('span:last-child');
    const toggle = checkbox.closest('.toggle-switch');
    checkbox.disabled = true;
    if (toggle) toggle.style.opacity = '0.5';
    try {
        const data = await ApiService.post('/api/inventory.php', {
            islem: 'nobetci_guncelle',
            durum: isChecked
        });
        if (data.basari) {
            if (label) label.textContent = isChecked ? 'Nöbetçiyim' : 'Nöbetçi değilim';
            ToastManager.show(data.mesaj, 'basari');
            const rozet = document.querySelector('.sayfa-baslik .rozet');
            if (rozet) {
                rozet.className = isChecked ? 'rozet rozet-yesil' : 'rozet rozet-gri';
                rozet.textContent = isChecked ? '🔔 Nöbetçi' : 'Nöbet Yok';
            }
        } else {
            checkbox.checked = !isChecked;
            ToastManager.show(data.mesaj || 'Hata oluştu.', 'tehlike');
        }
    } catch (err) {
        checkbox.checked = !isChecked;
        ToastManager.show('Bağlantı hatası.', 'tehlike');
    } finally {
        checkbox.disabled = false;
        if (toggle) toggle.style.opacity = '1';
    }
};
/* Core managers will be initialized in the main App.init() */
document.querySelectorAll('[data-onay]').forEach(btn => {
    btn.addEventListener('click', e => {
        e.preventDefault();
        Swal.fire({
            title: 'Emin misiniz?',
            text: btn.dataset.onay,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: 'var(--renk-tehlike)',
            cancelButtonColor: 'var(--kenar-rengi)',
            confirmButtonText: 'Evet, Onaylıyorum',
            cancelButtonText: 'İptal',
            background: 'var(--arkaplan-kart)',
            color: 'var(--metin-birincil)',
            customClass: {
                confirmButton: 'btn btn-tehlike',
                cancelButton: 'btn btn-gri'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                if (btn.hasAttribute('href') && btn.getAttribute('href') !== '#') {
                    window.location.href = btn.getAttribute('href');
                } else if (btn.closest('form')) {
                    btn.closest('form').submit();
                }
            }
        });
    });
});
window.tabloFiltrele('aramaInput', 'anaTablosu');
const isAppBody = document.body.classList.contains('app-body');
if (isAppBody) {
    let lastNotificationId = 0;
    const checkNotifications = async () => {
        try {
            const res = await fetch(window.APP_URL + '/api/notifications.php?action=check_new&last_id=' + lastNotificationId);
            if (res.ok) {
                const data = await res.json();
                if (data.basari && data.bildirimVar) {
                    ToastManager.show(data.mesaj || data.baslik, data.tip);
                    lastNotificationId = data.last_id;
                    if (window.pharmaNotifications) {
                        window.pharmaNotifications.fetchNotifications();
                    }
                }
            }
        } catch (err) {
            console.error("Polling Error:", err);
        }
    };
    setTimeout(() => {
        checkNotifications();
        setInterval(checkNotifications, 15000);
    }, 5000);
}
if (document.getElementById('drugSearchInput')) {
    new SearchAutocomplete('#drugSearchInput', '#autocompleteList', '#publicSearchForm');
}

/* PharmaBot class removed */

class PasswordValidator {
    constructor(inputSelector, hintSelector) {
        this.input = document.querySelector(inputSelector);
        this.hintContainer = document.querySelector(hintSelector);
        if (!this.input || !this.hintContainer) return;
        this.rules = {
            length: { regex: /.{8,}/, element: null, text: 'En az 8 karakter' },
            uppercase: { regex: /[A-Z]/, element: null, text: 'En az 1 büyük harf' },
            number: { regex: /[0-9]/, element: null, text: 'En az 1 rakam' },
            special: { regex: /[^A-Za-z0-9]/, element: null, text: 'En az 1 sembol (!@#$ vb.)' }
        };
        this.init();
    }
    init() {
        this.hintContainer.innerHTML = `
            <div style="font-weight:800; margin-bottom:1rem; color:#fff; display:flex; align-items:center; gap:0.5rem; font-size:1rem;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="var(--auth-accent)" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Şifre Gereksinimleri
            </div>
            <div class="rules-list" style="display:grid; grid-template-columns:1fr 1fr; gap:0.75rem;"></div>
        `;
        const list = this.hintContainer.querySelector('.rules-list');
        for (const [key, rule] of Object.entries(this.rules)) {
            const div = document.createElement('div');
            div.className = 'rule-item invalid';
            div.style.display = 'flex';
            div.style.alignItems = 'center';
            div.style.gap = '0.5rem';
            div.style.fontSize = '0.85rem';
            div.style.fontWeight = '600';
            div.style.transition = 'all 0.3s ease';
            div.innerHTML = `
                <span class="icon" style="display:flex; align-items:center;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                </span>
                <span>${rule.text}</span>
            `;
            this.rules[key].element = div;
            list.appendChild(div);
        }
        this.input.addEventListener('input', () => {
            const val = this.input.value;
            if (val.length > 0) {
                this.hintContainer.style.display = 'block';
                this.validate();
            } else {
                this.hintContainer.style.display = 'none';
            }
        });
        const form = this.input.closest('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                if (!this.isValid()) {
                    e.preventDefault();
                    this.hintContainer.style.display = 'block';
                    this.input.focus();
                    this.hintContainer.style.animation = 'none';
                    this.hintContainer.offsetHeight;
                    this.hintContainer.style.animation = 'fadeInDownShake 0.6s cubic-bezier(0.16, 1, 0.3, 1)';
                }
            });
        }
    }
    validate() {
        const val = this.input.value;
        let allValid = true;
        for (const [key, rule] of Object.entries(this.rules)) {
            const isValid = rule.regex.test(val);
            const el = rule.element;
            const icon = el.querySelector('.icon');
            if (isValid) {
                el.classList.remove('invalid');
                el.classList.add('valid');
                el.style.color = 'var(--auth-success)';
                icon.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
            } else {
                el.classList.remove('valid');
                el.classList.add('invalid');
                el.style.color = 'var(--auth-text-muted)';
                icon.innerHTML = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
                allValid = false;
            }
        }
        if (allValid && val.length > 0) {
            setTimeout(() => {
                if (this.isValid()) {
                    this.hintContainer.style.opacity = '0.5';
                    this.hintContainer.style.filter = 'grayscale(1)';
                }
            }, 1000);
        } else {
            this.hintContainer.style.opacity = '1';
            this.hintContainer.style.filter = 'none';
        }
        return allValid;
    }
    isValid() {
        const val = this.input.value;
        return Object.values(this.rules).every(rule => rule.regex.test(val));
    }
}
/* PasswordValidator and other logic will be handled here or in App.init() */
const regPass = document.getElementById('reg_sifre');
if (regPass && typeof PasswordValidator !== 'undefined') {
    new PasswordValidator('#reg_sifre', '#passwordRulesHint');
}

const PharmaSearch = {
    userLoc: null,
    internalResults: [],

    init() {
        // 'el' değişkeninin dışarıdan geldiğini veya döngü içinde olduğunu varsayıyorum.
        // Eğer bu kısım statik veriyse şu şekilde düzelttim:
        this.internalResults = Array.from(document.querySelectorAll('.isletme-item')).map(el => ({
            id: el.dataset.id,
            lat: parseFloat(el.dataset.lat),
            lng: parseFloat(el.dataset.lng)
        }));

        this.discoverLocation().then(loc => {
            if (loc) {
                this.userLoc = loc;
                if (window.PHARMA_CONFIG && window.PHARMA_CONFIG.searchActive) {
                    this.exploreExternalPharmacies();
                    const resEl = document.getElementById('sonuclar');
                    if (resEl) resEl.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    },

    async discoverLocation() {
        return new Promise((resolve) => {
            if (!navigator.geolocation) return resolve(null);
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    const loc = { lat: pos.coords.latitude, lng: pos.coords.longitude };
                    const latInp = document.getElementById('latInput');
                    const lngInp = document.getElementById('lngInput');
                    if (latInp) latInp.value = loc.lat;
                    if (lngInp) lngInp.value = loc.lng;
                    resolve(loc);
                },
                () => resolve(null),
                { timeout: 5000 }
            );
        });
    },

    async exploreExternalPharmacies() {
        if (!this.userLoc) {
            const fallbackCity = (window.PHARMA_CONFIG && window.PHARMA_CONFIG.userCity) || 'İstanbul';
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ address: fallbackCity }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    this.userLoc = {
                        lat: results[0].geometry.location.lat(),
                        lng: results[0].geometry.location.lng()
                    };
                    this.performExternalSearch();
                }
            });
            return;
        }
        this.performExternalSearch();
    },

    async performExternalSearch() {
        const loader = document.getElementById('searchLoader');
        if (loader) loader.style.display = 'block';
        try {
            const { Place } = await google.maps.importLibrary("places");
            const request = {
                fields: ['displayName', 'location', 'formattedAddress', 'rating', 'userRatingCount', 'googleMapsURI'],
                locationRestriction: { center: this.userLoc, radius: 5000 },
                includedPrimaryTypes: ['pharmacy']
            };
            const { places } = await Place.searchNearby(request);
            this.renderExternalResults(places);
        } catch (err) {
            console.error("Harici arama hatası:", err);
        } finally {
            if (loader) loader.style.display = 'none';
        }
    },

    renderExternalResults(places) {
        const container = document.getElementById('externalResultsContainer');
        if (!container || !places || places.length === 0) return;

        let html = '';
        let addedCount = 0;

        places.forEach(place => {
            // Mesafe kontrolü için geometry kütüphanesi gereklidir
            const isDuplicate = this.internalResults.some(res =>
                google.maps.geometry.spherical.computeDistanceBetween(
                    new google.maps.LatLng(res.lat, res.lng),
                    place.location
                ) < 150
            );

            if (!isDuplicate && place.location) {
                addedCount++;
                const distInput = google.maps.geometry.spherical.computeDistanceBetween(
                    new google.maps.LatLng(this.userLoc.lat, this.userLoc.lng),
                    place.location
                );
                const distKm = (distInput / 1000).toFixed(2);

                // Güvenli stringler
                const safeName = (place.displayName || 'Eczane').toString().replace(/'/g, "\\'");
                const safeAddr = (place.formattedAddress || '').toString().replace(/'/g, "\\'");

                html += `
                    <div class="pub-drug-card external-result" style="opacity: 0.9; border-style: dashed;">
                        <div class="card-left">
                            <h3 style="font-size:1.15rem; color:var(--metin-birincil); margin:0;">${place.displayName}</h3>
                            <div style="display:flex;gap:0.5rem; flex-wrap:wrap;">
                                <span class="ext-badge">Harici Eczane</span>
                                ${place.rating ? `<span style="font-size:0.75rem; color:#fbbf24; font-weight:700;">&#9733; ${place.rating}</span>` : ''}
                            </div>
                            <p style="color:var(--metin-ikincil); font-size:0.85rem; margin:0;">${place.formattedAddress}</p>
                        </div>
                        <div class="card-mid">
                            <div style="padding:0.5rem; background:rgba(239,68,68,0.05); border-radius:var(--yaricap); border:1px solid rgba(239,68,68,0.2); color:#ef4444; text-align:center; width:100%; font-size:0.9rem;">
                                <strong>&#x2715; Net Stok Yok</strong>
                            </div>
                        </div>
                        <div class="card-right">
                            <div style="font-size:1rem; font-weight:700; color:var(--metin-uc);">${distKm} km</div>
                            <button type="button"
                                onclick="openRouteModal(${place.location.lat()}, ${place.location.lng()}, '${safeName}', '${safeAddr}')"
                                class="btn btn-cizgili btn-sm">&#9658; Rota</button>
                        </div>
                    </div>`;
            }
        });

        container.innerHTML = html;
        const counter = document.getElementById('searchCounter');
        if (counter) {
            counter.innerText = `${this.internalResults.length} Sistem + ${addedCount} Harici Sonuç`;
        }
    }
};

window.drawPublicRoute = (lat, lng, name, addr) => openRouteModal(lat, lng, name, addr);
if (typeof google !== 'undefined') {
    PharmaSearch.init();
} else {
    window.addEventListener('load', () => { PharmaSearch.init(); });
}
let pubRouteMap = null;
let pubDirectionsService = null;
let pubDirectionsRenderer = null;
let pubRouteMapInitialized = false;
let currentRouteTarget = null;
let currentTravelMode = 'DRIVING';
function openRouteModal(lat, lng, name, address) {
    currentRouteTarget = { lat: parseFloat(lat), lng: parseFloat(lng), name, address };
    document.getElementById('routeDestName').textContent = name || 'Eczane';
    document.getElementById('routeDestAddress').textContent = address || '';
    const userLoc = PharmaSearch.userLoc;
    const mode = currentTravelMode.toLowerCase();
    document.getElementById('openGoogleMapsBtn').href = userLoc
        ? `https://www.google.com/maps/dir/?api=1&origin=${userLoc.lat},${userLoc.lng}&destination=${lat},${lng}&travelmode=${mode}`
        : `https://www.google.com/maps/search/?api=1&query=${lat},${lng}`;
    if (!userLoc) {
        const stepsList = document.getElementById('routeStepsList');
        stepsList.innerHTML = `<div style="padding:2rem;text-align:center;color:var(--metin-uc);">
                    <div style="font-size:2rem;margin-bottom:1rem;">📍</div>
                    <strong>Konum Bilgisi Gerekli</strong><br>
                    Rota çizilebilmesi için tarayıcı konum iznine izin vermeniz gerekmektedir.
                </div>`;
        document.getElementById('routeSummarySection').style.display = 'flex';
        document.getElementById('routeDuration').textContent = '—';
        document.getElementById('routeDistance').textContent = '—';
        document.getElementById('routeCalcLoading').style.display = 'none';
    }
    document.getElementById('routeModal').classList.add('open');
    document.body.style.overflow = 'hidden';
    if (!pubRouteMapInitialized) {
        initPubRouteMap().then(() => calculatePubRoute());
    } else {
        calculatePubRoute();
    }
}
async function initPubRouteMap() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    const darkStyles = [
        { elementType: 'geometry', stylers: [{ color: '#1d2235' }] },
        { elementType: 'labels.text.fill', stylers: [{ color: '#8ec3b9' }] },
        { featureType: 'water', elementType: 'geometry', stylers: [{ color: '#171d33' }] },
        { featureType: 'road', elementType: 'geometry', stylers: [{ color: '#38414e' }] },
        { featureType: 'poi', stylers: [{ visibility: 'off' }] },
        { featureType: 'transit', stylers: [{ visibility: 'off' }] }
    ];
    const center = currentRouteTarget || { lat: 39.0, lng: 35.0 };
    pubRouteMap = new google.maps.Map(document.getElementById('pubRouteMap'), {
        center: { lat: center.lat, lng: center.lng },
        zoom: 13,
        styles: isDark ? darkStyles : [{ featureType: 'poi', stylers: [{ visibility: 'off' }] }],
        mapTypeControl: false, streetViewControl: false, fullscreenControl: true
    });
    pubDirectionsService = new google.maps.DirectionsService();
    pubDirectionsRenderer = new google.maps.DirectionsRenderer({
        map: pubRouteMap,
        suppressMarkers: false,
        polylineOptions: { strokeColor: '#6366f1', strokeWeight: 6, strokeOpacity: 0.85 }
    });
    pubRouteMapInitialized = true;
}
function calculatePubRoute() {
    const userLoc = PharmaSearch.userLoc;
    if (!userLoc || !currentRouteTarget) {
        document.getElementById('routeCalcLoading').style.display = 'none';
        return;
    }
    document.getElementById('routeCalcLoading').style.display = 'flex';
    document.getElementById('routeSummarySection').style.display = 'none';
    const travelModeMap = {
        'DRIVING': google.maps.TravelMode.DRIVING,
        'WALKING': google.maps.TravelMode.WALKING,
        'TRANSIT': google.maps.TravelMode.TRANSIT
    };
    pubDirectionsService.route({
        origin: new google.maps.LatLng(userLoc.lat, userLoc.lng),
        destination: new google.maps.LatLng(currentRouteTarget.lat, currentRouteTarget.lng),
        travelMode: travelModeMap[currentTravelMode],
        provideRouteAlternatives: false,
        unitSystem: google.maps.UnitSystem.METRIC,
        region: 'tr'
    }, (response, status) => {
        document.getElementById('routeCalcLoading').style.display = 'none';
        if (status === 'OK') {
            pubDirectionsRenderer.setDirections(response);
            const leg = response.routes[0].legs[0];
            document.getElementById('routeDuration').textContent = leg.duration.text;
            document.getElementById('routeDistance').textContent = leg.distance.text;
            const stepsList = document.getElementById('routeStepsList');
            stepsList.innerHTML = '';
            leg.steps.forEach((step, i) => {
                const clean = step.instructions.replace(/<[^>]*>/g, '');
                const div = document.createElement('div');
                div.className = 'route-step';
                div.innerHTML = `
                        <div class="step-num">${i + 1}</div>
                        <div class="step-text">${clean}<div class="step-dist">${step.distance.text} · ${step.duration.text}</div></div>`;
                div.addEventListener('click', () => { pubRouteMap.panTo(step.start_location); pubRouteMap.setZoom(17); });
                stepsList.appendChild(div);
            });
            document.getElementById('routeSummarySection').style.display = 'flex';
            const mode = currentTravelMode.toLowerCase();
            document.getElementById('openGoogleMapsBtn').href =
                `https://www.google.com/maps/dir/?api=1&origin=${userLoc.lat},${userLoc.lng}&destination=${currentRouteTarget.lat},${currentRouteTarget.lng}&travelmode=${mode}`;
        } else {
            document.getElementById('routeStepsList').innerHTML =
                `<div style="padding:2rem;text-align:center;color:var(--metin-uc);">Rota bulunamadı (${status}).<br><small>Farklı ulaşım modu deneyin.</small></div>`;
            document.getElementById('routeSummarySection').style.display = 'flex';
            document.getElementById('routeDuration').textContent = '—';
            document.getElementById('routeDistance').textContent = '—';
        }
    });
}
const travelModeTabs = document.getElementById('travelModeTabs');
if (travelModeTabs) {
    travelModeTabs.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-mode]');
        if (!btn) return;
        document.querySelectorAll('.travel-mode-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        currentTravelMode = btn.dataset.mode;
        if (currentRouteTarget && pubRouteMapInitialized) calculatePubRoute();
    });
}

function closeRouteModal() {
    const modal = document.getElementById('routeModal');
    if (modal) modal.classList.remove('open');
    document.body.style.overflow = '';
}

const closeBtn = document.getElementById('closeRouteModal');
if (closeBtn) closeBtn.addEventListener('click', closeRouteModal);

const rModal = document.getElementById('routeModal');
if (rModal) {
    rModal.addEventListener('click', (e) => {
        if (e.target === e.currentTarget) closeRouteModal();
    });
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeRouteModal();
});

const clearBtn = document.getElementById('clearRouteBtn');
if (clearBtn) {
    clearBtn.addEventListener('click', () => {
        if (pubDirectionsRenderer) pubDirectionsRenderer.setMap(null);
        pubRouteMapInitialized = false;
        closeRouteModal();
    });
}
const contactForm = document.getElementById('contactForm');
if (contactForm) {
    contactForm.addEventListener('submit', async function (e) {
        e.preventDefault();
        const btn = document.getElementById('contactSubmit');
        const originalText = btn.innerHTML;
        btn.innerHTML = `<div class="spinner" style="width:20px;height:20px;border-width:2px;border-top-color:#fff;margin:0 auto;"></div> Gönderiliyor...`;
        btn.disabled = true;
        const formData = new FormData(this);
        try {
            const response = await fetch('api/contact.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.basari) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'success', text: result.mesaj, background: 'var(--arkaplan-kart)' });
                } else {
                    alert(result.mesaj);
                }
                contactForm.reset();
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({ icon: 'error', text: result.mesaj, background: 'var(--arkaplan-kart)' });
                } else {
                    alert(result.mesaj);
                }
            }
        } catch (err) {
            console.error(err);
            alert("Mesaj gönderilirken bir hata oluştu.");
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    });
}
let activeRezData = null;
function openRezervasyonModal(eczaneId, eczaneAdi, ilacId, ilacAdi) {
    if (!window.PHARMA_CONFIG || !window.PHARMA_CONFIG.isLoggedIn) {
        window.location.href = (window.PHARMA_CONFIG ? window.PHARMA_CONFIG.baseUrl : '') + '/auth/login.php';
        return;
    }
    activeRezData = { eczaneId, ilacId };
    const info = document.getElementById('rezInfo');
    info.innerHTML = `<strong>${eczaneAdi}</strong> eczanesinden <strong>${ilacAdi}</strong> ilacını ayırtmak üzeresiniz. Eczacı onayladığında tarafınıza bildirim iletilecektir.`;
    document.getElementById('rezModal').style.display = 'flex';
}
function closeRezModal() {
    document.getElementById('rezModal').style.display = 'none';
    document.getElementById('rezNot').value = '';
}
document.getElementById('rezOnayBtn')?.addEventListener('click', async function () {
    const btn = this;
    const originalText = btn.innerText;
    const userNot = document.getElementById('rezNot').value;
    btn.disabled = true;
    btn.innerHTML = `<div class="spinner" style="width:20px;height:20px;border-width:2px;border-top-color:#fff;margin:0 auto;"></div> İletiliyor...`;
    try {
        const formData = new FormData();
        formData.append('eczane_id', activeRezData.eczaneId);
        formData.append('ilac_id', activeRezData.ilacId);
        formData.append('not', userNot);
        formData.append('csrf_token', window.PHARMA_CONFIG ? window.PHARMA_CONFIG.csrfToken : '');
        const res = await fetch((window.PHARMA_CONFIG ? window.PHARMA_CONFIG.baseUrl : '') + '/api/reservations.php?action=create', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await res.json();
        if (data.basari) {
            closeRezModal();
            if (typeof ToastManager !== 'undefined') {
                ToastManager.show(data.mesaj, 'basari');
            } else {
                alert(data.mesaj);
            }
        } else {
            alert(data.mesaj || 'Hata oluştu.');
        }
    } catch (err) {
        console.error(err);
        alert('İşlem sırasında bir hata oluştu.');
    } finally {
        btn.disabled = false;
        btn.innerText = originalText;
    }
});

// --- Core Application Initialization ---
const App = {
    init() {
        try {
            // Instantiate Global Managers
            window.pharmaNotifications = new NotificationManager();
            window.pharmaDropdown = new DropdownManager();
            window.pharmaRegister = new RegisterManager();

            // Initialize Class-based components
            new SidebarManager();
            ModalManager.initEvents();

            // Initialize Functional components
            if (typeof PharmaSearch !== 'undefined') PharmaSearch.init();
            if (typeof PubRoute !== 'undefined') PubRoute.init();

            this.bindGlobalEvents();
            console.log('PharmaLink App initialized successfully');
        } catch (err) {
            console.error('App initialization error:', err);
        }
    },
    bindGlobalEvents() {
        // Confirm Dialogs
        document.querySelectorAll('[data-onay]').forEach(btn => {
            btn.addEventListener('click', e => {
                e.preventDefault();
                const doAction = () => {
                    if (btn.hasAttribute('href') && btn.getAttribute('href') && btn.getAttribute('href') !== '#') {
                        window.location.href = btn.getAttribute('href');
                    } else if (btn.closest('form')) {
                        btn.closest('form').submit();
                    }
                };
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Emin misiniz?',
                        text: btn.dataset.onay,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: 'var(--renk-tehlike)',
                        cancelButtonColor: 'var(--kenar-rengi)',
                        confirmButtonText: 'Evet, Onaylıyorum',
                        cancelButtonText: 'Vazgeç',
                        background: 'var(--arkaplan-kart)',
                        color: 'var(--metin-birincil)'
                    }).then((result) => {
                        if (result.isConfirmed) doAction();
                    });
                } else {
                    if (confirm(btn.dataset.onay)) doAction();
                }
            });
        });

        // SSS Logic
        document.querySelectorAll('.sss-soru').forEach(item => {
            item.addEventListener('click', () => {
                item.parentElement.classList.toggle('aktif');
            });
        });

        // History Cleanups
        if (window.PHARMA_CONFIG && window.PHARMA_CONFIG.searchActive) {
            if (window.history.replaceState) {
                const cleanUrl = window.location.protocol + "//" + window.location.host + window.location.pathname;
                window.history.replaceState({ path: cleanUrl }, '', cleanUrl);
            }
        }
    }
};

document.addEventListener('DOMContentLoaded', () => App.init());
