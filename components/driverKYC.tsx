/* eslint-disable require-jsdoc */
/* eslint-disable react/no-unescaped-entities */

import React from "react";
import Image from "next/image";
import Link from "next/link";

type KYCProp = {
  name: string;
  date: string;
  description: string;
  status: string;
};

function KYC(props: KYCProp) {
  return (
    <div className="border-b h-[80px] flex items-center justify-between grow bg-white border-[#E6E6E6]">
      <div>
        <p className="text-[#595959] font-normal leading-[18px] ">
          {props.name}
        </p>
        <p className="font-medium text-xs pt-2 leading-[14.52px] ">
          {props.description}
        </p>
      </div>
      <div className="ml-[] text-right">
        <p className="text-[#8C8C8C] font-normal text-xs leading-4 ">
          {props.date}
        </p>
        <div
          style={{
            backgroundColor:
              props.status === "Completed"
                ? "#E7F6F1"
                : props.status === "Failed"
                ? "#FBEDEC"
                : "#FBF6E9",
          }}
          className="px-2 text-right rounded-[2px] py-[2px] mt-2"
        >
          <p
            style={{
              color:
                props.status === "Completed"
                  ? "#0EA371"
                  : props.status === "Failed"
                  ? "#DC4A41"
                  : "#E8B123",
            }}
            className="font-medium text-xs text-right leading-4"
          >
            {props.status}
          </p>
        </div>
      </div>
    </div>
  );
}

export default KYC;
