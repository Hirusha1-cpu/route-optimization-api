import React, { useState, useEffect } from 'react';
import api from '../api/client';

function AuditLogs() {
    const [logs, setLogs] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchAuditLogs();
    }, []);

    const fetchAuditLogs = async () => {
        try {
            const response = await api.get('/audit-logs');
            setLogs(response.data.data || []);
        } catch (error) {
            console.error('Error fetching audit logs:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return <div className="text-center py-8">Loading audit logs...</div>;
    }

    return (
        <div className="bg-white rounded-lg shadow p-6">
            <h2 className="text-xl font-bold mb-4">Audit Logs</h2>
            <div className="overflow-x-auto">
                <table className="min-w-full divide-y divide-gray-200">
                    <thead className="bg-gray-50">
                        <tr>
                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500">Time</th>
                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500">Action</th>
                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500">Subject</th>
                            <th className="px-4 py-2 text-left text-xs font-medium text-gray-500">Details</th>
                        </tr>
                    </thead>
                    <tbody className="bg-white divide-y divide-gray-200">
                        {logs.map((log) => (
                            <tr key={log.id}>
                                <td className="px-4 py-2 text-sm">
                                    {new Date(log.created_at).toLocaleString()}
                                </td>
                                <td className="px-4 py-2 text-sm">
                                    <span className="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                                        {log.action}
                                    </span>
                                </td>
                                <td className="px-4 py-2 text-sm">{log.subject_type}</td>
                                <td className="px-4 py-2 text-sm">
                                    <pre className="text-xs bg-gray-50 p-2 rounded overflow-x-auto">
                                        {JSON.stringify(log.details, null, 2)}
                                    </pre>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

export default AuditLogs;