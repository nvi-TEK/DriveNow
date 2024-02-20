/* eslint-disable require-jsdoc */
import React from "react";
import Image from "next/image";
import { Avatar, Box, ButtonBase } from "@mui/material";
import CustomizedMenus from "./headerDropdown";
import NotificationsNoneSharpIcon from "@mui/icons-material/NotificationsNoneSharp";
import KeyboardArrowDownSharpIcon from "@mui/icons-material/KeyboardArrowDownSharp";
import logo from "../assets/logo.png";
// import { Dropdown } from 'flowbite-react';
import profilepic from "../assets/profilepic.png";

type pageProp = {
  name: string;
}


function Header(prop: pageProp) {
  return (
    <>
      <header className="flex w-full h-[4rem] shadow-lg items-center border-b border-[#E6E6E6] pr-4 bg-Grey/50">
        <Image
          src={logo}
          alt={"DriveNow logo"}
          className=" ml-[62.5px] h-[18px]"
        />

        <p className="ml-[6%] mr-auto text-[#595959] font-normal  ">
          Dashboard/ {prop.name}
        </p>

        {/* Search bar */}
        <form className="max-w-md mx-auto">
          <label
            htmlFor="default-search"
            className="mb-2 text-sm font-medium text-gray-900 sr-only dark:text-white"
          >
            Search
          </label>
          <div className="relative ml-[60%]">
            <div className="absolute inset-y-0 start-0 flex items-center ps-3 pointer-events-none">
              <svg
                className="w-4 h-4 text-gray-500 dark:text-gray-400"
                aria-hidden="true"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 20 20"
              >
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"
                />
              </svg>
            </div>
            <input
              type="search"
              id="default-search"
              className="block w-[256px] p-2 ps-10 text-sm text-gray-900 rounded-lg bg-[#F2F2F2] dark:placeholder-gray-400"
              placeholder="What are you looking for？"
            />
          </div>
        </form>

        <div className="mr-[1%] ">
          <NotificationsNoneSharpIcon />
        </div>

        {/* profile picture */}
        <Image
          src={profilepic}
          alt={"profile pic"}
          className="rounded-full mr-2 w-[40px] h-[40px]"
        />

        <p className="mr-9 leading-4 text-xs font-normal">Kweku Asamoah</p>

        <div className="">
          <CustomizedMenus />
        </div>
      </header>
    </>
  );
}

export default Header;
