import React, { useState, useEffect } from 'react';
import api from '../api/client';

function RouteGenerator() {
    const [deliveries, setDeliveries] = useState([]);
    const [selectedDeliveries, setSelectedDeliveries] = useState([]);
    const [drivers, setDrivers] = useState([]);
    const [selectedDriver, setSelectedDriver] = useState('');
    const [loading, setLoading] = useState(false);
    const [route, setRoute] = useState(null);
    const [error, setError] = useState('');
    const [loadingDrivers, setLoadingDrivers] = useState(false);

    useEffect(() => {
        fetchPendingDeliveries();
        fetchDrivers();
    }, []);

    const fetchPendingDeliveries = async () => {
        try {
            const response = await api.get('/deliveries?status=pending');
            setDeliveries(response.data.data || []);
        } catch (error) {
            console.error('Error fetching deliveries:', error);
        }
    };

    const fetchDrivers = async () => {
        setLoadingDrivers(true);
        try {
            // 👇 Real API call
            const response = await api.get('/drivers');
            setDrivers(response.data || []);
            
            // If no drivers found, show a message
            if (response.data.length === 0) {
                console.warn('No drivers found. Please register a driver first.');
            }
        } catch (error) {
            console.error('Error fetching drivers:', error);
            // Fallback: empty array
            setDrivers([]);
        } finally {
            setLoadingDrivers(false);
        }
    };

    const toggleDelivery = (id) => {
        setSelectedDeliveries(prev =>
            prev.includes(id)
                ? prev.filter(d => d !== id)
                : [...prev, id]
        );
    };

    const generateRoute = async () => {
        if (selectedDeliveries.length < 2) {
            setError('Please select at least 2 deliveries');
            return;
        }
        if (!selectedDriver) {
            setError('Please select a driver');
            return;
        }

        setLoading(true);
        setError('');

        try {
            const response = await api.post('/routes/generate', {
                delivery_ids: selectedDeliveries,
                driver_id: parseInt(selectedDriver),
                start_lat: 6.9271,
                start_lng: 79.8612,
            });
            setRoute(response.data);
            setSelectedDeliveries([]);
            fetchPendingDeliveries();
        } catch (error) {
            setError(error.response?.data?.error || 'Failed to generate route');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div>
            <div className="bg-white rounded-lg shadow p-6">
                <h2 className="text-xl font-bold mb-4">Generate Route</h2>

                {/* Select Driver */}
                <div className="mb-4">
                    <label className="block text-sm font-medium text-gray-700 mb-1">Select Driver</label>
                    {loadingDrivers ? (
                        <div className="text-sm text-gray-500">Loading drivers...</div>
                    ) : (
                        <select
                            className="w-full md:w-64 border rounded-md px-3 py-2 text-sm"
                            value={selectedDriver}
                            onChange={(e) => setSelectedDriver(e.target.value)}
                        >
                            <option value="">Select a driver...</option>
                            {drivers.map(driver => (
                                <option key={driver.id} value={driver.id}>
                                    {driver.name} {driver.phone ? `(${driver.phone})` : ''}
                                </option>
                            ))}
                        </select>
                    )}
                    {drivers.length === 0 && !loadingDrivers && (
                        <div className="text-sm text-yellow-600 mt-1">
                            ⚠️ No drivers found. Please register a driver first.
                        </div>
                    )}
                </div>

                {/* Select Deliveries */}
                <div className="mb-4">
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                        Select Deliveries ({selectedDeliveries.length} selected)
                    </label>
                    <div className="max-h-60 overflow-y-auto border rounded-md">
                        {deliveries.length === 0 ? (
                            <div className="p-4 text-gray-500 text-sm">No pending deliveries available</div>
                        ) : (
                            deliveries.map(delivery => (
                                <label key={delivery.id} className="flex items-center p-3 hover:bg-gray-50 border-b last:border-0">
                                    <input
                                        type="checkbox"
                                        checked={selectedDeliveries.includes(delivery.id)}
                                        onChange={() => toggleDelivery(delivery.id)}
                                        className="mr-3"
                                    />
                                    <div>
                                        <div className="font-medium">{delivery.customer_name}</div>
                                        <div className="text-sm text-gray-500">{delivery.address}</div>
                                        <div className="text-sm text-gray-500">COD: LKR {delivery.cod_amount}</div>
                                    </div>
                                </label>
                            ))
                        )}
                    </div>
                </div>

                {error && (
                    <div className="mb-4 text-red-500 text-sm">{error}</div>
                )}

                <button
                    onClick={generateRoute}
                    disabled={loading || selectedDeliveries.length < 2 || !selectedDriver}
                    className="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-md text-sm disabled:opacity-50"
                >
                    {loading ? 'Generating...' : 'Generate Route'}
                </button>

                {/* Route Result */}
                {route && (
                    <div className="mt-6 border-t pt-4">
                        <h3 className="font-bold text-lg mb-2">Route Generated! 🎉</h3>
                        <div className="grid grid-cols-2 gap-4 mb-4">
                            <div className="bg-gray-50 p-3 rounded">
                                <span className="text-sm text-gray-500">Total Distance</span>
                                <div className="font-bold">{route.total_distance_km} km</div>
                            </div>
                            <div className="bg-gray-50 p-3 rounded">
                                <span className="text-sm text-gray-500">Total Duration</span>
                                <div className="font-bold">{route.total_duration_min} min</div>
                            </div>
                        </div>
                        <div className="bg-blue-50 p-3 rounded mb-4">
                            <p className="text-sm text-blue-800">{route.ai_summary}</p>
                        </div>
                        <div className="max-h-60 overflow-y-auto">
                            {route.ordered_stops.map((stop, index) => (
                                <div key={index} className="flex items-start p-2 border-b last:border-0">
                                    <span className="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center text-sm font-bold mr-3 flex-shrink-0">
                                        {index + 1}
                                    </span>
                                    <div>
                                        <div className="font-medium">{stop.customer_name}</div>
                                        <div className="text-sm text-gray-500">{stop.address}</div>
                                        <div className="text-sm text-gray-500">
                                            {stop.distance_from_prev_km} km · {stop.duration_from_prev_min} min
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}

export default RouteGenerator;