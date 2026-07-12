<?php
require_once __DIR__ . '/../includes/admin_functions.php';
require_admin();
$user_id = $_SESSION['user_id'];

$total_users = get_user_count();
$banned_users = get_banned_count();
$unverified_users = get_unverified_count();
$admin_users = get_admin_count();
$recent_users = get_recent_users(5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Foxy Client</title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="style.css">
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
            <li><a href="../accounts/dashboard/">Dashboard</a></li>
            <li><a href="../accounts/login/?logout=1">Logout</a></li>
            <li>
                <button id="themeToggle" class="theme-toggle" aria-label="Toggle Theme">
                    <i class="fas fa-moon"></i>
                </button>
            </li>
        </ul>
    </nav>

    <div class="admin-layout">
        <div class="admin-sidebar">
            <a href="index.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="users.php"><i class="fas fa-users"></i> Users</a>
        </div>

        <div class="admin-main">
            <h1>Admin Dashboard</h1>
            <p class="subtitle">Welcome, <?php echo $_SESSION['username']; ?></p>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-users"></i></div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="stat-value"><?php echo $admin_users; ?></div>
                    <div class="stat-label">Admins</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-ban"></i></div>
                    <div class="stat-value"><?php echo $banned_users; ?></div>
                    <div class="stat-label">Banned</div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                    <div class="stat-value"><?php echo $unverified_users; ?></div>
                    <div class="stat-label">Unverified</div>
                </div>
            </div>

            <h2 style="font-size: 1.3rem; margin-bottom: 20px;">Recent Registrations</h2>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Registered</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recent_users)): ?>
                        <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-muted);">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recent_users as $u): ?>
                            <tr>
                                <td><?php echo $u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><span class="badge <?php echo $u['role'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>"><?php echo $u['role']; ?></span></td>
                                <td>
                                    <?php if ($u['banned']): ?>
                                        <span class="badge badge-banned">Banned</span>
                                    <?php elseif (!$u['is_verified']): ?>
                                        <span class="badge badge-unverified">Unverified</span>
                                    <?php else: ?>
                                        <span class="badge badge-active">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($u['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
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
        }
        const nav = document.querySelector('nav');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 50) nav.classList.add('scrolled');
            else nav.classList.remove('scrolled');
        });
    </script>
</body>
</html>
