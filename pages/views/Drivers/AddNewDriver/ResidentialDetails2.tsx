/* eslint-disable require-jsdoc */

/* eslint-disable react/no-unescaped-entities */
import React from "react";
import { useEffect, useState } from "react";
import KYC from "@/components/driverKYC";
import { Formik, Field, Form, ErrorMessage } from "formik";
import * as Yup from "yup";
import Head from "next/head";
import Layout from "../../../../components/layout";
import blueline from "../../../../assets/Line 11.png";
import greyline from "../../../../assets/greyline.png";
import StyledDropzone from "../../../../components/dropzone";
import CheckOutlinedIcon from "@mui/icons-material/CheckOutlined";
import { Tab, Tabs, TabList, TabPanel } from "react-tabs";
// import TableData from "../../../../components/Drivers/AllDriversTable";
import AllDriverTiles from "@/components/Drivers/DriverTiles";
import Link from "next/link";
import Image from "next/image";
import info from "../../../../assets/info_icon.png";
import greenline from "../../../../assets/greenline.png";

import InfoOutlinedIcon from "@mui/icons-material/InfoOutlined";
import driver from "../../../assets/driver_icon.png";
import plus from "../../../../assets/plus.png";
import vehicle from "../../../assets/vehicle_icon.png";
import payment from "../../../assets/payments.png";
import { DashboardTiles1 } from "@/components/tiles";
import DashboardTiles from "@/components/tiles";
import AccountMenu from "@/components/headerDropdown";
import { Grid } from "@mui/material";
// import { gridSpacing } from "../components/revenueChart/constant";
import BasicStacking from "@/components/stackedChart";
import Header from "@/components/header";

type ResidentialProps = {
  houseAddress: string;
  streetName: string;
  city: string;
  region: string;
  gpsAddress: string;
};

type UploadModel = {
  documents?: any;
};

export default function ResidentialDetails(prop: ResidentialProps) {
  return (
    <>
      <Header name="All Drivers / Drivers KYC" />

      <Layout>
        <Head>
          <title>Add New Driver</title>
          <meta name="description" content="Generated by create next app" />
          <meta name="viewport" content="width=device-width, initial-scale=1" />
        </Head>

        {/* Code goes into the main tag */}
        <main className="bg-[#F2F2F2] w-full xg:min-h-screen">
          {/* Bottom menu */}
          <section className="w-full ">
            <div className="bg-[#FFFFFF] mx-[19px] mt-[18px] ">
              <h3>Driver KYC</h3>
              <p>
                Complete this form to initiate background checks, ensuring we
                meet regulatory requirements and protect our business from
                potential risks.
              </p>

              {/* Timeline */}
              <div className="flex mx-4 py-10 px-[44px] border-y items-center">
                <div className="bg-[#0C9064] flex items-center justify-center  w-8 h-8 text-white rounded-[100%] ">
                  <CheckOutlinedIcon fontSize="small" />
                </div>
                <p className="pl-[10px] font-bold text-sm leading-[30px] text-[#0C9064]">
                  Personal Details
                </p>
                <Image
                  src={blueline}
                  alt="blue timeline"
                  className="w-[100px] ml-1"
                />
                <div className="bg-[#007AF5] flex items-center justify-center ml-1 w-8 h-8 text-white rounded-[100%] ">
                  2
                </div>
                <p className="pl-[10px] font-bold text-sm leading-[30px] text-[#007AF5]">
                  Residence Details{" "}
                </p>
                <Image
                  src={greyline}
                  alt="blue timeline"
                  className="w-[100px] ml-1"
                />
                <div className="bg-[#8C8C8C] flex items-center justify-center ml-1 w-8 h-8 text-white rounded-[100%] ">
                  3
                </div>
                <p className="pl-[10px] font-bold text-sm leading-[30px] text-[#8C8C8C]">
                  Guarantor Details{" "}
                </p>
                <Image
                  src={greyline}
                  alt="blue timeline"
                  className="w-[100px] ml-1"
                />
                <div className="bg-[#8C8C8C] flex items-center justify-center ml-1 w-8 h-8 text-white rounded-[100%] ">
                  4
                </div>
                <p className="pl-[10px] font-bold text-sm leading-[30px] text-[#8C8C8C]">
                  Relative Details{" "}
                </p>
              </div>

              <h3 className="font-medium leading-[30px] text-lg text-[#262626] ">
                Residence Details (Driver)
              </h3>

              <div className="mt-10 mb-12 border-0 shadow mx-">
                <Formik<ResidentialProps>
                  initialValues={{
                    streetName: "",
                    city: "",
                    houseAddress: "",
                    region: "",
                    gpsAddress: "",
                  }}
                  validationSchema={Yup.object({
                    houseAddress: Yup.string().required("Required field"),
                    streetName: Yup.string().required("Required field"),
                    city: Yup.string().required("Required field"),
                    region: Yup.string().required("Required field"),
                    gpsAddress: Yup.string().required("Required field"),
                  })}
                  onSubmit={async (values) => {
                    alert(JSON.stringify(values, null, 2));
                  }}
                >
                  {({ handleSubmit, values, handleChange, setFieldValue }) => (
                    <Form
                      onSubmit={handleSubmit}
                      className="bg-white border-0 shadow-lg px-[2rem]  pb-10"
                    >
                      <section className="border-b">
                        <div className="flex w-full">
                          {/* House Address */}
                          <div className="mt-[1.875rem]">
                            <label
                              htmlFor="houseAddress"
                              className="block mb-2 text-sm font-normal text-gray-900"
                            >
                              House Address{" "}
                            </label>
                            <Field
                              type="text"
                              id="houseAddress"
                              className="border border-gray-300 text-gray-900 text-sm rounded2 block w-[500px] p-2"
                              placeholder="Address"
                              value={values.houseAddress}
                              onChange={handleChange}
                            />
                            <p className="font-medium text-xs text-red-700">
                              <ErrorMessage name="houseAddress" />
                            </p>
                          </div>

                          {/* Street Name */}
                          <div className="mt-[1.875rem]">
                            <label
                              htmlFor="streetName"
                              className="block mb-2 text-sm font-normal text-gray-900"
                            >
                              Street Name{" "}
                            </label>
                            <Field
                              type="text"
                              id="streetName"
                              className="border border-gray-300 text-gray-900 text-sm rounded2 block w-[500px] p-2"
                              placeholder="Street Name"
                              value={values.streetName}
                              onChange={handleChange}
                            />
                            <p className="font-medium text-xs text-red-700">
                              <ErrorMessage name="streetName" />
                            </p>
                          </div>
                        </div>

                        <div className="flex">
                          {/* City */}
                          <div className="mt-[1.875rem]">
                            <label
                              htmlFor="city"
                              className="block mb-2 text-sm font-normal text-gray-900"
                            >
                              City{" "}
                            </label>
                            <Field
                              type="text"
                              id="city"
                              className="border border-gray-300 text-gray-900 text-sm rounded2 block w-[500px] p-2"
                              placeholder="City"
                              value={values.city}
                              onChange={handleChange}
                            />
                            <p className="font-medium text-xs text-red-700">
                              <ErrorMessage name="city" />
                            </p>
                          </div>

                          {/* Region */}
                          <div className="mt-[1.875rem]">
                            <label
                              htmlFor="region"
                              className="block mb-2 text-sm font-normal text-gray-900"
                            >
                              Region{" "}
                            </label>
                            <Field
                              type="text"
                              id="region"
                              className="border border-gray-300 text-gray-900 text-sm rounded2 block w-[500px] p-2"
                              placeholder="Region"
                              value={values.region}
                              onChange={handleChange}
                            />
                            <p className="font-medium text-xs text-red-700">
                              <ErrorMessage name="region" />
                            </p>
                          </div>
                        </div>
                      </section>

                      {/* GPS Address */}
                      <div className="mt-[1.875rem]">
                        <label
                          htmlFor="gpsAddress"
                          className="block mb-2 text-sm font-normal text-gray-900"
                        >
                          GPS Address
                        </label>
                        <Field
                          type="text"
                          id="gpsAddress"
                          className="border border-gray-300 text-gray-900 text-sm rounded2 block w-[500px] p-2"
                          placeholder="GT-123-456"
                          value={values.gpsAddress}
                          onChange={handleChange}
                        />
                        <p className="font-medium text-xs text-red-700">
                          <ErrorMessage name="gpsAddress" />
                        </p>
                      </div>

                      <section>
                        <p>Upload Image of Residence</p>
                        <StyledDropzone />
                        <div className="flex items-center">
                          <Image
                            src={info}
                            alt="info icon"
                            className="w-4 h-4"
                          />
                          <p className="text-[#737373] pl-1 font-normal leading-[30px] text-sm ">
                            This image can only be uploaded by Fleet
                            Manager/Fleet Officer
                          </p>
                        </div>
                      </section>

                      <div className="flex justify-between">
                        <Link
                          href={"/views/Drivers/AddNewDriver/PersonalDetails"}
                        >
                          <button
                            type="button"
                            className="text-[#FFFFFF] border mt-[57px] bg-[#007AF5] ml-auto rounded-[4px] w-[85px] border-[#DADADA] focus:outline-none text-sm   py-1.5 text-center inline-flex justify-center font-normal items-center mr-5 mb-2 "
                          >
                            Previous
                          </button>
                        </Link>

                        <Link
                          href={"/views/Drivers/AddNewDriver/PersonalDetails"}
                        >
                          <button
                            type="button"
                            className="text-[#FFFFFF] border mt-[57px] bg-[#007AF5] ml-auto rounded-[4px] w-[63px] border-[#DADADA] focus:outline-none text-sm   py-1.5 text-center inline-flex justify-center font-normal items-center mr-5 mb-2 "
                          >
                            Next{" "}
                          </button>
                        </Link>
                      </div>
                    </Form>
                  )}
                </Formik>
              </div>
            </div>
          </section>
        </main>
      </Layout>
    </>
  );
}
