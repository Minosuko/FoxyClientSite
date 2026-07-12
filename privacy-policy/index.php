<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | Foxy Client</title>
    <meta name="description" content="Foxy Client Privacy Policy - Learn how we collect, use, and protect your personal data.">
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="bg-overlay"></div>
    <div class="bg-mesh"></div>

    <nav>
        <div class="logo-container">
            <img src="../assets/logo.png" alt="Foxy Logo">
            <span class="logo-text">FOXY CLIENT</span>
        </div>
        <ul class="nav-links">
            <li><a href="../#home">Home</a></li>
            <li><a href="../#downloads">Downloads</a></li>
            <li><a href="../#faq">FAQ</a></li>
            <li><a href="../#support">Support</a></li>
            <li><a href="../accounts/login/">Account</a></li>
            <li>
                <button id="themeToggle" class="theme-toggle" aria-label="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </li>
        </ul>
        <a href="../#downloads" class="btn btn-primary" style="padding: 10px 25px; font-size: 0.85rem;">PLAY NOW</a>
    </nav>

    <header class="hero" style="height: auto; min-height: 100vh; padding: 140px 5% 80px;">
        <div class="hero-content" style="max-width: 900px; text-align: left; padding: 50px;">
            <h1 style="font-size: 2.5rem; margin-bottom: 10px; text-align: center;">Privacy Policy</h1>
            <p style="text-align: center; font-size: 0.9rem; color: var(--text-muted); margin-bottom: 40px;">Last updated: July 12, 2026</p>

            <div style="color: var(--text-muted); font-size: 0.95rem; line-height: 1.8;">
                <h2 style="color: var(--text-main); font-size: 1.3rem; margin: 30px 0 15px;">1. Information We Collect</h2>
                <p>When you create an account on Foxy Client, we collect the following information:</p>
                <ul style="margin: 10px 0 10px 20px;">
                    <li>Username</li>
                    <li>Email address</li>
                    <li>Hashed password (we never store plain-text passwords)</li>
                    <li>Minecraft profile data (UUID, skin/cape textures) that you voluntarily upload</li>
                </ul>

                <h2 style="color: var(--text-main); font-size: 1.3rem; margin: 30px 0 15px;">2. How We Use Your Information</h2>
                <p>We use your information solely to:</p>
                <ul style="margin: 10px 0 10px 20px;">
                    <li>Provide and maintain your Foxy Client account</li>
                    <li>Authenticate you via our Yggdrasil-compatible auth server</li>
                    <li>Serve your uploaded skins and capes to the Minecraft client</li>
                    <li>Send important account-related emails (verification, password reset)</li>
                    <li>Improve and troubleshoot our services</li>
                </ul>

                <h2 style="color: var(--text-main); font-size: 1.3rem; margin: 30px 0 15px;">3. Data Storage & Security</h2>
                <p>Your data is stored securely on our servers. Passwords are hashed using industry-standard algorithms. We implement reasonable security measures including encryption in transit (TLS) and at rest to protect your personal information.</p>

                <h2 style="color: var(--text-main); font-size: 1.3rem; margin: 30px 0 15px;">4. Data Sharing</h2>
                <p>We do not sell, trade, or share your personal information with third parties except as required by law. Your Minecraft profile data (skins, capes, username, UUID) is publicly accessible via our API as required by the Yggdrasil authentication protocol, which is necessary for the launcher to function.</p>

                <h2 style="color: var(--text-main); font-size: 1.3rem; margin: 30px 0 15px;">5. Third-Party Services</h2>
                <p>Our site uses Cloudflare Turnstile for bot protection. Turnstile may collect anonymous browsing data to function. We also use Google Fonts and Font Awesome via CDN, which may log your IP address in accordance with their respective privacy policies.</p>

                <h2 style="color: var(--text-main); font-size: 1.3rem; margin: 30px 0 15px;">6. Your Rights</h2>
                <p>You have the right to:</p>
                <ul style="margin: 10px 0 10px 20px;">
                    <li>Access the personal data we hold about you</li>
                    <li>Request correction or deletion of your data</li>
                    <li>Export your data</li>
                    <li>Delete your account at any time</li>
                </ul>
                <p>To exercise these rights, contact us at <a href="mailto:support@foxyclient.qzz.io" style="color: var(--text-main);">support@foxyclient.qzz.io</a>.</p>

                <h2 style="color: var(--text-main); font-size: 1.3rem; margin: 30px 0 15px;">7. Cookies</h2>
                <p>We use essential session cookies for authentication purposes. No tracking or advertising cookies are used. The theme preference is stored in your browser's local storage and is not sent to our servers.</p>

                <h2 style="color: var(--text-main); font-size: 1.3rem; margin: 30px 0 15px;">8. Changes to This Policy</h2>
                <p>We may update this Privacy Policy from time to time. We will notify users of material changes via email or through the website. Continued use of the service after changes constitutes acceptance of the updated policy.</p>

                <h2 style="color: var(--text-main); font-size: 1.3rem; margin: 30px 0 15px;">9. Contact</h2>
                <p>If you have questions about this Privacy Policy, please contact us at <a href="mailto:support@foxyclient.qzz.io" style="color: var(--text-main);">support@foxyclient.qzz.io</a> or join our <a href="https://discord.gg/HhRDbGQHXz" style="color: var(--text-main);">Discord server</a>.</p>
            </div>
        </div>
    </header>

    <footer>
        <div class="logo-text" style="font-size: 1.2rem; margin-bottom: 20px;">FOXY CLIENT</div>
        <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 600px; margin: 0 auto;">&copy; 2026 Foxy Client Dev. Not an official Minecraft product. Not approved by or associated with Mojang or Microsoft.</p>
    </footer>

    <script>
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = themeToggle.querySelector('i');
        const savedTheme = localStorage.getItem('theme');
        const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (savedTheme === 'dark' || (!savedTheme && systemPrefersDark)) {
            document.documentElement.setAttribute('data-theme', 'dark');
            themeIcon.classList.replace('fa-moon', 'fa-sun');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
            themeIcon.classList.replace('fa-sun', 'fa-moon');
        }
        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            if (newTheme === 'dark') {
                themeIcon.classList.replace('fa-moon', 'fa-sun');
            } else {
                themeIcon.classList.replace('fa-sun', 'fa-moon');
            }
        });
        const nav = document.querySelector('nav');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) nav.classList.add('scrolled');
            else nav.classList.remove('scrolled');
        });
    </script>
</body>

</html>
