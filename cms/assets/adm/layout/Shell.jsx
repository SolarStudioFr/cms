import React from 'react';
import { Outlet } from 'react-router-dom';
import Sidebar from './Sidebar';

export default function Shell({ extraNavItems = [] }) {
    return (
        <div className="d-flex">
            <Sidebar extraItems={extraNavItems} />
            <div className="flex-grow-1 p-4">
                <Outlet />
            </div>
        </div>
    );
}
