/* eslint-disable require-jsdoc */
/* eslint-disable react/no-unescaped-entities */
import React from "react";
import { useEffect, useState } from "react";
import Head from "next/head";
import Layout from "../../../components/layout";
import { ListofVehiclesTiles } from "@/components/Vehicle/ListOfVehiclesTiles";
import { ListOfVehiclesTable } from "../../../components/Vehicle/ListOfVehiclesTable";
import Link from "next/link";
import Image from "next/image";
import vehicle from "../../../assets/vehicle.svg";
import repair from "../../../assets/repair.svg";
import payment from "../../../assets/payments.png";
import bell from "../../../assets/bell_icon.png";
import plus from "../../../assets/plus.png";

import BasicStacking from "@/components/stackedChart";
import Header from "@/components/header";

export default function ListOfVehicles() {
  const [modalIsOpen, setModalIsOpen] = useState(false);

  const openModal = () => {
    setModalIsOpen(true);
  };

  const closeModal = () => {
    setModalIsOpen(false);
  };

  const customStyles = {
    content: {
      top: "50%",
      left: "50%",
      right: "auto",
      bottom: "auto",
      marginRight: "-50%",
      transform: "translate(-50%, -50%)",
      padding: "0",
      width: "308px",
      height: "183px",
      borderRadius: "8px 8px 8px 8px",
      backgroundColor: "white",
    },
    overlay: {
      background: "#0000008F",
    },
  };

  return (
    <>
      <div className="flex w-full">
        <div className="w-full z-10">
          <Header name="Vehicles" />
        </div>
      </div>
      <Layout>
        <Head>
          <title>List of Vehicles</title>
          <meta name="description" content="Generated by create next app" />
          <meta name="viewport" content="width=device-width, initial-scale=1" />
        </Head>

        {/* Code goes into the main tag */}
        <main className="bg-[#F2F2F2] w-full xg:min-h-screen">
          {/* Bottom menu */}
          <section className="w-full ">
            <div className="flex space-x-4 grow m-5">
              <ListofVehiclesTiles
                icon={vehicle}
                entity1="Total Vehicles"
                entity1value={1250}
                entity1differential={+15.8}
                entity2="Allocated Vehicles"
                entity2value={450}
                entity3="Unallocated Vehicles"
                entity3value={70}
              />
              <ListofVehiclesTiles
                icon={repair}
                entity1="Completed repairs"
                entity1value={20}
                entity2="Active Repairs"
                entity2value={450}
                entity3="Out of service"
                entity3value={15}
                entity1differential={-5.8}
              />
            </div>

            {/* Table */}

            <div className="bg-white rounded-lg px-[10px] mx-5 mb-12 mt-6 ">
              <div className="flex justify-between ">
                <h4 className="text-[#262626] font-medium text-[22px] leading-[30px] pt-4 ">
                  List of Vehicles{" "}
                </h4>
                <Link href={"/views/Vehicles/AddVehicle/VehicleDetails"}>
                  <button
                    type="button"
                    className="text-[#FFFFFF] border mt-4 bg-[#007AF5] rounded-[4px] w-[] focus:outline-none text-sm px-4 py-1.5 text-[14px] text-center inline-flex justify-center font-normal items-center mb-2 "
                  >
                    <Image src={plus} alt="plus sign" className="ml-0 mr-1" />
                    Add New Vehicle
                  </button>
                </Link>
              </div>
              <ListOfVehiclesTable />
            </div>
          </section>
        </main>
      </Layout>
    </>
  );
}
