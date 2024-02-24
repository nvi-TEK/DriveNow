import React, { useState } from "react";
import SearchOutlinedIcon from "@mui/icons-material/SearchOutlined";

export const ExpenseFilter = ({ filter, setFilter }) => {
  return (
    <span className="">
      <input
        value={filter || ""}
        onChange={(e) => setFilter(e.target.value)}
        className="border w-[353px] pl-3 py-1 border-[#D9D9D9] rounded text-black"
        placeholder="Search"
      />
    </span>
  );
};