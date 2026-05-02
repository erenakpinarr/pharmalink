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
        // Clear and prepare hint container
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

        // Form submit integration
        const form = this.input.closest('form');
        if (form) {
            form.addEventListener('submit', (e) => {
                if (!this.isValid()) {
                    e.preventDefault();
                    this.hintContainer.style.display = 'block';
                    this.input.focus();
                    
                    // Visual shake feedback
                    this.hintContainer.style.animation = 'none';
                    this.hintContainer.offsetHeight; // trigger reflow
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

        // Auto-hide if all valid (Smart behavior)
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

// Auto-init for registration page
document.addEventListener('DOMContentLoaded', () => {
    const regPass = document.getElementById('reg_sifre');
    if (regPass) {
        new PasswordValidator('#reg_sifre', '#passwordRulesHint');
    }
});
