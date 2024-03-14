/* eslint-disable require-jsdoc */
import React, { useState } from "react";
import { useRouter } from "next/router";
import Image from "next/image";
import List from "./sidebarList";
import logo from "../assets/logo.png";
import dashboard from "../assets/dashboard.svg";
import activedashboard from "../assets/activedashboard.svg";
import heatmap from "../assets/heatmap.svg";
import activeheatmap from "../assets/activeheatmap.svg";
import push from "../assets/push.svg";
import activepush from "../assets/activepush.svg";
import settings from "../assets/settings.svg";
import help from "../assets/help.svg";
import darkmode from "../assets/moon-star.svg";
import expense from "../assets/expense.svg";
import activeexpense from "../assets/activeexpense.svg";
import vehicle from "../assets/vehicleside.svg";
import downarrow from "../assets/downarrow.svg";
import uparrow from "../assets/uparrow.svg";
import driverside from "../assets/driverside.svg";
import transactionside from "../assets/transactionside.svg";
import Link from "next/link";
import { HelpRounded } from "@mui/icons-material";

function SideBar() {
  const selectChange = (
    event: React.ChangeEvent<HTMLInputElement>,
    eventTarget: string
  ) => {
    const { value, checked } = event.target;

    console.log(`${value} is ${checked}`);
  };

  const router = useRouter();
  const [showDriver, setShowDriver] = useState(false);
  const [showTransaction, setShowTransaction] = useState(false);
  const [showVehicle, setShowVehicle] = useState(false);

  const DriverArrow = showDriver ? uparrow : downarrow;
  const TransactionArrow = showTransaction ? uparrow : downarrow;
  const VehicleArrow = showVehicle ? uparrow : downarrow;

  return (
    <>
      <aside
        className="z-10 rounded-r-lg w-[17.6%] flex-shrink-0"
        aria-label="Sidebar"
      >
        <aside className="bg-white border-r pt-4 pb-7 shadow-[0px_4px_16px_0px_#0000001A] overflow-x-visible rounded-r-lg scroll-smooth no-scrollbar">
          <ul className="space-y-2 max-2xl:space-y-2 text-white">
            <Link href={"/views/dashboard"} as="">
              <List
                icon={dashboard}
                activeIcon={activedashboard}
                name={"Dashboard"}
                url="/views/dashboard"
              />
            </Link>

            <Link href={"/views/heatmap"} as="">
              <List
                icon={heatmap}
                activeIcon={activeheatmap}
                name={"Heat Map"}
                url="/views/heatmap"
              />
            </Link>

            <Link href={"/views/customPush"} as="">
              <List
                icon={push}
                activeIcon={activepush}
                name={"Custom Push/SMS"}
                url="/views/customPush"
              />
            </Link>

            <div
              className="flex ml-6 cursor-pointer max-2xl:pb-2 pb-2 justify-between items-center text-black"
              onClick={() => setShowDriver(!showDriver)}
            >
              <div className="flex max-2xl:text-[15px] max-2xl:py-0 py-2">
                <Image
                  src={driverside}
                  className="mr-2 max-2xl:w-5"
                  alt="tool icon"
                />
                Drivers
              </div>
              <div>
                <Image
                  className="mr-4 h-5 max-2xl:w-4 w-5"
                  src={DriverArrow}
                  alt="arrow"
                />
              </div>
            </div>
            <div className="pl-6 max-2xl:pl-2">
              {showDriver ? (
                <>
                  <Link href={"/views/Drivers/AllDrivers"} as="">
                    <List
                      name={"All Drivers"}
                      url="/views/Drivers/AllDrivers"
                    />
                  </Link>
                  <Link href={"/views/Drivers/DriverMapView"} as="">
                    <List
                      name={"Driver Map View"}
                      url="/views/Drivers/DriverMapView"
                    />
                  </Link>
                  <Link href={"/views/Drivers/DriverKyc"} as="">
                    <List name={"Driver KYC"} url="/views/Drivers/DriverKyc" />
                  </Link>
                </>
              ) : null}
            </div>

            <div
              className="flex ml-6 cursor-pointer max-2xl:pb-2 pb-2 justify-between items-center text-black"
              onClick={() => setShowTransaction(!showTransaction)}
            >
              <div className="flex max-2xl:text-[15px]">
                <Image
                  src={transactionside}
                  className="mr-2 max-2xl:w-5"
                  alt="tool icon"
                />
                Transactions
              </div>
              <Image
                className="mr-4 max-2xl:w-4 h-5 w-5"
                src={TransactionArrow}
                alt="arrow"
              />
            </div>
            <div className="pl-6 max-2xl:pl-2">
              {showTransaction ? (
                <>
                  <Link href={"/views/Transactions/payments"} as="">
                    <List
                      name={"Payments"}
                      url="/views/Transactions/payments"
                    />
                  </Link>
                  <Link href={"/views/Transactions/DrivenowInvoices"} as="">
                    <List
                      name={"DriveNow Invoices"}
                      url="/views/Transactions/DrivenowInvoices"
                    />
                  </Link>
                </>
              ) : null}
            </div>

            <div
              className="flex ml-6 cursor-pointer justify-between items-center text-black"
              onClick={() => setShowVehicle(!showVehicle)}
            >
              <div className="flex max-2xl:text-[15px] max-2xl:py-0 py-2">
                <Image
                  src={vehicle}
                  className="mr-2 max-2xl:w-5"
                  alt="tool icon"
                />
                Vehicle
              </div>
              <Image
                className="mr-4 h-5 max-2xl:w-4 w-5"
                src={VehicleArrow}
                alt="arrow"
              />
            </div>
            <div className="pl-6 max-2xl:pl-2">
              {showVehicle ? (
                <>
                  <Link href={"/views/Vehicles/ListofVehicles"} as="">
                    <List
                      name={"List of Vehicles"}
                      url="/views/Vehicles/ListofVehicles"
                    />
                  </Link>
                  <Link href={"/views/Vehicles/VehicleIssuesLogs"} as="">
                    <List
                      name={"Vehicle Issues Logs"}
                      url="/views/Vehicles/VehicleIssuesLogs"
                    />
                  </Link>
                  <Link href={"/views/Vehicles/VehicleTracker"} as="">
                    <List
                      name={"Vehicle Tracker"}
                      url="/views/Vehicles/VehicleTracker"
                    />
                  </Link>
                </>
              ) : null}
            </div>
            <Link href={"/views/expense"} as="">
              <List
                icon={expense}
                activeIcon={activeexpense}
                name={"Expense"}
                url="/views/expense"
              />
            </Link>

            <div className="flex justify-center ">
              <hr className="h-px my-6 w-[85%] bg-[#E6E6E6] border-0" />
            </div>

            <div>
              <Link href={""}>
                <List icon={settings} name={"Settings"} url="" />
              </Link>

              <Link href={""} as="">
                <List icon={help} name={"Help"} url="" />
              </Link>
            </div>

            <div className="flex pb-7 h-[52px] pt-[394px] max-2xl:pt-[290px] pl-6">
              <div>
                <Image
                  src={darkmode}
                  className="max-2xl:w-5"
                  alt="darkmode switch"
                />
              </div>

              <p className="text-[#262626] max-2xl:text-[15px] ml-2 ">
                Dark mode
              </p>

              <div className="ml-auto">
                <label className="relative inline-flex items-center cursor-pointer">
                  <input
                    type="checkbox"
                    value="7"
                    name="switch"
                    onChange={(event) =>
                      selectChange(event, event.target.tagName)
                    }
                    className="sr-only peer"
                  />
                  <div
                    className="w-12 h-7 mr-4 pl-1 bg-[#D9D9D9] rounded-[2.5rem] peer 
                                        peer-checked:after:translate-x-5 peer-checked:after:border-white after:content-[''] 
                                        after:absolute after:top-1  after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all  
                                        peer-checked:bg-black"
                  ></div>
                </label>
              </div>
            </div>
          </ul>
        </aside>
      </aside>
    </>
  );
}

export default SideBar;
