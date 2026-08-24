import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './styles.css'

function App() {
  return (
    <main>
      <h1>TP Final ERP</h1>
      <p>Frontend React para las vistas.</p>
    </main>
  )
}

createRoot(document.getElementById('root')).render(
  <StrictMode>
    <App />
  </StrictMode>,
)
