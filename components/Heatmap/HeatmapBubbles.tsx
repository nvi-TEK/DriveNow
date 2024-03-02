/* eslint-disable require-jsdoc */
import React from "react";

type HeatmapBubbleProp = {
    status?: any;
    agreed?: any;
    engineControl?: any
};
export default function HeatmapBubble(props: HeatmapBubbleProp) {
    const color =
        props.status === "Online"
            ? "bg-[#E7F6F1] text-[#0EA371] text-sm font-medium mr-2 px-3 py-0.5 rounded-lg list-disc"
            : props.status === "Offline"
            ? "bg-[#FBEDEC] text-[#DC4A41] text-sm font-medium mr-2 px-3 py-0.5 rounded-lg"
            : "text-black text-sm font-medium mr-2 px-3 py-0.5 rounded-lg";
    return (
        <>
            <span className={`${color}`}>{props.status}</span>
        </>
    );
}
