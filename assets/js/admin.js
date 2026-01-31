// Admin JavaScript
document.addEventListener('DOMContentLoaded', function() {
    // Sidebar Toggle
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('active');
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 1024) {
            if (!sidebar.contains(event.target) && !sidebarToggle.contains(event.target)) {
                sidebar.classList.remove('active');
            }
        }
    });

    // Form Validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let valid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    valid = false;
                    field.style.borderColor = 'var(--danger)';
                } else {
                    field.style.borderColor = '';
                }
            });
            
            if (!valid) {
                e.preventDefault();
                showNotification('Por favor, complete todos los campos requeridos', 'error');
            }
        });
    });

    // Image Preview
    const imageInputs = document.querySelectorAll('input[type="file"][accept^="image"]');
    imageInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const previewId = this.getAttribute('data-preview');
                if (previewId) {
                    const preview = document.getElementById(previewId);
                    if (preview) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                            preview.style.display = 'block';
                        }
                        reader.readAsDataURL(file);
                    }
                }
            }
        });
    });

    // Confirmación para acciones peligrosas
    const dangerousButtons = document.querySelectorAll('.btn-danger, a[href*="eliminar"], a[href*="delete"]');
    dangerousButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('¿Está seguro de que desea realizar esta acción? Esta acción no se puede deshacer.')) {
                e.preventDefault();
            }
        });
    });

    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Dynamic form fields
    window.addFormField = function(button) {
        const container = button.parentNode.querySelector('.form-fields-container');
        const template = container.querySelector('.form-field-template');
        const newField = template.cloneNode(true);
        newField.classList.remove('form-field-template');
        newField.style.display = 'block';
        
        // Update names and IDs
        const inputs = newField.querySelectorAll('input, select, textarea');
        const index = container.children.length - 1; // -1 porque el último es el template
        
        inputs.forEach(input => {
            const name = input.getAttribute('name');
            const id = input.getAttribute('id');
            if (name) input.setAttribute('name', name.replace('[]', '[' + index + ']'));
            if (id) input.setAttribute('id', id + '_' + index);
        });
        
        container.insertBefore(newField, template);
    };

    window.removeFormField = function(button) {
        const field = button.closest('.form-field');
        if (field && !field.classList.contains('form-field-template')) {
            field.remove();
        }
    };

    // Table sorting
    const sortableHeaders = document.querySelectorAll('th[data-sort]');
    sortableHeaders.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const table = this.closest('table');
            const column = this.getAttribute('data-sort');
            const isAsc = this.classList.contains('sort-asc');
            
            // Reset other headers
            table.querySelectorAll('th[data-sort]').forEach(h => {
                h.classList.remove('sort-asc', 'sort-desc');
            });
            
            // Toggle sort direction
            this.classList.toggle('sort-asc', !isAsc);
            this.classList.toggle('sort-desc', isAsc);
            
            // Sort table (simplified - in real implementation, you'd sort the data)
            console.log('Sort by:', column, 'Direction:', isAsc ? 'desc' : 'asc');
        });
    });

    // Search functionality
    const searchInputs = document.querySelectorAll('.search-input');
    searchInputs.forEach(input => {
        input.addEventListener('input', debounce(function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const table = e.target.closest('.table-container').querySelector('table');
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        }, 300));
    });

    // Modal functionality
    window.openModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeModal = function(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    };

    // Close modal when clicking outside
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal')) {
            e.target.style.display = 'none';
            document.body.style.overflow = '';
        }
    });

    // Notification system
    window.showNotification = function(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close" onclick="this.parentElement.remove()">&times;</button>
        `;
        
        document.body.appendChild(notification);
        
        // Add styles if not already added
        if (!document.getElementById('notification-styles')) {
            const styles = document.createElement('style');
            styles.id = 'notification-styles';
            styles.textContent = `
                .notification {
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    background: white;
                    padding: 1rem 1.5rem;
                    border-radius: 12px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
                    display: flex;
                    align-items: center;
                    gap: 1rem;
                    transform: translateX(400px);
                    opacity: 0;
                    transition: all 0.3s ease;
                    z-index: 10000;
                    border-left: 4px solid #27ae60;
                    max-width: 400px;
                }
                
                .notification-error {
                    border-left-color: #e74c3c;
                }
                
                .notification-warning {
                    border-left-color: #f39c12;
                }
                
                .notification.show {
                    transform: translateX(0);
                    opacity: 1;
                }
                
                .notification-content {
                    display: flex;
                    align-items: center;
                    gap: 0.5rem;
                    flex: 1;
                }
                
                .notification-close {
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: #6c757d;
                    padding: 0;
                    width: 30px;
                    height: 30px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                }
            `;
            document.head.appendChild(styles);
        }
        
        // Animate in
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Auto remove
        setTimeout(() => {
            if (notification.parentNode) {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);
    };

    // Initialize tooltips
    const tooltips = document.querySelectorAll('[data-tooltip]');
    tooltips.forEach(element => {
        element.addEventListener('mouseenter', function(e) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = this.getAttribute('data-tooltip');
            document.body.appendChild(tooltip);
            
            const rect = this.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
            
            this._tooltip = tooltip;
        });
        
        element.addEventListener('mouseleave', function() {
            if (this._tooltip) {
                this._tooltip.remove();
            }
        });
    });
});

// Utility functions
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

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// AJAX helper
function ajaxRequest(url, options = {}) {
    const defaults = {
        method: 'GET',
        data: null,
        headers: {}
    };
    
    const config = { ...defaults, ...options };
    
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(config.method, url);
        
        // Set headers
        if (config.data && !(config.data instanceof FormData)) {
            xhr.setRequestHeader('Content-Type', 'application/json');
        }
        
        Object.keys(config.headers).forEach(key => {
            xhr.setRequestHeader(key, config.headers[key]);
        });
        
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    resolve(response);
                } catch (e) {
                    resolve(xhr.responseText);
                }
            } else {
                reject(new Error(xhr.statusText));
            }
        };
        
        xhr.onerror = function() {
            reject(new Error('Network error'));
        };
        
        xhr.send(config.data instanceof FormData ? config.data : JSON.stringify(config.data));
    });
}