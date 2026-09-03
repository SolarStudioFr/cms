import React from 'react';
import { BrowserRouter, Routes, Route } from 'react-router-dom';

function Home() {
    return <h1>CMS</h1>;
}

export default function App() {
    return (
        <BrowserRouter>
            <Routes>
                <Route path="/" element={<Home />} />
            </Routes>
        </BrowserRouter>
    );
}
