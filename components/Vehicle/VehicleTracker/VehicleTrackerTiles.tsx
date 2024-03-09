/* eslint-disable require-jsdoc */
/* eslint-disable react/no-unescaped-entities */

import React from "react";
import Image from "next/image";
import Link from "next/link";

type VehicleTrackerProp1 = {
  icon: any;
  entity1: string;
  entity1value: number;
  entity2: string;
  entity3?: string;
  entity2value: number;
  entity3value: number;
  entity1differential?: string;
  entity2differential?: string;
};

export default function VehicleTrackerTiles(props: VehicleTrackerProp1) {
  return (
    <div className="border h-[8rem] p-4 pt-3 shadow-[0px_1px_2px_0px_#1B283614] rounded-lg w-[30px] bg-white flex grow rounded-t-lg border-[#E9ECEF]">
      <div>
        <Image src={props.icon} width={30} height={10} alt="" />

        <p className="font-normal mt-4 text-xs leading-5 text-[#8C8C8C] ">
          {props.entity1}
        </p>
        <div className="flex items-center pt-2">
          <p className="text-[#262626] pt-  leading-7 font-medium text-xl">
            {props.entity1value.toLocaleString()}
          </p>
          <p className="text-xs font-normal pl-2 text-[#0EA371]">
            {props.entity1differential}
          </p>
        </div>
      </div>

      <div className="ml-[14%]">
        <p className="font-normal pt-[45px] text-xs leading-5 text-[#8C8C8C]">
          {props.entity2}
        </p>
        <div className="flex items-center pt-2">
          <p className="text-[#262626]   leading-7 font-medium text-xl">
            {props.entity2value.toLocaleString()}
          </p>
          <p className="text-xs font-normal pl-2 text-[#0EA371] ">
            {props.entity2differential}
          </p>
        </div>
      </div>

      <div className="ml-[14%]">
        <p className="font-normal pt-[45px] text-xs leading-5 text-[#8C8C8C]">
          {props.entity3}
        </p>
        <div className="items-center pt-2">
          <p className="text-[#262626] leading-7 font-medium text-xl">
            {props.entity3value.toLocaleString()}
          </p>
        </div>
      </div>
    </div>
  );
}
