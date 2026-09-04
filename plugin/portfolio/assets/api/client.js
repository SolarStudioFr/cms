import axios from 'axios';

const client = axios.create({
    baseURL: '/api',
    withCredentials: true,
    headers: {
        Accept: 'application/json',
    },
});

export default client;
