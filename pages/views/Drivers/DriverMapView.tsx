/* eslint-disable require-jsdoc */
/* eslint-disable react/no-unescaped-entities */
import React from "react";
import Head from "next/head";
import Layout from "../../../components/layout";
import Image from "next/image";
import driver from "../../../assets/driver.svg";
import onlinedriver from "../../../assets/onlinedriver.svg";
import vehilce from "../../../assets/vehicle_icon.png";
import offlinedriver from "../../../assets/offlinedriver.svg";
import vehicle from "../../../assets/vehicle.svg";
import Header from "@/components/header";
import ListOfVehiclesTiles from "@/components/Vehicle/ListOfVehiclesTiles";
import DriverMap from "@/components/Drivers/DriverMap";
export default function DriverKyc() {
  return (
    <>
<div className="flex w-full">
        <div className="w-full z-10">
          <Header name="Driver Map View"/>
        </div>
      </div>
      <Layout>
        <Head>
          <title>Driver KYC</title>
          <meta name="description" content="Generated by create next app" />
          <meta name="viewport" content="width=device-width, initial-scale=1" />
        </Head>

        {/* Code goes into the main tag */}
        <main className="bg-[#F2F2F2] w-full xg:min-h-screen">
          <section className="w-full pb-[130px] ">
            <div className="flex space-x-4 grow m-5">
              <ListOfVehiclesTiles
                icon={vehicle}
                entity1="Total Vehicles"
                entity1value={1250}
                entity1differential="+15.80%"
                entity2="Active Vehicles"
                entity2value={1180}
                entity2differential="+4.90%"
                entity3="Damaged"
                entity3value={70}
                entity3differential="-4.90%"
              />
              <ListOfVehiclesTiles
                icon={driver}
                entity1="Total Drivers"
                entity1value={1250}
                entity1differential="+15.80%"
                entity2="Online Drivers"
                entity2value={1180}
                entity3="Offline Drivers"
                entity3value={70}
                entity3differential="-4.90%"
              />
            </div>

            {/* Map */}
            <div className="rounded-lg bg-white h-[443px] mx-5 mt-[24px]">
              <h3 className="text-[#262626] p-4 font-medium leading-[30px] text-[22px]">
                Driver Map View
              </h3>

              <div className="h-[365px] px-4 ">
                <DriverMap />
              </div>
            </div>

            <section className="rounded-lg h-[115px] bg-white mx-5 mt-[18px]">
              <h5 className="text-center font-medium leading-[30px] text-[22px] text-[#262626] pt-4">
                Legend
              </h5>
              <div className="flex mt-4 justify-evenly">
                <div className="flex items-center">
                  <Image src={onlinedriver} alt="red car icon" />
                  <p className="text-[#262626] pl-4 leading-[30px] font-normal text-sm">
                    Online Driver
                  </p>
                </div>

                <div className="flex items-center">
                  <Image src={offlinedriver} alt="black car icon" />
                  <p className="text-[#262626] pl-4 leading-[30px] font-normal text-sm">
                    Offline Driver
                  </p>
                </div>
              </div>
            </section>
          </section>
        </main>
      </Layout>
    </>
  );
}
