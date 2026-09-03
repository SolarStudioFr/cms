// Module Federation requires an async boundary before any `shared` module
// (react, react-dom, react-router-dom) is consumed, so the actual app code
// lives in ./bootstrap.jsx, loaded here via a dynamic import.
import('./bootstrap');
