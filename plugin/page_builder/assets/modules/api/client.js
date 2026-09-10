import axios from 'axios';

// Same convention as every other plugin's own admin API client (e.g.
// plugin/portfolio/assets/api/client.js) - session-cookie auth (withCredentials),
// baseURL '/api' since the admin bundle is served from the same origin.
const client = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        Accept: 'application/json',
    },
});

export default client;
