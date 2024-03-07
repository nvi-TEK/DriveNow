import React, { useState } from "react";
import SearchOutlinedIcon from "@mui/icons-material/SearchOutlined";

export const VehicleTrackerFilter = ({ filter, setFilter }) => {
  return (
    <span className="flex items-center">
    <p className="text-sm leading-[30px] font-medium text-[#262626] ">Search:</p>
      <input
        value={filter || ""}
        onChange={(e) => setFilter(e.target.value)}
        className="border placeholder-[#BFBFBF] w-[173px] text-sm pl-3 ml-3 py-1 border-[#D9D9D9] rounded text-black"
        placeholder="Search"
      />
    </span>
  );
};
