/* eslint-disable require-jsdoc */
/* eslint-disable react/no-unescaped-entities */

import React from "react";
import Image from "next/image";
import Link from "next/link";
import TileDropdown from "@/components/tileDropdown";

type VehicleTrackerProp = {
  icon: any;
  entity1: string;
  entity1value: number;
  entity2: string;
  entity3: string;
  entity2value: number;
  entity3value: number;
  entity1differential: number;
};

export default function VehicleTrackerTiles(props: VehicleTrackerProp) {
  return (
    <div className="border h-[8rem] p-4 pt-3 pr-0 shadow-[0px_1px_2px_0px_#1B283614] rounded-lg bg-white dark:bg-dm-700 dark:border-0 grow border-[#E9ECEF]">
      <div className="flex items-center justify-between">
        <div>
          <Image src={props.icon} width={30} height={10} alt="" />
        </div>
        <div>
          <TileDropdown />
        </div>
      </div>

      <div className="flex mt-4">
        <div className="grow">
          <p className="font-normal dark:text-white leading-5 text-[#8C8C8C] ">
            {props.entity1}
          </p>
          <div className="flex items-center grow pt-2">
            <h2 className="text-[#262626] dark:text-white leading-7 font-medium">
              {props.entity1value.toLocaleString()}
            </h2>
            <p
              style={{
                color: props.entity1differential >= 0 ? "#0EA371" : "#DC4A41",
              }}
              className="text-[10px] font-normal pl-1"
            >
              {props.entity1differential <= 0 ? "" : "+"}
              {props.entity1differential}%
            </p>
          </div>
        </div>

        <div className="ml-[] grow">
          <p className="font-normal dark:text-white leading-5 text-[#8C8C8C]">
            {props.entity2}
          </p>
          <div className="flex items-center pt-2">
            <h2 className="text-[#262626] dark:text-white leading-7 font-medium">
              {props.entity2value.toLocaleString()}
            </h2>
          </div>
        </div>

        <div className="ml-[] grow">
          <p className="font-normal dark:text-white leading-5 text-[#8C8C8C]">
            {props.entity3}
          </p>
          <div className="items-center pt-2">
            <p className="text-[#262626] leading-7 dark:text-white font-medium">
              {props.entity3value}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
