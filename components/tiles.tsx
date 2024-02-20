/* eslint-disable require-jsdoc */
/* eslint-disable react/no-unescaped-entities */

import React from "react";
import Image from "next/image";
import Link from "next/link";

type tileProp1 = {
  icon: any;
  entity1: string;
  entity1value: number;
  entity2: string;
  entity2value: number;
  entity1differential?: string;
  entity2differential?: string;
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

function DashboardTiles(props: tileProp1) {
  return (
    <div className="border h-[8rem] p-4 pt-3  rounded-lg w-[20.75rem] bg-white flex grow rounded-t-lg border-[#E9ECEF]">
      <div>
        <Image src={props.icon} width={30} height={10} alt="" />

        <p className="font-normal pt-4 text-sm leading-5 text-[#8C8C8C] ">
          {props.entity1}
        </p>
        <div className="flex items-center pt-2">
          <p className="text-[#262626] pt-  leading-7 font-medium text-xl">
            {props.entity1value}
          </p>
          <p className="text-xs font-normal pl-2 text-[#0EA371] ">
            {props.entity1differential}
          </p>
        </div>
      </div>

      <div className="ml-[30%]">
        <p className="font-normal pt-12 text-sm leading-5 text-[#8C8C8C]">
          {props.entity2}
        </p>
        <div className="flex items-center pt-2">
          <p className="text-[#262626]   leading-7 font-medium text-xl">
            {props.entity2value}
          </p>
          <p className="text-xs font-normal pl-2 text-[#0EA371] ">
            {props.entity2differential}
          </p>
        </div>
      </div>
    </div>
  );
}

function DashboardTiles1(props: tileProp2) {
  return (
    <div className="border h-[8rem] p-4 pt-3  rounded-lg w-[20.85rem] bg-white  flex rounded-t-lg border-[#E9ECEF]">
      <div>
        <Image src={props.icon} width={30} height={10} alt="" />

        <p className="font-normal pt-4 text-sm leading-5 text-[#8C8C8C] ">
          {props.entity1}
        </p>
        <div className="flex items-center pt-2">
          <p className="text-[#262626] pt-  leading-7 font-medium text-xl">
            {props.entity1value}
          </p>
        </div>
      </div>

      <div className="ml-[10%]">
        <p className="font-normal pt-12 text-sm leading-5 text-[#8C8C8C]">
          {props.entity2}
        </p>
        <div className="flex items-center pt-2">
          <p className="text-[#262626]   leading-7 font-medium text-xl">
            {props.entity2value}
          </p>
        </div>
      </div>

      <div className="ml-[10%]">
        <p className="font-normal pt-12 text-sm leading-5 text-[#8C8C8C]">
          {props.entity3}
        </p>
        <div className="flex items-center pt-2">
          <p className="text-[#262626] leading-7 font-medium text-xl">
            {props.entity3value}
          </p>
        </div>
      </div>
    </div>
  );
}

export default DashboardTiles;
export { DashboardTiles1 };
