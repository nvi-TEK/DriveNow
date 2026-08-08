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
      <section className="w-full bg-[#F2F2F2] dark:bg-dm-600">
        <div className="flex flex-grow border-0 bg-[#F2F2F2] dark:bg-dm-600">
          <SideBar />
          {children}
        </div>
      </section>
      {/* </Providers> */}
    </div>
  );
}
