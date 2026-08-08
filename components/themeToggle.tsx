"use client";

import DarkModeIcon from "@mui/icons-material/DarkMode";
import WbSunnyIcon from "@mui/icons-material/WbSunny";
import { useState, useEffect } from "react";
import { useTheme } from "next-themes";
import Image from "next/image";
import Avatar from "../assets/Avatar.svg"

export default function ThemeToggle() {
  const [mounted, setMounted] = useState(false);
  const { setTheme, resolvedTheme } = useTheme();

  useEffect(() => setMounted(true), []);

  if (!mounted)
    return (
      <Image
        src={Avatar}
        width={36}
        height={36}
        sizes="36x36"
        alt="Loading Light/Dark Toggle"
        priority={false}
        title="Loading Light/Dark Toggle"
        className="cursor-pointer "
      />
    );


    
  if (resolvedTheme === "dark") {
    return <WbSunnyIcon className="cursor-pointer text-white" onClick={() => setTheme("light")} />;
  }

  if (resolvedTheme === "light") {
    return <DarkModeIcon className="cursor-pointer text-black" onClick={() => setTheme("dark")} />;
  }
}
