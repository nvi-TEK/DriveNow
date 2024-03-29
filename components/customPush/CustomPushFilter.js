import React, { useState } from "react";
import SearchOutlinedIcon from "@mui/icons-material/SearchOutlined";

export const CustomPushFilter = ({ filter, setFilter }) => {
  return (
    <span className="">
      <input
        value={filter || ""}
        onChange={(e) => setFilter(e.target.value)}
        className="border w-[164px] placeholder-[#BFBFBF] pl-3 py-1.5 text-xs shadow-[0px_1px_2px_0px_#1B283614] border-[#D9D9D9] rounded text-black"
        placeholder="Select group to send"
      />
    </span>
  );
};
