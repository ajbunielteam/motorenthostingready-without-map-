// Default motorcycle data (used for initialization)
const defaultMotorcycles = [
    {
        id: 1,
        name: "Yamaha R1",
        category: "manual",
        transmission: "manual",
        engine: "998cc",
        power: "200 HP",
        topSpeed: "186 mph",
        price: 150,
        description: "High-performance sport bike with cutting-edge technology",
        available: 3
    },
    {
        id: 2,
        name: "Harley-Davidson Street Glide",
        category: "automatic",
        transmission: "automatic",
        engine: "1868cc",
        power: "92 HP",
        topSpeed: "110 mph",
        price: 200,
        description: "Classic American cruiser with iconic style",
        available: 2
    },
    {
        id: 3,
        name: "BMW R 1250 GS",
        category: "manual",
        transmission: "manual",
        engine: "1254cc",
        power: "136 HP",
        topSpeed: "125 mph",
        price: 180,
        description: "Adventure touring bike perfect for long journeys",
        available: 4
    },
    {
        id: 4,
        name: "Honda Gold Wing",
        category: "automatic",
        transmission: "automatic",
        engine: "1833cc",
        power: "125 HP",
        topSpeed: "120 mph",
        price: 220,
        description: "Ultimate touring motorcycle with premium comfort",
        available: 2
    },
    {
        id: 5,
        name: "Ducati Panigale V4",
        category: "manual",
        transmission: "manual",
        engine: "1103cc",
        power: "214 HP",
        topSpeed: "190 mph",
        price: 250,
        description: "Italian super sport bike with racing heritage",
        available: 1
    },
    {
        id: 6,
        name: "Indian Chief",
        category: "automatic",
        transmission: "automatic",
        engine: "1890cc",
        power: "100 HP",
        topSpeed: "115 mph",
        price: 190,
        description: "Premium cruiser with modern technology",
        available: 3
    },
    {
        id: 7,
        name: "KTM 1290 Super Adventure",
        category: "manual",
        transmission: "manual",
        engine: "1301cc",
        power: "160 HP",
        topSpeed: "140 mph",
        price: 200,
        description: "Powerful adventure bike for off-road and touring",
        available: 2
    },
    {
        id: 8,
        name: "Kawasaki Ninja ZX-10R",
        category: "manual",
        transmission: "manual",
        engine: "998cc",
        power: "203 HP",
        topSpeed: "186 mph",
        price: 160,
        description: "Track-focused sport bike with advanced electronics",
        available: 3
    },
    {
        id: 9,
        name: "Triumph Tiger 1200",
        category: "manual",
        transmission: "manual",
        engine: "1215cc",
        power: "141 HP",
        topSpeed: "130 mph",
        price: 175,
        description: "British adventure bike with excellent off-road capability",
        available: 2
    },
    {
        id: 10,
        name: "Honda CBR1000RR",
        category: "manual",
        transmission: "manual",
        engine: "999cc",
        power: "189 HP",
        topSpeed: "180 mph",
        price: 140,
        description: "Reliable sport bike with proven performance",
        available: 4
    }
];

// Get current logged-in user
function getCurrentUser() {
    return localStorage.getItem('motorent_current_user') || null;
}

// Get account-specific storage key for motorcycles
function getMotorcyclesKey(username) {
    return `motorent_motorcycles_${username}`;
}

// Get account-specific storage key for bookings
function getBookingsKey(username) {
    return `motorent_bookings_${username}`;
}

// Get account-specific storage key for profile
function getProfileKey(username) {
    return `motorent_profile_${username}`;
}

// Get profile data for current account
async function getProfile() {
    const currentUser = getCurrentUser();
    if (!currentUser) {
        return { photo: null };
    }
    
    try {
        const profile = await ProfilesAPI.get(currentUser);
        const profileData = profile || { photo: null };
        // Cache in localStorage for faster access
        if (profile) {
            const key = getProfileKey(currentUser);
            localStorage.setItem(key, JSON.stringify(profileData));
        }
        return profileData;
    } catch (error) {
        console.error('Error fetching profile:', error);
        // Fallback to localStorage if API fails
        const key = getProfileKey(currentUser);
        const stored = localStorage.getItem(key);
        return stored ? JSON.parse(stored) : { photo: null };
    }
}

// Get profile data for a specific account
function getAccountProfile(username) {
    const key = getProfileKey(username);
    const stored = localStorage.getItem(key);
    return stored ? JSON.parse(stored) : { photo: null };
}

// Display all provider profiles on homepage
async function displayProviders() {
    const providersGrid = document.getElementById('providersGrid');
    if (!providersGrid) {
        return;
    }
    
    providersGrid.innerHTML = '';
    
    // Get all accounts and filter only approved ones
    const accounts = await getAccounts();
    if (!Array.isArray(accounts)) {
        console.error('getAccounts() did not return an array:', accounts);
        return;
    }
    
    const approvedAccounts = accounts.filter(account => {
        // Only show approved accounts (or accounts with no status, which are treated as approved)
        return account.status === 'approved' || !account.status;
    });
    
    // Add approved accounts only
    for (const account of approvedAccounts) {
        const accountProfile = await getAccountProfile(account.username);
        const accountMotorcycles = await getAccountMotorcycles(account.username);
        
        if (accountMotorcycles.length > 0) {
            const providerCard = createProviderCard(
                account.username,
                accountProfile.photo,
                accountMotorcycles.length
            );
            providersGrid.appendChild(providerCard);
        }
    }
    
    // Show message if no approved providers with motorcycles
    if (approvedAccounts.length === 0) {
        providersGrid.innerHTML = '<p style="text-align: center; padding: 2rem; color: #666; grid-column: 1 / -1;">No rental providers available at the moment.</p>';
    } else {
        // Check if any have motorcycles
        let hasMotorcycles = false;
        for (const acc of approvedAccounts) {
            const motorcycles = await getAccountMotorcycles(acc.username);
            if (motorcycles.length > 0) {
                hasMotorcycles = true;
                break;
            }
        }
        if (!hasMotorcycles) {
            providersGrid.innerHTML = '<p style="text-align: center; padding: 2rem; color: #666; grid-column: 1 / -1;">No rental providers available at the moment.</p>';
        }
    }
}

// Create a provider card element
function createProviderCard(username, photo, motorcycleCount) {
    const card = document.createElement('div');
    card.className = 'provider-card';
    if (selectedProvider === username) {
        card.classList.add('active');
    }
    
    card.onclick = () => filterByProvider(username);
    
    const displayName = username === ADMIN_CREDENTIALS.username ? 'AEROADS RENTAL' : username;
    
    card.innerHTML = `
        <div class="provider-photo">
            ${photo ? `<img src="${photo}" alt="${displayName}">` : '<div class="provider-photo-placeholder">👤</div>'}
        </div>
        <div class="provider-info">
            <h3>${displayName}</h3>
            <p>${motorcycleCount} Motorcycle${motorcycleCount !== 1 ? 's' : ''}</p>
        </div>
    `;
    
    return card;
}

// Filter motorcycles by provider
async function filterByProvider(username) {
    if (selectedProvider === username) {
        // If clicking the same provider, clear filter
        await clearProviderFilter();
        return;
    }
    
    selectedProvider = username;
    currentPage = 1; // Reset to first page when selecting provider
    
    // Update active provider display
    const activeFilter = document.getElementById('activeProviderFilter');
    const activeName = document.getElementById('activeProviderName');
    if (activeFilter && activeName) {
        const displayName = username === ADMIN_CREDENTIALS.username ? 'AEROADS RENTAL' : username;
        activeName.textContent = `Showing: ${displayName}`;
        activeFilter.style.display = 'flex';
    }
    
    // Update provider cards
    document.querySelectorAll('.provider-card').forEach(card => {
        card.classList.remove('active');
    });
    const clickedCard = Array.from(document.querySelectorAll('.provider-card')).find(card => {
        const cardName = card.querySelector('h3').textContent;
        const expectedName = username === ADMIN_CREDENTIALS.username ? 'AEROADS RENTAL' : username;
        return cardName === expectedName;
    });
    if (clickedCard) {
        clickedCard.classList.add('active');
    }
    
    // Filter and display motorcycles (all of this provider's motorcycles with pagination if needed)
    const allMotorcycles = getAllMotorcycles();
    displayBikes(allMotorcycles);
    
    // Scroll to motorcycles section
    document.getElementById('bikes').scrollIntoView({ behavior: 'smooth' });
}

// Clear provider filter
async function clearProviderFilter() {
    selectedProvider = null;
    currentPage = 1; // Reset to first page
    
    // Hide active provider display
    const activeFilter = document.getElementById('activeProviderFilter');
    if (activeFilter) {
        activeFilter.style.display = 'none';
    }
    
    // Remove active class from all provider cards
    document.querySelectorAll('.provider-card').forEach(card => {
        card.classList.remove('active');
    });
    
    // Display all motorcycles (limited to 15 on homepage)
    const allMotorcycles = await getAllMotorcycles();
    displayBikes(allMotorcycles);
}

// Display pagination controls
function displayPagination(totalPages) {
    const paginationContainer = document.getElementById('paginationContainer');
    const pageInfo = document.getElementById('pageInfo');
    const prevBtn = document.getElementById('prevPageBtn');
    const nextBtn = document.getElementById('nextPageBtn');
    
    if (!paginationContainer) return;
    
    paginationContainer.style.display = 'flex';
    pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
    
    // Enable/disable buttons
    prevBtn.disabled = currentPage === 1;
    nextBtn.disabled = currentPage === totalPages;
}

// Hide pagination controls
function hidePagination() {
    const paginationContainer = document.getElementById('paginationContainer');
    if (paginationContainer) {
        paginationContainer.style.display = 'none';
    }
}

// Change page
async function changePage(direction) {
    const allMotorcycles = await getAllMotorcycles();
    if (!Array.isArray(allMotorcycles)) {
        console.error('getAllMotorcycles() did not return an array:', allMotorcycles);
        return;
    }
    
    let filteredBikes = allMotorcycles;
    
    if (selectedProvider) {
        filteredBikes = allMotorcycles.filter(bike => (bike.owner || bike.owner_username) === selectedProvider);
    }
    
    const totalPages = Math.ceil(filteredBikes.length / itemsPerPage);
    const newPage = currentPage + direction;
    
    if (newPage >= 1 && newPage <= totalPages) {
        currentPage = newPage;
        displayBikes(allMotorcycles);
        
        // Scroll to top of motorcycles section
        document.getElementById('bikes').scrollIntoView({ behavior: 'smooth' });
    }
}

// Save profile data for current account
async function saveProfile(profileData) {
    const currentUser = getCurrentUser();
    if (!currentUser) {
        console.error('No user logged in. Cannot save profile.');
        return;
    }
    
    // Save to localStorage for immediate access
    const key = getProfileKey(currentUser);
    localStorage.setItem(key, JSON.stringify(profileData));
    
    // Also save to database via API
    try {
        await ProfilesAPI.save({
            username: currentUser,
            photo: profileData.photo || '',
            businessName: profileData.business_name || profileData.businessName || '',
            contactNumber: profileData.contact_number || profileData.contactNumber || '',
            description: profileData.description || ''
        });
    } catch (error) {
        console.error('Error saving profile to database:', error);
        // Don't throw - localStorage save was successful
    }
}

// Get all motorcycles from all accounts (for homepage display)
async function getAllMotorcycles() {
    try {
        return await MotorcyclesAPI.getAll(true); // approved_only = true
    } catch (error) {
        console.error('Error fetching all motorcycles:', error);
        return [];
    }
}

// Get motorcycles for current account (for admin panel)
async function getMotorcycles() {
    const currentUser = getCurrentUser();
    if (!currentUser) {
        // If no user logged in, return all motorcycles for homepage
        return await getAllMotorcycles();
    }
    
    try {
        return await MotorcyclesAPI.getByOwner(currentUser);
    } catch (error) {
        console.error('Error fetching motorcycles:', error);
        return [];
    }
}

// Find which account owns a motorcycle by ID
async function findMotorcycleOwner(bikeId) {
    try {
        const allBikes = await MotorcyclesAPI.getAll(true); // approved only
        const bike = allBikes.find(b => b.id === bikeId);
        return bike ? bike.owner_username : null;
    } catch (error) {
        console.error('Error finding motorcycle owner:', error);
        return null;
    }
}

// Find which account owns a motorcycle by name
async function findMotorcycleOwnerByName(bikeName) {
    try {
        const allBikes = await MotorcyclesAPI.getAll(true); // approved only
        const bike = allBikes.find(b => b.name === bikeName);
        return bike ? bike.owner_username : null;
    } catch (error) {
        console.error('Error finding motorcycle owner by name:', error);
        return null;
    }
}

// Get motorcycles for a specific account
async function getAccountMotorcycles(username) {
    try {
        return await MotorcyclesAPI.getByOwner(username);
    } catch (error) {
        console.error('Error fetching account motorcycles:', error);
        return [];
    }
}

// Save motorcycles for a specific account (use MotorcyclesAPI.create/update for individual operations)
async function saveAccountMotorcycles(username, motorcycles) {
    console.warn('saveAccountMotorcycles() called - use MotorcyclesAPI.create() or MotorcyclesAPI.update() instead');
}

// Save booking to a specific account
async function saveAccountBooking(username, booking) {
    try {
        const result = await BookingsAPI.create({
            owner: username,
            bike: booking.bike,
            customer: booking.customer,
            pickupLocation: booking.pickupLocation,
            pickupDateTime: booking.pickupDateTime,
            returnDateTime: booking.returnDateTime,
            days: booking.days,
            hours: booking.hours,
            totalPrice: booking.totalPrice
        });
        
        // Store email status in booking object for display
        if (result) {
            booking.emailSent = result.customer_email_sent || false;
            booking.emailErrors = result.email_errors || [];
        }
        
        return result;
    } catch (error) {
        console.error('Error saving booking:', error);
        throw error;
    }
}

// Save motorcycles to current account's storage (use MotorcyclesAPI.create/update for individual operations)
async function saveMotorcycles(motorcycles) {
    console.warn('saveMotorcycles() called - use MotorcyclesAPI.create() or MotorcyclesAPI.update() instead');
}

// Current selected bike for booking
let selectedBike = null;
let isAdminLoggedIn = false;
let isSuperAdminLoggedIn = false;
let pendingBooking = null; // Store booking data before confirmation

// Admin credentials (in production, this should be on a server)
const ADMIN_CREDENTIALS = {
    username: 'aeroadsshop',
    password: 'aeroadsshop123'
};

// Super Admin credentials (for admin dashboard)
const SUPER_ADMIN_CREDENTIALS = {
    username: 'admin',
    password: 'admin123'
};

// Get all accounts from localStorage
// Cache for accounts to reduce API calls
let accountsCache = null;
let accountsCacheTime = 0;
const CACHE_DURATION = 30000; // 30 seconds

// Get accounts from database
async function getAccounts() {
    try {
        // Use cache if available and fresh
        const now = Date.now();
        if (accountsCache && (now - accountsCacheTime) < CACHE_DURATION) {
            return accountsCache;
        }
        
        const result = await AccountsAPI.getAll();
        accountsCache = result;
        accountsCacheTime = now;
        return result;
    } catch (error) {
        console.error('Error fetching accounts:', error);
        return accountsCache || [];
    }
}

// Get account location by username
async function getAccountLocation(username) {
    // Default account location
    if (username === ADMIN_CREDENTIALS.username) {
        return 'Purok 4, Linibunan Madrid Surigao del Sur, AEROADS RENTAL SERVICES';
    }
    
    // Get location from database
    try {
        const account = await AccountsAPI.getByUsername(username);
        return account && account.location ? account.location : 'Location not specified';
    } catch (error) {
        console.error('Error fetching account location:', error);
        return 'Location not specified';
    }
}

// Save accounts to database (for bulk operations - use AccountsAPI.create/update for single operations)
async function saveAccounts(accounts) {
    // This function is kept for compatibility but should use AccountsAPI.create/update directly
    console.warn('saveAccounts() called - use AccountsAPI.create() or AccountsAPI.update() instead');
    accountsCache = accounts;
    accountsCacheTime = Date.now();
}

// Get all tickets from database
async function getTickets() {
    try {
        return await TicketsAPI.getAll();
    } catch (error) {
        console.error('Error fetching tickets:', error);
        return [];
    }
}

// Save tickets to database (use TicketsAPI.create() for new tickets)
async function saveTickets(tickets) {
    // This function is kept for compatibility but should use TicketsAPI.create() directly
    console.warn('saveTickets() called - use TicketsAPI.create() instead');
}

// Check if username already exists
async function usernameExists(username) {
    if (!username || typeof username !== 'string') return false;
    
    const normalizedUsername = username.trim().toLowerCase();
    if (!normalizedUsername) return false;
    
    // Check super admin account (case-insensitive) - only reserved username
    if (normalizedUsername === SUPER_ADMIN_CREDENTIALS.username.toLowerCase()) {
        return true;
    }
    
    // Check database
    try {
        return await AuthAPI.checkUsername(username);
    } catch (error) {
        console.error('Error checking username:', error);
        return false;
    }
}

// Store pending booking ID when terms need to be shown
let pendingBookingBikeId = null;

// Track selected provider filter
let selectedProvider = null;

// Pagination state
let currentPage = 1;
const itemsPerPage = 15;

// Initialize the application
document.addEventListener('DOMContentLoaded', async function() {
    // Always show main content on load, terms will show when booking
    showMainContent();
    setupEventListeners();
    setMinDate();
    await checkAdminSession();
});

// Check if terms have been accepted
function checkTermsAcceptance() {
    const termsAccepted = localStorage.getItem('motorent_terms_accepted');
    return termsAccepted === 'true';
}

// Show terms modal
function showTermsModal() {
    const termsModal = document.getElementById('termsModal');
    if (!termsModal) {
        console.error('Terms modal not found!');
        return;
    }
    console.log('Showing terms modal');
    termsModal.style.display = 'block';
    termsModal.style.zIndex = '5000'; // Ensure it's on top
}

// Accept terms and show main content
function acceptTerms() {
    console.log('Terms accepted, pendingBookingBikeId:', pendingBookingBikeId);
    localStorage.setItem('motorent_terms_accepted', 'true');
    const termsModal = document.getElementById('termsModal');
    if (termsModal) {
        termsModal.style.display = 'none';
    }
    
    // If there's a pending booking, open it after accepting terms
    if (pendingBookingBikeId !== null) {
        const bikeId = pendingBookingBikeId;
        pendingBookingBikeId = null;
        console.log('Opening booking modal for bikeId:', bikeId);
        // Small delay to ensure modal is closed
        setTimeout(async () => {
            await openBookingModalDirect(bikeId);
        }, 100);
    } else {
        console.log('No pending booking');
    }
}

// Decline terms
function declineTerms() {
    alert('You must accept the terms and conditions to book a motorcycle.');
    // Clear pending booking
    pendingBookingBikeId = null;
    document.getElementById('termsModal').style.display = 'none';
}

// Show main content
function showMainContent() {
    document.getElementById('mainContent').style.display = 'block';
    document.getElementById('termsModal').style.display = 'none';
    // Initialize the rest of the app
    initializeApp();
}

// Initialize the app
async function initializeApp() {
    // Check if admin is logged in
    if (isAdminLoggedIn) {
        await showAdminPage();
    } else {
        // Show homepage - display all motorcycles from all accounts
        await displayProviders();
        const motorcycles = await getAllMotorcycles();
        displayBikes(motorcycles);
    }
}

// Display motorcycles in the grid
function displayBikes(bikes) {
    // Validate that bikes is an array
    if (!bikes || !Array.isArray(bikes)) {
        console.error('displayBikes: bikes is not an array:', bikes);
        const bikesGrid = document.getElementById('bikesGrid');
        if (bikesGrid) {
            bikesGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; padding: 2rem;">Error loading motorcycles. Please refresh the page.</p>';
        }
        hidePagination();
        return;
    }
    
    // Apply provider filter if one is selected
    let filteredBikes = bikes;
    if (selectedProvider) {
        filteredBikes = bikes.filter(bike => (bike.owner || bike.owner_username) === selectedProvider);
    } else {
        // On homepage (no provider selected), limit to 15 newest motorcycles
        // Sort by ID descending (highest ID = newest) and take first 15
        filteredBikes = [...bikes].sort((a, b) => b.id - a.id).slice(0, 15);
    }
    
    // Sort by ID descending (newest first)
    filteredBikes = filteredBikes.sort((a, b) => b.id - a.id);
    
    const bikesGrid = document.getElementById('bikesGrid');
    bikesGrid.innerHTML = '';

    if (filteredBikes.length === 0) {
        bikesGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; padding: 2rem;">No motorcycles found matching your criteria.</p>';
        hidePagination();
        return;
    }

    // If provider is selected and has more than 15 motorcycles, use pagination
    if (selectedProvider && filteredBikes.length > itemsPerPage) {
        const totalPages = Math.ceil(filteredBikes.length / itemsPerPage);
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const paginatedBikes = filteredBikes.slice(startIndex, endIndex);
        
        paginatedBikes.forEach(bike => {
            const bikeCard = createBikeCard(bike);
            bikesGrid.appendChild(bikeCard);
        });
        
        displayPagination(totalPages);
    } else {
        // No pagination needed - show all
        filteredBikes.forEach(bike => {
            const bikeCard = createBikeCard(bike);
            bikesGrid.appendChild(bikeCard);
        });
        hidePagination();
    }
}

// Create a bike card element
function createBikeCard(bike) {
    const card = document.createElement('div');
    card.className = 'bike-card';
    const available = bike.available || 0;
    const isAvailable = available > 0;
    const availabilityClass = isAvailable ? 'available' : 'unavailable';
    const availabilityText = isAvailable ? `Available (${available})` : 'Not Available';
    
    // Display motorcycle photo if available, otherwise show emoji
    const bikeImage = bike.image 
        ? `<img src="${bike.image}" alt="${bike.name}" style="width: 100%; height: 200px; object-fit: cover; border-radius: 8px 8px 0 0;">`
        : `<div class="bike-image">🏍️</div>`;
    
    card.innerHTML = `
        ${bikeImage}
        <div class="bike-info">
            <h3 class="bike-name">${bike.name}</h3>
            <span class="bike-category">${bike.transmission.charAt(0).toUpperCase() + bike.transmission.slice(1)}</span>
            <div class="availability-badge ${availabilityClass}">${availabilityText}</div>
            <button class="btn-primary" onclick="openBookingModal(${bike.id})" ${!isAvailable ? 'disabled' : ''}>${isAvailable ? 'Book Now' : 'Not Available'}</button>
        </div>
    `;
    return card;
}

// Setup event listeners
function setupEventListeners() {
    // Category filter
    document.getElementById('categoryFilter').addEventListener('change', filterBikes);
    
    // Search input
    document.getElementById('searchInput').addEventListener('input', filterBikes);
    
    // Booking form
    document.getElementById('bookingForm').addEventListener('submit', handleBooking);
    
    // Admin login form (for super admin and regular admin)
    const adminLoginForm = document.getElementById('adminLoginForm');
    if (adminLoginForm) {
        adminLoginForm.addEventListener('submit', handleAdminLoginForm);
    }
    
    // Rental services login form (for rental providers only)
    document.getElementById('loginForm').addEventListener('submit', handleAdminLogin);
    
    // Sign up form
    const signUpForm = document.getElementById('signUpForm');
    if (signUpForm) {
        signUpForm.addEventListener('submit', handleSignUp);
    }
    
    // Check super admin session
    checkSuperAdminSession();
    
    // Ticket form
    const ticketForm = document.getElementById('ticketForm');
    if (ticketForm) {
        ticketForm.addEventListener('submit', handleTicketSubmission);
    }
    
    // Add motorcycle form
    const addMotorcycleForm = document.getElementById('addMotorcycleForm');
    if (addMotorcycleForm) {
        addMotorcycleForm.addEventListener('submit', handleAddMotorcycle);
    }
    
    // Date and time inputs for price calculation - use both 'input' and 'change' events
    const dateTimeInputs = [
        'pickupDate', 'pickupHour', 'pickupMinute', 'pickupAmPm',
        'returnDate', 'returnHour', 'returnMinute', 'returnAmPm'
    ];
    
    dateTimeInputs.forEach(inputId => {
        const input = document.getElementById(inputId);
        if (input) {
            input.addEventListener('change', calculatePrice);
            input.addEventListener('input', calculatePrice);
        }
    });
    
    // File upload preview
    document.getElementById('idPhoto').addEventListener('change', handleFileUpload);
    
    // Bike photo upload preview
    const bikePhotoInput = document.getElementById('bikePhoto');
    if (bikePhotoInput) {
        bikePhotoInput.addEventListener('change', handleBikePhotoUpload);
    }
    
    // Modal close buttons (except terms modal)
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        const closeBtn = modal.querySelector('.close');
        if (closeBtn && modal.id !== 'termsModal') {
            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
                // Clear pending booking if confirmation modal is closed
                if (modal.id === 'confirmationModal') {
                    pendingBooking = null;
                }
            });
        }
    });
    
    // Close modal when clicking outside (except terms modal)
    window.addEventListener('click', function(event) {
        modals.forEach(modal => {
            if (event.target === modal && modal.id !== 'termsModal') {
                modal.style.display = 'none';
                // Clear pending booking if confirmation modal is closed
                if (modal.id === 'confirmationModal') {
                    pendingBooking = null;
                }
            }
        });
    });
}

// Filter bikes based on transmission and search
function filterBikes() {
    const transmission = document.getElementById('categoryFilter').value;
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    // Use all motorcycles for homepage search
    const motorcycles = isAdminLoggedIn ? getMotorcycles() : getAllMotorcycles();
    
    let filtered = motorcycles;
    
    // Filter by transmission type
    if (transmission !== 'all') {
        filtered = filtered.filter(bike => bike.transmission === transmission);
    }
    
    // Filter by search term
    if (searchTerm) {
        filtered = filtered.filter(bike => 
            bike.name.toLowerCase().includes(searchTerm) ||
            bike.transmission.toLowerCase().includes(searchTerm) ||
            (bike.description && bike.description.toLowerCase().includes(searchTerm))
        );
    }
    
    // Sort by ID descending (newest first)
    filtered = filtered.sort((a, b) => b.id - a.id);
    
    // If no provider selected and on homepage, limit to 15
    if (!selectedProvider && !isAdminLoggedIn) {
        filtered = filtered.slice(0, 15);
    }
    
    // Temporarily override displayBikes to use the filtered list
    const bikesGrid = document.getElementById('bikesGrid');
    bikesGrid.innerHTML = '';

    if (filtered.length === 0) {
        bikesGrid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; padding: 2rem;">No motorcycles found matching your criteria.</p>';
        hidePagination();
        return;
    }

    // If provider is selected and has more than 15 motorcycles, use pagination
    if (selectedProvider && filtered.length > itemsPerPage) {
        const totalPages = Math.ceil(filtered.length / itemsPerPage);
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const paginatedBikes = filtered.slice(startIndex, endIndex);
        
        paginatedBikes.forEach(bike => {
            const bikeCard = createBikeCard(bike);
            bikesGrid.appendChild(bikeCard);
        });
        
        displayPagination(totalPages);
    } else {
        // No pagination needed - show all
        filtered.forEach(bike => {
            const bikeCard = createBikeCard(bike);
            bikesGrid.appendChild(bikeCard);
        });
        hidePagination();
    }
}

// Open booking modal (checks terms first)
function openBookingModal(bikeId) {
    console.log('openBookingModal called with bikeId:', bikeId);
    
    // Make sure booking modal is closed first
    const bookingModal = document.getElementById('bookingModal');
    if (bookingModal) {
        bookingModal.style.display = 'none';
    }
    
    // ALWAYS show terms modal first before booking form
    // Store the bike ID and show terms modal FIRST
    pendingBookingBikeId = bikeId;
    showTermsModal();
    
    // Don't open booking form yet - wait for terms acceptance
    return;
}

// Open booking modal directly (after terms check)
async function openBookingModalDirect(bikeId) {
    // Use all motorcycles to find the bike (for booking from homepage)
    const motorcycles = await getAllMotorcycles();
    if (!Array.isArray(motorcycles)) {
        console.error('getAllMotorcycles() did not return an array:', motorcycles);
        alert('Error loading motorcycles. Please try again.');
        return;
    }
    
    selectedBike = motorcycles.find(bike => bike.id === bikeId);
    if (!selectedBike) {
        alert('Motorcycle not found.');
        return;
    }
    
    // Check availability
    if (!selectedBike.available || selectedBike.available <= 0) {
        alert('This motorcycle is currently not available for booking.');
        return;
    }
    
    // Get the owner's location - handle both 'owner' and 'owner_username' field names
    const ownerUsername = selectedBike.owner || selectedBike.owner_username;
    if (!ownerUsername) {
        console.error('Motorcycle has no owner:', selectedBike);
        alert('Error: Motorcycle owner information is missing.');
        return;
    }
    
    const ownerLocation = await getAccountLocation(ownerUsername);
    
    const modal = document.getElementById('bookingModal');
    const bikeDetails = document.getElementById('bikeDetails');
    
    bikeDetails.innerHTML = `
        <h3>${selectedBike.name}</h3>
        <p><strong>Available:</strong> ${selectedBike.available} unit(s)</p>
        <p><strong>Pickup Location:</strong> <span style="color: #4CAF50; font-weight: 600;">${ownerLocation}</span></p>
    `;
    
    // Reset form
    document.getElementById('bookingForm').reset();
    document.getElementById('totalPrice').textContent = '₱0.00';
    document.getElementById('rentalDuration').textContent = '-';
    document.getElementById('idPreview').style.display = 'none';
    
    modal.style.display = 'block';
    setMinDate();
    calculatePrice();
}

// Convert 12-hour time to 24-hour format
function convertTo24Hour(hour, minute, amPm) {
    let hour24 = parseInt(hour);
    if (amPm === 'PM' && hour24 !== 12) {
        hour24 += 12;
    } else if (amPm === 'AM' && hour24 === 12) {
        hour24 = 0;
    }
    return `${hour24.toString().padStart(2, '0')}:${minute}`;
}

// Get combined date and time as Date object
function getDateTime(dateValue, hour, minute, amPm) {
    if (!dateValue || !hour || !minute || !amPm) return null;
    const time24 = convertTo24Hour(hour, minute, amPm);
    return new Date(`${dateValue}T${time24}:00`);
}

// Set minimum date to today
function setMinDate() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('pickupDate').setAttribute('min', today);
    document.getElementById('returnDate').setAttribute('min', today);
}

// Calculate total price based on rental duration
function calculatePrice() {
    if (!selectedBike) {
        // Reset display if no bike selected
        const totalPriceElement = document.getElementById('totalPrice');
        const rentalDurationElement = document.getElementById('rentalDuration');
        if (totalPriceElement) totalPriceElement.textContent = '₱0.00';
        if (rentalDurationElement) rentalDurationElement.textContent = '-';
        return;
    }
    
    const pickupDateEl = document.getElementById('pickupDate');
    const pickupHourEl = document.getElementById('pickupHour');
    const pickupMinuteEl = document.getElementById('pickupMinute');
    const pickupAmPmEl = document.getElementById('pickupAmPm');
    const returnDateEl = document.getElementById('returnDate');
    const returnHourEl = document.getElementById('returnHour');
    const returnMinuteEl = document.getElementById('returnMinute');
    const returnAmPmEl = document.getElementById('returnAmPm');
    
    if (!pickupDateEl || !returnDateEl) return;
    
    const pickupDate = pickupDateEl.value || '';
    const pickupHour = pickupHourEl?.value || '';
    const pickupMinute = pickupMinuteEl?.value || '';
    const pickupAmPm = pickupAmPmEl?.value || '';
    
    const returnDate = returnDateEl.value || '';
    const returnHour = returnHourEl?.value || '';
    const returnMinute = returnMinuteEl?.value || '';
    const returnAmPm = returnAmPmEl?.value || '';
    
    const pickup = getDateTime(pickupDate, pickupHour, pickupMinute, pickupAmPm);
    const returnD = getDateTime(returnDate, returnHour, returnMinute, returnAmPm);
    
    if (pickup && returnD) {
        if (returnD >= pickup) {
            // Calculate duration in hours
            const hours = (returnD - pickup) / (1000 * 60 * 60);
            const days = Math.floor(hours / 24);
            const remainingHours = hours % 24;
            
            let total = 0;
            let durationText = '';
            
            // Pricing structure:
            // 150 pesos for 3 hours
            // 400 pesos for 6 hours
            // 700 pesos for 12 hours
            // 1000 pesos for 1 day (24 hours)
            
            if (hours <= 3) {
                total = 150;
                durationText = `${hours.toFixed(1)} hours (3-hour rate)`;
            } else if (hours <= 6) {
                total = 400;
                durationText = `${hours.toFixed(1)} hours (6-hour rate)`;
            } else if (hours <= 12) {
                total = 700;
                durationText = `${hours.toFixed(1)} hours (12-hour rate)`;
            } else if (hours <= 24) {
                total = 1000;
                durationText = `${hours.toFixed(1)} hours (1-day rate)`;
            } else {
                // For more than 1 day, calculate based on days and remaining hours
                total = days * 1000;
                if (remainingHours > 0) {
                    if (remainingHours <= 3) {
                        total += 150;
                    } else if (remainingHours <= 6) {
                        total += 400;
                    } else if (remainingHours <= 12) {
                        total += 700;
                    } else {
                        total += 1000; // Another full day
                    }
                }
                durationText = `${days} day(s) ${remainingHours > 0 ? remainingHours.toFixed(1) + ' hours' : ''}`;
            }
            
            // Update total price and duration display
            const totalPriceElement = document.getElementById('totalPrice');
            const rentalDurationElement = document.getElementById('rentalDuration');
            
            if (totalPriceElement) {
                totalPriceElement.textContent = `₱${total.toFixed(2)}`;
            }
            if (rentalDurationElement) {
                rentalDurationElement.textContent = durationText;
            }
        } else {
            // Return date is before pickup date
            const totalPriceElement = document.getElementById('totalPrice');
            const rentalDurationElement = document.getElementById('rentalDuration');
            
            if (totalPriceElement) {
                totalPriceElement.textContent = '₱0.00';
            }
            if (rentalDurationElement) {
                rentalDurationElement.textContent = '-';
            }
        }
    } else {
        // Missing date/time inputs
        const totalPriceElement = document.getElementById('totalPrice');
        const rentalDurationElement = document.getElementById('rentalDuration');
        
        if (totalPriceElement) {
            totalPriceElement.textContent = '₱0.00';
        }
        if (rentalDurationElement) {
            rentalDurationElement.textContent = '-';
        }
    }
}

// Handle file upload and show preview
function handleFileUpload(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(event) {
            document.getElementById('idPreviewImg').src = event.target.result;
            document.getElementById('idPreview').style.display = 'block';
        };
        reader.readAsDataURL(file);
    }
}

// Capture photo using device camera
function capturePhoto() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Camera access is not available in your browser.');
        return;
    }
    
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(function(stream) {
            // Create video element
            const video = document.createElement('video');
            video.srcObject = stream;
            video.play();
            
            // Create modal for camera preview
            const cameraModal = document.createElement('div');
            cameraModal.className = 'modal';
            cameraModal.id = 'idCameraModal';
            cameraModal.style.zIndex = '6000';
            cameraModal.innerHTML = `
                <div class="modal-content" style="max-width: 500px;">
                    <span class="close" onclick="closeIdCameraModal()">&times;</span>
                    <h2>Capture ID Photo</h2>
                    <div style="text-align: center; margin: 20px 0;">
                        <video id="idCameraVideo" autoplay style="width: 100%; max-width: 400px; border-radius: 8px;"></video>
                    </div>
                    <div style="text-align: center;">
                        <button class="btn-primary" onclick="captureIdPhotoSnapshot()">Capture</button>
                        <button class="btn-secondary" onclick="closeIdCameraModal()">Cancel</button>
                    </div>
                </div>
            `;
            document.body.appendChild(cameraModal);
            cameraModal.style.display = 'block';
            
            // Set video source
            setTimeout(() => {
                const videoElement = document.getElementById('idCameraVideo');
                if (videoElement) {
                    videoElement.srcObject = stream;
                }
            }, 100);
            
            // Store stream for cleanup
            window.idCameraStream = stream;
        })
        .catch(function(error) {
            console.error('Error accessing camera:', error);
            alert('Error accessing camera. Please make sure you grant camera permissions.');
        });
}

// Close ID camera modal
function closeIdCameraModal() {
    const cameraModal = document.getElementById('idCameraModal');
    if (cameraModal) {
        if (window.idCameraStream) {
            window.idCameraStream.getTracks().forEach(track => track.stop());
            window.idCameraStream = null;
        }
        cameraModal.remove();
    }
}

// Capture ID photo snapshot
async function captureIdPhotoSnapshot() {
    const video = document.getElementById('idCameraVideo');
    if (!video) {
        return;
    }
    
    try {
        // Create canvas and capture frame
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        
        // Convert to blob
        canvas.toBlob(async function(blob) {
            if (!blob) {
                alert('Error capturing photo.');
                return;
            }
            
            try {
                // Compress the image
                const compressedImageData = await compressImage(blob, 800, 800, 0.7);
                
                // Update file input
                const fileInput = document.getElementById('idPhoto');
                const response = await fetch(compressedImageData);
                const blobFile = await response.blob();
                const file = new File([blobFile], 'id-photo.jpg', { type: 'image/jpeg' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;
                
                // Show preview
                document.getElementById('idPreviewImg').src = compressedImageData;
                document.getElementById('idPreview').style.display = 'block';
                
                // Close camera modal
                closeIdCameraModal();
            } catch (error) {
                console.error('Error processing image:', error);
                alert('Error processing image. Please try again.');
            }
        }, 'image/jpeg', 0.8);
    } catch (error) {
        console.error('Error capturing photo:', error);
        alert('Error capturing photo. Please try again.');
    }
}

// Remove photo
function removePhoto() {
    document.getElementById('idPhoto').value = '';
    document.getElementById('idPreview').style.display = 'none';
    document.getElementById('idPreviewImg').src = '';
}

// Handle bike photo upload
async function handleBikePhotoUpload(e) {
    const file = e.target.files[0];
    if (file) {
        try {
            // compressImage returns a base64 string, so we can use it directly
            const compressedImageData = await compressImage(file, 800, 800, 0.7);
            document.getElementById('bikePhotoPreviewImg').src = compressedImageData;
            document.getElementById('bikePhotoPreview').style.display = 'block';
        } catch (error) {
            console.error('Error compressing image:', error);
            alert('Error processing image. Please try again.');
        }
    }
}

// Capture bike photo
function captureBikePhoto() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Camera access is not available in your browser.');
        return;
    }
    
    navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
        .then(function(stream) {
            // Create video element
            const video = document.createElement('video');
            video.srcObject = stream;
            video.play();
            
            // Create modal for camera preview
            const cameraModal = document.createElement('div');
            cameraModal.className = 'modal';
            cameraModal.id = 'bikeCameraModal';
            cameraModal.style.zIndex = '6000';
            cameraModal.innerHTML = `
                <div class="modal-content" style="max-width: 500px;">
                    <span class="close" onclick="closeBikeCameraModal()">&times;</span>
                    <h2>Capture Motorcycle Photo</h2>
                    <div style="text-align: center; margin: 20px 0;">
                        <video id="bikeCameraVideo" autoplay style="width: 100%; max-width: 400px; border-radius: 8px;"></video>
                    </div>
                    <div style="text-align: center;">
                        <button class="btn-primary" onclick="captureBikePhotoSnapshot()">Capture</button>
                        <button class="btn-secondary" onclick="closeBikeCameraModal()">Cancel</button>
                    </div>
                </div>
            `;
            document.body.appendChild(cameraModal);
            cameraModal.style.display = 'block';
            
            // Set video source
            setTimeout(() => {
                const videoElement = document.getElementById('bikeCameraVideo');
                if (videoElement) {
                    videoElement.srcObject = stream;
                }
            }, 100);
            
            // Store stream for cleanup
            window.bikeCameraStream = stream;
        })
        .catch(function(error) {
            console.error('Error accessing camera:', error);
            alert('Error accessing camera. Please make sure you grant camera permissions.');
        });
}

// Close bike camera modal
function closeBikeCameraModal() {
    const cameraModal = document.getElementById('bikeCameraModal');
    if (cameraModal) {
        if (window.bikeCameraStream) {
            window.bikeCameraStream.getTracks().forEach(track => track.stop());
            window.bikeCameraStream = null;
        }
        cameraModal.remove();
    }
}

// Capture bike photo snapshot
async function captureBikePhotoSnapshot() {
    const video = document.getElementById('bikeCameraVideo');
    if (!video) {
        return;
    }
    
    try {
        // Create canvas and capture frame
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        
        // Convert to blob
        canvas.toBlob(async function(blob) {
            if (!blob) {
                alert('Error capturing photo.');
                return;
            }
            
            try {
                // Compress the image
                const compressedImageData = await compressImage(blob, 800, 800, 0.7);
                
                // Update file input
                const fileInput = document.getElementById('bikePhoto');
                const response = await fetch(compressedImageData);
                const blobFile = await response.blob();
                const file = new File([blobFile], 'bike-photo.jpg', { type: 'image/jpeg' });
                const dataTransfer = new DataTransfer();
                dataTransfer.items.add(file);
                fileInput.files = dataTransfer.files;
                
                // Show preview
                document.getElementById('bikePhotoPreviewImg').src = compressedImageData;
                document.getElementById('bikePhotoPreview').style.display = 'block';
                
                // Close camera modal
                closeBikeCameraModal();
            } catch (error) {
                console.error('Error processing image:', error);
                alert('Error processing image. Please try again.');
            }
        }, 'image/jpeg', 0.7);
    } catch (error) {
        console.error('Error capturing photo:', error);
        alert('Error capturing photo. Please try again.');
    }
}

// Remove bike photo
function removeBikePhoto() {
    document.getElementById('bikePhoto').value = '';
    document.getElementById('bikePhotoPreview').style.display = 'none';
    document.getElementById('bikePhotoPreviewImg').src = '';
}

// Compress image to reduce file size
function compressImage(file, maxWidth = 800, maxHeight = 800, quality = 0.7) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = function(event) {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                let width = img.width;
                let height = img.height;
                
                // Calculate new dimensions
                if (width > height) {
                    if (width > maxWidth) {
                        height = (height * maxWidth) / width;
                        width = maxWidth;
                    }
                } else {
                    if (height > maxHeight) {
                        width = (width * maxHeight) / height;
                        height = maxHeight;
                    }
                }
                
                canvas.width = width;
                canvas.height = height;
                
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);
                
                // Convert to base64 with compression
                canvas.toBlob(function(blob) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        resolve(e.target.result);
                    };
                    reader.onerror = reject;
                    reader.readAsDataURL(blob);
                }, 'image/jpeg', quality);
            };
            img.onerror = reject;
            img.src = event.target.result;
        };
        reader.onerror = reject;
        reader.readAsDataURL(file);
    });
}

// Handle booking form submission
function handleBooking(e) {
    e.preventDefault();
    
    if (!selectedBike) return;
    
    const pickupDate = document.getElementById('pickupDate').value;
    const pickupHour = document.getElementById('pickupHour').value;
    const pickupMinute = document.getElementById('pickupMinute').value;
    const pickupAmPm = document.getElementById('pickupAmPm').value;
    
    const returnDate = document.getElementById('returnDate').value;
    const returnHour = document.getElementById('returnHour').value;
    const returnMinute = document.getElementById('returnMinute').value;
    const returnAmPm = document.getElementById('returnAmPm').value;
    
    const customerName = document.getElementById('customerName').value;
    const customerEmail = document.getElementById('customerEmail').value;
    const customerAddress = document.getElementById('customerAddress').value;
    const idPhoto = document.getElementById('idPhoto').files[0];
    
    // Validate all time fields are filled
    if (!pickupDate || !pickupHour || !pickupMinute || !pickupAmPm) {
        alert('Please fill in all pickup date and time fields!');
        return;
    }
    
    if (!returnDate || !returnHour || !returnMinute || !returnAmPm) {
        alert('Please fill in all return date and time fields!');
        return;
    }
    
    // Validate Gmail
    if (!customerEmail.includes('@gmail.com')) {
        alert('Please enter a valid Gmail address!');
        return;
    }
    
    // Validate Address
    if (!customerAddress || customerAddress.trim() === '') {
        alert('Please enter your address!');
        return;
    }
    
    // Validate ID photo
    if (!idPhoto) {
        alert('Please upload or capture your license/valid ID photo!');
        return;
    }
    
    // Get date objects
    const pickup = getDateTime(pickupDate, pickupHour, pickupMinute, pickupAmPm);
    const returnD = getDateTime(returnDate, returnHour, returnMinute, returnAmPm);
    
    if (!pickup || !returnD) {
        alert('Please fill in all date and time fields correctly!');
        return;
    }
    
    if (returnD < pickup) {
        alert('Return date/time must be after pickup date/time!');
        return;
    }
    
    // Create datetime strings for storage
    const pickupDateTime = `${pickupDate}T${convertTo24Hour(pickupHour, pickupMinute, pickupAmPm)}:00`;
    const returnDateTime = `${returnDate}T${convertTo24Hour(returnHour, returnMinute, returnAmPm)}:00`;
    
    // Calculate duration and price
    const hours = (returnD - pickup) / (1000 * 60 * 60);
    const days = Math.floor(hours / 24);
    const remainingHours = hours % 24;
    
    let total = 0;
    
    if (hours <= 3) {
        total = 150;
    } else if (hours <= 6) {
        total = 400;
    } else if (hours <= 12) {
        total = 700;
    } else if (hours <= 24) {
        total = 1000;
    } else {
        total = days * 1000;
        if (remainingHours > 0) {
            if (remainingHours <= 3) {
                total += 150;
            } else if (remainingHours <= 6) {
                total += 400;
            } else if (remainingHours <= 12) {
                total += 700;
            } else {
                total += 1000;
            }
        }
    }
    
    // Compress and convert photo to base64 for storage
    compressImage(idPhoto, 800, 800, 0.6)
        .then(async function(idPhotoBase64) {
            // Format dates for display
            const pickupDisplay = new Date(pickup).toLocaleString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            const returnDisplay = new Date(returnD).toLocaleString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
            
            const durationText = days > 0 
                ? `${days} day(s) ${(hours % 24).toFixed(1)} hours`
                : `${hours.toFixed(1)} hours`;
            
            // Get pickup location from motorcycle owner
            const ownerUsername = selectedBike ? (selectedBike.owner || selectedBike.owner_username) : null;
            const pickupLocation = ownerUsername 
                ? await getAccountLocation(ownerUsername) 
                : 'Purok 4, Linibunan Madrid Surigao del Sur, AEROADS RENTAL SERVICES';
            
            // Create booking object (temporary, not saved yet)
            const booking = {
                id: Date.now(), // Unique ID for the booking
                bike: selectedBike.name,
                bikeId: selectedBike.id,
                pickupDateTime: pickupDateTime,
                returnDateTime: returnDateTime,
                pickupLocation: pickupLocation,
                hours: hours,
                days: days,
                totalPrice: total,
                bookingDate: new Date().toISOString(),
                lateFeePerHour: 100, // Late return fee
                customer: {
                    name: customerName,
                    email: customerEmail,
                    address: customerAddress,
                    idPhoto: idPhotoBase64
                }
            };
            
            // Store booking temporarily
            pendingBooking = booking;
            
            // Show confirmation modal with booking details
            showConfirmationModal(booking, pickupDisplay, returnDisplay, durationText);
            
            // Close booking modal
            document.getElementById('bookingModal').style.display = 'none';
        })
        .catch(function(error) {
            console.error('Error compressing image:', error);
            alert('Error processing image. Please try again with a smaller image file.');
        });
}

// Show confirmation modal
function showConfirmationModal(booking, pickupDisplay, returnDisplay, durationText) {
    const modal = document.getElementById('confirmationModal');
    const details = document.getElementById('confirmationDetails');
    
    details.innerHTML = `
        <div class="confirmation-section">
            <h3>Booking Summary</h3>
            <p><strong>Name:</strong> ${booking.customer.name}</p>
            <p><strong>Email:</strong> ${booking.customer.email}</p>
            <p><strong>Address:</strong> ${booking.customer.address}</p>
            <p><strong>Motorcycle:</strong> ${booking.bike}</p>
            <p><strong>Pickup Location:</strong> <span style="color: #4CAF50; font-weight: 600;">${booking.pickupLocation || 'Purok 4, Linibunan Madrid Surigao del Sur, AEROADS RENTAL SERVICES'}</span></p>
            <p><strong>Pickup:</strong> ${pickupDisplay}</p>
            <p><strong>Return:</strong> ${returnDisplay}</p>
            <p><strong>Duration:</strong> ${durationText}</p>
            <p><strong>Total Price:</strong> <span style="color: #ff6b6b; font-size: 1.2rem; font-weight: bold;">₱${booking.totalPrice.toFixed(2)}</span></p>
            <p style="color: #ff6b6b; margin-top: 1rem;"><strong>⚠️ Note:</strong> Late return fee is ₱100 per hour</p>
            <p style="margin-top: 1rem;">Please review your booking details and confirm to proceed.</p>
        </div>
    `;
    
    modal.style.display = 'block';
}

// Close confirmation modal
function closeConfirmationModal() {
    document.getElementById('confirmationModal').style.display = 'none';
    pendingBooking = null;
}

// Confirm booking and save it
async function confirmBooking() {
    if (!pendingBooking) {
        alert('No booking to confirm!');
        return;
    }
    
    // Find which account owns this motorcycle
    const owner = await findMotorcycleOwner(selectedBike.id);
    if (!owner) {
        alert('Error: Could not find motorcycle owner. Booking not saved.');
        return;
    }
    
    // Decrease motorcycle availability for the owner
    const ownerMotorcycles = await getAccountMotorcycles(owner);
    const bikeIndex = ownerMotorcycles.findIndex(bike => bike.id === selectedBike.id);
    if (bikeIndex !== -1 && ownerMotorcycles[bikeIndex].available > 0) {
        ownerMotorcycles[bikeIndex].available -= 1;
        await saveAccountMotorcycles(owner, ownerMotorcycles);
    }
    
    // Save booking to the owner's account
    try {
        const result = await saveAccountBooking(owner, pendingBooking);
        
        // Check if pendingBooking is still valid before setting properties
        if (!pendingBooking) {
            console.error('pendingBooking became null during save operation');
            alert('Error: Booking data was lost. Please try again.');
            return;
        }
        
        // Store email status
        pendingBooking.emailSent = result?.customer_email_sent || false;
        pendingBooking.emailErrors = result?.email_errors || [];
        
        // Refresh bike display if on homepage
        if (!isAdminLoggedIn) {
            displayProviders();
            const allMotorcycles = await getAllMotorcycles();
            displayBikes(allMotorcycles);
        }
        
        // Show success message
        showSuccessModal(pendingBooking);
        
        // Close confirmation modal
        document.getElementById('confirmationModal').style.display = 'none';
        
        // Clear pending booking
        const confirmedBooking = pendingBooking;
        pendingBooking = null;
        
        console.log('Booking confirmed and saved:', confirmedBooking);
    } catch (error) {
        console.error('Error saving booking:', error);
        alert('Error saving booking. Please try again.');
    }
}

// Show success modal
function showSuccessModal(booking) {
    const modal = document.getElementById('successModal');
    const message = document.getElementById('successMessage');
    
    const pickupDate = new Date(booking.pickupDateTime).toLocaleString('en-US');
    const returnDate = new Date(booking.returnDateTime).toLocaleString('en-US');
    const durationText = booking.days > 0 
        ? `${booking.days} day(s) ${(booking.hours % 24).toFixed(1)} hours`
        : `${booking.hours.toFixed(1)} hours`;
    
    // Check if email was sent
    const emailSent = booking.emailSent !== false; // Default to true if not specified
    const emailStatus = emailSent 
        ? `<p style="color: #4caf50;">✅ A confirmation email has been sent to ${booking.customer.email}</p>`
        : `<p style="color: #ff9800;">⚠️ Confirmation email could not be sent to ${booking.customer.email}. Please check your email or contact support.</p>`;
    
    message.innerHTML = `
        <p><strong>Thank you, ${booking.customer.name}!</strong></p>
        <p>Your booking for <strong>${booking.bike}</strong> has been confirmed.</p>
        <p><strong>Pickup:</strong> ${pickupDate}</p>
        <p><strong>Return:</strong> ${returnDate}</p>
        <p><strong>Duration:</strong> ${durationText}</p>
        <p><strong>Total:</strong> <strong>₱${booking.totalPrice.toFixed(2)}</strong></p>
        <p><strong>⚠️ Note:</strong> Late return fee is ₱100 per hour</p>
        ${emailStatus}
    `;
    
    modal.style.display = 'block';
}

// Close success modal
function closeSuccessModal() {
    document.getElementById('successModal').style.display = 'none';
}

// Save booking to localStorage with error handling
function saveBooking(booking) {
    const currentUser = getCurrentUser();
    if (!currentUser) {
        console.error('No user logged in. Cannot save booking.');
        alert('Error: You must be logged in to save bookings.');
        return;
    }
    
    const key = getBookingsKey(currentUser);
    
    try {
        let bookings = getBookings();
        bookings.push(booking);
        
        // Try to save
        const dataString = JSON.stringify(bookings);
        
        // Check if data is too large (approximate check)
        if (dataString.length > 4 * 1024 * 1024) { // 4MB warning
            console.warn('Warning: Booking data is getting large. Consider cleaning old bookings.');
        }
        
        // This function is now handled by saveAccountBooking which uses the API
        // Keeping this for backward compatibility but it should not be called
        console.warn('saveAccountBookingLegacy called - this should use the API instead');
    } catch (error) {
        console.error('Error in legacy booking save:', error);
        throw error;
    }
}

// Clear old bookings to free up space
function clearOldBookings(keepCount = 10) {
    const currentUser = getCurrentUser();
    if (!currentUser) {
        return;
    }
    
    const key = getBookingsKey(currentUser);
    
    try {
        let bookings = getBookings();
        
        if (bookings.length <= keepCount) {
            return; // No need to clear
        }
        
        // Sort by booking date (newest first)
        bookings.sort((a, b) => {
            const dateA = new Date(a.bookingDate || 0);
            const dateB = new Date(b.bookingDate || 0);
            return dateB - dateA;
        });
        
        // Keep only the most recent bookings
        const recentBookings = bookings.slice(0, keepCount);
        localStorage.setItem(key, JSON.stringify(recentBookings));
        
        console.log(`Cleared ${bookings.length - keepCount} old bookings.`);
    } catch (error) {
        console.error('Error clearing old bookings:', error);
    }
}

// Get all bookings from database for current account
async function getBookings() {
    const currentUser = getCurrentUser();
    if (!currentUser) {
        return [];
    }
    
    try {
        return await BookingsAPI.getAll(currentUser);
    } catch (error) {
        console.error('Error fetching bookings:', error);
        return [];
    }
}

// Open login modal
// Open admin login modal (for super admin and regular admin)
function openAdminLoginModal() {
    document.getElementById('adminLoginModal').style.display = 'block';
}

// Open rental services login modal (for rental providers only)
function openLoginModal() {
    document.getElementById('loginModal').style.display = 'block';
}

// Handle admin login form (for super admin only)
async function handleAdminLoginForm(e) {
    e.preventDefault();
    
    const username = document.getElementById('adminLoginUsername').value;
    const password = document.getElementById('adminLoginPassword').value;
    
    // Check super admin credentials only
    if (username === SUPER_ADMIN_CREDENTIALS.username && password === SUPER_ADMIN_CREDENTIALS.password) {
        isSuperAdminLoggedIn = true;
        localStorage.setItem('motorent_super_admin_logged_in', 'true');
        await updateSuperAdminUI();
        document.getElementById('adminLoginModal').style.display = 'none';
        document.getElementById('adminLoginForm').reset();
        // Show super admin page and hide main content
        document.getElementById('mainContent').style.display = 'none';
        document.getElementById('adminPage').style.display = 'none';
        document.getElementById('superAdminPage').style.display = 'block';
        await displaySuperAdminDashboard();
        setupSuperAdminSearch();
        await updateTicketNotification();
        return;
    }
    
    alert('Invalid super admin credentials!');
}

// Handle rental services login (for rental providers only - no super admin access)
async function handleAdminLogin(e) {
    e.preventDefault();
    
    const username = document.getElementById('adminUsername').value;
    const password = document.getElementById('adminPassword').value;
    
    if (!username || !password) {
        alert('Please enter both username and password.');
        return;
    }
    
    try {
        const result = await AuthAPI.login(username.trim(), password);
        
        if (result && result.success && result.user) {
            const account = result.user;
            
            if (account.status === 'blocked') {
                alert('Your account has been blocked. Please contact support.');
                return;
            }
            if (account.status === 'denied') {
                alert('Your account has been denied. Please contact support.');
                return;
            }
            if (account.status === 'pending') {
                alert('Your account is pending approval. Please wait for admin approval.');
                return;
            }
            if (account.status === 'approved' || !account.status) {
                isAdminLoggedIn = true;
                localStorage.setItem('motorent_admin_logged_in', 'true');
                localStorage.setItem('motorent_current_user', username.trim());
                // Hide super admin page and show admin page
                document.getElementById('superAdminPage').style.display = 'none';
                document.getElementById('mainContent').style.display = 'none';
                await updateAdminUI();
                document.getElementById('loginModal').style.display = 'none';
                document.getElementById('loginForm').reset();
                alert('Login successful!');
            } else {
                alert('Account status issue. Please contact support.');
            }
        } else {
            // Handle API response errors
            const errorMsg = (result && result.error) ? result.error : 'Invalid username or password!';
            console.error('Login failed:', result);
            alert(errorMsg);
        }
    } catch (error) {
        console.error('Login error details:', {
            error: error,
            message: error?.message,
            stack: error?.stack,
            name: error?.name
        });
        
        // Extract error message from various possible error formats
        let errorMsg = 'Error during login. Please try again.';
        if (error) {
            if (error.message) {
                errorMsg = error.message;
            } else if (typeof error === 'string') {
                errorMsg = error;
            } else if (error.toString && error.toString() !== '[object Object]') {
                errorMsg = error.toString();
            }
        }
        
        alert(errorMsg);
    }
}

// Validate email format
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Open sign up modal
function openSignUpModal() {
    document.getElementById('loginModal').style.display = 'none';
    const signUpModal = document.getElementById('signUpModal');
    if (signUpModal) {
        signUpModal.style.display = 'block';
        signUpModal.style.zIndex = '5000';
        const form = document.getElementById('signUpForm');
        if (form) {
            form.reset();
            // Focus on first input field
            setTimeout(() => {
                const firstInput = document.getElementById('signUpUsername');
                if (firstInput) {
                    firstInput.focus();
                }
            }, 100);
        }
    }
}

// Handle sign up
async function handleSignUp(e) {
    e.preventDefault();
    
    const username = document.getElementById('signUpUsername').value.trim();
    const email = document.getElementById('signUpEmail').value.trim();
    const password = document.getElementById('signUpPassword').value;
    const confirmPassword = document.getElementById('signUpConfirmPassword').value;
    const location = document.getElementById('signUpLocation').value.trim();
    const licenseFile = document.getElementById('signUpLicense').files[0];
    const validIdFile = document.getElementById('signUpValidId').files[0];
    
    // Validate inputs
    if (!username || username.length < 3) {
        alert('Username must be at least 3 characters long.');
        return;
    }
    
    if (!email || !isValidEmail(email)) {
        alert('Please enter a valid email address.');
        return;
    }
    
    if (!password || password.length < 6) {
        alert('Password must be at least 6 characters long.');
        return;
    }
    
    if (password !== confirmPassword) {
        alert('Passwords do not match!');
        return;
    }
    
    if (!location || location.length < 5) {
        alert('Please enter a valid pickup location/address (at least 5 characters).');
        return;
    }
    
    if (!licenseFile) {
        alert('Please upload the front side of your driver\'s license.');
        return;
    }
    
    if (!validIdFile) {
        alert('Please upload the back side of your driver\'s license.');
        return;
    }
    
    // Check if username already exists
    const usernameExistsResult = await usernameExists(username);
    if (usernameExistsResult) {
        const normalizedUsername = username.trim().toLowerCase();
        // Check if it's a reserved username (only super admin)
        if (normalizedUsername === SUPER_ADMIN_CREDENTIALS.username.toLowerCase()) {
            alert('The username "admin" is reserved for the super admin account. Please choose a different username.');
        } else {
            alert('Username already exists! Please choose a different username.');
        }
        return;
    }
    
    // Check if email already exists
    const emailExistsResult = await AuthAPI.checkEmail(email);
    if (emailExistsResult) {
        alert('Email address already exists! Please use a different email address.');
        return;
    }
    
    try {
        // Compress and convert driver's license photos to base64 (front and back)
        const licenseFrontBase64 = await compressImage(licenseFile, 800, 800, 0.7);
        const licenseBackBase64 = await compressImage(validIdFile, 800, 800, 0.7);
        
        // Create new account in database
        await AccountsAPI.create({
            username: username,
            email: email,
            password: password, // Will be hashed on server
            location: location,
            license: licenseFrontBase64, // Driver's license front
            validId: licenseBackBase64, // Driver's license back
            status: 'pending'
        });
        
        // Clear cache
        accountsCache = null;
        
        alert('Account created successfully! Your account is pending admin approval. You will be notified once approved.');
        document.getElementById('signUpModal').style.display = 'none';
        document.getElementById('signUpForm').reset();
        document.getElementById('loginModal').style.display = 'block';
    } catch (error) {
        console.error('Error creating account:', error);
        if (error.message && error.message.includes('already exists')) {
            alert('Username or email already exists! Please use different credentials.');
        } else {
            alert('Error creating account. Please try again.');
        }
    }
}

// Check if admin is logged in
async function checkAdminSession() {
    const loggedIn = localStorage.getItem('motorent_admin_logged_in');
    const currentUser = localStorage.getItem('motorent_current_user');
    
    if (loggedIn === 'true' && currentUser) {
        // Check if it's the default admin account (cannot be blocked)
        if (currentUser === ADMIN_CREDENTIALS.username) {
            isAdminLoggedIn = true;
            await updateAdminUI();
            return;
        }
        
        // For rental provider accounts, check if they're still approved
        const accounts = await getAccounts();
        if (!Array.isArray(accounts)) {
            console.error('getAccounts() did not return an array:', accounts);
            return;
        }
        
        const account = accounts.find(acc => acc.username === currentUser);
        
        if (account) {
            // If account is blocked, deny access and log them out
            if (account.status === 'blocked') {
                alert('Your account has been blocked. You have been logged out.');
                isAdminLoggedIn = false;
                localStorage.removeItem('motorent_admin_logged_in');
                localStorage.removeItem('motorent_current_user');
                await updateAdminUI();
                return;
            }
            
            // If account is denied or pending, deny access and log them out
            if (account.status === 'denied' || account.status === 'pending') {
                isAdminLoggedIn = false;
                localStorage.removeItem('motorent_admin_logged_in');
                localStorage.removeItem('motorent_current_user');
                await updateAdminUI();
                return;
            }
            
            // Account is approved, allow access
            if (account.status === 'approved' || !account.status) {
                isAdminLoggedIn = true;
                await updateAdminUI();
                return;
            }
        } else {
            // Account not found in stored accounts, log them out
            isAdminLoggedIn = false;
            localStorage.removeItem('motorent_admin_logged_in');
            localStorage.removeItem('motorent_current_user');
            await updateAdminUI();
        }
    }
}

// Update admin UI based on login status
async function updateAdminUI() {
    if (isAdminLoggedIn) {
        await showAdminPage();
    } else {
        await showHomePage();
    }
}

// Show admin page
async function showAdminPage() {
    // Verify account is still valid before showing admin page
    const currentUser = localStorage.getItem('motorent_current_user');
    if (currentUser && currentUser !== ADMIN_CREDENTIALS.username) {
        const accounts = await getAccounts();
        const account = accounts.find(acc => acc.username === currentUser);
        
        if (account) {
            // If account is blocked, denied, or pending, don't show admin page
            if (account.status === 'blocked') {
                alert('Your account has been blocked. You have been logged out.');
                isAdminLoggedIn = false;
                localStorage.removeItem('motorent_admin_logged_in');
                localStorage.removeItem('motorent_current_user');
                await showHomePage();
                return;
            }
            
            if (account.status === 'denied' || account.status === 'pending') {
                isAdminLoggedIn = false;
                localStorage.removeItem('motorent_admin_logged_in');
                localStorage.removeItem('motorent_current_user');
                await showHomePage();
                return;
            }
        }
    }
    
    document.getElementById('adminPage').style.display = 'block';
    document.getElementById('mainContent').style.display = 'none';
    document.getElementById('superAdminPage').style.display = 'none';
    showAdminTab('bookings');
}

// Show homepage
async function showHomePage() {
    document.getElementById('adminPage').style.display = 'none';
    document.getElementById('superAdminPage').style.display = 'none';
    document.getElementById('mainContent').style.display = 'block';
    // Clear any provider filter
    await clearProviderFilter();
    // Show all providers and motorcycles from all accounts on homepage
    await displayProviders();
    const motorcycles = await getAllMotorcycles();
    displayBikes(motorcycles);
}

// Show admin tab
async function showAdminTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.admin-tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    if (tabName === 'bookings') {
        document.getElementById('bookingsTab').classList.add('active');
        const tabButtons = document.querySelectorAll('.tab-btn');
        if (tabButtons.length > 0) {
            tabButtons[0].classList.add('active');
        }
        await showBookingsSubTab('ongoing');
    } else if (tabName === 'motorcycles') {
        document.getElementById('motorcyclesTab').classList.add('active');
        const tabButtons = document.querySelectorAll('.tab-btn');
        if (tabButtons.length > 1) {
            tabButtons[1].classList.add('active');
        }
        await displayAdminMotorcycles();
    } else if (tabName === 'analytics') {
        document.getElementById('analyticsTab').classList.add('active');
        const tabButtons = document.querySelectorAll('.tab-btn');
        if (tabButtons.length > 2) {
            tabButtons[2].classList.add('active');
        }
        await displayAnalytics();
    } else if (tabName === 'profile') {
        document.getElementById('profileTab').classList.add('active');
        const tabButtons = document.querySelectorAll('.tab-btn');
        if (tabButtons.length > 3) {
            tabButtons[3].classList.add('active');
        }
        await displayProfile();
    }
}

// Display profile information
async function displayProfile() {
    const currentUser = getCurrentUser();
    if (!currentUser) {
        return;
    }
    
    // Display username
    document.getElementById('profileUsername').textContent = currentUser;
    
    // Get account creation date
    let createdDate = '-';
    if (currentUser === ADMIN_CREDENTIALS.username) {
        createdDate = 'Default Account';
    } else {
        const accounts = await getAccounts();
        if (Array.isArray(accounts)) {
            const account = accounts.find(acc => acc.username === currentUser);
            if (account) {
                const createdDateValue = account.createdAt || account.created_at;
                if (createdDateValue) {
                    const date = new Date(createdDateValue);
                    if (!isNaN(date.getTime())) {
                        createdDate = date.toLocaleDateString('en-US', {
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });
                    }
                }
            }
        }
    }
    document.getElementById('profileCreatedDate').textContent = createdDate;
    
    // Display motorcycle count
    const motorcycles = await getMotorcycles();
    if (Array.isArray(motorcycles)) {
        document.getElementById('profileMotorcyclesCount').textContent = motorcycles.length;
    } else {
        document.getElementById('profileMotorcyclesCount').textContent = '0';
    }
    
    // Display booking count
    const bookings = await getBookings();
    if (Array.isArray(bookings)) {
        document.getElementById('profileBookingsCount').textContent = bookings.length;
    } else {
        document.getElementById('profileBookingsCount').textContent = '0';
    }
    
    // Display profile photo
    const profile = await getProfile();
    if (profile && profile.photo) {
        document.getElementById('profilePhotoPreviewImg').src = profile.photo;
        document.getElementById('profilePhotoPreview').style.display = 'block';
        document.getElementById('profilePhotoPlaceholder').style.display = 'none';
    } else {
        document.getElementById('profilePhotoPreview').style.display = 'none';
        document.getElementById('profilePhotoPlaceholder').style.display = 'block';
    }
}

// Handle profile photo upload
async function handleProfilePhotoUpload(event) {
    const file = event.target.files[0];
    if (!file) {
        return;
    }
    
    if (!file.type.startsWith('image/')) {
        alert('Please select an image file.');
        return;
    }
    
    try {
        // Compress the image
        const compressedPhoto = await compressImage(file, 400, 400, 0.7);
        
        // Save to profile
        const profile = await getProfile() || {};
        profile.photo = compressedPhoto;
        await saveProfile(profile);
        
        // Update display
        document.getElementById('profilePhotoPreviewImg').src = compressedPhoto;
        document.getElementById('profilePhotoPreview').style.display = 'block';
        document.getElementById('profilePhotoPlaceholder').style.display = 'none';
        
        alert('Profile photo uploaded successfully!');
    } catch (error) {
        console.error('Error uploading profile photo:', error);
        alert('Error uploading profile photo. Please try again.');
    }
    
    // Reset file input
    event.target.value = '';
}

// Capture profile photo
function captureProfilePhoto() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        alert('Camera access is not available in your browser.');
        return;
    }
    
    navigator.mediaDevices.getUserMedia({ video: true })
        .then(function(stream) {
            // Create video element
            const video = document.createElement('video');
            video.srcObject = stream;
            video.play();
            
            // Create modal for camera preview
            const cameraModal = document.createElement('div');
            cameraModal.className = 'modal';
            cameraModal.style.zIndex = '6000';
            cameraModal.innerHTML = `
                <div class="modal-content" style="max-width: 500px;">
                    <span class="close" onclick="closeCameraModal()">&times;</span>
                    <h2>Capture Profile Photo</h2>
                    <div style="text-align: center; margin: 20px 0;">
                        <video id="cameraVideo" autoplay style="width: 100%; max-width: 400px; border-radius: 8px;"></video>
                    </div>
                    <div style="text-align: center;">
                        <button class="btn-primary" onclick="captureProfilePhotoSnapshot()">Capture</button>
                        <button class="btn-secondary" onclick="closeCameraModal()">Cancel</button>
                    </div>
                </div>
            `;
            document.body.appendChild(cameraModal);
            cameraModal.style.display = 'block';
            
            // Set video source
            setTimeout(() => {
                const videoElement = document.getElementById('cameraVideo');
                if (videoElement) {
                    videoElement.srcObject = stream;
                }
            }, 100);
            
            // Store stream for cleanup
            window.cameraStream = stream;
        })
        .catch(function(error) {
            console.error('Error accessing camera:', error);
            alert('Error accessing camera. Please make sure you grant camera permissions.');
        });
}

// Close camera modal
function closeCameraModal() {
    const cameraModal = document.querySelector('.modal:last-of-type');
    if (cameraModal) {
        if (window.cameraStream) {
            window.cameraStream.getTracks().forEach(track => track.stop());
            window.cameraStream = null;
        }
        cameraModal.remove();
    }
}

// Capture photo snapshot
async function captureProfilePhotoSnapshot() {
    const video = document.getElementById('cameraVideo');
    if (!video) {
        return;
    }
    
    try {
        // Create canvas and capture frame
        const canvas = document.createElement('canvas');
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(video, 0, 0);
        
        // Convert to blob
        canvas.toBlob(async function(blob) {
            if (!blob) {
                alert('Error capturing photo.');
                return;
            }
            
            try {
                // Compress the image
                const compressedPhoto = await compressImage(blob, 400, 400, 0.7);
                
                // Save to profile
                const profile = await getProfile() || {};
                profile.photo = compressedPhoto;
                await saveProfile(profile);
                
                // Update display
                document.getElementById('profilePhotoPreviewImg').src = compressedPhoto;
                document.getElementById('profilePhotoPreview').style.display = 'block';
                document.getElementById('profilePhotoPlaceholder').style.display = 'none';
                
                // Close camera modal
                closeCameraModal();
                
                alert('Profile photo captured successfully!');
            } catch (error) {
                console.error('Error processing captured photo:', error);
                alert('Error processing photo. Please try again.');
            }
        }, 'image/jpeg', 0.7);
    } catch (error) {
        console.error('Error capturing snapshot:', error);
        alert('Error capturing photo. Please try again.');
    }
}

// Remove profile photo
async function removeProfilePhoto() {
    if (!confirm('Are you sure you want to remove your profile photo?')) {
        return;
    }
    
    const profile = await getProfile() || {};
    profile.photo = null;
    await saveProfile(profile);
    
    // Update display
    document.getElementById('profilePhotoPreview').style.display = 'none';
    document.getElementById('profilePhotoPlaceholder').style.display = 'block';
    
    alert('Profile photo removed.');
}

// Admin logout
function adminLogout() {
    if (!confirm('Are you sure you want to logout?')) {
        return;
    }
    
    isAdminLoggedIn = false;
    localStorage.removeItem('motorent_admin_logged_in');
    updateAdminUI();
    alert('Logged out successfully!');
}

// Handle ticket submission
async function handleTicketSubmission(e) {
    e.preventDefault();
    
    const name = document.getElementById('ticketName').value.trim();
    const email = document.getElementById('ticketEmail').value.trim();
    const subject = document.getElementById('ticketSubject').value.trim();
    const message = document.getElementById('ticketMessage').value.trim();
    
    if (!name || !email || !subject || !message) {
        alert('Please fill in all fields.');
        return;
    }
    
    if (!isValidEmail(email)) {
        alert('Please enter a valid email address.');
        return;
    }
    
    try {
        await TicketsAPI.create({
            name: name,
            email: email,
            subject: subject,
            message: message
        });
        
        document.getElementById('ticketForm').reset();
        alert('Ticket submitted successfully! We will get back to you soon.');
        
        // Update notification if super admin is logged in
        if (isSuperAdminLoggedIn) {
            await updateTicketNotification();
        }
    } catch (error) {
        console.error('Error submitting ticket:', error);
        alert('Error submitting ticket. Please try again.');
    }
}

// Current active ticket tab
let currentTicketTab = 'unread';

// Open tickets modal
async function openTicketsModal() {
    const modal = document.getElementById('ticketsModal');
    if (modal) {
        modal.style.display = 'block';
        modal.style.zIndex = '6000';
        currentTicketTab = 'unread';
        await switchTicketTab('unread');
        await updateTicketTabCounts();
    }
}

// Switch ticket tab
async function switchTicketTab(tab) {
    currentTicketTab = tab;
    
    // Update tab buttons
    const unreadTab = document.getElementById('unreadTab');
    const readTab = document.getElementById('readTab');
    const deleteAllBtn = document.getElementById('deleteAllReadBtn');
    
    if (unreadTab && readTab) {
        if (tab === 'unread') {
            unreadTab.classList.add('active');
            readTab.classList.remove('active');
            if (deleteAllBtn) deleteAllBtn.style.display = 'none';
        } else {
            readTab.classList.add('active');
            unreadTab.classList.remove('active');
            // Show delete all button only if there are read tickets
            const tickets = await getTickets();
            if (Array.isArray(tickets)) {
                const readCount = tickets.filter(t => t.read).length;
                if (deleteAllBtn) {
                    deleteAllBtn.style.display = readCount > 0 ? 'block' : 'none';
                }
            }
        }
    }
    
    await displayTickets(tab);
}

// Update ticket tab counts
async function updateTicketTabCounts() {
    const tickets = await getTickets();
    if (!Array.isArray(tickets)) {
        console.error('getTickets() did not return an array:', tickets);
        return;
    }
    const unreadCount = tickets.filter(t => !t.read).length;
    const readCount = tickets.filter(t => t.read).length;
    
    const unreadCountSpan = document.getElementById('unreadCount');
    const readCountSpan = document.getElementById('readCount');
    
    if (unreadCountSpan) {
        unreadCountSpan.textContent = unreadCount;
    }
    if (readCountSpan) {
        readCountSpan.textContent = readCount;
    }
}

// Display tickets in modal
async function displayTickets(filter = 'unread') {
    const tickets = await getTickets();
    if (!Array.isArray(tickets)) {
        console.error('getTickets() did not return an array:', tickets);
        return;
    }
    const ticketsList = document.getElementById('ticketsList');
    if (!ticketsList) return;
    
    ticketsList.innerHTML = '';
    
    // Filter tickets based on tab - handle both 'read' and 'read_status' field names
    let filteredTickets = tickets;
    if (filter === 'unread') {
        filteredTickets = tickets.filter(t => !(t.read || t.read_status));
    } else if (filter === 'read') {
        filteredTickets = tickets.filter(t => t.read || t.read_status);
    }
    
    // Update delete all button visibility
    await updateDeleteAllButton();
    
    if (filteredTickets.length === 0) {
        const emptyMessage = filter === 'unread' 
            ? 'No unread tickets.' 
            : filter === 'read' 
            ? 'No read tickets yet.' 
            : 'No tickets submitted yet.';
        ticketsList.innerHTML = `<p style="text-align: center; padding: 2rem; color: #666;">${emptyMessage}</p>`;
        return;
    }
    
    // Sort tickets by date (newest first)
    const sortedTickets = [...filteredTickets].sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
    
    sortedTickets.forEach(ticket => {
        const ticketCard = document.createElement('div');
        ticketCard.className = `ticket-card ${ticket.read ? 'read' : 'unread'}`;
        ticketCard.style.cssText = 'background: white; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem; border-left: 4px solid ' + (ticket.read ? '#ddd' : '#667eea') + '; box-shadow: 0 2px 5px rgba(0,0,0,0.1);';
        
        const date = new Date(ticket.createdAt).toLocaleString();
        
        ticketCard.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                <div>
                    <h3 style="margin: 0 0 0.5rem 0; color: #333;">${ticket.subject}</h3>
                    <p style="margin: 0; color: #666; font-size: 0.9rem;">From: ${ticket.name} (${ticket.email})</p>
                    <p style="margin: 0.3rem 0 0 0; color: #999; font-size: 0.85rem;">${date}</p>
                </div>
                ${!ticket.read ? '<span style="background: #667eea; color: white; padding: 0.3rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: 600;">NEW</span>' : ''}
            </div>
            <div style="background: #f9f9f9; padding: 1rem; border-radius: 5px; margin-bottom: 1rem;">
                <p style="margin: 0; color: #333; line-height: 1.6;">${ticket.message}</p>
            </div>
            <div style="display: flex; gap: 0.5rem;">
                ${!ticket.read ? `<button class="btn-primary" onclick="markTicketAsRead(${ticket.id})" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Mark as Read</button>` : ''}
                <button class="btn-secondary" onclick="deleteTicket(${ticket.id})" style="padding: 0.5rem 1rem; font-size: 0.9rem;">Delete</button>
            </div>
        `;
        
        ticketsList.appendChild(ticketCard);
    });
}

// Mark ticket as read
async function markTicketAsRead(ticketId) {
    try {
        await TicketsAPI.markAsRead(ticketId);
        await displayTickets(currentTicketTab);
        await updateTicketNotification();
        await updateTicketTabCounts();
        await updateDeleteAllButton();
    } catch (error) {
        console.error('Error marking ticket as read:', error);
        alert('Error marking ticket as read. Please try again.');
    }
}

// Delete ticket
async function deleteTicket(ticketId) {
    if (!confirm('Are you sure you want to delete this ticket?')) {
        return;
    }
    
    try {
        await TicketsAPI.delete(ticketId);
        await displayTickets(currentTicketTab);
        await updateTicketNotification();
        await updateTicketTabCounts();
        await updateDeleteAllButton();
    } catch (error) {
        console.error('Error deleting ticket:', error);
        alert('Error deleting ticket. Please try again.');
    }
}

// Delete all read tickets
async function deleteAllReadTickets() {
    const tickets = await getTickets();
    if (!Array.isArray(tickets)) {
        console.error('getTickets() did not return an array:', tickets);
        return;
    }
    
    const readTickets = tickets.filter(t => t.read || t.read_status);
    
    if (readTickets.length === 0) {
        alert('No read tickets to delete.');
        return;
    }
    
    if (!confirm(`Are you sure you want to delete all ${readTickets.length} read ticket(s)? This action cannot be undone.`)) {
        return;
    }
    
    try {
        await TicketsAPI.deleteAllRead();
        await displayTickets(currentTicketTab);
        await updateTicketNotification();
        await updateTicketTabCounts();
        await updateDeleteAllButton();
    } catch (error) {
        console.error('Error deleting read tickets:', error);
        alert('Error deleting read tickets. Please try again.');
    }
}

// Update delete all button visibility
async function updateDeleteAllButton() {
    const deleteAllBtn = document.getElementById('deleteAllReadBtn');
    if (deleteAllBtn && currentTicketTab === 'read') {
        const tickets = await getTickets();
        if (!Array.isArray(tickets)) {
            console.error('getTickets() did not return an array:', tickets);
            deleteAllBtn.style.display = 'none';
            return;
        }
        const readCount = tickets.filter(t => t.read || t.read_status).length;
        deleteAllBtn.style.display = readCount > 0 ? 'block' : 'none';
    }
}

// Update ticket notification badge
async function updateTicketNotification() {
    const tickets = await getTickets();
    if (!Array.isArray(tickets)) {
        console.error('getTickets() did not return an array:', tickets);
        return;
    }
    const unreadCount = tickets.filter(t => !(t.read || t.read_status)).length;
    const badge = document.getElementById('ticketNotificationBadge');
    
    if (badge) {
        if (unreadCount > 0) {
            badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
    
    // Also update tab counts if modal is open
    await updateTicketTabCounts();
}

// Super admin logout
function superAdminLogout() {
    if (!confirm('Are you sure you want to logout as Super Admin?')) {
        return;
    }
    
    isSuperAdminLoggedIn = false;
    localStorage.removeItem('motorent_super_admin_logged_in');
    updateSuperAdminUI();
    // Hide super admin page and show main content
    document.getElementById('superAdminPage').style.display = 'none';
    document.getElementById('mainContent').style.display = 'block';
    alert('Super Admin logged out successfully!');
}

// Show bookings sub-tab (Ongoing or Returned)
async function showBookingsSubTab(tabType) {
    // Hide all booking lists
    document.getElementById('ongoingBookingsList').style.display = 'none';
    document.getElementById('returnedBookingsList').style.display = 'none';
    
    // Remove active class from all sub-tabs
    document.querySelectorAll('.sub-tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Clear search when switching tabs
    const searchInput = document.getElementById('bookingsSearchInput');
    if (searchInput) {
        searchInput.value = '';
        currentBookingSearchTerm = '';
    }
    
    // Show selected tab
    if (tabType === 'ongoing') {
        document.getElementById('ongoingBookingsList').style.display = 'block';
        document.querySelectorAll('.sub-tab-btn')[0].classList.add('active');
        await displayBookings('ongoing');
    } else if (tabType === 'returned') {
        document.getElementById('returnedBookingsList').style.display = 'block';
        document.querySelectorAll('.sub-tab-btn')[1].classList.add('active');
        await displayBookings('returned');
    }
}

// Current booking search term
let currentBookingSearchTerm = '';

// Filter bookings by search term
async function filterBookings() {
    const searchInput = document.getElementById('bookingsSearchInput');
    if (searchInput) {
        currentBookingSearchTerm = searchInput.value.trim().toLowerCase();
        // Get current active sub-tab
        const activeSubTab = document.querySelector('.sub-tab-btn.active');
        if (activeSubTab) {
            const tabType = activeSubTab.textContent.toLowerCase();
            await displayBookings(tabType);
        } else {
            await displayBookings('ongoing');
        }
    }
}

// Display bookings (filtered by status and search)
async function displayBookings(filterType = 'ongoing') {
    const bookings = await getBookings();
    if (!Array.isArray(bookings)) {
        console.error('getBookings() did not return an array:', bookings);
        return;
    }
    
    const bookingsCount = document.getElementById('bookingsCount');
    
    // Filter bookings based on return status
    let filteredBookings = [];
    if (filterType === 'ongoing') {
        filteredBookings = bookings.filter(booking => !booking.returned || booking.returned === false);
    } else if (filterType === 'returned') {
        filteredBookings = bookings.filter(booking => booking.returned === true);
    }
    
    // Apply search filter if there's a search term
    if (currentBookingSearchTerm) {
        filteredBookings = filteredBookings.filter(booking => {
            const name = (booking.customer?.name || '').toLowerCase();
            const email = (booking.customer?.email || '').toLowerCase();
            const bike = (booking.bike || '').toLowerCase();
            return name.includes(currentBookingSearchTerm) || 
                   email.includes(currentBookingSearchTerm) || 
                   bike.includes(currentBookingSearchTerm);
        });
    }
    
    bookingsCount.textContent = `Total Bookings: ${bookings.length} (${filterType === 'ongoing' ? filteredBookings.length : filteredBookings.length} ${filterType})`;
    
    // Get the appropriate list container
    const bookingsList = filterType === 'ongoing' 
        ? document.getElementById('ongoingBookingsList')
        : document.getElementById('returnedBookingsList');
    
    if (filteredBookings.length === 0) {
        const noResultsMessage = currentBookingSearchTerm 
            ? `No ${filterType} bookings found matching "${currentBookingSearchTerm}".`
            : `No ${filterType} bookings found.`;
        bookingsList.innerHTML = `<p class="no-bookings">${noResultsMessage}</p>`;
        return;
    }
    
    bookingsList.innerHTML = '';
    
    filteredBookings.forEach(booking => {
        const bookingItem = document.createElement('div');
        bookingItem.className = 'booking-item';
        
        // Format booking date
        const bookingDate = booking.bookingDate 
            ? new Date(booking.bookingDate).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            })
            : 'N/A';
        
        // Check booking status
        const isReturned = booking.returned === true;
        const isStarted = booking.started === true;
        
        let statusText = '';
        if (isReturned) {
            statusText = '<span style="color: #4caf50; font-weight: bold;">✓ Returned</span>';
        } else if (isStarted) {
            statusText = '<span style="color: #2196F3; font-weight: bold;">In Progress</span>';
        } else {
            statusText = '<span style="color: #ff9800; font-weight: bold;">Not Started</span>';
        }
        
        // Determine which button to show
        let actionButton = '';
        if (!isReturned) {
            if (!isStarted) {
                // Show "Start" button if not started
                actionButton = `<button class="btn-primary btn-start-booking" data-booking-id="${booking.id}" style="padding: 8px 15px; font-size: 0.9rem; background: #2196F3; position: relative; z-index: 10; cursor: pointer; pointer-events: auto;">Start</button>`;
            } else {
                // Show "Confirm Return" button if started but not returned
                actionButton = `<button class="btn-primary btn-confirm-return" data-booking-id="${booking.id}" style="padding: 8px 15px; font-size: 0.9rem; background: #4caf50; position: relative; z-index: 10; cursor: pointer; pointer-events: auto;">Confirm Return</button>`;
            }
        }
        
        bookingItem.innerHTML = `
            <div class="booking-item-content">
                <div>
                    <h3 class="customer-name" onclick="showBookingDetails(${booking.id})">${booking.customer.name}</h3>
                    <p class="booking-date">Booked on: ${bookingDate}</p>
                    <p><strong>Status:</strong> ${statusText}</p>
                </div>
                <div style="display: flex; gap: 0.5rem; align-items: center; position: relative; z-index: 10;">
                    ${actionButton}
                </div>
            </div>
        `;
        bookingsList.appendChild(bookingItem);
        
        // Add event listeners to buttons after they're added to DOM
        if (!isReturned) {
            if (!isStarted) {
                const startBtn = bookingItem.querySelector('.btn-start-booking');
                if (startBtn) {
                    startBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        startBooking(booking.id);
                    });
                }
            } else {
                const confirmBtn = bookingItem.querySelector('.btn-confirm-return');
                if (confirmBtn) {
                    confirmBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        confirmReturn(booking.id);
                    });
                }
            }
        }
    });
}

// Display analytics
async function displayAnalytics() {
    const bookings = await getBookings();
    if (!Array.isArray(bookings)) {
        console.error('getBookings() did not return an array:', bookings);
        return;
    }
    
    const analyticsContent = document.getElementById('analyticsContent');
    if (!analyticsContent) return;
    
    // Get current date info
    const now = new Date();
    const currentYear = now.getFullYear();
    const currentMonth = now.getMonth(); // 0-11
    const currentDay = now.getDate();
    
    // Calculate start of week (Sunday)
    const startOfWeek = new Date(now);
    startOfWeek.setDate(currentDay - now.getDay());
    startOfWeek.setHours(0, 0, 0, 0);
    
    // Calculate start of month
    const startOfMonth = new Date(currentYear, currentMonth, 1);
    startOfMonth.setHours(0, 0, 0, 0);
    
    // Filter bookings for this week
    const thisWeekBookings = bookings.filter(booking => {
        const bookingDate = new Date(booking.bookingDate);
        return bookingDate >= startOfWeek && bookingDate <= now;
    });
    
    // Filter bookings for this month
    const thisMonthBookings = bookings.filter(booking => {
        const bookingDate = new Date(booking.bookingDate);
        return bookingDate >= startOfMonth && bookingDate <= now;
    });
    
    // Calculate earnings
    const thisWeekEarnings = thisWeekBookings.reduce((sum, booking) => sum + (booking.totalPrice || 0), 0);
    const thisMonthEarnings = thisMonthBookings.reduce((sum, booking) => sum + (booking.totalPrice || 0), 0);
    
    // Calculate monthly breakdown
    const monthlyData = [];
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                       'July', 'August', 'September', 'October', 'November', 'December'];
    
    for (let month = 0; month < 12; month++) {
        const monthStart = new Date(currentYear, month, 1);
        const monthEnd = new Date(currentYear, month + 1, 0, 23, 59, 59);
        
        const monthBookings = bookings.filter(booking => {
            const bookingDate = new Date(booking.bookingDate);
            return bookingDate >= monthStart && bookingDate <= monthEnd;
        });
        
        const monthEarnings = monthBookings.reduce((sum, booking) => sum + (booking.totalPrice || 0), 0);
        
        monthlyData.push({
            name: monthNames[month],
            bookings: monthBookings.length,
            earnings: monthEarnings
        });
    }
    
    // Build HTML
    let html = `
        <div class="analytics-summary">
            <div class="analytics-card">
                <h3>This Week</h3>
                <div class="analytics-stat">
                    <p class="stat-label">Bookings</p>
                    <p class="stat-value">${thisWeekBookings.length}</p>
                </div>
                <div class="analytics-stat">
                    <p class="stat-label">Earnings</p>
                    <p class="stat-value">₱${thisWeekEarnings.toFixed(2)}</p>
                </div>
            </div>
            <div class="analytics-card">
                <h3>This Month</h3>
                <div class="analytics-stat">
                    <p class="stat-label">Bookings</p>
                    <p class="stat-value">${thisMonthBookings.length}</p>
                </div>
                <div class="analytics-stat">
                    <p class="stat-label">Earnings</p>
                    <p class="stat-value">₱${thisMonthEarnings.toFixed(2)}</p>
                </div>
            </div>
        </div>
        <div class="analytics-monthly">
            <h3>Monthly Breakdown (${currentYear})</h3>
            <div class="monthly-table">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Bookings</th>
                            <th>Earnings</th>
                        </tr>
                    </thead>
                    <tbody>
    `;
    
    monthlyData.forEach(month => {
        html += `
            <tr>
                <td>${month.name}</td>
                <td>${month.bookings}</td>
                <td>₱${month.earnings.toFixed(2)}</td>
            </tr>
        `;
    });
    
    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    analyticsContent.innerHTML = html;
}

// Confirm return of motorcycle
// Start booking (mark as started)
async function startBooking(bookingId) {
    if (!confirm('Start this booking? The rental period will begin now.')) {
        return;
    }
    
    const currentUser = getCurrentUser();
    if (!currentUser) {
        alert('Error: You must be logged in to start bookings.');
        return;
    }
    
    const bookings = getBookings();
    const bookingIndex = bookings.findIndex(b => b.id === bookingId);
    
    if (bookingIndex === -1) {
        alert('Booking not found!');
        return;
    }
    
    const booking = bookings[bookingIndex];
    
    // Check if already started
    if (booking.started === true) {
        alert('This booking has already been started.');
        return;
    }
    
    // Mark booking as started
    bookings[bookingIndex].started = true;
    bookings[bookingIndex].startedDate = new Date().toISOString();
    
    // Save updated bookings
    const bookingsKey = getBookingsKey(currentUser);
    localStorage.setItem(bookingsKey, JSON.stringify(bookings));
    
    // Refresh display
    const activeSubTab = document.querySelector('.sub-tab-btn.active');
    if (activeSubTab) {
        const tabType = activeSubTab.textContent.toLowerCase();
        displayBookings(tabType);
    } else {
        displayBookings('ongoing');
    }
    
    alert('Booking started successfully!');
}

async function confirmReturn(bookingId) {
    const currentUser = getCurrentUser();
    if (!currentUser) {
        alert('Error: You must be logged in to confirm returns.');
        return;
    }
    
    const bookings = getBookings();
    const bookingIndex = bookings.findIndex(b => b.id === bookingId);
    
    if (bookingIndex === -1) {
        alert('Booking not found!');
        return;
    }
    
    const booking = bookings[bookingIndex];
    
    // Check if booking has been started
    if (booking.started !== true) {
        alert('Please start the booking first before confirming return.');
        return;
    }
    
    // Check if already returned
    if (booking.returned === true) {
        alert('This booking has already been returned.');
        return;
    }
    
    if (!confirm('Confirm that the customer has returned the motorcycle?')) {
        return;
    }
    
    // Mark booking as returned
    bookings[bookingIndex].returned = true;
    bookings[bookingIndex].returnedDate = new Date().toISOString();
    
    // Find which account owns this motorcycle
    const bikeName = booking.bike;
    const owner = findMotorcycleOwnerByName(bikeName);
    
    if (owner) {
        // Update motorcycle availability for the owner
        const ownerMotorcycles = getAccountMotorcycles(owner);
        const motorcycle = ownerMotorcycles.find(b => b.name === bikeName);
        
        if (motorcycle) {
            motorcycle.available = (motorcycle.available || 0) + 1;
            saveAccountMotorcycles(owner, ownerMotorcycles);
        }
    }
    
    // Save updated bookings to current account
    const bookingsKey = getBookingsKey(currentUser);
    localStorage.setItem(bookingsKey, JSON.stringify(bookings));
    
    // Update homepage if visible
    const mainContent = document.getElementById('mainContent');
    if (mainContent && mainContent.style.display !== 'none') {
        displayProviders();
        const updatedMotorcycles = getAllMotorcycles();
        displayBikes(updatedMotorcycles);
    }
    
    // Refresh admin display
    await displayBookings();
    await displayAdminMotorcycles();
    
    // Refresh bookings list - show the current active tab
    const activeSubTab = document.querySelector('.sub-tab-btn.active');
    if (activeSubTab) {
        const tabType = activeSubTab.textContent.toLowerCase();
        await showBookingsSubTab(tabType);
    } else {
        await displayBookings('ongoing');
    }
    
    alert('Return confirmed! Motorcycle availability has been updated.');
}

// Show booking details popup
function showBookingDetails(bookingId) {
    // Convert to number if it's a string
    const id = typeof bookingId === 'string' ? parseInt(bookingId) : bookingId;
    
    const bookings = getBookings();
    const booking = bookings.find(b => b.id === id || b.id === bookingId);
    
    if (!booking) {
        alert('Booking not found!');
        return;
    }
    
    const modal = document.getElementById('bookingDetailsModal');
    if (!modal) {
        alert('Error: Modal not found. Please refresh the page.');
        return;
    }
    
    const content = document.getElementById('bookingDetailsContent');
    if (!content) {
        alert('Error: Content area not found. Please refresh the page.');
        return;
    }
    
    // Handle both old and new booking formats
    const pickupDateTime = booking.pickupDateTime || booking.pickupDate;
    const returnDateTime = booking.returnDateTime || booking.returnDate;
    
    // Format dates for display
    const pickupDate = new Date(pickupDateTime).toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const returnDate = new Date(returnDateTime).toLocaleString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const bookingDate = booking.bookingDate 
        ? new Date(booking.bookingDate).toLocaleString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        })
        : 'N/A';
    
    // Calculate duration
    const hours = booking.hours || (booking.days ? booking.days * 24 : 0);
    const days = booking.days || Math.floor(hours / 24);
    const durationText = days > 0 
        ? `${days} day(s) ${(hours % 24).toFixed(1)} hours`
        : `${hours.toFixed(1)} hours`;
    
    // Check if return time has passed and calculate late fee
    const returnTime = new Date(returnDateTime);
    const now = new Date();
    const isLate = now > returnTime;
    const lateHours = isLate ? Math.ceil((now - returnTime) / (1000 * 60 * 60)) : 0;
    const lateFee = lateHours > 0 ? lateHours * (booking.lateFeePerHour || 100) : 0;
    
    let idPhotoSection = '';
    if (booking.customer.idPhoto) {
        idPhotoSection = `
            <div class="booking-detail-section">
                <h3>ID Photo</h3>
                <img src="${booking.customer.idPhoto}" alt="ID Photo" style="max-width: 100%; border-radius: 5px; margin-top: 0.5rem;">
            </div>
        `;
    }
    
    content.innerHTML = `
        <div class="booking-detail-section">
            <h3>Customer Information</h3>
            <p><strong>Name:</strong> ${booking.customer.name}</p>
            <p><strong>Email:</strong> ${booking.customer.email}</p>
            ${booking.customer.address ? `<p><strong>Address:</strong> ${booking.customer.address}</p>` : ''}
            ${booking.customer.phone ? `<p><strong>Phone:</strong> ${booking.customer.phone}</p>` : ''}
        </div>
        ${idPhotoSection}
        <div class="booking-detail-section">
            <h3>Motorcycle Details</h3>
            <p><strong>Motorcycle:</strong> ${booking.bike}</p>
        </div>
        <div class="booking-detail-section">
            <h3>Rental Period</h3>
            <p><strong>Pickup Date & Time:</strong> ${pickupDate}</p>
            <p><strong>Return Date & Time:</strong> ${returnDate}</p>
            <p><strong>Duration:</strong> ${durationText}</p>
            ${isLate ? `<p style="color: #ff6b6b; font-weight: bold;">⚠️ LATE RETURN: ${lateHours} hour(s) overdue</p>` : ''}
        </div>
        <div class="booking-detail-section">
            <h3>Booking Information</h3>
            <p><strong>Booking Date:</strong> ${bookingDate}</p>
            <p><strong>Total Price:</strong> ₱${booking.totalPrice.toFixed(2)}</p>
            ${lateFee > 0 ? `<p style="color: #ff6b6b; font-weight: bold;"><strong>Late Fee:</strong> ₱${lateFee.toFixed(2)} (₱${booking.lateFeePerHour || 100}/hour)</p>` : ''}
            ${lateFee > 0 ? `<p style="color: #ff6b6b;"><strong>Total with Late Fee:</strong> ₱${(booking.totalPrice + lateFee).toFixed(2)}</p>` : ''}
            ${!isLate ? `<p style="color: #4caf50;">✓ On time</p>` : ''}
        </div>
    `;
    
    // Ensure modal is visible and on top
    modal.style.display = 'block';
    modal.style.zIndex = '5000';
    
    console.log('Booking details modal displayed for booking:', bookingId);
}

// Display motorcycles in admin panel
async function displayAdminMotorcycles() {
    const motorcycles = await getMotorcycles();
    if (!Array.isArray(motorcycles)) {
        console.error('getMotorcycles() did not return an array:', motorcycles);
        return;
    }
    
    const motorcyclesList = document.getElementById('motorcyclesList');
    if (!motorcyclesList) return;
    
    if (motorcycles.length === 0) {
        motorcyclesList.innerHTML = '<p class="no-bookings">No motorcycles added yet.</p>';
        return;
    }
    
    motorcyclesList.innerHTML = '';
    
    motorcycles.forEach(bike => {
        const bikeItem = document.createElement('div');
        bikeItem.className = 'booking-item';
        const availableText = bike.available > 0 
            ? `<span style="color: #4caf50; font-weight: bold;">Available (${bike.available} unit(s))</span>`
            : `<span style="color: #ff6b6b; font-weight: bold;">Not Available</span>`;
        
        bikeItem.innerHTML = `
            <div class="booking-item-content">
                <div>
                    <h3>${bike.name}</h3>
                    <p><strong>Status:</strong> ${availableText}</p>
                </div>
                <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                    <button class="btn-primary" onclick="editMotorcycle(${bike.id})" style="padding: 8px 15px; font-size: 0.9rem;">Edit</button>
                    <button class="btn-secondary" onclick="deleteMotorcycle(${bike.id})">Delete</button>
                </div>
            </div>
        `;
        motorcyclesList.appendChild(bikeItem);
    });
}

// Open add motorcycle modal
function openAddMotorcycleModal() {
    const modal = document.getElementById('addMotorcycleModal');
    if (!modal) {
        alert('Error: Modal not found. Please refresh the page.');
        return;
    }
    
    document.getElementById('motorcycleModalTitle').textContent = 'Add New Motorcycle';
    document.getElementById('motorcycleSubmitBtn').textContent = 'Add Motorcycle';
    document.getElementById('editBikeId').value = '';
    document.getElementById('addMotorcycleForm').reset();
    document.getElementById('bikePhotoPreview').style.display = 'none';
    document.getElementById('bikePhotoPreviewImg').src = '';
    modal.style.display = 'block';
    modal.style.zIndex = '5000';
}

// Edit motorcycle
async function editMotorcycle(bikeId) {
    const modal = document.getElementById('addMotorcycleModal');
    if (!modal) {
        alert('Error: Modal not found. Please refresh the page.');
        return;
    }
    
    const motorcycles = await getMotorcycles();
    if (!Array.isArray(motorcycles)) {
        console.error('getMotorcycles() did not return an array:', motorcycles);
        alert('Error loading motorcycles. Please try again.');
        return;
    }
    
    const bike = motorcycles.find(b => b.id === bikeId);
    
    if (!bike) {
        alert('Motorcycle not found!');
        return;
    }
    
    // Fill form with bike data
    document.getElementById('motorcycleModalTitle').textContent = 'Edit Motorcycle';
    document.getElementById('motorcycleSubmitBtn').textContent = 'Update Motorcycle';
    document.getElementById('editBikeId').value = bike.id;
    document.getElementById('bikeName').value = bike.name || '';
    document.getElementById('bikeTransmission').value = bike.transmission || bike.category || '';
    document.getElementById('bikeAvailability').value = bike.available || 0;
    
    // Show existing photo if available
    if (bike.image) {
        document.getElementById('bikePhotoPreviewImg').src = bike.image;
        document.getElementById('bikePhotoPreview').style.display = 'block';
    } else {
        document.getElementById('bikePhotoPreview').style.display = 'none';
    }
    
    modal.style.display = 'block';
    modal.style.zIndex = '5000';
}


// Delete motorcycle
async function deleteMotorcycle(bikeId) {
    if (confirm('Are you sure you want to delete this motorcycle?')) {
        try {
            await MotorcyclesAPI.delete(bikeId);
            await displayAdminMotorcycles();
        } catch (error) {
            console.error('Error deleting motorcycle:', error);
            alert('Error deleting motorcycle. Please try again.');
        }
    }
}

// Handle add/edit motorcycle form
async function handleAddMotorcycle(e) {
    e.preventDefault();
    
    // Get form values
    const name = document.getElementById('bikeName').value.trim();
    const transmission = document.getElementById('bikeTransmission').value;
    const available = parseInt(document.getElementById('bikeAvailability').value) || 0;
    
    // Validate required fields
    if (!name) {
        alert('Please fill in the motorcycle name.');
        return;
    }
    
    if (!transmission) {
        alert('Please select transmission type (Automatic or Manual).');
        return;
    }
    
    const motorcycles = await getMotorcycles();
    if (!Array.isArray(motorcycles)) {
        console.error('getMotorcycles() did not return an array:', motorcycles);
        alert('Error loading motorcycles. Please try again.');
        return;
    }
    
    const editId = document.getElementById('editBikeId').value;
    
    // Get bike photo if uploaded
    let bikeImage = null;
    const bikePhotoFile = document.getElementById('bikePhoto').files[0];
    if (bikePhotoFile) {
        try {
            // compressImage returns a base64 string directly
            bikeImage = await compressImage(bikePhotoFile, 800, 800, 0.7);
            console.log('Photo compressed successfully, length:', bikeImage ? bikeImage.length : 0);
        } catch (error) {
            console.error('Error processing bike photo:', error);
            alert('Error processing photo. Motorcycle will be saved without photo.');
        }
    }
    
    if (editId) {
        // Edit existing motorcycle
        const bikeIndex = motorcycles.findIndex(b => b.id === parseInt(editId));
        if (bikeIndex !== -1) {
            const updatedBike = {
                ...motorcycles[bikeIndex],
                name: name,
                transmission: transmission,
                category: transmission, // Category matches transmission
                available: available
            };
            
            // Only update image if a new one was uploaded
            if (bikeImage) {
                updatedBike.image = bikeImage;
            }
            
            // Update motorcycle via API
            try {
                await MotorcyclesAPI.update(updatedBike.id, {
                    name: updatedBike.name,
                    transmission: updatedBike.transmission,
                    category: updatedBike.category,
                    available: updatedBike.available,
                    image: updatedBike.image
                });
            } catch (error) {
                console.error('Error updating motorcycle:', error);
                alert('Error updating motorcycle. Please try again.');
                return;
            }
            
            document.getElementById('addMotorcycleForm').reset();
            document.getElementById('editBikeId').value = '';
            document.getElementById('bikePhotoPreview').style.display = 'none';
            document.getElementById('bikePhotoPreviewImg').src = '';
            document.getElementById('addMotorcycleModal').style.display = 'none';
            await displayAdminMotorcycles();
            // Always update homepage to reflect photo changes
            const mainContent = document.getElementById('mainContent');
            if (mainContent) {
                await displayProviders();
                const updatedMotorcycles = await getAllMotorcycles();
                displayBikes(updatedMotorcycles);
            }
            console.log('Motorcycle updated with image:', updatedBike.image ? 'Yes' : 'No');
            alert('Motorcycle updated successfully!');
        } else {
            alert('Error: Motorcycle not found for editing.');
        }
    } else {
        // Add new motorcycle via API
        const currentUser = getCurrentUser();
        if (!currentUser) {
            alert('Error: You must be logged in to add a motorcycle.');
            return;
        }
        
        try {
            await MotorcyclesAPI.create({
                owner: currentUser,
                name: name,
                category: transmission,
                transmission: transmission,
                engine: '',
                power: '',
                topSpeed: '',
                description: '',
                image: bikeImage || null,
                available: available
            });
        } catch (error) {
            console.error('Error creating motorcycle:', error);
            alert('Error creating motorcycle. Please try again.');
            return;
        }
        
        document.getElementById('addMotorcycleForm').reset();
        document.getElementById('editBikeId').value = '';
        document.getElementById('bikePhotoPreview').style.display = 'none';
        document.getElementById('bikePhotoPreviewImg').src = '';
        document.getElementById('addMotorcycleModal').style.display = 'none';
        await displayAdminMotorcycles();
        // Always update homepage to reflect new motorcycle
        const mainContent = document.getElementById('mainContent');
        if (mainContent) {
            await displayProviders();
            const updatedMotorcycles = await getAllMotorcycles();
            displayBikes(updatedMotorcycles);
        }
        console.log('Motorcycle added with image:', bikeImage ? 'Yes' : 'No');
        alert('Motorcycle added successfully!');
    }
}

// Check super admin session
function checkSuperAdminSession() {
    const loggedIn = localStorage.getItem('motorent_super_admin_logged_in');
    if (loggedIn === 'true') {
        isSuperAdminLoggedIn = true;
        updateSuperAdminUI();
        // Show super admin page and hide main content
        document.getElementById('mainContent').style.display = 'none';
        document.getElementById('adminPage').style.display = 'none';
        document.getElementById('superAdminPage').style.display = 'block';
        displaySuperAdminDashboard();
        setupSuperAdminSearch();
        updateTicketNotification();
    }
}

// Update super admin UI
function updateSuperAdminUI() {
    const adminBtn = document.getElementById('adminDashboardBtn');
    if (adminBtn) {
        adminBtn.style.display = isSuperAdminLoggedIn ? 'flex' : 'none';
    }
}

// Open admin dashboard
async function openAdminDashboard() {
    if (!isSuperAdminLoggedIn) {
        alert('Please login as super admin first (admin/admin123)');
        return;
    }
    
    const modal = document.getElementById('adminDashboardModal');
    if (modal) {
        modal.style.display = 'block';
        modal.style.zIndex = '6000';
        await displayAdminDashboard();
    }
}

// Display admin dashboard
async function displayAdminDashboard() {
    const accounts = await getAccounts();
    if (!Array.isArray(accounts)) {
        console.error('getAccounts() did not return an array:', accounts);
        return;
    }
    
    // Update stats
    const total = accounts.length;
    const pending = accounts.filter(acc => acc.status === 'pending').length;
    const approved = accounts.filter(acc => acc.status === 'approved' || !acc.status).length;
    const blocked = accounts.filter(acc => acc.status === 'blocked').length;
    
    document.getElementById('totalAccountsCount').textContent = total;
    document.getElementById('pendingAccountsCount').textContent = pending;
    document.getElementById('approvedAccountsCount').textContent = approved;
    document.getElementById('blockedAccountsCount').textContent = blocked;
    
    // Display accounts list
    const accountsList = document.getElementById('accountsList');
    if (!accountsList) return;
    
    accountsList.innerHTML = '';
    
    if (accounts.length === 0) {
        accountsList.innerHTML = '<p style="text-align: center; padding: 2rem; color: #666;">No accounts found.</p>';
        return;
    }
    
    for (const account of accounts) {
        const accountCard = await createAccountCard(account);
        accountsList.appendChild(accountCard);
    }
}

// Display super admin dashboard
async function displaySuperAdminDashboard() {
    const accounts = await getAccounts();
    const searchInput = document.getElementById('superAdminSearchInput');
    const searchTerm = searchInput ? searchInput.value.trim().toLowerCase() : '';
    
    // Filter accounts based on search term
    let filteredAccounts = accounts;
    if (searchTerm) {
        filteredAccounts = accounts.filter(account => {
            const username = (account.username || '').toLowerCase();
            const email = (account.email || '').toLowerCase();
            const location = (account.location || '').toLowerCase();
            return username.includes(searchTerm) || email.includes(searchTerm) || location.includes(searchTerm);
        });
    }
    
    // Update stats (based on all accounts, not filtered)
    const total = accounts.length;
    const pending = accounts.filter(acc => acc.status === 'pending').length;
    const approved = accounts.filter(acc => acc.status === 'approved' || !acc.status).length;
    const blocked = accounts.filter(acc => acc.status === 'blocked').length;
    
    const totalEl = document.getElementById('superAdminTotalAccountsCount');
    const pendingEl = document.getElementById('superAdminPendingAccountsCount');
    const approvedEl = document.getElementById('superAdminApprovedAccountsCount');
    const blockedEl = document.getElementById('superAdminBlockedAccountsCount');
    
    if (totalEl) totalEl.textContent = total;
    if (pendingEl) pendingEl.textContent = pending;
    if (approvedEl) approvedEl.textContent = approved;
    if (blockedEl) blockedEl.textContent = blocked;
    
    // Display accounts list (filtered)
    const accountsList = document.getElementById('superAdminAccountsList');
    if (!accountsList) return;
    
    accountsList.innerHTML = '';
    
    if (filteredAccounts.length === 0) {
        if (searchTerm) {
            accountsList.innerHTML = '<p style="text-align: center; padding: 2rem; color: #666;">No accounts found matching your search.</p>';
        } else {
            accountsList.innerHTML = '<p style="text-align: center; padding: 2rem; color: #666;">No accounts found.</p>';
        }
        return;
    }
    
    for (const account of filteredAccounts) {
        const accountCard = await createAccountCard(account);
        accountsList.appendChild(accountCard);
    }
}

// Setup super admin search filter
function setupSuperAdminSearch() {
    const searchInput = document.getElementById('superAdminSearchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            displaySuperAdminDashboard();
        });
    }
}

// Create account card for admin dashboard
async function createAccountCard(account) {
    const card = document.createElement('div');
    card.className = `account-card ${account.status || 'pending'}`;
    
    const status = account.status || 'pending';
    const statusColors = {
        pending: '#ffc107',
        approved: '#4CAF50',
        denied: '#f44336',
        blocked: '#9e9e9e'
    };
    
    // Get motorcycle count for this account
    const accountMotorcycles = await getAccountMotorcycles(account.username);
    const motorcycleCount = Array.isArray(accountMotorcycles) ? accountMotorcycles.length : 0;
    
    // Handle date - check both createdAt and created_at
    const createdDate = account.createdAt || account.created_at;
    let formattedDate = 'Not available';
    if (createdDate) {
        try {
            const date = new Date(createdDate);
            if (!isNaN(date.getTime())) {
                formattedDate = date.toLocaleDateString();
            }
        } catch (e) {
            console.error('Error formatting date:', e);
        }
    }
    
    // Handle image display - check if it's base64 and add data URI prefix if needed
    const licenseImage = account.license || account.license_front;
    const validIdImage = account.valid_id || account.validId || account.license_back;
    
    const formatImageSrc = (img) => {
        if (!img || typeof img !== 'string') return null;
        
        // Clean whitespace
        const cleaned = img.trim();
        
        // If it's already a data URI, return as is
        if (cleaned.startsWith('data:image/')) {
            return cleaned;
        }
        
        // If it's a URL, return as is
        if (cleaned.startsWith('http://') || cleaned.startsWith('https://')) {
            return cleaned;
        }
        
        // If it looks like base64 (long string without spaces/special chars), add data URI prefix
        if (cleaned.length > 50 && /^[A-Za-z0-9+/=]+$/.test(cleaned)) {
            // Remove any existing prefix if present
            const base64Data = cleaned.replace(/^data:image\/[^;]+;base64,/, '');
            return `data:image/jpeg;base64,${base64Data}`;
        }
        
        return cleaned;
    };
    
    const licenseSrc = formatImageSrc(licenseImage);
    const validIdSrc = formatImageSrc(validIdImage);
    
    card.innerHTML = `
        <div class="account-header">
            <h3>${account.username}</h3>
            <span class="status-badge ${status}" style="background: ${statusColors[status] || '#ffc107'}; color: white; padding: 0.4rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: 600; text-transform: uppercase;">
                ${status}
            </span>
        </div>
        <div class="account-info">
            <p><strong>Email:</strong> ${account.email || 'Not provided'}</p>
            <p><strong>Location:</strong> ${account.location || 'Not provided'}</p>
            <p><strong>Motorcycles:</strong> <span style="color: #667eea; font-weight: 600;">${motorcycleCount}</span> ${motorcycleCount === 1 ? 'unit' : 'units'}</p>
            <p><strong>Created:</strong> ${formattedDate}</p>
        </div>
        <div class="account-ids">
            <div class="id-preview-section">
                <h4>Driver's License (Front)</h4>
                ${licenseSrc ? `<img src="${licenseSrc}" alt="Driver's License Front" class="id-preview-img" onclick="viewFullImage('${licenseSrc}')" onerror="this.parentElement.innerHTML='<p style=\\'color: #f44336;\\'>Failed to load image</p>'">` : '<p>Not uploaded</p>'}
            </div>
            <div class="id-preview-section">
                <h4>Driver's License (Back)</h4>
                ${validIdSrc ? `<img src="${validIdSrc}" alt="Driver's License Back" class="id-preview-img" onclick="viewFullImage('${validIdSrc}')" onerror="this.parentElement.innerHTML='<p style=\\'color: #f44336;\\'>Failed to load image</p>'">` : '<p>Not uploaded</p>'}
            </div>
        </div>
        <div class="account-actions">
            ${status === 'pending' ? `
                <button class="btn-approve" onclick="approveAccount('${account.username}')">Approve</button>
                <button class="btn-deny" onclick="denyAccount('${account.username}')">Deny</button>
            ` : ''}
            ${status === 'approved' || status === null || status === undefined ? `
                <button class="btn-block" onclick="blockAccount('${account.username}')">Block</button>
            ` : ''}
            ${status === 'blocked' ? `
                <button class="btn-unblock" onclick="unblockAccount('${account.username}')">Unblock</button>
            ` : ''}
            ${status === 'denied' ? `
                <button class="btn-approve" onclick="approveAccount('${account.username}')">Approve</button>
            ` : ''}
            <button class="btn-delete" onclick="deleteAccount('${account.username}')" style="margin-left: auto;">Delete</button>
        </div>
    `;
    
    return card;
}

// View full image
function viewFullImage(imageSrc) {
    const modal = document.createElement('div');
    modal.className = 'modal';
    modal.style.zIndex = '7000';
    modal.style.display = 'flex';
    modal.style.alignItems = 'center';
    modal.style.justifyContent = 'center';
    modal.onclick = () => modal.remove();
    modal.innerHTML = `
        <div style="max-width: 90%; max-height: 90vh; background: white; padding: 1rem; border-radius: 8px;">
            <img src="${imageSrc}" alt="ID" style="max-width: 100%; max-height: 80vh; border-radius: 4px;">
            <p style="text-align: center; margin-top: 1rem; color: #666;">Click outside to close</p>
        </div>
    `;
    document.body.appendChild(modal);
}

// Approve account
// Approve account
async function approveAccount(username) {
    if (!confirm(`Approve account "${username}"?`)) return;
    
    try {
        // Get account details first
        const accounts = await getAccounts();
        const account = accounts.find(acc => acc.username === username);
        
        if (account) {
            // Update account status via API (this will trigger email notification)
            await AccountsAPI.update(account.id, { status: 'approved' });
            
            // Clear cache to refresh data
            accountsCache = null;
            
            // Refresh dashboards
            await displayAdminDashboard();
            if (isSuperAdminLoggedIn && document.getElementById('superAdminPage').style.display !== 'none') {
                await displaySuperAdminDashboard();
            }
            
            alert('Account approved successfully! An email notification has been sent to the applicant.');
        } else {
            alert('Account not found!');
        }
    } catch (error) {
        console.error('Error approving account:', error);
        alert('Error approving account. Please try again.');
    }
}

// Deny account
async function denyAccount(username) {
    if (!confirm(`Deny account "${username}"?`)) return;
    
    try {
        // Get account details first
        const accounts = await getAccounts();
        const account = accounts.find(acc => acc.username === username);
        
        if (account) {
            // Update account status via API (this will trigger email notification)
            await AccountsAPI.update(account.id, { status: 'denied' });
            
            // Clear cache to refresh data
            accountsCache = null;
            
            // Refresh dashboards
            await displayAdminDashboard();
            if (isSuperAdminLoggedIn && document.getElementById('superAdminPage').style.display !== 'none') {
                await displaySuperAdminDashboard();
            }
            
            alert('Account denied. An email notification has been sent to the applicant.');
        } else {
            alert('Account not found!');
        }
    } catch (error) {
        console.error('Error denying account:', error);
        alert('Error denying account. Please try again.');
    }
}

// Block account
async function blockAccount(username) {
    if (!confirm(`Block account "${username}"?`)) return;
    
    try {
        const accounts = await getAccounts();
        const account = accounts.find(acc => acc.username === username);
        if (account) {
            await AccountsAPI.update(account.id, { status: 'blocked' });
            accountsCache = null;
            await displayAdminDashboard();
            if (isSuperAdminLoggedIn && document.getElementById('superAdminPage').style.display !== 'none') {
                await displaySuperAdminDashboard();
            }
            alert('Account blocked successfully!');
        }
    } catch (error) {
        console.error('Error blocking account:', error);
        alert('Error blocking account. Please try again.');
    }
}

// Unblock account
async function unblockAccount(username) {
    if (!confirm(`Unblock account "${username}"?`)) return;
    
    try {
        const accounts = await getAccounts();
        const account = accounts.find(acc => acc.username === username);
        if (account) {
            await AccountsAPI.update(account.id, { status: 'approved' });
            accountsCache = null;
            await displayAdminDashboard();
            if (isSuperAdminLoggedIn && document.getElementById('superAdminPage').style.display !== 'none') {
                await displaySuperAdminDashboard();
            }
            alert('Account unblocked successfully!');
        }
    } catch (error) {
        console.error('Error unblocking account:', error);
        alert('Error unblocking account. Please try again.');
    }
}

// Delete account
async function deleteAccount(username) {
    if (!confirm(`Are you sure you want to permanently delete account "${username}"?\n\nThis will delete:\n- The account\n- All associated motorcycles\n- All bookings\n- Profile data\n\nThis action cannot be undone!`)) {
        return;
    }
    
    // Check if user is currently logged in as this account
    const currentUser = localStorage.getItem('motorent_current_user');
    const isCurrentUser = currentUser === username;
    
    try {
        // Get all motorcycles owned by this account to delete them
        const motorcycles = await MotorcyclesAPI.getByOwner(username);
        if (Array.isArray(motorcycles)) {
            // Delete each motorcycle
            for (const bike of motorcycles) {
                try {
                    await MotorcyclesAPI.delete(bike.id);
                } catch (error) {
                    console.error(`Error deleting motorcycle ${bike.id}:`, error);
                }
            }
        }
        
        // Delete the account (this should cascade delete bookings and profile in the database)
        await AccountsAPI.delete(username);
        
        // Clear cache
        accountsCache = null;
        
        // If this is the currently logged in user, log them out
        if (isCurrentUser) {
            isAdminLoggedIn = false;
            localStorage.removeItem('motorent_admin_logged_in');
            localStorage.removeItem('motorent_current_user');
            await updateAdminUI();
            alert('Your account has been deleted. You have been logged out.');
        }
        
        // Refresh dashboards
        if (isSuperAdminLoggedIn && document.getElementById('superAdminPage').style.display !== 'none') {
            await displaySuperAdminDashboard();
        }
        
        alert('Account deleted successfully!');
    } catch (error) {
        console.error('Error deleting account:', error);
        alert('Error deleting account. Please try again.');
    }
}

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

