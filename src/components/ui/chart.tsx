import type React from "react"

interface ChartContainerProps {
  children: React.ReactNode
  className?: string
}

export const ChartContainer: React.FC<ChartContainerProps> = ({ children, className }) => {
  return <div className={`relative ${className}`}>{children}</div>
}

