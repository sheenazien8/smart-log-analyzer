// Smart Log Analyzer Dashboard JavaScript - Tailwind Compatible

document.addEventListener('DOMContentLoaded', function() {
    // Initialize dashboard functionality
    initializeRefreshButton();
    initializeAutoRefresh();
    initializeTooltips();
    initializeFilters();
});

// Refresh button functionality
function initializeRefreshButton() {
    const refreshButton = document.querySelector('.refresh-btn, [onclick*="reload"]');
    if (refreshButton) {
        refreshButton.addEventListener('click', function(e) {
            e.preventDefault();
            refreshDashboard();
        });
    }
}

// Auto-refresh functionality
function initializeAutoRefresh() {
    const autoRefreshInterval = 30000; // 30 seconds
    
    if (window.location.pathname.includes('smart-log-analyzer')) {
        setInterval(function() {
            refreshStats();
        }, autoRefreshInterval);
    }
}

// Initialize tooltips using native JavaScript (no Bootstrap dependency)
function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(function(element) {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

// Initialize filter functionality
function initializeFilters() {
    const filterForms = document.querySelectorAll('.filter-form, form');
    filterForms.forEach(function(form) {
        const inputs = form.querySelectorAll('input, select');
        inputs.forEach(function(input) {
            input.addEventListener('change', function() {
                // Add loading state
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Filtering...';
                    submitBtn.disabled = true;
                }
                form.submit();
            });
        });
    });
}

// Show/hide tooltip
function showTooltip(e) {
    const tooltip = document.createElement('div');
    tooltip.id = 'tooltip';
    tooltip.className = 'absolute bg-gray-900 text-white text-xs rounded py-1 px-2 z-50 pointer-events-none';
    tooltip.textContent = e.target.getAttribute('title');
    
    // Remove title to prevent browser tooltip
    e.target.setAttribute('data-original-title', e.target.getAttribute('title'));
    e.target.removeAttribute('title');
    
    document.body.appendChild(tooltip);
    
    // Position tooltip
    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
}

function hideTooltip(e) {
    const tooltip = document.getElementById('tooltip');
    if (tooltip) {
        tooltip.remove();
    }
    
    // Restore original title
    const originalTitle = e.target.getAttribute('data-original-title');
    if (originalTitle) {
        e.target.setAttribute('title', originalTitle);
        e.target.removeAttribute('data-original-title');
    }
}

// Refresh entire dashboard
function refreshDashboard() {
    showLoading();
    window.location.reload();
}

// Refresh only statistics via AJAX
function refreshStats() {
    fetch('/api/smart-log-analyzer/stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatsCards(data.data);
            }
        })
        .catch(error => {
            console.error('Error refreshing stats:', error);
        });
}

// Update statistics cards
function updateStatsCards(stats) {
    const statElements = {
        'total_logs': document.querySelector('[data-stat="total_logs"]'),
        'total_errors': document.querySelector('[data-stat="total_errors"]'),
        'unique_patterns': document.querySelector('[data-stat="unique_patterns"]'),
        'active_anomalies': document.querySelector('[data-stat="active_anomalies"]')
    };

    Object.keys(statElements).forEach(key => {
        const element = statElements[key];
        if (element && stats[key] !== undefined) {
            // Add animation effect
            element.classList.add('transition-all', 'duration-300');
            element.style.transform = 'scale(1.05)';
            element.textContent = formatNumber(stats[key]);
            
            setTimeout(() => {
                element.style.transform = 'scale(1)';
            }, 200);
        }
    });
}

// Show loading state
function showLoading() {
    const refreshButtons = document.querySelectorAll('[onclick*="reload"], .refresh-btn');
    refreshButtons.forEach(button => {
        button.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Refreshing...';
        button.disabled = true;
        button.classList.add('opacity-75', 'cursor-not-allowed');
    });
}

// Hide loading state
function hideLoading() {
    const refreshButtons = document.querySelectorAll('[onclick*="reload"], .refresh-btn');
    refreshButtons.forEach(button => {
        button.innerHTML = '<i class="fas fa-sync-alt mr-2"></i>Refresh';
        button.disabled = false;
        button.classList.remove('opacity-75', 'cursor-not-allowed');
    });
}

// Format numbers with commas
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

// Pattern management functions
function resolvePattern(patternId) {
    if (confirm('Are you sure you want to mark this pattern as resolved?')) {
        fetch(`/smart-log-analyzer/patterns/${patternId}/resolve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Pattern marked as resolved', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showAlert('Failed to resolve pattern', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred', 'error');
        });
    }
}

function unresolvePattern(patternId) {
    if (confirm('Are you sure you want to mark this pattern as unresolved?')) {
        fetch(`/smart-log-analyzer/patterns/${patternId}/unresolve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Pattern marked as unresolved', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showAlert('Failed to unresolve pattern', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred', 'error');
        });
    }
}

// Anomaly management functions
function resolveAnomaly(anomalyId) {
    if (confirm('Are you sure you want to mark this anomaly as resolved?')) {
        fetch(`/smart-log-analyzer/anomalies/${anomalyId}/resolve`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Anomaly marked as resolved', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showAlert('Failed to resolve anomaly', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred', 'error');
        });
    }
}

function ignoreAnomaly(anomalyId) {
    if (confirm('Are you sure you want to ignore this anomaly?')) {
        fetch(`/smart-log-analyzer/anomalies/${anomalyId}/ignore`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Anomaly ignored', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showAlert('Failed to ignore anomaly', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('An error occurred', 'error');
        });
    }
}

// Show alert messages using Tailwind classes
function showAlert(message, type = 'info') {
    const alertClasses = {
        success: 'bg-green-100 border border-green-400 text-green-700',
        error: 'bg-red-100 border border-red-400 text-red-700',
        warning: 'bg-yellow-100 border border-yellow-400 text-yellow-700',
        info: 'bg-blue-100 border border-blue-400 text-blue-700'
    };
    
    const alertClass = alertClasses[type] || alertClasses.info;
    
    const alertHtml = `
        <div class="${alertClass} px-4 py-3 rounded mb-4 relative alert-dismissible" role="alert">
            <span class="block sm:inline">${message}</span>
            <button type="button" class="absolute top-0 bottom-0 right-0 px-4 py-3 alert-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `;
    
    const container = document.querySelector('main');
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHtml);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            const alert = container.querySelector('.alert-dismissible');
            if (alert) {
                alert.classList.add('transition-opacity', 'duration-300', 'opacity-0');
                setTimeout(() => alert.remove(), 300);
            }
        }, 5000);
    }
}

// Chart utilities
function createLineChart(ctx, data, options = {}) {
    return new Chart(ctx, {
        type: 'line',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            ...options
        }
    });
}

function createDoughnutChart(ctx, data, options = {}) {
    return new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            },
            ...options
        }
    });
}

// Utility functions for UI interactions
function toggleVisibility(elementId) {
    const element = document.getElementById(elementId);
    if (element) {
        element.classList.toggle('hidden');
    }
}

function fadeIn(element, duration = 300) {
    element.style.opacity = '0';
    element.style.display = 'block';
    element.classList.add('transition-opacity');
    element.style.transitionDuration = duration + 'ms';
    
    setTimeout(() => {
        element.style.opacity = '1';
    }, 10);
}

function fadeOut(element, duration = 300) {
    element.classList.add('transition-opacity');
    element.style.transitionDuration = duration + 'ms';
    element.style.opacity = '0';
    
    setTimeout(() => {
        element.style.display = 'none';
    }, duration);
}

// Export functions for global access
window.SmartLogAnalyzer = {
    resolvePattern,
    unresolvePattern,
    resolveAnomaly,
    ignoreAnomaly,
    refreshDashboard,
    refreshStats,
    showAlert,
    createLineChart,
    createDoughnutChart,
    toggleVisibility,
    fadeIn,
    fadeOut,
    formatNumber
};