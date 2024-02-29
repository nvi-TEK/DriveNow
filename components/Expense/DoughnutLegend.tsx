import React from "react";

type legendProp = {
    color: string,
    name: string
}

export default function DoughnutLegend(prop: legendProp){
    return(
<>
<div className="flex items-center rounded-[4px] border border-[#E6E6E6] py-[6px] px-3">
                  <div className="w-[10px] mr-1 h-[10px] border bg-[#0076EC] rounded-[50%]"></div>
                  Car Insurance
                </div>


</>
       
    )
}