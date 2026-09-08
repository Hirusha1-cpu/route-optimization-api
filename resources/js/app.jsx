import './bootstrap';
import './echo';
import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './components/App';
import '../css/app.css';

// Mount React app
const rootElement = document.getElementById('app');
if (rootElement) {
    const root = ReactDOM.createRoot(rootElement);
    root.render(
        <React.StrictMode>
            <App />
        </React.StrictMode>
    );
}