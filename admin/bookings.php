<?php
require_once 'auth_check.php';
require_once '../config/db.php';

$action = $_GET['action'] ?? '';
$booking_id = $_GET['id'] ?? '';

// Update booking status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $status = $_POST['status'];
    
    $query = "UPDATE bookings SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $status, $booking_id);
    
    if ($stmt->execute()) {
        $success = "Booking status updated successfully!";
    } else {
        $error = "Error updating booking!";
    }
}

// Get all bookings
$filter = $_GET['filter'] ?? 'all';
$where_clause = "";

if ($filter !== 'all') {
    $where_clause = "WHERE b.status = '$filter'";
}

$bookings = $conn->query("
    SELECT b.*, u.name, u.email, u.phone, v.vehicle_type, v.plate_number
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN vehicles v ON b.vehicle_id = v.id
    $where_clause
    ORDER BY b.created_at DESC
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Admin Panel</title>
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
                <a href="bookings.php" class="nav-link active">
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
                </div>
                <a href="logout.php" class="btn-logout">Logout</a>
            </div>
        </aside>
        
        <main class="main-content">
            <header class="top-bar">
                <h1>Manage Bookings</h1>
            </header>
            
            <div class="content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <!-- Filter Buttons -->
                <div class="filter-buttons">
                    <a href="?filter=all" class="btn-filter <?php echo $filter === 'all' ? 'active' : ''; ?>">All Bookings</a>
                    <a href="?filter=pending" class="btn-filter <?php echo $filter === 'pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="?filter=confirmed" class="btn-filter <?php echo $filter === 'confirmed' ? 'active' : ''; ?>">Confirmed</a>
                    <a href="?filter=completed" class="btn-filter <?php echo $filter === 'completed' ? 'active' : ''; ?>">Completed</a>
                    <a href="?filter=cancelled" class="btn-filter <?php echo $filter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
                </div>
                
                <!-- Bookings Table -->
                <div class="section">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Customer</th>
                                <th>Email</th>
                                <th>Vehicle</th>
                                <th>Pickup Date</th>
                                <th>Return Date</th>
                                <th>Total Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = $bookings->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $booking['id']; ?></td>
                                <td><?php echo htmlspecialchars($booking['name']); ?></td>
                                <td><?php echo htmlspecialchars($booking['email']); ?></td>
                                <td><?php echo htmlspecialchars($booking['vehicle_type']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['pickup_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($booking['return_date'])); ?></td>
                                <td>$<?php echo number_format($booking['total_price'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo $booking['status']; ?>"><?php echo ucfirst($booking['status']); ?></span></td>
                                <td>
                                    <button class="btn-action" onclick="openUpdateModal(<?php echo $booking['id']; ?>, '<?php echo $booking['status']; ?>')">Update Status</button>
                                    <a href="booking_detail.php?id=<?php echo $booking['id']; ?>" class="btn-action btn-primary">View Details</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Update Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Update Booking Status</h2>
            <form method="POST">
                <input type="hidden" id="booking_id" name="booking_id">
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
                <button type="submit" name="update_status" class="btn-primary">Update</button>
            </form>
        </div>
    </div>
    
    <script>
        function openUpdateModal(bookingId, currentStatus) {
            document.getElementById('booking_id').value = bookingId;
            document.getElementById('status').value = currentStatus;
            document.getElementById('statusModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('statusModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('statusModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>