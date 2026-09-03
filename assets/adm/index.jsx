import React from 'react';
import { createRoot } from 'react-dom/client';
import 'bootstrap/dist/css/bootstrap.min.css';
import App from './App';

const container = document.getElementById('adm-root');
createRoot(container).render(
    <React.StrictMode>
        <App />
    </React.StrictMode>
);
