import React, { useState } from "react";
import SearchOutlinedIcon from "@mui/icons-material/SearchOutlined";

export const CustomPushFilter = ({ filter, setFilter }) => {
  return (
    <span className="flex items-center">
      <p className="font-medium leading-[30px] text-[#262626]">Search:</p>
      <input
        value={filter || ""}
        onChange={(e) => setFilter(e.target.value)}
        className="border w-[164px] placeholder-[#BFBFBF] ml-2 dark:bg-gray-600 dark:border-0 dark:text-white pl-3 py-1.5 text-xs shadow-[0px_1px_2px_0px_#1B283614] border-[#D9D9D9] rounded text-black"
        placeholder="Select group to send"
      />
    </span>
  );
};
