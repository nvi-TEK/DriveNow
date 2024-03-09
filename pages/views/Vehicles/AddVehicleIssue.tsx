/* eslint-disable react/no-unescaped-entities */
/* eslint-disable require-jsdoc */
import React from "react";
import { useEffect, useState } from "react";
import KYC from "@/components/driverKYC";
import Head from "next/head";
import Layout from "../../../components/layout";
import Link from "next/link";
import info from "../../../assets/info_icon.png";
import yellowinfo from "../../../assets/yellowinfo.png";
import Image from "next/image";
import { Formik, Form, Field, ErrorMessage } from "formik";
import * as Yup from "yup";
import StyledDropzone from "@/components/dropzone";
import Header from "@/components/header";
import Picker from "@/components/dateRange";

type PushProp = {
  vehicleRegistration: string;
  issueCategory: string;
  amount: string;
  date: string;
  description: string;
};

export default function AddVehicleIssue() {
  return (
    <>
      <div className="flex w-full">
        <div className="w-full z-10">
          <Header name="Vehicle Issue log" />
        </div>
      </div>
      <Layout>
        <Head>
          <title>Add Vehicle Issue</title>
          <meta name="description" content="Generated by create next app" />
          <meta name="viewport" content="width=device-width, initial-scale=1" />
        </Head>

        {/* Code goes into the main tag */}
        <main className="bg-[#F2F2F2] w-full xg:min-h-screen">
          {/* Bottom menu */}
          <section className="w- ">
            <div className="mt-5 rounded-lg mb-12 shadow mx-6">
              <Formik<PushProp>
                initialValues={{
                  vehicleRegistration: "",
                  issueCategory: "",
                  amount: "",
                  date: "",
                  description: "",
                }}
                validationSchema={Yup.object({
                  vehicleRegistration: Yup.string().required(
                    "Vehicle Registration required"
                  ),

                  issueCategory: Yup.string().required(
                    "Issue Category required"
                  ),
                  amount: Yup.number().required("Amount rquired"),
                  date: Yup.string().required("Required field"),
                  description: Yup.string().required("Required"),
                })}
                onSubmit={async (values) => {
                  alert(JSON.stringify(values, null, 2));
                }}
              >
                {({ handleSubmit, values, handleChange, setFieldValue }) => (
                  <Form
                    onSubmit={handleSubmit}
                    className="bg-white shadow-lg rounded-lg px-4 pb-4"
                  >
                    <h3 className="font-medium pt-4 text-[22px] leading-[30px] text-[#262626]">
                      Add Vehicle Issues
                    </h3>
                    <p className="text-base font-medium pt-2 leading-[30px] text-[#737373]  ">
                      Complete this form to add a vehicle issue, ensuring
                      vehicle issue logs are recorded effectively.
                    </p>

                    <section className="border-t gap-x-4 pt-4 mt-4 flex">
                      <div className="w-[50%]">
                        <label
                          htmlFor="vehicleRegistration"
                          className="block mb-2 text-sm pt-[10px] font-medium text-[#262626]"
                        >
                          Vehicle Registration
                        </label>
                        <Field
                          id="vehicleRegistration"
                          as="select"
                          className="bg-[#FFFFFF] border shadow-[0px_1px_2px_0px_#1B283614] border-[#D9D9D9] text-gray-900 text-sm rounded-[4px] block w-full p-1.5 "
                          value={values.vehicleRegistration}
                          onChange={handleChange}
                        >
                          <option disabled={true} value={""}>
                            Select Vehicle
                          </option>
                          <option>GT 9202-22</option>
                          <option>GS 4504-23</option>
                          <option>GS 4204-23</option>
                          <option>GS 4004-23</option>
                          <option>GS 4504-23</option>
                        </Field>
                        <p className="font-medium text-xs text-red-700">
                          <ErrorMessage name="vehicleRegistration" />
                        </p>
                      </div>

                      <div className="w-[50%]">
                        <label
                          htmlFor="issueCategory"
                          className="block mb-2 text-sm pt-[10px] font-medium text-gray-900"
                        >
                          Issue Category
                        </label>
                        <Field
                          id="issueCategory"
                          as="select"
                          className="bg-[#FFFFFF] border shadow-[0px_1px_2px_0px_#1B283614] border-[#D9D9D9] text-gray-900 text-sm rounded-[4px] block w-full p-1.5 "
                          value={values.issueCategory}
                          onChange={handleChange}
                        >
                          <option disabled={true} value={""}>
                            Select Category
                          </option>
                          <option>Car Insurance</option>
                          <option>Vehicle Maintenance</option>
                          <option>Driver Support</option>
                          <option>Vehicle Registration</option>
                          <option>Vehicle Recovery</option>
                          <option>Vehicle Administration</option>
                          <option>Accident Repair</option>
                          <option>Fleet Expense</option>
                          <option>Part Replacement</option>
                          <option>Other</option>
                        </Field>
                        <p className="font-medium text-xs text-red-700">
                          <ErrorMessage name="issueCategory" />
                        </p>
                      </div>
                    </section>

                    <section className="flex border-b pb-4 mt-[24px] gap-x-4">
                      <div className="w-[50%]">
                        <label
                          htmlFor="amount"
                          className="block mb-2 text-sm font-medium text-[#262626]"
                        >
                          Amount (GH₵)
                        </label>
                        <Field
                          type="text"
                          id="amount"
                          className="border border-gray-300 shadow-[0px_1px_2px_0px_#1B283614] text-gray-900 text-sm rounded block w-full p-1.5"
                          placeholder="Amount"
                          value={values.amount}
                          onChange={handleChange}
                        />
                        <p className="font-medium text-xs text-red-700">
                          <ErrorMessage name="amount" />
                        </p>
                      </div>
                      <div className="w-[50%]">
                        <label
                          htmlFor="date"
                          className="block mb-2 text-sm font-medium text-[#262626]"
                        >
                          Date
                        </label>
                        <Picker />
                      </div>
                    </section>

                    <section className="mt-4 ">
                      <h4 className="text-base font-medium leading-[30px] text-[#262626]">
                        Upload Images of Damage
                      </h4>
                      <div className="flex mt-4 justify-between">
                        <div>
                          <StyledDropzone />
                          <p className="text-base font-medium leading-[30px] text-[#262626]   ">
                            Upload Repair Image 1
                          </p>
                        </div>
                        <div>
                          <StyledDropzone />
                          <p className="text-base font-medium leading-[30px] text-[#262626]  ">
                            Upload Repair Image 2
                          </p>
                        </div>
                        <div>
                          <StyledDropzone />
                          <p className="text-base font-medium leading-[30px] text-[#262626]  ">
                            Upload Repair Image 3
                          </p>
                        </div>
                      </div>
                      <div className="flex mt-4 items-center">
                        <Image
                          className="h-4 w-4 "
                          src={yellowinfo}
                          alt="info icon"
                        />
                        <p className="text-[#FFBC0D] pl-1 font-normal text-sm leading-[30px] ">
                          Ensure that images uploaded are clear and details are
                          clearly captured.
                        </p>
                      </div>

                      <div className="mt-2">
                        <label
                          htmlFor="description"
                          className="block mb-2 mt-3 text-base font-medium leading-[30px] text-[#262626] "
                        >
                          Description
                        </label>
                        <Field
                          id="description"
                          as="textarea"
                          rows={10}
                          className="block p-2.5 shadow-[0px_1px_2px_0px_#1B283614] mb-auto w-[100%] h-[100px] text-sm text-gray-900 bg-[#FFFFFF] rounded-[4px] border border-gray-300"
                          placeholder="Enter Description"
                          value={values.description}
                          onChange={handleChange}
                        />
                        <p className="font-medium text-xs text-red-700">
                          <ErrorMessage name="description" />
                        </p>
                      </div>
                    </section>

                    <Link href={""}>
                      <button
                        type="button"
                        className="text-[#FFFFFF] border bg-[#007AF5] mt-4 rounded-[4px] w-[84px] border-[#DADADA] focus:outline-none text-xs py-1.5 text-center inline-flex justify-center font-normal items-center ml-[92%] mr-2 "
                      >
                        Submit
                      </button>
                    </Link>
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
