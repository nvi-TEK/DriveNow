/* eslint-disable require-jsdoc */
import React from "react";

type bubbleProp = {
    status?: any;
    agreed?: any;
    engineControl?: any
};
export default function Bubble(props: bubbleProp) {
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


function AgreedBubble(props: bubbleProp) {
    const color =
        props.agreed === "Yes"
            ? "bg-[#E7F6F1] text-[#0EA371] text-sm font-medium mr-2 px-3 py-0.5 rounded-lg list-disc"
            : props.agreed === "No"
            ? "bg-[#FBEDEC] text-[#DC4A41] text-sm font-medium mr-2 px-3 py-0.5 rounded-lg"
            : "text-black text-sm font-medium mr-2 px-3 py-0.5 rounded-lg";
    return (
        <>
            <span className={`${color}`}>{props.agreed}</span>
        </>
    );
}


function EngineControlBubble(props: bubbleProp) {
    const color =
        props.engineControl == "ON"
            ? "bg-[#E7F6F1] text-[#0EA371] text-sm font-medium mr-2 px-3 py-0.5 rounded-lg list-disc"
            : props.engineControl === "OFF"
            ? "bg-[#FBEDEC] text-[#DC4A41] text-sm font-medium mr-2 px-3 py-0.5 rounded-lg"
            : "text-black text-sm font-medium mr-2 px-3 py-0.5 rounded-lg";
    return (
        <>
            <span className={`${color}`}>{props.engineControl}</span>
        </>
    );
}

export {AgreedBubble, EngineControlBubble}