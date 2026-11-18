import { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';

// Service to interact with Laravel API
const API_URL = import.meta.env.VITE_API_URL || 'https://api.woodex.com.pk';

export const useLeadService = () => {
  const [leads, setLeads] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchLeads = async (search = '', status = '') => {
    setLoading(true);
    setError(null);
    try {
      const response = await fetch(`${API_URL}/api/v1/leads?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`);
      if (!response.ok) throw new Error('Failed to fetch leads');
      const data = await response.json();
      setLeads(data.data || []);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  return { leads, loading, error, fetchLeads };
};

// Example Component for React Frontend
export const LeadsIndex = () => {
  const { leads, loading, error, fetchLeads } = useLeadService();
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const navigate = useNavigate();

  useEffect(() => {
    fetchLeads(search, status);
  }, [search, status]);

  return (
    <section className="card records-card" aria-labelledby="leads-list-title">
      <div className="records-title">
        <div>
          <h2 id="leads-list-title">All leads</h2>
          <p>Owner access only · Testing</p>
        </div>
        <span className="sample-chip">Testing</span>
      </div>
      <div className="records-tools">
        <label className="records-search">
          <span className="sr-only">Search leads by title</span>
          <input 
            type="search" 
            value={search}
            onChange={(e) => setSearch(e.target.value)}
            placeholder="Search leads..."
            className="border p-2 rounded"
          />
        </label>
        <label className="records-filter">
          <span className="sr-only">Filter leads by status</span>
          <select 
            name="status" 
            value={status}
            onChange={(e) => setStatus(e.target.value)}
            className="border p-2 rounded"
          >
            <option value="">All statuses</option>
            <option value="new">New</option>
            <option value="qualified">Qualified</option>
            <option value="closed">Closed</option>
          </select>
        </label>
      </div>
      
      {error && <p className="customer-validation" role="alert">{error}</p>}
      {loading && <p className="customer-loading" role="status">Updating results…</p>}
      
      <div className="table-wrap" aria-busy={loading}>
        <table>
          <thead>
            <tr>
              <th>Title</th>
              <th>Contact</th>
              <th>Source</th>
              <th>Status</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            {leads.length === 0 ? (
              <tr>
                <td colSpan="5">
                  <div className="list-empty">
                    <strong>No leads found</strong>
                    <p>{search || status ? 'Try another search or filter.' : 'No leads available.'}</p>
                  </div>
                </td>
              </tr>
            ) : (
              leads.map((lead) => (
                <tr key={lead.id}>
                  <td><a className="record-name customer-record-link" href={`/admin/leads/${lead.id}`}>{lead.title}</a></td>
                  <td>{lead.contact?.name || '—'}</td>
                  <td>{lead.source || '—'}</td>
                  <td><span className="record-status">{lead.status}</span></td>
                  <td>{new Date(lead.created_at).toLocaleDateString()}</td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </section>
  );
};
