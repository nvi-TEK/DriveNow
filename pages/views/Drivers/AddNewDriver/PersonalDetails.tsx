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
import { Tab, Tabs, TabList, TabPanel } from "react-tabs";
import AllDriverTiles from "@/components/Drivers/DriverTiles";
import Link from "next/link";
import Image from "next/image";
import info from "../../../../assets/info_icon.png";
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

type PersonalProps = {
  firstName: string;
  lastName: string;
  phone: string;
  email: number | string;
  ghanaCardName: string;
  ghanaCardNumber: string;
  checked: any;
};

type UploadModel = {
  documents?: any;
};

export default function PersonalDetails(prop: PersonalProps) {
  const phoneRegExp =
    /^((\+[1-9]{1,4}[ -]?)|(\([0-9]{2,3}\)[ -]?)|([0-9]{2,4})[ -]?)*?[0-9]{3,4}[ -]?[0-9]{3,4}$/;

  return (
    <>
<div className="flex w-full">
        <div className="w-full z-10">
          <Header name="All Drivers" secondName="Drivers KYC" />
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
          <section className="bg-[#FFFFFF] rounded-lg m-[19px] p-4  ">
            <div className="pb-4">
              <h3 className="font-medium text-[22px] leading-[30px] text-[#262626]">
                Driver KYC
              </h3>
              <p className="text-[#737373] font-medium leading-[30px] text-base ">
                Complete this form to initiate background checks, ensuring we
                meet regulatory requirements and protect our business from
                potential risks.
              </p>
            </div>
            {/* Timeline */}
            <div className="flex py-10 px-[44px] border-y items-center">
              <div className="bg-[#007AF5] flex items-center justify-center  w-8 h-8 text-white rounded-[100%] ">
                1
              </div>
              <p className="pl-[10px] font-bold text-sm leading-[30px] text-[#007AF5]">
                Personal Details
              </p>
              <Image
                src={blueline}
                alt="blue timeline"
                className="w-[100px] ml-1"
              />
              <div className="bg-[#8C8C8C] flex items-center justify-center ml-1 w-8 h-8 text-white rounded-[100%] ">
                2
              </div>
              <p className="pl-[10px] font-bold text-sm leading-[30px] text-[#8C8C8C]">
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
              Personal Details (Driver)
            </h3>

            <div className="mt-2 mb-12 border-0  mx-">
              <Formik<PersonalProps>
                initialValues={{
                  firstName: "",
                  lastName: "",
                  phone: "",
                  email: "",
                  ghanaCardName: "",
                  ghanaCardNumber: "",
                  checked: [],
                }}
                validationSchema={Yup.object({
                  firstName: Yup.string()
                    .max(20, "Must be 40 characters or less")
                    .required("Required field"),
                  lastName: Yup.string().required("Required field"),
                  phone: Yup.string()
                    .matches(phoneRegExp, "Invalid Phone number")
                    .required("Required Field"),
                  email: Yup.string()
                    .email("Invalid email")
                    .required("Required field"),
                  ghanaCardName: Yup.string().required("Required field"),
                  ghanaCardNumber: Yup.string().required("Required field"),
                })}
                onSubmit={async (values) => {
                  alert(JSON.stringify(values, null, 2));
                }}
              >
                {({ handleSubmit, values, handleChange, setFieldValue }) => (
                  <Form
                    onSubmit={handleSubmit}
                    className="bg-white border-0   pb-10"
                  >
                    <section className="border-b pb-4">
                      <div className="flex w-full gap-x-4 mt-[1.875rem]">
                        {/* First Name */}
                        <div className="w-[50%]">
                          <label
                            htmlFor="firstName"
                            className="block mb-2 text-base font-medium leading-[30px] text-[#262626]"
                          >
                            First Name
                          </label>
                          <Field
                            type="text"
                            id="firstName"
                            className="border placeholder-[#BFBFBF]  border-gray-300 text-gray-900 text-sm rounded block w-full p-2"
                            placeholder="First Name"
                            value={values.firstName}
                            onChange={handleChange}
                          />
                          <p className="font-medium text-xs text-red-700">
                            <ErrorMessage name="firstName" />
                          </p>

                          <p className="text-[#737373] text-sm font-normal leading-[30px] pt-1">
                            Enter First Name.
                          </p>
                        </div>

                        {/* Last Name */}
                        <div className="w-[50%] ">
                          <label
                            htmlFor="lastName"
                            className="block mb-2 text-base font-medium leading-[30px] text-[#262626]"
                          >
                            Last Name
                          </label>
                          <Field
                            type="text"
                            id="lastName"
                            className="border border-gray-300 placeholder-[#BFBFBF] text-gray-900 text-sm rounded block w-full p-2"
                            placeholder="Last Name"
                            value={values.lastName}
                            onChange={handleChange}
                          />
                          <p className="font-medium text-xs text-red-700">
                            <ErrorMessage name="lastName" />
                          </p>

                          <p className="text-[#737373] text-sm font-normal leading-[30px] pt-1">
                            Enter Last Name.
                          </p>
                        </div>
                      </div>

                      <div className="flex gap-x-4 mt-[1.25rem]">
                        {/* phone number */}
                        <div className="w-[50%]">
                          <label
                            htmlFor="phone"
                            className="block mb-2 text-base font-medium leading-[30px] text-[#262626]"
                          >
                            Phone
                          </label>
                          <Field
                            type="text"
                            id="phone"
                            className="border border-gray-300 placeholder-[#BFBFBF] text-gray-900 text-sm rounded block w-full p-2.5"
                            placeholder="+233 123 456 789"
                            value={values.phone}
                            onChange={handleChange}
                          />
                          <p className="font-medium text-red-700">
                            <ErrorMessage name="phone" />
                          </p>
                          <p className="text-[#737373] text-sm font-normal leading-[30px] pt-1">
                            Enter phone number
                          </p>
                        </div>

                        <div className="w-[50%]">
                          <label
                            htmlFor="email"
                            className="block mb-2 text-base font-medium leading-[30px] text-[#262626]"
                          >
                            Email address
                          </label>
                          <Field
                            type="text"
                            id="email"
                            className="border border-gray-300 placeholder-[#BFBFBF] text-gray-900 text-sm rounded block w-full p-2.5"
                            placeholder="example@email.com"
                            value={values.email}
                            onChange={handleChange}
                          />
                          <p className="font-medium text-red-700">
                            <ErrorMessage name="email" />
                          </p>
                          <p className="text-[#737373] text-sm font-normal leading-[30px] pt-1">
                            Enter email address
                          </p>
                        </div>
                      </div>
                    </section>

                    <section className="mt-4">
                      <h3 className="font-medium text-[#262626] text-lg ">
                        Identification Details
                      </h3>

                      {/* Ghana Card Name */}
                      <div className="flex gap-x-4 mt-2">
                        <div className="w-[50%]">
                          <label
                            htmlFor="ghanaCardName"
                            className="block mb-2 text-base font-medium leading-[30px] text-[#262626]"
                          >
                            Ghana Card Name
                          </label>
                          <Field
                            type="text"
                            id="ghanaCardName"
                            className="border border-gray-300 placeholder-[#BFBFBF] text-gray-900 text-sm rounded block w-full p-2"
                            placeholder="Full name"
                            value={values.ghanaCardName}
                            onChange={handleChange}
                          />
                          <p className="font-medium text-xs text-red-700">
                            <ErrorMessage name="ghanaCardName" />
                          </p>
                          <p className="text-[#737373] text-sm font-normal leading-[30px] pt-1">
                            Enter name as seen on the Ghana Card.
                          </p>
                        </div>

                        <div className="w-[50%]">
                          <label
                            htmlFor="ghanaCardNumber"
                            className="block mb-2 text-base font-medium leading-[30px] text-[#262626]"
                          >
                            Ghana Card Number
                          </label>
                          <Field
                            type="text"
                            id="ghanaCardNumber"
                            className="border border-gray-300 placeholder-[#BFBFBF] text-gray-900 text-sm rounded block w-full p-2"
                            placeholder="GHA-12345678-42"
                            value={values.ghanaCardNumber}
                            onChange={handleChange}
                          />
                          <p className="font-medium text-xs text-red-700">
                            <ErrorMessage name="ghanaCardNumber" />
                          </p>
                          <p className="text-[#737373] text-sm font-normal leading-[30px] pt-1">
                            Enter the Ghana Card number beginning with GHA.
                          </p>
                        </div>
                      </div>
                    </section>

                    <div className="mt-5">
                      <h4 className="text-sm pb-5 font-medium leading-[30px] text-[#262626]">
                        Upload Selfie image
                      </h4>
                      <StyledDropzone />
                      <p className="pl-[30px] text-[#262626] text-base font-medium leading-[30px] pt-3 ">
                        Profile Picture
                      </p>
                      <div className="flex pt-5 items-center">
                        <Image src={info} alt="info icon" className="w-4 h-4" />
                        <p className="text-[#737373] pl-1 font-normal leading-[30px] text-sm ">
                          Ensure that images uploaded are clear and details are
                          clearly captured.
                        </p>
                      </div>
                    </div>

                    <div className="mt-5">
                      <h4 className="font-medium text-base leading-[30px] text-[#262626] ">
                        Upload Ghana Card
                      </h4>
                      <div className="flex mt-5 gap-x-5">
                        <div>
                          <StyledDropzone />
                          <p className="pl-[30px] text-[#262626] text-base font-medium leading-[30px] pt-3 ">
                            Front
                          </p>
                        </div>
                        <div>
                          <StyledDropzone />
                          <p className="pl-[30px] text-[#262626] text-base font-medium leading-[30px] pt-3 ">
                            Back
                          </p>
                        </div>
                      </div>
                      <div className="flex mt-5 items-center">
                        <Image src={info} alt="info icon" className="w-4 h-4" />
                        <p className="text-[#737373] pl-1 font-normal leading-[30px] text-sm ">
                          Ensure that images uploaded are clear and details are
                          clearly captured.
                        </p>
                      </div>
                    </div>

                    <div className="flex justify-between mt-5">
                      <button
                        type="button"
                        className="text-[#FFFFFF] border bg-[#A6A6A6] rounded-[4px] w-[85px] border-[#DADADA] focus:outline-none text-sm py-1.5 text-center inline-flex justify-center font-normal items-center"
                        disabled
                      >
                        Previous
                      </button>

                      <Link
                        href={"/views/Drivers/AddNewDriver/ResidentialDetails"}
                      >
                        <button
                          type="button"
                          className="text-[#FFFFFF] border bg-[#007AF5] rounded-[4px] w-[63px] border-[#DADADA] focus:outline-none text-sm py-1.5 text-center inline-flex justify-center font-normal items-center mr-5"
                        >
                          Next
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
