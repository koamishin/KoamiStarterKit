import axios from 'axios';

const http = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
});

const token = document
    .querySelector('meta[name="csrf-token"]')
    ?.getAttribute('content');
if (token) {
    http.defaults.headers.common['X-CSRF-TOKEN'] = token;
}

export default http;
