/* eslint-disable react/no-unescaped-entities */
/* eslint-disable require-jsdoc */
import React from "react";
import KYC from "@/components/driverKYC";
import Head from "next/head";
import Layout from "../../components/layout";
import Link from "next/link";
import Image from "next/image";
import { DashChart } from "../../components/dashboard/dashboardColumnChart";
import driver from "../../assets/driver_icon.png";
import vehicle from "../../assets/vehicle_icon.png";
import payment from "../../assets/payments.png";
import { DashboardTiles1 } from "@/components/tiles";
import DashboardStack from "@/components/stackedChart";
import DashboardTiles from "@/components/tiles";
import MouseOverPopover from "@/components/popover";
import revenue from "../../assets/revenue_icon.png";
import Header from "@/components/header";

export default function Dashboard() {
  return (
    <>
      <Header name="Dashboard" />
      <Layout>
        <Head>
          <title>Dashboard</title>
          <meta name="description" content="Generated by create next app" />
          <meta name="viewport" content="width=device-width, initial-scale=1" />
        </Head>

        {/* Code goes into the main tag */}
        <main className="bg-[#F2F2F2] w-full xg:min-h-screen">
          {/* Bottom menu */}
          <section className="w-full  ">
            <div className="flex gap-5 mt-5 px-5 mb-6  grow">
              <DashboardTiles
                icon={revenue}
                entity1="Revenue"
                entity1value={2412570.0}
                entity2="Total Payments"
                entity2value={450}
                entity2differential="+20.00%"
              />

              <DashboardTiles
                icon={driver}
                entity1="Total Drivers"
                entity1value={1250}
                entity2="Active Drivers"
                entity2value={1180}
                entity2differential="-4.90%"
                entity1differential="+15.80%"
              />

              <DashboardTiles1
                icon={payment}
                entity1="Total Payments"
                entity1value={450}
                entity2="Failed"
                entity2value={5}
                entity3="Completed"
                entity3value={445}
              />
            </div>

            <div className="flex gap-5 mt-5 px-5  grow">
              <DashboardTiles
                icon={revenue}
                entity1="Revenue"
                entity1value={2412570.0}
                entity2="Total Payments"
                entity2value={450}
                entity2differential="+20.00%"
              />

              <DashboardTiles
                icon={vehicle}
                entity1="Total Vehicles"
                entity1value={2412570.0}
                entity2="Active Vehicles"
                entity2value={450}
                entity2differential="+20.00%"
              />

              <DashboardTiles1
                icon={payment}
                entity1="Total Payments"
                entity1value={450}
                entity2="Failed"
                entity2value={5}
                entity3="Completed"
                entity3value={445}
              />
            </div>
          </section>

          <section className="flex px-5 justify-between mb-5">
            <div className="mt-6 w-[74%] ">
              <div className="bg-white h-[657px]  rounded-[8px]">
                <div className="p-[32px]">
                  <h6 className="text-[#777777] font-medium leading-[14.06px] text-xs">
                    Total Revenue
                  </h6>
                  <p className="text-xl font-medium pt-2 leading-7 text-[#262626]   ">
                    ₵2,412,570.00
                  </p>
                </div>

                <div className="px-[32px]">
                <DashboardStack />
                </div>
                <div className="flex mt-[30px] px-[32px] justify-evenly ">
                  <div className="flex">
                    <div className="h-[14px] w-[14px] rounded bg-[#A6D2FF]"></div>
                    <p className="font-normal text-xs pl-1 leading-[14px] text-[#585858]">
                      Online Payment
                    </p>
                  </div>

                  <div className="flex">
                    <div className="h-[14px] w-[14px] rounded bg-[#1F8FFF]"></div>
                    <p className="font-normal text-xs pl-1 leading-[14px] text-[#585858]">
                      Manual Payment
                    </p>
                  </div>

                  <div className="flex">
                    <div className="h-[14px] w-[14px] rounded bg-[#D52D4D]"></div>
                    <p className="font-normal text-xs pl-1 leading-[14px] text-[#585858]">
                      Loss
                    </p>
                  </div>

                  <div className="flex">
                    <div className="h-[14px] w-[14px] rounded bg-[#FFDBE2]"></div>
                    <p className="font-normal text-xs pl-1 leading-[14px] text-[#585858]">
                      Maintenance
                    </p>
                  </div>
                </div>
              </div>
              <div className="bg-white l-7 rounded-[8px] mt-5">
                <div className="flex mb-[33px]">
                  <h4 className="text-[#262626] text-base font-medium leading-[22px] pt-[15px] pl-5">
                    Contracts
                  </h4>
                </div>
                <div className="pl-5">
                  <DashChart />
                </div>
              </div>
            </div>

            {/* Driver KYC  */}
            <div className="mt-6 bg-[#FFFFFF] rounded-lg ml-5 pb-5 w-[25%] ">
              <div className="flex  ">
                <p className="m-5 text-base font-medium leading-[22px] text-[#262626] ">
                  Driver KYC
                </p>
              </div>
              <div className="mx-[6%] ">
                <KYC
                  name="Frank Mensah"
                  description="Driver’s License Uploaded"
                  date="12 Sept"
                  status="Completed"
                />
                <KYC
                  name="Frank Mensah"
                  description="Driver’s License Uploaded"
                  date="12 Sept"
                  status="Completed"
                />
                <KYC
                  name="Frank Mensah"
                  description="Driver’s License Uploaded"
                  date="12 Sept"
                  status="Completed"
                />
                <KYC
                  name="Frank Mensah"
                  description="Driver’s License Uploaded"
                  date="12 Sept"
                  status="Completed"
                />
                <KYC
                  name="Frank Mensah"
                  description="Driver’s License Uploaded"
                  date="12 Sept"
                  status="Completed"
                />
                <KYC
                  name="Frank Mensah"
                  description="Driver’s License Uploaded"
                  date="12 Sept"
                  status="Completed"
                />
                <KYC
                  name="Frank Mensah"
                  description="Driver’s License Uploaded"
                  date="12 Sept"
                  status="Completed"
                />
                <KYC
                  name="Frank Mensah"
                  description="Driver’s License Uploaded"
                  date="12 Sept"
                  status="Completed"
                />
                <KYC
                  name="Frank Mensah"
                  description="Driver’s License Uploaded"
                  date="12 Sept"
                  status="Completed"
                />
              </div>
            </div>
          </section>
    
        </main>
      </Layout>
    </>
  );
}