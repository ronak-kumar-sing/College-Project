import { motion } from "framer-motion"

export default function Home() {
  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <motion.div initial={{ opacity: 0, y: 20 }} animate={{ opacity: 1, y: 0 }} transition={{ duration: 0.5 }}>
        <h1 className="text-4xl font-bold text-center mb-8">Welcome to Career Guidance Platform</h1>
        <p className="text-xl text-center mb-12">
          Discover your ideal career path with AI-powered insights and expert guidance.
        </p>
      </motion.div>

      <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
        <motion.div
          initial={{ opacity: 0, x: -20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.5, delay: 0.2 }}
          className="bg-white p-6 rounded-lg shadow-md"
        >
          <h2 className="text-2xl font-semibold mb-4">About Our Platform</h2>
          <p className="text-gray-600">
            Our Career Guidance Platform leverages cutting-edge AI technology to analyze your skills, interests, and
            market trends. We provide personalized career suggestions, learning resources, and insights to help you make
            informed decisions about your professional future.
          </p>
        </motion.div>

        <motion.div
          initial={{ opacity: 0, x: 20 }}
          animate={{ opacity: 1, x: 0 }}
          transition={{ duration: 0.5, delay: 0.4 }}
          className="bg-white p-6 rounded-lg shadow-md"
        >
          <h2 className="text-2xl font-semibold mb-4">About the Developer</h2>
          <p className="text-gray-600">
            Hi, I'm John Doe, a passionate full-stack developer with expertise in AI and career development. I created
            this platform to bridge the gap between technology and career guidance, helping individuals find their true
            calling in the ever-evolving job market.
          </p>
        </motion.div>
      </div>
    </div>
  )
}

