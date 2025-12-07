// API Helper Functions - Replace localStorage calls with API calls
// Adjust this path based on your project structure
const API_BASE_URL = window.location.pathname.includes('htdocs') 
    ? 'api/' 
    : (window.location.pathname.split('/').slice(0, -1).join('/') + '/api/').replace('//', '/');

// Helper function for API calls
async function apiCall(endpoint, method = 'GET', data = null) {
    try {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            }
        };
        
        if (data && (method === 'POST' || method === 'PUT' || method === 'DELETE')) {
            options.body = JSON.stringify(data);
        }
        
        const response = await fetch(API_BASE_URL + endpoint, options);
        
        // Check if response is OK
        if (!response.ok) {
            const errorText = await response.text();
            let errorData;
            try {
                errorData = JSON.parse(errorText);
            } catch (e) {
                errorData = { error: errorText || `HTTP ${response.status}: ${response.statusText}` };
            }
            throw new Error(errorData.error || `HTTP ${response.status}: ${response.statusText}`);
        }
        
        const result = await response.json();
        
        // Check if API returned an error
        if (result && !result.success && result.error) {
            throw new Error(result.error);
        }
        
        return result;
    } catch (error) {
        console.error('API Error:', {
            endpoint: endpoint,
            method: method,
            error: error,
            message: error?.message
        });
        
        // Re-throw with a more descriptive message if needed
        if (error instanceof TypeError && error.message.includes('fetch')) {
            throw new Error('Network error: Could not connect to server. Please check if the server is running.');
        }
        
        throw error;
    }
}

// Accounts API
const AccountsAPI = {
    async getAll(status = null) {
        const endpoint = status ? `accounts.php?status=${status}` : 'accounts.php';
        const result = await apiCall(endpoint);
        return result.data || [];
    },
    
    async getByUsername(username) {
        const result = await apiCall(`accounts.php?username=${username}`);
        return result.data || null;
    },
    
    async create(accountData) {
        return await apiCall('accounts.php', 'POST', accountData);
    },
    
    async update(id, updateData) {
        return await apiCall('accounts.php', 'PUT', { id, ...updateData });
    },
    
    async delete(username) {
        return await apiCall('accounts.php', 'DELETE', { username });
    }
};

// Auth API
const AuthAPI = {
    async login(username, password) {
        return await apiCall('auth.php', 'POST', {
            action: 'login',
            username,
            password
        });
    },
    
    async checkUsername(username) {
        const result = await apiCall('auth.php', 'POST', {
            action: 'check_username',
            username
        });
        return result.exists || false;
    },
    
    async checkEmail(email) {
        const result = await apiCall('auth.php', 'POST', {
            action: 'check_email',
            email
        });
        return result.exists || false;
    }
};

// Motorcycles API
const MotorcyclesAPI = {
    async getAll(approvedOnly = false) {
        const endpoint = approvedOnly ? 'motorcycles.php?approved_only=1' : 'motorcycles.php';
        const result = await apiCall(endpoint);
        return result.data || [];
    },
    
    async getByOwner(username) {
        const result = await apiCall(`motorcycles.php?username=${username}`);
        return result.data || [];
    },
    
    async create(motorcycleData) {
        return await apiCall('motorcycles.php', 'POST', motorcycleData);
    },
    
    async update(id, motorcycleData) {
        return await apiCall('motorcycles.php', 'PUT', { id, ...motorcycleData });
    },
    
    async delete(id) {
        return await apiCall('motorcycles.php', 'DELETE', { id });
    }
};

// Bookings API
const BookingsAPI = {
    async getAll(username = null, returned = null) {
        let endpoint = 'bookings.php';
        if (username) {
            endpoint += `?username=${username}`;
            if (returned !== null) {
                endpoint += `&returned=${returned ? 1 : 0}`;
            }
        }
        const result = await apiCall(endpoint);
        return result.data || [];
    },
    
    async create(bookingData) {
        return await apiCall('bookings.php', 'POST', bookingData);
    },
    
    async start(bookingId) {
        return await apiCall('bookings.php', 'PUT', {
            id: bookingId,
            action: 'start'
        });
    },
    
    async confirmReturn(bookingId) {
        return await apiCall('bookings.php', 'PUT', {
            id: bookingId,
            action: 'confirm_return'
        });
    }
};

// Tickets API
const TicketsAPI = {
    async getAll(readStatus = null) {
        const endpoint = readStatus !== null ? `tickets.php?read=${readStatus ? 1 : 0}` : 'tickets.php';
        const result = await apiCall(endpoint);
        return result.data || [];
    },
    
    async create(ticketData) {
        return await apiCall('tickets.php', 'POST', ticketData);
    },
    
    async markAsRead(ticketId) {
        return await apiCall('tickets.php', 'PUT', {
            id: ticketId,
            read: 1
        });
    },
    
    async delete(ticketId) {
        return await apiCall('tickets.php', 'DELETE', { id: ticketId });
    },
    
    async deleteAllRead() {
        return await apiCall('tickets.php', 'DELETE', { delete_all_read: true });
    }
};

// Profiles API
const ProfilesAPI = {
    async get(username) {
        const result = await apiCall(`profiles.php?username=${username}`);
        return result.data || null;
    },
    
    async save(profileData) {
        return await apiCall('profiles.php', 'POST', profileData);
    }
};

