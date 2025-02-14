import { Link } from "react-router-dom"
import { Button } from "../components/ui/button"
import type React from "react" // Added import for React

export default function Navbar() {
  return (
    <nav className="bg-white shadow-sm fixed top-0 left-0 right-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16">
          <div className="flex">
            <Link to="/" className="flex-shrink-0 flex items-center">
              <img className="h-8 w-auto" src="/logo.svg" alt="Career Guidance" />
            </Link>
            <div className="hidden sm:ml-6 sm:flex sm:space-x-8">
              <NavLink to="/">Home</NavLink>
              <NavLink to="/chatbot">ChatBot</NavLink>
              <NavLink to="/ai-suggestions">AI Suggestions</NavLink>
              <NavLink to="/interaction">Interaction</NavLink>
              <NavLink to="/job-trends">Job Trends</NavLink>
              <NavLink to="/mock-tests">Mock Tests</NavLink>
              <NavLink to="/dashboard">Dashboard</NavLink>
            </div>
          </div>
          <div className="hidden sm:ml-6 sm:flex sm:items-center">
            <Button variant="outline">Sign In</Button>
          </div>
        </div>
      </div>
    </nav>
  )
}

function NavLink({ to, children }: { to: string; children: React.ReactNode }) {
  return (
    <Link
      to={to}
      className="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium"
    >
      {children}
    </Link>
  )
}

