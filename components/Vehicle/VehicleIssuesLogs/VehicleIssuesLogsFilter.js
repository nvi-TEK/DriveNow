import React, { useState } from "react";
import SearchOutlinedIcon from "@mui/icons-material/SearchOutlined";

export const VehicleIssuesLogsFilter = ({ filter, setFilter }) => {
  return (
    <span className="flex items-center">
      <p className="text-[#262626] font-medium leading-[30px]">Search:</p>
      <input
        value={filter || ""}
        onChange={(e) => setFilter(e.target.value)}
        className="border shadow-[0px_1px_2px_0px_#1B283614] ml-2 dark:border-0 dark:bg-dm-600 dark:text-white text-sm placeholder-[#BFBFBF] w-[353px]vtext-sm pl-3 py-1 border-[#D9D9D9] rounded text-black"
        placeholder="Search"
      />
    </span>
  );
};
