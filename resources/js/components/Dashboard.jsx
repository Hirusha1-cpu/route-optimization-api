import React, { useState, useEffect } from 'react';
import api from '../api/client';
import LiveMap from './LiveMap';
import DeliveryList from './DeliveryList';
import RouteGenerator from './RouteGenerator';

function Dashboard({ user }) {
    const [stats, setStats] = useState({
        today_deliveries: 0,
        pending_deliveries: 0,
        assigned_deliveries: 0,
        in_transit_deliveries: 0,
        delivered_today: 0,
        failed_today: 0,
        active_drivers: 0,
        total_drivers: 0,
    });
    const [loading, setLoading] = useState(true);
    const [activeTab, setActiveTab] = useState('map');

    useEffect(() => {
        fetchStats();
    }, []);

    const fetchStats = async () => {
        try {
            const response = await api.get('/dashboard/stats');
            setStats(response.data);
        } catch (error) {
            console.error('Error fetching stats:', error);
            if (error.response?.status === 403) {
                alert('You need admin access to view dashboard stats');
            }
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return (
            <div className="flex justify-center items-center h-64">
                <div className="text-gray-500">Loading dashboard...</div>
            </div>
        );
    }

    return (
        <div>
            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div className="bg-white p-4 rounded-lg shadow">
                    <h3 className="text-sm text-gray-500">Today's Deliveries</h3>
                    <p className="text-2xl font-bold">{stats.today_deliveries}</p>
                </div>
                <div className="bg-white p-4 rounded-lg shadow">
                    <h3 className="text-sm text-gray-500">Pending</h3>
                    <p className="text-2xl font-bold text-yellow-600">{stats.pending_deliveries}</p>
                </div>
                <div className="bg-white p-4 rounded-lg shadow">
                    <h3 className="text-sm text-gray-500">In Transit</h3>
                    <p className="text-2xl font-bold text-blue-600">{stats.in_transit_deliveries}</p>
                </div>
                <div className="bg-white p-4 rounded-lg shadow">
                    <h3 className="text-sm text-gray-500">Delivered Today</h3>
                    <p className="text-2xl font-bold text-green-600">{stats.delivered_today}</p>
                </div>
            </div>

            {/* Driver Stats */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div className="bg-white p-4 rounded-lg shadow">
                    <h3 className="text-sm text-gray-500">Active Drivers</h3>
                    <p className="text-2xl font-bold">{stats.active_drivers}</p>
                </div>
                <div className="bg-white p-4 rounded-lg shadow">
                    <h3 className="text-sm text-gray-500">Total Drivers</h3>
                    <p className="text-2xl font-bold">{stats.total_drivers}</p>
                </div>
            </div>

            {/* Tabs */}
            <div className="border-b border-gray-200 mb-4">
                <nav className="-mb-px flex space-x-8">
                    <button
                        onClick={() => setActiveTab('map')}
                        className={`py-2 px-1 border-b-2 font-medium text-sm ${
                            activeTab === 'map'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        }`}
                    >
                        🗺️ Live Map
                    </button>
                    <button
                        onClick={() => setActiveTab('deliveries')}
                        className={`py-2 px-1 border-b-2 font-medium text-sm ${
                            activeTab === 'deliveries'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        }`}
                    >
                        📦 Deliveries
                    </button>
                    <button
                        onClick={() => setActiveTab('route')}
                        className={`py-2 px-1 border-b-2 font-medium text-sm ${
                            activeTab === 'route'
                                ? 'border-blue-500 text-blue-600'
                                : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                        }`}
                    >
                        🛣️ Generate Route
                    </button>
                </nav>
            </div>

            {/* Content */}
            <div>
                {activeTab === 'map' && <LiveMap user={user} />}
                {activeTab === 'deliveries' && <DeliveryList />}
                {activeTab === 'route' && <RouteGenerator />}
            </div>
        </div>
    );
}

export default Dashboard;