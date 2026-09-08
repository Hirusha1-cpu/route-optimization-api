import React, { useState } from 'react';
import api from '../api/client';

function DeliveryCreate({ onClose, onSuccess }) {
    const [formData, setFormData] = useState({
        customer_name: '',
        address: '',
        lat: '',
        lng: '',
        window_start: '09:00',
        window_end: '10:00',
        cod_amount: '',
    });
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const handleChange = (e) => {
        setFormData({
            ...formData,
            [e.target.name]: e.target.value
        });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setError('');

        try {
            await api.post('/deliveries', {
                ...formData,
                lat: parseFloat(formData.lat),
                lng: parseFloat(formData.lng),
                cod_amount: parseFloat(formData.cod_amount),
            });
            onSuccess();
        } catch (error) {
            setError(error.response?.data?.message || 'Failed to create delivery');
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div className="flex justify-between items-center mb-4">
                    <h3 className="text-lg font-bold">Create New Delivery</h3>
                    <button onClick={onClose} className="text-gray-500 hover:text-gray-700">×</button>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="space-y-3">
                        <input
                            type="text"
                            name="customer_name"
                            required
                            className="w-full px-3 py-2 border rounded-md text-sm"
                            placeholder="Customer Name"
                            value={formData.customer_name}
                            onChange={handleChange}
                        />
                        <input
                            type="text"
                            name="address"
                            required
                            className="w-full px-3 py-2 border rounded-md text-sm"
                            placeholder="Address"
                            value={formData.address}
                            onChange={handleChange}
                        />
                        <div className="grid grid-cols-2 gap-2">
                            <input
                                type="number"
                                name="lat"
                                required
                                step="any"
                                className="w-full px-3 py-2 border rounded-md text-sm"
                                placeholder="Latitude"
                                value={formData.lat}
                                onChange={handleChange}
                            />
                            <input
                                type="number"
                                name="lng"
                                required
                                step="any"
                                className="w-full px-3 py-2 border rounded-md text-sm"
                                placeholder="Longitude"
                                value={formData.lng}
                                onChange={handleChange}
                            />
                        </div>
                        <div className="grid grid-cols-2 gap-2">
                            <input
                                type="time"
                                name="window_start"
                                required
                                className="w-full px-3 py-2 border rounded-md text-sm"
                                value={formData.window_start}
                                onChange={handleChange}
                            />
                            <input
                                type="time"
                                name="window_end"
                                required
                                className="w-full px-3 py-2 border rounded-md text-sm"
                                value={formData.window_end}
                                onChange={handleChange}
                            />
                        </div>
                        <input
                            type="number"
                            name="cod_amount"
                            required
                            step="0.01"
                            className="w-full px-3 py-2 border rounded-md text-sm"
                            placeholder="COD Amount (LKR)"
                            value={formData.cod_amount}
                            onChange={handleChange}
                        />
                    </div>

                    {error && (
                        <div className="mt-2 text-red-500 text-sm">{error}</div>
                    )}

                    <div className="mt-4 flex space-x-2">
                        <button
                            type="submit"
                            disabled={loading}
                            className="flex-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm disabled:opacity-50"
                        >
                            {loading ? 'Creating...' : 'Create Delivery'}
                        </button>
                        <button
                            type="button"
                            onClick={onClose}
                            className="flex-1 bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md text-sm"
                        >
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
}

export default DeliveryCreate;