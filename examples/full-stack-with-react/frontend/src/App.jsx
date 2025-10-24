import { useState, useEffect } from 'react'
import Counter from './components/Counter'
import './App.css'

function App({ title = 'YAFS', message = 'React + PHP' }) {
  const [data, setData] = useState(null)
  
  useEffect(() => {
    fetch('/api/hello')
      .then(res => res.json())
      .then(data => {
        console.log('API Hello:', data)
        setData(data.message)
      })
      .catch(err => console.error('Error:', err))
  }, [])
  
  return (
    <div className="app">
      <h1>{title}</h1>
      {data && <h3>{data}</h3>}
      <p>{message}</p>
      <Counter />
    </div>
  )
}

export default App