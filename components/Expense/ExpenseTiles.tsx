/* eslint-disable require-jsdoc */
/* eslint-disable react/no-unescaped-entities */

import React from "react";
import Image from "next/image";
import Link from "next/link";
import { TileDropdown1 } from "../tileDropdown";

type ExpenseProp1 = {
  icon: any;
  entity1: string;
  entity1value: number;
  entity2: string;
  entity2value: number;
  entity1differential: number;
  entity2differential?: number;
};

export default function ExpenseTiles(props: ExpenseProp1) {
  return (
    <div className="h-[8rem] max-2xl:h-[110px] p-4 pt-3 max-2xl:pt-2 pr-0 shadow-[0px_1px_2px_0px_#1B283614] rounded-lg bg-white grow  border-[#E9ECEF]">
      <div className="flex justify-between items-center">
        <div>
          <Image src={props.icon} className="w-[30px] max-2xl:w-7" alt="" />
        </div>
        <div>
          <TileDropdown1 />
        </div>
      </div>

      <div className="flex mt-4 max-2xl:mt-3">
        <div className="grow">
          <p className="font-normal  text-sm max-2xl:text-[11px] leading-5 text-[#8C8C8C] ">
            {props.entity1}
          </p>
          <div className="flex items-center max-2xl:pt-1 pt-2">
            <p className="text-[#262626] leading-7 font-medium max-2xl:text-lg text-xl">
              {props.entity1value.toLocaleString()}
            </p>
            <p
              style={{
                color: props.entity1differential >= 0 ? "#0EA371" : "#DC4A41",
              }}
              className="text-xs max-2xl:text-[11px] font-normal pl-2"
            >
              {props.entity1differential <= 0 ? "" : "+"}
              {props.entity1differential}%
            </p>
          </div>
        </div>

        <div className="grow">
          <p className="font-normal text-sm leading-5 max-2xl:text-[11px] text-[#8C8C8C]">
            {props.entity2}
          </p>
          <div className="flex items-center max-2xl:pt-1 pt-2">
            <p className="text-[#262626] leading-7 font-medium max-2xl:text-lg text-xl">
              {props.entity2value.toLocaleString()}
            </p>
            <p className="text-xs font-normal max-2xl:text-[11px] pl-2">
              {props.entity2differential}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
