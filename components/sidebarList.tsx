/* eslint-disable require-jsdoc */
import React from "react";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/router";

type sideBarProp = {
  icon?: any;
  activeIcon?: any;
  name: string;
  url?: string;
};

function List(props: sideBarProp) {
  const router = useRouter();

  return (
    <li
      className={
        router.pathname == props.url
          ? "bg-[#F1F8FF] mx-2 rounded-lg text-[]"
          : ""
      }
    >
      <div className="flex items-center py-3 max-2xl:py-2 mx-2 pl-4 rounded-lg text-base font-medium hover:bg-[#F1F8FF]">
        <Image src={router.pathname == props.url ? props.activeIcon : props.icon} className="max-2xl:w-5" alt="" />
        <span
          className={
            router.pathname == props.url
              ? "flex-1 ml-2 leading-5 whitespace-nowrap text-base max-2xl:text-[15px]  font-normal text-[#007AF5]"
              : "flex-1 ml-2 leading-5 whitespace-nowrap text-base max-2xl:text-[15px]  font-normal text-[#262626]"
          }
        >
          {props.name}
        </span>
      </div>
    </li>
  );
}

export default List;
