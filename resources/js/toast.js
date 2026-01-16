export default (Alpine) => {
    Alpine.store('toast', {
        notifications: [],
        nextId: 1,

        add(message, variant = 'neutral', duration = null) {
            // 1. Normalize global variants
            // Ensure variant is lower case string
            let normalized = String(variant || 'neutral').toLowerCase();

            // Map invalid/legacy variants to standard
            if (normalized === 'error') normalized = 'danger';
            if (normalized === 'warning') normalized = 'info'; // Premium calm UI rule
            
            // Allow only specific variants, fallback to neutral
            const allowed = ['neutral', 'info', 'success', 'danger'];
            if (!allowed.includes(normalized)) {
                normalized = 'neutral';
            }

            const id = this.nextId++;
            
            // Default durations
            const defaultDurations = {
                danger: 6000,
                success: 3500,
                info: 4000,
                neutral: 3000
            };

            const finalDuration = duration || defaultDurations[normalized] || 4000;

            this.notifications.push({ id, message, variant: normalized });

            setTimeout(() => {
                this.remove(id);
            }, finalDuration);
        },

        remove(id) {
            this.notifications = this.notifications.filter(n => n.id !== id);
        }
    });

    // Global helper
    window.toast = function (variant, message, duration = null) {
        // Logic is now centralized in store.add()
        if (Alpine && Alpine.store && Alpine.store('toast')) {
            Alpine.store('toast').add(message, variant, duration);
        } else {
            console.error('Toast store not initialized', variant, message);
        }
    };

    // Global Event Listener
    if (!window.__toastListenerBound) {
        window.addEventListener('toast', (event) => {
            const detail = event.detail || {};
            const message = detail.message;
            if (!message) return;

            const variant = detail.variant || 'neutral';
            
            if (Alpine && Alpine.store && Alpine.store('toast')) {
                Alpine.store('toast').add(message, variant);
            }
        });
        window.__toastListenerBound = true;
    }
};
