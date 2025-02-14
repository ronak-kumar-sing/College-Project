"use client"

import { useState } from "react"
import { motion } from "framer-motion"
import { Button } from "../components/ui/button"
import { Card, CardHeader, CardTitle, CardContent } from "../components/ui/card"
import { RadioGroup, RadioGroupItem } from "../components/ui/radio-group"
import { Label } from "../components/ui/label"

type Question = {
  id: number
  text: string
  options: string[]
  correctAnswer: number
}

const mockQuestions: Question[] = [
  {
    id: 1,
    text: "What is the primary purpose of version control systems like Git?",
    options: [
      "To make backups of code",
      "To track changes and collaborate on code",
      "To compile code faster",
      "To automatically fix bugs in code",
    ],
    correctAnswer: 1,
  },
  {
    id: 2,
    text: "Which of the following is NOT a JavaScript framework or library?",
    options: ["React", "Angular", "Vue", "Python"],
    correctAnswer: 3,
  },
  {
    id: 3,
    text: "Which data structure uses LIFO (Last In, First Out) principle?",
    options: ["Queue", "Stack", "Linked List", "Tree"],
    correctAnswer: 1,
  },
]

export default function MockTests() {
  const [currentQuestion, setCurrentQuestion] = useState(0)
  const [selectedAnswers, setSelectedAnswers] = useState<number[]>([])
  const [showResults, setShowResults] = useState(false)

  const handleAnswer = (answer: number) => {
    const newAnswers = [...selectedAnswers]
    newAnswers[currentQuestion] = answer
    setSelectedAnswers(newAnswers)
  }

  const handleNext = () => {
    if (currentQuestion < mockQuestions.length - 1) {
      setCurrentQuestion(currentQuestion + 1)
    } else {
      setShowResults(true)
    }
  }

  const calculateScore = () => {
    return selectedAnswers.reduce((score, answer, index) => {
      return answer === mockQuestions[index].correctAnswer ? score + 1 : score
    }, 0)
  }

  return (
    <div className="max-w-4xl mx-auto px-4 py-8">
      <motion.h1
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="text-3xl font-bold mb-8 text-center"
      >
        Mock Test: Web Development
      </motion.h1>

      {!showResults ? (
        <motion.div
          key={currentQuestion}
          initial={{ opacity: 0, x: 50 }}
          animate={{ opacity: 1, x: 0 }}
          exit={{ opacity: 0, x: -50 }}
        >
          <Card>
            <CardHeader>
              <CardTitle>
                Question {currentQuestion + 1} of {mockQuestions.length}
              </CardTitle>
            </CardHeader>
            <CardContent>
              <p className="mb-4">{mockQuestions[currentQuestion].text}</p>
              <RadioGroup
                value={selectedAnswers[currentQuestion]?.toString()}
                onValueChange={(value) => handleAnswer(Number.parseInt(value))}
              >
                {mockQuestions[currentQuestion].options.map((option, index) => (
                  <div key={index} className="flex items-center space-x-2">
                    <RadioGroupItem value={index.toString()} id={`option-${index}`} />
                    <Label htmlFor={`option-${index}`}>{option}</Label>
                  </div>
                ))}
              </RadioGroup>
              <Button onClick={handleNext} className="mt-4">
                {currentQuestion === mockQuestions.length - 1 ? "Finish" : "Next"}
              </Button>
            </CardContent>
          </Card>
        </motion.div>
      ) : (
        <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }}>
          <Card>
            <CardHeader>
              <CardTitle>Test Results</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-xl mb-4">
                Your score: {calculateScore()} out of {mockQuestions.length}
              </p>
              <Button
                onClick={() => {
                  setCurrentQuestion(0)
                  setSelectedAnswers([])
                  setShowResults(false)
                }}
              >
                Retake Test
              </Button>
            </CardContent>
          </Card>
        </motion.div>
      )}
    </div>
  )
}

