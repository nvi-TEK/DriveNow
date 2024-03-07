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
import ResidentialDetailsMap from "../../../../components/Drivers/DriverKyc/ResidentialDetailsMap";
import { Tab, Tabs, TabList, TabPanel } from "react-tabs";
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
};

type UploadModel = {
  documents?: any;
};

export default function ResidentialDetails() {
  return (
    <>
<div className="flex w-full">
        <div className="w-full z-10">
          <Header />
        </div>
      </div>
      <Layout>
        <Head>
          <title>Add New Driver</title>
          <meta name="description" content="Generated by create next app" />
          <meta name="viewport" content="width=device-width, initial-scale=1" />
        </Head>

        {/* Code goes into the main tag */}
        <main className="bg-[#F2F2F2] w-full xg:min-h-screen">
          {/* Bottom menu */}
          <section className="bg-white rounded-lg m-[19px] p-4 mb-[47px]">
            <div className="pb-4">
              <h3 className="font-medium text-[22px] leading-[30px] text-[#262626]">
                Driver KYC
              </h3>
              <p className="text-[#737373] font-medium leading-[30px] text-base">
                Complete this form to initiate background checks, ensuring we
                meet regulatory requirements and protect our business from
                potential risks.
              </p>
            </div>
            {/* Timeline */}
            <div className="flex py-10 px-[44px] border-y items-center">
              <div className="bg-[#0C9064] flex items-center justify-center  w-8 h-8 text-white rounded-[100%] ">
                <CheckOutlinedIcon fontSize="small" />
              </div>
              <p className="pl-[10px] font-bold text-sm leading-[30px] text-[#0C9064]">
                Personal Details
              </p>
              <Image
                src={greenline}
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

            <h3 className="font-medium leading-[30px] pt-4 text-lg text-[#262626] ">
              Residence Details (Driver)
            </h3>

            <div className="mb-12 border-0 mx-">
              <Formik<ResidentialProps>
                initialValues={{
                  streetName: "",
                  city: "",
                  houseAddress: "",
                  region: "",
                }}
                validationSchema={Yup.object({
                  houseAddress: Yup.string().required("Required field"),
                  streetName: Yup.string().required("Required field"),
                  city: Yup.string().required("Required field"),
                  region: Yup.string().required("Required field"),
                })}
                onSubmit={async (values) => {
                  alert(JSON.stringify(values, null, 2));
                }}
              >
                {({ handleSubmit, values, handleChange, setFieldValue }) => (
                  <Form
                    onSubmit={handleSubmit}
                    className="bg-white border-0 pb-10"
                  >
                    <section className="">
                      <div className="flex gap-x-4 mt-3 w-full">
                        {/* House Address */}
                        <div className="w-[50%]">
                          <label
                            htmlFor="houseAddress"
                            className="block mb-2 text-sm font-normal text-gray-900"
                          >
                            House Address{" "}
                          </label>
                          <Field
                            type="text"
                            id="houseAddress"
                            className="border placeholder-[#BFBFBF] border-gray-300 text-gray-900 text-sm rounded block w-full p-2"
                            placeholder="Address"
                            value={values.houseAddress}
                            onChange={handleChange}
                          />
                          <p className="font-medium text-xs text-red-700">
                            <ErrorMessage name="houseAddress" />
                          </p>
                          <p className="text-[#737373] text-sm font-normal leading-[30px] pt-1">
                            Enter house address
                          </p>
                        </div>

                        {/* Street Name */}
                        <div className="w-[50%]">
                          <label
                            htmlFor="streetName"
                            className="block mb-2 text-sm font-normal text-gray-900"
                          >
                            Street Name{" "}
                          </label>
                          <Field
                            type="text"
                            id="streetName"
                            className="border placeholder-[#BFBFBF] border-gray-300 text-gray-900 text-sm rounded block w-full p-2"
                            placeholder="Street Name"
                            value={values.streetName}
                            onChange={handleChange}
                          />
                          <p className="font-medium text-xs text-red-700">
                            <ErrorMessage name="streetName" />
                          </p>
                          <p className="text-[#737373] text-sm font-normal leading-[30px] pt-1">
                            Enter Street Name.
                          </p>
                        </div>
                      </div>

                      <div className="flex mt-4 gap-x-4">
                        {/* City */}
                        <div className="w-[50%]">
                          <label
                            htmlFor="city"
                            className="block mb-2 text-sm font-normal text-gray-900"
                          >
                            City{" "}
                          </label>
                          <Field
                            type="text"
                            id="city"
                            className="border placeholder-[#BFBFBF] border-gray-300 text-gray-900 text-sm rounded block w-full p-2"
                            placeholder="City"
                            value={values.city}
                            onChange={handleChange}
                          />
                          <p className="font-medium text-xs text-red-700">
                            <ErrorMessage name="city" />
                          </p>
                          <p className="text-[#737373] text-sm font-normal leading-[30px] pt-1">
                            Enter City
                          </p>
                        </div>

                        {/* Region */}
                        <div className="w-[50%]">
                          <label
                            htmlFor="region"
                            className="block mb-2 text-sm font-normal text-gray-900"
                          >
                            Region{" "}
                          </label>
                          <Field
                            type="text"
                            id="region"
                            className="border placeholder-[#BFBFBF] border-gray-300 text-gray-900 text-sm rounded block w-full p-2"
                            placeholder="Region"
                            value={values.region}
                            onChange={handleChange}
                          />
                          <p className="font-medium text-xs text-red-700">
                            <ErrorMessage name="region" />
                          </p>
                          <p className="text-[#737373] text-sm font-normal leading-[30px] pt-1">
                            Enter Region
                          </p>
                        </div>
                      </div>
                    </section>

                    {/* Utility Bills */}
                    <section className="mt-5 pb-4 ">
                      <h3 className="font-medium leading-[30px] text-[#262626] text-base">
                        Upload an Image of Utility Bills{" "}
                      </h3>

                      <div className="flex mt-5 gap-x-5">
                        <div>
                          <StyledDropzone />
                          <p className="pl-[0px] text-[#737373] text-xs font-normal leading-[30px] pt-3 ">
                            Upload an image of Water Bill
                          </p>
                        </div>
                        <div>
                          <StyledDropzone />
                          <p className=" text-[#737373] text-xs  font-normal leading-[30px] pt-3 ">
                            Upload an image of Electricity Bill
                          </p>
                        </div>
                      </div>
                    </section>

                    <section className="mt-8 pb-4">
                      <h4 className="mb-[]">
                        Drop the pin on the location of the Address on the map.
                      </h4>
                      <div className="mt-4 border">
                        <ResidentialDetailsMap />
                      </div>
                    </section>

                    <section className="mt-4 pb-3">
                      <p className="text-[#262626] pb-5 font-medium text-base leading-[30px]">
                        Upload Image of Residence
                      </p>
                      <StyledDropzone />
                      <div className="flex mt-3 items-center">
                        <Image src={info} alt="info icon" className="w-4 h-4" />
                        <p className="text-[#737373] pl-1 font-normal leading-[30px] text-sm ">
                          This image can only be uploaded by Fleet Manager/Fleet
                          Officer
                        </p>
                      </div>
                    </section>

                    <div className="flex mt-4 justify-between">
                      <Link
                        href={"/views/Drivers/AddNewDriver/PersonalDetails"}
                      >
                        <button
                          type="button"
                          className="text-[#FFFFFF] border bg-[#007AF5] ml-auto rounded-[4px] w-[85px] border-[#DADADA] focus:outline-none text-sm   py-1.5 text-center inline-flex justify-center font-normal items-center mr-5 mb-2 "
                        >
                          Previous
                        </button>
                      </Link>

                      <Link
                        href={"/views/Drivers/AddNewDriver/GuarantorDetails"}
                      >
                        <button
                          type="button"
                          className="text-[#FFFFFF] border bg-[#007AF5] ml-auto rounded-[4px] w-[63px] border-[#DADADA] focus:outline-none text-sm   py-1.5 text-center inline-flex justify-center font-normal items-center mr-5 mb-2 "
                        >
                          Next{" "}
                        </button>
                      </Link>
                    </div>
                  </Form>
                )}
              </Formik>
            </div>
          </section>
        </main>
      </Layout>
    </>
  );
}
