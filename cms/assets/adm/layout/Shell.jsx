import React from 'react';
import { Outlet } from 'react-router-dom';
import Sidebar from './Sidebar';

export default function Shell({ pluginItems = [] }) {
    return (
        <div className="d-flex">
            <Sidebar pluginItems={pluginItems} />
            <div className="flex-grow-1 p-4">
                <Outlet />
            </div>
        </div>
    );
}
