import React, { useState, useEffect, useCallback } from 'react';
import api from '../api/client';
import LiveMap from './LiveMap';

function DriverDashboard({ user }) {
    const [myDeliveries, setMyDeliveries] = useState([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [location, setLocation] = useState(null);
    const [error, setError] = useState(null);

    // 👇 Use useCallback to prevent re-creation
    const fetchMyDeliveries = useCallback(async () => {
        try {
            const token = localStorage.getItem('token');
            if (!token) {
                window.location.href = '/login';
                return;
            }
            
            const response = await api.get('/deliveries');
            setMyDeliveries(response.data.data || []);
            setError(null);
        } catch (error) {
            console.error('Error fetching deliveries:', error);
            if (error.response?.status === 401) {
                localStorage.removeItem('token');
                localStorage.removeItem('user');
                window.location.href = '/login';
            } else {
                setError('Failed to load deliveries. Please refresh.');
            }
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, []);

    // 👇 Deliveries fetch - Run once on mount only
    useEffect(() => {
        fetchMyDeliveries();
    }, [fetchMyDeliveries]);

    // 👇 GPS Location - Separate effect
    useEffect(() => {
        if (!navigator.geolocation) {
            console.warn('Geolocation not supported');
            return;
        }

        const getLocation = () => {
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    setLocation({
                        lat: pos.coords.latitude,
                        lng: pos.coords.longitude,
                    });
                },
                (err) => {
                    console.error('Geolocation error:', err);
                    // Use default location if geolocation fails
                    setLocation({ lat: 6.9271, lng: 79.8612 });
                }
            );
        };

        getLocation();

        // Send GPS ping every 30 seconds
        const pingInterval = setInterval(() => {
            if (location) {
                sendGpsPing();
            }
        }, 30000);

        // Refresh deliveries every 60 seconds (optional)
        const refreshInterval = setInterval(() => {
            if (!refreshing) {
                fetchMyDeliveries();
            }
        }, 60000);

        return () => {
            clearInterval(pingInterval);
            clearInterval(refreshInterval);
        };
    }, [location, refreshing, fetchMyDeliveries]);

    const sendGpsPing = async () => {
        if (!location) return;
        try {
            await api.post('/gps/ping', location);
        } catch (error) {
            console.error('GPS ping failed:', error);
        }
    };

    const handleRefresh = () => {
        setRefreshing(true);
        fetchMyDeliveries();
    };

    const updateStatus = async (id, status) => {
        try {
            await api.put(`/deliveries/${id}/status`, { status });
            fetchMyDeliveries();
        } catch (error) {
            console.error('Status update failed:', error);
            alert(error.response?.data?.error || 'Failed to update status');
        }
    };

    const confirmPayment = async (id, amount) => {
        try {
            await api.post(`/deliveries/${id}/confirm-payment`, {
                amount_collected: amount,
            });
            fetchMyDeliveries();
            alert('Payment confirmed successfully! ✅');
        } catch (error) {
            console.error('Payment confirmation failed:', error);
            alert(error.response?.data?.error || 'Failed to confirm payment');
        }
    };

    const getStatusColor = (status) => {
        const colors = {
            pending: 'bg-yellow-100 text-yellow-800',
            assigned: 'bg-blue-100 text-blue-800',
            in_transit: 'bg-indigo-100 text-indigo-800',
            delivered: 'bg-green-100 text-green-800',
            failed: 'bg-red-100 text-red-800',
        };
        return colors[status] || 'bg-gray-100 text-gray-800';
    };

    if (loading) {
        return <div className="text-center py-8">Loading...</div>;
    }

    return (
        <div>
            {/* Header */}
            <div className="flex justify-between items-center mb-4">
                <h2 className="text-xl font-bold">Driver Dashboard</h2>
                <button
                    onClick={handleRefresh}
                    disabled={refreshing}
                    className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm disabled:opacity-50"
                >
                    {refreshing ? '⏳ Refreshing...' : '🔄 Refresh'}
                </button>
            </div>

            {error && (
                <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    {error}
                </div>
            )}

            {/* Stats Cards */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div className="bg-white p-4 rounded-lg shadow">
                    <h3 className="text-sm text-gray-500">My Deliveries</h3>
                    <p className="text-2xl font-bold">{myDeliveries.length}</p>
                </div>
                <div className="bg-white p-4 rounded-lg shadow">
                    <h3 className="text-sm text-gray-500">In Transit</h3>
                    <p className="text-2xl font-bold text-blue-600">
                        {myDeliveries.filter(d => d.status === 'in_transit').length}
                    </p>
                </div>
                <div className="bg-white p-4 rounded-lg shadow">
                    <h3 className="text-sm text-gray-500">Delivered</h3>
                    <p className="text-2xl font-bold text-green-600">
                        {myDeliveries.filter(d => d.status === 'delivered').length}
                    </p>
                </div>
            </div>

            {/* Content */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div className="bg-white rounded-lg shadow overflow-hidden">
                    <div className="p-4 border-b">
                        <h3 className="font-bold">Live Map</h3>
                    </div>
                    <LiveMap user={user} />
                </div>

                <div className="bg-white rounded-lg shadow">
                    <div className="p-4 border-b">
                        <h3 className="font-bold">My Deliveries</h3>
                    </div>
                    <div className="max-h-96 overflow-y-auto">
                        {myDeliveries.length === 0 ? (
                            <div className="p-4 text-gray-500 text-center">No deliveries assigned</div>
                        ) : (
                            myDeliveries.map(delivery => (
                                <div key={delivery.id} className="p-4 border-b last:border-0">
                                    <div className="flex justify-between items-start">
                                        <div>
                                            <div className="font-medium">{delivery.customer_name}</div>
                                            <div className="text-sm text-gray-500">{delivery.address}</div>
                                            <div className="text-sm text-gray-500">COD: LKR {delivery.cod_amount}</div>
                                            <span className={`inline-block px-2 py-1 rounded text-xs mt-1 ${getStatusColor(delivery.status)}`}>
                                                {delivery.status}
                                            </span>
                                        </div>
                                        <div className="flex flex-col space-y-1">
                                            {delivery.status === 'assigned' && (
                                                <button
                                                    onClick={() => updateStatus(delivery.id, 'in_transit')}
                                                    className="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs"
                                                >
                                                    Start Route
                                                </button>
                                            )}
                                            {delivery.status === 'in_transit' && (
                                                <>
                                                    <button
                                                        onClick={() => confirmPayment(delivery.id, delivery.cod_amount)}
                                                        className="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs"
                                                    >
                                                        Confirm COD
                                                    </button>
                                                    <button
                                                        onClick={() => updateStatus(delivery.id, 'failed')}
                                                        className="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs"
                                                    >
                                                        Failed
                                                    </button>
                                                </>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}

export default DriverDashboard;