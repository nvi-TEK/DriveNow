/* eslint-disable require-jsdoc */
import React from "react";
import SideBar from "./sidebar";
import Header from "./header";
type layoutType = {
  children?: any;
};

export default function Layout({ children }: layoutType) {
  return (
    <>
      <section className=" w-full bg-[#FAFAFA]">
        

        <div
          className="flex flex-grow bg-[#FAFAFA] 
			"
        >
          <SideBar />
          {children}
        </div>
      </section>
    </>
  );
}
