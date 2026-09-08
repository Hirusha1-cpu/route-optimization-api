import axios from 'axios';
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// Token handling
const token = localStorage.getItem('token');
if (token) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Company ID from meta
const companyIdMeta = document.querySelector('meta[name="company-id"]');
window.companyId = companyIdMeta ? companyIdMeta.content : null;

// Role from meta
const userRoleMeta = document.querySelector('meta[name="user-role"]');
window.userRole = userRoleMeta ? userRoleMeta.content : null;