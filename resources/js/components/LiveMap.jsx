import React, { useState, useEffect } from 'react';
import { MapContainer, TileLayer, Marker, Popup, Polyline } from 'react-leaflet';
import 'leaflet/dist/leaflet.css';
import L from 'leaflet';
import api from '../api/client';

// Fix marker icons
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon-2x.png',
    iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
});

// Custom driver icon
const driverIcon = L.divIcon({
    className: 'driver-marker',
    html: '🚚',
    iconSize: [30, 30],
    iconAnchor: [15, 15],
});

function LiveMap({ user }) {
    const [driverLocations, setDriverLocations] = useState({});
    const [deliveries, setDeliveries] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        fetchDeliveries();

        // Listen for WebSocket events
        if (window.Echo && user?.company_id) {
            window.Echo.private(`company.${user.company_id}.drivers`)
                .listen('driver.location.updated', (event) => {
                    setDriverLocations(prev => ({
                        ...prev,
                        [event.driver_id]: {
                            lat: event.lat,
                            lng: event.lng,
                            timestamp: event.timestamp
                        }
                    }));
                });
        }

        return () => {
            if (window.Echo && user?.company_id) {
                window.Echo.leave(`company.${user.company_id}.drivers`);
            }
        };
    }, [user]);

    const fetchDeliveries = async () => {
        try {
            const response = await api.get('/deliveries');
            setDeliveries(response.data.data || []);
        } catch (error) {
            console.error('Error fetching deliveries:', error);
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return <div className="text-center py-8">Loading map...</div>;
    }

    return (
        <div className="bg-white rounded-lg shadow overflow-hidden">
            <MapContainer
                center={[6.9271, 79.8612]}
                zoom={13}
                className="h-[500px] w-full"
                style={{ height: '500px', width: '100%' }}
            >
                <TileLayer
                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                />
                
                {/* Deliveries */}
                {deliveries.map((delivery) => (
                    <Marker
                        key={delivery.id}
                        position={[delivery.lat, delivery.lng]}
                    >
                        <Popup>
                            <div className="text-sm">
                                <strong>{delivery.customer_name}</strong><br />
                                {delivery.address}<br />
                                <span className={`inline-block px-2 py-1 rounded text-xs ${
                                    delivery.status === 'delivered' ? 'bg-green-100 text-green-800' :
                                    delivery.status === 'pending' ? 'bg-yellow-100 text-yellow-800' :
                                    delivery.status === 'in_transit' ? 'bg-blue-100 text-blue-800' :
                                    'bg-gray-100 text-gray-800'
                                }`}>
                                    {delivery.status}
                                </span><br />
                                COD: LKR {delivery.cod_amount}
                            </div>
                        </Popup>
                    </Marker>
                ))}

                {/* Drivers */}
                {Object.entries(driverLocations).map(([driverId, location]) => (
                    <Marker
                        key={driverId}
                        position={[location.lat, location.lng]}
                        icon={driverIcon}
                    >
                        <Popup>
                            <div className="text-sm">
                                🚚 Driver #{driverId}<br />
                                Last update: {new Date(location.timestamp).toLocaleTimeString()}
                            </div>
                        </Popup>
                    </Marker>
                ))}

                {/* Route lines */}
                {deliveries.filter(d => d.status !== 'delivered').length > 1 && (
                    <Polyline
                        positions={deliveries
                            .filter(d => d.status !== 'delivered')
                            .map(d => [d.lat, d.lng])}
                        color="blue"
                        weight={2}
                        opacity={0.5}
                        dashArray="5, 10"
                    />
                )}
            </MapContainer>
        </div>
    );
}

export default LiveMap;