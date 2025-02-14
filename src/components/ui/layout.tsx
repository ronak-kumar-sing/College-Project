import { Link } from "react-router-dom"
import { Button } from "./button"
import type React from "react" // Added import for React

export function Layout({ children }: { children: React.ReactNode }) {
  return (
    <div className="min-h-screen bg-gray-50 flex flex-col">
      <header className="bg-white shadow-sm">
        <nav className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
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
              </div>
            </div>
            <div className="hidden sm:ml-6 sm:flex sm:items-center">
              <Button variant="outline">Sign In</Button>
            </div>
          </div>
        </nav>
      </header>
      <main className="flex-grow">{children}</main>
      <footer className="bg-white border-t">
        <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
          <p className="text-center text-sm text-gray-500">© 2025 Career Guidance Platform. All rights reserved.</p>
        </div>
      </footer>
    </div>
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

