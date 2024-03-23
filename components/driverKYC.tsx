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
    <div className="border-b max-2xl:h-[70px] h-[80px] flex items-center justify-between grow dark:bg-gray-700 bg-white dark:border-gray-500 border-[#E6E6E6]">
      <div>
        <p className="text-[#595959] dark:text-white font-normal leading-[18px] ">
          {props.name}
        </p>
        <h5 className="font-medium dark:text-white pt-2 leading-[14.52px] ">
          {props.description}
        </h5>
      </div>
      <div className="ml-[] text-right">
        <h5 className="text-[#8C8C8C] font-normal dark:text-white leading-4 ">
          {props.date}
        </h5>
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
          <h5
            style={{
              color:
                props.status === "Completed"
                  ? "#0EA371"
                  : props.status === "Failed"
                  ? "#DC4A41"
                  : "#E8B123",
            }}
            className="font-medium text-right leading-4"
          >
            {props.status}
          </h5>
        </div>
      </div>
    </div>
  );
}

export default KYC;
