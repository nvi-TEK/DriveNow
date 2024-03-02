/* eslint-disable require-jsdoc */
/* eslint-disable react/no-unescaped-entities */

import React from "react";
import Image from "next/image"
import Bubble from "./HeatmapBubbles";
import HeatmapBubble from "./HeatmapBubbles";

type DriverActivitiesProps = {
  name: string;
  lastUpdate: string;
  rating: string;
  status: string;
};

function DriverActivities(props: DriverActivitiesProps) {
  return (
    <div className="border-b h-[80px] flex justify-between items-center bg-white border-[#E6E6E6]">
      <div>
        <p className="text-[#262626] text-sm font-normal leading-[18px] ">
          {props.name}
        </p>
        <p className="text-[#595959] font-normal text-xs pt-2 leading-[14.52px] ">
          Ratings: {props.rating}
        </p>
      </div>
      <div className="text-right">
        <p className="text-[#8C8C8C] font-normal text-xs leading-4 ">
          {props.lastUpdate}
        </p>
        <p className="">{props.status}</p>
      </div>
    </div>
  );
}

export default DriverActivities;
