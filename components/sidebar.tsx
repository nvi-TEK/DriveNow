/* eslint-disable require-jsdoc */
import React, { useState } from "react";
import Image from "next/image";
import List, { Title } from "./sidebarList";
import logo from "../assets/logo.png";
import dashboard from "../assets/dashboard_logo.png";
import heatmap from "../assets/heatmap.png";
import push from "../assets/push.png";
import settings from "../assets/settings.png";
import help from "../assets/help.png";
import darkmode from "../assets/moon-star.png";
import expense from "../assets/expense_icon.png";
import vehicle from "../assets/vehicleSidebar.png";
import downarrow from "../assets/arrowdown.png";
import uparrow from "../assets/arrowup.png";
import driverside from "../assets/driverSidebar.png";
import transactionside from "../assets/transactionSide.png";
// import Support from "../assets/support.png";
// import Transaction from "../assets/transactions.png";
// import loan from "../assets/loan.svg";
// import dashboard from "../assets/dashboard.svg";
// import percent from "../assets/percent.png";
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

  const [showDriver, setShowDriver] = useState(false);
  const [showTransaction, setShowTransaction] = useState(false);
  const [showVehicle, setShowVehicle] = useState(false);

  const DriverArrow = showDriver ? uparrow : downarrow;
  const TransactionArrow = showTransaction ? uparrow : downarrow;
  const VehicleArrow = showVehicle ? uparrow : downarrow;

  return (
    <>
      <aside
        className="h-[63rem] border-r shadow-[0_0_60px_0_rgba(0,0,0,0.1)] mt-[1px] rounded-r-lg  w-[15rem] flex-shrink-0"
        aria-label="Sidebar"
      >
        <aside className="sidecolor py-4 overflow-x-visible rounded-r-lg overflow-y-scroll scroll-smooth lg:h-full no-scrollbar">
          <ul className="space-y-2 text-white">
            <Link href={"/dashboard"} as="">
              <List icon={dashboard} name={"Dashboard"} url="/dashboard" />
            </Link>

            <Link href={"/views/heatmap"} as="">
              <List icon={heatmap} name={"Heat Map"} url="/views/heatmap" />
            </Link>

            <Link href={"/views/customPush"} as="">
              <List
                icon={push}
                name={"Custom Push/SMS"}
                url="/views/customPush"
              />
            </Link>

            <div
              className="flex ml-6 cursor-pointer justify-between items-center text-black"
              onClick={() => setShowDriver(!showDriver)}
            >
              <div className="flex py-2">
                <Image src={driverside} className="mr-2" alt="tool icon" />
                Drivers
              </div>
              <Image className="mr-4 h-2 w-3" src={DriverArrow} alt="arrow" />
            </div>
            <div className="pl-6">
              {showDriver ? (
                <>
                  <Link href={"/views/Drivers/AllDrivers"} as="">
                    <List
                      name={"All Drivers"}
                      url="/views/Drivers/AllDrivers"
                    />
                  </Link>
                  <Link href={"/views/Drivers/DriverKyc"} as="">
                    <List name={"Driver KYC"} url="/views/Drivers/DriverKyc" />
                  </Link>
                </>
              ) : null}
            </div>

            <div
              className="flex ml-6 cursor-pointer justify-between items-center text-black"
              onClick={() => setShowTransaction(!showTransaction)}
            >
              <div className="flex py-2">
                <Image src={transactionside} className="mr-2" alt="tool icon" />
                Transactions
              </div>
              <Image
                className="mr-4 h-2 w-3"
                src={TransactionArrow}
                alt="arrow"
              />
            </div>
            <div className="pl-6">
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
              <div className="flex py-2">
                <Image src={vehicle} className="mr-2" alt="tool icon" />
                Vehicle
              </div>
              <Image className="mr-4 h-2 w-3" src={VehicleArrow} alt="arrow" />
            </div>
            <div className="pl-6">
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
              <List icon={expense} name={"Expense"} url="/views/expense" />
            </Link>

            <div className="flex justify-center ">
              <hr className="h-px my-6 w-[80%] bg-[#E6E6E6] border-0" />
            </div>

            <div>
              <Link href={""}>
                <List icon={settings} name={"Settings"} url="" />
              </Link>

              <Link href={"/views/dashboard"} as="">
                <List icon={help} name={"Help"} url="" />
              </Link>
            </div>

            <div className="flex  h-[52px] pt-[432px] pl-6">
              <div>
                <Image src={darkmode} alt="darkmode switch" />
              </div>4

              <p className="text-[#262626] ml-2 ">Dark mode</p>

              <div className="ml-auto mt">
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
                    className="w-12 h-6 mr-4 bg-[#D9D9D9] rounded-[2.5rem] peer 
                                        peer-checked:after:translate-x-6 peer-checked:after:border-white after:content-[''] 
                                        after:absolute after:top-0.5  after:bg-white after:border after:rounded-full after:h-5 after:w-5 after:transition-all  
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
