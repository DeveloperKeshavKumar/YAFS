import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App'
import './index.css'

const props = window.__YAFS_PROPS__ || {}

ReactDOM.createRoot(document.getElementById('root')).render(
  <React.StrictMode>
    <App {...props} />
  </React.StrictMode>
)