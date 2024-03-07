import React, { useMemo, useState } from "react";
import { Button, ConfigProvider, Popover, Segmented } from "antd";
import Image from "next/image";
import info from "../assets/information_icon.svg";



const content = (
  <div className="w-[300px] pl-2 pr-4 rounded ">
    <p className="font-normal leading-[18px] text-sm">
      Driver info is required for background verification, checks and as part of
      regulations. This provides the business with confidence in who’s on the
      road.
    </p>
  </div>
);

const contract = (
  <div className="w-[290px] pl-2 pr-4 rounded">
    <p className="font-normal leading-[20px] text-sm">
      Contracts info provides the business with info on the number of new lease
      contracts obtained and assist the make informed decisions to drive more
      leads.
    </p>
  </div>
);

function DriverKYCPop() {
  return (
    <ConfigProvider
      theme={{
        token: {
          colorBgContainer: "#595959",
          colorBgElevated: "#595959",
          colorText: "white",
        },
      }}
    >
      <div className="demo ">
        <div className="">
          <Popover placement="topRight" content={content} trigger="hover">
            <div>
              <Image className="cursor-pointer w-[14px] h-[14px]"  src={info} alt="" />
            </div>
          </Popover>
        </div>
      </div>
    </ConfigProvider>
  );
}

function ContractPop() {
  return (
    <ConfigProvider
      theme={{
        token: {
          colorBgContainer: "#595959",
          colorBgElevated: "#595959",
          colorText: "white",
        },
      }}
    >
      <div className="demo ">
        <div className="">
          <Popover placement="topRight" content={contract} trigger="hover">
            <div>
              <Image className="cursor-pointer w-[14px] h-[14px]" src={info} alt="" />
            </div>
          </Popover>
        </div>
      </div>
    </ConfigProvider>
  );
}

export default DriverKYCPop;
export { ContractPop };
