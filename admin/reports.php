<?php
require_once 'auth_check.php';
require_once '../config/db.php';

// Get report data
$month = $_GET['month'] ?? date('m');
$year = $_GET['year'] ?? date('Y');

// Revenue Report
$revenue_query = "
    SELECT SUM(total_price) as total_revenue, COUNT(*) as booking_count
    FROM bookings 
    WHERE MONTH(created_at) = $month AND YEAR(created_at) = $year
    AND status = 'completed'
";
$revenue_result = $conn->query($revenue_query);
$revenue_data = $revenue_result->fetch_assoc();

// Top Vehicles
$top_vehicles_query = "
    SELECT v.vehicle_type, COUNT(b.id) as bookings, SUM(b.total_price) as revenue
    FROM bookings b
    JOIN vehicles v ON b.vehicle_id = v.id
    WHERE MONTH(b.created_at) = $month AND YEAR(b.created_at) = $year
    GROUP BY v.id
    ORDER BY bookings DESC
    LIMIT 5
";
$top_vehicles = $conn->query($top_vehicles_query);

// Monthly Stats
$monthly_stats = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as bookings, SUM(total_price) as revenue
    FROM bookings
    WHERE status = 'completed'
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - Admin Panel</title>
    <link rel="stylesheet" href="../css/admin-style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <a href="reports.php" class="nav-link active">
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
                <h1>Reports & Analytics</h1>
            </header>
            
            <div class="content">
                <!-- Date Filter -->
                <div class="filter-section">
                    <form method="GET" style="display: flex; gap: 10px;">
                        <select name="month">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" <?php echo $month == str_pad($m, 2, '0', STR_PAD_LEFT) ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $m, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <select name="year">
                            <?php for ($y = date('Y'); $y >= 2024; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $year == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                        <button type="submit" class="btn-primary">Filter</button>
                    </form>
                </div>
                
                <!-- Revenue Stats -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #48bb78;">💰</div>
                        <div class="stat-content">
                            <h3>Total Revenue</h3>
                            <p class="stat-number">$<?php echo number_format($revenue_data['total_revenue'] ?? 0, 2); ?></p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #667eea;">📅</div>
                        <div class="stat-content">
                            <h3>Completed Bookings</h3>
                            <p class="stat-number"><?php echo $revenue_data['booking_count'] ?? 0; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Top Vehicles -->
                <div class="section">
                    <h2>Top Vehicles This Month</h2>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Vehicle Type</th>
                                <th>Bookings</th>
                                <th>Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($vehicle = $top_vehicles->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($vehicle['vehicle_type']); ?></td>
                                <td><?php echo $vehicle['bookings']; ?></td>
                                <td>$<?php echo number_format($vehicle['revenue'], 2); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Monthly Stats Chart -->
                <div class="section">
                    <h2>Monthly Performance</h2>
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </main>
    </div>
    
    <script>
        const monthlyStatsData = [];
        <?php
        $monthly_stats->data_seek(0);
        while ($stat = $monthly_stats->fetch_assoc()) {
            echo "monthlyStatsData.unshift({month: '{$stat['month']}', bookings: {$stat['bookings']}, revenue: {$stat['revenue']}});";
        }
        ?>
        
        const ctx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: monthlyStatsData.map(d => d.month),
                datasets: [
                    {
                        label: 'Bookings',
                        data: monthlyStatsData.map(d => d.bookings),
                        backgroundColor: '#667eea'
                    },
                    {
                        label: 'Revenue ($)',
                        data: monthlyStatsData.map(d => d.revenue),
                        backgroundColor: '#48bb78'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>