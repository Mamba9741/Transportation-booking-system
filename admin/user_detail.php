<?php
require_once 'auth_check.php';
require_once '../config/db.php';

$user_id = $_GET['id'] ?? '';

if (!$user_id) {
    header("Location: users.php");
    exit();
}

$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header("Location: users.php");
    exit();
}

$user = $result->fetch_assoc();

// Get user's bookings
$bookings_query = "
    SELECT b.*, v.vehicle_type 
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
    LIMIT 10
";
$bookings_stmt = $conn->prepare($bookings_query);
$bookings_stmt->bind_param("i", $user_id);
$bookings_stmt->execute();
$bookings = $bookings_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Details - Admin Panel</title>
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
                <a href="users.php" class="nav-link active">
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
                </div>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="top-bar">
                <h1>User Details - <?php echo htmlspecialchars($user['name']); ?></h1>
                <a href="users.php" class="btn-secondary">Back to Users</a>
            </header>
            
            <div class="content">
                <!-- User Information -->
                <div class="detail-grid">
                    <div class="detail-section">
                        <h2>Personal Information</h2>
                        <div class="detail-row">
                            <span class="label">Name:</span>
                            <span class="value"><?php echo htmlspecialchars($user['name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Email:</span>
                            <span class="value"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Phone:</span>
                            <span class="value"><?php echo htmlspecialchars($user['phone']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Status:</span>
                            <span class="value"><span class="status-badge status-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></span>
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h2>License Information</h2>
                        <div class="detail-row">
                            <span class="label">License Number:</span>
                            <span class="value"><?php echo htmlspecialchars($user['license_number']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Joined Date:</span>
                            <span class="value"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="label">Last Updated:</span>
                            <span class="value"><?php echo date('M d, Y H:i', strtotime($user['updated_at'])); ?></span>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Bookings -->
                <div class="section" style="margin-top: 30px;">
                    <h2>User's Recent Bookings</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Vehicle</th>
                                <th>Pickup Date</th>
                                <th>Return Date</th>
                                <th>Total Price</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($bookings->num_rows > 0): ?>
                                <?php while ($booking = $bookings->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $booking['id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['vehicle_type']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['pickup_date'])); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['return_date'])); ?></td>
                                    <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                    <td><span class="status-badge status-<?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                                    <td>
                                        <a href="booking_detail.php?id=<?php echo $booking['id']; ?>" class="btn-action btn-primary">View</a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 20px;">No bookings found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <style>
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
        
        .detail-section h2 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #333;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .detail-row:last-child {
            border-bottom: none;
        }
        
        .detail-row .label {
            font-weight: 600;
            color: #666;
        }
        
        .detail-row .value {
            color: #333;
        }
    </style>
</body>
</html>