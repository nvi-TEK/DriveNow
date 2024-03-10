/* eslint-disable require-jsdoc */
/* eslint-disable react/no-unescaped-entities */

import React from "react";
import Image from "next/image";
import driver from "../assets/driver_icon.png";
import TileDropdown from "../../components/tileDropdown";
import Link from "next/link";
import LongMenu from "../../components/tileDropdown";

type PaymentProp = {
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
  entity3: string;
  entity2value: number;
  entity3value: number;
  entity1differential: number;
  entity2differential: number;
  entity3differential: number;
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

function PaymentTiles(props: PaymentProp) {
  return (
    <div className="border shadow-[0px_1px_2px_0px_#1B283614] h-[8rem] p-4 pt-3 pr-0 rounded-lg grow w-full bg-white rounded-t-lg border-[#E9ECEF]">
      <div className="flex items-center justify-between ">
        <div>
          <Image src={props.icon} width={30} height={10} alt="" />
        </div>
        <div>
          <TileDropdown />
        </div>
      </div>
      <section className="flex mt-4">
        <div className="grow">
          <p className="font-normal text-xs leading-5 text-[#8C8C8C] ">
            {props.entity1}
          </p>
          <div className="flex items-center">
            <p className="text-[#262626] pt-2 leading-7 font-medium text-lg ">
              ₵{props.entity1value.toLocaleString()}
            </p>
          </div>
        </div>

        <div className="ml-[] grow">
          <p className="font-normal  text-xs leading-5 text-[#8C8C8C]">
            {props.entity2}
          </p>
          <div className="flex mt-2 items-center">
            <p className="text-[#262626] leading-7 font-medium text-lg ">
            ₵{props.entity2value.toLocaleString()}
            </p>
            <p
              style={{
                color: props.entity2differential >= 0 ? "#0EA371" : "#DC4A41",
              }}
              className="text-xs font-normal pl-1"
            >
              {props.entity2differential <= 0? "":"+"}{props.entity2differential}%
            </p>
          </div>
        </div>
      </section>
    </div>
  );
}

function MidTiles(props: midTileProp) {
    return (
      <div className="border shadow-[0px_1px_2px_0px_#1B283614] h-[8rem] p-4 pr-0 pt-3 rounded-lg w-full bg-white grow rounded-t-lg border-[#E9ECEF]">
        <div className="flex justify-between items-center">
          <div>
            <Image src={props.icon} width={30} height={10} alt="" />
          </div>
          <div>
            <TileDropdown />
          </div>
        </div>
  
        <div className="flex ">
          <div className="grow">
            <p className="font-normal mt-4 text-xs leading-5 text-[#8C8C8C] ">
              {props.entity1}
            </p>
            <div className="flex items-center pt-2">
              <p className="text-[#262626] pt-  leading-7 font-medium text-lg">
                {props.entity1value.toLocaleString()}
              </p>
              <p
                style={{
                  color: props.entity1differential >= 0 ? "#0EA371" : "#DC4A41",
                }}
                className="text-[10px] font-normal pl-1"
              >
              {props.entity1differential <= 0? "":"+"}{props.entity1differential}%
              </p>
            </div>
          </div>
  
          <div className="ml-[] grow">
            <p className="font-normal pt-4 text-xs leading-5 text-[#8C8C8C]">
              {props.entity2}
            </p>
            <div className="flex items-center pt-2">
              <p className="text-[#262626] leading-7 font-medium text-lg">
                {props.entity2value.toLocaleString()}
              </p>
              <p
                style={{
                  color: props.entity2differential >= 0 ? "#0EA371" : "#DC4A41",
                }}
                className="text-[10px] font-normal pl-1 "
              >
              {props.entity2differential <= 0? "":"+"}{props.entity2differential}%
              </p>
            </div>
          </div>
  
          <div className="ml-[4%] grow">
            <p className="font-normal pt-4 text-xs leading-5 text-[#8C8C8C]">
              {props.entity3}
            </p>
            <div className="flex items-center pt-2">
              <p className="text-[#262626]   leading-7 font-medium text-lg">
                {props.entity3value.toLocaleString()}
              </p>
              <p
                style={{
                  color: props.entity3differential >= 0 ? "#0EA371" : "#DC4A41",
                }}
                className="text-[10px] font-normal pl-1"
              >
              {props.entity3differential <= 0? "":"+"}{props.entity3differential}%
              </p>
            </div>
          </div>
        </div>
      </div>
    );
  }
  

function LastTile(props: tileProp2) {
  return (
    <div className="border shadow-[0px_1px_2px_0px_#1B283614] h-[8rem] p-4 pt-3 pr-0 rounded-lg grow w-full bg-white rounded-t-lg border-[#E9ECEF]">
      <div className="flex justify-between items-center">
        <Image src={props.icon} width={30} height={10} alt="" />
        <div>
          <TileDropdown />
        </div>
      </div>

      <div className="flex ">
        <div>
          <p className="font-normal pt-4 text-xs leading-5 text-[#8C8C8C] ">
            {props.entity1}
          </p>
          <div className="flex items-center pt-2">
            <p className="text-[#262626] pt-  leading-7 font-medium text-lg">
              {props.entity1value.toLocaleString()}
            </p>
          </div>
        </div>

        <div className="ml-[14%]">
          <p className="font-normal pt-4 text-xs leading-5 text-[#8C8C8C]">
            {props.entity2}
          </p>
          <div className="flex items-center pt-2">
            <p className="text-[#262626]   leading-7 font-medium text-lg">
              {props.entity2value.toLocaleString()}
            </p>
          </div>
        </div>

        <div className="ml-[19%]">
          <p className="font-normal pt-4 text-xs leading-5 text-[#8C8C8C]">
            {props.entity3}
          </p>
          <div className="flex items-center pt-2">
            <p className="text-[#262626] leading-7 font-medium text-lg">
              {props.entity3value.toLocaleString()}
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}

export default PaymentTiles;
export { LastTile, MidTiles };
