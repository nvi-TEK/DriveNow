/* eslint-disable require-jsdoc */
/* eslint-disable react/no-unescaped-entities */
import React from "react";
import { useEffect, useState } from "react";
import Modal from "react-modal";
import KYC from "@/components/driverKYC";
import Head from "next/head";
import Layout from "../../../components/layout";
import { Formik, Form, Field, ErrorMessage } from "formik";
import * as Yup from "yup";
import { Tab, Tabs, TabList, TabPanel } from "react-tabs";
import TableData from "../../../components/Drivers/AllDriversTable";
import ListOfVehiclesTiles from "../../../components/Vehicle/ListOfVehiclesTiles";
import { VehicleIssuesLogsTable } from "../../../components/Vehicle/VehicleIssuesLogs/VehicleIssuesLogsTable";
import Link from "next/link";
import Image from "next/image";
import driver from "../../../assets/driver_icon.png";
import revenue from "../../../assets/revenue_icon.png";
import vehicle from "../../../assets/vehicle_icon.png";
import repair from "../../../assets/repair.png";
import payment from "../../../assets/payments.png";
import bell from "../../../assets/bell_icon.png";
import plus from "../../../assets/plus.png";

import BasicStacking from "@/components/stackedChart";

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
              <ListOfVehiclesTiles
                icon={vehicle}
                entity1="Total Vehicles"
                entity1value={1250}
                entity1differential="+15.80%"
                entity2="Allocated Vehicles"
                entity2value={1180}
                entity3="Unallocated Vehicles"
                entity3value={70}
              />
              <ListOfVehiclesTiles
                icon={repair}
                entity1="Completed Issues"
                entity1value={20}
                entity1differential="+5.80%"
                entity2="Active Issues"
                entity2value={450}
                entity3="Out of service"
                entity3value={15}
              />
            </div>

        

            <div className="bg-white rounded-lg mx-5 mb-12 mt-6 ">
              <div className="flex justify-between ">
                <h4 className="text-[#262626] font-medium text-[22px] leading-[30px] pl-[10px] pt-4 ">
                  Vehicle Issue Log
                </h4>
                <Link href={"AddVehicleIssue"}>
                  <button
                    type="button"
                    className="text-[#FFFFFF] border mt-4 bg-[#007AF5] rounded-[4px] w-[] focus:outline-none text-sm px-4 py-1.5 text-[14px] text-center inline-flex justify-center font-normal items-center mr-5 mb-2 "
                  >
                    <Image src={plus} alt="plus sign" className="ml-0 mr-1" />
                    Add Vehicle Issue Log
                  </button>
                </Link>
              </div>
              <VehicleIssuesLogsTable />
            </div>
          </section>
        </main>
      </Layout>
    </>
  );
}