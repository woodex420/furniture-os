import React from 'react';
import { BrowserRouter } from 'react-router-dom';
import './index.css';
import { LeadsIndex } from './pages/LeadsIndex';

export default function App() {
  return (
    <BrowserRouter>
      <div className="min-h-screen bg-gray-50">
        <LeadsIndex />
      </div>
    </BrowserRouter>
  );
}
