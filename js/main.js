// Initialize
document.addEventListener('DOMContentLoaded', function() {
    setMinDate();
    loadVehicles();
    checkLoginStatus();
    setupEventListeners();
});

// Set minimum date to today
function setMinDate() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('pickupDate').setAttribute('min', today);
    document.getElementById('dropoffDate').setAttribute('min', today);
}

// Setup Event Listeners
function setupEventListeners() {
    document.getElementById('loginForm').addEventListener('submit', handleLogin);
    document.getElementById('registerForm').addEventListener('submit', handleRegister);
    document.getElementById('bookingForm').addEventListener('submit', handleBooking);
    document.getElementById('pickupDate').addEventListener('change', calculatePrice);
    document.getElementById('dropoffDate').addEventListener('change', calculatePrice);
    document.getElementById('distanceOrHours').addEventListener('change', calculatePrice);
    document.getElementById('passengers').addEventListener('change', calculatePrice);
    document.getElementById('vehicleSelect').addEventListener('change', calculatePrice);
}

// Load Vehicles
function loadVehicles(type = '') {
    const url = type ? `api/get_vehicles.php?type=${type}` : 'api/get_vehicles.php';
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            displayVehicles(data.vehicles);
            populateVehicleSelect(data.vehicles);
        })
        .catch(error => console.error('Error loading vehicles:', error));
}

// Display Vehicles
function displayVehicles(vehicles) {
    const container = document.getElementById('vehiclesContainer');
    container.innerHTML = '';

    if (vehicles.length === 0) {
        container.innerHTML = '<p style="grid-column: 1/-1; text-align: center;">No vehicles available</p>';
        return;
    }

    vehicles.forEach(vehicle => {
        const icons = {
            motorcycle: '🏍️',
            car: '🚗',
            minibus: '🚌',
            jeep: '🚙',
            bus: '🚐'
        };

        const vehicleCard = document.createElement('div');
        vehicleCard.className = 'vehicle-card';
        vehicleCard.innerHTML = `
            <div class="vehicle-image">${icons[vehicle.type] || '🚗'}</div>
            <div class="vehicle-info">
                <h3>${vehicle.name}</h3>
                <p>${vehicle.description}</p>
                <div class="vehicle-specs">
                    <span>👥 ${vehicle.capacity} seats</span>
                    <span>📏 ₹${vehicle.price_per_km}/km</span>
                </div>
                <div class="vehicle-specs">
                    <span>⏰ ₹${vehicle.hourly_rate}/hour</span>
                    <span>📅 ₹${vehicle.daily_rate}/day</span>
                </div>
                <div class="vehicle-price">₹${vehicle.hourly_rate} / Hour</div>
                <button class="btn btn-primary" onclick="selectVehicle(${vehicle.id})">Select</button>
            </div>
        `;
        container.appendChild(vehicleCard);
    });
}

// Populate Vehicle Select
function populateVehicleSelect(vehicles) {
    const select = document.getElementById('vehicleSelect');
    const currentValue = select.value;
    
    select.innerHTML = '<option value="">Select Vehicle</option>';
    
    vehicles.forEach(vehicle => {
        const option = document.createElement('option');
        option.value = vehicle.id;
        option.textContent = `${vehicle.name} (${vehicle.type})`;
        option.dataset.type = vehicle.type;
        option.dataset.hourlyRate = vehicle.hourly_rate;
        option.dataset.dailyRate = vehicle.daily_rate;
        option.dataset.pricePerKm = vehicle.price_per_km;
        option.dataset.capacity = vehicle.capacity;
        select.appendChild(option);
    });
    
    select.value = currentValue;
}

// Select Vehicle
function selectVehicle(vehicleId) {
    document.getElementById('vehicleSelect').value = vehicleId;
    document.getElementById('distanceOrHours').focus();
    scrollToSection('#booking');
}

// Filter Vehicles
function filterVehicles(type) {
    // Update active button
    document.querySelectorAll('.filter-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Load vehicles
    loadVehicles(type);
}

// Update Booking Fields
function updateBookingFields() {
    const bookingType = document.getElementById('bookingType').value;
    const distanceOrHoursLabel = document.getElementById('distanceOrHoursLabel');
    const dropoffFields = document.getElementById('dropoffDateTimeFields');
    
    if (bookingType === 'short_tour') {
        distanceOrHoursLabel.textContent = 'Duration (Hours)';
        dropoffFields.style.display = 'grid';
        document.getElementById('dropoffDate').removeAttribute('required');
        document.getElementById('dropoffTime').removeAttribute('required');
    } else {
        distanceOrHoursLabel.textContent = 'Distance (KM)';
        dropoffFields.style.display = 'grid';
        document.getElementById('dropoffDate').setAttribute('required', 'required');
        document.getElementById('dropoffTime').setAttribute('required', 'required');
    }
    
    calculatePrice();
}

// Calculate Price
function calculatePrice() {
    const vehicleSelect = document.getElementById('vehicleSelect');
    const bookingType = document.getElementById('bookingType').value;
    const distanceOrHours = parseFloat(document.getElementById('distanceOrHours').value) || 0;
    const passengers = parseInt(document.getElementById('passengers').value) || 1;
    
    if (!vehicleSelect.value) {
        document.getElementById('totalPrice').value = '';
        return;
    }
    
    const option = vehicleSelect.options[vehicleSelect.selectedIndex];
    const hourlyRate = parseFloat(option.dataset.hourlyRate);
    const pricePerKm = parseFloat(option.dataset.pricePerKm);
    
    let totalPrice = 0;
    
    if (bookingType === 'short_tour') {
        totalPrice = hourlyRate * distanceOrHours;
    } else {
        totalPrice = pricePerKm * distanceOrHours;
    }
    
    totalPrice *= passengers;
    
    document.getElementById('totalPrice').value = '₹' + totalPrice.toFixed(2);
    
    // Store calculated price
    document.getElementById('totalPrice').dataset.amount = totalPrice.toFixed(2);
}

// Handle Booking
function handleBooking(e) {
    e.preventDefault();
    
    const userId = localStorage.getItem('userId');
    if (!userId) {
        showAlert('Please login to book', 'error');
        openLoginModal();
        return;
    }
    
    const vehicleSelect = document.getElementById('vehicleSelect');
    const vehicleId = vehicleSelect.value;
    
    if (!vehicleId) {
        showAlert('Please select a vehicle', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('vehicle_id', vehicleId);
    formData.append('pickup_location', document.getElementById('pickupLocation').value);
    formData.append('dropoff_location', document.getElementById('dropoffLocation').value);
    formData.append('pickup_date', document.getElementById('pickupDate').value);
    formData.append('pickup_time', document.getElementById('pickupTime').value);
    formData.append('dropoff_date', document.getElementById('dropoffDate').value);
    formData.append('dropoff_time', document.getElementById('dropoffTime').value);
    formData.append('booking_type', document.getElementById('bookingType').value);
    
    if (document.getElementById('bookingType').value === 'short_tour') {
        formData.append('duration_hours', document.getElementById('distanceOrHours').value);
    } else {
        formData.append('distance_km', document.getElementById('distanceOrHours').value);
    }
    
    formData.append('passengers', document.getElementById('passengers').value);
    formData.append('total_price', document.getElementById('totalPrice').dataset.amount);
    formData.append('special_requests', document.getElementById('specialRequests').value);
    
    fetch('api/create_booking.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Booking created successfully! Booking ID: ' + data.booking_id, 'success');
            document.getElementById('bookingForm').reset();
            document.getElementById('totalPrice').value = '';
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while booking', 'error');
    });
}

// Handle Login
function handleLogin(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('email', document.getElementById('loginEmail').value);
    formData.append('password', document.getElementById('loginPassword').value);
    
    fetch('api/login.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            localStorage.setItem('userId', data.user_id);
            showAlert('Login successful!', 'success');
            closeLoginModal();
            checkLoginStatus();
            location.reload();
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred during login', 'error');
    });
}

// Handle Register
function handleRegister(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('name', document.getElementById('regName').value);
    formData.append('email', document.getElementById('regEmail').value);
    formData.append('phone', document.getElementById('regPhone').value);
    formData.append('address', document.getElementById('regAddress').value);
    formData.append('city', document.getElementById('regCity').value);
    formData.append('password', document.getElementById('regPassword').value);
    
    fetch('api/register.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Registration successful! Please login.', 'success');
            document.getElementById('registerForm').reset();
            closeRegisterModal();
            openLoginModal();
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred during registration', 'error');
    });
}

// Check Login Status
function checkLoginStatus() {
    const userId = localStorage.getItem('userId');
    const userName = localStorage.getItem('userName');
    
    if (userId) {
        document.getElementById('userMenuBtn').style.display = 'block';
        document.getElementById('userName').textContent = userName || 'User';
        document.querySelectorAll('a[onclick="openLoginModal()"]')[0].style.display = 'none';
        document.querySelectorAll('a[onclick="openRegisterModal()"]')[0].style.display = 'none';
    } else {
        document.getElementById('userMenuBtn').style.display = 'none';
    }
}

// Toggle User Menu
function toggleUserMenu() {
    const menu = document.getElementById('userMenu');
    menu.classList.toggle('show');
}

// Modal Functions
function openLoginModal() {
    document.getElementById('loginModal').classList.add('show');
}

function closeLoginModal() {
    document.getElementById('loginModal').classList.remove('show');
}

function openRegisterModal() {
    document.getElementById('registerModal').classList.add('show');
}

function closeRegisterModal() {
    document.getElementById('registerModal').classList.remove('show');
}

function openMyBookings() {
    const userId = localStorage.getItem('userId');
    if (!userId) {
        showAlert('Please login to view bookings', 'error');
        return;
    }
    
    fetch('api/get_bookings.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.bookings.length > 0) {
                displayBookings(data.bookings);
                document.getElementById('myBookingsModal').classList.add('show');
            } else {
                showAlert('No bookings found', 'info');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred while fetching bookings', 'error');
        });
}

function displayBookings(bookings) {
    const container = document.getElementById('bookingsContainer');
    container.innerHTML = '';
    
    bookings.forEach(booking => {
        const bookingItem = document.createElement('div');
        bookingItem.className = 'booking-item';
        bookingItem.innerHTML = `
            <h3>${booking.name} (${booking.type.toUpperCase()})</h3>
            <div class="booking-details">
                <div class="booking-detail">
                    <strong>Pickup:</strong>
                    ${booking.pickup_location} - ${booking.pickup_date} ${booking.pickup_time}
                </div>
                <div class="booking-detail">
                    <strong>Dropoff:</strong>
                    ${booking.dropoff_location} - ${booking.dropoff_date} ${booking.dropoff_time}
                </div>
                <div class="booking-detail">
                    <strong>Type:</strong>
                    ${booking.booking_type.replace('_', ' ').toUpperCase()}
                </div>
                <div class="booking-detail">
                    <strong>Passengers:</strong>
                    ${booking.passengers}
                </div>
                <div class="booking-detail">
                    <strong>Total Price:</strong>
                    ₹${parseFloat(booking.total_price).toFixed(2)}
                </div>
                <div class="booking-detail">
                    <strong>Status:</strong>
                    <span class="booking-status ${booking.status}">${booking.status.toUpperCase()}</span>
                </div>
            </div>
            ${booking.status === 'pending' ? `
                <div class="booking-actions">
                    <button class="btn btn-danger" onclick="cancelBooking(${booking.id})">Cancel Booking</button>
                </div>
            ` : ''}
        `;
        container.appendChild(bookingItem);
    });
}

function closeMyBookings() {
    document.getElementById('myBookingsModal').classList.remove('show');
}

function cancelBooking(bookingId) {
    if (!confirm('Are you sure you want to cancel this booking?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('booking_id', bookingId);
    
    fetch('api/cancel_booking.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Booking cancelled successfully', 'success');
            openMyBookings(); // Refresh bookings
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while cancelling booking', 'error');
    });
}

// Show Alert
function showAlert(message, type = 'info') {
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.textContent = message;
    
    const container = document.querySelector('.container');
    container.parentNode.insertBefore(alert, container);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}

// Scroll to Section
function scrollToSection(selector) {
    document.querySelector(selector).scrollIntoView({ behavior: 'smooth' });
}

// Close modals when clicking outside
window.onclick = function(event) {
    const loginModal = document.getElementById('loginModal');
    const registerModal = document.getElementById('registerModal');
    const myBookingsModal = document.getElementById('myBookingsModal');
    
    if (event.target === loginModal) {
        loginModal.classList.remove('show');
    }
    if (event.target === registerModal) {
        registerModal.classList.remove('show');
    }
    if (event.target === myBookingsModal) {
        myBookingsModal.classList.remove('show');
    }
};