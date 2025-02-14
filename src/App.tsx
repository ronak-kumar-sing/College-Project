import { BrowserRouter as Router, Route, Routes } from "react-router-dom"
import Navbar from "./components/Navbar"
import Home from "./pages/Home"
import ChatBot from "./pages/ChatBot"
import AISuggestions from "./pages/AISuggestions"
import Interaction from "./pages/Interaction"
import JobTrends from "./pages/JobTrends"
import MockTests from "./pages/MockTests"
import Dashboard from "./pages/Dashboard"

function App() {
  return (
    <Router>
      <div className="min-h-screen bg-gray-50">
        <Navbar />
        <div className="pt-16">
          <Routes>
            <Route path="/" element={<Home />} />
            <Route path="/chatbot" element={<ChatBot />} />
            <Route path="/ai-suggestions" element={<AISuggestions />} />
            <Route path="/interaction" element={<Interaction />} />
            <Route path="/job-trends" element={<JobTrends />} />
            <Route path="/mock-tests" element={<MockTests />} />
            <Route path="/dashboard" element={<Dashboard />} />
          </Routes>
        </div>
      </div>
    </Router>
  )
}

export default App

