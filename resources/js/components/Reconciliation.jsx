import React, { useState } from 'react';
import api from '../api/client';

function Reconciliation() {
    const [date, setDate] = useState(new Date().toISOString().split('T')[0]);
    const [results, setResults] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState('');

    const fetchReconciliation = async () => {
        setLoading(true);
        setError('');
        try {
            const response = await api.get(`/reconciliation/daily?date=${date}`);
            setResults(response.data.results || []);
        } catch (error) {
            setError('Failed to fetch reconciliation data');
            console.error(error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-xl font-bold mb-4">Daily Reconciliation</h2>
            <div className="flex gap-4 mb-4">
                <input
                    type="date"
                    value={date}
                    onChange={(e) => setDate(e.target.value)}
                    className="border rounded-md px-3 py-2 text-sm"
                />
                <button
                    onClick={fetchReconciliation}
                    disabled={loading}
                    className="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-md text-sm disabled:opacity-50"
                >
                    {loading ? 'Loading...' : 'Check Reconciliation'}
                </button>
            </div>

            {error && <div className="text-red-500 text-sm mb-4">{error}</div>}

            {results.length > 0 && (
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200">
                        <thead className="bg-gray-50">
                            <tr>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500">Driver</th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500">Deliveries</th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500">Expected</th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500">Actual</th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500">Discrepancy</th>
                                <th className="px-4 py-2 text-left text-xs font-medium text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody className="bg-white divide-y divide-gray-200">
                            {results.map((result, index) => (
                                <tr key={index}>
                                    <td className="px-4 py-2 text-sm">{result.driver_name}</td>
                                    <td className="px-4 py-2 text-sm">{result.delivery_count}</td>
                                    <td className="px-4 py-2 text-sm">LKR {result.expected_total}</td>
                                    <td className="px-4 py-2 text-sm">LKR {result.actual_collected}</td>
                                    <td className="px-4 py-2 text-sm">LKR {result.discrepancy}</td>
                                    <td className="px-4 py-2 text-sm">
                                        {result.has_discrepancy ? (
                                            <span className="text-red-600 font-bold">⚠️ Discrepancy</span>
                                        ) : (
                                            <span className="text-green-600">✅ Matched</span>
                                        )}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
}

export default Reconciliation;