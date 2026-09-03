import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';

function Dashboard() {
    return <h1>Administration</h1>;
}

export default function App() {
    return (
        <BrowserRouter>
            <Routes>
                <Route path="/" element={<Dashboard />} />
            </Routes>
        </BrowserRouter>
    );
}
