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
    <div className="border shadow-[0px_1px_2px_0px_#1B283614] max-2xl:h-[110px] h-[8rem] p-4 pt-3 pr-0 rounded-lg grow w-full bg-white rounded-t-lg border-[#E9ECEF]">
      <div className="flex items-center justify-between ">
        <div>
          <Image src={props.icon} className="w-[30px] max-2xl:w-7"  alt="" />
        </div>
        <div>
          <TileDropdown />
        </div>
      </div>
      <section className="flex mt-4 max-2xl:mt-3">
        <div className="grow">
          <p className="font-normal text-sm max-2xl:text-xs leading-5 text-[#8C8C8C] ">
            {props.entity1}
          </p>
          <div className="flex mt-2 max-2xl:mt-1 items-center">
            <p className="text-[#262626] pt- max-2xl:text-lg leading-7 font-medium text-xl">
              ₵{props.entity1value.toLocaleString()}
            </p>
            <p>{props.entity1differential}</p>
          </div>
        </div>

        <div className="grow">
          <p className="font-normal text-sm max-2xl:text-xs leading-5 text-[#8C8C8C]">
            {props.entity2}
          </p>
          <div className="flex mt-2 max-2xl:mt-1 items-center">
            <p className="text-[#262626]  max-2xl:text-lg leading-7 font-medium text-xl">
              {props.entity2value.toLocaleString()}
            </p>
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
    <div className="border shadow-[0px_1px_2px_0px_#1B283614] max-2xl:h-[110px] h-[8rem] p-4 pt-3 pr-0 rounded-lg grow w-full bg-white rounded-t-lg border-[#E9ECEF]">
      <div className="flex items-center justify-between ">
        <div>
          <Image src={props.icon} className="w-[30px] max-2xl:w-7" alt="" />
        </div>
        <div>
          <TileDropdown />
        </div>
      </div>
      <section className="flex mt-4 max-2xl:mt-3">
        <div className="grow">
          <p className="font-normal pt- max-2xl:text-xs text-sm leading-5 text-[#8C8C8C] ">
            {props.entity1}
          </p>

          <p className="text-[#262626] pt-2 max-2xl:pt-1  leading-7 font-medium max-2xl:text-lg text-xl">
            {props.entity1value.toLocaleString()}
          </p>
        </div>

        <div className="grow">
          <p className="font-normal pt- max-2xl:text-xs text-sm leading-5 text-[#8C8C8C]">
            {props.entity2}
          </p>
          <div className="flex mt-2 max-2xl:mt-1 items-center">
            <p className="text-[#262626]  max-2xl:text-lg leading-7 font-medium text-xl">
              {props.entity2value.toLocaleString()}
            </p>
          </div>
        </div>
      </section>
    </div>
  );
}

function MidTiles(props: midTileProp) {
  return (
    <div className="border shadow-[0px_1px_2px_0px_#1B283614] max-2xl:h-[110px] h-[8rem] p-4 pt-3 pr-0 rounded-lg grow w-full bg-white rounded-t-lg border-[#E9ECEF]">
      <div className="flex items-center justify-between ">
        <div>
          <Image src={props.icon} className="w-[30px] max-2xl:w-7" alt="" />
        </div>
        <div>
          <TileDropdown />
        </div>
      </div>
      <section className="flex mt-4 max-2xl:mt-3">
        <div className="grow">
          <p className="font-normal  text-sm max-2xl:text-xs leading-5 text-[#8C8C8C] ">
            {props.entity1}
          </p>
          <div className="flex mt-2 max-2xl:mt-1 items-center">
            <p className="text-[#262626] max-2xl:text-lg leading-7 font-medium text-xl">
              {props.entity1value.toLocaleString()}
            </p>
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
          <p className="font-normal text-sm max-2xl:text-xs leading-5 text-[#8C8C8C]">
            {props.entity2}
          </p>
          <div className="flex mt-2 max-2xl:mt-1 items-center">
            <p className="text-[#262626] leading-7 max-2xl:text-lg font-medium text-xl">
              {props.entity2value.toLocaleString()}
            </p>
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
    <div className="border shadow-[0px_1px_2px_0px_#1B283614] h-[8rem] max-2xl:h-[110px] max-lg:h-[50px] p-4 pt-3 pr-0 rounded-lg grow w-full bg-white rounded-t-lg border-[#E9ECEF]">
      <div className="flex justify-between items-center">
        <Image src={props.icon} className="w-[30px] max-2xl:w-7" alt="" />
        <div>
          <TileDropdown />
        </div>
      </div>

      <div className="flex mt-4 max-2xl:mt-3">
        <div className="grow">
          <p className="font-normal max-2xl:text-xs text-sm leading-5 text-[#8C8C8C] ">
            {props.entity1}
          </p>
          <div className="flex items-center pt-2 max-2xl:pt-1">
            <p className="text-[#262626] pt-  leading-7 font-medium text-xl">
              {props.entity1value.toLocaleString()}
            </p>
          </div>
        </div>

        <div className="grow">
          <p className="font-normal max-2xl:text-xs text-sm leading-5 text-[#8C8C8C]">
            {props.entity2}
          </p>
          <div className="flex items-center pt-2 max-2xl:pt-1">
            <p className="text-[#262626] max-2xl:text-lg  leading-7 font-medium text-xl">
              {props.entity2value.toLocaleString()}
            </p>
          </div>
        </div>

        <div className="grow">
          <p className="font-normal max-2xl:text-xs text-sm leading-5 text-[#8C8C8C]">
            {props.entity3}
          </p>
          <div className="flex items-center pt-2 max-2xl:pt-1">
            <p className="text-[#262626] leading-7 max-2xl:text-lg font-medium text-xl">
              {props.entity3value.toLocaleString()}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}

export default DashboardTiles;
export { DashboardTiles1, MidTiles, LastTile };
