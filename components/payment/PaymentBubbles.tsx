/* eslint-disable require-jsdoc */
import React from "react";

type PaymentBubbleProp = {
    status?: any;
    agreed?: any;
    engineControl?: any
};
export default function Bubble(props: PaymentBubbleProp) {
    const color =
        props.status === "Online"
            ? "bg-[#E7F6F1] dark:bg-[#0EA37133] text-[#0EA371] dark:text-[#34D399] text-sm font-medium mr-2 px-3 py-0.5 rounded-lg list-disc"
            : props.status === "Offline"
            ? "bg-[#FBEDEC] dark:bg-[#DC4A4133] text-[#DC4A41] dark:text-[#F87171] text-sm font-medium mr-2 px-3 py-0.5 rounded-lg"
            : "text-black dark:text-white text-sm font-medium mr-2 px-3 py-0.5 rounded-lg";
    return (
        <>
            <span className={`${color}`}>{props.status}</span>
        </>
    );
}


function AgreedBubble(props: PaymentBubbleProp) {
    const color =
        props.agreed === "Yes"
            ? "bg-[#E7F6F1] dark:bg-[#0EA37133] text-[#0EA371] dark:text-[#34D399] text-sm font-medium mr-2 px-3 py-0.5 rounded-lg list-disc"
            : props.agreed === "No"
            ? "bg-[#FBEDEC] dark:bg-[#DC4A4133] text-[#DC4A41] dark:text-[#F87171] text-sm font-medium mr-2 px-3 py-0.5 rounded-lg"
            : "text-black dark:text-white text-sm font-medium mr-2 px-3 py-0.5 rounded-lg";
    return (
        <>
            <span className={`${color}`}>{props.agreed}</span>
        </>
    );
}


function EngineControlBubble(props: PaymentBubbleProp) {
    const color =
        props.engineControl == "ON"
            ? "bg-[#E7F6F1] dark:bg-[#0EA37133] text-[#0EA371] dark:text-[#34D399] text-sm font-medium mr-2 px-3 py-0.5 rounded-lg list-disc"
            : props.engineControl === "OFF"
            ? "bg-[#FBEDEC] dark:bg-[#DC4A4133] text-[#DC4A41] dark:text-[#F87171] text-sm font-medium mr-2 px-3 py-0.5 rounded-lg"
            : "text-black dark:text-white text-sm font-medium mr-2 px-3 py-0.5 rounded-lg";
    return (
        <>
            <span className={`${color}`}>{props.engineControl}</span>
        </>
    );
}

export {AgreedBubble, EngineControlBubble}