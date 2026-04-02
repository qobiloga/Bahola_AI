// Theme Toggle Logic
document.addEventListener('DOMContentLoaded', () => {
    const theme = localStorage.getItem('theme') || 'dark';
    document.documentElement.setAttribute('data-theme', theme);
    
    // We update icons for all buttons if there are multiple
    const themeToggles = document.querySelectorAll('.theme-toggle-btn');
    
    themeToggles.forEach(btn => {
        updateThemeIcon(btn, theme);
        btn.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            
            themeToggles.forEach(b => updateThemeIcon(b, newTheme));
        });
    });
});

// Run immediately to avoid flashing
const savedTheme = localStorage.getItem('theme') || 'dark';
document.documentElement.setAttribute('data-theme', savedTheme);

function updateThemeIcon(btn, theme) {
    const sun = btn.querySelector('.sun-icon');
    const moon = btn.querySelector('.moon-icon');
    if (sun && moon) {
        if (theme === 'dark') {
            sun.style.display = 'none';
            moon.style.display = 'block';
        } else {
            sun.style.display = 'block';
            moon.style.display = 'none';
        }
    }
}
