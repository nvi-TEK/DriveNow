import type { Config } from "tailwindcss";

const config: Config = {
  content: [
    "./pages/**/*.{js,ts,jsx,tsx,mdx}",
    "./components/**/*.{js,ts,jsx,tsx,mdx}",
    "./app/**/*.{js,ts,jsx,tsx,mdx}",
    "./node_modules/flowbite/**/*.js",
  ],
  theme: {
    extend: {
      backgroundImage: {
        "gradient-radial": "radial-gradient(var(--tw-gradient-stops))",
        "gradient-conic":
          "conic-gradient(from 180deg at 50% 50%, var(--tw-gradient-stops))",
      },
      colors: {
        // Neutral, darker replacement for the dark-mode "gray" surfaces/text/borders
        // (default Tailwind gray has a cool blue cast at these steps).
        dm: {
          300: "#B0B0B0",
          400: "#8A8A8A",
          500: "#5C5C5C",
          600: "#404040",
          700: "#2A2A2A",
          800: "#1A1A1A",
        },
      },
    },
  },
  plugins: [require("flowbite/plugin")({
    charts: true,
  })],
  darkMode: 'class',
};
export default config;
