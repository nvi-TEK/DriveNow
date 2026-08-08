/* eslint-disable require-jsdoc */
import React, { useState, useEffect } from "react";
import { useRouter } from "next/router";
import { useTheme } from "next-themes";
import Image from "next/image";
import List from "./sidebarList";
import dashboard from "../assets/dashboard.svg";
import activedashboard from "../assets/activedashboard.svg";
import heatmap from "../assets/heatmap.svg";
import activeheatmap from "../assets/activeheatmap.svg";
import push from "../assets/push.svg";
import activepush from "../assets/activepush.svg";
import expense from "../assets/expense.svg";
import activeexpense from "../assets/activeexpense.svg";
import vehicle from "../assets/vehicleside.svg";
import downarrow from "../assets/downarrow.svg";
import uparrow from "../assets/uparrow.svg";
import driverside from "../assets/driverside.svg";
import transactionside from "../assets/transactionside.svg";
import KeyboardArrowDownIcon from "@mui/icons-material/KeyboardArrowDown";
import KeyboardArrowUpIcon from "@mui/icons-material/KeyboardArrowUp";
import Link from "next/link";
import { HelpRounded } from "@mui/icons-material";
import ThemeToggle from "./themeToggle";

function SideBar() {
  const [mounted, setMounted] = useState(false);
  const { setTheme, resolvedTheme } = useTheme();

  useEffect(() => setMounted(true), []);

  const router = useRouter();

  const driverUrls = [
    "/views/Drivers/AllDrivers",
    "/views/Drivers/DriverMapView",
    "/views/Drivers/DriverKyc",
  ];
  const transactionUrls = [
    "/views/Transactions/payments",
    "/views/Transactions/DrivenowInvoices",
  ];
  const vehicleUrls = [
    "/views/Vehicles/ListofVehicles",
    "/views/Vehicles/VehicleIssuesLogs",
    "/views/Vehicles/VehicleTracker",
  ];

  const [showDriver, setShowDriver] = useState(
    driverUrls.includes(router.pathname)
  );
  const [showTransaction, setShowTransaction] = useState(
    transactionUrls.includes(router.pathname)
  );
  const [showVehicle, setShowVehicle] = useState(
    vehicleUrls.includes(router.pathname)
  );

  const DriverArrow = showDriver ? uparrow : downarrow;
  const TransactionArrow = showTransaction ? uparrow : downarrow;
  const VehicleArrow = showVehicle ? uparrow : downarrow;

  return (
    <aside
      className="z-10 w-[17.6%] flex-shrink-0 h-[calc(100vh-4rem)] max-2xl:h-[calc(100vh-55px)] sticky top-[4rem] max-2xl:top-[55px]"
      aria-label="Sidebar"
    >
      <div className="bg-white dark:bg-dm-700 rounded-r-lg h-full border-r dark:border-0 pt-4 shadow-[0px_4px_16px_0px_#0000001A] overflow-x-visible overflow-y-auto no-scrollbar">
        <ul className="flex flex-col h-full gap-2 text-white">
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
            className={`flex items-center justify-between py-3 max-2xl:py-2 mx-2 pl-4 cursor-pointer rounded-lg text-black hover:bg-[#F1F8FF] dark:hover:bg-dm-600 ${
              driverUrls.includes(router.pathname)
                ? "bg-[#F1F8FF] dark:bg-dm-600"
                : ""
            }`}
            onClick={() => setShowDriver(!showDriver)}
          >
            <div className="flex dark:text-white max-2xl:text-[15px]">
              <Image
                src={driverside}
                className="mr-2 w-5 max-2xl:w-5 dark:brightness-0 dark:invert"
                alt="tool icon"
              />
              <p id="sidebar-text" className="leading-5">Drivers</p>
            </div>
            <div>
              <Image
                className="mr-4 h-5 max-2xl:w-4 w-5 dark:brightness-0 dark:invert"
                src={DriverArrow}
                alt="arrow"
              />
            </div>
          </div>
          {showDriver ? (
            <div className="pl-6 max-2xl:pl-2 flex flex-col gap-2">
              <Link href={"/views/Drivers/AllDrivers"} as="">
                <List name={"All Drivers"} url="/views/Drivers/AllDrivers" />
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
            </div>
          ) : null}

          <div
            className={`flex items-center justify-between py-3 max-2xl:py-2 mx-2 pl-4 cursor-pointer rounded-lg text-black hover:bg-[#F1F8FF] dark:hover:bg-dm-600 ${
              transactionUrls.includes(router.pathname)
                ? "bg-[#F1F8FF] dark:bg-dm-600"
                : ""
            }`}
            onClick={() => setShowTransaction(!showTransaction)}
          >
            <div className="flex dark:text-white max-2xl:text-[15px]">
              <Image
                src={transactionside}
                className="mr-2 w-5 max-2xl:w-5 dark:brightness-0 dark:invert"
                alt="tool icon"
              />
              <p id="sidebar-text" className="leading-5">Transactions</p>
            </div>
            <Image
              className="mr-4 max-2xl:w-4 h-5 w-5 dark:brightness-0 dark:invert"
              src={TransactionArrow}
              alt="arrow"
            />
          </div>
          {showTransaction ? (
            <div className="pl-6 max-2xl:pl-2 flex flex-col gap-2">
              <Link href={"/views/Transactions/payments"} as="">
                <List name={"Payments"} url="/views/Transactions/payments" />
              </Link>
              <Link href={"/views/Transactions/DrivenowInvoices"} as="">
                <List
                  name={"DriveNow Invoices"}
                  url="/views/Transactions/DrivenowInvoices"
                />
              </Link>
            </div>
          ) : null}

          <div
            className={`flex items-center justify-between py-3 max-2xl:py-2 mx-2 pl-4 cursor-pointer rounded-lg text-black hover:bg-[#F1F8FF] dark:hover:bg-dm-600 ${
              vehicleUrls.includes(router.pathname)
                ? "bg-[#F1F8FF] dark:bg-dm-600"
                : ""
            }`}
            onClick={() => setShowVehicle(!showVehicle)}
          >
            <div className="flex max-2xl:text-[15px] dark:text-white">
              <Image
                src={vehicle}
                className="mr-2 w-5 max-2xl:w-5 dark:brightness-0 dark:invert"
                alt="tool icon"
              />
              <p id="sidebar-text" className="leading-5">Vehicle</p>
            </div>
            <Image
              className="mr-4 h-5 max-2xl:w-4 w-5 dark:brightness-0 dark:invert"
              src={VehicleArrow}
              alt="arrow"
            />
          </div>
          {showVehicle ? (
            <div className="pl-6 max-2xl:pl-2 flex flex-col gap-2">
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
            </div>
          ) : null}
          <Link href={"/views/expense"} as="">
            <List
              icon={expense}
              activeIcon={activeexpense}
              name={"Expense"}
              url="/views/expense"
            />
          </Link>

          <div className="flex ml-6 mr-4 mt-auto pb-4 justify-between items-center text-black">
            <p className="text-[#262626] max-2xl:text-sm dark:text-white">
              Theme
            </p>
            <ThemeToggle />
          </div>
        </ul>
      </div>
    </aside>
  );
}

export default SideBar;
