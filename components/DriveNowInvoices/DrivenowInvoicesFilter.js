import React, { useState } from "react";
import SearchOutlinedIcon from "@mui/icons-material/SearchOutlined";

export const DriveNowInvoicesFilter = ({ filter, setFilter }) => {
  return (
    <span className="">
      <input
        value={filter || ""}
        onChange={(e) => setFilter(e.target.value)}
        className="border text-sm shadow-[0px_1px_2px_0px_#1B283614] placeholder-[#BFBFBF] w-[353px] pl-3 py-1 border-[#D9D9D9] rounded text-black"
        placeholder="Search by Name, Email or Phone Number"
      />
    </span>
  );
};
