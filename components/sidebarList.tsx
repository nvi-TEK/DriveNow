/* eslint-disable require-jsdoc */
import React from "react";
import Image from "next/image";
import Link from "next/link";
import { useRouter } from "next/router";

type sideBarProp = {
  icon?: any;
  name: string;
  url?: string;
};

function List(props: sideBarProp) {
  const router = useRouter();

  return (
    <li
      className=
      {router.pathname == props.url
        ? "active: bg-[#F1F8FF] mx-2 rounded-lg text-[]"
        : ""}>
      <div className="flex items-center py-3 mx-2 pl-4 rounded-lg text-base font-medium hover:bg-[#F1F8FF]">
        <Image src={props.icon} alt="" />
        <span className={router.pathname == props.url
        ? "active: flex-1 ml-2 leading-5 whitespace-nowrap text-base font-normal text-[#007AF5]"
        : "flex-1 ml-2 leading-5 whitespace-nowrap text-base font-normal text-[#262626]"}>
          {props.name}
        </span>
      </div>
    </li>
  );
}

function Title(props: sideBarProp) {
  return (
    <li>
      <span className="flex items-center flex-1 ml-9 whitespace-nowrap p-2 mt-3 text-base font-normal  rounded-lg dark:text-white">
        <Image src={props.icon} className="pr-1" alt="" /> {props.name}
      </span>
    </li>
  );
}

export default List;
export { Title };
