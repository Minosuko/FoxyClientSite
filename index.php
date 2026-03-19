<?php
header("X-Authlib-Injector-Api-Location: https://" . $_SERVER['HTTP_HOST'] . "/api/");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foxy Client</title>
    <meta name="description"
        content="Download Foxy Client - The most powerful and beautiful Minecraft launcher with native OpenGL GUI, smooth performance, and modern features.">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body>
    <div class="bg-overlay"></div>
    <div class="bg-mesh"></div>

    <nav>
        <div class="logo-container">
            <img src="assets/logo.png" alt="Foxy Logo">
            <span class="logo-text">FOXY CLIENT</span>
        </div>
        <ul class="nav-links">
            <li><a href="#home">Home</a></li>
            <li><a href="#downloads">Downloads</a></li>
            <li><a href="#faq">FAQ</a></li>
            <li><a href="#support">Support</a></li>
            <li><a href="accounts/login/">Account</a></li>
        </ul>
        <a href="#downloads" class="btn btn-primary" style="padding: 10px 25px; font-size: 0.85rem;">PLAY NOW</a>
    </nav>

    <header class="hero" id="home">
        <div class="hero-content">
            <h1>The Optimized Launcher</h1>
            <p>Foxy Client is a lightweight Minecraft launcher built for performance. Get more FPS, lower latency.</p>
            <div class="cta-group" style="display: flex; gap: 20px; justify-content: center;">
                <a href="#downloads" class="btn btn-primary">
                    <i class="fas fa-download"></i> DOWNLOAD NOW
                </a>
                <a href="#faq" class="btn btn-secondary">
                    LEARN MORE
                </a>
            </div>
        </div>
    </header>

    <section id="downloads">
        <div class="section-title">
            <h2>Downloads</h2>
            <div class="underline"></div>
        </div>
        <div class="card-grid">
            <div class="feature-card">
                <i class="fab fa-windows"></i>
                <h3>Windows Native</h3>
                <p>Full-featured installer for Windows 10 & 11. Optimized for native performance and stability.</p>
                <a href="https://github.com/Minosuko/FoxyClient/releases/latest/download/FoxyClient.exe" class="btn btn-primary" style="margin-top: 20px; width: 100%;">Download Installer</a>
            </div>
            <div class="feature-card" style="opacity: 0.7;">
                <i class="fab fa-java"></i>
                <h3>Universal Archive</h3>
                <p>Run Foxy Client on macOS or Linux with Java 21+. Portable, lightweight, and fast.</p>
                <button class="btn btn-secondary" style="margin-top: 20px; width: 100%; cursor: not-allowed;" disabled>COMING SOON</button>
            </div>
            <div class="feature-card">
                <i class="fas fa-satellite-dish"></i>
                <h3>Beta Access</h3>
                <p>Get the latest experimental optimization features before they hit the stable release channel.</p>
                <a href="https://discord.gg/HhRDbGQHXz" class="btn btn-secondary" style="margin-top: 20px; width: 100%;">Join Beta Program</a>
            </div>
        </div>
    </section>

    <section id="faq">
        <div class="section-title">
            <h2>FAQ</h2>
            <div class="underline"></div>
        </div>
        <div class="faq-container">
            <div class="faq-item">
                <div class="faq-question">
                    What is Foxy Client?
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    Foxy Client is a specialized Minecraft launcher built from the ground up with a native OpenGL GUI.
                    It offers superior performance, a modern tabbed interface, and advanced mod management features.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    Is Foxy Client free to use?
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    Yes! Foxy Client is free to download and use. We believe in providing the best Minecraft experience
                    to everyone without any cost.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    Is it safe to use?
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    Foxy Client is 100% safe. We use official Minecraft authentication and do not store your
                    credentials. The client is built with security as a top priority for the community.
                </div>
            </div>
            <div class="faq-item">
                <div class="faq-question">
                    Does it support mods?
                    <i class="fas fa-chevron-down faq-toggle"></i>
                </div>
                <div class="faq-answer">
                    Absolutely. Foxy Client has built-in support for Fabric, Forge, NeoForge, and Quilt. You can easily
                    manage your modpacks and configurations directly from the launcher.
                </div>
            </div>
        </div>
    </section>

    <section id="support">
        <div class="section-title">
            <h2>Support & Community</h2>
            <div class="underline"></div>
        </div>
        <div class="card-grid">
            <div class="feature-card">
                <i class="fab fa-discord" style="color: #5865F2;"></i>
                <h3>Discord Server</h3>
                <p>Join our massive community hub for instant help, mod discussions, and to meet other players.</p>
                <a href="https://discord.gg/HhRDbGQHXz" class="btn btn-secondary" style="margin-top: 20px; width: 100%;">Join Discord</a>
            </div>
            <div class="feature-card">
                <i class="fas fa-code-branch" style="color: var(--primary);"></i>
                <h3>Source Code</h3>
                <p>Foxy Client's website and ecosystem is transparent. Check out our repositories on GitHub.</p>
                <a href="https://github.com/Minosuko/FoxyClient" class="btn btn-secondary" style="margin-top: 20px; width: 100%;">View GitHub</a>
            </div>
            <div class="feature-card">
                <i class="fas fa-envelope-open-text" style="color: var(--secondary);"></i>
                <h3>Official Support</h3>
                <p>Need account help? Send us an email for secure billing and profile-related inquiries.</p>
                <a href="mailto:support@foxyclient.qzz.io" class="btn btn-secondary" style="margin-top: 20px; width: 100%;">Contact Us</a>
            </div>
        </div>
    </section>

    <footer style="padding: 80px 10% 40px; text-align: center; border-top: 1px solid var(--glass-border);">
        <div class="logo-text" style="font-size: 1.2rem; margin-bottom: 20px;">FOXY CLIENT</div>
        <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 600px; margin: 0 auto;">&copy; 2026 Foxy Client Dev. Not an official Minecraft product. Not approved by or
            associated with Mojang or Microsoft.</p>
    </footer>

    <script>
        // Navbar Scroll Effect
        const nav = document.querySelector('nav');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) nav.classList.add('scrolled');
            else nav.classList.remove('scrolled');
        });

        // FAQ Accordion
        document.querySelectorAll('.faq-question').forEach(button => {
            button.addEventListener('click', () => {
                const faqItem = button.parentElement;
                const wasActive = faqItem.classList.contains('active');
                
                // Close others
                document.querySelectorAll('.faq-item').forEach(item => {
                    item.classList.remove('active');
                });
                
                if (!wasActive) faqItem.classList.add('active');
            });
        });

        // Smooth Scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                if(targetId === '#') return;
                const targetElem = document.querySelector(targetId);
                if(targetElem) {
                    window.scrollTo({
                        top: targetElem.offsetTop - 80,
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>

</html>
