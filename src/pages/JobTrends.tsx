"use client"

import { useState, useEffect } from "react"
import { motion } from "framer-motion"
import { Button } from "../components/ui/button"
import { Input } from "../components/ui/input"
import { Card, CardHeader, CardTitle, CardContent } from "../components/ui/card"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select"

type Job = {
  id: number
  title: string
  company: string
  location: string
  salary: string
  description: string
}

const mockJobs: Job[] = [
  {
    id: 1,
    title: "Frontend Developer",
    company: "TechCorp",
    location: "Remote",
    salary: "$80,000 - $120,000",
    description: "We're looking for an experienced frontend developer...",
  },
  {
    id: 2,
    title: "Data Scientist",
    company: "DataCo",
    location: "New York, NY",
    salary: "$100,000 - $150,000",
    description: "Join our data science team to work on cutting-edge projects...",
  },
  {
    id: 3,
    title: "UX Designer",
    company: "DesignHub",
    location: "San Francisco, CA",
    salary: "$90,000 - $130,000",
    description: "We need a creative UX designer to help shape our product...",
  },
]

export default function JobTrends() {
  const [jobs, setJobs] = useState<Job[]>([])
  const [search, setSearch] = useState("")
  const [filter, setFilter] = useState("all")

  useEffect(() => {
    // In a real application, this would be an API call to the PHP backend
    // For demonstration, we'll use the mock data
    setJobs(mockJobs)
  }, [])

  const filteredJobs = jobs
    .filter(
      (job) =>
        job.title.toLowerCase().includes(search.toLowerCase()) ||
        job.company.toLowerCase().includes(search.toLowerCase()) ||
        job.location.toLowerCase().includes(search.toLowerCase()),
    )
    .filter((job) => filter === "all" || job.location.toLowerCase().includes(filter))

  return (
    <div className="max-w-4xl mx-auto px-4 py-8">
      <motion.h1
        initial={{ opacity: 0, y: -20 }}
        animate={{ opacity: 1, y: 0 }}
        className="text-3xl font-bold mb-8 text-center"
      >
        Job Trends
      </motion.h1>
      <motion.div
        initial={{ opacity: 0 }}
        animate={{ opacity: 1 }}
        transition={{ delay: 0.2 }}
        className="mb-8 flex gap-4"
      >
        <Input
          placeholder="Search jobs..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="flex-1"
        />
        <Select value={filter} onValueChange={setFilter}>
          <SelectTrigger className="w-[180px]">
            <SelectValue placeholder="Filter by location" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="all">All Locations</SelectItem>
            <SelectItem value="remote">Remote</SelectItem>
            <SelectItem value="new york">New York</SelectItem>
            <SelectItem value="san francisco">San Francisco</SelectItem>
          </SelectContent>
        </Select>
      </motion.div>

      <motion.div
        initial={{ opacity: 0, y: 20 }}
        animate={{ opacity: 1, y: 0 }}
        transition={{ delay: 0.4 }}
        className="space-y-4"
      >
        {filteredJobs.map((job) => (
          <Card key={job.id}>
            <CardHeader>
              <CardTitle>{job.title}</CardTitle>
            </CardHeader>
            <CardContent>
              <p className="text-sm text-gray-600 mb-2">
                {job.company} • {job.location}
              </p>
              <p className="text-sm font-semibold mb-2">{job.salary}</p>
              <p className="text-sm text-gray-700 mb-4">{job.description}</p>
              <Button variant="outline">Apply Now</Button>
            </CardContent>
          </Card>
        ))}
      </motion.div>
    </div>
  )
}

