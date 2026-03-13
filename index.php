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
        <div class="downloads-container">
            <div class="download-card">
                <i class="fab fa-windows"></i>
                <h3>Windows</h3>
                <p>Full-featured installer for Windows 10 & 11. Optimized for native performance and stability.</p>
                <a href="#" class="btn btn-primary" style="width: 100%; justify-content: center;">Download Installer</a>
            </div>
            <div class="download-card">
                <i class="fab fa-java"></i>
                <h3>Universal</h3>
                <p>Run Foxy Client on any system with Java installed. Portable, lightweight, and efficient.</p>
                <a href="#" class="btn btn-secondary" style="width: 100%; justify-content: center; opacity: 0.6; cursor: not-allowed;">COMING SOON</a>
            </div>
            <div class="download-card">
                <i class="fas fa-rocket"></i>
                <h3>Beta Access</h3>
                <p>Get the latest experimental features and help us shape the future of FoxyClient development.</p>
                <a href="#" class="btn btn-secondary" style="width: 100%; justify-content: center;">Join Beta Program</a>
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
            <h2>Support</h2>
            <div class="underline"></div>
        </div>
        <div class="support-content">
            <div class="downloads-container" style="width: 100%;">
                <div class="support-card" style="padding: 40px; border-radius: 20px; text-align: center;">
                    <i class="fab fa-discord" style="font-size: 3rem; color: #5865F2; margin-bottom: 20px;"></i>
                    <h4>Discord Community</h4>
                    <p style="margin-bottom: 25px; color: var(--text-muted);">Join our Discord for instant help, community updates, and to meet other users.</p>
                    <a href="https://discord.gg/HhRDbGQHXz" class="btn btn-secondary" style="width: 100%; justify-content: center;">Join Discord</a>
                </div>
                <div class="support-card" style="padding: 40px; border-radius: 20px; text-align: center;">
                    <i class="fas fa-book" style="font-size: 3rem; color: var(--primary); margin-bottom: 20px;"></i>
                    <h4>Documentation</h4>
                    <p style="margin-bottom: 25px; color: var(--text-muted);">Read our detailed guides on how to get the most out of Foxy Client features.</p>
                    <a href="#" class="btn btn-secondary" style="width: 100%; justify-content: center;">View Wiki</a>
                </div>
                <div class="support-card" style="padding: 40px; border-radius: 20px; text-align: center;">
                    <i class="fas fa-envelope" style="font-size: 3rem; color: var(--secondary); margin-bottom: 20px;"></i>
                    <h4>Official Support</h4>
                    <p style="margin-bottom: 25px; color: var(--text-muted);">Send us an email for account or billing inquiries and professional assistance.</p>
                    <a href="mailto:support@foxyclient.com" class="btn btn-secondary" style="width: 100%; justify-content: center;">Contact Us</a>
                </div>
            </div>
        </div>
    </section>

    <footer style="padding: 80px 10% 40px; text-align: center; border-top: 1px solid var(--glass-border);">
        <div class="logo-text" style="font-size: 1.2rem; margin-bottom: 20px;">FOXY CLIENT</div>
        <p style="color: var(--text-muted); font-size: 0.9rem; max-width: 600px; margin: 0 auto;">&copy; 2026 Foxy Client Dev. Not an official Minecraft product. Not approved by or
            associated with Mojang or Microsoft.</p>
    </footer>

    <script>
        // FAQ Accordion
        document.querySelectorAll('.faq-question').forEach(button => {
            button.addEventListener('click', () => {
                const faqItem = button.parentElement;
                faqItem.classList.toggle('active');
            });
        });

        // Smooth Scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>

</html>
