<?php
require_once 'auth_check.php';
require_once '../config/db.php';

// Update admin password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Get current admin
    $query = "SELECT password FROM admin_users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['admin_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    
    if (password_verify($current_password, $admin['password'])) {
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
            $query = "UPDATE admin_users SET password = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $hashed_password, $_SESSION['admin_id']);
            
            if ($stmt->execute()) {
                $success = "Password changed successfully!";
            } else {
                $error = "Error updating password!";
            }
        } else {
            $error = "New passwords do not match!";
        }
    } else {
        $error = "Current password is incorrect!";
    }
}

// Add new admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_admin']) && hasPermission('super_admin')) {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];
    
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $query = "INSERT INTO admin_users (username, email, password, role) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $username, $email, $hashed_password, $role);
    
    if ($stmt->execute()) {
        $success = "Admin user created successfully!";
    } else {
        $error = "Error creating admin user!";
    }
}

// Get all admin users
$admin_users = $conn->query("SELECT id, username, email, role, is_active FROM admin_users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin-style.css">
</head>
<body>
    <div class="admin-wrapper">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🚐 Admin Panel</h2>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link">
                    <span class="icon">📊</span> Dashboard
                </a>
                <a href="bookings.php" class="nav-link">
                    <span class="icon">📅</span> Manage Bookings
                </a>
                <a href="vehicles.php" class="nav-link">
                    <span class="icon">🚗</span> Manage Vehicles
                </a>
                <a href="users.php" class="nav-link">
                    <span class="icon">👥</span> Manage Users
                </a>
                <a href="pricing.php" class="nav-link">
                    <span class="icon">💰</span> Pricing
                </a>
                <a href="reports.php" class="nav-link">
                    <span class="icon">📈</span> Reports & Analytics
                </a>
                <a href="settings.php" class="nav-link active">
                    <span class="icon">⚙️</span> Settings
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="admin-info">
                    <p>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></p>
                </div>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="top-bar">
                <h1>Settings</h1>
            </header>
            
            <div class="content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Change Password Section -->
                <div class="section">
                    <h2>Change Password</h2>
                    <form method="POST" class="settings-form">
                        <div class="form-group">
                            <label for="current_password">Current Password:</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password:</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password:</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn-primary">Change Password</button>
                    </form>
                </div>
                
                <!-- Add New Admin (Super Admin Only) -->
                <?php if (hasPermission('super_admin')): ?>
                <div class="section">
                    <h2>Add New Admin User</h2>
                    <form method="POST" class="settings-form">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="role">Role:</label>
                            <select id="role" name="role" required>
                                <option value="admin">Admin</option>
                                <option value="super_admin">Super Admin</option>
                            </select>
                        </div>
                        
                        <button type="submit" name="add_admin" class="btn-primary">Create Admin User</button>
                    </form>
                </div>
                
                <!-- Admin Users List -->
                <div class="section">
                    <h2>Admin Users</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($admin = $admin_users->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($admin['username']); ?></td>
                                <td><?php echo htmlspecialchars($admin['email']); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $admin['role'])); ?></td>
                                <td><span class="status-badge status-<?php echo ($admin['is_active'] ? 'active' : 'inactive'); ?>"><?php echo ($admin['is_active'] ? 'Active' : 'Inactive'); ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <style>
        .settings-form {
            max-width: 500px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        
        .alert-error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .btn-action {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            margin: 0 4px;
        }
        
        .btn-action.btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-action.btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-action.btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-action.btn-success {
            background: #28a745;
            color: white;
        }
    </style>
</body>
</html>