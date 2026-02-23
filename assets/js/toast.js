/**
 * Simple Toast Notification System
 * Usage: showToast('Message', 'success' | 'error' | 'info')
 */
const Toast = {
    init() {
        if (!document.getElementById('toast-container')) {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'fixed top-4 right-4 z-[9999] flex flex-col gap-2';
            document.body.appendChild(container);

            // Add styles if not using Tailwind (or to ensure consistency)
            const style = document.createElement('style');
            style.innerHTML = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes fadeOut {
                    from { opacity: 1; }
                    to { opacity: 0; }
                }
                .toast-enter { animation: slideIn 0.3s ease-out forwards; }
                .toast-exit { animation: fadeOut 0.3s ease-in forwards; }
            `;
            document.head.appendChild(style);
        }
    },

    show(message, type = 'info') {
        this.init();

        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');

        let bgClass, icon;
        switch (type) {
            case 'success':
                bgClass = 'bg-green-600 text-white';
                icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                break;
            case 'error':
                bgClass = 'bg-red-600 text-white';
                icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
                break;
            default:
                bgClass = 'bg-blue-600 text-white';
                icon = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
        }

        toast.className = `${bgClass} shadow-lg rounded-lg px-4 py-3 flex items-center gap-3 min-w-[300px] max-w-md transform transition-all toast-enter`;
        toast.innerHTML = `
            ${icon}
            <span class="font-medium text-sm">${message}</span>
        `;

        container.appendChild(toast);

        // Auto remove
        setTimeout(() => {
            toast.classList.remove('toast-enter');
            toast.classList.add('toast-exit');
            toast.addEventListener('animationend', () => toast.remove());
        }, 3000);
    }
};

window.showToast = (msg, type) => Toast.show(msg, type);
