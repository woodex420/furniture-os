import { createRoot } from "react-dom/client";
import App from "./App.tsx";
import ErrorBoundary from "./components/ErrorBoundary.tsx";
import { initAnalytics } from "./lib/analytics";
import "./index.css";

// Never initialize analytics in the isolated synthetic editor frame.
if (!(window.parent !== window && new URLSearchParams(window.location.search).get('furniture-canvas') === 'document-proof')) {
  initAnalytics();
}

createRoot(document.getElementById("root")!).render(
  <ErrorBoundary>
    <App />
  </ErrorBoundary>
);
