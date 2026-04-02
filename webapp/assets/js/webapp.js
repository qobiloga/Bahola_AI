// Initialize Telegram WebApp
const tg = window.Telegram?.WebApp;

if (tg) {
    tg.ready();
    tg.expand();
    
    // Set theme colors
    document.documentElement.style.setProperty('--bg-color', tg.backgroundColor || '#ffffff');
    document.documentElement.style.setProperty('--text-color', tg.textColor || '#000000');
    
    // Configure main button if needed
    tg.MainButton.setParams({
        color: tg.themeParams.button_color || '#2481cc',
        text_color: tg.themeParams.button_text_color || '#ffffff'
    });
}

const WebAppHelper = {
    // Show alert in TG style
    alert: (message) => {
        if (tg) {
            tg.showAlert(message);
        } else {
            alert(message);
        }
    },
    
    // Show confirmation
    confirm: (message, callback) => {
        if (tg) {
            tg.showConfirm(message, callback);
        } else {
            if (confirm(message)) callback(true);
        }
    },
    
    // Vibrations
    haptic: (style = 'light') => {
        if (tg && tg.HapticFeedback) {
            tg.HapticFeedback.impactOccurred(style);
        }
    },
    
    // Navigation
    to: (path) => {
        WebAppHelper.haptic();
        window.location.href = path;
    },
    
    // Set data in storage
    saveRole: (role) => {
        localStorage.setItem('webapp_role', role);
        if (tg && tg.CloudStorage) {
            tg.CloudStorage.setItem('role', role);
        }
    },
    
    getRole: () => {
        return localStorage.getItem('webapp_role');
    },

    // User Info
    getUser: () => {
        if (tg && tg.initDataUnsafe && tg.initDataUnsafe.user) {
            return tg.initDataUnsafe.user;
        }
        // Mock user for testing in browser
        return {
            id: 12345678,
            first_name: 'Test',
            last_name: 'User',
            username: 'tester'
        };
    }
};

// Auto-expand theme variables if TG updates
if (tg) {
    tg.onEvent('themeChanged', () => {
        document.documentElement.style.setProperty('--bg-color', tg.backgroundColor);
        document.documentElement.style.setProperty('--text-color', tg.textColor);
    });
}
