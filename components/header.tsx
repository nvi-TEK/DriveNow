/* eslint-disable require-jsdoc */
import React, { useState, useEffect } from "react";
import Image from "next/image";
import { useTheme } from "next-themes";
import LongMenu from "./headerDropdown";
import logo from "../assets/DriveNow.svg";
import bell from "../assets/bell.svg";
import avatar from "../assets/Avatar.svg";
import Link from "next/link";

type pageProp = {
  name: string;
  secondName?: string;
};

function Header(prop: pageProp) {
  const [mounted, setMounted] = useState(false);
  const { setTheme, resolvedTheme } = useTheme();

  useEffect(() => setMounted(true), []);

  return (
    <div className="bg-white dark:border-gray-700 border-b z-20">
      <div className="flex w-full h-[4rem] max-2xl:h-[55px] shadow-[0px_4px_16px_0px_#0000001A] items-center dark:bg-gray-700 dark:border-gray-500 border-[#E6E6E6] pr-4 bg-white">
        <Link href={"/views/dashboard"}>
          <Image
            src={logo}
            alt={"DriveNow logo"}
            className=" ml-[62.5px] h-[18px]"
          />
        </Link>

        <p className="text-[#595959] ml-[6%] mr-auto leading-[18px] font-normal">
          <Link className="text-[#007AF5]" href={"/views/dashboard"}>
            Dashboard
          </Link>

          <span
            style={{ color: prop.secondName ? "#595959" : "#8C8C8C" }}
            className="leading-[18px] font-normal"
          >
            <span className="text-[#D9D9D9]"> / </span> {prop.name}
          </span>
          <span className="text-[#262626] leading-[18px] font-normal">
            {prop.secondName}
          </span>
        </p>

        <div className="flex w-[550px] justify-end items-center">
          {/* Search bar */}
          <form>
            <div className="relative">
              <div className="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
                <svg
                  className="w-4 h-4 text-gray-500 dark:text-gray-300"
                  aria-hidden="true"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 20 20"
                >
                  <path
                    stroke="currentColor"
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    strokeWidth="2"
                    d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
                  />
                </svg>
              </div>
              <input
                type="search"
                id="default-search"
                className="block w-[256px] border-0 p-1.5 ps-10 text-sm text-gray-900 rounded-lg dark:bg-gray-600 dark:text-white bg-[#F2F2F2] dark:placeholder-gray-300"
                placeholder="What are you looking for？"
              />
            </div>
          </form>

          {/* Notification bell */}
          <div className="ml-[3%]">
            <Image
              className="cursor-pointer max-2xl:w-5 w-[14px] h-[14px]"
              alt="Notification bell"
              src={resolvedTheme == "light" ? avatar : bell}
            />
          </div>

          {/* profile picture */}
          <div className="mr-[1.5%] ml-[4.5%]">
            <Image
              src={avatar}
              alt={"profile pic"}
              className="rounded-full max-2xl:w-[34px] max-2xl:h-[34px] w-[40px] h-[40px]"
            />
          </div>
          <h5 className="ml-[] leading-4 font-normal">Kweku Asamoah</h5>

          {/* Dropdown */}
          <div className="ml-[1%] ">
            <LongMenu />
          </div>
        </div>
      </div>
    </div>
  );
}

export default Header;
