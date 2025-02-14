import { useState } from "react"
import { motion } from "framer-motion"
import { Button } from "../components/ui/button"
import { Input } from "../components/ui/input"
import { Textarea } from "../components/ui/textarea"
import { Card, CardHeader, CardTitle, CardContent } from "../components/ui/card"

export default function Interaction() {
  const [query, setQuery] = useState("")
  const [response, setResponse] = useState("")

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    // In a real application, this would be an API call to the PHP backend
    // For demonstration, we'll use a mock response
    const mockResponse =
      `Here's some information about ${query}:\n\n` +
      `1. Key skills required\n` +
      `2. Job market outlook\n` +
      `3. Recommended learning resources\n` +
      `4. Potential career paths`
    setResponse(mockResponse)
  }

  return (
    <div className="max-w-4xl mx-auto px-4 py-8">
      <motion.h1
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="text-3xl font-bold mb-8 text-center"
      >
        Career Interaction Hub
      </motion.h1>
      <motion.form
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ delay: 0.2 }}
        onSubmit={handleSubmit}
        className="space-y-4 mb-8"
      >
        <div>
          <label htmlFor="query" className="block text-sm font-medium text-gray-700 mb-1">
            Ask about a career or skill
          </label>
          <Input
            id="query"
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="e.g., What skills do I need for web development?"
          />
        </div>
        <Button type="submit" className="w-full">
          Get Information
        </Button>
      </motion.form>

      {response && (
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.4 }}>
          <Card>
            <CardHeader>
              <CardTitle>AI Response</CardTitle>
            </CardHeader>
            <CardContent>
              <Textarea value={response} readOnly rows={10} className="w-full" />
            </CardContent>
          </Card>
        </motion.div>
      )}
    </div>
  )
}

