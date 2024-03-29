import React, { useState } from "react";
import SearchOutlinedIcon from "@mui/icons-material/SearchOutlined";

export const ExpenseFilter = ({ filter, setFilter }) => {
  return (
    <span className="flex items-center">
    <p className="text-[#262626] max-2xl:text-xs text-sm leading-[30px] font-medium">Search:</p>
      <input
        value={filter || ""}
        onChange={(e) => setFilter(e.target.value)}
        className="border shadow-[0px_1px_2px_0px_#1B283614] w-[170px] placeholder-[#BFBFBF] ml-2 pl-3 py-1 text-sm border-[#D9D9D9] rounded text-black"
        placeholder="Search"
      />
    </span>
  );
};
