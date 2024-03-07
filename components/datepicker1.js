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
        
        <div>
          <input
            type="date"
            value={date}
            onChange={onDateChange}
            className="rounded-[4px] border border-[#D9D9D9]  text-[#BFBFBF] w-[100%] py-1 px-2 "
          />
        </div>
      </div>
    </>
  );
}
