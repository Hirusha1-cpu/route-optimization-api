import React, { useState, useEffect } from 'react';
import api from '../api/client';
import DeliveryCreate from './DeliveryCreate';

function DeliveryList() {
    const [deliveries, setDeliveries] = useState([]);
    const [loading, setLoading] = useState(true);
    const [statusFilter, setStatusFilter] = useState('');
    const [showCreate, setShowCreate] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        fetchDeliveries();
    }, [statusFilter]);

    const fetchDeliveries = async () => {
        try {
            setLoading(true);
            setError('');
            const url = statusFilter ? `/deliveries?status=${statusFilter}` : '/deliveries';
            const response = await api.get(url);
            console.log('📦 Deliveries loaded:', response.data.data?.length);
            setDeliveries(response.data.data || []);
        } catch (error) {
            console.error('❌ Error fetching deliveries:', error);
            if (error.response?.status === 403) {
                setError('You need admin access to view all deliveries. Please login as admin.');
            } else if (error.response?.status === 401) {
                setError('Please login again.');
                window.location.href = '/login';
            } else {
                setError('Failed to load deliveries. Please try again.');
            }
        } finally {
            setLoading(false);
        }
    };

    const handleRefresh = () => {
        fetchDeliveries();
    };

    const handleStatusUpdate = async (id, status) => {
        try {
            await api.put(`/deliveries/${id}/status`, { status });
            fetchDeliveries();
        } catch (error) {
            console.error('Error updating status:', error);
            alert(error.response?.data?.error || 'Failed to update status');
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
        return <div className="text-center py-8">Loading deliveries...</div>;
    }

    return (
        <div>
            <div className="flex justify-between items-center mb-4">
                <h2 className="text-xl font-bold">Deliveries</h2>
                <div className="flex gap-2">
                    <button
                        onClick={handleRefresh}
                        className="bg-gray-500 hover:bg-gray-600 text-white px-3 py-2 rounded-md text-sm"
                    >
                        🔄 Refresh
                    </button>
                    <button
                        onClick={() => setShowCreate(true)}
                        className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm"
                    >
                        + New Delivery
                    </button>
                </div>
            </div>

            {error && (
                <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    ⚠️ {error}
                </div>
            )}

            {/* Filter */}
            <div className="mb-4">
                <select
                    className="border rounded-md px-3 py-2 text-sm"
                    value={statusFilter}
                    onChange={(e) => setStatusFilter(e.target.value)}
                >
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="assigned">Assigned</option>
                    <option value="in_transit">In Transit</option>
                    <option value="delivered">Delivered</option>
                    <option value="failed">Failed</option>
                </select>
                <span className="ml-2 text-sm text-gray-500">
                    {deliveries.length} deliveries found
                </span>
            </div>

            {/* Table */}
            <div className="bg-white rounded-lg shadow overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">COD</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {deliveries.length === 0 ? (
                            <tr>
                                <td colSpan="5" className="px-6 py-4 text-center text-gray-500">
                                    No deliveries found
                                </td>
                            </tr>
                        ) : (
                            deliveries.map((delivery) => (
                                <tr key={delivery.id}>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {delivery.customer_name}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        {delivery.address}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        LKR {delivery.cod_amount}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusColor(delivery.status)}`}>
                                            {delivery.status}
                                        </span>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <select
                                            className="border rounded px-2 py-1 text-sm"
                                            value={delivery.status}
                                            onChange={(e) => handleStatusUpdate(delivery.id, e.target.value)}
                                        >
                                            <option value="pending">Pending</option>
                                            <option value="assigned">Assigned</option>
                                            <option value="in_transit">In Transit</option>
                                            <option value="failed">Failed</option>
                                        </select>
                                    </td>
                                </tr>
                            ))
                        )}
                    </tbody>
                </table>
            </div>

            {/* Create Modal */}
            {showCreate && (
                <DeliveryCreate
                    onClose={() => setShowCreate(false)}
                    onSuccess={() => {
                        setShowCreate(false);
                        fetchDeliveries();
                    }}
                />
            )}
        </div>
    );
}

export default DeliveryList;