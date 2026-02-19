<?php
require_once 'auth_check.php';
require_once '../config/db.php';

// Update pricing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_pricing'])) {
    $vehicle_id = $_POST['vehicle_id'];
    $daily_rate = $_POST['daily_rate'];
    $weekly_discount = $_POST['weekly_discount'] ?? 0;
    $monthly_discount = $_POST['monthly_discount'] ?? 0;
    
    $query = "UPDATE vehicles SET daily_rate = ?, weekly_discount = ?, monthly_discount = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("dddi", $daily_rate, $weekly_discount, $monthly_discount, $vehicle_id);
    
    if ($stmt->execute()) {
        $success = "Pricing updated successfully!";
    } else {
        $error = "Error updating pricing!";
    }
}

// Get vehicles with pricing
$vehicles = $conn->query("SELECT * FROM vehicles ORDER BY vehicle_type");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pricing - Admin Panel</title>
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
                <a href="pricing.php" class="nav-link active">
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
                <h1>Manage Pricing</h1>
            </header>
            
            <div class="content">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="section">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Vehicle Type</th>
                                <th>Daily Rate</th>
                                <th>Weekly Discount (%)</th>
                                <th>Monthly Discount (%)</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($vehicle['vehicle_type']); ?></td>
                                <td>$<?php echo number_format($vehicle['daily_rate'], 2); ?></td>
                                <td><?php echo $vehicle['weekly_discount'] ?? 0; ?>%</td>
                                <td><?php echo $vehicle['monthly_discount'] ?? 0; ?>%</td>
                                <td>
                                    <button class="btn-action btn-primary" onclick="openPricingModal(<?php echo htmlspecialchars(json_encode($vehicle)); ?>)">Edit</button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Pricing Modal -->
    <div id="pricingModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2>Edit Pricing</h2>
            <form method="POST">
                <input type="hidden" id="vehicle_id" name="vehicle_id">
                
                <div class="form-group">
                    <label for="daily_rate">Daily Rate ($):</label>
                    <input type="number" id="daily_rate" name="daily_rate" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="weekly_discount">Weekly Discount (%):</label>
                    <input type="number" id="weekly_discount" name="weekly_discount" step="0.01" min="0" max="100">
                </div>
                
                <div class="form-group">
                    <label for="monthly_discount">Monthly Discount (%):</label>
                    <input type="number" id="monthly_discount" name="monthly_discount" step="0.01" min="0" max="100">
                </div>
                
                <button type="submit" name="update_pricing" class="btn-primary">Update Pricing</button>
            </form>
        </div>
    </div>
    
    <script>
        function openPricingModal(vehicle) {
            document.getElementById('vehicle_id').value = vehicle.id;
            document.getElementById('daily_rate').value = vehicle.daily_rate;
            document.getElementById('weekly_discount').value = vehicle.weekly_discount || 0;
            document.getElementById('monthly_discount').value = vehicle.monthly_discount || 0;
            document.getElementById('pricingModal').style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('pricingModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('pricingModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>