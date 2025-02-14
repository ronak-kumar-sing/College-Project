import * as React from "react"
import {
  Bell,
  BookOpen,
  ChevronDown,
  LayoutGrid,
  LogOut,
  MessageSquare,
  MoreVertical,
  Settings,
  Users,
} from "lucide-react"

import { Avatar, AvatarFallback, AvatarImage } from "../components/ui/avatar"
import { Button } from "../components/ui/button"
import { Calendar as CalendarComponent } from "../components/ui/calendar"
import { ChartContainer } from "../components/ui/chart"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "../components/ui/select"
import { Card, CardContent, CardHeader, CardTitle } from "../components/ui/card"

export default function DashboardPage() {
  const [date, setDate] = React.useState<Date | undefined>(new Date())
  return (
    <div className="min-h-screen bg-slate-50 flex">
      {/* Sidebar */}
      <aside className="w-16 bg-[#1C1C1C] flex flex-col items-center py-6 gap-8">
        <div className="w-8 h-8 bg-indigo-600 rounded-full flex items-center justify-center">
          <img src="/placeholder-logo.svg" alt="Logo" width={20} height={20} className="invert" />
        </div>
        <nav className="flex flex-col gap-6">
          <Button variant="ghost" size="icon" className="text-gray-400">
            <LayoutGrid className="w-5 h-5" />
          </Button>
          <Button variant="ghost" size="icon" className="text-gray-400">
            <MessageSquare className="w-5 h-5" />
          </Button>
          <Button variant="ghost" size="icon" className="text-gray-400">
            <BookOpen className="w-5 h-5" />
          </Button>
          <Button variant="ghost" size="icon" className="text-gray-400">
            <Users className="w-5 h-5" />
          </Button>
          <Button variant="ghost" size="icon" className="text-gray-400">
            <Settings className="w-5 h-5" />
          </Button>
          <Button variant="ghost" size="icon" className="text-gray-400">
            <Bell className="w-5 h-5" />
          </Button>
        </nav>
        <Button variant="ghost" size="icon" className="text-gray-400 mt-auto">
          <LogOut className="w-5 h-5" />
        </Button>
      </aside>

      {/* Main Content */}
      <main className="flex-1 p-8">
        <div className="max-w-7xl mx-auto">
          {/* Header */}
          <header className="flex justify-between items-center mb-8">
            <div>
              <h1 className="text-2xl font-semibold text-gray-900">Greetings, Karla!</h1>
              <p className="text-gray-500">7 May, 2023</p>
            </div>
            <div className="flex items-center gap-2">
              <Bell className="w-5 h-5 text-gray-400" />
              <Avatar>
                <AvatarImage src="/placeholder.svg" />
                <AvatarFallback>JK</AvatarFallback>
              </Avatar>
              <div className="text-sm">
                <p className="font-medium">John Karla</p>
                <p className="text-gray-500">john@gmail.com</p>
              </div>
            </div>
          </header>

          {/* Stats */}
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <Card>
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <img src="/placeholder.svg" alt="Classes" width={40} height={40} className="rounded-full" />
                <Button variant="ghost" size="icon">
                  <MoreVertical className="w-4 h-4" />
                </Button>
              </CardHeader>
              <CardContent>
                <CardTitle className="text-2xl font-bold">02/08</CardTitle>
                <p className="text-gray-500">Total classes</p>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <img src="/placeholder.svg" alt="Students" width={40} height={40} className="rounded-full" />
                <Button variant="ghost" size="icon">
                  <MoreVertical className="w-4 h-4" />
                </Button>
              </CardHeader>
              <CardContent>
                <CardTitle className="text-2xl font-bold">02/08</CardTitle>
                <p className="text-gray-500">Total Students</p>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <img src="/placeholder.svg" alt="Lessons" width={40} height={40} className="rounded-full" />
                <Button variant="ghost" size="icon">
                  <MoreVertical className="w-4 h-4" />
                </Button>
              </CardHeader>
              <CardContent>
                <CardTitle className="text-2xl font-bold">40/50</CardTitle>
                <p className="text-gray-500">Total Lessons</p>
              </CardContent>
            </Card>
            <Card>
              <CardHeader className="flex flex-row items-center justify-between pb-2">
                <img src="/placeholder.svg" alt="Hours" width={40} height={40} className="rounded-full" />
                <Button variant="ghost" size="icon">
                  <MoreVertical className="w-4 h-4" />
                </Button>
              </CardHeader>
              <CardContent>
                <CardTitle className="text-2xl font-bold">12/20</CardTitle>
                <p className="text-gray-500">Total Hours</p>
              </CardContent>
            </Card>
          </div>

          {/* Main Grid */}
          <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {/* Left Column */}
            <div className="lg:col-span-2 space-y-6">
              {/* Students Performance */}
              <Card>
                <CardHeader className="flex flex-row items-center justify-between">
                  <CardTitle>Students Performance</CardTitle>
                  <Select defaultValue="weekly">
                    <SelectTrigger className="w-32">
                      <SelectValue placeholder="Select period" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="weekly">Weekly</SelectItem>
                      <SelectItem value="monthly">Monthly</SelectItem>
                      <SelectItem value="yearly">Yearly</SelectItem>
                    </SelectContent>
                  </Select>
                </CardHeader>
                <CardContent>
                  {/* Student List */}
                  <div className="space-y-4">
                    {[98, 92, 95, 96, 90].map((score, i) => (
                      <div key={i} className="flex items-center gap-4">
                        <Avatar>
                          <AvatarImage src="/placeholder.svg" />
                          <AvatarFallback>OJ</AvatarFallback>
                        </Avatar>
                        <div className="flex-1">
                          <p className="font-medium">Oliver James</p>
                          <p className="text-sm text-gray-500">All Class - B</p>
                        </div>
                        <p className="font-semibold">{score}%</p>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>

              {/* Attendance Chart */}
              <Card>
                <CardHeader className="flex flex-row items-center justify-between">
                  <CardTitle>Total attendance report</CardTitle>
                  <Select defaultValue="weekly">
                    <SelectTrigger className="w-32">
                      <SelectValue placeholder="Select period" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="weekly">Weekly</SelectItem>
                      <SelectItem value="monthly">Monthly</SelectItem>
                    </SelectContent>
                  </Select>
                </CardHeader>
                <CardContent>
                  <ChartContainer className="h-[200px]">
                    {/* Chart would go here - using a placeholder div */}
                    <div className="w-full h-full bg-gray-50 rounded-lg" />
                  </ChartContainer>
                </CardContent>
              </Card>

              {/* Teaching Lessons */}
              <Card>
                <CardHeader>
                  <CardTitle>Teaching Lessons</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  {[1, 2, 3, 4].map((lesson) => (
                    <div key={lesson} className="flex items-center gap-4 p-4 bg-gray-50 rounded-lg">
                      <div className="w-10 h-10 bg-white rounded-lg flex items-center justify-center">
                        <BookOpen className="w-5 h-5 text-gray-500" />
                      </div>
                      <div className="flex-1">
                        <div className="flex flex-col sm:flex-row sm:justify-between mb-1">
                          <div>
                            <p className="font-medium">Start from</p>
                            <p className="text-sm text-gray-500">Today, 10:30 AM</p>
                          </div>
                          <div>
                            <p className="font-medium">High fidelity wireframes</p>
                            <p className="text-sm text-gray-500">2 lesson • 60 min</p>
                          </div>
                          <div>
                            <p className="font-medium">Mathematics</p>
                          </div>
                        </div>
                      </div>
                      <Button variant="secondary" size="sm">
                        Reminder
                      </Button>
                    </div>
                  ))}
                </CardContent>
              </Card>
            </div>

            {/* Right Column */}
            <div className="space-y-6">
              {/* Calendar */}
              <Card>
                <CardContent className="p-0">
                  <div className="p-4">
                    <div className="flex items-center justify-between mb-4">
                      <h3 className="font-semibold">May 2023</h3>
                      <Button variant="ghost" size="sm">
                        <ChevronDown className="w-4 h-4" />
                      </Button>
                    </div>
                    <CalendarComponent
                      mode="single"
                      selected={date}
                      onSelect={setDate}
                      className="rounded-md"
                      disabled={(date) => date > new Date() || date < new Date("1900-01-01")}
                    />
                  </div>
                </CardContent>
              </Card>

              {/* Upcoming Events */}
              <Card>
                <CardHeader className="flex flex-row items-center justify-between">
                  <CardTitle>Upcoming Events</CardTitle>
                  <Button variant="ghost" size="icon">
                    <MoreVertical className="w-4 h-4" />
                  </Button>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {[
                      { time: "9.00 am", subject: "Biology", duration: "09:00-10:00 am" },
                      { time: "11.00 am", subject: "Chemistry", duration: "09:00-10:00 am" },
                      { time: "1.00 pm", subject: "Physics", duration: "09:00-10:00 am" },
                    ].map((event, i) => (
                      <div key={i} className="flex items-start gap-4">
                        <p className="text-sm font-medium w-20">{event.time}</p>
                        <div className="flex-1">
                          <p className="font-medium">{event.subject}</p>
                          <p className="text-sm text-gray-500">{event.duration}</p>
                        </div>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>

              {/* My Notes */}
              <Card>
                <CardHeader className="flex flex-row items-center justify-between">
                  <div className="flex items-center gap-2">
                    <CardTitle>My Notes</CardTitle>
                    <span className="bg-gray-100 text-xs font-medium px-2 py-1 rounded-full">12</span>
                  </div>
                  <Button size="sm" variant="outline">
                    Add Notes
                  </Button>
                </CardHeader>
                <CardContent>
                  <div className="space-y-4">
                    {[1, 2, 3, 4].map((note) => (
                      <div key={note} className="flex items-start gap-4">
                        <div className="w-2 h-2 rounded-full bg-green-500 mt-2" />
                        <div className="flex-1">
                          <p className="font-medium">Prepare Questions for final test</p>
                          <p className="text-sm text-gray-500">
                            Prepare Questions for final test for the students of class A
                          </p>
                        </div>
                        <Button variant="ghost" size="icon">
                          <MoreVertical className="w-4 h-4" />
                        </Button>
                      </div>
                    ))}
                  </div>
                </CardContent>
              </Card>
            </div>
          </div>
        </div>
      </main>
    </div>
  )
}