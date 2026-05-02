'use strict';

class ApiService {
    static async post(url, data = {}) {
        const formData = new FormData();
        for (const [key, value] of Object.entries(data)) {
            formData.append(key, value);
        }

        // CSRF Token Ekle
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
        
        // Dışarı tıklanınca kapat
        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target) && !this.list.contains(e.target)) {
                this.close();
            }
        });

        // Form gönderimini kontrol et
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
        // Eğer liste zaten açıksa "Aranıyor" animasyonunu sessizce yapabiliriz veya hiç göstermeyiz
        // Böylece her harf basışında liste silinip "Aranıyor" yazısı gelmez (Flicker engelleme)
        if (this.list.style.display !== 'block' || this.list.innerHTML === '') {
            this.list.innerHTML = `<div class="autocomplete-item loading">Aranıyor...</div>`;
            this.list.style.display = 'block';
        }

        const baseUrl = window.APP_URL || '';
        const currentPath = window.location.pathname;
        // Yeni api/ klasör yapısına uygun yol
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
            // 'mousedown' kullanımı 'click'ten daha güvenlidir (blur'dan önce çalışır)
            div.addEventListener('mousedown', (e) => {
                e.preventDefault(); // Input'un blur olmasını engelleyebiliriz veya seçimi hemen yapabiliriz
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

        // ── KONUM İZNİ ÖN ONAY POP-UP ─────────────────────────────
        Swal.fire({
            title: 'Eczaneleri Bulalım mı?',
            text: 'Size en yakın eczaneleri ve ilaç stoklarını gösterebilmemiz için konum bilginize ihtiyaç duyuyoruz.',
            icon: 'info',
            iconColor: 'var(--renk-ikincil)',
            showCancelButton: true,
            confirmButtonText: 'Konumu Kullan',
            cancelButtonText: 'Konumsuz Ara',
            confirmButtonColor: 'var(--renk-birincil)',
            cancelButtonColor: 'var(--kenar-rengi)',
            background: 'var(--arkaplan-kart)',
            color: 'var(--metin-birincil)',
            reverseButtons: true,
            customClass: {
                popup: 'kart',
                confirmButton: 'btn btn-birincil',
                cancelButton: 'btn btn-gri'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Kullanıcı izin verdi, tarayıcı promptunu tetikle
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
                            // Tarayıcıdan reddetti veya hata oluştu
                            this.form.submit();
                        },
                        { timeout: 5000 }
                    );
                } else {
                    this.form.submit();
                }
            } else {
                // Kullanıcı "Konumsuz Ara" dedi
                this.form.submit();
            }
        });
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

        // Sayfa yüklendiğinde, eğer daha önce daraltılmışsa durumu koru (Masaüstü için)
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

        // Ekran boyutu değiştiğinde durum yönetimi
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

class NotificationManager {
    static init() {
        // Global Alert Override
        window.alert = (msg) => this.show(msg, 'bilgi');
        
        // Toast config
        this.Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            background: 'var(--arkaplan-kart)',
            color: 'var(--metin-birincil)',
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
    }

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
            iconColor: (swalType === 'success') ? 'var(--renk-basari)' : (swalType === 'error' ? 'var(--renk-tehlike)' : 'var(--renk-ikincil)'),
            confirmButtonText: 'Tamam',
            confirmButtonColor: 'var(--renk-ikincil)',
            background: 'var(--arkaplan-kart)',
            color: 'var(--metin-birincil)',
            backdrop: `rgba(0,0,0,0.4)`,
            customClass: {
                popup: 'kart', 
                confirmButton: 'btn btn-ikincil'
            }
        });
    }

    static toast(message, type = 'basari') {
        const dmap = {
            'basari': 'success',
            'tehlike': 'error',
            'uyari': 'warning',
            'bilgi': 'info'
        };
        this.Toast.fire({
            icon: dmap[type] || 'success',
            title: message
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
        // ESC tuşuyla kapatma
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-arkaplan.acik').forEach(m => {
                    m.classList.remove('acik');
                    document.body.style.overflow = '';
                });
            }
        });

        // Arkaplana tıklayınca kapatma
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
            e.stopPropagation(); // Diğer tıklama olaylarını durdur
            const isShowing = this.menu.classList.contains('goster');
            
            // Eğer açıksa kapat, kapalıysa aç
            if (isShowing) {
                this.close();
            } else {
                this.open();
            }
        });

        // Dışarı tıklanınca kapatma
        document.addEventListener('click', (e) => {
            if (this.menu.classList.contains('goster')) {
                // Eğer tıklanan yer dropdown menünün içi veya butonu değilse kapat
                if (!this.menu.contains(e.target) && e.target !== this.toggleBtn) {
                    this.close();
                }
            }
        });
        
        // Menü içindeki linklere tıklayınca kapat (opsiyonel ama iyi bir pratik)
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

                // Inputu güncelle
                this.tipInput.value = tip;

                // Bölümü göster/gizle
                this.eczaneBolumu.style.display = (tip === 'eczane') ? 'block' : 'none';

                // Aktif klasını güncelle
                this.tabButonlar.forEach(b => b.classList.remove('aktif'));
                btn.classList.add('aktif');
            });
        });
    }
}

class ThemeManager {
    constructor() {
        this.init();
    }

    init() {
        this.insertToggleButton();
        this.bindEvents();
    }

    insertToggleButton() {
        if (document.getElementById('theme-toggle-btn')) return;

        const btn = document.createElement('button');
        btn.id = 'theme-toggle-btn';
        btn.className = 'theme-toggle-floating';
        btn.setAttribute('aria-label', 'Karanlık Modu Aç/Kapat');
        btn.title = 'Karanlık/Aydınlık Mod';

        // Sun & Moon icons
        btn.innerHTML = `
            <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
            <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="5"></circle>
                <line x1="12" y1="1" x2="12" y2="3"></line>
                <line x1="12" y1="21" x2="12" y2="23"></line>
                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                <line x1="1" y1="12" x2="3" y2="12"></line>
                <line x1="21" y1="12" x2="23" y2="12"></line>
                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
            </svg>
        `;

        document.body.appendChild(btn);
    }

    bindEvents() {
        const btn = document.getElementById('theme-toggle-btn');
        if (!btn) return;

        btn.addEventListener('click', () => {
            const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
            if (isDark) {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
            }
        });
    }
}

window.modalAc = ModalManager.open;
window.modalKapat = ModalManager.close;

window.gostermeBildirim = NotificationManager.show;
window.toast = NotificationManager.toast;

// Tablo Filtreleme Bridge
window.tabloFiltrele = function(aramaInputId, tabloId) {
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

// Stok Güncelleme Bridge
window.stokGuncelle = async function(stokId, yeniDurum, btn) {
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
            NotificationManager.show('Stok durumu güncellendi.', 'basari');
        } else {
            NotificationManager.show(data.mesaj || 'Hata oluştu.', 'tehlike');
        }
    } catch (err) {
        NotificationManager.show('İstek başarısız oldu.', 'tehlike');
    } finally {
        btn.disabled = false;
    }
};

// Nöbetçi Durumu Toggle Bridge
window.nobetciToggle = async function(checkbox) {
    const isChecked = checkbox.checked ? 1 : 0;
    const label = checkbox.closest('label').querySelector('span:last-child');
    const toggle = checkbox.closest('.toggle-switch');
    
    // Geçici olarak pasifleştir
    checkbox.disabled = true;
    if(toggle) toggle.style.opacity = '0.5';

    try {
        const data = await ApiService.post('/api/inventory.php', {
            islem: 'nobetci_guncelle',
            durum: isChecked
        });

        if (data.basari) {
            if (label) label.textContent = isChecked ? 'Nöbetçiyim' : 'Nöbetçi değilim';
            NotificationManager.show(data.mesaj, 'basari');
            
            // Eğer sayfada rozet varsa onu da güncelle (Header veya Dashboard için)
            const rozet = document.querySelector('.sayfa-baslik .rozet');
            if (rozet) {
                rozet.className = isChecked ? 'rozet rozet-yesil' : 'rozet rozet-gri';
                rozet.textContent = isChecked ? '🔔 Nöbetçi' : 'Nöbet Yok';
            }
        } else {
            checkbox.checked = !isChecked; // Geri al
            NotificationManager.show(data.mesaj || 'Hata oluştu.', 'tehlike');
        }
    } catch (err) {
        checkbox.checked = !isChecked; // Geri al
        NotificationManager.show('Bağlantı hatası.', 'tehlike');
    } finally {
        checkbox.disabled = false;
        if(toggle) toggle.style.opacity = '1';
    }
};

document.addEventListener('DOMContentLoaded', () => {
    // Sınıf örmeklerini oluştur
    NotificationManager.init();

    new SidebarManager();
    new DropdownManager();
    new RegisterManager();
    new ThemeManager();
    ModalManager.initEvents();

    // Onay gerektiren butonları bağla (SweetAlert2 ile)
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
                    // Eğer href veya event varsa yönlendir/submit et
                    if(btn.href) {
                        window.location.href = btn.href;
                    } else if (btn.closest('form')) {
                        btn.closest('form').submit();
                    }
                }
            });
        });
    });

    // Filtreleme bağla (varsa)
    window.tabloFiltrele('aramaInput', 'anaTablosu');

    // ============================================
    // GERÇEK ZAMANLI BİLDİRİM (POLLING)
    // ============================================
    // Yalnızca kullanıcı giriş yapmışsa (Rol varsa body class'ından anlarız) çalışır.
    const isAppBody = document.body.classList.contains('app-body');
    if (isAppBody) {
        let lastNotificationStr = '';
        
        const checkNotifications = async () => {
            try {
                const res = await fetch(window.APP_URL + '/api/notifications.php');
                if (res.ok) {
                    const data = await res.json();
                    if (data.basari && data.bildirimVar) {
                        const notifStr = data.tip + '|' + data.mesaj;
                        // Sadece yeni/farklı bir bildirim ise göster (tekrarlamayı önle)
                        if (notifStr !== lastNotificationStr) {
                            NotificationManager.show(data.mesaj, data.tip);
                            lastNotificationStr = notifStr;
                        }
                    }
                }
            } catch (err) {
                console.error("Polling Error:", err);
            }
        };

        // İlk kontrolü 5 saniye sonra yap, sonra her 30 saniyede bir
        setTimeout(() => {
            checkNotifications();
            setInterval(checkNotifications, 30000);
        }, 5000);
    }

    // ============================================
    // İLAÇ ARAMA AUTOCOMPLETE
    // ============================================
    if (document.getElementById('drugSearchInput')) {
        new SearchAutocomplete('#drugSearchInput', '#autocompleteList', '#publicSearchForm');
    }
});

