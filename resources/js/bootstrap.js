import axios from 'axios';

// 👇 Setup axios
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// 👇 Token handling
const token = localStorage.getItem('token');
if (token) {
    window.axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// 👇 Meta data
const companyIdMeta = document.querySelector('meta[name="company-id"]');
window.companyId = companyIdMeta ? companyIdMeta.content : null;

const userRoleMeta = document.querySelector('meta[name="user-role"]');
window.userRole = userRoleMeta ? userRoleMeta.content : null;

export default window.axios;