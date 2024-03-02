import React, { useState } from "react";

export default function Datepicker() {
  const [date, setDate] = useState("none");
  const onDateChange = (event) => {
    setDate(event.target.value);
  };
  const mystyle = {
    color: "black",
    backgroundColor: "",
    padding: "",
    width: "100%",
  };

  return (
    <>
      <div style={mystyle}>
        <p className="text-[#404040] pb-[10px] font-normal text-sm leading-[18px] ">
          Date
        </p>
        <div>
          <input
            type="date"
            disabled
            value={date}
            onChange={onDateChange}
            className="rounded-[4px] border border-[#D9D9D9] bg-[#F0F0F0] text-[#BFBFBF] w-[100%] py-1 px-2 "
          />
        </div>
      </div>
    </>
  );
}
