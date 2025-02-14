"use client"

import { useState } from "react"
import { motion, AnimatePresence } from "framer-motion"
import { FileText, Grid, MessageSquare, PlusCircle, Search, Send, Settings, X } from "lucide-react"

import { Button } from "../components/ui/button"
import { Card } from "../components/ui/card"
import { Input } from "../components/ui/input"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select"

const suggestions = [
  {
    icon: "🎨",
    title: "Help me choose a career path",
    color: "bg-blue-100",
  },
  {
    icon: "📊",
    title: "Analyze my skills and interests",
    color: "bg-pink-100",
  },
  {
    icon: "✍️",
    title: "Suggest learning resources",
    color: "bg-yellow-100",
  },
]

const chatHistory = [
  {
    id: 1,
    title: "Career path exploration",
    preview: "We discussed potential career paths in tech...",
    date: "Today",
    unread: true,
  },
  {
    id: 2,
    title: "Skill assessment",
    preview: "Analyzed your current skillset and suggested improvements...",
    date: "Yesterday",
    unread: false,
  },
  {
    id: 3,
    title: "Learning resource recommendations",
    preview: "Provided a list of courses and books for skill development...",
    date: "Feb 12",
    unread: false,
  },
]

export default function ChatBot() {
  const [message, setMessage] = useState("")
  const [isTyping, setIsTyping] = useState(false)
  const [showHistory, setShowHistory] = useState(false)
  const [searchHistory, setSearchHistory] = useState("")
  const [selectedChat, setSelectedChat] = useState<number | null>(null)

  const handleSend = () => {
    if (!message.trim()) return
    setIsTyping(true)
    setTimeout(() => {
      setIsTyping(false)
    }, 2000)
    setMessage("")
  }

  const filteredHistory = chatHistory.filter(
    (chat) =>
      chat.title.toLowerCase().includes(searchHistory.toLowerCase()) ||
      chat.preview.toLowerCase().includes(searchHistory.toLowerCase()),
  )

  return (
    <div className="flex h-[calc(100vh-4rem)] bg-gray-50">
      {/* Sidebar */}
      <motion.aside
        initial={{ x: -100, opacity: 0 }}
        animate={{ x: 0, opacity: 1 }}
        className="w-16 bg-white border-r flex flex-col items-center py-4 gap-6"
      >
        <Button variant="ghost" size="icon" className="rounded-full">
          <X className="h-5 w-5" />
        </Button>
        <Button variant={showHistory ? "default" : "ghost"} size="icon" onClick={() => setShowHistory(!showHistory)}>
          <MessageSquare className="h-5 w-5" />
        </Button>
        <Button variant="ghost" size="icon">
          <Grid className="h-5 w-5" />
        </Button>
        <Button variant="ghost" size="icon">
          <FileText className="h-5 w-5" />
        </Button>
        <Button variant="ghost" size="icon">
          <Settings className="h-5 w-5" />
        </Button>
      </motion.aside>

      {/* History Sidebar */}
      <AnimatePresence>
        {showHistory && (
          <motion.div
            initial={{ x: -300, opacity: 0 }}
            animate={{ x: 0, opacity: 1 }}
            exit={{ x: -300, opacity: 0 }}
            className="w-80 bg-white border-r overflow-hidden"
          >
            <div className="p-4 border-b">
              <h2 className="text-lg font-semibold mb-4">Chat History</h2>
              <div className="relative">
                <Search className="w-4 h-4 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <Input
                  placeholder="Search conversations..."
                  className="pl-9"
                  value={searchHistory}
                  onChange={(e) => setSearchHistory(e.target.value)}
                />
              </div>
            </div>
            <div className="overflow-auto h-[calc(100vh-9rem)]">
              {filteredHistory.map((chat) => (
                <motion.div
                  key={chat.id}
                  initial={{ x: -20, opacity: 0 }}
                  animate={{ x: 0, opacity: 1 }}
                  whileHover={{ backgroundColor: "rgba(0,0,0,0.05)" }}
                  className={`p-4 border-b cursor-pointer ${selectedChat === chat.id ? "bg-blue-50" : ""}`}
                  onClick={() => setSelectedChat(chat.id)}
                >
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <div className="flex items-center gap-2">
                        <h3 className="font-medium truncate">{chat.title}</h3>
                        {chat.unread && <span className="w-2 h-2 bg-blue-500 rounded-full" />}
                      </div>
                      <p className="text-sm text-gray-500 truncate">{chat.preview}</p>
                    </div>
                    <span className="text-xs text-gray-400">{chat.date}</span>
                  </div>
                </motion.div>
              ))}
            </div>
          </motion.div>
        )}
      </AnimatePresence>

      {/* Main Content */}
      <div className="flex-1 flex flex-col">
        {/* Header */}
        <motion.header
          initial={{ y: -20, opacity: 0 }}
          animate={{ y: 0, opacity: 1 }}
          className="h-14 border-b bg-white flex items-center justify-between px-4"
        >
          <div className="flex items-center gap-2">
            <img src="/logo.svg" alt="Logo" width={24} height={24} className="rounded" />
            <span className="font-semibold">Career Guidance ChatBot</span>
          </div>
          <div className="flex items-center gap-2">
            <Select defaultValue="ai-2">
              <SelectTrigger className="w-32">
                <SelectValue placeholder="Select AI version" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="ai-2">AI Assistant 2.0</SelectItem>
                <SelectItem value="ai-1">AI Assistant 1.0</SelectItem>
              </SelectContent>
            </Select>
            <Button
              variant="default"
              size="sm"
              className="bg-blue-500 hover:bg-blue-600"
              onClick={() => {
                setSelectedChat(null)
                setShowHistory(false)
              }}
            >
              <PlusCircle className="mr-2 h-4 w-4" />
              New Chat
            </Button>
          </div>
        </motion.header>

        {/* Chat Area */}
        <div className="flex-1 overflow-auto p-4">
          {!selectedChat ? (
            <motion.div
              initial={{ scale: 0.9, opacity: 0 }}
              animate={{ scale: 1, opacity: 1 }}
              transition={{ delay: 0.2 }}
              className="max-w-2xl mx-auto text-center pt-20"
            >
              <div className="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <MessageSquare className="w-8 h-8 text-blue-500" />
              </div>
              <h1 className="text-xl text-gray-600 mb-2">Welcome to Career Guidance ChatBot</h1>
              <h2 className="text-3xl font-semibold mb-8">How can I assist you today?</h2>

              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                {suggestions.map((suggestion, index) => (
                  <motion.div
                    key={index}
                    initial={{ y: 20, opacity: 0 }}
                    animate={{ y: 0, opacity: 1 }}
                    transition={{ delay: 0.3 + index * 0.1 }}
                  >
                    <Card className="p-4 cursor-pointer hover:shadow-md transition-shadow">
                      <div className={`w-8 h-8 ${suggestion.color} rounded-lg flex items-center justify-center mb-2`}>
                        {suggestion.icon}
                      </div>
                      <p className="text-sm text-left">{suggestion.title}</p>
                    </Card>
                  </motion.div>
                ))}
              </div>
            </motion.div>
          ) : (
            <motion.div initial={{ opacity: 0 }} animate={{ opacity: 1 }} className="max-w-2xl mx-auto">
              <div className="space-y-4">
                {/* Example chat messages for selected chat */}
                <div className="flex items-start gap-3">
                  <div className="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">🤖</div>
                  <div className="flex-1 bg-white p-4 rounded-lg shadow-sm">
                    <p>Here's the chat history for conversation #{selectedChat}</p>
                  </div>
                </div>
              </div>
            </motion.div>
          )}

          {isTyping && (
            <motion.div
              initial={{ opacity: 0, y: 10 }}
              animate={{ opacity: 1, y: 0 }}
              className="max-w-2xl mx-auto mt-4"
            >
              <div className="flex items-center gap-2 text-sm text-gray-500">
                <div className="w-2 h-2 bg-blue-500 rounded-full animate-bounce" />
                <div className="w-2 h-2 bg-blue-500 rounded-full animate-bounce [animation-delay:0.2s]" />
                <div className="w-2 h-2 bg-blue-500 rounded-full animate-bounce [animation-delay:0.4s]" />
              </div>
            </motion.div>
          )}
        </div>

        {/* Input Area */}
        <motion.div initial={{ y: 20, opacity: 0 }} animate={{ y: 0, opacity: 1 }} className="p-4 border-t bg-white">
          <div className="max-w-2xl mx-auto flex gap-2">
            <Button variant="ghost" size="icon">
              <FileText className="h-5 w-5" />
            </Button>
            <Input
              placeholder="Ask me about career guidance..."
              value={message}
              onChange={(e) => setMessage(e.target.value)}
              onKeyDown={(e) => e.key === "Enter" && handleSend()}
              className="flex-1"
            />
            <Button onClick={handleSend} disabled={!message.trim()}>
              <Send className="h-5 w-5" />
            </Button>
          </div>
          <div className="max-w-2xl mx-auto mt-2">
            <p className="text-sm text-gray-500 text-center">
              Our AI assistant provides career guidance based on current data. Always verify important information.{" "}
              <a href="#" className="underline">
                Learn more about our AI
              </a>
            </p>
          </div>
        </motion.div>
      </div>
    </div>
  )
}

