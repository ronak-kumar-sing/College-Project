import { useState } from "react"
import { motion } from "framer-motion"
import { Button } from "../components/ui/button"
import { Input } from "../components/ui/input"
import { Textarea } from "../components/ui/textarea"
import { Card, CardHeader, CardTitle, CardContent } from "../components/ui/card"

export default function AISuggestions() {
  const [skills, setSkills] = useState("")
  const [interests, setInterests] = useState("")
  const [suggestions, setSuggestions] = useState<string[]>([])

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    // In a real application, this would be an API call to the PHP backend
    // For demonstration, we'll use a mock response
    const mockSuggestions = [
      "Web Development",
      "Data Science",
      "UX/UI Design",
      "Digital Marketing",
      "Artificial Intelligence",
    ]
    setSuggestions(mockSuggestions)
  }

  return (
    <div className="max-w-4xl mx-auto px-4 py-8">
      <motion.h1
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="text-3xl font-bold mb-8 text-center"
      >
        AI Career Suggestions
      </motion.h1>
      <motion.form
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ delay: 0.2 }}
        onSubmit={handleSubmit}
        className="space-y-4 mb-8"
      >
        <div>
          <label htmlFor="skills" className="block text-sm font-medium text-gray-700 mb-1">
            Your Skills
          </label>
          <Input
            id="skills"
            value={skills}
            onChange={(e) => setSkills(e.target.value)}
            placeholder="e.g., programming, writing, design"
          />
        </div>
        <div>
          <label htmlFor="interests" className="block text-sm font-medium text-gray-700 mb-1">
            Your Interests
          </label>
          <Textarea
            id="interests"
            value={interests}
            onChange={(e) => setInterests(e.target.value)}
            placeholder="Describe your interests and passions"
            rows={4}
          />
        </div>
        <Button type="submit" className="w-full">
          Get AI Suggestions
        </Button>
      </motion.form>

      {suggestions.length > 0 && (
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ delay: 0.4 }}>
          <h2 className="text-2xl font-semibold mb-4">Suggested Career Paths</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {suggestions.map((suggestion, index) => (
              <Card key={index}>
                <CardHeader>
                  <CardTitle>{suggestion}</CardTitle>
                </CardHeader>
                <CardContent>
                  <p className="text-sm text-gray-600">
                    Based on your skills and interests, this career path could be a great fit for you.
                  </p>
                  <Button variant="outline" className="mt-4">
                    Learn More
                  </Button>
                </CardContent>
              </Card>
            ))}
          </div>
        </motion.div>
      )}
    </div>
  )
}

