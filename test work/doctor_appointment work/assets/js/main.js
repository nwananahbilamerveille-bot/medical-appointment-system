/**
 * MedAppoint - Main JavaScript File
 * Contains common functions and utilities
 */

// Common Functions
const MedAppoint = {
    // Show alert message
    showAlert: function(message, type = 'info') {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Add to top of page
        document.body.insertBefore(alertDiv, document.body.firstChild);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    },

    // Format date
    formatDate: function(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    },

    // Format time
    formatTime: function(timeString) {
        const [hours, minutes] = timeString.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const formattedHour = hour % 12 || 12;
        return `${formattedHour}:${minutes} ${ampm}`;
    },

    // Confirm action
    confirmAction: function(message, callback) {
        if (confirm(message)) {
            callback();
        }
    },

    // Validate email
    validateEmail: function(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    },

    // Validate phone
    validatePhone: function(phone) {
        const re = /^[\+]?[1-9][\d]{0,15}$/;
        return re.test(phone.replace(/[\s\-\(\)]/g, ''));
    },

    // Loader functions
    showLoader: function() {
        const loader = document.createElement('div');
        loader.id = 'medappoint-loader';
        loader.innerHTML = `
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        `;
        loader.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        `;
        document.body.appendChild(loader);
    },

    hideLoader: function() {
        const loader = document.getElementById('medappoint-loader');
        if (loader) {
            loader.remove();
        }
    },

    // Make API request
    apiRequest: async function(url, method = 'GET', data = null) {
        this.showLoader();
        
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            }
        };
        
        if (data) {
            options.body = JSON.stringify(data);
        }
        
        try {
            const response = await fetch(url, options);
            const result = await response.json();
            this.hideLoader();
            return result;
        } catch (error) {
            this.hideLoader();
            this.showAlert('Network error. Please try again.', 'danger');
            console.error('API Error:', error);
            return { success: false, message: 'Network error' };
        }
    },

    // Check session
    checkSession: function() {
        // Check if user is logged in
        if (!localStorage.getItem('user_id')) {
            window.location.href = '../auth/login.php';
        }
    },

    // Logout
    logout: function() {
        localStorage.clear();
        sessionStorage.clear();
        window.location.href = '../auth/logout.php';
    },

    // Initialize
    init: function() {
        // Add global event listeners
        document.addEventListener('DOMContentLoaded', () => {
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 5000);
            });

            // Add loading to all forms
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', () => {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Processing...';
                    }
                });
            });

            // Check for session warnings
            const lastActivity = localStorage.getItem('last_activity');
            const now = Date.now();
            if (lastActivity && (now - lastActivity) > 30 * 60 * 1000) {
                this.showAlert('Your session will expire soon. Please save your work.', 'warning');
            }
            localStorage.setItem('last_activity', now);
        });
    }
};

// Appointment specific functions
const AppointmentManager = {
    cancelAppointment: function(appointmentId) {
        MedAppoint.confirmAction('Are you sure you want to cancel this appointment?', () => {
            MedAppoint.apiRequest('../api/cancel-appointment.php', 'POST', { id: appointmentId })
                .then(result => {
                    if (result.success) {
                        MedAppoint.showAlert('Appointment cancelled successfully!', 'success');
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        MedAppoint.showAlert(result.message, 'danger');
                    }
                });
        });
    },

    updateStatus: function(appointmentId, status) {
        MedAppoint.apiRequest('../api/update-appointment.php', 'POST', { 
            id: appointmentId, 
            status: status 
        })
        .then(result => {
            if (result.success) {
                MedAppoint.showAlert(`Appointment marked as ${status}`, 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                MedAppoint.showAlert(result.message, 'danger');
            }
        });
    },

    bookAppointment: function(doctorId, date, time, symptoms) {
        return MedAppoint.apiRequest('../api/book-appointment.php', 'POST', {
            doctor_id: doctorId,
            appointment_date: date,
            appointment_time: time,
            symptoms: symptoms
        });
    }
};

// Doctor specific functions
const DoctorManager = {
    setAvailability: function(day, startTime, endTime, slotDuration) {
        return MedAppoint.apiRequest('../api/set-availability.php', 'POST', {
            day: day,
            start_time: startTime,
            end_time: endTime,
            slot_duration: slotDuration
        });
    },

    getAvailableSlots: function(doctorId, date) {
        return MedAppoint.apiRequest(`../api/get-available-slots.php?doctor_id=${doctorId}&date=${date}`);
    }
};

// Admin specific functions
const AdminManager = {
    verifyDoctor: function(doctorId) {
        return MedAppoint.apiRequest(`../api/verify-doctor.php`, 'POST', { id: doctorId });
    },

    deleteUser: function(userId) {
        return MedAppoint.apiRequest(`../api/delete-user.php`, 'POST', { id: userId });
    },

    getStatistics: function() {
        return MedAppoint.apiRequest('../api/get-statistics.php');
    }
};

// Initialize when DOM is loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => MedAppoint.init());
} else {
    MedAppoint.init();
}

// Make functions globally available
window.MedAppoint = MedAppoint;
window.AppointmentManager = AppointmentManager;
window.DoctorManager = DoctorManager;
window.AdminManager = AdminManager;

// Helper function for form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;

    const inputs = form.querySelectorAll('[required]');
    let isValid = true;

    inputs.forEach(input => {
        if (!input.value.trim()) {
            input.classList.add('is-invalid');
            isValid = false;
        } else {
            input.classList.remove('is-invalid');
        }

        // Email validation
        if (input.type === 'email' && input.value.trim()) {
            if (!MedAppoint.validateEmail(input.value)) {
                input.classList.add('is-invalid');
                isValid = false;
            }
        }

        // Phone validation
        if (input.type === 'tel' && input.value.trim()) {
            if (!MedAppoint.validatePhone(input.value)) {
                input.classList.add('is-invalid');
                isValid = false;
            }
        }
    });

    return isValid;
}

// Auto logout after 1 hour of inactivity
let inactivityTimer;
function resetInactivityTimer() {
    clearTimeout(inactivityTimer);
    inactivityTimer = setTimeout(() => {
        MedAppoint.showAlert('You have been logged out due to inactivity.', 'warning');
        setTimeout(() => MedAppoint.logout(), 2000);
    }, 60 * 60 * 1000); // 1 hour
}

// Reset timer on user activity
['click', 'mousemove', 'keypress', 'scroll'].forEach(event => {
    document.addEventListener(event, resetInactivityTimer);
});

// Start timer
resetInactivityTimer();