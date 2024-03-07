/* eslint-disable require-jsdoc */
/* eslint-disable react/no-unescaped-entities */

import React from "react";
import Image from "next/image";

type DriverActivitiesProps = {
  name: string;
  lastUpdate: string;
  rating: string;
  status: string;
};

function DriverActivities(props: DriverActivitiesProps) {
  return (
    <div className="border-b h-[80px] flex items-center justify-between grow bg-white border-[#E6E6E6]">
      <div>
        <p className="text-[#262626] text-sm font-normal leading-[18px] ">
          {props.name}
        </p>
        <p className="text-[#595959] font-normal text-xs pt-2 leading-[14.52px] ">
          Ratings: {props.rating}
        </p>
      </div>

      <div className="text-right flex flex-col">
        <p className="text-[#8C8C8C] font-normal text-xs leading-4 ">
          {props.lastUpdate}
        </p>
        <div
          style={{
            backgroundColor: props.status === "Online" ? "#E7F6F1" : "#FBEDEC",
          }}
          className="px-3 rounded-sm  text-right py-[2px] place-self-end mt-2"
        >
          <p
            style={{
              color: props.status === "Online" ? "#0EA371" : "#DC4A41",
            }}
            className="font-medium text-xs text-right leading-4"
          >
            {props.status}
          </p>
        </div>
      </div>
    </div>
  );
}

export default DriverActivities;
