/* eslint-disable require-jsdoc */
/* eslint-disable react/no-unescaped-entities */

import React from "react";
import Image from "next/image";
import driver from "../assets/driver_icon.png";
import TileDropdown from "../components/tileDropdown";
import Link from "next/link";
import LongMenu from "../components/tileDropdown";

type tileProp1 = {
  icon: any;
  entity1: string;
  entity1value: number;
  entity2: string;
  entity2value: number;
  entity1differential?: number;
  entity2differential: number;
};
type midTileProp = {
  icon: any;
  entity1: string;
  entity1value: number;
  entity2: string;
  entity2value: number;
  entity1differential: number;
  entity2differential: number;
};

type tileProp2 = {
  icon: any;
  entity1: string;
  entity1value: number;
  entity2: string;
  entity3: string;
  entity2value: number;
  entity3value: number;
};

type LastTileProp = {
  icon: any;
  entity1: string;
  entity1value: number;
  entity2: string;
  entity2value: number;
};

function DashboardTiles(props: tileProp1) {
  return (
    <div className="border shadow-[0px_1px_2px_0px_#1B283614] max-2xl:h-[110px] dark:bg-gray-700 dark:border-0 h-[8rem] p-4 pt-3 pr-0 rounded-lg grow w-full bg-white rounded-t-lg border-[#E9ECEF]">
      <div className="flex items-center justify-between ">
        <div>
          <Image src={props.icon} className="w-[30px] max-2xl:w-7" alt="" />
        </div>
        <div>
          <TileDropdown />
        </div>
      </div>
      <section className="flex mt-4 max-2xl:mt-2">
        <div className="grow">
          <p className="font-normal dark:text-white  leading-5 text-[#8C8C8C] ">
            {props.entity1}
          </p>
          <div className="flex mt-2 max-2xl:mt-1 items-center">
            <h2 className="text-[#262626] pt- dark:text-white  leading-7 font-medium">
              ₵{props.entity1value.toLocaleString(undefined, {maximumFractionDigits: 2, minimumFractionDigits: 2})}
            </h2>
            <p>{props.entity1differential}</p>
          </div>
        </div>

        <div className="grow">
          <p className="font-normal dark:text-white  leading-5 text-[#8C8C8C]">
            {props.entity2}
          </p>
          <div className="flex mt-2 max-2xl:mt-1 items-center">
            <h2 className="text-[#262626] dark:text-white leading-7 font-medium">
              {props.entity2value.toLocaleString()}
            </h2>
            <p
              style={{
                color: props.entity2differential >= 0 ? "#0EA371" : "#DC4A41",
              }}
              className="text-xs max-2xl:text-[10px] font-normal pl-1"
            >
              {props.entity2differential <= 0 ? "" : "+"}
              {props.entity2differential}%
            </p>
          </div>
        </div>
      </section>
    </div>
  );
}

// last tile
function LastTile(props: LastTileProp) {
  return (
    <div className="border shadow-[0px_1px_2px_0px_#1B283614] dark:bg-gray-700 dark:border-0 max-2xl:h-[110px] h-[8rem] p-4 pt-3 pr-0 rounded-lg grow w-full bg-white rounded-t-lg border-[#E9ECEF]">
      <div className="flex items-center justify-between ">
        <div>
          <Image src={props.icon} className="w-[30px] max-2xl:w-7" alt="" />
        </div>
        <div>
          <TileDropdown />
        </div>
      </div>
      <section className="flex mt-4 max-2xl:mt-2">
        <div className="grow">
          <p className="font-normal dark:text-white pt- leading-5 text-[#8C8C8C] ">
            {props.entity1}
          </p>

          <h2 className="text-[#262626] dark:text-white pt-2 max-2xl:pt-1  leading-7 font-medium ">
            {props.entity1value.toLocaleString()}
          </h2>
        </div>

        <div className="grow">
          <p className="font-normal dark:text-white pt- leading-5 text-[#8C8C8C]">
            {props.entity2}
          </p>
          <div className="flex mt-2 max-2xl:mt-1 items-center">
            <h2 className="text-[#262626] dark:text-white  leading-7 font-medium">
              {props.entity2value.toLocaleString()}
            </h2>
          </div>
        </div>
      </section>
    </div>
  );
}

function MidTiles(props: midTileProp) {
  return (
    <div className="border dark:border-0 shadow-[0px_1px_2px_0px_#1B283614] max-2xl:h-[110px] h-[8rem] p-4 pt-3 pr-0 rounded-lg grow w-full  dark:bg-gray-700 bg-white rounded-t-lg border-[#E9ECEF]">
      <div className="flex items-center justify-between ">
        <div>
          <Image src={props.icon} className="w-[30px] max-2xl:w-7" alt="" />
        </div>
        <div>
          <TileDropdown />
        </div>
      </div>
      <section className="flex mt-4 max-2xl:mt-2">
        <div className="grow">
          <p className="font-normal dark:text-white  leading-5 text-[#8C8C8C] ">
            {props.entity1}
          </p>
          <div className="flex mt-2 max-2xl:mt-1 items-center">
            <h2 className="text-[#262626] dark:text-white  leading-7 font-medium">
              {props.entity1value.toLocaleString()}
            </h2>
            <p
              style={{
                color: props.entity1differential >= 0 ? "#0EA371" : "#DC4A41",
              }}
              className="text-xs font-normal max-2xl:text-[10px] pl-1"
            >
              {props.entity1differential <= 0 ? "" : "+"}
              {props.entity1differential}%
            </p>
          </div>
        </div>

        <div className="grow">
          <p className="font-normal  leading-5 dark:text-white text-[#8C8C8C]">
            {props.entity2}
          </p>
          <div className="flex mt-2 max-2xl:mt-1 items-center">
            <h2 className="text-[#262626] leading-7 dark:text-white font-medium">
              {props.entity2value.toLocaleString()}
            </h2>
            <p
              style={{
                color: props.entity2differential >= 0 ? "#0EA371" : "#DC4A41",
              }}
              className="text-xs max-2xl:text-[10px] font-normal pl-1"
            >
              {props.entity2differential <= 0 ? "" : "+"}
              {props.entity2differential}%
            </p>
          </div>
        </div>
      </section>
    </div>
  );
}

function DashboardTiles1(props: tileProp2) {
  return (
    <div className="border shadow-[0px_1px_2px_0px_#1B283614] dark:bg-gray-700 dark:border-0 h-[8rem] max-2xl:h-[110px] p-4 pt-3 pr-0 rounded-lg grow w-full bg-white rounded-t-lg border-[#E9ECEF]">
      <div className="flex justify-between items-center">
        <Image src={props.icon} className="w-[30px] max-2xl:w-7" alt="" />
        <div>
          <TileDropdown />
        </div>
      </div>

      <div className="flex mt-4 max-2xl:mt-2">
        <div className="grow">
          <p className="font-normal dark:text-white  leading-5 text-[#8C8C8C] ">
            {props.entity1}
          </p>
          <div className="flex items-center pt-2 max-2xl:pt-1">
            <h2 className="text-[#262626] pt- dark:text-white leading-7 font-medium">
              {props.entity1value.toLocaleString()}
            </h2>
          </div>
        </div>

        <div className="grow">
          <p className="font-normal dark:text-white  leading-5 text-[#8C8C8C]">
            {props.entity2}
          </p>
          <div className="flex items-center pt-2 max-2xl:pt-1">
            <h2 className="text-[#262626] dark:text-white  leading-7 font-medium">
              {props.entity2value.toLocaleString()}
            </h2>
          </div>
        </div>

        <div className="grow">
          <p className="font-normal dark:text-white  leading-5 text-[#8C8C8C]">
            {props.entity3}
          </p>
          <div className="flex items-center pt-2 max-2xl:pt-1">
            <h2 className="text-[#262626] dark:text-white leading-7  font-medium">
              {props.entity3value.toLocaleString()}
            </h2>
          </div>
        </div>
      </div>
    </div>
  );
}

export default DashboardTiles;
export { DashboardTiles1, MidTiles, LastTile };
