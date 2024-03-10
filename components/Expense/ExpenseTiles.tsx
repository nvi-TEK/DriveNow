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
    <div className="h-[8rem] p-4 pt-3 pr-0 shadow-[0px_1px_2px_0px_#1B283614] rounded-lg bg-white grow  border-[#E9ECEF]">
      <div className="flex justify-between items-center">
        <div>
          <Image src={props.icon} width={30} height={10} alt="" />
        </div>
        <div>
          <TileDropdown1 />
        </div>
      </div>

      <div className="flex">
        <div className="grow">
          <p className="font-normal mt-4 text-sm leading-5 text-[#8C8C8C] ">
            {props.entity1}
          </p>
          <div className="flex items-center pt-2">
            <p className="text-[#262626] pt-  leading-7 font-medium text-xl">
              {props.entity1value.toLocaleString()}
            </p>
            <p
              style={{
                color: props.entity1differential >= 0 ? "#0EA371" : "#DC4A41",
              }}
              className="text-xs font-normal pl-2"
            >
              {props.entity1differential <= 0 ? "" : "+"}
              {props.entity1differential}%
            </p>
          </div>
        </div>

        <div className="grow">
          <p className="font-normal pt-4 text-sm leading-5 text-[#8C8C8C]">
            {props.entity2}
          </p>
          <div className="flex items-center pt-2">
            <p className="text-[#262626]   leading-7 font-medium text-xl">
              {props.entity2value.toLocaleString()}
            </p>
            <p className="text-xs font-normal pl-2">
              {props.entity2differential}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
