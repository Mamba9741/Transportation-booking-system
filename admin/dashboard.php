<?php
require_once 'auth_check.php';
require_once '../config/db.php';

// Get dashboard statistics
$stats = [];

// Total Bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings");
$stats['total_bookings'] = $result->fetch_assoc()['count'];

// Completed Bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'completed'");
$stats['completed_bookings'] = $result->fetch_assoc()['count'];

// Pending Bookings
$result = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'");
$stats['pending_bookings'] = $result->fetch_assoc()['count'];

// Total Revenue
$result = $conn->query("SELECT SUM(total_price) as total FROM bookings WHERE status = 'completed'");
$stats['total_revenue'] = $result->fetch_assoc()['total'] ?? 0;

// Total Users
$result = $conn->query("SELECT COUNT(*) as count FROM users");
$stats['total_users'] = $result->fetch_assoc()['count'];

// Total Vehicles
$result = $conn->query("SELECT COUNT(*) as count FROM vehicles");
$stats['total_vehicles'] = $result->fetch_assoc()['count'];

// Recent Bookings
$recent_bookings = $conn->query("
    SELECT b.*, u.name, u.email, v.vehicle_type 
    FROM bookings b 
    JOIN users u ON b.user_id = u.id 
    JOIN vehicles v ON b.vehicle_id = v.id 
    ORDER BY b.created_at DESC 
    LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Transportation Booking System</title>
    <link rel="stylesheet" href="../css/admin-style.css">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>🚐 Admin Panel</h2>
            </div>
            
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link active">
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
                <a href="settings.php" class="nav-link">
                    <span class="icon">⚙️</span> Settings
                </a>
            </nav>
            
            <div class="sidebar-footer">
                <div class="admin-info">
                    <p>Logged in as: <strong><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></p>
                    <p class="role"><?php echo ucfirst(str_replace('_', ' ', $_SESSION['admin_role'])); ?></p>
                </div>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="main-content">
            <header class="top-bar">
                <h1>Dashboard</h1>
                <div class="top-bar-right">
                    <span class="time"><?php echo date('l, F j, Y'); ?></span>
                </div>
            </header>
            
            <div class="content">
                <!-- Statistics Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #667eea;">📅</div>
                        <div class="stat-content">
                            <h3>Total Bookings</h3>
                            <p class="stat-number"><?php echo $stats['total_bookings']; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #48bb78;">✅</div>
                        <div class="stat-content">
                            <h3>Completed</h3>
                            <p class="stat-number"><?php echo $stats['completed_bookings']; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f6ad55;">⏳</div>
                        <div class="stat-content">
                            <h3>Pending</h3>
                            <p class="stat-number"><?php echo $stats['pending_bookings']; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #ed8936;">💰</div>
                        <div class="stat-content">
                            <h3>Total Revenue</h3>
                            <p class="stat-number">$<?php echo number_format($stats['total_revenue'], 2); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #9f7aea;">👥</div>
                        <div class="stat-content">
                            <h3>Total Users</h3>
                            <p class="stat-number"><?php echo $stats['total_users']; ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #38b2ac;">🚗</div>
                        <div class="stat-content">
                            <h3>Total Vehicles</h3>
                            <p class="stat-number"><?php echo $stats['total_vehicles']; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Bookings -->
                <div class="section">
                    <h2>Recent Bookings</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Vehicle</th>
                                <th>Status</th>
                                <th>Total Price</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = $recent_bookings->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td><?php echo htmlspecialchars($booking['name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['vehicle_type']); ?></td>
                                <td><span class="status-badge status-<?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                                <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>