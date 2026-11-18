import React from 'react';
import ReactDOM from 'react-dom/client';
import { BrowserRouter } from 'react-router-dom';
import './index.css';
import { LeadsIndex } from './pages/LeadsIndex';

function App() {
  return (
    <BrowserRouter>
      <div className="min-h-screen bg-gray-50">
        <LeadsIndex />
      </div>
    </BrowserRouter>
  );
}

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>
);
