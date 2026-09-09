// import './bootstrap';
// import './echo';
// import React from 'react';
// import ReactDOM from 'react-dom/client';
// import App from './components/App';
// import '../css/app.css';

// // Mount React app
// const rootElement = document.getElementById('app');
// if (rootElement) {
//     const root = ReactDOM.createRoot(rootElement);
//     root.render(
//         <React.StrictMode>
//             <App />
//         </React.StrictMode>
//     );
// }

import './bootstrap';
import './echo';
import React from 'react';
import ReactDOM from 'react-dom/client';
import App from './components/App';
import '../css/app.css';

const rootElement = document.getElementById('app');
if (rootElement) {
    const root = ReactDOM.createRoot(rootElement);
    // 👇 StrictMode ඉවත් කරලා test කරන්න
    root.render(<App />);
}