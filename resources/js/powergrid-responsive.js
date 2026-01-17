/**
 * PowerGrid Responsive Mobile Functionality
 * Handles row expansion toggle for mobile card view
 */

// Initialize PowerGrid row expansion state
if (typeof window.pgRowExpanded === 'undefined') {
    window.pgRowExpanded = {};
}

// Alpine.js data function for row expansion
const registerPgRowExpand = (AlpineInstance) => {
    AlpineInstance.data('pgRowExpand', (rowId, showByDefault) => {
        // Initialize state for this row if not exists
        if (!window.pgRowExpanded.hasOwnProperty(rowId)) {
            window.pgRowExpanded[rowId] = showByDefault;
        }

        // Initialize expanded state from global store
        const initialExpanded = window.pgRowExpanded[rowId] !== undefined
            ? window.pgRowExpanded[rowId]
            : showByDefault;

        return {
            rowId: rowId,
            showByDefault: showByDefault,
            expanded: initialExpanded,
            toggleRow() {
                this.expanded = !this.expanded;
                const newValue = this.expanded;

                // Update global state
                window.pgRowExpanded[this.rowId] = newValue;

                // Dispatch custom event to update all cells in this row
                document.dispatchEvent(new CustomEvent('pg-row-toggle', {
                    detail: { rowId: this.rowId, expanded: newValue }
                }));

                // Manually update display for all cells with same rowId (fallback)
                this.$nextTick(() => {
                    const cells = document.querySelectorAll(`[data-row-id="${this.rowId}"].mobile-hidden-field`);
                    cells.forEach(cell => {
                        // Update Alpine data if available
                        const alpineData = Alpine.$data(cell);
                        if (alpineData && alpineData.rowId === this.rowId) {
                            alpineData.expanded = newValue;
                        }
                        // Also directly update display as fallback
                        if (newValue) {
                            cell.style.display = 'block';
                        } else {
                            cell.style.display = 'none';
                        }
                    });

                    // Update toggle button text by updating all toggle buttons in this row
                    const toggleButtons = document.querySelectorAll(`[data-row-id="${this.rowId}"].mobile-toggle-cell`);
                    toggleButtons.forEach(toggleCell => {
                        const toggleAlpineData = Alpine.$data(toggleCell);
                        if (toggleAlpineData && toggleAlpineData.rowId === this.rowId) {
                            toggleAlpineData.expanded = newValue;
                        }
                    });
                });
            },
            init() {
                // Sync with global state on init
                if (window.pgRowExpanded[this.rowId] !== undefined) {
                    this.expanded = window.pgRowExpanded[this.rowId];
                }

                // Listen for global toggle events from other cells
                const handler = (e) => {
                    if (e.detail.rowId === this.rowId) {
                        this.expanded = e.detail.expanded;
                    }
                };
                document.addEventListener('pg-row-toggle', handler);

                // Cleanup on destroy
                this.$el._x_cleanups = this.$el._x_cleanups || [];
                this.$el._x_cleanups.push(() => {
                    document.removeEventListener('pg-row-toggle', handler);
                });
            }
        };
    });
};

// Register pgRowExpand when Alpine initializes
if (window.Alpine) {
    // Alpine already initialized → register immediately
    registerPgRowExpand(window.Alpine);
} else {
    // Register when Alpine initializes
    document.addEventListener('alpine:init', () => registerPgRowExpand(Alpine));
}

// Global event listener to update all cells when row is toggled (fallback for CSS)
document.addEventListener('pg-row-toggle', (event) => {
    const { rowId, expanded } = event.detail;
    const cells = document.querySelectorAll(`[data-row-id="${rowId}"].mobile-hidden-field`);
    cells.forEach(cell => {
        cell.style.display = expanded ? 'block' : 'none';
    });
});

// Ensure checkboxes are visible on mobile
function ensureCheckboxVisibility() {
    if (window.innerWidth <= 768) {
        // Find all checkbox cells and inputs
        const checkboxCells = document.querySelectorAll('.power-grid-table tbody td:has(input[type="checkbox"]), .power-grid-table tbody td[class*="checkbox"]');
        checkboxCells.forEach(cell => {
            // Ensure cell is visible
            cell.style.display = 'flex';
            cell.style.visibility = 'visible';
            cell.style.opacity = '1';
            cell.style.height = 'auto';
            cell.style.minHeight = 'auto';
            cell.style.width = 'auto';
            cell.style.padding = '0.375rem 0.5rem'; /* py-1.5 px-2 to match footer */

            // Ensure checkbox input is visible
            const checkbox = cell.querySelector('input[type="checkbox"]');
            if (checkbox) {
                checkbox.style.display = 'inline-block';
                checkbox.style.visibility = 'visible';
                checkbox.style.opacity = '1';
                checkbox.style.height = '1rem';
                checkbox.style.width = '1rem';
            }

            // Ensure container divs and labels are visible
            const containers = cell.querySelectorAll('div, label');
            containers.forEach(container => {
                container.style.display = 'flex';
                container.style.visibility = 'visible';
                container.style.opacity = '1';
            });
        });
    }
}

// Hide inline filter row on mobile - Force removal (Common solution for all tabs)
function hideFilterRowOnMobile() {
    if (window.innerWidth <= 768) {
        // Find all filter rows by wire:key pattern - check all tables
        const allTables = document.querySelectorAll('.power-grid-table, table.power-grid-table');
        allTables.forEach(table => {
            const allRows = table.querySelectorAll('tbody tr');
            allRows.forEach(row => {
                // Skip if already marked as hidden
                if (row.getAttribute('data-filter-row-hidden') === 'true') {
                    return;
                }

                // Skip if this row contains a toggle cell (read more button) - this is a data row, not a filter row
                if (row.querySelector('.mobile-toggle-cell, td.mobile-toggle-cell')) {
                    return;
                }

                // Skip if this row contains a checkbox - this is a data row, not a filter row
                if (row.querySelector('input[type="checkbox"]')) {
                    return;
                }

                // Skip if this row has data-row-id attribute (data rows, not filter rows)
                if (row.querySelector('[data-row-id]')) {
                    // Only skip if it's not a filter row - check if it has filter cells
                    const filterCells = row.querySelectorAll('td[wire\\:key*="column-filter"]');
                    if (filterCells.length === 0) {
                        return; // This is a data row, skip it
                    }
                }

                // Check for filter cells by wire:key attribute - must have multiple filter cells to be a filter row
                const filterCells = row.querySelectorAll('td[wire\\:key*="column-filter"], td[wire\\:key*="filter-"]');

                // A filter row should have at least 2 filter cells (one per column with filter)
                // This ensures we don't accidentally hide data rows that might have one input
                if (filterCells.length >= 2) {
                    // This is definitely a filter row - completely hide it
                    row.style.display = 'none';
                    row.style.visibility = 'hidden';
                    row.style.height = '0';
                    row.style.minHeight = '0';
                    row.style.maxHeight = '0';
                    row.style.padding = '0';
                    row.style.margin = '0';
                    row.style.border = 'none';
                    row.style.opacity = '0';
                    row.style.position = 'absolute';
                    row.style.left = '-9999px';
                    row.style.width = '0';
                    row.style.overflow = 'hidden';
                    row.style.lineHeight = '0';
                    row.style.fontSize = '0';
                    row.setAttribute('data-filter-row-hidden', 'true');

                    // Also hide all cells in this row
                    const cells = row.querySelectorAll('td');
                    cells.forEach(cell => {
                        cell.style.display = 'none';
                        cell.style.visibility = 'hidden';
                        cell.style.height = '0';
                        cell.style.width = '0';
                        cell.style.padding = '0';
                        cell.style.margin = '0';
                        cell.style.border = 'none';
                    });
                } else if (filterCells.length === 1) {
                    // Single filter cell - check if it's part of a filter row pattern
                    // Filter rows typically have filter inputs with specific patterns
                    const hasFilterInput = row.querySelector('td input[data-cy*="input_text"], td select[wire\\:model*="filters"], td .flatpickr');
                    const hasMultipleFilterInputs = row.querySelectorAll('td input[data-cy*="input_text"], td select[wire\\:model*="filters"], td .flatpickr').length >= 2;

                    // Only hide if it has multiple filter inputs (indicating it's a filter row)
                    if (hasMultipleFilterInputs) {
                        row.style.display = 'none';
                        row.style.visibility = 'hidden';
                        row.style.height = '0';
                        row.style.minHeight = '0';
                        row.style.maxHeight = '0';
                        row.style.padding = '0';
                        row.style.margin = '0';
                        row.style.border = 'none';
                        row.style.opacity = '0';
                        row.style.position = 'absolute';
                        row.style.left = '-9999px';
                        row.style.width = '0';
                        row.style.overflow = 'hidden';
                        row.style.lineHeight = '0';
                        row.style.fontSize = '0';
                        row.setAttribute('data-filter-row-hidden', 'true');
                    }
                }
            });
        });
    }
}

// Debounce function to avoid too many calls
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Debounced version of hideFilterRowOnMobile
const debouncedHideFilterRow = debounce(hideFilterRowOnMobile, 100);

// Run on page load
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        ensureCheckboxVisibility();
        hideFilterRowOnMobile();
        // Also run after a short delay to catch late-rendered content
        setTimeout(() => {
            ensureCheckboxVisibility();
            hideFilterRowOnMobile();
        }, 500);
    });
} else {
    ensureCheckboxVisibility();
    hideFilterRowOnMobile();
    setTimeout(() => {
        ensureCheckboxVisibility();
        hideFilterRowOnMobile();
    }, 500);
}

// Run on resize
window.addEventListener('resize', () => {
    ensureCheckboxVisibility();
    debouncedHideFilterRow();
});

// Run after Livewire updates - comprehensive event handling
document.addEventListener('livewire:load', () => {
    ensureCheckboxVisibility();
    hideFilterRowOnMobile();
    setTimeout(() => {
        ensureCheckboxVisibility();
        hideFilterRowOnMobile();
    }, 300);
});

document.addEventListener('livewire:update', () => {
    ensureCheckboxVisibility();
    hideFilterRowOnMobile();
    setTimeout(() => {
        ensureCheckboxVisibility();
        hideFilterRowOnMobile();
    }, 300);
});

document.addEventListener('livewire:navigated', () => {
    ensureCheckboxVisibility();
    hideFilterRowOnMobile();
    setTimeout(() => {
        ensureCheckboxVisibility();
        hideFilterRowOnMobile();
    }, 500);
});

// Hook into Livewire's morph system
if (window.Livewire) {
    // Listen for morph updates
    Livewire.hook('morph.updated', ({ el, component }) => {
        setTimeout(() => {
            ensureCheckboxVisibility();
            hideFilterRowOnMobile();
        }, 100);
    });

    Livewire.hook('morph.added', ({ el, component }) => {
        setTimeout(() => {
            ensureCheckboxVisibility();
            hideFilterRowOnMobile();
        }, 100);
    });
}

// Use MutationObserver to watch for new rows - more aggressive
if (typeof MutationObserver !== 'undefined') {
    const observer = new MutationObserver((mutations) => {
        let shouldCheck = false;
        mutations.forEach(mutation => {
            if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1) { // Element node
                        if (node.matches && (node.matches('.power-grid-table') || node.matches('table') || node.querySelector('.power-grid-table'))) {
                            shouldCheck = true;
                        }
                        if (node.matches && node.matches('tr') && node.querySelector('td[wire\\:key*="column-filter"]')) {
                            shouldCheck = true;
                        }
                    }
                });
            }
        });
        if (shouldCheck) {
            ensureCheckboxVisibility();
            debouncedHideFilterRow();
        }
    });

    // Observe document body for all table changes
    if (document.body) {
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        });
    }
}
