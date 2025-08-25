$(document).ready(function() {
    // Initialize tooltips and popovers
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });

    // Update navigation based on auth state
    updateNavigation();

    // Handle logout clicks
    $(document).on('click', '[data-action="logout"]', function(e) {
        e.preventDefault();
        Auth.logout();
    });

    // Handle form submissions with loading states
    $(document).on('submit', '.ajax-form', function(e) {
        e.preventDefault();
        const form = $(this);
        const submitBtn = form.find('button[type="submit"]');

        Utils.showLoading(submitBtn[0]);

        // Form will be handled by specific page scripts
        setTimeout(() => {
            Utils.hideLoading(submitBtn[0]);
        }, 2000);
    });
});

function updateNavigation() {
    const navbar = document.querySelector('.navbar-nav');
    if (!navbar) return;

    const authButtons = navbar.querySelector('.nav-item:last-child').parentElement;

    if (Auth.isAuthenticated()) {
        const user = Auth.getUser();
        const dashboardUrl = user.role === 'admin' ? '/admin/dashboard.html' : '/student/dashboard.html';

        authButtons.innerHTML = `
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                    <i class="fas fa-user me-2"></i>${user.name}
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="${dashboardUrl}"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                    <li><a class="dropdown-item" href="/student/profile.html"><i class="fas fa-user-edit me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="#" data-action="logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                </ul>
            </li>
        `;
    } else {
        authButtons.innerHTML = `
            <li class="nav-item ms-3">
                <a href="/auth/login.html" class="btn btn-outline-light me-2">Login</a>
            </li>
            <li class="nav-item">
                <a href="/auth/register.html" class="btn btn-primary">Register</a>
            </li>
        `;
    }
}

// Global error handler
window.addEventListener('unhandledrejection', function(event) {
    console.error('Unhandled promise rejection:', event.reason);
    if (event.reason instanceof APIError && event.reason.status === 401) {
        Auth.logout();
    }
});

// Check auth status on page load
window.addEventListener('load', function() {
    if (Auth.isAuthenticated()) {
        // Verify token is still valid
        Auth.getCurrentUser().catch(() => {
            // Token invalid, logout silently
        });
    }
});