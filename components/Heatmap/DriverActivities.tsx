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
    <div className="border-b dark:border-gray-500 h-[80px] flex items-center justify-between grow bg-white dark:bg-gray-700 border-[#E6E6E6]">
      <div>
        <p className="text-[#262626] dark:text-white font-normal leading-[18px] ">
          {props.name}
        </p>
        <h5 className="text-[#595959] dark:text-white font-normal pt-2 leading-[14.52px] ">
          Ratings: {props.rating}
        </h5>
      </div>

      <div className="text-right flex flex-col">
        <h5 className="text-[#8C8C8C] dark:text-white font-normal leading-4 ">
          {props.lastUpdate}
        </h5>
        <div
          style={{
            backgroundColor: props.status === "Online" ? "#E7F6F1" : "#FBEDEC",
          }}
          className="px-3 rounded-sm text-right py-[2px] place-self-end mt-2"
        >
          <h5
            style={{
              color: props.status === "Online" ? "#0EA371" : "#DC4A41",
            }}
            className="font-medium text-right leading-4"
          >
            {props.status}
          </h5>
        </div>
      </div>
    </div>
  );
}

export default DriverActivities;
