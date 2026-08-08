/* eslint-disable require-jsdoc */
/* eslint-disable react/no-unescaped-entities */

import React from "react";
import Image from "next/image";
import Link from "next/link";
import TileDropdown from "../tileDropdown";

type ListOfVehiclesProp1 = {
  icon: any;
  entity1: string;
  entity1value: number;
  entity2: string;
  entity3?: string;
  entity2value: number;
  entity3value: number;
  entity1differential: number;
  entity2differential: number;
  entity3differential: number;
};

type ListofVehiclesProp = {
  icon: any;
  entity1: string;
  entity1value: number;
  entity2: string;
  entity3: string;
  entity2value: number;
  entity3value: number;
  entity1differential: number;
};

export default function ListOfVehiclesTiles(props: ListOfVehiclesProp1) {
  return (
    <div className="border dark:border-0 dark:bg-dm-700 shadow-[0px_1px_2px_0px_#1B283614] h-[8rem] p-4 pt-3 pr-0 rounded-lg w-[30px] bg-white grow rounded-t-lg border-[#E9ECEF]">
      <div className="flex justify-between items-center">
        <div>
          <Image src={props.icon} width={30} height={10} alt="" />
        </div>
        <div>
          <TileDropdown />
        </div>
      </div>

      <div className="flex">
        <div className="grow">
          <p className="font-normal mt-4 dark:text-white   leading-5 text-[#8C8C8C] ">
            {props.entity1}
          </p>
          <div className="flex items-center pt-2">
            <h2 className="text-[#262626] pt- dark:text-white leading-7 font-medium  ">
              {props.entity1value.toLocaleString()}
            </h2>
            <p
              style={{
                color: props.entity1differential >= 0 ? "#0EA371" : "#DC4A41",
              }}
              className="text-xs font-normal pl-1"
            >
              {props.entity1differential <= 0 ? "" : "+"}
              {props.entity1differential}%
            </p>
          </div>
        </div>

        <div className="ml-[] grow">
          <p className="font-normal dark:text-white pt-4 leading-5 text-[#8C8C8C]">
            {props.entity2}
          </p>
          <div className="flex items-center pt-2">
            <h2 className="text-[#262626] dark:text-white leading-7 font-medium  ">
              {props.entity2value.toLocaleString()}
            </h2>
            <p
              style={{
                color: props.entity2differential >= 0 ? "#0EA371" : "#DC4A41",
              }}
              className="text-xs font-normal pl-1"
            >
              {props.entity2differential <= 0 ? "" : "+"}
              {props.entity2differential}%
            </p>
          </div>
        </div>

        <div className="ml-[] grow">
          <p className="font-normal pt-4   dark:text-white leading-5 text-[#8C8C8C]">
            {props.entity3}
          </p>
          <div className="items-center flex pt-2">
            <h2 className="text-[#262626] dark:text-white leading-7 font-medium  ">
              {props.entity3value.toLocaleString()}
            </h2>
            <p
              style={{
                color: props.entity3differential >= 0 ? "#0EA371" : "#DC4A41",
              }}
              className="text-xs font-normal pl-1"
            >
              {props.entity3differential <= 0 ? "" : "+"}
              {props.entity3differential}%
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}

function ListofVehiclesTiles(props: ListofVehiclesProp) {
  return (
    <div className="border shadow-[0px_1px_2px_0px_#1B283614] dark:bg-dm-700 dark:border-0 h-[8rem] p-4 pt-3 pr-0 rounded-lg w-[30px] bg-white grow rounded-t-lg border-[#E9ECEF]">
      <div className="flex justify-between items-center">
        <div>
          <Image src={props.icon} width={30} height={10} alt="" />
        </div>
        <div>
          <TileDropdown />
        </div>
      </div>

      <div className="flex">
        <div className="grow">
          <p className="font-normal dark:text-white mt-4   leading-5 text-[#8C8C8C] ">
            {props.entity1}
          </p>
          <div className="flex items-center pt-2">
            <h2 className="text-[#262626] pt- dark:text-white leading-7 font-medium ">
              {props.entity1value.toLocaleString()}
            </h2>
            <p
              style={{
                color: props.entity1differential >= 0 ? "#0EA371" : "#DC4A41",
              }}
              className="text-xs font-normal pl-1"
            >
              {props.entity1differential <= 0 ? "" : "+"}
              {props.entity1differential}%
            </p>
          </div>
        </div>

        <div className="ml-[] grow">
          <p className="font-normal pt-4 dark:text-white   leading-5 text-[#8C8C8C]">
            {props.entity2}
          </p>
          <h2 className="text-[#262626] pt-2 dark:text-white leading-7 font-medium ">
            {props.entity2value.toLocaleString()}
          </h2>
        </div>

        <div className="ml-[] grow">
          <p className="font-normal pt-4   dark:text-white leading-5 text-[#8C8C8C]">
            {props.entity3}
          </p>
          <h2 className="text-[#262626] pt-2 leading-7 dark:text-white font-medium ">
            {props.entity3value.toLocaleString()}
          </h2>
        </div>
      </div>
    </div>
  );
}

export { ListofVehiclesTiles };
