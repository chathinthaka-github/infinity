class Auth {
    static TOKEN_KEY = 'auth_token';
    static USER_KEY = 'auth_user';

    static setToken(token) {
        localStorage.setItem(this.TOKEN_KEY, token);
    }

    static getToken() {
        return localStorage.getItem(this.TOKEN_KEY);
    }

    static setUser(user) {
        localStorage.setItem(this.USER_KEY, JSON.stringify(user));
    }

    static getUser() {
        const user = localStorage.getItem(this.USER_KEY);
        return user ? JSON.parse(user) : null;
    }

    static isAuthenticated() {
        return !!this.getToken();
    }

    static isAdmin() {
        const user = this.getUser();
        return user && user.role === 'admin';
    }

    static isStudent() {
        const user = this.getUser();
        return user && user.role === 'student';
    }

    static logout() {
        localStorage.removeItem(this.TOKEN_KEY);
        localStorage.removeItem(this.USER_KEY);
        window.location.href = '/';
    }

    static async login(email, password) {
        try {
            const response = await API.post('/auth/login', {
                email,
                password
            });

            if (response.success) {
                this.setToken(response.data.token);
                this.setUser(response.data.user);
                return response;
            }

            throw new Error(response.error || 'Login failed');
        } catch (error) {
            throw error;
        }
    }

    static async register(userData) {
        try {
            const response = await API.post('/auth/register', userData);

            if (response.success) {
                this.setToken(response.data.token);
                this.setUser(response.data.user);
                return response;
            }

            throw new Error(response.error || 'Registration failed');
        } catch (error) {
            throw error;
        }
    }

    static async getCurrentUser() {
        try {
            const response = await API.get('/auth/me');
            if (response.success) {
                this.setUser(response.data.user);
                return response.data.user;
            }
            throw new Error('Failed to get user data');
        } catch (error) {
            this.logout();
            throw error;
        }
    }

    static redirectIfNotAuthenticated() {
        if (!this.isAuthenticated()) {
            window.location.href = '/auth/login.html';
            return false;
        }
        return true;
    }

    static redirectIfNotAdmin() {
        if (!this.isAuthenticated() || !this.isAdmin()) {
            window.location.href = '/';
            return false;
        }
        return true;
    }
}