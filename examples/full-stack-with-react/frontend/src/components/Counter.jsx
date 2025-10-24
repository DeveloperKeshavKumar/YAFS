import { useState } from 'react'

function Counter() {
  const [count, setCount] = useState(0)
  
  return (
    <div style={{ textAlign: 'center', padding: '2rem' }}>
      <p>Count: {count}</p>
      <button 
        onClick={() => setCount(count + 1)}
        style={{ 
          padding: '0.5rem 1rem', 
          fontSize: '1rem', 
          cursor: 'pointer',
          background: '#2563eb',
          color: 'white',
          border: 'none',
          borderRadius: '0.5rem'
        }}
      >
        Increment
      </button>
    </div>
  )
}

export default Counter