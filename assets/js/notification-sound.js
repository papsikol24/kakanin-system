// ===== GLOBAL NOTIFICATION SOUND SYSTEM =====
// This runs on EVERY page - both customer and staff
// Sound state is saved per user type in localStorage

(function() {
    'use strict';

    // Configuration
    const SOUND_URL = '/assets/sounds/notification.mp3';
    const CHECK_INTERVAL = 5000; // Check every 5 seconds
    const CUSTOMER_STORAGE_KEY = 'lastCustomerNotificationCount';
    const STAFF_STORAGE_KEY = 'lastStaffOrderCount';
    const CUSTOMER_SOUND_KEY = 'customerSoundEnabled';
    const STAFF_SOUND_KEY = 'staffSoundEnabled';

    // Create audio element once
    let notificationSound = null;
    let isSoundEnabled = true; // Will be updated based on user type

    function initNotificationSound() {
        if (!notificationSound) {
            notificationSound = new Audio(SOUND_URL);
            notificationSound.preload = 'auto';
            notificationSound.volume = 0.7;
            
            // Preload the sound
            notificationSound.load();
        }
    }

    function playNotificationSound() {
        // Check if sound is enabled for this user type
        if (!isSoundEnabled) {
            console.log('🔇 Sound is muted - not playing');
            return;
        }
        
        initNotificationSound();
        
        if (notificationSound) {
            // Reset to beginning
            notificationSound.currentTime = 0;
            
            // Play the sound
            notificationSound.play().catch(function(error) {
                console.log('Sound play failed:', error);
            });
        }
    }

    // Get last count from localStorage
    function getLastCount(key) {
        try {
            const stored = localStorage.getItem(key);
            return stored ? parseInt(stored) : 0;
        } catch (e) {
            return 0;
        }
    }

    // Save current count to localStorage
    function saveLastCount(key, count) {
        try {
            localStorage.setItem(key, count);
        } catch (e) {
            console.log('localStorage error:', e);
        }
    }

    // Get sound preference for user type
    function getSoundPreference(userType) {
        try {
            const key = userType === 'staff' ? STAFF_SOUND_KEY : CUSTOMER_SOUND_KEY;
            const saved = localStorage.getItem(key);
            // Default to true if not set
            return saved !== 'false';
        } catch (e) {
            return true;
        }
    }

    // Check for new notifications (for customers)
    function checkCustomerNotifications() {
        fetch('/api/get_realtime_data.php')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.unreadCount !== undefined) {
                    const lastCount = getLastCount(CUSTOMER_STORAGE_KEY);
                    
                    // Only play sound if there are NEW notifications AND sound is enabled
                    if (data.unreadCount > lastCount && isSoundEnabled) {
                        console.log('🎵 New notification! Playing sound...');
                        playNotificationSound();
                    }
                    
                    saveLastCount(CUSTOMER_STORAGE_KEY, data.unreadCount);
                }
            })
            .catch(function(error) {
                console.log('Error checking customer notifications:', error);
            });
    }

    // Check for new orders (for staff)
    function checkStaffNotifications() {
        fetch('/api/get_realtime_data.php')
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.pendingCount !== undefined) {
                    const lastCount = getLastCount(STAFF_STORAGE_KEY);
                    
                    // Only play sound if there are NEW pending orders AND sound is enabled
                    if (data.pendingCount > lastCount && isSoundEnabled) {
                        console.log('🎵 New order! Playing sound...');
                        playNotificationSound();
                    }
                    
                    saveLastCount(STAFF_STORAGE_KEY, data.pendingCount);
                }
            })
            .catch(function(error) {
                console.log('Error checking staff notifications:', error);
            });
    }

    // Determine if current page is staff or customer
    function detectUserType() {
        const path = window.location.pathname;
        
        // Staff pages
        if (path.includes('/staff-dashboard') || 
            path.includes('/inventory') || 
            path.includes('/orders') || 
            path.includes('/customers') || 
            path.includes('/reports') || 
            path.includes('/tools') || 
            path.includes('/users') ||
            path === '/staff-dashboard' ||
            path === '/staff-login') {
            return 'staff';
        }
        
        // Customer pages
        if (path.includes('/dashboard') || 
            path.includes('/menu') || 
            path.includes('/cart') || 
            path.includes('/checkout') || 
            path.includes('/order-success') || 
            path.includes('/about') ||
            path === '/dashboard' ||
            path === '/login') {
            return 'customer';
        }
        
        // Check for login pages
        if (path === '/login' || path === '/staff-login') {
            return 'login';
        }
        
        return 'unknown';
    }

    // Start checking based on user type
    function startNotificationChecking() {
        // Don't run on login pages
        const path = window.location.pathname;
        if (path.includes('login') || path === '/login' || path === '/staff-login') {
            console.log('Notification sound: Skipping login page');
            return;
        }
        
        const userType = detectUserType();
        console.log('Notification sound: Detected user type:', userType);
        
        // Set sound preference based on user type
        if (userType === 'staff') {
            isSoundEnabled = getSoundPreference('staff');
            console.log('Staff sound enabled:', isSoundEnabled);
            checkStaffNotifications();
            setInterval(checkStaffNotifications, CHECK_INTERVAL);
        } else if (userType === 'customer') {
            isSoundEnabled = getSoundPreference('customer');
            console.log('Customer sound enabled:', isSoundEnabled);
            checkCustomerNotifications();
            setInterval(checkCustomerNotifications, CHECK_INTERVAL);
        } else {
            console.log('Notification sound: Unknown user type, skipping');
        }
    }

    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(startNotificationChecking, 1500);
        });
    } else {
        setTimeout(startNotificationChecking, 1500);
    }

    // Initialize audio on first user interaction
    function enableAudioOnInteraction() {
        initNotificationSound();
        
        // Try to play and immediately pause to unlock audio
        if (notificationSound) {
            notificationSound.volume = 0.01;
            notificationSound.play().then(function() {
                notificationSound.pause();
                notificationSound.currentTime = 0;
                notificationSound.volume = 0.7;
                console.log('Audio unlocked by user interaction');
            }).catch(function(error) {
                console.log('Audio unlock failed:', error);
            });
        }
        
        // Remove listeners after first interaction
        document.removeEventListener('click', enableAudioOnInteraction);
        document.removeEventListener('touchstart', enableAudioOnInteraction);
        document.removeEventListener('keydown', enableAudioOnInteraction);
    }

    // Enable audio on first user interaction (required by browsers)
    document.addEventListener('click', enableAudioOnInteraction, { once: true });
    document.addEventListener('touchstart', enableAudioOnInteraction, { once: true });
    document.addEventListener('keydown', enableAudioOnInteraction, { once: true });

    console.log('✅ Global notification sound system loaded');
})();