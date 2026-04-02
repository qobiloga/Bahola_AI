/**
 * Ustoz ko'makchi - Landing Page JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
    initNavbar();
    initCounters();
    initDemoTabs();
    initAIDemo();
    initScrollAnimations();
});

/* ============================================
   Navbar Scroll Effect
   ============================================ */
function initNavbar() {
    const navbar = document.getElementById('navbar');
    const navToggle = document.getElementById('navToggle');
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Mobile toggle
    if (navToggle) {
        navToggle.addEventListener('click', () => {
            const links = document.getElementById('navLinks');
            links.classList.toggle('open');
            navToggle.classList.toggle('active');
        });

        // Close menu when clicking links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                const links = document.getElementById('navLinks');
                links.classList.remove('open');
                navToggle.classList.remove('active');
            });
        });
    }

    // Smooth scroll for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(link => {
        link.addEventListener('click', (e) => {
            const href = link.getAttribute('href');
            if (href === '#') return;
            
            const target = document.querySelector(href);
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });
}

/* ============================================
   Animated Counters
   ============================================ */
function initCounters() {
    const counters = document.querySelectorAll('[data-count]');
    const options = { threshold: 0.5 };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, options);

    counters.forEach(counter => observer.observe(counter));
}

function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-count'));
    const duration = 2000;
    const start = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - start;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
        const current = Math.round(target * eased);
        
        element.textContent = current;
        
        if (progress < 1) {
            requestAnimationFrame(update);
        }
    }
    
    requestAnimationFrame(update);
}

/* ============================================
   Demo Tabs
   ============================================ */
function initDemoTabs() {
    const tabs = document.querySelectorAll('.demo-tab');
    
    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const target = tab.getAttribute('data-tab');
            
            // Update tabs
            tabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            
            // Update panels
            document.querySelectorAll('.demo-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            document.getElementById(`demo-${target}`).classList.add('active');
        });
    });
}

/* ============================================
   AI Demo Animation
   ============================================ */
function initAIDemo() {
    // Hero mockup typing animation
    const aiDemoText = document.getElementById('aiDemoText');
    const typingIndicator = document.querySelector('.typing-indicator');
    const demoMessages = [
        "Natijalar tahlil qilindi...",
        "Natija: 16/20 ball (80%)",
        "Zaif mavzu: Trigonometriya",
        "Tavsiya: sin, cos funksiyalarini qayta o'rganing"
    ];
    
    let msgIndex = 0;
    function typeMessage() {
        if (msgIndex < demoMessages.length) {
            const msg = demoMessages[msgIndex];
            if (typingIndicator) typingIndicator.style.display = 'none';
            
            let charIndex = 0;
            if (aiDemoText) {
                aiDemoText.textContent = '';
                const interval = setInterval(() => {
                    aiDemoText.textContent += msg[charIndex];
                    charIndex++;
                    if (charIndex >= msg.length) {
                        clearInterval(interval);
                        msgIndex++;
                        setTimeout(typeMessage, 2000);
                    }
                }, 40);
            }
        } else {
            msgIndex = 0;
            if (typingIndicator) typingIndicator.style.display = 'flex';
            if (aiDemoText) aiDemoText.textContent = '';
            setTimeout(typeMessage, 3000);
        }
    }
    
    setTimeout(typeMessage, 2000);

    // Animate score ring
    animateScoreRing();

    // Test demo button
    if (runTestBtn) {
        runTestBtn.addEventListener('click', runTestDemoAnimation);
    }
}

function animateScoreRing() {
    const progress = document.getElementById('scoreProgress');
    const scoreValue = document.getElementById('scoreValue');
    const correctCount = document.getElementById('correctCount');
    const wrongCount = document.getElementById('wrongCount');
    
    if (!progress) return;

    const targetPercent = 80;
    const circumference = 2 * Math.PI * 42; // r=42
    const offset = circumference - (targetPercent / 100) * circumference;
    
    setTimeout(() => {
        progress.style.transition = 'stroke-dashoffset 2s ease';
        progress.style.strokeDashoffset = offset;
        
        // Animate number
        animateValue(scoreValue, 0, targetPercent, 2000, '%');
        animateValue(correctCount, 0, 16, 1500);
        animateValue(wrongCount, 0, 4, 1500);
    }, 1000);
}

function animateValue(element, start, end, duration, suffix = '') {
    if (!element) return;
    const startTime = performance.now();
    
    function update(currentTime) {
        const elapsed = currentTime - startTime;
        const progress = Math.min(elapsed / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3);
        const current = Math.round(start + (end - start) * eased);
        element.textContent = current + suffix;
        
        if (progress < 1) requestAnimationFrame(update);
    }
    
    requestAnimationFrame(update);
}

function runTestDemoAnimation() {
    const output = document.getElementById('demoAiOutput');
    if (!output) return;

    // Show loading
    output.innerHTML = `
        <div class="ai-loading">
            <div class="ai-spinner"></div>
            <span>AI xato javoblarni tahlil qilmoqda...</span>
        </div>
    `;

    // Step 1: Error analysis
    setTimeout(() => {
        output.innerHTML = `
            <div class="ai-result">
                <div class="ai-result-item error-analysis" style="animation-delay: 0s">
                    <strong>❌ Savol 2 — sin(90°)</strong><br>
                    Talaba javobi: 0 | To'g'ri javob: 1<br>
                    <em>Xato sababi: Trigonometrik funksiyalarning asosiy qiymatlari o'zlashtirilmagan.</em>
                </div>
                <div class="ai-result-item error-analysis" style="animation-delay: 0.3s">
                    <strong>❌ Savol 4 — ∫x dx</strong><br>
                    Talaba javobi: x | To'g'ri javob: x²/2 + C<br>
                    <em>Xato sababi: Integrallash qoidalari, xususan daraja formulasi noto'g'ri qo'llanilgan.</em>
                </div>
            </div>
        `;
    }, 1500);

    // Step 2: Recommendations
    setTimeout(() => {
        output.innerHTML += `
            <div class="ai-result" style="margin-top: 16px">
                <div class="ai-result-item recommendation" style="animation-delay: 0s">
                    <strong>📚 Tavsiya 1:</strong> "Trigonometriya asoslari" mavzusini qayta o'qing<br>
                    <em>sin, cos, tan funksiyalarining 0°, 30°, 45°, 60°, 90° dagi qiymatlarini yodlang.</em>
                </div>
                <div class="ai-result-item recommendation" style="animation-delay: 0.3s">
                    <strong>📚 Tavsiya 2:</strong> "Aniqmas integral" mavzusini takrorlang<br>
                    <em>∫xⁿ dx = xⁿ⁺¹/(n+1) + C formulasini mashq qiling.</em>
                </div>
            </div>
        `;
    }, 3500);
}



/* ============================================
   Scroll Animations (Intersection Observer)
   ============================================ */
function initScrollAnimations() {
    const animatedElements = document.querySelectorAll('.animate-fade-in-up, .animate-fade-in-down, .animate-scale-in');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, { threshold: 0.1 });

    animatedElements.forEach(el => {
        el.style.opacity = '0';
        el.style.animationPlayState = 'paused';
        observer.observe(el);
    });

    // Re-trigger hero animations immediately
    setTimeout(() => {
        document.querySelectorAll('.hero .animate-fade-in-up, .hero .animate-scale-in').forEach(el => {
            el.style.opacity = '1';
            el.style.animationPlayState = 'running';
        });
    }, 100);
}


