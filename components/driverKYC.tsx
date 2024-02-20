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
    <div className="border-b h-[80px] flex items-center w-[290px] bg-white border-[#E6E6E6]">
      <div>
        <p className="text-[#595959] font-normal leading-[18px] ">
          {props.name}
        </p>
        <p className="font-medium text-xs pt-2 leading-[14.52px] ">
          {props.description}
        </p>
      </div>
      <div className="ml-[24%]">
        <p className="text-[#8C8C8C] font-normal text-xs leading-4 ">{props.date}</p>
        <p>{props.status}</p>
      </div>
    </div>
  );
}

export default KYC;
