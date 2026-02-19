<?php
require_once 'auth_check.php';
require_once '../config/db.php';

// Add new vehicle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    $vehicle_type = $_POST['vehicle_type'];
    $plate_number = $_POST['plate_number'];
    $capacity = $_POST['capacity'];
    $daily_rate = $_POST['daily_rate'];
    $status = $_POST['status'] ?? 'available';
    
    $query = "INSERT INTO vehicles (vehicle_type, plate_number, capacity, daily_rate, status) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssidi", $vehicle_type, $plate_number, $capacity, $daily_rate, $status);
    
    if ($stmt->execute()) {
        $success = "Vehicle added successfully!";
    } else {
        $error = "Error adding vehicle!";
    }
}

// Update vehicle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_vehicle'])) {
    $vehicle_id = $_POST['vehicle_id'];
    $vehicle_type = $_POST['vehicle_type'];
    $plate_number = $_POST['plate_number'];
    $capacity = $_POST['capacity'];
    $daily_rate = $_POST['daily_rate'];
    $status = $_POST['status'];
    
    $query = "UPDATE vehicles SET vehicle_type = ?, plate_number = ?, capacity = ?, daily_rate = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssidsi", $vehicle_type, $plate_number, $capacity, $daily_rate, $status, $vehicle_id);
    
    if ($stmt->execute()) {
        $success = "Vehicle updated successfully!";
    } else {
        $error = "Error updating vehicle!";
    }
}

// Delete vehicle
if (isset($_GET['delete_id'])) {
    $vehicle_id = $_GET['delete_id'];
    $query = "DELETE FROM vehicles WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $vehicle_id);
    
    if ($stmt->execute()) {
        $success = "Vehicle deleted successfully!";
    }
}

// Get all vehicles
$vehicles = $conn->query("SELECT * FROM vehicles ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vehicles - Admin Panel</title>
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
                <a href="vehicles.php" class="nav-link active">
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
                <h1>Manage Vehicles</h1>
                <button class="btn-primary" onclick="openAddModal()">+ Add Vehicle</button>
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
                                <th>ID</th>
                                <th>Vehicle Type</th>
                                <th>Plate Number</th>
                                <th>Capacity</th>
                                <th>Daily Rate</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($vehicle = $vehicles->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $vehicle['id']; ?></td>
                                <td><?php echo htmlspecialchars($vehicle['vehicle_type']); ?></td>
                                <td><?php echo htmlspecialchars($vehicle['plate_number']); ?></td>
                                <td><?php echo $vehicle['capacity']; ?> persons</td>
                                <td>$<?php echo number_format($vehicle['daily_rate'], 2); ?></td>
                                <td><span class="status-badge status-<?php echo $vehicle['status']; ?>"><?php echo ucfirst($vehicle['status']); ?></span></td>
                                <td>
                                    <button class="btn-action btn-secondary" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($vehicle)); ?>)">Edit</button>
                                    <a href="?delete_id=<?php echo $vehicle['id']; ?>" class="btn-action btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
    
    <!-- Add/Edit Vehicle Modal -->
    <div id="vehicleModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Add Vehicle</h2>
            <form method="POST">
                <input type="hidden" id="vehicle_id" name="vehicle_id">
                
                <div class="form-group">
                    <label for="vehicle_type">Vehicle Type:</label>
                    <input type="text" id="vehicle_type" name="vehicle_type" required>
                </div>
                
                <div class="form-group">
                    <label for="plate_number">Plate Number:</label>
                    <input type="text" id="plate_number" name="plate_number" required>
                </div>
                
                <div class="form-group">
                    <label for="capacity">Capacity (persons):</label>
                    <input type="number" id="capacity" name="capacity" required>
                </div>
                
                <div class="form-group">
                    <label for="daily_rate">Daily Rate ($):</label>
                    <input type="number" id="daily_rate" name="daily_rate" step="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <option value="available">Available</option>
                        <option value="unavailable">Unavailable</option>
                        <option value="maintenance">Maintenance</option>
                    </select>
                </div>
                
                <button type="submit" id="submitBtn" class="btn-primary">Add Vehicle</button>
            </form>
        </div>
    </div>
    
    <script>
        function openAddModal() {
            document.getElementById('vehicleModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Add Vehicle';
            document.getElementById('submitBtn').textContent = 'Add Vehicle';
            document.getElementById('submitBtn').name = 'add_vehicle';
            document.getElementById('vehicle_id').value = '';
            document.getElementById('vehicle_type').value = '';
            document.getElementById('plate_number').value = '';
            document.getElementById('capacity').value = '';
            document.getElementById('daily_rate').value = '';
            document.getElementById('status').value = 'available';
        }
        
        function openEditModal(vehicle) {
            document.getElementById('vehicleModal').style.display = 'block';
            document.getElementById('modalTitle').textContent = 'Edit Vehicle';
            document.getElementById('submitBtn').textContent = 'Update Vehicle';
            document.getElementById('submitBtn').name = 'update_vehicle';
            document.getElementById('vehicle_id').value = vehicle.id;
            document.getElementById('vehicle_type').value = vehicle.vehicle_type;
            document.getElementById('plate_number').value = vehicle.plate_number;
            document.getElementById('capacity').value = vehicle.capacity;
            document.getElementById('daily_rate').value = vehicle.daily_rate;
            document.getElementById('status').value = vehicle.status;
        }
        
        function closeModal() {
            document.getElementById('vehicleModal').style.display = 'none';
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('vehicleModal');
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>