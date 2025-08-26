document.addEventListener('DOMContentLoaded', () => {
    function formatTime(minutes, includeSeconds = false) {
        const totalMs = minutes * 60 * 1000;
        const mins = Math.floor(totalMs / 60000);
        const secs = Math.floor((totalMs % 60000) / 1000);
        const hrs = Math.floor(mins / 60);
        const remMins = mins % 60;
        if (includeSeconds) {
            if (hrs > 0) return `${hrs}h ${remMins}m ${secs}s`;
            if (remMins > 0) return `${remMins}m ${secs}s`;
            return `${secs}s`;
        } else {
            if (hrs > 0 && remMins > 0) return `${hrs}h ${remMins}m`;
            if (hrs > 0) return `${hrs}h`;
            return `${remMins}m`;
        }
    }

    function updateCountdowns() {
        document.querySelectorAll('.countdown').forEach(el => {
            const end = new Date(el.dataset.end);
            const diff = end - new Date();
            const estimatedMs = parseFloat(el.dataset.estimatedMs);
            if (isNaN(diff) || diff <= 0) {
                el.textContent = 'Expired';
            } else {
                const mins = diff / 60000;
                const total = estimatedMs / 1000;
                const elapsed = total - diff / 1000;
                const progressRatio = Math.max(0, Math.min(1, elapsed / total));
                const barSegments = 20;
                const filledSegments = Math.round(barSegments * progressRatio);
                const stripes = Array.from({ length: filledSegments }, () => '<div></div>').join('');
                el.innerHTML = `
                    <div class="progress-bar">
                        <div class="progress-stripe">${stripes}</div>
                    </div>in progress <br> [${formatTime(mins, true)}]
                `;
            }
        });
    }

    setInterval(updateCountdowns, 1000);
    updateCountdowns();

    setInterval(() => {
        fetch('/api/reset_expired.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'global_reset=true'
        })
            .then(res => res.json())
            .then(result => {
                if (result.success && result.reset > 0) {
                    location.reload();
                }
            })
            .catch(err => console.error('Global reset failed', err));
    }, 10000);

    // Mobile-friendly enhancements for admin
    function addMobileEnhancements() {
        // Add tap highlighting removal for better mobile UX
        if ('ontouchstart' in window) {
            document.body.style.webkitTapHighlightColor = 'transparent';
            
            // Improve mobile form focus behavior
            const inputs = document.querySelectorAll('input, textarea');
            inputs.forEach(input => {
                input.addEventListener('focus', () => {
                    // Small delay to ensure viewport adjustment
                    setTimeout(() => {
                        if (input.scrollIntoViewIfNeeded) {
                            input.scrollIntoViewIfNeeded();
                        } else {
                            input.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }, 300);
                });
            });
        }
    }

    // Initialize mobile enhancements
    addMobileEnhancements();

    // Dark mode functionality
    function initDarkMode() {
        const toggle = document.getElementById('darkModeToggle');
        if (!toggle) return;

        // Check for saved theme preference or default to system preference
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        
        let isDark = false;
        if (savedTheme) {
            isDark = savedTheme === 'dark';
        } else {
            isDark = systemPrefersDark;
        }

        // Apply initial theme
        applyTheme(isDark);
        toggle.checked = isDark;

        // Toggle event listener
        toggle.addEventListener('change', () => {
            const newTheme = toggle.checked;
            applyTheme(newTheme);
            localStorage.setItem('theme', newTheme ? 'dark' : 'light');
        });

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            // Only apply system theme if user hasn't manually set a preference
            if (!localStorage.getItem('theme')) {
                applyTheme(e.matches);
                toggle.checked = e.matches;
            }
        });
    }

    function applyTheme(isDark) {
        const root = document.documentElement;
        if (isDark) {
            root.classList.add('dark-theme');
            root.classList.remove('light-theme');
        } else {
            root.classList.add('light-theme');
            root.classList.remove('dark-theme');
        }
    }

    // Initialize dark mode
    initDarkMode();
});
