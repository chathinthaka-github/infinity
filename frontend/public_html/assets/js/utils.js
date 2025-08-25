class Utils {
    static showAlert(message, type = 'info', duration = 5000) {
        const alertContainer = document.getElementById('alert-container') || this.createAlertContainer();

        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        alertContainer.appendChild(alertDiv);

        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, duration);
    }

    static createAlertContainer() {
        const container = document.createElement('div');
        container.id = 'alert-container';
        container.className = 'position-fixed top-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
        return container;
    }

    static showLoading(element, text = 'Loading...') {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }

        element.classList.add('loading');
        element.setAttribute('data-original-html', element.innerHTML);
        element.innerHTML = `<span class="spinner me-2"></span>${text}`;
        element.disabled = true;
    }

    static hideLoading(element, originalText = null) {
        if (typeof element === 'string') {
            element = document.querySelector(element);
        }

        element.classList.remove('loading');
        const originalHtml = element.getAttribute('data-original-html');
        element.innerHTML = originalText || originalHtml || element.innerHTML;
        element.disabled = false;
        element.removeAttribute('data-original-html');
    }

    static formatDate(dateString, options = {}) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            ...options
        });
    }

    static formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    static truncateText(text, length = 100) {
        return text.length > length ? text.substring(0, length) + '...' : text;
    }

    static validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    static validatePassword(password) {
        return password.length >= 8;
    }

    static debounce(func, wait, immediate) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                timeout = null;
                if (!immediate) func(...args);
            };
            const callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func(...args);
        };
    }

    static generateSlug(text) {
        return text
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    static copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            this.showAlert('Copied to clipboard!', 'success', 2000);
        }).catch(() => {
            this.showAlert('Failed to copy to clipboard', 'error');
        });
    }

    static createProgressRing(percentage, size = 60) {
        const radius = 18;
        const circumference = 2 * Math.PI * radius;
        const strokeDasharray = `${(percentage / 100) * circumference} ${circumference}`;

        return `
            <svg class="progress-ring" width="${size}" height="${size}">
                <circle
                    stroke="#e5e7eb"
                    stroke-width="4"
                    fill="transparent"
                    r="${radius}"
                    cx="${size/2}"
                    cy="${size/2}"/>
                <circle
                    stroke="#4f46e5"
                    stroke-width="4"
                    stroke-linecap="round"
                    fill="transparent"
                    r="${radius}"
                    cx="${size/2}"
                    cy="${size/2}"
                    style="stroke-dasharray: ${strokeDasharray}; transform: rotate(-90deg); transform-origin: ${size/2}px ${size/2}px;"/>
                <text x="${size/2}" y="${size/2}" font-family="Arial" font-size="12" font-weight="bold" text-anchor="middle" dy=".3em" fill="#374151">
                    ${Math.round(percentage)}%
                </text>
            </svg>
        `;
    }
}