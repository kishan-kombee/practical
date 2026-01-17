/**
 * SSE Export Streaming System (phpMyAdmin-style)
 * Handles real-time export streaming using Server-Sent Events
 * Supports multiple concurrent exports, reconnection, and cross-tab communication
 * 
 * phpMyAdmin-style Architecture:
 * - fileContent stored in memory only (NOT in localStorage) to avoid quota issues
 * - Supports server-side file storage with downloadUrl for large exports (1M+ records)
 * - Only metadata (progress, status) stored in localStorage
 * - Works reliably for exports up to 1,000,000+ records
 * 
 * Key Features:
 * - Real-time progress updates via SSE
 * - Automatic reconnection on page refresh
 * - Cross-tab communication via BroadcastChannel
 * - Queue system for multiple exports
 * - Download current partial export feature
 */

/**
 * Client-side logger for SSE exports
 * Logs to browser console with structured format for debugging
 */
const SSEExportClientLogger = {
    log: function(level, message, data = {}) {
        const timestamp = new Date().toISOString();
        const logData = {
            timestamp,
            level: level.toUpperCase(),
            message,
            ...data
        };
        
        const emoji = {
            'info': 'ℹ️',
            'success': '✅',
            'warning': '⚠️',
            'error': '❌',
            'debug': '🔍'
        };
        
        // console.log(`${emoji[level] || '📝'} [SSE Export] ${message}`, logData);
    },
    
    info: function(message, data = {}) { this.log('info', message, data); },
    success: function(message, data = {}) { this.log('success', message, data); },
    warning: function(message, data = {}) { this.log('warning', message, data); },
    error: function(message, data = {}) { this.log('error', message, data); },
    debug: function(message, data = {}) { this.log('debug', message, data); }
};

/**
 * Formats export type name for display (e.g., "user" -> "Users", "role" -> "Roles")
 * Capitalizes first letter and adds 's' for plural, with support for special cases
 * 
 * @param {string} exportType - The export type to format (e.g., "user", "role")
 * @returns {string} Formatted export type name (e.g., "Users", "Roles")
 * 
 * @example
 * formatExportTypeName('user') // Returns "Users"
 * formatExportTypeName('role') // Returns "Roles"
 */
function formatExportTypeName(exportType) {
    if (!exportType) return 'Data';
    
    // Capitalize first letter and add 's' for plural
    const formatted = exportType.charAt(0).toUpperCase() + exportType.slice(1);
    
    // Handle special cases
    const specialCases = {
    };
    
    return specialCases[exportType.toLowerCase()] || formatted + 's';
}

// LocalStorage keys - defined early so they can be used throughout the file
const STORAGE_KEY_PREFIX = 'export_active_';
const STORAGE_EXPORT_LIST = 'export_active_list';
const STORAGE_EXPORT_QUEUE = 'export_queue_list';
const STORAGE_QUEUE_TYPE = 'export_queue_type';

// Queue limit - maximum number of exports allowed in queue
const QUEUE_MAX_LIMIT = 5;

// Export queue enabled - set to true to enable queue mechanism for exports
// When enabled, exports are queued when one is in progress
const EXPORT_USE_QUEUE = true;

// Maximum record limit for export - exports exceeding this limit will be blocked
// Change this value to adjust the maximum allowed record count for exports
const EXPORT_MAX_RECORD_LIMIT = 1000000; // 10 Lakh (1,000,000)

// Immediately hide queue badge container if queue is disabled (runs after Alpine/Livewire are ready)
(function() {
    function hideQueueBadgeIfDisabled() {
        if (!EXPORT_USE_QUEUE) {
            const queueBadgeContainer = document.getElementById('queueBadgeContainer');
            if (queueBadgeContainer) {
                queueBadgeContainer.style.display = 'none';
            }
        }
    }
    
    // Wait for Alpine.js to be initialized first to avoid interfering with Alpine initialization
    function initAfterAlpine() {
        if (window.Alpine && window.Alpine.version) {
            // Alpine is ready, safe to manipulate DOM
            hideQueueBadgeIfDisabled();
        } else {
            // Wait for Alpine to initialize
            if (document.readyState === 'loading') {
                document.addEventListener('alpine:init', () => {
                    setTimeout(hideQueueBadgeIfDisabled, 100);
                });
            } else {
                // Check if Alpine exists but might not be fully initialized
                setTimeout(() => {
                    if (window.Alpine) {
                        hideQueueBadgeIfDisabled();
                    }
                }, 500);
            }
        }
    }
    
    // Try after DOM is ready and Alpine is initialized
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        setTimeout(initAfterAlpine, 100);
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(initAfterAlpine, 100);
        });
    }
    
    // Also try on window load as fallback
    window.addEventListener('load', () => {
        setTimeout(hideQueueBadgeIfDisabled, 200);
    });
    
    // Also wait for Livewire initialization
    document.addEventListener('livewire:initialized', () => {
        setTimeout(hideQueueBadgeIfDisabled, 100);
    });
})();

/**
 * Retrieves the array of unique export types currently in the queue from localStorage
 * This is used to track which types of exports are waiting to be processed
 * 
 * @returns {string[]} Array of unique exportTypes in queue (e.g., ["user", "role"])
 * 
 * @example
 * const types = getQueueTypes(); // Returns ["user", "role"] if both are queued
 */
function getQueueTypes() {
    try {
        const typesJson = localStorage.getItem(STORAGE_QUEUE_TYPE);
        if (typesJson) {
            const types = JSON.parse(typesJson);
            return Array.isArray(types) ? types : [];
        }
        return [];
    } catch (e) {
        // console.error('Error getting queue types from localStorage:', e);
        return [];
    }
}

/**
 * Stores the array of unique export types in the queue to localStorage
 * Automatically removes duplicates and updates the UI queue count display
 * 
 * @param {string[]} types - Array of exportTypes to store (e.g., ["user", "role"])
 * 
 * @example
 * setQueueTypes(["user", "role"]); // Stores and updates UI
 */
function setQueueTypes(types) {
    try {
        // Ensure it's an array and remove duplicates
        const uniqueTypes = Array.isArray(types) ? [...new Set(types)] : [];
        localStorage.setItem(STORAGE_QUEUE_TYPE, JSON.stringify(uniqueTypes));
        // Update UI element with count
        const count = uniqueTypes.length;
        updateQueueCountDisplay(count);
    } catch (e) {
        // console.error('Error setting queue types in localStorage:', e);
    }
}

/**
 * Adds an export type to the queue types list (only if it doesn't already exist)
 * Used to track which types of exports are in the queue without duplicates
 * 
 * @param {string} exportType - The exportType to add (e.g., "user", "role")
 * @returns {boolean} True if added successfully, false if already exists
 * 
 * @example
 * addQueueType('user'); // Returns true if added, false if already exists
 */
function addQueueType(exportType) {
    if (!exportType) return false;
    
    const types = getQueueTypes();
    // Check if exportType already exists
    if (types.includes(exportType)) {
        return false;
    }
    
    // Add the new exportType
    types.push(exportType);
    setQueueTypes(types);
    return true;
}

/**
 * Removes an export type from the queue types list
 * Called when an export completes or is cancelled to clean up the queue tracking
 * 
 * @param {string} exportType - The exportType to remove (e.g., "user", "role")
 * 
 * @example
 * removeQueueType('user'); // Removes "user" from queue types
 */
function removeQueueType(exportType) {
    if (!exportType) return;
    
    const types = getQueueTypes();
    const filteredTypes = types.filter(type => type !== exportType);
    setQueueTypes(filteredTypes);
}

/**
 * Checks if a specific export type exists in the queue types list
 * Used to prevent duplicate exports of the same type
 * 
 * @param {string} exportType - The exportType to check (e.g., "user", "role")
 * @returns {boolean} True if the exportType exists in queue, false otherwise
 * 
 * @example
 * hasQueueType('user'); // Returns true if "user" is in queue
 */
function hasQueueType(exportType) {
    if (!exportType) return false;
    const types = getQueueTypes();
    return types.includes(exportType);
}

/**
 * Gets the current count of unique export types in the queue
 * This represents how many different types of exports are waiting (not total items)
 * 
 * @returns {number} The current queue count (number of unique exportTypes)
 * 
 * @example
 * const count = getQueueCount(); // Returns 2 if "user" and "role" are queued
 */
function getQueueCount() {
    try {
        // Prefer actual queue contents to avoid stale queueTypes
        const queueJson = localStorage.getItem(STORAGE_EXPORT_QUEUE);
        if (queueJson) {
            const queue = JSON.parse(queueJson);
            if (Array.isArray(queue) && queue.length > 0) {
                const uniqueTypes = new Set();
                queue.forEach(item => {
                    if (item && typeof item === 'object' && item.exportType) {
                        uniqueTypes.add(String(item.exportType));
                    }
                });
                return uniqueTypes.size;
            }
        }
    } catch (e) {
        // Fall back to queueTypes if parsing fails
    }
    return getQueueTypes().length;
}

/**
 * Updates the queue list display in the dropdown menu
 * Reads queue data from localStorage and displays all queued export types
 * Groups items by exportType and shows a simple list of export type names
 * Falls back to showing queueTypes if queue array is empty but types exist
 * 
 * @example
 * updateQueueListDisplay(); // Updates the dropdown with current queue items
 */
function updateQueueListDisplay() {
    try {
        const queueList = document.getElementById('queueList');
        const queueDropdownCount = document.getElementById('queueDropdownCount');
        
        if (!queueList) {
            return;
        }
        
        // Get the actual queue array (source of truth) - read directly from localStorage
        let queue = [];
        let queueTypes = [];
        
        try {
            // Read directly from localStorage instead of calling getQueue (which might not be in scope)
            const queueJson = localStorage.getItem(STORAGE_EXPORT_QUEUE);
            if (queueJson) {
                queue = JSON.parse(queueJson);
                if (!Array.isArray(queue)) {
                    queue = [];
                }
            }
        } catch (e) {
            queue = [];
        }
        
        try {
            queueTypes = getQueueTypes();
        } catch (e) {
            queueTypes = [];
        }
        
        // Update count in dropdown header - use unique types count (matches badge)
        if (queueDropdownCount) {
            queueDropdownCount.textContent = queueTypes.length.toString();
        }
        
        // Clear existing content
        queueList.innerHTML = '';
        
        // Check if queue is empty
        if (!Array.isArray(queue) || queue.length === 0) {
            // Show empty state
            queueList.innerHTML = '<div class="p-3 text-center"><p class="text-xs text-gray-500 dark:text-gray-400">No exports in queue</p></div>';
            return;
        }
        
        // Group queue items by exportType for better display
        const groupedQueue = {};
        let validItemsCount = 0;
        
        queue.forEach((item, idx) => {
            try {
                if (item && typeof item === 'object') {
                    // Check for exportType in various possible locations
                    const exportType = item.exportType || item.type || null;
                    
                    if (exportType) {
                        validItemsCount++;
                        const exportTypeStr = String(exportType);
                        if (!groupedQueue[exportTypeStr]) {
                            groupedQueue[exportTypeStr] = [];
                        }
                        groupedQueue[exportTypeStr].push(item);
                    }
                }
            } catch (itemErr) {
                // Silently skip invalid items
            }
        });
        
        // If no valid items found, but queueTypes has items, show queueTypes instead
        if (validItemsCount === 0 || Object.keys(groupedQueue).length === 0) {
            // Fallback: if queueTypes has items but queue doesn't, show queueTypes
            if (queueTypes.length > 0) {
                queueTypes.forEach((exportType, index) => {
                    try {
                        const exportTypeStr = String(exportType);
                        let exportTypeName = 'Export';
                        try {
                            if (typeof formatExportTypeName === 'function') {
                                exportTypeName = formatExportTypeName(exportTypeStr);
                            } else {
                                exportTypeName = exportTypeStr ? (exportTypeStr.charAt(0).toUpperCase() + exportTypeStr.slice(1) + 's') : 'Export';
                            }
                        } catch (nameErr) {
                            exportTypeName = exportTypeStr || 'Export';
                        }
                        
                        // Simple display with remove button
                        const queueItem = document.createElement('div');
                        queueItem.className = 'p-2.5 border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors flex items-center justify-between gap-2';
                        
                        const nameP = document.createElement('p');
                        nameP.className = 'text-xs font-medium text-gray-900 dark:text-gray-100 flex-1';
                        nameP.textContent = exportTypeName;
                        
                        // Remove button
                        const removeBtn = document.createElement('button');
                        removeBtn.type = 'button';
                        removeBtn.className = 'flex-shrink-0 p-1 rounded hover:bg-red-100 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500/50';
                        removeBtn.setAttribute('aria-label', `Remove ${exportTypeName} from queue`);
                        removeBtn.innerHTML = `
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        `;
                        removeBtn.onclick = function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            removeExportTypeFromQueue(exportTypeStr);
                        };
                        
                        queueItem.appendChild(nameP);
                        queueItem.appendChild(removeBtn);
                        queueList.appendChild(queueItem);
                    } catch (typeErr) {
                        // Silently skip invalid items
                    }
                });
                return; // Exit early after showing fallback
            }
            
            // No items at all
            queueList.innerHTML = '<div class="p-3 text-center"><p class="text-xs text-gray-500 dark:text-gray-400">No exports in queue</p></div>';
            return;
        }
        
        // Display grouped queue items
        const exportTypes = Object.keys(groupedQueue);
        exportTypes.forEach((exportType, index) => {
            try {
                const items = groupedQueue[exportType];
                if (!items || items.length === 0) return;
                
                // Format export type name
                let exportTypeName = 'Export';
                try {
                    if (typeof formatExportTypeName === 'function') {
                        exportTypeName = formatExportTypeName(exportType);
                    } else {
                        // Fallback formatting
                        exportTypeName = exportType ? (exportType.charAt(0).toUpperCase() + exportType.slice(1) + 's') : 'Export';
                    }
                } catch (nameErr) {
                    exportTypeName = exportType || 'Export';
                }
                
                // Simple display with remove button
                const queueItem = document.createElement('div');
                queueItem.className = 'p-2.5 border-b border-gray-100 dark:border-gray-700/50 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors flex items-center justify-between gap-2';
                
                const nameP = document.createElement('p');
                nameP.className = 'text-xs font-medium text-gray-900 dark:text-gray-100 flex-1';
                nameP.textContent = exportTypeName;
                
                // Remove button
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'flex-shrink-0 p-1 rounded hover:bg-red-100 dark:hover:bg-red-900/30 text-red-600 dark:text-red-400 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500/50';
                removeBtn.setAttribute('aria-label', `Remove ${exportTypeName} from queue`);
                removeBtn.innerHTML = `
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                `;
                removeBtn.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    removeExportTypeFromQueue(exportType);
                };
                
                queueItem.appendChild(nameP);
                queueItem.appendChild(removeBtn);
                queueList.appendChild(queueItem);
            } catch (itemError) {
                // Silently skip invalid items
            }
        });
    } catch (e) {
        // Show error state
        const queueList = document.getElementById('queueList');
        if (queueList) {
            queueList.innerHTML = '<div class="p-3 text-center"><p class="text-xs text-red-500 dark:text-red-400">Error loading queue</p></div>';
        }
    }
}

/**
 * Updates the queue status message element to sync with actual queue count
 * Uses getQueueCount() to match badge and dropdown (counts unique export types)
 * Forces fresh read from localStorage to avoid stale data
 * 
 * @example
 * updateQueueStatus(); // Updates queueStatus element with current queue count
 */
function updateQueueStatus() {
    try {
        const queueStatus = document.getElementById('queueStatus');
        if (!queueStatus) {
            return; // Element doesn't exist
        }
        
        // Force fresh read from localStorage (no caching)
        // Read queue directly to get accurate count
        let queueCount = 0;
        try {
            const queueJson = localStorage.getItem(STORAGE_EXPORT_QUEUE);
            if (queueJson) {
                const queue = JSON.parse(queueJson);
                if (Array.isArray(queue) && queue.length > 0) {
                    // Count unique export types (matches badge logic)
                    const uniqueTypes = new Set();
                    queue.forEach(item => {
                        if (item && typeof item === 'object' && item.exportType) {
                            uniqueTypes.add(String(item.exportType));
                        }
                    });
                    queueCount = uniqueTypes.size;
                }
            }
        } catch (e) {
            // Fallback to getQueueCount() if direct read fails
            queueCount = getQueueCount();
        }
        
        // Update status message based on queue count
        if (queueCount > 0) {
            // Show queue count (matches badge and dropdown)
            queueStatus.textContent = `${queueCount} export${queueCount !== 1 ? 's' : ''} waiting in queue`;
        } else {
            // Check if there's an active export (read directly from localStorage - no function dependency)
            let hasActive = false;
            try {
                const activeExports = JSON.parse(localStorage.getItem(STORAGE_EXPORT_LIST) || '[]');
                if (activeExports.length > 0) {
                    // Quick check: See if any export has processing/starting status
                    for (let i = 0; i < activeExports.length && i < 3; i++) { // Limit to 3 for speed
                        const expId = activeExports[i];
                        const expData = localStorage.getItem(STORAGE_KEY_PREFIX + expId);
                        if (expData) {
                            try {
                                const data = JSON.parse(expData);
                                if (data.status === 'processing' || data.status === 'starting') {
                                    hasActive = true;
                                    break;
                                }
                            } catch (e) {
                                // Ignore parse errors
                            }
                        }
                    }
                }
            } catch (e) {
                // Ignore errors, assume no active export
            }
            
            if (hasActive) {
                queueStatus.textContent = 'Processing export...';
            } else {
                queueStatus.textContent = 'No exports in queue';
            }
        }
    } catch (e) {
        // console.error('Error updating queue status:', e);
    }
}

/**
 * Updates the queue count display in the UI badge
 * Shows/hides the queue badge based on count and updates the queue list dropdown
 * 
 * @param {number} count - The count to display (number of unique export types in queue)
 * 
 * @example
 * updateQueueCountDisplay(2); // Shows badge with "2 in queue" and updates dropdown
 */
function updateQueueCountDisplay(count) {
    // If queue is disabled, always hide the badge and container
    if (!EXPORT_USE_QUEUE) {
        const queueBadge = document.getElementById('queueBadge');
        const queueBadgeMobile = document.getElementById('queueBadgeMobile');
        const queueBadgeContainer = document.getElementById('queueBadgeContainer');
        if (queueBadge) {
            queueBadge.classList.add('hidden');
        }
        if (queueBadgeMobile) {
            queueBadgeMobile.classList.add('hidden');
        }
        if (queueBadgeContainer) {
            queueBadgeContainer.style.display = 'none';
        }
        // Close dropdown if badge is hidden
        closeQueueDropdown();
        return;
    }
    
    // Show container when queue is enabled
    const queueBadgeContainer = document.getElementById('queueBadgeContainer');
    if (queueBadgeContainer) {
        queueBadgeContainer.style.display = '';
    }
    
    const queueCountElement = document.getElementById('queueCount');
    if (queueCountElement) {
        queueCountElement.textContent = count.toString();
        
        // Show/hide badge based on count
        const queueBadge = document.getElementById('queueBadge');
        if (queueBadge) {
            if (count > 0) {
                queueBadge.classList.remove('hidden');
            } else {
                queueBadge.classList.add('hidden');
                // Close dropdown if badge is hidden
                closeQueueDropdown();
            }
        }
    }
    
    // Also update the queue list display
    updateQueueListDisplay();
}

/**
 * Initializes the queue count display from localStorage on page load
 * Called when the page loads to restore the queue count badge state
 * 
 * @example
 * initializeQueueCount(); // Restores queue count from localStorage on page load
 */
function initializeQueueCount() {
    const count = getQueueCount();
    updateQueueCountDisplay(count);
}

/**
 * Global handler function called when export button is clicked
 * This is a placeholder function - the actual export logic is handled in startExportStreamSSE
 * Note: We don't add to queue types here because we don't know yet if it will be queued or start immediately
 * The queue type will be added in startExportStreamSSE if the export is actually queued
 * 
 * @param {Event} event - The click event from the export button
 * 
 * @example
 * // Called automatically when export button is clicked
 * handleExportClick(event);
 */
window.handleExportClick = function(event) {
    // This function is called when export button is clicked
    // Check for duplicates BEFORE Livewire method is called to prevent isExporting state from being set
    const exportButton = event.target.closest('[data-export-type]') || event.target.closest('a[wire\\:click*="export"]');
    if (exportButton) {
        const exportType = exportButton.getAttribute('data-export-type');
        if (exportType) {
            // Quick check using localStorage (available before Livewire initializes)
            // Check if exportType is in queue
            const queueTypes = getQueueTypes();
            if (queueTypes.includes(exportType)) {
                // Prevent the click from reaching Livewire
                event.preventDefault();
                event.stopPropagation();
                event.stopImmediatePropagation();
                
                const exportTypeName = formatExportTypeName(exportType);
                if (typeof window.showFlashMessage === 'function') {
                    window.showFlashMessage('info', `A ${exportTypeName} export is already in queue. Please wait for it to complete.`);
                }
                return false; // Prevent export from starting
            }
            
            // Check if exportType is currently active (processing or starting) - check localStorage only
            const STORAGE_KEY_PREFIX = 'export_active_';
            const STORAGE_EXPORT_LIST = 'export_active_list';
            try {
                const activeExports = JSON.parse(localStorage.getItem(STORAGE_EXPORT_LIST) || '[]');
                if (activeExports.length > 0) {
                    for (const exportId of activeExports) {
                        const exportData = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
                        if (exportData) {
                            try {
                                const data = JSON.parse(exportData);
                                // Check if export is in progress and has the same exportType
                                if ((data.status === 'processing' || data.status === 'starting') && 
                                    data.exportType === exportType) {
                                    // Prevent the click from reaching Livewire
                                    event.preventDefault();
                                    event.stopPropagation();
                                    event.stopImmediatePropagation();
                                    
                                    const exportTypeName = formatExportTypeName(exportType);
                                    if (typeof window.showFlashMessage === 'function') {
                                        window.showFlashMessage('warning', `A ${exportTypeName} export is already in progress. Please wait for it to complete.`);
                                    }
                                    return false; // Prevent export from starting
                                }
                            } catch (e) {
                                // Ignore parse errors, continue checking
                            }
                        }
                    }
                }
            } catch (e) {
                // Ignore errors, allow export to proceed
            }
        }
    }
    
    // Note: We don't add to queue types here because we don't know yet if it will be queued or start immediately
    // The queue type will be added in startExportStreamSSE if the export is actually queued
    // console.log('Export button clicked');
}

/**
 * Toggles the queue dropdown visibility (open/close)
 * Called when user clicks on the queue badge to view queued exports
 * 
 * @param {Event} event - Click event from the queue badge button
 * 
 * @example
 * // Called from onclick handler in the queue badge
 * toggleQueueDropdown(event);
 */
window.toggleQueueDropdown = function(event) {
    if (event) {
        event.stopPropagation();
    }
    
    const dropdown = document.getElementById('queueDropdown');
    const badge = document.getElementById('queueBadge');
    const arrow = document.getElementById('queueBadgeArrow');
    
    if (!dropdown || !badge) return;
    
    const isOpen = !dropdown.classList.contains('hidden');
    
    if (isOpen) {
        closeQueueDropdown();
    } else {
        openQueueDropdown();
    }
}

/**
 * Opens the queue dropdown menu
 * Updates the queue list display, shows the dropdown, and rotates the arrow icon
 * 
 * @example
 * openQueueDropdown(); // Opens the queue dropdown and updates its content
 */
function openQueueDropdown() {
    const dropdown = document.getElementById('queueDropdown');
    const badge = document.getElementById('queueBadge');
    const arrow = document.getElementById('queueBadgeArrow');
    
    if (!dropdown || !badge) return;
    
    // Update queue list before showing
    updateQueueListDisplay();
    
    // Sync queue status message when dropdown opens
    updateQueueStatus();
    
    dropdown.classList.remove('hidden');
    badge.setAttribute('aria-expanded', 'true');
    if (arrow) {
        arrow.style.transform = 'rotate(180deg)';
    }
}

/**
 * Closes the queue dropdown menu
 * Hides the dropdown and resets the arrow icon rotation
 * 
 * @example
 * closeQueueDropdown(); // Closes the queue dropdown
 */
function closeQueueDropdown() {
    const dropdown = document.getElementById('queueDropdown');
    const badge = document.getElementById('queueBadge');
    const arrow = document.getElementById('queueBadgeArrow');
    
    if (!dropdown || !badge) return;
    
    dropdown.classList.add('hidden');
    badge.setAttribute('aria-expanded', 'false');
    if (arrow) {
        arrow.style.transform = 'rotate(0deg)';
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const queueBadgeContainer = document.getElementById('queueBadgeContainer');
    const dropdown = document.getElementById('queueDropdown');
    
    if (queueBadgeContainer && dropdown && !queueBadgeContainer.contains(event.target)) {
        closeQueueDropdown();
    }
}, true);

/**
 * Retrieves the export type for a specific export ID from localStorage
 * Used to get the type of export (e.g., "user", "role") for a given export ID
 * 
 * @param {string} exportId - The unique export ID to look up
 * @returns {string|null} The export type (e.g., "user", "role"), or null if not found/invalid
 * 
 * @example
 * const type = getExportType('export_1234567890_abc'); // Returns "user", "role", or null
 */
function getExportType(exportId) {
    try {
        const STORAGE_KEY_PREFIX = 'export_active_';
        const exportData = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
        if (exportData) {
            const data = JSON.parse(exportData);
            const exportType = data.exportType;
            // Validate exportType before returning
            if (exportType && typeof exportType === 'string' && exportType.trim() !== '') {
                return exportType.trim();
            } else {
                // console.warn('Invalid or missing exportType in stored data for exportId:', exportId);
                return null;
            }
        }
    } catch (e) {
        // console.error('Error getting export type:', e);
    }
    return null; // Return null instead of defaulting to 'user'
}

/**
 * Updates all progress bars in the UI with the current export progress
 * Available globally before Livewire initializes so it can be called from anywhere
 * Updates both desktop and mobile progress bars, progress text, and waiting messages
 * Supports ETA (Estimated Time to Arrival) display in the progress text
 * 
 * @param {number} percentage - The progress percentage (0-100)
 * @param {string|null} message - Optional message to display (e.g., "Exporting Users...")
 * @param {number|null} etaSeconds - Optional ETA in seconds to display in progress text
 * 
 * @example
 * updateProgressBars(50, 'Exporting Users...', 120); // Shows 50% with message and ~2m ETA
 */
window.updateProgressBars = function(percentage, message = null, etaSeconds = null) {
    try {
        // Get all progress bar elements (there may be two - old design and new design)
        const progressBars = document.querySelectorAll('#progressBar');
        const progressBarsMobile = document.querySelectorAll('#progressBarMobile');
        const progressTexts = document.querySelectorAll('#progressText');
        const progressTextsMobile = document.querySelectorAll('#progressTextMobile');
        const waitingMessages = document.querySelectorAll('#waitingMessage');
        
        const percentageValue = Math.round(percentage);
        let percentageStr = percentageValue + '%';
        
        // Add ETA to progress text if available
        if (etaSeconds !== null && etaSeconds > 0) {
            const etaMinutes = Math.floor(etaSeconds / 60);
            const etaSecs = Math.floor(etaSeconds % 60);
            if (etaMinutes > 0) {
                percentageStr += ` (~${etaMinutes}m ${etaSecs}s)`;
            } else {
                percentageStr += ` (~${etaSecs}s)`;
            }
        }
        
        // Update all desktop progress bars
        progressBars.forEach(progressBar => {
            if (progressBar) {
                progressBar.style.width = percentage + '%';
            }
        });
        
        // Update all progress text elements
        progressTexts.forEach(progressText => {
            if (progressText) {
                progressText.textContent = percentageStr;
            }
        });
        
        // Update all mobile progress bars
        progressBarsMobile.forEach(progressBarMobile => {
            if (progressBarMobile) {
                progressBarMobile.style.width = percentage + '%';
            }
        });
        
        // Update all mobile progress text elements
        progressTextsMobile.forEach(progressTextMobile => {
            if (progressTextMobile) {
                progressTextMobile.textContent = percentageStr;
            }
        });
        
        // Update all waiting messages if provided
        if (message) {
            waitingMessages.forEach(waitingMessage => {
                if (waitingMessage) {
                    waitingMessage.textContent = message;
                }
            });
        }
    } catch (err) {
        // console.warn('Error updating progress bars:', err);
    }
};

/**
 * Shows the appropriate progress bar based on the current page
 * Available globally - determines which progress bar design to show:
 * - Old design on the start page (where export was initiated)
 * - New fixed design on other pages (slides down from top)
 * Only shows if there are active exports in progress
 * 
 * @example
 * showExportProgress(); // Shows/hides progress bar based on active exports and current page
 */
window.showExportProgress = function() {
    try {
        // First check if there are any active exports
        const STORAGE_KEY_PREFIX = 'export_active_';
        const STORAGE_EXPORT_LIST = 'export_active_list';
        const activeExports = JSON.parse(localStorage.getItem(STORAGE_EXPORT_LIST) || '[]');
        
        // If no active exports, hide all progress bars and return
        if (activeExports.length === 0) {
            const progressBarDivs = document.querySelectorAll('#progressBarDiv');
            progressBarDivs.forEach(progressBarDiv => {
                if (progressBarDiv) {
                    progressBarDiv.style.display = 'none';
                    progressBarDiv.classList.add('hidden');
                    // Reset transform for fixed progress bar
                    if (progressBarDiv.classList.contains('fixed')) {
                        progressBarDiv.style.transform = 'translateY(-100%)';
                    }
                    // Remove body padding
                    document.body.style.paddingTop = '';
                }
            });
            // Update toast position when progress bar is hidden
            updateFlashMessagePosition();
            return;
        }
        
        // Get current page URL
        const currentPage = window.location.pathname;
        
        // Get the start page from the first active export (sent from Table.php, e.g., 'role', 'users')
        const startPage = activeExports.length > 0 ? (() => {
            const firstExportId = activeExports[0];
            const firstExportData = localStorage.getItem(STORAGE_KEY_PREFIX + firstExportId);
            if (firstExportData) {
                try {
                    const data = JSON.parse(firstExportData);
                    return data.startPage || null;
                } catch (e) {
                    // Ignore parse errors
                }
            }
            return null;
        })() : null;
        
        // Determine which progress bar to show
        const isStartPage = startPage && currentPage === startPage;
        
        // Get all progress bar divs (there may be two - old design and new design)
        const progressBarDivs = document.querySelectorAll('#progressBarDiv');
        
        if (progressBarDivs.length > 0) {
            progressBarDivs.forEach(progressBarDiv => {
                if (progressBarDiv) {
                    const isFixed = progressBarDiv.classList.contains('fixed');
                    
                    if (isStartPage) {
                        // On start page: show only old design (not fixed), hide top design (fixed)
                        if (isFixed) {
                            // Hide top progress bar on start page
                            progressBarDiv.style.display = 'none';
                            progressBarDiv.classList.add('hidden');
                            progressBarDiv.style.transform = 'translateY(-100%)';
                            // Remove body padding
                            document.body.style.paddingTop = '';
                        } else {
                            // Show old progress bar on start page
                            progressBarDiv.classList.remove('hidden');
                            progressBarDiv.style.display = 'block';
                        }
                    } else {
                        // On other pages: show only top design (fixed), hide old design (not fixed)
                        if (isFixed) {
                            // Show top progress bar on other pages
                            progressBarDiv.classList.remove('hidden');
                            progressBarDiv.style.display = 'block';
                            // Slide down animation
                            requestAnimationFrame(() => {
                                progressBarDiv.style.transform = 'translateY(0)';
                            });
                            
                            // Add padding to body to prevent content from being hidden behind fixed progress bar
                            setTimeout(() => {
                                const progressBarHeight = progressBarDiv.offsetHeight || 80;
                                document.body.style.paddingTop = progressBarHeight + 'px';
                                // Update toast position when progress bar is shown
                                updateFlashMessagePosition();
                            }, 50);
                        } else {
                            // Hide old progress bar on other pages
                            progressBarDiv.style.display = 'none';
                            progressBarDiv.classList.add('hidden');
                        }
                    }
                }
            });
        } else {
            // console.warn('Progress bar element not found in DOM');
        }
    } catch (err) {
        // console.warn('Error showing export progress:', err);
    }
};

// Initialize export check on page load - runs immediately and on DOMContentLoaded
(function() {
    const STORAGE_KEY_PREFIX = 'export_active_';
    const STORAGE_EXPORT_LIST = 'export_active_list';
    
    function checkAndShowProgressBar() {
        try {
            // Check if progress bar element exists (can be multiple - old and new design)
            const progressBarDivs = document.querySelectorAll('#progressBarDiv');
            if (progressBarDivs.length === 0) {
                // Progress bar not in DOM yet, try again
                return false;
            }
            
            // Check for active exports
            const activeExports = JSON.parse(localStorage.getItem(STORAGE_EXPORT_LIST) || '[]');
            
            if (activeExports.length > 0) {
                const firstExportId = activeExports[0];
                const firstExportData = localStorage.getItem(STORAGE_KEY_PREFIX + firstExportId);
                
                if (firstExportData) {
                    try {
                        const data = JSON.parse(firstExportData);
                        const percentage = data.percentage || 0;
                        
                        // Get current page and start page to determine which progress bar to show
                        const currentPage = window.location.pathname;
                        const startPage = data.startPage || null;
                        const isStartPage = startPage && currentPage === startPage;
                        
                        // Show progress bar immediately with stored progress (only if export is in progress)
                        if (typeof window.showExportProgress === 'function') {
                            window.showExportProgress();
                            
                            // Add cancel button and preview button if export is in progress
                            if ((data.status === 'processing' || data.status === 'starting') && typeof addCancelButton === 'function') {
                                addCancelButton(firstExportId);
                                if (typeof addDownloadCurrentButton === 'function') {
                                    addDownloadCurrentButton(firstExportId);
                                }
                            }
                            
                            if (typeof window.updateProgressBars === 'function') {
                                const exportType = data.exportType || 'user';
                                const exportTypeName = formatExportTypeName(exportType);
                                window.updateProgressBars(percentage, `Checking ${exportTypeName} export status...`);
                            }
                            return true;
                        } else {
                            // console.warn('[Export Progress] showExportProgress function not available');
                        }
                    } catch (e) {
                        // console.error('[Export Progress] Error parsing export data:', e);
                    }
                } else {
                    // No export data, hide progress bars
                    if (typeof window.showExportProgress === 'function') {
                        window.showExportProgress(); // This will hide them since no active exports
                    }
                }
            } else {
                // No active exports, hide progress bars
                if (typeof window.showExportProgress === 'function') {
                    window.showExportProgress(); // This will hide them since no active exports
                }
            }
        } catch (e) {
            // console.error('[Export Progress] Error checking for active exports:', e);
        }
        return false;
    }
    
    // Try immediately if DOM is ready
    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        if (!checkAndShowProgressBar()) {
            // If progress bar not found, try again after a short delay
            setTimeout(() => {
                if (!checkAndShowProgressBar()) {
                    // Try one more time after a longer delay
                    setTimeout(checkAndShowProgressBar, 300);
                }
            }, 100);
        }
    }
    
    // Also listen for DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                checkAndShowProgressBar();
            }, 50);
            // Initialize queue count from localStorage
            initializeQueueCount();
        });
    } else {
        // DOM already loaded, initialize immediately
        initializeQueueCount();
    }
    
    // Also listen for Livewire navigation events (multiple events for compatibility)
    document.addEventListener('livewire:navigated', () => {
        setTimeout(() => {
            checkAndShowProgressBar();
            // Also update progress bar visibility based on current page (will hide if no active exports)
            if (typeof window.showExportProgress === 'function') {
                window.showExportProgress();
            }
        }, 100);
    });
    
    document.addEventListener('livewire:navigate', () => {
        setTimeout(() => {
            checkAndShowProgressBar();
            // Also update progress bar visibility based on current page (will hide if no active exports)
            if (typeof window.showExportProgress === 'function') {
                window.showExportProgress();
            }
        }, 200);
    });
    
    // Listen for Livewire initialization
    document.addEventListener('livewire:initialized', () => {
        setTimeout(() => {
            checkAndShowProgressBar();
            // Ensure progress bars are hidden if no active exports
            if (typeof window.showExportProgress === 'function') {
                window.showExportProgress();
            }
        }, 100);
    });
    
    // Also check on window load (for full page reloads)
    window.addEventListener('load', () => {
        setTimeout(() => {
            checkAndShowProgressBar();
            // Ensure progress bars are hidden if no active exports
            if (typeof window.showExportProgress === 'function') {
                window.showExportProgress();
            }
            // Initialize queue count from localStorage
            initializeQueueCount();
        }, 100);
    });
})();

/**
 * Updates the position of the flash message container based on progress bar visibility
 * Positions toast messages below the progress bar when it's visible
 * Available globally before Livewire initializes so it can be called from showExportProgress
 * 
 * @example
 * updateFlashMessagePosition(); // Updates toast position based on progress bar
 */
function updateFlashMessagePosition() {
    const alertContainer = document.getElementById('flash-alert-container');
    if (!alertContainer) {
        return; // No toast container exists yet
    }
    
    const progressBarDiv = document.getElementById('progressBarDiv');
    let topOffset = '1rem'; // Default top-4 (1rem)
    
    if (progressBarDiv && !progressBarDiv.classList.contains('hidden') && progressBarDiv.style.display !== 'none') {
        // Progress bar is visible, calculate its height and position toast below it
        const progressBarHeight = progressBarDiv.offsetHeight || 80; // Default to 80px if not available
        const progressBarTop = progressBarDiv.getBoundingClientRect().top;
        const progressBarBottom = progressBarTop + progressBarHeight;
        
        // Position toast below progress bar with some spacing (1rem = 16px)
        topOffset = (progressBarBottom + 16) + 'px';
    }
    
    // Apply the calculated top position
    alertContainer.style.top = topOffset;
}

/**
 * Escapes HTML special characters to prevent XSS attacks
 * 
 * @param {string} text - Text to escape
 * @returns {string} Escaped HTML text
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Gets the current export queue from localStorage
 * Returns an array of queued export objects
 * Available globally before Livewire initializes
 * 
 * @returns {Array} Array of queued export objects, empty array if queue is empty or error occurs
 * 
 * @example
 * const queue = getQueue(); // Returns [{ exportId: '...', exportType: 'user', ... }, ...]
 */
function getQueue() {
    try {
        return JSON.parse(localStorage.getItem(STORAGE_EXPORT_QUEUE) || '[]');
    } catch (e) {
        // console.error('Error getting export queue:', e);
        return [];
    }
}

/**
 * Removes all exports of a specific exportType from the queue
 * Called when user clicks remove button in queue dropdown
 * Updates the queue badge UI after removal
 * Available globally before Livewire initializes
 * 
 * @param {string} exportType - The exportType to remove from queue (e.g., "user", "role")
 * 
 * @example
 * removeExportTypeFromQueue('user'); // Removes all user exports from queue
 */
function removeExportTypeFromQueue(exportType) {
    try {
        if (!exportType) return;
        
        const queue = getQueue();
        const filteredQueue = queue.filter(item => item.exportType !== exportType);
        
        // Update localStorage
        localStorage.setItem(STORAGE_EXPORT_QUEUE, JSON.stringify(filteredQueue));
        
        // Remove exportType from queue types
        removeQueueType(exportType);
        
        // Update UI (check if functions exist - they may be inside Livewire block)
        if (typeof updateQueueBadge === 'function') {
            updateQueueBadge();
        }
        if (typeof updateQueueListDisplay === 'function') {
            updateQueueListDisplay();
        }
        
        // Sync queue status message immediately (use requestAnimationFrame to ensure localStorage is updated)
        requestAnimationFrame(() => {
            updateQueueStatus();
        });
        
        // Show confirmation message
        const exportTypeName = formatExportTypeName(exportType);
        if (typeof window.showFlashMessage === 'function') {
            window.showFlashMessage('info', `${exportTypeName} export removed from queue.`);
        }
        
        // console.log(`[Export Queue] Removed all ${exportType} exports from queue`);
    } catch (e) {
        // console.error('Error removing export type from queue:', e);
        if (typeof window.showFlashMessage === 'function') {
            window.showFlashMessage('error', 'Unable to remove export from queue. Please try again.');
        }
    }
}

/**
 * Shows a flash message matching the flash message component design
 * Creates a flash message element with the same styling as the Livewire flash message component
 * Available globally before Livewire initializes so it can be called from handleExportClick
 * 
 * @param {string} type - Message type: 'error', 'success', 'warning', or 'info'
 * @param {string} message - Message text to display
 * 
 * @example
 * showFlashMessage('error', 'Export failed');
 * showFlashMessage('success', 'Export completed successfully');
 */
window.showFlashMessage = function(type, message) {
    // Ensure we have a container for alerts
    let alertContainer = document.getElementById('flash-alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.id = 'flash-alert-container';
        alertContainer.className = 'fixed right-4 z-[90] space-y-2';
        document.body.appendChild(alertContainer);
    }
    
    // Check if progress bar is visible and adjust position accordingly
    const progressBarDiv = document.getElementById('progressBarDiv');
    let topOffset = '1rem'; // Default top-4 (1rem)
    
    if (progressBarDiv && !progressBarDiv.classList.contains('hidden') && progressBarDiv.style.display !== 'none') {
        // Progress bar is visible, calculate its height and position toast below it
        const progressBarHeight = progressBarDiv.offsetHeight || 80; // Default to 80px if not available
        const progressBarTop = progressBarDiv.getBoundingClientRect().top;
        const progressBarBottom = progressBarTop + progressBarHeight;
        
        // Position toast below progress bar with some spacing (1rem = 16px)
        topOffset = (progressBarBottom + 16) + 'px';
    }
    
    // Apply the calculated top position
    alertContainer.style.top = topOffset;

    // Icon SVG paths for different types
    const icons = {
        error: `<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="8" x2="12" y2="12"></line>
            <line x1="12" y1="16" x2="12.01" y2="16"></line>
        </svg>`,
        success: `<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
            <polyline points="22 4 12 14.01 9 11.01"></polyline>
        </svg>`,
        warning: `<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
            <line x1="12" y1="9" x2="12" y2="13"></line>
            <line x1="12" y1="17" x2="12.01" y2="17"></line>
        </svg>`,
        info: `<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="12" y1="16" x2="12" y2="12"></line>
            <line x1="12" y1="8" x2="12.01" y2="8"></line>
        </svg>`
    };

    // Color classes based on type
    const colorClasses = {
        error: 'bg-red-50 border-red-200 text-red-800',
        success: 'bg-green-50 border-green-200 text-green-800',
        warning: 'bg-yellow-50 border-yellow-200 text-yellow-800',
        info: 'bg-blue-50 border-blue-200 text-blue-800'
    };

    // Close icon SVG
    const closeIcon = `<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
    </svg>`;

    const alertType = type || 'info';
    const iconHtml = icons[alertType] || icons.info;
    const colorClass = colorClasses[alertType] || colorClasses.info;

    // Create the alert element
    const alertId = 'flash-alert-' + Date.now();
    const alertHtml = `
        <div
            id="${alertId}"
            data-flash-alert
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 5000)"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-95 translate-x-full"
            x-transition:enter-end="opacity-100 transform scale-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100 translate-x-0"
            x-transition:leave-end="opacity-0 transform scale-95 translate-x-full"
            class="max-w-sm w-full"
        >
            <div class="flex items-center justify-between rounded-lg border px-4 py-3 shadow-lg ${colorClass}" role="alert">
                <div class="flex items-center">
                    <div class="mr-2">${iconHtml}</div>
                    <span class="text-sm font-medium">${escapeHtml(message)}</span>
                </div>
                <button 
                    type="button"
                    x-on:click="$el.closest('[data-flash-alert]').remove()"
                    class="ml-3 inline-flex h-6 w-6 items-center justify-center rounded-md hover:bg-black hover:bg-opacity-10 focus:outline-none"
                >
                    ${closeIcon}
                </button>
            </div>
        </div>
    `;

    // Create a temporary container and insert the HTML
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = alertHtml.trim();
    const alertElement = tempDiv.firstChild;

    // Append to container
    alertContainer.appendChild(alertElement);

    // Show immediately without waiting for Alpine (faster display)
    alertElement.style.display = 'block';
    alertElement.style.opacity = '1';
    
    // Initialize Alpine.js on the new element if Alpine is available (non-blocking)
    if (window.Alpine) {
        try {
            Alpine.initTree(alertElement);
        } catch (e) {
            // If Alpine fails, still show the message
            // console.warn('Alpine initialization failed for flash message, showing anyway:', e);
        }
    } else {
        // If Alpine not available, show message without Alpine animations
        alertElement.style.transform = 'translateX(0)';
        alertElement.style.transition = 'opacity 0.3s ease-out';
    }

    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertElement && alertElement.parentNode) {
            // Fade out before removing
            alertElement.style.opacity = '0';
            alertElement.style.transition = 'opacity 0.2s ease-in';
            setTimeout(() => {
                if (alertElement && alertElement.parentNode) {
                    alertElement.remove();
                }
            }, 200);
        }
    }, 5000);
};

// Wait for Livewire to be initialized
document.addEventListener('livewire:initialized', () => {

// Store active SSE connections (support multiple concurrent exports)
let exportProgressEventSource = null;
const exportStreamConnections = new Map(); // Map<exportId, {eventSource, fileContent, exportType}>
const exportProgressBars = new Map(); // Map<exportId, progressBarElement>
const activeAnimations = new Map(); // Map<exportId, {animationFrame, targetProgress}>
const completedExports = new Set(); // Set<exportId> - Track completed exports to prevent duplicate downloads

// showFlashMessage and escapeHtml are now defined globally above (before Livewire block)
// They are available for use throughout the file


// BroadcastChannel for cross-tab communication
let exportBroadcastChannel = null;
try {
    exportBroadcastChannel = new BroadcastChannel('export_stream_channel');
} catch (e) {
    // console.warn('BroadcastChannel not supported, cross-tab communication disabled');
}

// LocalStorage keys are defined at the top of the file

/**
 * Adds an export to the queue for later processing
 * Prevents duplicate exports by exportId and overrides exports with the same exportType
 * Updates the queue badge UI after adding
 * 
 * @param {Object} exportData - The export data object containing exportId, exportType, and eventObj
 * @param {string} exportData.exportId - Unique export identifier
 * @param {string} exportData.exportType - Type of export (e.g., "user", "role")
 * @param {Object} exportData.eventObj - Original event object with export parameters
 * 
 * @example
 * addToQueue({ exportId: 'export_123', exportType: 'user', eventObj: {...} });
 */
function addToQueue(exportData) {
    try {
        const queue = JSON.parse(localStorage.getItem(STORAGE_EXPORT_QUEUE) || '[]');
        
        // Check if this export is already in queue by exportId (prevent exact duplicates)
        const existsById = queue.some(item => item.exportId === exportData.exportId);
        if (existsById) {
            // console.log('[Export Queue] Export already in queue, skipping:', exportData.exportId);
            return;
        }
        
        // Check if an export with the same exportType already exists
        const existingIndex = queue.findIndex(item => item.exportType === exportData.exportType);
        if (existingIndex !== -1) {
            // Remove the existing export with the same exportType (override)
            const removedExport = queue.splice(existingIndex, 1)[0];
            // console.log('[Export Queue] Overriding existing export with same exportType:', removedExport.exportId, '->', exportData.exportId, 'exportType:', exportData.exportType);
            // Remove the old exportType from queue types (will be re-added below)
            removeQueueType(removedExport.exportType);
        }
        
        // Check queue limit before adding
        if (queue.length >= QUEUE_MAX_LIMIT) {
            const exportTypeName = formatExportTypeName(exportData.exportType || 'export');
            showFlashMessage('error', `Queue limit reached. Maximum ${QUEUE_MAX_LIMIT} exports allowed in queue. Please wait for current exports to complete.`);
            // console.log('[Export Queue] Queue limit reached, cannot add more exports. Current queue size:', queue.length);
            return;
        }
        
        queue.push(exportData);
        localStorage.setItem(STORAGE_EXPORT_QUEUE, JSON.stringify(queue));
        
        // Add exportType to queue types (only if not already exists - handled by addQueueType)
        if (exportData.exportType) {
            addQueueType(exportData.exportType);
        }
        
        updateQueueBadge(); // Update UI
        
        // Sync queue status message (use requestAnimationFrame to ensure localStorage is updated)
        requestAnimationFrame(() => {
            updateQueueStatus();
        });
    } catch (e) {
        // console.error('Error adding export to queue:', e);
        // console.error('Error details:', e.message, e.stack);
    }
}

// getQueue is now defined globally above (before Livewire block)
// It is available for use throughout the file

/**
 * Removes an export from the queue by exportId
 * Also removes the exportType from queue types if it was the last export of that type
 * Updates the queue badge UI after removal
 * 
 * @param {string} exportId - The unique export ID to remove from queue
 * 
 * @example
 * removeFromQueue('export_1234567890_abc'); // Removes export from queue
 */
function removeFromQueue(exportId) {
    try {
        const queue = getQueue();
        // Find the export being removed to get its exportType
        const removedExport = queue.find(item => item.exportId === exportId);
        
        const filteredQueue = queue.filter(item => item.exportId !== exportId);
        localStorage.setItem(STORAGE_EXPORT_QUEUE, JSON.stringify(filteredQueue));
        
        // Remove exportType from queue types if this was the last export of this type
        if (removedExport && removedExport.exportType) {
            // Check if there are any other exports with the same exportType in the queue
            const hasOtherSameType = filteredQueue.some(item => item.exportType === removedExport.exportType);
            if (!hasOtherSameType) {
                // No other exports of this type, remove from queue types
                removeQueueType(removedExport.exportType);
            }
        }
        
        updateQueueBadge(); // Update UI
        
        // Sync queue status message (use requestAnimationFrame to ensure localStorage is updated)
        requestAnimationFrame(() => {
            updateQueueStatus();
        });
    } catch (e) {
        // console.error('Error removing export from queue:', e);
    }
}

// removeExportTypeFromQueue is now defined globally above (before Livewire block)
// It is available for use throughout the file

/**
 * Updates the queue badge UI element with the current queue count
 * Shows/hides the badge based on queue count and updates both desktop and mobile badges
 * 
 * @example
 * updateQueueBadge(); // Updates queue badge display
 */
function updateQueueBadge() {
    try {
        // If queue is disabled, always hide the badge and container
        if (!EXPORT_USE_QUEUE) {
            const queueBadge = document.getElementById('queueBadge');
            const queueBadgeMobile = document.getElementById('queueBadgeMobile');
            const queueBadgeContainer = document.getElementById('queueBadgeContainer');
            if (queueBadge) {
                queueBadge.classList.add('hidden');
            }
            if (queueBadgeMobile) {
                queueBadgeMobile.classList.add('hidden');
            }
            if (queueBadgeContainer) {
                queueBadgeContainer.style.display = 'none';
            }
            // Close dropdown if badge is hidden
            closeQueueDropdown();
            return;
        }
        
        // Show container when queue is enabled
        const queueBadgeContainer = document.getElementById('queueBadgeContainer');
        if (queueBadgeContainer) {
            queueBadgeContainer.style.display = '';
        }
        
        // Get queue count from localStorage instead of queue length
        const queueCount = getQueueCount();
        const queueBadge = document.getElementById('queueBadge');
        const queueBadgeMobile = document.getElementById('queueBadgeMobile');
        
        // Update the display using the helper function
        updateQueueCountDisplay(queueCount);
        
        // Handle mobile badge
        if (queueBadgeMobile) {
            if (queueCount > 0) {
                queueBadgeMobile.classList.remove('hidden');
            } else {
                queueBadgeMobile.classList.add('hidden');
            }
        }
    } catch (e) {
        // console.error('Error updating queue badge:', e);
    }
}

// Cache for active export check (performance optimization)
let activeExportCache = {
    hasActive: false,
    lastCheck: 0,
    cacheDuration: 100 // Cache for 100ms to avoid excessive localStorage reads
};

/**
 * Checks if there are any active exports currently in progress
 * Checks both localStorage (for cross-tab detection) and active connections in this tab
 * Returns true if any export is in "processing" or "starting" status
 * OPTIMIZED: Uses cache to reduce localStorage reads for faster response
 * 
 * @param {boolean} [forceCheck=false] - Force check even if cache is valid
 * @returns {boolean} True if there are active exports, false otherwise
 * 
 * @example
 * if (hasActiveExport()) {
 *     // Queue the new export
 * } else {
 *     // Start immediately
 * }
 */
function hasActiveExport(forceCheck = false) {
    try {
        const now = Date.now();
        
        // Use cache if valid and not forcing check (for performance)
        if (!forceCheck && (now - activeExportCache.lastCheck) < activeExportCache.cacheDuration) {
            return activeExportCache.hasActive;
        }
        
        // Fast path: Check active connections in THIS tab first (no localStorage read)
        if (exportStreamConnections.size > 0) {
            for (const [exportId, connection] of exportStreamConnections.entries()) {
                if (connection && connection.eventSource) {
                    // Check if EventSource is still open (readyState: 0=CONNECTING, 1=OPEN, 2=CLOSED)
                    if (connection.eventSource.readyState !== 2) {
                        activeExportCache.hasActive = true;
                        activeExportCache.lastCheck = now;
                        return true; // Active export found in this tab
                    }
                }
            }
        }
        
        // Check localStorage for cross-tab detection (only if no active connections in this tab)
        const activeExports = JSON.parse(localStorage.getItem(STORAGE_EXPORT_LIST) || '[]');
        if (activeExports.length > 0) {
            // Read all export data in one batch (optimized)
            for (const exportId of activeExports) {
                const exportData = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
                if (exportData) {
                    try {
                        const data = JSON.parse(exportData);
                        // Check if export is in progress (processing or starting)
                        if (data.status === 'processing' || data.status === 'starting') {
                            activeExportCache.hasActive = true;
                            activeExportCache.lastCheck = now;
                            return true; // Active export found
                        }
                    } catch (e) {
                        // If we can't parse but export is in the list, assume it might be active
                        activeExportCache.hasActive = true;
                        activeExportCache.lastCheck = now;
                        return true; // Assume active to be safe
                    }
                }
            }
        }
        
        // No active exports found
        activeExportCache.hasActive = false;
        activeExportCache.lastCheck = now;
        return false;
    } catch (e) {
        // console.error('Error checking active exports:', e);
        return false;
    }
}

/**
 * Checks if an export with the same exportType is already active or in queue
 * Used to prevent duplicate exports of the same type
 * Returns detailed information about the duplicate if found
 * 
 * @param {string} exportType - The exportType to check (e.g., "user", "role")
 * @returns {Object|null} Returns object with duplicate info if found, null otherwise
 * @returns {boolean} return.isDuplicate - True if duplicate found
 * @returns {string} return.type - Type of duplicate: "active" or "queued"
 * @returns {string} return.exportType - The exportType that was checked
 * @returns {string} return.message - User-friendly message about the duplicate
 * @returns {number} [return.percentage] - Progress percentage if active export
 * 
 * @example
 * const duplicate = hasActiveOrQueuedExport('user');
 * if (duplicate) {
 *     // Show warning message
 * }
 */
function hasActiveOrQueuedExport(exportType) {
    try {
        if (!exportType) return null;
        
        // Check if exportType is in queue
        if (hasQueueType(exportType)) {
            const queue = getQueue();
            const queuedExport = queue.find(item => item.exportType === exportType);
            return {
                isDuplicate: true,
                type: 'queued',
                exportType: exportType,
                message: `A ${formatExportTypeName(exportType)} export is already in the queue. Please wait for it to complete.`
            };
        }
        
        // Check if exportType is currently active (processing or starting)
        const activeExports = JSON.parse(localStorage.getItem(STORAGE_EXPORT_LIST) || '[]');
        if (activeExports.length > 0) {
            for (const exportId of activeExports) {
                const exportData = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
                if (exportData) {
                    try {
                        const data = JSON.parse(exportData);
                        // Check if export is in progress and has the same exportType
                        if ((data.status === 'processing' || data.status === 'starting') && 
                            data.exportType === exportType) {
                            // Also check if connection is actually active in this tab
                            const connection = exportStreamConnections.get(exportId);
                            const isConnectionActive = connection && 
                                                      connection.eventSource && 
                                                      connection.eventSource.readyState !== 2;
                            
                            // If connection exists in this tab or status indicates active, it's a duplicate
                            if (isConnectionActive || data.status === 'processing' || data.status === 'starting') {
                                return {
                                    isDuplicate: true,
                                    type: 'active',
                                    exportType: exportType,
                                    exportId: exportId,
                                    percentage: data.percentage || 0,
                                    message: `A ${formatExportTypeName(exportType)} export is already in progress (${Math.round(data.percentage || 0)}% complete). Please wait for it to complete.`
                                };
                            }
                        }
                    } catch (e) {
                        // Ignore parse errors, continue checking
                    }
                }
            }
        }
        
        // Also check active connections in this tab
        for (const [exportId, connection] of exportStreamConnections.entries()) {
            if (connection && 
                connection.exportType === exportType && 
                connection.eventSource && 
                connection.eventSource.readyState !== 2) {
                // Get percentage from storage if available
                const exportData = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
                let percentage = 0;
                if (exportData) {
                    try {
                        const data = JSON.parse(exportData);
                        percentage = data.percentage || 0;
                    } catch (e) {
                        // Ignore parse errors
                    }
                }
                
                return {
                    isDuplicate: true,
                    type: 'active',
                    exportType: exportType,
                    exportId: exportId,
                    percentage: percentage,
                    message: `A ${formatExportTypeName(exportType)} export is already in progress (${Math.round(percentage)}% complete). Please wait for it to complete.`
                };
            }
        }
        
        return null; // No duplicate found
    } catch (e) {
        // console.error('Error checking for duplicate export:', e);
        return null;
    }
}

/**
 * Processes the next export in the queue when no active exports are running
 * Automatically starts the next queued export when the current one completes
 * Validates that only one export per exportType can run at a time
 * Shows notifications when queued exports start processing
 * OPTIMIZED: Fast processing with minimal delays
 * 
 * @example
 * processNextInQueue(); // Starts next export in queue if available
 */
function processNextInQueue() {
    try {
        // Check if queue is enabled
        const useQueue = EXPORT_USE_QUEUE;
        if (!useQueue) {
            return; // Queue is disabled, don't process
        }
        
        // PERFORMANCE: Fast check - connections first (no localStorage read)
        if (exportStreamConnections.size > 0) {
            // Check if any connection is actually active
            let hasActiveConnection = false;
            for (const [exportId, connection] of exportStreamConnections.entries()) {
                if (connection && connection.eventSource && connection.eventSource.readyState !== 2) {
                    hasActiveConnection = true;
                    break;
                }
            }
            
            if (hasActiveConnection) {
                // Retry after minimal delay (50ms for faster response)
                setTimeout(() => {
                    processNextInQueue();
                }, 50);
                return; // Still processing, wait
            }
        }
        
        // Fast check: Use cached hasActiveExport (optimized)
        if (hasActiveExport()) {
            // Retry after minimal delay (50ms for faster response)
            setTimeout(() => {
                processNextInQueue();
            }, 50);
            return; // Still processing, wait
        }
        
        // Get next export from queue
        const queue = getQueue();
        if (queue.length === 0) {
            return; // No exports in queue
        }
        
        // Get queue types to validate
        const queueTypes = getQueueTypes();
        
        // Find the first export in queue that has an exportType in queueTypes
        let nextExport = null;
        let nextExportIndex = -1;
        
        for (let i = 0; i < queue.length; i++) {
            const exportItem = queue[i];
            if (exportItem.exportType && queueTypes.includes(exportItem.exportType)) {
                nextExport = exportItem;
                nextExportIndex = i;
                break;
            }
        }
        
        // If no valid export found, skip
        if (!nextExport || nextExportIndex === -1) {
            return;
        }
        
        // Validate: Ensure only one export per exportType can be processed
        // Check if this exportType is already in active exports
        const activeExports = JSON.parse(localStorage.getItem(STORAGE_EXPORT_LIST) || '[]');
        const hasActiveSameType = activeExports.some(activeId => {
            try {
                const activeData = localStorage.getItem(STORAGE_KEY_PREFIX + activeId);
                if (activeData) {
                    const data = JSON.parse(activeData);
                    return data.exportType === nextExport.exportType;
                }
            } catch (e) {
                // Ignore errors
            }
            return false;
        });
        
        if (hasActiveSameType) {
            // Skip this export (keep it in queue) and try to find another export with different type
            // Check if there are other exports in queue with different exportTypes
            const otherExports = queue.filter((item, index) => 
                index !== nextExportIndex && 
                item.exportType && 
                queueTypes.includes(item.exportType) &&
                item.exportType !== nextExport.exportType
            );
            
            if (otherExports.length > 0) {
                // Try processing a different exportType (minimal delay)
                setTimeout(() => {
                    processNextInQueue();
                }, 50);
            }
            return;
        }
        
        // Remove from queue
        removeFromQueue(nextExport.exportId);
        
        // Remove exportType from queue types (moving to active)
        removeQueueType(nextExport.exportType);
        
        // Show notification that queued export is starting
        const exportTypeName = formatExportTypeName(nextExport.exportType);
        const remainingInQueue = queue.length - 1;
        
        // Success messages removed - only show errors
        
        // Sync queue status (use requestAnimationFrame to ensure localStorage is updated)
        requestAnimationFrame(() => {
            updateQueueStatus();
        });
        
        // Start the export
        startExportProcess(
            nextExport.eventObj,
            nextExport.exportId,
            nextExport.exportType
        );
    } catch (e) {
        // console.error('Error processing queue:', e);
    }
}

// Initialize: Check for active exports on page load and reconnect
/**
 * Checks for active exports in localStorage and attempts to reconnect to them
 * Called on page load to restore export progress if user refreshes or navigates back
 * Shows progress bar immediately and then checks server status for each export
 * 
 * @example
 * checkAndReconnectExports(); // Reconnects to active exports on page load
 */
function checkAndReconnectExports() {
    try {
        const activeExports = JSON.parse(localStorage.getItem(STORAGE_EXPORT_LIST) || '[]');
        
        // If no active exports, hide all progress bars
        if (activeExports.length === 0) {
            if (typeof window.showExportProgress === 'function') {
                window.showExportProgress(); // This will hide them since no active exports
            }
            return;
        }
        
        if (activeExports.length > 0) {
            // Show progress bar immediately if there are active exports
            // This ensures the progress bar is visible even before server check
            const firstExportId = activeExports[0];
            const firstExportData = localStorage.getItem(STORAGE_KEY_PREFIX + firstExportId);
            
                    if (firstExportData) {
                        try {
                            const data = JSON.parse(firstExportData);
                            // Show progress bar immediately with stored progress
                            const progressBarDiv = document.getElementById('progressBarDiv');
                            if (progressBarDiv) {
                                const percentage = data.percentage || 0;
                                showExportProgress();
                                
                                // Add cancel button and download current button if export is in progress
                                if (data.status === 'processing' || data.status === 'starting') {
                                    addCancelButton(firstExportId);
                                    addDownloadCurrentButton(firstExportId);
                                }
                                
                                const exportType = data.exportType || 'user';
                                const exportTypeName = formatExportTypeName(exportType);
                                updateProgressBars(percentage, `Checking ${exportTypeName} export status...`);
                                
                                // Update queue badge when showing progress
                                setTimeout(() => {
                                    updateQueueBadge();
                                }, 50);
                            }
                        } catch (e) {
                            // Invalid data, will be cleaned up below
                            // Hide progress bars if data is invalid
                            if (typeof window.showExportProgress === 'function') {
                                window.showExportProgress(); // This will hide them since no valid active exports
                            }
                        }
                    } else {
                // No export data, hide progress bars
                if (typeof window.showExportProgress === 'function') {
                    window.showExportProgress(); // This will hide them since no active exports
                }
            }
        }
        
        // Always update queue badge when checking exports
        setTimeout(() => {
            updateQueueBadge();
        }, 50);
        
        // Now check each export with the server
        activeExports.forEach(exportId => {
            const exportData = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
            if (exportData) {
                try {
                    const data = JSON.parse(exportData);
                    // Check server status and reconnect if still active
                    checkExportStatusAndReconnect(exportId, data);
                } catch (e) {
                    // Invalid data, remove from storage
                    removeExportFromStorage(exportId);
                }
            } else {
                // Data not found, remove from list
                removeExportFromStorage(exportId);
            }
        });
    } catch (e) {
        // console.error('Error checking for active exports:', e);
    }
}

// Check export status from server and reconnect if needed
async function checkExportStatusAndReconnect(exportId, storedData) {
    try {
        const response = await fetch(`/export-stream/status?exportId=${encodeURIComponent(exportId)}`);
        
        // Check if response is ok and is JSON
        if (!response.ok) {
            // Response not ok (404, 500, etc.) - likely export expired or not found
            removeExportFromStorage(exportId);
            return;
        }
        
        // Check content type to ensure it's JSON
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Not JSON response - likely an error page
            removeExportFromStorage(exportId);
            return;
        }
        
        const state = await response.json();
        
        if (state.status === 'processing') {
            // Export still in progress, reconnect
            reconnectToExport(exportId, state);
        } else if (state.status === 'complete') {
            // Export completed, trigger download
            handleExportComplete(exportId, state);
        } else if (state.status === 'error') {
            // Export error, show message and cleanup
            showFlashMessage('error', state.message || 'Something went wrong with the export. Please try again later.');
            removeExportFromStorage(exportId);
        } else {
            // Export not found or expired, cleanup
            removeExportFromStorage(exportId);
        }
    } catch (e) {
        // console.error('Error checking export status:', e);
        // On error (network, parse error, etc.), cleanup and don't try to reconnect
        // The export might have expired or the server might be unavailable
        removeExportFromStorage(exportId);
    }
}

/**
 * Reconnects to an active export that was interrupted (e.g., page refresh)
 * Restores progress from server state or localStorage, re-establishes SSE connection
 * Shows progress bar and cancel button, continues from last processed count
 * 
 * @param {string} exportId - The unique export ID to reconnect to
 * @param {Object} serverState - Server state object with current export status and progress
 * 
 * @example
 * reconnectToExport('export_123', { status: 'processing', percentage: 50, processed: 1000 });
 */
function reconnectToExport(exportId, serverState) {
    SSEExportClientLogger.info('Attempting to reconnect to export', { 
        exportId, 
        serverState: {
            status: serverState.status,
            percentage: serverState.percentage,
            processed: serverState.processed
        }
    });
    
    // Get stored data from localStorage (has original params)
    const storedDataStr = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
    if (!storedDataStr) {
        SSEExportClientLogger.error('No stored data found for reconnection', { exportId });
        return;
    }
    
    const storedData = JSON.parse(storedDataStr);
    
    // Validate exportType - it's required for reconnection
    if (!storedData.exportType || typeof storedData.exportType !== 'string' || storedData.exportType.trim() === '') {
        SSEExportClientLogger.error('Invalid exportType in stored data', { exportId, storedData });
        // console.error('Reconnection error: exportType is missing or invalid in stored data for exportId:', exportId);
        showFlashMessage('error', 'Unable to reconnect to your export. Please start a new export.');
        removeExportFromStorage(exportId);
        return;
    }
    
    const exportType = storedData.exportType.trim();
    
    // Show progress bar (will automatically show the correct one based on current page vs startPage)
    showExportProgress();
    
    // Add cancel button and download current button when reconnecting
    addCancelButton(exportId);
    addDownloadCurrentButton(exportId);
    
    // Restore progress from server state or stored data (use the higher value to avoid resetting)
    const serverPercentage = serverState.percentage || 0;
    const storedPercentage = storedData.percentage || 0;
    // Use the maximum to ensure we don't reset progress
    const percentage = Math.max(serverPercentage, storedPercentage);
    
    // Restore processed count from server state or stored data (use the higher value to avoid resetting)
    const serverProcessed = serverState.processed || 0;
    const storedProcessed = storedData.processed || 0;
    // Use the maximum to ensure we don't reset processed count
    const processed = Math.max(serverProcessed, storedProcessed);
    
    // Restore start time for ETA calculation (use stored start time or current time)
    const startTime = storedData.startTime || Date.now();
    
    SSEExportClientLogger.info('Reconnection progress restored', {
        exportId,
        exportType,
        serverPercentage,
        storedPercentage,
        finalPercentage: percentage,
        serverProcessed,
        storedProcessed,
        finalProcessed: processed
    });
    
    updateProgressBars(percentage, `Reconnecting...`);
    
    // Update stored data with the current progress and processed count to prevent reset
    updateExportInStorage(exportId, {
        percentage: percentage,
        processed: processed,
        status: 'processing',
    });
    
    // phpMyAdmin-style: fileContent is NOT stored in localStorage (to avoid quota issues)
    // CRITICAL FIX: Preserve existing fileContent on reconnection to avoid data loss
    // If connection already exists, keep its fileContent (data received before navigation)
    // If new connection, start with empty fileContent
    const existingConnection = exportStreamConnections.get(exportId);
    let preservedFileContent = '';
    
    if (existingConnection && existingConnection.fileContent) {
        // Preserve existing fileContent (data received before navigation)
        preservedFileContent = existingConnection.fileContent;
        SSEExportClientLogger.info('Preserving existing fileContent on reconnection', {
            exportId,
            preservedLength: preservedFileContent.length,
            preservedRecords: preservedFileContent.split('\n').length - 1 // Approximate record count
        });
    }
    
    const connection = existingConnection || {
        fileContent: preservedFileContent, // Preserve existing data or start fresh
        exportType: exportType,
        startTime: startTime,
        lastUpdateTime: Date.now(),
        lastProcessed: processed,
        errorNotified: false,
    };
    
    // CRITICAL: Preserve fileContent if it exists (don't reset on reconnection)
    if (preservedFileContent && !connection.fileContent) {
        connection.fileContent = preservedFileContent;
    } else if (!connection.fileContent) {
        // Only reset if no existing data
        connection.fileContent = '';
    }
    
    // Ensure reconnection resets notification throttle
    connection.errorNotified = connection.errorNotified || false;
    connection.startTime = startTime; // Restore start time for ETA
    connection.lastProcessed = processed; // Update last processed count
    connection.exportType = exportType; // Ensure exportType is set
    exportStreamConnections.set(exportId, connection);
    
    // Reconnect to SSE stream using stored parameters and last processed count
    // Pass processed count so server can continue from where it left off
    const sseUrl = `/export-stream/stream?exportType=${encodeURIComponent(exportType)}&exportId=${encodeURIComponent(exportId)}&reconnect=true&processed=${encodeURIComponent(processed)}&filters=${encodeURIComponent(JSON.stringify(storedData.filters || {}))}&checkboxValues=${encodeURIComponent(JSON.stringify(storedData.checkboxValues || []))}&search=${encodeURIComponent(storedData.search || '')}&chunkSize=${storedData.chunkSize || 100}`;
    
    SSEExportClientLogger.info('Creating new SSE connection for reconnection', {
        exportId,
        sseUrl: sseUrl.substring(0, 100) + '...', // Truncate for logging
        resumeFromRecord: processed
    });
    
    const eventSource = new EventSource(sseUrl);
    connection.eventSource = eventSource;
    exportStreamConnections.set(exportId, connection);
    
    // Handle reconnection events (same handlers as new export)
    setupEventSourceHandlers(eventSource, exportId, connection);
}

/**
 * Stores export data in localStorage for persistence across page reloads
 * Adds the exportId to the active exports list for tracking
 * 
 * @param {string} exportId - Unique export identifier
 * @param {Object} exportData - Export data object to store (filters, status, progress, etc.)
 * 
 * @example
 * storeExportInStorage('export_123', { exportType: 'user', status: 'processing', percentage: 0 });
 */
function storeExportInStorage(exportId, exportData) {
    try {
        // phpMyAdmin-style: Store only metadata, NOT fileContent (to avoid localStorage quota issues)
        // fileContent is kept in memory only (connection.fileContent)
        const metadataOnly = { ...exportData };
        delete metadataOnly.fileContent; // Remove fileContent from storage
        
        localStorage.setItem(STORAGE_KEY_PREFIX + exportId, JSON.stringify(metadataOnly));
        
        // Add to active exports list
        const activeExports = JSON.parse(localStorage.getItem(STORAGE_EXPORT_LIST) || '[]');
        if (!activeExports.includes(exportId)) {
            activeExports.push(exportId);
            localStorage.setItem(STORAGE_EXPORT_LIST, JSON.stringify(activeExports));
            
            // Invalidate cache (new export started)
            activeExportCache.lastCheck = 0;
        }
    } catch (e) {
        // Check if it's a quota exceeded error
        if (e.name === 'QuotaExceededError' || e.code === 22 || e.code === 1014) {
            // console.error('localStorage quota exceeded - storing metadata only (fileContent kept in memory)', e);
            // Try storing minimal metadata only
            try {
                const minimalData = {
                    exportId: exportData.exportId,
                    exportType: exportData.exportType,
                    status: exportData.status,
                    percentage: exportData.percentage || 0,
                    processed: exportData.processed || 0,
                    total: exportData.total || 0,
                    startPage: exportData.startPage,
                    startTime: exportData.startTime,
                    // NO fileContent - kept in memory only
                };
                localStorage.setItem(STORAGE_KEY_PREFIX + exportId, JSON.stringify(minimalData));
                
                const activeExports = JSON.parse(localStorage.getItem(STORAGE_EXPORT_LIST) || '[]');
                if (!activeExports.includes(exportId)) {
                    activeExports.push(exportId);
                    localStorage.setItem(STORAGE_EXPORT_LIST, JSON.stringify(activeExports));
                }
            } catch (e2) {
                // console.error('Error storing minimal export metadata:', e2);
            }
        } else {
            // console.error('Error storing export in localStorage:', e);
        }
    }
}

/**
 * Removes export data from localStorage and active exports list
 * Called when export completes, is cancelled, or expires
 * 
 * @param {string} exportId - Unique export identifier to remove
 * 
 * @example
 * removeExportFromStorage('export_123'); // Removes export from storage
 */
function removeExportFromStorage(exportId) {
    try {
        localStorage.removeItem(STORAGE_KEY_PREFIX + exportId);
        
        // Remove from active exports list
        const activeExports = JSON.parse(localStorage.getItem(STORAGE_EXPORT_LIST) || '[]');
        const index = activeExports.indexOf(exportId);
        if (index > -1) {
            activeExports.splice(index, 1);
            localStorage.setItem(STORAGE_EXPORT_LIST, JSON.stringify(activeExports));
            
            // Invalidate cache (export removed)
            activeExportCache.lastCheck = 0;
        }
    } catch (e) {
        // console.error('Error removing export from localStorage:', e);
    }
}

/**
 * Updates existing export data in localStorage with new values
 * Preserves the startPage value when updating to maintain page context
 * 
 * @param {string} exportId - Unique export identifier
 * @param {Object} updates - Object with properties to update (e.g., { percentage: 50, status: 'processing' })
 * 
 * @example
 * updateExportInStorage('export_123', { percentage: 50, processed: 1000 });
 */
function updateExportInStorage(exportId, updates) {
    try {
        const existing = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
        if (existing) {
            const data = JSON.parse(existing);
            // Preserve startPage when updating (don't overwrite it)
            const updated = { ...data, ...updates };
            if (data.startPage && !updates.startPage) {
                updated.startPage = data.startPage; // Preserve original startPage
            }
            
            // phpMyAdmin-style: Remove fileContent from storage (keep only in memory)
            delete updated.fileContent;
            
            localStorage.setItem(STORAGE_KEY_PREFIX + exportId, JSON.stringify(updated));
        }
    } catch (e) {
        // Check if it's a quota exceeded error
        if (e.name === 'QuotaExceededError' || e.code === 22 || e.code === 1014) {
            // console.warn('localStorage quota exceeded - skipping fileContent storage (kept in memory only)', e);
            // Try storing without fileContent
            try {
                const existing = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
                if (existing) {
                    const data = JSON.parse(existing);
                    const updated = { ...data, ...updates };
                    delete updated.fileContent; // Ensure fileContent is removed
                    if (data.startPage && !updates.startPage) {
                        updated.startPage = data.startPage;
                    }
                    localStorage.setItem(STORAGE_KEY_PREFIX + exportId, JSON.stringify(updated));
                }
            } catch (e2) {
                // console.error('Error updating export metadata (quota exceeded):', e2);
            }
        } else {
            // console.error('Error updating export in localStorage:', e);
        }
    }
}

/**
 * Broadcasts a message to other browser tabs using BroadcastChannel API
 * Used for cross-tab communication (e.g., notify other tabs when export completes)
 * 
 * @param {Object} message - Message object to broadcast (e.g., { type: 'export_complete', ... })
 * 
 * @example
 * broadcastToOtherTabs({ type: 'export_complete', exportId: 'export_123', fileContent: '...' });
 */
function broadcastToOtherTabs(message) {
    if (exportBroadcastChannel) {
        try {
            exportBroadcastChannel.postMessage(message);
        } catch (e) {
            // console.error('Error broadcasting to other tabs:', e);
        }
    }
}

// Listen for messages from other tabs
if (exportBroadcastChannel) {
    exportBroadcastChannel.onmessage = function(event) {
        const message = event.data;
        
        if (message.type === 'export_complete') {
            const { exportId, fileContent, downloadUrl, filename } = message;
            
            // Only download if this export is NOT in our current connections
            // (meaning it was started in another tab, not this one)
            // This prevents duplicate downloads when the same tab receives its own broadcast
            if (!exportStreamConnections.has(exportId)) {
                // Check if this export is in our storage (user might have started it in another tab)
                const exportData = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
                if (exportData) {
                    // phpMyAdmin-style: Download from server URL if available, otherwise use fileContent
                    if (downloadUrl) {
                        const downloadLink = document.createElement('a');
                        downloadLink.href = downloadUrl;
                        downloadLink.download = filename;
                        downloadLink.style.display = 'none';
                        document.body.appendChild(downloadLink);
                        downloadLink.click();
                        document.body.removeChild(downloadLink);
                    } else if (fileContent) {
                        downloadExportFile(exportId, fileContent, filename, 'BroadcastChannel');
                    }
                    removeExportFromStorage(exportId);
                    
                    // Hide progress bar if visible
                    stopExportProgress();
                }
            }
        } else if (message.type === 'export_progress') {
            const { exportId, percentage } = message;
            
            // Update progress bar if this export is active in this tab
            if (exportStreamConnections.has(exportId)) {
                const progressBar = document.getElementById('progressBar');
                const progressText = document.getElementById('progressText');
                const waitingMessage = document.getElementById('waitingMessage');
                
                if (progressBar && progressText && waitingMessage) {
                    const exportType = getExportType(exportId);
                    const exportTypeName = formatExportTypeName(exportType);
                    progressBar.style.width = percentage + '%';
                    progressText.textContent = Math.round(percentage) + '%';
                    waitingMessage.textContent = `Exporting ${exportTypeName}...`;
                }
            }
        }
    };
}

/**
 * Closes all SSE connections immediately and synchronously
 * Critical for navigation - EventSource connections must be closed before page navigation
 * Closes all EventSource connections, cancels animations, and clears connection maps
 * Called before Livewire navigation and page unload to prevent connection blocking
 * 
 * @example
 * cleanupAllConnections(); // Closes all connections before navigation
 */
function cleanupAllConnections() {
    // Close legacy EventSource immediately
    if (exportProgressEventSource) {
        try {
            // Remove all event listeners to prevent any callbacks during close
            exportProgressEventSource.onopen = null;
            exportProgressEventSource.onmessage = null;
            exportProgressEventSource.onerror = null;
            exportProgressEventSource.close();
        } catch (e) {
            // Ignore errors - just ensure it's null
        }
        exportProgressEventSource = null;
    }
    
    // Cancel all active animations immediately
    activeAnimations.forEach((animation, exportId) => {
        try {
            if (animation && animation.animationFrame) {
                cancelAnimationFrame(animation.animationFrame);
            }
        } catch (e) {
            // Ignore errors
        }
    });
    activeAnimations.clear();
    
    // Close all streaming connections immediately and synchronously
    // This is critical - EventSource connections must be closed before page navigation
    // CRITICAL FIX: Preserve fileContent when closing connections for reconnection
    // Close EventSource but keep connection objects with fileContent for data preservation
    // MEMORY SAFEGUARD: Limit preserved connections to prevent memory buildup
    const MAX_PRESERVED_CONNECTIONS = 5; // Limit to 5 preserved exports to prevent memory issues
    let preservedCount = 0;
    
    exportStreamConnections.forEach((connection, exportId) => {
        try {
            if (connection && connection.eventSource) {
                // Remove all event listeners first to prevent callbacks
                connection.eventSource.onopen = null;
                connection.eventSource.onmessage = null;
                connection.eventSource.onerror = null;
                // Close EventSource immediately (required for navigation)
                connection.eventSource.close();
                
                // MEMORY SAFEGUARD: Only preserve connections with significant data
                // For large datasets, we need to preserve, but limit total preserved connections
                const fileContentSize = connection.fileContent ? connection.fileContent.length : 0;
                const shouldPreserve = fileContentSize > 0 && preservedCount < MAX_PRESERVED_CONNECTIONS;
                
                if (shouldPreserve) {
                    // CRITICAL: Preserve fileContent by keeping connection object
                    // Just remove eventSource reference - fileContent stays in memory
                    connection.eventSource = null;
                    // Mark as disconnected for reconnection detection
                    connection.isDisconnected = true;
                    preservedCount++;
                    
                    SSEExportClientLogger.info('Preserving connection for reconnection', {
                        exportId,
                        fileContentSizeMB: (fileContentSize / 1024 / 1024).toFixed(2),
                        preservedCount
                    });
                } else {
                    // Too many connections or no data - clear to free memory
                    if (fileContentSize > 0) {
                        SSEExportClientLogger.warning('Clearing connection due to preservation limit', {
                            exportId,
                            fileContentSizeMB: (fileContentSize / 1024 / 1024).toFixed(2),
                            reason: preservedCount >= MAX_PRESERVED_CONNECTIONS ? 'max_connections' : 'no_data'
                        });
                    }
                    // Clear fileContent to free memory
                    connection.fileContent = null;
                    exportStreamConnections.delete(exportId);
                }
            }
        } catch (e) {
            // Ignore errors - navigation must proceed
        }
    });
    // Note: Some connections may be preserved, others cleared based on memory limits
    exportProgressBars.clear();
}

// Close all SSE connections on Livewire navigation (but keep state in localStorage)
// Use both events to ensure cleanup happens
document.addEventListener('livewire:before-navigate', () => {
    cleanupAllConnections();
}, { once: false, passive: true });

// Also listen to navigate event (Livewire v3)
document.addEventListener('livewire:navigate', () => {
    cleanupAllConnections();
}, { once: false, passive: true });

// Handle page visibility change (user switches tabs, minimizes window, etc.)
// Note: We do NOT close or reconnect - let exports continue running without interruption
document.addEventListener('visibilitychange', () => {
    // Do nothing - exports continue running in background
}, { passive: true });

// Handle regular page navigation - but don't use beforeunload as it can block navigation
// Instead, use pagehide which is more reliable and doesn't block
window.addEventListener('pagehide', () => {
    cleanupAllConnections();
}, { passive: true });

// Intercept link clicks to ensure cleanup before navigation (only for non-Livewire links)
// This is critical for SSE - EventSource must be closed before browser navigation
document.addEventListener('click', (e) => {
    // Only handle if there are active exports
    if (exportStreamConnections.size > 0 || exportProgressEventSource) {
        // Only intercept actual anchor links (not buttons, forms, etc.)
        const link = e.target.closest('a[href]');
        
        if (link && link.tagName === 'A') {
            const href = link.getAttribute('href');
            
            // Skip if it's a Livewire navigate link
            const isLivewireNavigate = link.hasAttribute('wire:navigate') || 
                                      link.closest('[wire\\:navigate]');
            
            // Only handle actual navigation links (not hash, javascript, mailto, tel, etc.)
            const isNavigationLink = href && 
                                    !href.startsWith('#') && 
                                    !href.startsWith('javascript:') && 
                                    !href.startsWith('mailto:') && 
                                    !href.startsWith('tel:') &&
                                    !href.startsWith('data:');
            
            // If it's a regular navigation link (not Livewire), cleanup immediately
            if (!isLivewireNavigate && isNavigationLink) {
                // Cleanup connections immediately and synchronously before navigation
                // This is critical - EventSource must be closed before browser navigation
                cleanupAllConnections();
            }
        }
    }
}, { capture: false, passive: true });

// Also handle form submissions that might cause navigation
document.addEventListener('submit', (e) => {
    // Only handle if there are active exports
    if (exportStreamConnections.size > 0 || exportProgressEventSource) {
        const form = e.target;
        if (form && form.tagName === 'FORM') {
            // Only cleanup for GET forms (which cause navigation) or forms with explicit action
            const method = (form.method || 'get').toLowerCase();
            const action = form.action || form.getAttribute('action') || '';
            
            // Skip if it's a Livewire form or AJAX form
            const isLivewireForm = form.hasAttribute('wire:submit') || 
                                  form.closest('[wire\\:submit]') ||
                                  form.hasAttribute('x-data'); // Alpine.js forms
            
            // If it's a GET form (causes navigation) and not Livewire, cleanup
            if (!isLivewireForm && method === 'get' && action && !action.startsWith('#')) {
                // Cleanup connections immediately before form submission
                cleanupAllConnections();
            }
        }
    }
}, { capture: false, passive: true });

// Check for active exports on page load (inside Livewire context)
checkAndReconnectExports();

// Update queue badge on page load (with delay to ensure DOM is ready)
setTimeout(() => {
    updateQueueBadge();
}, 100);

// Process queue on page load (in case an export completed while user was away)
setTimeout(() => {
    processNextInQueue();
}, 2000);

// Also check after Livewire navigation completes
document.addEventListener('livewire:navigated', () => {
    // Small delay to ensure DOM is updated after navigation
    setTimeout(() => {
        checkAndReconnectExports();
        // Update progress bar visibility based on current page vs start page
        if (typeof window.showExportProgress === 'function') {
            window.showExportProgress();
        }
        // Update queue badge after navigation
        updateQueueBadge();
        // Update toast position after navigation
        updateFlashMessagePosition();
    }, 150);
});

// Also check on regular page load (for non-Livewire navigation)
window.addEventListener('load', () => {
    setTimeout(() => {
        checkAndReconnectExports();
        updateQueueBadge();
        updateFlashMessagePosition(); // Update toast position on page load
    }, 100);
});

// Update toast position on window resize (handles responsive changes)
window.addEventListener('resize', () => {
    updateFlashMessagePosition();
});

// Also check on DOMContentLoaded for pages that might not have Livewire yet
// This ensures the progress bar shows immediately on any page navigation
(function() {
    function initExportCheck() {
        // Check if progress bar element exists
        const progressBarDiv = document.getElementById('progressBarDiv');
        if (!progressBarDiv) {
            // Progress bar not in DOM yet, try again after a short delay
            setTimeout(initExportCheck, 100);
            return;
        }
        
        // Check for active exports and show progress bar if needed
        try {
            const activeExports = JSON.parse(localStorage.getItem('export_active_list') || '[]');
            if (activeExports.length > 0) {
                const firstExportId = activeExports[0];
                const firstExportData = localStorage.getItem('export_active_' + firstExportId);
                
                if (firstExportData) {
                    try {
                        const data = JSON.parse(firstExportData);
                        const percentage = data.percentage || 0;
                        // Show progress bar immediately with stored progress (only if export is in progress)
                        if (typeof window.showExportProgress === 'function') {
                            window.showExportProgress();
                            
                            // Add cancel button and preview button if export is in progress
                            if ((data.status === 'processing' || data.status === 'starting') && typeof addCancelButton === 'function') {
                                addCancelButton(firstExportId);
                                if (typeof addDownloadCurrentButton === 'function') {
                                    addDownloadCurrentButton(firstExportId);
                                }
                            }
                            
                            if (typeof window.updateProgressBars === 'function') {
                                const exportType = data.exportType || 'user';
                                const exportTypeName = formatExportTypeName(exportType);
                                window.updateProgressBars(percentage, `Checking ${exportTypeName} export status...`);
                            }
                        }
                    } catch (e) {
                        // Invalid data, will be cleaned up by checkAndReconnectExports
                        // Hide progress bars if data is invalid
                        if (typeof window.showExportProgress === 'function') {
                            window.showExportProgress(); // This will hide them since no valid active exports
                        }
                    }
                } else {
                    // No export data, hide progress bars
                    if (typeof window.showExportProgress === 'function') {
                        window.showExportProgress(); // This will hide them since no active exports
                    }
                }
            } else {
                // No active exports, hide progress bars
                if (typeof window.showExportProgress === 'function') {
                    window.showExportProgress(); // This will hide them since no active exports
                }
            }
        } catch (e) {
            // console.error('Error checking for active exports on page load:', e);
            // On error, hide progress bars
            if (typeof window.showExportProgress === 'function') {
                window.showExportProgress(); // This will hide them
            }
        }
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initExportCheck);
    } else {
        // DOM is already loaded
        initExportCheck();
    }
})();

// Helper function to generate unique ID
/**
 * Generates a unique export ID using timestamp and random string
 * Format: "export_[timestamp]_[random]"
 * 
 * @returns {string} Unique export identifier (e.g., "export_1234567890_abc123xyz")
 * 
 * @example
 * const exportId = generateExportId(); // Returns "export_1234567890_abc123xyz"
 */
function generateExportId() {
    return 'export_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

/**
 * Handles export completion - triggers download, shows success message, processes next in queue
 * Prevents duplicate completion handling using completedExports Set
 * Calculates and displays time taken, broadcasts to other tabs, cleans up connections
 * phpMyAdmin-style: Uses downloadUrl if provided (server-side storage), otherwise uses fileContent from memory
 * 
 * @param {string} exportId - Unique export identifier
 * @param {Object} data - Completion data object with fileContent, filename, and optional downloadUrl
 * @param {string} [data.fileContent] - CSV file content (from memory, used if downloadUrl not available)
 * @param {string} [data.filename] - Filename for download
 * @param {string} [data.downloadUrl] - Server-side download URL (phpMyAdmin-style, preferred for large files)
 * 
 * @example
 * handleExportComplete('export_123', { fileContent: '...', filename: 'users.csv' });
 * handleExportComplete('export_123', { downloadUrl: '/export/download/123', filename: 'users.csv' });
 */
function handleExportComplete(exportId, data) {
    // Prevent duplicate completion handling
    if (completedExports.has(exportId)) {
        return;
    }
    
    // Mark as completed immediately to prevent race conditions
    completedExports.add(exportId);
    
    const connection = exportStreamConnections.get(exportId);
    if (!connection) {
        return;
    }
    
    // phpMyAdmin-style: Prefer downloadUrl (server-side storage) for large files
    // Fallback to fileContent from memory if downloadUrl not available
    const downloadUrl = data.downloadUrl || null;
    const fileContent = downloadUrl ? null : (data.fileContent || connection.fileContent || '');
    
    // Get exportType from connection or stored data, validate it
    const exportType = connection.exportType || getExportType(exportId);
    if (!exportType || typeof exportType !== 'string' || exportType.trim() === '') {
        // console.error('Export completion error: exportType is missing or invalid for exportId:', exportId);
        // Use generic filename if exportType is missing
        const filename = data.filename || `ExportReports_` + new Date().toISOString().split('T')[0].replace(/-/g, '') + '.csv';
        downloadExportFile(exportId, fileContent, filename, 'handleExportComplete');
        removeExportFromStorage(exportId);
        return;
    }
    
    const validExportType = exportType.trim();
    const filename = data.filename || `${validExportType}Reports_` + new Date().toISOString().split('T')[0].replace(/-/g, '') + '.csv';
    
    // Calculate total time taken
    let timeTaken = '';
    if (connection.startTime) {
        const elapsedSeconds = Math.round((Date.now() - connection.startTime) / 1000);
        const minutes = Math.floor(elapsedSeconds / 60);
        const seconds = elapsedSeconds % 60;
        if (minutes > 0) {
            timeTaken = ` in ${minutes}m ${seconds}s`;
        } else {
            timeTaken = ` in ${seconds}s`;
        }
    }
    
    // Update progress bar
    const exportTypeName = formatExportTypeName(validExportType);
    updateProgressBars(100, `${exportTypeName} export completed${timeTaken}`);
    
    // Remove exportType from queue types when export completes
    // Check if there are any other exports with the same exportType still in queue
    const queue = getQueue();
    const hasOtherSameType = queue.some(item => item.exportType === validExportType);
    if (!hasOtherSameType) {
        // No other exports of this type, remove from queue types
        removeQueueType(validExportType);
    }
    
    // Update queue status - use updateQueueStatus() to ensure sync with badge
    // Show temporary completion message, then sync to actual queue count
    const queueCount = getQueueCount();
    const queueStatus = document.getElementById('queueStatus');
    
    // Success notification removed - only show errors
    if (queueStatus) {
        if (queueCount > 0) {
            // More exports in queue - show temporary message, then sync
            queueStatus.textContent = `${exportTypeName} export completed. Starting next export...`;
            // Sync after a short delay to show completion message
            setTimeout(() => {
                updateQueueStatus();
            }, 2000);
        } else {
            // No more exports in queue - show temporary message, then sync
            queueStatus.textContent = `${exportTypeName} export completed successfully!`;
            // Sync after a short delay to show completion message
            setTimeout(() => {
                updateQueueStatus();
            }, 2000);
        }
    } else {
        // If queueStatus element doesn't exist, just sync (will be called when element appears)
        setTimeout(() => {
            updateQueueStatus();
        }, 100);
    }
    
    // Auto-hide progress bar after 3 seconds if no more exports
    if (queueCount === 0) {
        setTimeout(() => {
            // Double-check no new exports started
            if (exportStreamConnections.size === 0 && getQueue().length === 0) {
                stopExportProgress();
            }
        }, 3000);
    }
    
    // Remove cancel button and preview button
    const cancelButton = document.getElementById('exportCancelButton');
    if (cancelButton) {
        cancelButton.remove();
    }
    const downloadButton = document.getElementById('exportDownloadCurrentButton');
    if (downloadButton) {
        downloadButton.remove();
    }
    
    // Close SSE connection
    if (connection.eventSource) {
        connection.eventSource.close();
    }
    
    // MEMORY CLEANUP: Free fileContent memory after download (critical for large datasets 1M+ records)
    // For 1.1M records, fileContent can be ~550MB, so we must free it immediately after use
    const fileContentSize = connection.fileContent ? connection.fileContent.length : 0;
    const fileContentMB = (fileContentSize / 1024 / 1024).toFixed(2);
    
    if (fileContentSize > 0) {
        SSEExportClientLogger.info('Freeing fileContent memory after export completion', {
            exportId,
            fileContentSizeMB: fileContentMB,
            records: connection.lastProcessed || 0
        });
    }
    
    // Remove from connections BEFORE broadcasting to prevent duplicate download
    // Explicitly clear fileContent to free memory immediately
    connection.fileContent = null; // Free memory immediately
    exportStreamConnections.delete(exportId);
    
    // Force garbage collection hint (browsers may ignore, but helps)
    if (fileContentSize > 100 * 1024 * 1024) { // If > 100MB
        // Large file - suggest garbage collection
        if (window.gc && typeof window.gc === 'function') {
            // Chrome DevTools garbage collection (if available)
            try {
                window.gc();
            } catch (e) {
                // Ignore - gc may not be available
            }
        }
    }
    
    // Remove from storage BEFORE broadcasting to prevent BroadcastChannel from downloading again
    removeExportFromStorage(exportId);
    
    // Broadcast completion to other tabs (only other tabs will download now)
    // phpMyAdmin-style: Include downloadUrl if available, otherwise fileContent
    broadcastToOtherTabs({
        type: 'export_complete',
        exportId: exportId,
        fileContent: fileContent, // Only if downloadUrl not available
        downloadUrl: downloadUrl, // Server-side file storage URL (preferred)
        filename: filename,
    });
    
    // Reset exporting state immediately (before download to speed up next export)
    if (typeof Livewire !== 'undefined') {
        Livewire.dispatch('export-completed');
    }
    
    // phpMyAdmin-style: Download from server URL if available, otherwise use fileContent
    if (downloadUrl) {
        // Server-side file storage - download directly from URL
        const downloadLink = document.createElement('a');
        downloadLink.href = downloadUrl;
        downloadLink.download = filename;
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
        SSEExportClientLogger.success('File downloaded from server storage', { exportId, downloadUrl, filename });
    } else {
        // Fallback: Download from memory (fileContent)
        downloadExportFile(exportId, fileContent, filename, 'handleExportComplete');
    }
    
    // Process next export in queue immediately (cleanup already done above)
    // Use immediate execution for fastest response
    processNextInQueue();
}

/**
 * Downloads the export file to the user's computer
 * Creates a Blob from file content and triggers browser download
 * 
 * @param {string} exportId - Unique export identifier (for logging)
 * @param {string} fileContent - CSV file content to download
 * @param {string} filename - Filename for the downloaded file
 * @param {string} [caller='unknown'] - Caller function name (for debugging)
 * 
 * @example
 * downloadExportFile('export_123', 'name,email\nJohn,john@example.com', 'users.csv', 'handleExportComplete');
 */
function downloadExportFile(exportId, fileContent, filename, caller = 'unknown') {
    try {
        if (!fileContent) {
            // console.error('No file content to download');
            return;
        }
        
        // MEMORY OPTIMIZATION: Check file size before creating Blob (for large datasets)
        const fileSize = fileContent.length;
        const fileSizeMB = (fileSize / 1024 / 1024).toFixed(2);
        
        // Log large file download
        if (fileSize > 100 * 1024 * 1024) { // > 100MB
            SSEExportClientLogger.info('Downloading large file', {
                exportId,
                fileSizeMB: fileSizeMB,
                caller
            });
        }
        
        // Create Blob efficiently - modern browsers handle large Blobs well
        // For 1.1M records (~550MB), Blob creation should work but may take a moment
        let blob;
        try {
            blob = new Blob([fileContent], { type: 'text/csv;charset=utf-8;' });
        } catch (blobError) {
            // Handle potential memory errors when creating Blob
            if (blobError.name === 'RangeError' || blobError.message.includes('Invalid')) {
                SSEExportClientLogger.error('Blob creation failed - file too large', {
                    exportId,
                    fileSizeMB: fileSizeMB,
                    error: blobError.message
                });
                showFlashMessage('error', `File is too large (${fileSizeMB}MB) for browser download. Please use filters to reduce the number of records or contact support.`);
                return;
            }
            throw blobError; // Re-throw if it's not a memory error
        }
        
        // Create object URL and trigger download
        const url = window.URL.createObjectURL(blob);
        const downloadLink = document.createElement('a');
        downloadLink.href = url;
        downloadLink.download = filename;
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
        
        // Clean up immediately after click (don't wait for download to complete)
        // Revoke URL after a short delay to ensure download starts
        setTimeout(() => {
            document.body.removeChild(downloadLink);
            window.URL.revokeObjectURL(url);
            
            // For large files, suggest garbage collection (browsers may ignore)
            if (fileSize > 100 * 1024 * 1024 && window.gc && typeof window.gc === 'function') {
                try {
                    window.gc();
                } catch (e) {
                    // Ignore - gc may not be available
                }
            }
        }, 100);
        
        SSEExportClientLogger.success('File download initiated', {
            exportId,
            filename,
            fileSizeMB: fileSizeMB,
            caller
        });
    } catch (err) {
        SSEExportClientLogger.error('Error downloading export file', {
            exportId,
            error: err.message,
            caller
        });
        showFlashMessage('error', 'Unable to download the export file. Please try again later.');
    }
}

// Start SSE streaming export (new direct streaming method - supports multiple concurrent exports)
Livewire.on('startExportStreamSSE', (eventData) => {
    try {
        // Livewire v3 passes data as array, so handle both string and array
        let eventObj;
        if (typeof eventData === 'string') {
            eventObj = JSON.parse(eventData);
        } else if (Array.isArray(eventData) && eventData.length > 0) {
            eventObj = typeof eventData[0] === 'string' ? JSON.parse(eventData[0]) : eventData[0];
        } else {
            eventObj = eventData;
        }
        const exportId = eventObj.exportId || generateExportId();
        
        // Validate exportType - it's required and should not default to 'user'
        if (!eventObj.exportType || typeof eventObj.exportType !== 'string' || eventObj.exportType.trim() === '') {
            // console.error('Export error: exportType is missing or invalid', eventObj);
            showFlashMessage('error', 'Something went wrong with the export. Please refresh the page and try again.');
            // Reset exporting state
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('export-error');
            }
            return;
        }
        
        const exportType = eventObj.exportType.trim();
        
        if (!eventObj.total || eventObj.total === 0) {
            showFlashMessage('error', 'No records found to export');
            // Reset exporting state
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('export-error');
            }
            return;
        }
        
        
        // Check if total record count exceeds the maximum limit
        const totalRecords = parseInt(eventObj.total) || 0;
        if (totalRecords > EXPORT_MAX_RECORD_LIMIT) {

            // Get error message from eventObj if provided (from backend), otherwise use default
            const errorMessage = 'Record count is more than '+ EXPORT_MAX_RECORD_LIMIT +'. Please apply filters to reduce the number of records before exporting.';
            
            showFlashMessage('error', errorMessage);
            
            // Reset exporting state
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('export-error');
            }
            return; // Prevent export from starting
        }

        // PERFORMANCE OPTIMIZATION: Fast duplicate check (optimized for instant response)
        // Check queue types first (fast - single localStorage read)
        const queueTypes = getQueueTypes();
        const isInQueue = queueTypes.includes(exportType);
        
        // Fast check: If in queue, return immediately (no need to check active exports)
        if (isInQueue) {
            const exportTypeName = formatExportTypeName(exportType);
            showFlashMessage('info', `A ${exportTypeName} export is already in queue. Please wait for it to complete.`);
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('export-error');
            }
            return; // Instant return - no further processing
        }
        
        // ULTRA-FAST check: Check if same exportType is active (localStorage only - no connection checks)
        // This ensures instant response even during reconnection
        let isActiveSameType = false;
        try {
            const activeExports = JSON.parse(localStorage.getItem(STORAGE_EXPORT_LIST) || '[]');
            if (activeExports.length > 0) {
                // Check localStorage first (fast, synchronous, no connection state checks)
                for (const expId of activeExports) {
                    const expData = localStorage.getItem(STORAGE_KEY_PREFIX + expId);
                    if (expData) {
                        try {
                            const data = JSON.parse(expData);
                            // Check status and exportType (no connection state check - that's slow during reconnection)
                            if ((data.status === 'processing' || data.status === 'starting') && data.exportType === exportType) {
                                isActiveSameType = true;
                                break; // Found match, no need to check more
                            }
                        } catch (e) {
                            // Ignore parse errors, continue checking
                        }
                    }
                }
            }
        } catch (e) {
            // On error, check connections as fallback (slower but more accurate)
            for (const [expId, conn] of exportStreamConnections.entries()) {
                if (conn && conn.exportType === exportType && conn.eventSource && conn.eventSource.readyState !== 2) {
                    isActiveSameType = true;
                    break;
                }
            }
        }
        
        if (isActiveSameType) {
            const exportTypeName = formatExportTypeName(exportType);
            showFlashMessage('warning', `A ${exportTypeName} export is already in progress. Please wait for it to complete.`);
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('export-error');
            }
            return; // Instant return - no further processing
        }

        // Check if queue is enabled and if there's an active export (any type)
        const useQueue = EXPORT_USE_QUEUE;
        
        if (useQueue) {
            // ULTRA-FAST check: Only check localStorage (no connection state checks)
            // This ensures instant response even during reconnection
            let hasActive = false;
            try {
                const activeExports = JSON.parse(localStorage.getItem(STORAGE_EXPORT_LIST) || '[]');
                if (activeExports.length > 0) {
                    // Quick check: Just see if any export has processing/starting status
                    // Don't check connection state - that's slow during reconnection
                    for (let i = 0; i < activeExports.length && i < 5; i++) { // Limit to 5 for speed
                        const expId = activeExports[i];
                        const expData = localStorage.getItem(STORAGE_KEY_PREFIX + expId);
                        if (expData) {
                            try {
                                const data = JSON.parse(expData);
                                if (data.status === 'processing' || data.status === 'starting') {
                                    hasActive = true;
                                    break; // Found active export, no need to check more
                                }
                            } catch (e) {
                                // If we can't parse, assume active (safe default)
                                hasActive = true;
                                break;
                            }
                        }
                    }
                }
            } catch (e) {
                // On error, fallback to hasActiveExport (slower but accurate)
                hasActive = hasActiveExport();
            }
            
            if (hasActive) {
                // Queue is enabled and there's an active export - add to queue INSTANTLY
                const queueData = {
                    exportId: exportId,
                    exportType: exportType,
                    eventObj: eventObj
                };
                
                addToQueue(queueData);
                
                // Get queue info (optimized - single read)
                const queue = getQueue();
                const queuePosition = queue.findIndex(item => item.exportId === exportId) + 1;
                const exportTypeName = formatExportTypeName(exportType);
                
                // Show notification IMMEDIATELY (synchronous for instant feedback)
                const queueMessage = queuePosition === 1 
                    ? `${exportTypeName} export has been queued (next in line). It will start automatically after the current export completes.`
                    : `${exportTypeName} export has been queued (position ${queuePosition} of ${queue.length}). It will start automatically after previous exports complete.`;
                
                // Show message immediately (no setTimeout - instant feedback)
                showFlashMessage('info', queueMessage);
                
                // Sync queue status immediately (no setTimeout - instant update)
                updateQueueStatus();
                
                // Reset exporting state immediately
                if (typeof Livewire !== 'undefined') {
                    Livewire.dispatch('export-error');
                }
                
                // Return immediately - queue processing happens in background
                return;
            }
        } else {
            // Queue is disabled - ULTRA-FAST check: Only localStorage (no connection checks)
            let hasActive = false;
            try {
                const activeExports = JSON.parse(localStorage.getItem(STORAGE_EXPORT_LIST) || '[]');
                if (activeExports.length > 0) {
                    // Quick check: Just see if any export has processing/starting status
                    for (let i = 0; i < activeExports.length && i < 5; i++) { // Limit to 5 for speed
                        const expId = activeExports[i];
                        const expData = localStorage.getItem(STORAGE_KEY_PREFIX + expId);
                        if (expData) {
                            try {
                                const data = JSON.parse(expData);
                                if (data.status === 'processing' || data.status === 'starting') {
                                    hasActive = true;
                                    break;
                                }
                            } catch (e) {
                                hasActive = true;
                                break;
                            }
                        }
                    }
                }
            } catch (e) {
                // On error, fallback to hasActiveExport
                hasActive = hasActiveExport();
            }
            
            if (hasActive) {
                const exportTypeName = formatExportTypeName(exportType);
                showFlashMessage('warning', `1 export is in progress. Please wait for it to complete before starting a new export.`);
                if (typeof Livewire !== 'undefined') {
                    Livewire.dispatch('export-error');
                }
                return; // Instant return
            }
        }
        
        // Start export immediately (queue disabled or no active export)
        startExportProcess(eventObj, exportId, exportType);
    } catch (err) {
        // console.error('Error starting export:', err);
        showFlashMessage('error', 'Unable to start the export. Please try again later.');
        // Reset exporting state
        if (typeof Livewire !== 'undefined') {
            Livewire.dispatch('export-error');
        }
    }
});

/**
 * Starts the actual export process by establishing SSE connection
 * Initializes connection data, stores export in localStorage, shows progress bar
 * Creates EventSource connection and sets up event handlers
 * 
 * @param {Object} eventObj - Export event object with filters, search, total, etc.
 * @param {string} exportId - Unique export identifier
 * @param {string} exportType - Type of export (e.g., "user", "role")
 * 
 * @example
 * startExportProcess({ filters: {}, total: 1000, ... }, 'export_123', 'user');
 */
function startExportProcess(eventObj, exportId, exportType) {
    try {
        // Close existing connection for this export if any
        if (exportStreamConnections.has(exportId)) {
            const existingConnection = exportStreamConnections.get(exportId);
            if (existingConnection.eventSource) {
                existingConnection.eventSource.close();
            }
        }

        // Get startPage from eventObj (sent from Table.php, e.g., 'role', 'users')
        const startPage = eventObj.startPage || null;
        const startTime = Date.now();
        
        // Initialize connection data for this export with time tracking
        exportStreamConnections.set(exportId, {
            eventSource: null,
            fileContent: '',
            exportType: exportType,
            startTime: startTime,
            lastUpdateTime: startTime,
            lastProcessed: 0,
            errorNotified: false,
        });
        
        // Store export in localStorage for reconnection
        // CRITICAL: Create deep copies to prevent filter changes from affecting ongoing export
        const exportDataToStore = {
            exportId: exportId,
            exportType: exportType,
            filters: JSON.parse(JSON.stringify(eventObj.filters || {})), // Deep copy filters
            checkboxValues: JSON.parse(JSON.stringify(eventObj.checkboxValues || [])), // Deep copy checkboxValues
            search: String(eventObj.search || ''), // Create string copy
            chunkSize: eventObj.chunkSize || 100,
            total: eventObj.total || 0,
            processed: 0,
            percentage: 0,
            status: 'starting',
            startPage: startPage, // Use startPage value sent from Table.php
            startTime: startTime, // Store start time for ETA calculation
        };
        
        storeExportInStorage(exportId, exportDataToStore);

        // Show progress bar (for single export, we use the main progress bar)
        // For multiple exports, you could create separate progress bars
        const progressBarDiv = document.getElementById('progressBarDiv');
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const waitingMessage = document.getElementById('waitingMessage');
        
        if (!progressBarDiv || !progressBar || !progressText || !waitingMessage) {
            showFlashMessage('error', 'Something went wrong. Please refresh the page and try again.');
            // Reset exporting state
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('export-error');
            }
            return;
        }
        
        showExportProgress();
        const exportTypeName = formatExportTypeName(exportType);
        
        // Check queue status (use getQueueCount() to match badge)
        const queueCount = getQueueCount();
        
        let initialMessage = `Preparing ${exportTypeName} export...`;
        if (queueCount > 0) {
            initialMessage += ` (${queueCount} in queue)`;
        }
        
        updateProgressBars(0, initialMessage);
        
        // Sync queue status message (use requestAnimationFrame to ensure localStorage is updated)
        requestAnimationFrame(() => {
            updateQueueStatus();
        });
        
        // Update queue badge
        updateQueueBadge();

        // Add cancel button and download current button to progress bar
        addCancelButton(exportId);
        addDownloadCurrentButton(exportId);

        // Build SSE URL with export parameters
        // Use the stored deep copies from localStorage to ensure consistency
        const storedExportData = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
        let filtersToUse = eventObj.filters || {};
        let checkboxValuesToUse = eventObj.checkboxValues || [];
        let searchToUse = eventObj.search || '';
        
        if (storedExportData) {
            try {
                const parsedData = JSON.parse(storedExportData);
                filtersToUse = parsedData.filters || {};
                checkboxValuesToUse = parsedData.checkboxValues || [];
                searchToUse = parsedData.search || '';
            } catch (e) {
                // console.warn('[Export] Could not parse stored export data, using original');
            }
        }
        
        const sseUrl = `/export-stream/stream?exportType=${encodeURIComponent(exportType)}&exportId=${encodeURIComponent(exportId)}&filters=${encodeURIComponent(JSON.stringify(filtersToUse))}&checkboxValues=${encodeURIComponent(JSON.stringify(checkboxValuesToUse))}&search=${encodeURIComponent(searchToUse)}&chunkSize=${eventObj.chunkSize || 100}`;

        // Create persistent EventSource connection
        const eventSource = new EventSource(sseUrl);
        const connection = exportStreamConnections.get(exportId);
        connection.eventSource = eventSource;
        connection.exportType = exportType;
        connection.eventObj = eventObj;
        exportStreamConnections.set(exportId, connection);

        // Setup handlers
        setupEventSourceHandlers(eventSource, exportId, connection);
    } catch (err) {
        // console.error('Error in startExportProcess:', err);
        showFlashMessage('error', 'Unable to start the export. Please try again later.');
        // Reset exporting state
        if (typeof Livewire !== 'undefined') {
            Livewire.dispatch('export-error');
        }
    }
}

// Smart reconnection: Automatically disconnect when ANY Livewire request is made
// This ensures ALL future features work without manual configuration
/**
 * Sets up smart reconnection system for SSE exports
 * Uses click-based detection to temporarily disconnect SSE on user interaction
 * 
 * @example
 * setupSmartReconnection(); // Initializes smart reconnection system
 */
function setupSmartReconnection() {
    // Use click-based detection - more reliable and doesn't interfere with page load
    setupClickBasedReconnection();
}

// Global flag to prevent multiple listeners
let clickListenerAdded = false;

/**
 * Sets up click-based reconnection detection
 * Temporarily disconnects SSE connections on page clicks to prevent conflicts
 * Automatically reconnects after a delay if export is still active
 * 
 * @example
 * setupClickBasedReconnection(); // Sets up click-based reconnection
 */
function setupClickBasedReconnection() {
    // Prevent adding multiple listeners
    if (clickListenerAdded) {
        // console.log('[Export] Click listener already active');
        return;
    }
    
    clickListenerAdded = true;
    
    // More aggressive approach: Disconnect on ANY click in the page
    document.addEventListener('click', function(e) {
        if (exportStreamConnections.size === 0) return;
        
        // Ignore clicks on the export progress bar itself
        if (e.target.closest('#progressBarDiv') || e.target.closest('#exportCancelButton')) {
            return;
        }
        
        // console.log('[Export] Click detected, checking if SSE should disconnect');
        
        exportStreamConnections.forEach((connection, exportId) => {
            if (connection && connection.eventSource && connection.eventSource.readyState === EventSource.OPEN && !connection.isTemporarilyDisconnected) {
                // console.log('[Export] Temporarily closing SSE for exportId:', exportId);
                connection.isTemporarilyDisconnected = true;
                connection.eventSource.close();
                
                setTimeout(() => {
                    const exportData = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
                    if (exportData) {
                        try {
                            const data = JSON.parse(exportData);
                            if (data.status === 'processing') {
                                // console.log('[Export] Reconnecting SSE for exportId:', exportId);
                                connection.isTemporarilyDisconnected = false;
                                reconnectToExport(exportId, data);
                            }
                        } catch (e) {
                            // console.error('[Export] Error reconnecting:', e);
                        }
                    }
                }, 2000);
            }
        });
    }, true); // Use capture phase to catch events early
    
    // console.log('[Export] Click-based reconnection listener added');
}

// Initialize smart reconnection after page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setupSmartReconnection);
} else {
    setupSmartReconnection();
}

// Re-initialize after Livewire navigation (SPA mode)
document.addEventListener('livewire:navigated', () => {
    // console.log('[Export] Page navigated via Livewire');
    // Click listener is global, no need to re-add
    // Just log that we're on a new page
});

// Also listen for older Livewire navigation events for compatibility
document.addEventListener('livewire:load', () => {
    // console.log('[Export] Livewire loaded');
    // Click listener is global, no need to re-add
});

/**
 * Adds a cancel button to the progress bar
 * Creates the button if it doesn't exist and attaches click handler to cancelExport
 * 
 * @param {string} exportId - Unique export identifier to cancel
 * 
 * @example
 * addCancelButton('export_123'); // Adds cancel button to progress bar
 */
function addCancelButton(exportId) {
    const progressBarDiv = document.getElementById('progressBarDiv');
    if (!progressBarDiv) return;

    // Check if cancel button already exists
    let cancelButton = document.getElementById('exportCancelButton');
    if (!cancelButton) {
        cancelButton = document.createElement('button');
        cancelButton.id = 'exportCancelButton';
        cancelButton.type = 'button';
        cancelButton.className = 'ml-3 inline-flex items-center px-3 py-1.5 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded-md hover:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors dark:bg-red-900 dark:text-red-200 dark:border-red-700 dark:hover:bg-red-800';
        cancelButton.innerHTML = `
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Cancel
        `;
        
        // Find the progress text container and add cancel button
        const progressTextContainer = document.getElementById('progressTextContainer');
        if (progressTextContainer) {
            // Add cancel button to the right side of the container
            const rightSide = progressTextContainer.querySelector('.flex.items-center');
            if (rightSide) {
                rightSide.appendChild(cancelButton);
            } else {
                progressTextContainer.appendChild(cancelButton);
            }
        }
    }

    // Add click handler
    cancelButton.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        cancelExport(exportId);
    };
}

/**
 * Adds a download current button to the progress bar
 * Creates the button if it doesn't exist and shows/hides based on record count
 * Only displays when records > 0, updates automatically as export progresses
 * 
 * @param {string} exportId - Unique export identifier
 * 
 * @example
 * addDownloadCurrentButton('export_123'); // Adds download current button to progress bar
 */
function addDownloadCurrentButton(exportId) {
    const progressBarDiv = document.getElementById('progressBarDiv');
    if (!progressBarDiv) return;

    // Get current processed count
    const storedDataStr = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
    let processed = 0;
    if (storedDataStr) {
        try {
            const storedData = JSON.parse(storedDataStr);
            processed = storedData.processed || 0;
        } catch (e) {
            // Ignore parse errors
        }
    }

    // Check if download button already exists
    let downloadButton = document.getElementById('exportDownloadCurrentButton');
    if (!downloadButton) {
        downloadButton = document.createElement('button');
        downloadButton.id = 'exportDownloadCurrentButton';
        downloadButton.type = 'button';
        downloadButton.className = 'ml-2 inline-flex items-center px-3 py-1.5 text-xs font-medium text-blue-700 bg-blue-50 border border-blue-200 rounded-md hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors dark:bg-blue-900 dark:text-blue-200 dark:border-blue-700 dark:hover:bg-blue-800';
        
        // Find the progress text container and add download button
        const progressTextContainer = document.getElementById('progressTextContainer');
        if (progressTextContainer) {
            // Add download button to the right side of the container (before cancel button)
            const rightSide = progressTextContainer.querySelector('.flex.items-center');
            if (rightSide) {
                // Insert before cancel button if it exists
                const cancelButton = document.getElementById('exportCancelButton');
                if (cancelButton) {
                    rightSide.insertBefore(downloadButton, cancelButton);
                } else {
                    rightSide.appendChild(downloadButton);
                }
            } else {
                progressTextContainer.appendChild(downloadButton);
            }
        }
    }

    // Update button text and visibility based on processed count
    updateDownloadCurrentButton(exportId, processed);

    // Add click handler
    downloadButton.onclick = function(e) {
        e.preventDefault();
        e.stopPropagation();
        downloadCurrentData(exportId);
    };
}

/**
 * Updates the download current button text and visibility
 * Hides button if processed count is 0, shows with current count otherwise
 * 
 * @param {string} exportId - Unique export identifier
 * @param {number} processed - Current processed record count
 */
function updateDownloadCurrentButton(exportId, processed) {
    const downloadButton = document.getElementById('exportDownloadCurrentButton');
    if (!downloadButton) return;

    if (processed > 0) {
        downloadButton.innerHTML = `
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
            Download Current (${processed.toLocaleString()})
        `;
        downloadButton.style.display = '';
        downloadButton.classList.remove('hidden');
    } else {
        downloadButton.style.display = 'none';
        downloadButton.classList.add('hidden');
    }
}

/**
 * Downloads current export data while export is in progress
 * 
 * @param {string} exportId - Unique export identifier
 */
function downloadCurrentData(exportId) {
    const connection = exportStreamConnections.get(exportId);
    const storedDataStr = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
    
    // phpMyAdmin-style: fileContent is in memory only (not localStorage)
    if (!connection || !connection.fileContent) {
        showFlashMessage('error', 'No data available for download yet.');
        return;
    }
    
    try {
        const storedData = JSON.parse(storedDataStr);
        const processed = storedData.processed || 0;
        const exportType = storedData.exportType && typeof storedData.exportType === 'string' && storedData.exportType.trim() !== '' 
            ? storedData.exportType.trim() 
            : 'export';
        const exportTypeName = formatExportTypeName(exportType);
        
        if (processed === 0) {
            showFlashMessage('error', 'No records processed yet. Please wait for export to start.');
            return;
        }
        
        const blob = new Blob([connection.fileContent], { type: 'text/csv;charset=utf-8;' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        
        const timestamp = new Date().toISOString().slice(0, 19).replace(/:/g, '-');
        const filename = `${exportTypeName.toLowerCase()}_current_${processed.toLocaleString()}_records_${timestamp}.csv`;
        link.download = filename;
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
        showFlashMessage('success', `Downloaded ${processed.toLocaleString()} records!`);
        SSEExportClientLogger.success('Current export data downloaded', { exportId, filename, records: processed });
        
    } catch (error) {
        // console.error('Error downloading current data:', error);
        showFlashMessage('error', 'Error downloading current data.');
    }
}


/**
 * Shows confirmation dialog with export progress info
 * If confirmed, fully cancels the export and processes next in queue
 * If cancelled (user clicks "Continue Export"), resumes the export
 * 
 * @param {string} exportId - Unique export identifier to cancel
 * 
 * @example
 * cancelExport('export_123'); // Shows confirmation dialog and cancels if confirmed
 */
function cancelExport(exportId) {
    SSEExportClientLogger.info('Cancel export requested', { exportId });
    
    // Pause export processing by closing the EventSource connection temporarily
    const connection = exportStreamConnections.get(exportId);
    let eventSourceBackup = null;
    let connectionDataBackup = null;
    
    if (connection && connection.eventSource) {
        // Store connection data for potential resume
        connectionDataBackup = {
            fileContent: connection.fileContent || '',
            exportType: connection.exportType || 'user',
            startTime: connection.startTime || Date.now(),
            lastUpdateTime: connection.lastUpdateTime || Date.now(),
            lastProcessed: connection.lastProcessed || 0,
        };
        
        SSEExportClientLogger.debug('Pausing export connection', { 
            exportId, 
            processedRecords: connectionDataBackup.lastProcessed 
        });
        
        // Close the EventSource to pause processing
        eventSourceBackup = connection.eventSource;
        connection.eventSource.close();
        connection.eventSource = null;
        
        // Update progress message to show paused state
        const waitingMessage = document.getElementById('waitingMessage');
        if (waitingMessage) {
            waitingMessage.textContent = 'Export paused - Waiting for confirmation...';
        }
    }
    
    // Get export progress info for better messaging
    const storedDataStr = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
    let progressInfo = '';
    if (storedDataStr) {
        try {
            const storedData = JSON.parse(storedDataStr);
            const percentage = storedData.percentage || 0;
            const processed = storedData.processed || 0;
            const total = storedData.total || 0;
            const exportType = storedData.exportType && typeof storedData.exportType === 'string' && storedData.exportType.trim() !== '' 
                ? storedData.exportType.trim() 
                : null;
            const exportTypeName = exportType ? formatExportTypeName(exportType) : 'Export';
            
            if (total > 0 && processed > 0) {
                progressInfo = `<div class="mt-3 p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-300">Progress:</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">${Math.round(percentage)}%</span>
                    </div>
                    <div class="flex items-center justify-between text-sm mt-1">
                        <span class="text-gray-600 dark:text-gray-300">Records processed:</span>
                        <span class="font-semibold text-gray-900 dark:text-gray-100">${processed.toLocaleString()} of ${total.toLocaleString()}</span>
                    </div>
                </div>`;
            }
        } catch (e) {
            // Ignore parse errors
        }
    }
    
    Swal.fire({
        title: '<div class="flex items-center gap-3"><svg class="w-8 h-8 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg><span class="text-xl font-semibold text-gray-900 dark:text-gray-100">Cancel Export?</span></div>',
        html: `
            <div class="text-left space-y-3">
                <p class="text-gray-700 dark:text-gray-300 leading-relaxed">
                    Are you sure you want to cancel this export? All progress will be lost and you'll need to start over.
                </p>
                ${progressInfo}
                <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/20 border-l-4 border-amber-400 rounded">
                    <p class="text-sm text-amber-800 dark:text-amber-200 flex items-start gap-2">
                        <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>This action cannot be undone. The export will be stopped immediately.</span>
                    </p>
                </div>
            </div>
        `,
        icon: false,
        showCancelButton: true,
        confirmButtonText: '<span class="flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>Cancel Export</span>',
        cancelButtonText: '<span class="flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>Continue Export</span>',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
        focusConfirm: false,
        focusCancel: true,
        allowOutsideClick: false,
        allowEscapeKey: true,
        showClass: {
            popup: 'animate-[fadeIn_0.2s_ease-out,slideUp_0.3s_ease-out]'
        },
        hideClass: {
            popup: 'animate-[fadeOut_0.15s_ease-in,slideDown_0.2s_ease-in]'
        },
        customClass: {
            popup: 'rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 backdrop-blur-sm',
            title: '!mb-4 !text-left',
            htmlContainer: '!text-left !p-0',
            confirmButton: '!px-6 !py-2.5 !rounded-lg !font-medium !transition-all !duration-200 hover:!bg-red-700 hover:!shadow-lg !transform hover:!scale-105 active:!scale-95',
            cancelButton: '!px-6 !py-2.5 !rounded-lg !font-medium !transition-all !duration-200 hover:!bg-gray-100 dark:hover:!bg-gray-700 hover:!shadow-md !transform hover:!scale-105 active:!scale-95 !border !border-gray-300 dark:!border-gray-600 !text-gray-700 dark:!text-gray-300',
            actions: '!mt-6 !gap-3 !justify-end'
        },
        buttonsStyling: true,
        width: '500px',
        padding: '1.5rem'
    }).then((result) => {
        if (result.isConfirmed) {
            SSEExportClientLogger.warning('User confirmed export cancellation', { exportId });
            
            // User confirmed cancellation - send cancellation to backend
            fetch('/export-stream/cancel', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ exportId: exportId })
            }).then(response => {
                if (response.ok) {
                    SSEExportClientLogger.success('Cancellation request sent to backend', { exportId });
                } else {
                    SSEExportClientLogger.error('Failed to send cancellation to backend', { 
                        exportId, 
                        status: response.status 
                    });
                }
            }).catch(error => {
                SSEExportClientLogger.error('Error sending cancellation to backend', { 
                    exportId, 
                    error: error.message 
                });
            });

            // Clean up client-side immediately
            const connection = exportStreamConnections.get(exportId);
            if (connection && connection.eventSource) {
                connection.eventSource.close();
            }
            exportStreamConnections.delete(exportId);
            removeExportFromStorage(exportId);
            stopExportProgress();
            
            // Remove cancel button and preview button
            const cancelButton = document.getElementById('exportCancelButton');
            if (cancelButton) {
                cancelButton.remove();
            }
            const downloadButton = document.getElementById('exportDownloadCurrentButton');
            if (downloadButton) {
                downloadButton.remove();
            }
            
            // Reset exporting state
            if (typeof Livewire !== 'undefined') {
                Livewire.dispatch('export-error');
            }
            
            SSEExportClientLogger.info('Export cancelled and cleaned up', { exportId });
            
            // Process next export in queue immediately
            processNextInQueue();
        
        } else {
            SSEExportClientLogger.info('User chose to continue export', { exportId });
            
            // User clicked "Continue Export" - resume the export
            const currentConnection = exportStreamConnections.get(exportId);
            if (connectionDataBackup && currentConnection) {
                // Restore connection data
                currentConnection.fileContent = connectionDataBackup.fileContent;
                currentConnection.exportType = connectionDataBackup.exportType;
                currentConnection.startTime = connectionDataBackup.startTime;
                currentConnection.lastUpdateTime = connectionDataBackup.lastUpdateTime;
                currentConnection.lastProcessed = connectionDataBackup.lastProcessed;
                
                // Get stored export parameters
                const storedDataStr = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
                if (storedDataStr) {
                    try {
                        const storedData = JSON.parse(storedDataStr);
                        
                        // Validate exportType before resuming
                        if (!storedData.exportType || typeof storedData.exportType !== 'string' || storedData.exportType.trim() === '') {
                            // console.error('Resume error: exportType is missing or invalid in stored data for exportId:', exportId);
                            showFlashMessage('error', 'Unable to resume the export. Please start a new export.');
                            // Clean up corrupted export
                            exportStreamConnections.delete(exportId);
                            removeExportFromStorage(exportId);
                            stopExportProgress();
                            if (typeof Livewire !== 'undefined') {
                                Livewire.dispatch('export-error');
                            }
                            return;
                        }
                        
                        const exportType = storedData.exportType.trim();

                        SSEExportClientLogger.info('Resuming export after pause', { 
                            exportId, 
                            exportType,
                            resumeFromRecord: currentConnection.lastProcessed 
                        });

                        // Reconnect to SSE stream with current progress
                        const sseUrl = `/export-stream/stream?exportType=${encodeURIComponent(exportType)}&exportId=${encodeURIComponent(exportId)}&reconnect=true&processed=${encodeURIComponent(currentConnection.lastProcessed || storedData.processed || 0)}&filters=${encodeURIComponent(JSON.stringify(storedData.filters || {}))}&checkboxValues=${encodeURIComponent(JSON.stringify(storedData.checkboxValues || []))}&search=${encodeURIComponent(storedData.search || '')}&chunkSize=${storedData.chunkSize || 100}`;
                        
                        // Create new EventSource connection
                        const eventSource = new EventSource(sseUrl);
                        currentConnection.eventSource = eventSource;
                        exportStreamConnections.set(exportId, currentConnection);
                        
                        // Update progress message
                        const waitingMessage = document.getElementById('waitingMessage');
                        if (waitingMessage) {
                            const exportTypeName = formatExportTypeName(exportType);
                            waitingMessage.textContent = `Resuming ${exportTypeName} export...`;
                        }
                        
                        // Setup handlers
                        setupEventSourceHandlers(eventSource, exportId, currentConnection);
                    } catch (e) {
                        // console.error('Error resuming export:', e);
                        showFlashMessage('error', 'Unable to resume the export. Please try again later.');
                        // Reset exporting state on error
                        if (typeof Livewire !== 'undefined') {
                            Livewire.dispatch('export-error');
                        }
                    }
                }
            }
        }
    });
}

/**
 * Sets up EventSource handlers for SSE export streaming
 * Handles connection events: connected, header, progress, data, complete, error
 * Updates progress bars, calculates ETA, stores file content, triggers download on completion
 * Shared function used for both new exports and reconnections
 * 
 * @param {EventSource} eventSource - The EventSource connection object
 * @param {string} exportId - Unique export identifier
 * @param {Object} connection - Connection object with fileContent, exportType, startTime, etc.
 * 
 * @example
 * setupEventSourceHandlers(eventSource, 'export_123', { exportType: 'user', ... });
 */
const setupEventSourceHandlers = (eventSource, exportId, connection) => {
    SSEExportClientLogger.info('Setting up SSE event handlers', { exportId });
    
    // Handle connection opened
    eventSource.onopen = function(event) {
        SSEExportClientLogger.success('SSE connection opened', { exportId });
        
        // Reset per-connection error notification so a fresh connection can alert once if it fails
        const currentConnection = exportStreamConnections.get(exportId);
        if (currentConnection) {
            currentConnection.errorNotified = false;
            exportStreamConnections.set(exportId, currentConnection);
        }
    };

    // Handle incoming messages
    eventSource.onmessage = function(event) {
        try {
            const data = JSON.parse(event.data);
            
            // Verify this message is for our export
            if (data.exportId && data.exportId !== exportId) {
                return; // Ignore messages for other exports
            }
            
            const currentConnection = exportStreamConnections.get(exportId);
            if (!currentConnection) {
                return;
            }
            
            // SSEExportClientLogger.debug('SSE message received', { 
            //     exportId, 
            //     status: data.status,
            //     processed: data.processed,
            //     percentage: data.percentage 
            // });
            
            if (data.status === 'connected') {
                const waitingMsg = document.getElementById('waitingMessage');
                if (waitingMsg) {
                    const exportType = getExportType(exportId);
                    const exportTypeName = formatExportTypeName(exportType);
                    waitingMsg.textContent = `Preparing ${exportTypeName} export...`;
                }
                
                // Update localStorage
                updateExportInStorage(exportId, {
                    status: 'processing',
                    total: data.total || 0,
                });
            } else if (data.status === 'header') {
                SSEExportClientLogger.info('CSV header received', { exportId });
                
                // CRITICAL FIX: Only set header if fileContent is empty (new export)
                // On reconnection, fileContent already has header + data, so don't replace it
                // This prevents data loss when reconnecting after navigation
                if (!currentConnection.fileContent || currentConnection.fileContent.trim() === '') {
                    // New export - set header
                    currentConnection.fileContent = data.content;
                    SSEExportClientLogger.info('CSV header set for new export', { exportId });
                } else {
                    // Reconnection - fileContent already exists with header + data
                    // Don't replace it, just log that header was received (but ignored)
                    SSEExportClientLogger.info('CSV header received on reconnection - preserving existing fileContent', {
                        exportId,
                        existingFileContentLength: currentConnection.fileContent.length
                    });
                }
                
                // Update localStorage (metadata only, NO fileContent)
                updateExportInStorage(exportId, {
                    status: 'processing',
                    // NO fileContent - kept in memory only
                });
            } else if (data.status === 'progress') {
                    // Progress update without data (for smoother progress bar)
                    const rawTargetProgress = data.percentage || 0;
                    const targetProgress = Math.round(rawTargetProgress);
                    
                    // Get stored progress to ensure we don't go backwards
                    const storedDataStr = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
                    let storedPercentage = 0;
                    let totalRecords = 0;
                    if (storedDataStr) {
                        try {
                            const storedData = JSON.parse(storedDataStr);
                            storedPercentage = storedData.percentage || 0;
                            totalRecords = storedData.total || 0;
                        } catch (e) {
                            // Ignore parse errors
                        }
                    }
                    
                    // Use the maximum of target and stored to prevent resetting
                    const finalProgress = Math.max(targetProgress, storedPercentage);
                    
                    const progressBar = document.getElementById('progressBar');
                    const progressText = document.getElementById('progressText');
                    const waitingMessage = document.getElementById('waitingMessage');
                    
                    if (!progressBar || !progressText || !waitingMessage) {
                        return;
                    }
                    
                    // Calculate ETA
                    const connection = exportStreamConnections.get(exportId);
                    let etaSeconds = null;
                    if (connection && connection.startTime && data.processed && totalRecords > 0) {
                        const currentTime = Date.now();
                        const elapsedTime = (currentTime - connection.startTime) / 1000; // seconds
                        const processed = data.processed || 0;
                        
                        if (processed > 0 && processed < totalRecords) {
                            const recordsPerSecond = processed / elapsedTime;
                            const remainingRecords = totalRecords - processed;
                            etaSeconds = Math.ceil(remainingRecords / recordsPerSecond);
                            
                            // Update connection tracking
                            connection.lastUpdateTime = currentTime;
                            connection.lastProcessed = processed;
                        }
                    }
                    
                    // Cancel any existing animation for this export
                    if (activeAnimations.has(exportId)) {
                        const existingAnimation = activeAnimations.get(exportId);
                        if (existingAnimation.animationFrame) {
                            cancelAnimationFrame(existingAnimation.animationFrame);
                        }
                    }
                    
                    const currentProgress = parseFloat(progressBar.style.width) || storedPercentage;
                    
                    // Create informative message
                    const exportType = getExportType(exportId);
                    const exportTypeName = formatExportTypeName(exportType);
                    const processed = data.processed || 0;
                    const total = data.total || totalRecords || 0;
                    const progressMessage = `Exporting ${exportTypeName}... ${processed.toLocaleString()} of ${total.toLocaleString()} records`;
                    
                    // Animate progress bar smoothly (only if final progress is higher)
                    if (finalProgress > currentProgress) {
                        const startProgress = currentProgress;
                        const progressDiff = finalProgress - startProgress;
                        const duration = Math.min(800, Math.max(150, progressDiff * 8)); // Faster animation
                        const startTime = Date.now();
                        
                        const animateProgress = () => {
                            const elapsed = Date.now() - startTime;
                            const progress = Math.min(1, elapsed / duration);
                            const easeOutQuad = 1 - (1 - progress) * (1 - progress);
                            const currentValue = startProgress + (progressDiff * easeOutQuad);
                            const roundedValue = Math.round(currentValue);
                            
                            updateProgressBars(currentValue, progressMessage, etaSeconds);
                            
                            if (progress < 1) {
                                const frameId = requestAnimationFrame(animateProgress);
                                activeAnimations.set(exportId, { animationFrame: frameId, targetProgress: finalProgress });
                            } else {
                                // Animation complete
                                updateProgressBars(finalProgress, progressMessage, etaSeconds);
                                activeAnimations.delete(exportId);
                            }
                        };
                        
                        const frameId = requestAnimationFrame(animateProgress);
                        activeAnimations.set(exportId, { animationFrame: frameId, targetProgress: finalProgress });
                    } else {
                        // Update immediately if no animation needed (but only if progress increased)
                        if (finalProgress > currentProgress) {
                            updateProgressBars(finalProgress, progressMessage, etaSeconds);
                        }
                    }
                    
                    // Broadcast progress to other tabs
                    broadcastToOtherTabs({
                        type: 'export_progress',
                        exportId: exportId,
                        percentage: finalProgress,
                    });
                    
                    // CRITICAL FIX: Do NOT update processed count on progress events
                    // Only update it on 'data' events when actual data is written
                    // This prevents the processed count from getting ahead of the actual file content
                    // which causes records to be skipped on reconnection
                    
                    // Only update percentage, not processed count
                    updateExportInStorage(exportId, {
                        percentage: finalProgress,
                    });
                } else if (data.status === 'data') {
                    // SSEExportClientLogger.debug('Data chunk received', { 
                    //     exportId, 
                    //     processed: data.processed,
                    //     percentage: data.percentage,
                    //     chunk: data.chunk 
                    // });
                    
                    // MEMORY OPTIMIZATION: Efficiently append chunk data to file content
                    // For large datasets (1M+ records), use efficient string concatenation
                    // Modern JS engines optimize += for strings, but we monitor memory usage
                    try {
                        currentConnection.fileContent += data.content;
                        
                        // Memory monitoring for large datasets (warn if approaching limits)
                        // Estimate: ~500 bytes per record, so 1.1M records ≈ 550MB
                        const fileContentSize = currentConnection.fileContent.length;
                        const estimatedMB = (fileContentSize / 1024 / 1024).toFixed(2);
                        
                        // Log memory usage periodically (every 100K records or 50MB)
                        if (data.processed && data.processed % 100000 === 0) {
                            SSEExportClientLogger.info('Large dataset export progress', {
                                exportId,
                                processed: data.processed,
                                fileContentSizeMB: estimatedMB,
                                memoryWarning: fileContentSize > 500 * 1024 * 1024 ? 'Approaching 500MB' : 'OK'
                            });
                        }
                    } catch (e) {
                        // Handle potential memory errors gracefully
                        if (e.name === 'RangeError' || e.message.includes('Invalid string length')) {
                            SSEExportClientLogger.error('Memory limit reached for fileContent', {
                                exportId,
                                fileContentSize: currentConnection.fileContent ? currentConnection.fileContent.length : 0,
                                error: e.message
                            });
                            showFlashMessage('error', 'Export file is too large for browser memory. Please use filters to reduce the number of records.');
                            
                            // Close connection and cleanup
                            if (currentConnection.eventSource) {
                                currentConnection.eventSource.close();
                            }
                            exportStreamConnections.delete(exportId);
                            removeExportFromStorage(exportId);
                            stopExportProgress();
                            
                            if (typeof Livewire !== 'undefined') {
                                Livewire.dispatch('export-error');
                            }
                            return;
                        }
                        throw e; // Re-throw if it's not a memory error
                    }
                    
                    // Update progress bar with smooth animation
                    const rawPercentage = data.percentage || 0;
                    const rawTargetProgress = Math.round(rawPercentage);
                    
                    // Get stored progress to ensure we don't go backwards
                    const storedDataStr = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
                    let storedPercentage = 0;
                    let totalRecords = 0;
                    if (storedDataStr) {
                        try {
                            const storedData = JSON.parse(storedDataStr);
                            storedPercentage = storedData.percentage || 0;
                            totalRecords = storedData.total || 0;
                        } catch (e) {
                            // Ignore parse errors
                        }
                    }
                    
                    // Use the maximum of target and stored to prevent resetting
                    const targetProgress = Math.max(rawTargetProgress, storedPercentage);
                    
                    const progressBar = document.getElementById('progressBar');
                    if (!progressBar) {
                        return;
                    }
                    
                    // Calculate ETA
                    let etaSeconds = null;
                    if (currentConnection.startTime && data.processed && totalRecords > 0) {
                        const currentTime = Date.now();
                        const elapsedTime = (currentTime - currentConnection.startTime) / 1000; // seconds
                        const processed = data.processed || 0;
                        
                        if (processed > 0 && processed < totalRecords) {
                            const recordsPerSecond = processed / elapsedTime;
                            const remainingRecords = totalRecords - processed;
                            etaSeconds = Math.ceil(remainingRecords / recordsPerSecond);
                            
                            // Update connection tracking
                            currentConnection.lastUpdateTime = currentTime;
                            currentConnection.lastProcessed = processed;
                        }
                    }
                    
                    const currentProgress = parseFloat(progressBar.style.width) || storedPercentage;
                    
                    // Create informative message
                    const exportType = getExportType(exportId);
                    const exportTypeName = formatExportTypeName(exportType);
                    const processed = data.processed || 0;
                    const total = data.total || totalRecords || 0;
                    const progressMessage = `Exporting ${exportTypeName}... ${processed.toLocaleString()} of ${total.toLocaleString()} records`;
                    
                    // Animate progress bar smoothly
                    if (targetProgress > currentProgress) {
                        const startProgress = currentProgress;
                        const progressDiff = targetProgress - startProgress;
                        const duration = Math.min(1000, Math.max(200, progressDiff * 10));
                        const startTime = Date.now();
                        
                        const animateProgress = () => {
                            const elapsed = Date.now() - startTime;
                            const progress = Math.min(1, elapsed / duration);
                            const easeOutQuad = 1 - (1 - progress) * (1 - progress);
                            const currentValue = startProgress + (progressDiff * easeOutQuad);
                            const roundedValue = Math.round(currentValue);
                            
                            updateProgressBars(currentValue, progressMessage, etaSeconds);
                            
                            if (progress < 1) {
                                requestAnimationFrame(animateProgress);
                            } else {
                                updateProgressBars(targetProgress, progressMessage, etaSeconds);
                            }
                        };
                        
                        requestAnimationFrame(animateProgress);
                    } else {
                        // Update immediately if no animation needed
                        updateProgressBars(targetProgress, progressMessage, etaSeconds);
                    }
                    
                    // Broadcast progress to other tabs
                    broadcastToOtherTabs({
                        type: 'export_progress',
                        exportId: exportId,
                        percentage: targetProgress,
                    });
                    
                    // Get stored processed count to ensure we don't go backwards
                    const storedDataStrForData = localStorage.getItem(STORAGE_KEY_PREFIX + exportId);
                    let storedProcessedForData = 0;
                    if (storedDataStrForData) {
                        try {
                            const storedDataForData = JSON.parse(storedDataStrForData);
                            storedProcessedForData = storedDataForData.processed || 0;
                        } catch (e) {
                            // Ignore parse errors
                        }
                    }
                    
                    // Use the maximum of server processed and stored processed (never decrease)
                    const newProcessedForData = Math.max(data.processed || 0, storedProcessedForData);
                    
                    // Update localStorage with metadata only (phpMyAdmin-style, NO fileContent)
                    updateExportInStorage(exportId, {
                        processed: newProcessedForData,
                        percentage: targetProgress,
                        // NO fileContent - kept in memory only (connection.fileContent)
                    });
                    
                    // Update download current button with new processed count
                    if (typeof updateDownloadCurrentButton === 'function') {
                        updateDownloadCurrentButton(exportId, newProcessedForData);
                    }
                } else if (data.status === 'complete') {
                    SSEExportClientLogger.success('Export completed', { 
                        exportId, 
                        processed: data.processed,
                        filename: data.filename 
                    });
                    
                    // Export completed - use shared handler
                    // phpMyAdmin-style: Use downloadUrl if provided, otherwise use fileContent from memory
                    handleExportComplete(exportId, {
                        fileContent: data.fileContent || currentConnection.fileContent,
                        filename: data.filename,
                        downloadUrl: data.downloadUrl, // Server-side file storage URL (if available)
                    });
                } else if (data.status === 'cancelled') {
                    SSEExportClientLogger.warning('Export cancelled by backend', { exportId });
                    
                    // Export was cancelled by backend
                    if (currentConnection.eventSource) {
                        currentConnection.eventSource.close();
                    }
                    exportStreamConnections.delete(exportId);
                    removeExportFromStorage(exportId);
                    stopExportProgress();
                    
                    // Remove cancel button
                    const cancelButton = document.getElementById('exportCancelButton');
                    if (cancelButton) {
                        cancelButton.remove();
                    }
                    
                    // Reset exporting state
                    if (typeof Livewire !== 'undefined') {
                        Livewire.dispatch('export-error');
                    }
                    
                    showFlashMessage('info', 'Export was cancelled successfully.');
                    
                    // Process next export in queue immediately
                    processNextInQueue();
                } else if (data.status === 'error') {
                    SSEExportClientLogger.error('Export error received', { 
                        exportId, 
                        message: data.message 
                    });
                    
                    // Error occurred
                    if (currentConnection.eventSource) {
                        currentConnection.eventSource.close();
                    }
                    exportStreamConnections.delete(exportId);
                    removeExportFromStorage(exportId);
                    stopExportProgress();
                    
                    // Remove cancel button
                    const cancelButton = document.getElementById('exportCancelButton');
                    if (cancelButton) {
                        cancelButton.remove();
                    }
                    
                    // Reset exporting state
                    if (typeof Livewire !== 'undefined') {
                        Livewire.dispatch('export-error');
                    }
                    
                    showFlashMessage('error', data.message || 'Something went wrong during the export. Please try again later.');
                    
                    // Process next export in queue immediately (cleanup happens in background)
                    processNextInQueue();
                }
            } catch (err) {
                SSEExportClientLogger.error('Error parsing SSE message', { 
                    exportId, 
                    error: err.message,
                    rawData: event.data 
                });
                // console.error('Error parsing SSE message:', err);
            }
        };

        // Handle errors
        eventSource.onerror = function(event) {
            SSEExportClientLogger.error('SSE connection error', { 
                exportId,
                readyState: eventSource.readyState,
                url: eventSource.url 
            });
            
            try {
                const currentConnection = exportStreamConnections.get(exportId);
                
                if (currentConnection && currentConnection.eventSource) {
                    try {
                        currentConnection.eventSource.close();
                    } catch (e) {
                        // console.warn('Error closing EventSource on error:', e);
                    }
                }
                
                // Throttle user-facing error to once per connection attempt
                if (currentConnection && !currentConnection.errorNotified && !document.hidden) {
                    try {
                        showFlashMessage('error', 'Connection lost. You can reconnect when you return to this page.');
                    } catch (e) {
                        // console.warn('Error showing flash message:', e);
                    }
                    currentConnection.errorNotified = true;
                    exportStreamConnections.set(exportId, currentConnection);
                }

                exportStreamConnections.delete(exportId);
                // Don't remove from storage on error - allow reconnection attempt
                stopExportProgress();
                
                // Remove cancel button
                const cancelButton = document.getElementById('exportCancelButton');
                if (cancelButton) {
                    cancelButton.remove();
                }
                
                // Reset exporting state
                if (typeof Livewire !== 'undefined') {
                    Livewire.dispatch('export-error');
                }
                
                // Process next export in queue immediately (cleanup happens in background)
                processNextInQueue();
            } catch (err) {
                // console.error('Error in EventSource error handler:', err);
                // Don't throw - ensure navigation can proceed
            }
        };
};

// Start SSE connection for export progress (legacy batch job method)
Livewire.on('startExportProgressSSE', (eventData) => {
    try {
        const eventObj = JSON.parse(eventData);
        const batchId = eventObj.batchId;
        
        if (!batchId) {
            return;
        }

        // Close existing connection if any
        if (exportProgressEventSource) {
            exportProgressEventSource.close();
        }

        // Show progress bar
        showExportProgress();
        document.getElementById('progressBar').style.width = '0%';
        document.getElementById('progressText').textContent = '0%';
        document.getElementById('waitingMessage').textContent = 'Initializing export...';

        // Build SSE URL with batch ID and function params
        const sseUrl = `/export-progress/stream?batchId=${encodeURIComponent(batchId)}&functionParams=${encodeURIComponent(eventData)}`;

        // Create EventSource connection
        exportProgressEventSource = new EventSource(sseUrl);

        // Handle connection opened
        exportProgressEventSource.onopen = function(event) {
            // Connection opened
        };

        // Handle incoming messages
        exportProgressEventSource.onmessage = function(event) {
            try {
                const data = JSON.parse(event.data);
                
                if (data.status === 'connected') {
                    // SSE connected
                } else if (data.status === 'progress') {
                    // Update progress bar with smooth animation
                    const progressData = data.data;
                    const targetProgress = progressData.exportProgress || 0;
                    const waitingMessage = progressData.waitingMessage || 'Processing...';
                    
                    // Get current progress
                    const progressBar = document.getElementById('progressBar');
                    const currentProgress = progressBar ? parseFloat(progressBar.style.width) || 0 : 0;

                    // Animate progress bar smoothly
                    if (targetProgress > currentProgress) {
                        // Animate from current to target progress
                        const startProgress = currentProgress;
                        const progressDiff = targetProgress - startProgress;
                        const duration = Math.min(1000, Math.max(200, progressDiff * 10)); // 200ms to 1000ms based on difference
                        const startTime = Date.now();
                        
                        const animateProgress = () => {
                            const elapsed = Date.now() - startTime;
                            const progress = Math.min(1, elapsed / duration);
                            
                            // Use easing function for smooth animation
                            const easeOutQuad = 1 - (1 - progress) * (1 - progress);
                            const currentValue = startProgress + (progressDiff * easeOutQuad);
                            
                            updateProgressBars(currentValue, waitingMessage);
                            
                            if (progress < 1) {
                                requestAnimationFrame(animateProgress);
                            } else {
                                // Ensure final value is exact
                                updateProgressBars(targetProgress, waitingMessage);
                            }
                        };
                        
                        requestAnimationFrame(animateProgress);
                    } else {
                        // If target is less than current (shouldn't happen, but handle it)
                        updateProgressBars(targetProgress, waitingMessage);
                    }
                } else if (data.status === 'complete') {
                    // Export completed
                    updateProgressBars(100, data.message || 'Export completed successfully');
                    
                    // Close SSE connection
                    if (exportProgressEventSource) {
                        exportProgressEventSource.close();
                        exportProgressEventSource = null;
                    }

                    // Show success message and trigger download
                    setTimeout(() => {
                        stopExportProgress();
                        
                        // Trigger download if download URL is provided
                        if (data.downloadUrl) {
                            const downloadUrl = data.downloadUrl + (data.functionParams ? '?functionParams=' + encodeURIComponent(data.functionParams) : '');
                            
                            // Create a temporary link and click it to trigger download
                            // This is the most reliable method for file downloads
                            const downloadLink = document.createElement('a');
                            downloadLink.href = downloadUrl;
                            downloadLink.download = data.downloadFileName || 'export.csv';
                            downloadLink.style.display = 'none';
                            
                            // Append to body, click, then remove
                            document.body.appendChild(downloadLink);
                            downloadLink.click();
                            
                            // Clean up after a short delay
                            setTimeout(() => {
                                document.body.removeChild(downloadLink);
                            }, 100);
                        } else {
                            // Fallback to old method
                            Livewire.dispatch('downloadExportFileEvent', eventData);
                        }
                    }, 1000);
                } else if (data.status === 'error' || data.status === 'cancelled') {
                    // Error or cancelled
                    
                    // Close SSE connection
                    if (exportProgressEventSource) {
                        exportProgressEventSource.close();
                        exportProgressEventSource = null;
                    }

                    // Hide progress bar
                    stopExportProgress();
                    
                    // Show error message
                    if (data.status === 'error') {
                        showFlashMessage('error', data.message || 'Something went wrong during the export. Please try again later.');
                    }
                } else if (data.status === 'timeout') {
                    // Timeout
                    
                    if (exportProgressEventSource) {
                        exportProgressEventSource.close();
                        exportProgressEventSource = null;
                    }
                    
                    stopExportProgress();
                }
            } catch (err) {
                // Error parsing SSE message
            }
        };

        // Handle errors
        exportProgressEventSource.onerror = function(event) {
            try {
                // Close connection on error
                if (exportProgressEventSource) {
                    try {
                        exportProgressEventSource.close();
                    } catch (e) {
                        // console.warn('Error closing exportProgressEventSource:', e);
                    }
                    exportProgressEventSource = null;
                }
                
                // Hide progress bar
                stopExportProgress();
                
                // Only show alert if page is still active
                if (!document.hidden) {
                    try {
                        showFlashMessage('error', 'Connection lost. Please try again later.');
                    } catch (e) {
                        // console.warn('Error showing flash message:', e);
                    }
                }
            } catch (err) {
                // console.error('Error in exportProgressEventSource error handler:', err);
                // Don't throw - ensure navigation can proceed
            }
        };

    } catch (err) {
        showFlashMessage('error', 'Unable to start export monitoring. Please try again later.');
    }
});

Livewire.on('stopExportProgressEvent', () => {
    try {
        // Close SSE connection if active
        if (exportProgressEventSource) {
            exportProgressEventSource.close();
            exportProgressEventSource = null;
        }
        
        stopExportProgress();

    } catch (err) {
        // Error handling
    }
});

Livewire.on('downloadExportFileEvent', (downloadedEventData) => {
    try {
        // Legacy handler - parse and call download function
        const downloadedObj = typeof downloadedEventData === 'string' 
            ? JSON.parse(downloadedEventData) 
            : downloadedEventData;
        
        if (downloadedObj.downloadUrl) {
            // Legacy download method
            const downloadLink = document.createElement('a');
            downloadLink.href = downloadedObj.downloadUrl;
            downloadLink.download = downloadedObj.downloadFileName || 'export.csv';
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
        } else if (downloadedObj.fileContent) {
            // New method with file content
            downloadExportFile(downloadedObj.exportId || '', downloadedObj.fileContent, downloadedObj.filename || 'export.csv');
        }
    } catch (err) {
        // console.error('Error downloading export file:', err);
    }
});

}); // End of livewire:initialized event listener



// Make stopExportProgress available globally so it can be called from onclick handlers
/**
 * Hides the export progress bar with smooth animation
 * Available globally so it can be called from onclick handlers and other contexts
 * Handles both fixed (top) and regular progress bar designs with appropriate animations
 * Removes body padding when fixed progress bar is hidden
 * 
 * @example
 * stopExportProgress(); // Hides progress bar with animation
 */
window.stopExportProgress = function() {
    try {
        // Get all progress bar divs (there may be two - old design and new design)
        const progressBarDivs = document.querySelectorAll('#progressBarDiv');
        
        if (progressBarDivs.length > 0) {
            progressBarDivs.forEach(progressBarDiv => {
                if (progressBarDiv) {
                    const isFixed = progressBarDiv.classList.contains('fixed');
                    
                    if (isFixed) {
                        // Enhanced professional closing animation
                        // Step 1: Add fade-out class for opacity transition
                        progressBarDiv.style.opacity = '1';
                        progressBarDiv.style.transition = 'transform 0.4s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.3s ease-out';
                        
                        // Step 2: Trigger fade out
                        requestAnimationFrame(() => {
                            progressBarDiv.style.opacity = '0';
                            
                            // Step 3: Slide up with smooth easing
                            requestAnimationFrame(() => {
                                progressBarDiv.style.transform = 'translateY(-100%)';
                            });
                        });
                        
                        // Step 4: Clean up after animation completes
                        setTimeout(() => {
                            progressBarDiv.style.display = 'none';
                            progressBarDiv.classList.add('hidden');
                            // Reset styles for next time
                            progressBarDiv.style.opacity = '';
                            progressBarDiv.style.transition = '';
                            // Remove body padding when progress bar is hidden
                            document.body.style.paddingTop = '';
                            // Update toast position when progress bar is hidden
                            updateFlashMessagePosition();
                        }, 400); // Slightly longer for smooth animation
                    } else {
                        // Simple fade out for regular progress bar (old design)
                        progressBarDiv.style.transition = 'opacity 0.3s ease-out';
                        progressBarDiv.style.opacity = '0';
                        setTimeout(() => {
                            progressBarDiv.style.display = 'none';
                            progressBarDiv.classList.add('hidden');
                            progressBarDiv.style.opacity = '';
                            progressBarDiv.style.transition = '';
                            // Update toast position when progress bar is hidden
                            updateFlashMessagePosition();
                        }, 300);
                    }
                }
            });
        }
    } catch (err) {
        // Silently handle errors - don't block navigation
        // console.warn('Error stopping export progress:', err);
    }
};


