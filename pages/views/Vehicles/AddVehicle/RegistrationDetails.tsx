/* eslint-disable require-jsdoc */
/* eslint-disable react/no-unescaped-entities */
import React from "react";
import { useEffect, useState } from "react";
import KYC from "@/components/driverKYC";
import { Formik, Field, Form, ErrorMessage } from "formik";
import * as Yup from "yup";
import Head from "next/head";
import Layout from "../../../../components/layout";
import blueline from "../../../../assets/blueline.svg";
import greyline from "../../../../assets/greyline.svg";
import StyledDropzone from "../../../../components/dropzone";
import { Tab, Tabs, TabList, TabPanel } from "react-tabs";
import AllDriverTiles from "@/components/Drivers/DriverTiles";
import Link from "next/link";
import Image from "next/image";
import CheckOutlinedIcon from "@mui/icons-material/CheckOutlined";
import greenline from "../../../../assets/greenline.svg";
import info from "../../../../assets/info_icon.png";
import InfoOutlinedIcon from "@mui/icons-material/InfoOutlined";
import driver from "../../../assets/driver_icon.png";
import plus from "../../../../assets/plus.png";
import vehicle from "../../../assets/vehicle_icon.png";
import payment from "../../../assets/payments.png";
import { DashboardTiles1 } from "@/components/tiles";
import DashboardTiles from "@/components/tiles";
import AccountMenu from "@/components/headerDropdown";
import BasicStacking from "@/components/stackedChart";
import Header from "@/components/header";
import Picker from "@/components/dateRange";
import { yellow } from "@mui/material/colors";

type RegistrationProps = {
  expiryDate: string;
  insuranceType: string;
  roadWorthyExpiry: string;
  maintenanceDate: number | string;
};

type UploadModel = {
  documents?: any;
};

export default function VehicleImages(prop: RegistrationProps) {
  const phoneRegExp =
    /^((\+[1-9]{1,4}[ -]?)|(\([0-9]{2,3}\)[ -]?)|([0-9]{2,4})[ -]?)*?[0-9]{3,4}[ -]?[0-9]{3,4}$/;

  return (
    <>
      <div className="flex w-full">
        <div className="w-full z-10">
          <Header name="Add Vehicle" />
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
          <section className="bg-[#FFFFFF] rounded-lg m-[19px] p-4">
            <div className="pb-4">
              <h3 className="font-medium text-[22px] leading-[30px] text-[#262626]">
                Add Vehicle
              </h3>
              <p className="text-[#737373] font-medium leading-[30px] text-base ">
                Complete this form to add a vehicle.
              </p>
            </div>
            {/* Timeline */}
            <div className="flex py-6 px-[44px] border-y items-center">
              <div className="bg-[#0C9064] flex items-center justify-center  w-8 h-8 text-white rounded-[100%] ">
                <CheckOutlinedIcon fontSize="small" />
              </div>
              <p className="pl-[10px] font-bold text-sm leading-[30px] text-[#0C9064]">
                Vehicle Details
              </p>
              <Image
                src={greenline}
                alt="blue timeline"
                className="w-[23%] grow ml-1"
              />
              <div className="bg-[#0C9064] flex items-center justify-center ml-1 w-8 h-8 text-white rounded-[100%] ">
                <CheckOutlinedIcon fontSize="small" />
              </div>
              <p className="pl-[10px] font-bold text-sm leading-[30px] text-[#0C9064]">
                Vehicle Images
              </p>
              <Image
                src={greenline}
                alt="blue timeline"
                className="w-[23%] grow ml-1"
              />
              <div className="bg-[#007AF5] flex items-center justify-center ml-1 w-8 h-8 text-white rounded-[100%] ">
                3
              </div>
              <p className="pl-[10px] font-bold text-sm leading-[30px] text-[#007AF5]">
                Registration Details
              </p>
            </div>

            <div className="mt-2 mb- border-0">
              <Formik<RegistrationProps>
                initialValues={{
                  expiryDate: "",
                  roadWorthyExpiry: "",
                  insuranceType: "",
                  maintenanceDate: "",
                }}
                validationSchema={Yup.object({
                  expiryDate: Yup.string().required("Required"),
                  roadWorthyExpiry: Yup.string().required("Required"),
                  insuranceType: Yup.string().required("Required"),
                  maintenanceDate: Yup.string().required("Required"),
                })}
                onSubmit={async (values) => {
                  alert(JSON.stringify(values, null, 2));
                }}
              >
                {({ handleSubmit, values, handleChange, setFieldValue }) => (
                  <Form onSubmit={handleSubmit} className="bg-white border-0">
                    <div className="mt-4">
                      <h5 className="text-[#262626] leading-[30px] font-medium text-base ">
                        Vehicle Registration Details
                      </h5>
                      <p className="text-[#737373] text-sm font-medium leading-[30px] pt-1">
                        Upload images of the Vehicle Documents. These documents
                        are required as part of regulations.
                      </p>
                    </div>
                    {/* dropzones */}
                    <div className="flex gap-x-10 mt-3 pb-3 flex-wrap">
                      <div className="flex flex-col items-center">
                        <StyledDropzone />
                        <p className="text-[#737373]  font-medium text-xs leading-[30px] pt-2 ">
                          Road Worthy Certificate
                        </p>
                      </div>
                      <div className="flex flex-col  items-center">
                        <StyledDropzone />
                        <p className="text-[#737373]  font-medium text-xs leading-[30px] pt-2 ">
                          Insurance Certificate
                        </p>
                      </div>
                    </div>

                    <section>
                      <div className="flex gap-x-4">
                        <div className="w-[50%]">
                          <label
                            htmlFor="insuranceExpiryDate"
                            className="block mb-2 text-sm font-medium leading-[30px] text-[#262626]"
                          >
                            Insurance Expiry Date
                          </label>
                          <div className="shadow-[0px_1px_2px_0px_#1B283614]">
                            <Picker />
                          </div>
                        </div>
                        <div className="w-[50%]">
                          <label
                            htmlFor="roadWorthyExpiry"
                            className="block mb-2 text-sm font-medium leading-[30px] text-[#262626]"
                          >
                            Road Worthy Certificate Expiry Date
                          </label>
                          <div className="shadow-[0px_1px_2px_0px_#1B283614]">
                            <Picker />
                          </div>
                        </div>
                      </div>

                      <div className="flex mt-3 gap-x-4">
                        <div className="w-[50%]">
                          <label
                            htmlFor="insuranceType"
                            className="block mb-2 text-sm font-medium leading-[30px] text-[#262626]"
                          >
                            Insurance Type
                          </label>
                          <Field
                            id="insuranceType"
                            as="select"
                            className="bg-[#FFFFFF] border shadow-[0px_1px_2px_0px_#1B283614] border-[#D9D9D9] placeholder-[#BFBFBF] text-gray-900 text-sm rounded-[4px] block w-full py-1.5 "
                            value={values.insuranceType}
                            onChange={handleChange}
                          >
                            <option
                              className="py-3"
                              disabled={true}
                              selected={true}
                              value={""}
                            >
                              e.g Third-party
                            </option>
                            <option className="py-3">Manual</option>
                            <option className="py-3">Automatic</option>
                          </Field>
                          <p className="font-medium text-xs text-red-700">
                            <ErrorMessage name="insuranceType" />
                          </p>
                        </div>

                        <div className="w-[50%]">
                          <label
                            htmlFor="maintenanceDate"
                            className="block mb-2 text-sm font-medium leading-[30px] text-[#262626]"
                          >
                            Maintenance Date
                          </label>
                          <div className="shadow-[0px_1px_2px_0px_#1B283614]">
                            <Picker />
                          </div>

                          <p className="text-[#8C8C8C] text-sm font-normal leading-4 pt-1">
                            Enter most recent maintenance date.
                          </p>
                        </div>
                      </div>
                    </section>

                    <div className="flex justify-between mt-7">
                      <Link href={"/views/Vehicles/AddVehicle/VehicleImages"}>
                        <button
                          type="button"
                          className="text-[#FFFFFF] border bg-[#8C8C8C] rounded-[4px] px-4 border-[#DADADA] focus:outline-none text-sm py-1.5 text-center inline-flex justify-center font-normal items-center"
                        >
                          Back
                        </button>
                      </Link>
                      <Link href={""}>
                        <button
                          type="button"
                          className="text-[#FFFFFF] border bg-[#007AF5] rounded-[4px] px-4 border-[#DADADA] focus:outline-none text-sm py-1.5 text-center inline-flex justify-center font-normal items-center"
                        >
                          Submit
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
