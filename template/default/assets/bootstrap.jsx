import React from 'react';
import { createRoot } from 'react-dom/client';
import 'bootstrap/dist/css/bootstrap.min.css';
import App from './App';

const container = document.getElementById('app');
createRoot(container).render(
    <React.StrictMode>
        <App />
    </React.StrictMode>
);
