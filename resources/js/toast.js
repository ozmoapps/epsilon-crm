export default (Alpine) => {
    Alpine.store('toast', {
        notifications: [],
        nextId: 1,

        add(message, variant = 'info', duration = null) {
            const id = this.nextId++;
            // Default durations
            const defaultDurations = {
                danger: 6000,
                success: 3500,
                warning: 5000,
                info: 4000
            };

            const finalDuration = duration || defaultDurations[variant] || 4000;

            this.notifications.push({ id, message, variant });

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
        // Map legacy types if needed (e.g. error -> danger)
        if (variant === 'error') variant = 'danger';

        // Check if store exists to avoid errors
        if (Alpine && Alpine.store && Alpine.store('toast')) {
            Alpine.store('toast').add(message, variant, duration);
        } else {
            console.error('Toast store not initialized', variant, message);
        }
    };
};
