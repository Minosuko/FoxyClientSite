<?php
require_once __DIR__ . '/../includes/admin_functions.php';
require_admin();

$user_id = $_SESSION['user_id'];
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

// Handle ban/unban AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_ajax) {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $target_id = intval($_POST['user_id'] ?? 0);

    if ($target_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Invalid user ID.']);
        exit;
    }

    if ($target_id == $user_id) {
        echo json_encode(['success' => false, 'error' => 'You cannot ban/unban yourself.']);
        exit;
    }

    if ($action === 'ban') {
        $reason = trim($_POST['reason'] ?? '');
        if (ban_user($target_id, $reason, $user_id)) {
            echo json_encode(['success' => true, 'message' => 'User has been banned.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to ban user.']);
        }
    } elseif ($action === 'unban') {
        if (unban_user($target_id)) {
            echo json_encode(['success' => true, 'message' => 'User has been unbanned.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to unban user.']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid action.']);
    }
    exit;
}

// Get search query
$search = trim($_GET['search'] ?? '');
$page = max(1, intval($_GET['page'] ?? 1));
$result = search_users($search, $page, 20);
$users = $result['users'];
$total = $result['total'];
$total_pages = max(1, ceil($total / $result['per_page']));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | Foxy Client Admin</title>
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
            <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="users.php" class="active"><i class="fas fa-users"></i> Users</a>
        </div>

        <div class="admin-main">
            <h1>User Management</h1>
            <p class="subtitle"><?php echo $total; ?> user<?php echo $total !== 1 ? 's' : ''; ?> found</p>

            <form class="search-bar" method="GET" action="users.php">
                <input type="text" name="search" placeholder="Search by username or email..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
                <?php if ($search): ?>
                    <a href="users.php" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</a>
                <?php endif; ?>
            </form>

            <div id="admin-alert" style="display:none;" class="alert"></div>

            <table class="admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Ban Reason</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="8" style="text-align:center;padding:30px;color:var(--text-muted);">No users found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr id="user-row-<?php echo $u['id']; ?>">
                                <td><?php echo $u['id']; ?></td>
                                <td><?php echo htmlspecialchars($u['username']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><span class="badge <?php echo $u['role'] === 'admin' ? 'badge-admin' : 'badge-user'; ?>"><?php echo $u['role']; ?></span></td>
                                <td>
                                    <?php if ($u['banned']): ?>
                                        <span class="badge badge-banned" id="status-badge-<?php echo $u['id']; ?>">Banned</span>
                                    <?php elseif (!$u['is_verified']): ?>
                                        <span class="badge badge-unverified" id="status-badge-<?php echo $u['id']; ?>">Unverified</span>
                                    <?php else: ?>
                                        <span class="badge badge-active" id="status-badge-<?php echo $u['id']; ?>">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td id="ban-reason-<?php echo $u['id']; ?>"><?php echo $u['ban_reason'] ? htmlspecialchars($u['ban_reason']) : '-'; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($u['created_at'])); ?></td>
                                <td>
                                    <?php if ($u['banned']): ?>
                                        <button class="btn btn-success btn-sm" onclick="unbanUser(<?php echo $u['id']; ?>)"><i class="fas fa-check"></i> Unban</button>
                                    <?php else: ?>
                                        <button class="btn btn-danger btn-sm" onclick="showBanModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['username'])); ?>')"><i class="fas fa-ban"></i> Ban</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page - 1; ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php endif; ?>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="current"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?search=<?php echo urlencode($search); ?>&page=<?php echo $page + 1; ?>"><i class="fas fa-chevron-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ban Modal -->
    <div class="ban-modal-overlay" id="ban-modal">
        <div class="ban-modal">
            <h3>Ban User</h3>
            <p style="color:var(--text-muted);margin-bottom:15px;">Ban <strong id="ban-username"></strong></p>
            <div id="ban-modal-alert" class="modal-alert"></div>
            <textarea id="ban-reason-input" placeholder="Enter ban reason (optional)"></textarea>
            <input type="hidden" id="ban-user-id">
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeBanModal()">Cancel</button>
                <button class="btn btn-danger" onclick="submitBan()"><i class="fas fa-ban"></i> Ban User</button>
            </div>
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

        function showAlert(msg, type) {
            const el = document.getElementById('admin-alert');
            el.className = 'alert alert-' + type;
            el.textContent = msg;
            el.style.display = 'flex';
            setTimeout(() => { el.style.display = 'none'; }, 4000);
        }

        function showBanModal(id, username) {
            document.getElementById('ban-user-id').value = id;
            document.getElementById('ban-username').textContent = username;
            document.getElementById('ban-reason-input').value = '';
            document.getElementById('ban-modal-alert').className = 'modal-alert';
            document.getElementById('ban-modal-alert').style.display = 'none';
            document.getElementById('ban-modal').classList.add('active');
        }

        function closeBanModal() {
            document.getElementById('ban-modal').classList.remove('active');
        }

        function submitBan() {
            const userId = document.getElementById('ban-user-id').value;
            const reason = document.getElementById('ban-reason-input').value;
            const alertEl = document.getElementById('ban-modal-alert');

            $.ajax({
                url: 'users.php',
                type: 'POST',
                data: { action: 'ban', user_id: userId, reason: reason },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        alertEl.className = 'modal-alert success';
                        alertEl.textContent = res.message;
                        alertEl.style.display = 'block';
                        setTimeout(function() {
                            closeBanModal();
                            location.reload();
                        }, 800);
                    } else {
                        alertEl.className = 'modal-alert error';
                        alertEl.textContent = res.error;
                        alertEl.style.display = 'block';
                    }
                },
                error: function() {
                    alertEl.className = 'modal-alert error';
                    alertEl.textContent = 'Request failed. Please try again.';
                    alertEl.style.display = 'block';
                }
            });
        }

        function unbanUser(id) {
            if (!confirm('Unban this user?')) return;
            $.ajax({
                url: 'users.php',
                type: 'POST',
                data: { action: 'unban', user_id: id },
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        showAlert(res.message, 'success');
                        location.reload();
                    } else {
                        showAlert(res.error, 'danger');
                    }
                },
                error: function() {
                    showAlert('Request failed. Please try again.', 'danger');
                }
            });
        }

        // Close modal on overlay click
        document.getElementById('ban-modal').addEventListener('click', function(e) {
            if (e.target === this) closeBanModal();
        });
    </script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</body>
</html>
