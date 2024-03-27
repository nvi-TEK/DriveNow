/* eslint-disable require-jsdoc */
import React from "react";
import SideBar from "./sidebar";
import Header from "./header";
// import Providers from "@/pages/providers";

type layoutType = {
  children?: any;
};

export default function Layout({ children }: layoutType) {
  return (
    <div suppressHydrationWarning>
      {/* <Providers> */}
      <section className="w-full dark: bg-white">
        <div className="flex flex-grow dark: border-0 bg-[#F2F2F2]">
          <SideBar />
          {children}
        </div>
      </section>
      {/* </Providers> */}
    </div>
  );
}
