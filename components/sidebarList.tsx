/* eslint-disable require-jsdoc */
import React, { useState, useEffect } from "react";
import { useTheme } from "next-themes";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/router";

type sideBarProp = {
  icon?: any;
  activeIcon?: any;
  darkIcon?: any;
  darkActiveIcon?: any;
  name: string;
  url?: string;
};

function List(props: sideBarProp) {
  const router = useRouter();
  const [mounted, setMounted] = useState(false);
  const { setTheme, resolvedTheme } = useTheme();

  useEffect(() => setMounted(true), []);

  return (
    <li
      className={
        router.pathname == props.url
          ? "bg-[#F1F8FF] dark:bg-gray-600 mx-2 rounded-lg text-[]"
          : ""
      }
    >
      <div className="flex items-center py-3 max-2xl:py-2 mx-2 pl-4 rounded-lg text-base font-medium dark:hover:bg-gray-600 hover:bg-[#F1F8FF]">
        <Image
          src={
            router.pathname == props.url && resolvedTheme == "light"
              ? props.activeIcon
              : router.pathname == props.url && resolvedTheme == "dark"
              ? props.darkActiveIcon
              : router.pathname !== props.url && resolvedTheme == "light"
              ? props.icon
              : props.darkIcon
          }
          className="max-2xl:w-5"
          alt=""
        />
        <span id="sidebar-text"
          className={
            router.pathname == props.url
              ? "flex-1 ml-2 leading-5 whitespace-nowrap text-base max-2xl:text-[15px] dark:text-blue-300 font-normal text-[#007AF5]"
              : "flex-1 ml-2 leading-5 whitespace-nowrap text-base max-2xl:text-[15px] dark:text-white font-normal text-[#262626]"
          }
        >
          {props.name}
        </span>
      </div>
    </li>
  );
}

export default List;
