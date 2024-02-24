/* eslint-disable require-jsdoc */
import React from "react";
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

  return (
    <>
      <aside
        className="h-[50rem] border-r shadow-[0_0_60px_0_rgba(0,0,0,0.1)] mt-[1px] rounded-r-lg  w-[15rem] flex-shrink-0"
        aria-label="Sidebar"
      >
        <aside className="sidecolor py-4 overflow-x-visible rounded-r-lg overflow-y-scroll scroll-smooth lg:h-full no-scrollbar">
          <ul className="space-y-2 text-white">
            <Link href={"/dashboard"} as="">
              <List icon={dashboard} name={"Dashboard"} />
            </Link>

            <Link href={"/views/heatmap"} as="">
              <List icon={heatmap} name={"Heat Map"} />
            </Link>

            <Link href={"/views/customPush"} as="">
              <List icon={push} name={"Custom Push/SMS"} />
            </Link>
            <Link href={"/views/Drivers/AllDrivers"} as="">
              <List icon={push} name={"All Drivers"} />
            </Link>
            <Link href={"/views/Drivers/DriverKyc"} as="">
              <List icon={push} name={"Driver KYC"} />
            </Link>
            <Link href={"/views/Transactions/payments"} as="">
              <List icon={push} name={"Payments"} />
            </Link>
            <Link href={"/views/Transactions/DrivenowInvoices"} as="">
              <List icon={push} name={"DriveNow Invoices"} />
            </Link>
            <Link href={"/views/Vehicles/ListofVehicles"} as="">
              <List icon={push} name={"List of Vehicles"} />
            </Link>
            <Link href={"/views/Vehicles/VehicleIssuesLogs"} as="">
              <List icon={push} name={"Vehicle Issues Logs"} />
            </Link>
            <Link href={"/views/Vehicles/VehicleTracker"} as="">
              <List icon={push} name={"Vehicle Tracker"} />
            </Link>
            <Link href={"/views/expense"} as="">
              <List icon={expense} name={"Expense"} />
            </Link>

            <div className="flex justify-center ">
              <hr className="h-px my-6 w-[80%] bg-[#E6E6E6] border-0" />
            </div>

            <div>
              <Link href={""}>
                <List icon={settings} name={"Settings"} />
              </Link>

              <Link href={"/views/dashboard"} as="">
                <List icon={help} name={"Help"} />
              </Link>
            </div>

            <div className="flex h-[52px] pt-[432px] pl-6">
              <div>
                <Image src={darkmode} alt="darkmode switch" />
              </div>

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
