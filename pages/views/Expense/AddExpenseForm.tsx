/* eslint-disable react/no-unescaped-entities */
/* eslint-disable require-jsdoc */
import React from "react";
import { useEffect, useState } from "react";
import KYC from "@/components/driverKYC";
import Head from "next/head";
import Datatable from "../../../components/customPush/table";
import Layout from "../../../components/layout";
import Link from "next/link";
import info from "../../../assets/info_icon.png";
import yellowinfo from "../../../assets/yellowinfo.png";
import DatePicker from "../../../components/datepicker1";
import Image from "next/image";
import { Formik, Form, Field, ErrorMessage } from "formik";
import * as Yup from "yup";
import BasicDatePicker from "../../../components/datepicker";
import StyledDropzone from "@/components/dropzone";

type AddExpenseProp = {
  expenseCategory: string;
  expenseLine: string;
  amount: string;
  payTo: string;
  description: string;
  bank: string;
  accountNumber: number | string;
};

export default function AddExpense() {
  return (
    <>
      <Layout>
        <Head>
          <title>Add Expense</title>
          <meta name="description" content="Generated by create next app" />
          <meta name="viewport" content="width=device-width, initial-scale=1" />
        </Head>

        {/* Code goes into the main tag */}
        <main className="bg-[#F2F2F2] w-full xg:min-h-screen">
          {/* Bottom menu */}
          <section className="w-full ">
            <div className="mt-[18px] rounded-lg mb-12 shadow mx-6">
              <Formik<AddExpenseProp>
                initialValues={{
                  expenseCategory: "",
                  expenseLine: "",
                  amount: "",
                  payTo: "",
                  description: "",
                  bank: "",
                  accountNumber: "",
                }}
                validationSchema={Yup.object({
                  vehicleRegistration: Yup.string().required(
                    "Vehicle Registration required"
                  ),

                  issueCategory: Yup.string().required(
                    "Issue Category required"
                  ),
                  amount: Yup.number().required("Amount rquired"),
                  payTo: Yup.string().required("Required field"),
                  accountNumber: Yup.number().required("Amount rquired"),
                })}
                onSubmit={async (values) => {
                  alert(JSON.stringify(values, null, 2));
                }}
              >
                {({ handleSubmit, values, handleChange, setFieldValue }) => (
                  <Form
                    onSubmit={handleSubmit}
                    className="bg-white shadow-lg rounded-lg px-[1rem] pb-4"
                  >
                    <h3 className="font-medium pt-4 text-[22px] leading-[30px] text-[#262626]">
                      Add Expense
                    </h3>
                    <p className="text-base font-medium pt-2 leading-[30px] text-[#737373]  ">
                      Complete this form to create an expense.
                    </p>

                    <div className="flex gap-x-5">
                      <div className="">
                        <label
                          htmlFor="costCenter"
                          className="block mb-2 text-sm font-normal  text-[#000000]"
                        >
                          Cost Center
                        </label>
                        <Field
                          type="text"
                          id="costCenter"
                          className="border border-gray-300 text-gray-900 text-sm rounded  w-[500px] p-1.5"
                          placeholder=""
                          value={values.amount}
                          onChange={handleChange}
                          disabled
                        />
                      </div>

                      <DatePicker />
                    </div>

                    <div className="flex mt-5 items-center">
                      <div className=" grow">
                        <label
                          htmlFor="expenseCategory"
                          className="block mb-2 text-sm pt-[10px] font-normal text-[#404040]"
                        >
                          Expense Category
                        </label>
                        <Field
                          id="expenseCategory"
                          as="select"
                          className="bg-[#FFFFFF] border border-[#D9D9D9] text-gray-900 text-sm rounded-[4px] block w-[500px] p-1.5 "
                          value={values.expenseCategory}
                          onChange={handleChange}
                        >
                          <option disabled={true} value={""}>
                            Choose an expense category
                          </option>
                          <option>Business Expense</option>
                          <option>General & Administration</option>
                          <option>Employee Cost</option>
                          <option>Current Provision for Tax</option>
                        </Field>
                        <p className="font-medium text-xs text-red-700">
                          <ErrorMessage name="expenseCategory" />
                        </p>
                      </div>

                      <div className="">
                        <label
                          htmlFor="createdBy"
                          className="block mb-2 text-sm font-normal  text-[#000000]"
                        >
                          Created By
                        </label>
                        <Field
                          type="text"
                          id="createdBy"
                          className="border border-gray-300 text-gray-900 text-sm rounded  w-[500px] p-1.5"
                          placeholder=""
                          value={values.amount}
                          onChange={handleChange}
                          disabled
                        />
                      </div>
                    </div>

                    <section className="mt-5">
                      <h3 className="font-medium text-lg text-[#404040] leading-[18px] ">
                        Expense Line
                      </h3>
                      <p className="pt-1 text-[#6F6F6F] text-sm leading-[18px] font-normal ">
                        Add Expense information to the expense line.
                      </p>

                      <div className="mt-5 grow">
                        <label
                          htmlFor="expenseLine"
                          className="block mb-2 text-sm font-medium text-gray-900"
                        >
                          Expense Line
                        </label>
                        <Field
                          id="expenseLine"
                          as="select"
                          className="bg-[#FFFFFF] border border-[#D9D9D9] text-gray-900 text-sm rounded-[4px] block w-[500px] p-1.5 "
                          value={values.expenseLine}
                          onChange={handleChange}
                        >
                          <option disabled={true} value={""}>
                            Choose an expense line
                          </option>
                          <option>Business Expense</option>
                          <option>General & Administration</option>
                          <option>Employee Cost</option>
                          <option>Current Provision for Tax</option>
                        </Field>
                        <p className="font-medium text-xs text-red-700">
                          <ErrorMessage name="expenseLine" />
                        </p>
                      </div>

                      <div className="flex mt-5 gap-x-5 border-b pb-7 ">
                        <div>
                          <label
                            htmlFor="description"
                            className="block mb-2 text-sm font-normal leading-[18px] text-[#404040] "
                          >
                            Description
                          </label>
                          <Field
                            id="description"
                            as="textarea"
                            rows={10}
                            className="block p-2.5 w-[500px] grow h-[100px] text-sm text-gray-900 bg-[#FFFFFF] rounded-[4px] border border-gray-300"
                            placeholder="Enter Description"
                            value={values.description}
                            onChange={handleChange}
                          />
                          <p className="font-medium text-xs  text-red-700">
                            <ErrorMessage name="description" />
                          </p>
                        </div>

                        <div className="">
                          <label
                            htmlFor="amount"
                            className="block mb-2 text-sm font-normal  text-[#000000]"
                          >
                            Amount (GH₵)
                          </label>
                          <Field
                            type="text"
                            id="amount"
                            className="border border-gray-300 text-gray-900 text-sm rounded block w-[420px] p-1.5"
                            placeholder="Amount"
                            value={values.amount}
                            onChange={handleChange}
                          />
                          <p className="font-medium text-red-700">
                            <ErrorMessage name="amount" />
                          </p>
                        </div>
                      </div>
                    </section>

                    <section className="mt-4">
                      <h3 className="font-medium text-lg text-[#404040] leading-[18px] ">
                        Payment Details
                      </h3>
                      <p className="pt-1 text-[#6F6F6F] text-sm leading-[18px] font-normal ">
                        Add payment information to the expense request.
                      </p>

                      <div className=" grow">
                        <label
                          htmlFor="payTo"
                          className="block mb-2 text-sm pt-[10px] font-medium text-gray-900"
                        >
                          Pay to
                        </label>
                        <Field
                          id="payTo"
                          as="select"
                          className="bg-[#FFFFFF] border border-[#D9D9D9] text-gray-900 text-sm rounded-[4px] block w-[100%] p-1.5 "
                          value={values.payTo}
                          onChange={handleChange}
                        >
                          <option disabled={true} value={""}>
                            To be paid to
                          </option>
                          <option>Kwamena@teksol.com</option>
                          <option>Edmond@teksol.com</option>
                          <option>Geoffrey@teksol.com</option>
                          <option>Kweku@teksol.com</option>
                        </Field>
                        <p className="text-[#A6A6A6] pt-[10px] font-normal leading-[18px] text-sm">
                          Select a name from the list
                        </p>
                        <p className="font-medium text-xs text-red-700">
                          <ErrorMessage name="payTo" />
                        </p>
                      </div>

                      <div className="flex mt-5">
                        <div className=" grow">
                          <label
                            htmlFor="bank"
                            className="block mb-2 text-sm pt-[10px] font-medium text-gray-900"
                          >
                            Bank Name
                          </label>
                          <Field
                            id="bank"
                            as="select"
                            className="bg-[#FFFFFF] border border-[#D9D9D9] text-gray-900 text-sm rounded-[4px] block w-[500px] p-1.5 "
                            value={values.bank}
                            onChange={handleChange}
                          >
                            <option disabled={true} value={""}>
                              Select Bank
                            </option>
                            <option>GT Bank</option>
                            <option>Fidelity Bank</option>
                            <option>Ecobank</option>
                            <option>Standard Chartered</option>
                          </Field>
                          <p className="text-[#A6A6A6] pt-[10px] font-normal leading-[18px] text-sm">
                            Select a name from the list
                          </p>
                          <p className="font-medium text-xs text-red-700">
                            <ErrorMessage name="bank" />
                          </p>
                        </div>

                        <div className="mt-2">
                          <label
                            htmlFor="accountNumber"
                            className="block mb-2 text-sm font-normal  text-[#000000]"
                          >
                            Account Number
                          </label>
                          <Field
                            type="text"
                            id="accountNumber"
                            className="border border-gray-300 text-gray-900 text-sm rounded block w-[500px] p-1.5"
                            placeholder="Enter account number"
                            value={values.accountNumber}
                            onChange={handleChange}
                          />
                          <div className="h-[35px] w-[500px] gap-x-2 flex items-center bg-[#FFFAE7] rounded-b-xl ">
                            <Image
                              src={yellowinfo}
                              className="ml-3 "
                              alt="info icon"
                            />
                            <p className="text-[#D6AA00] text-sm font-normal leading-[18px] ">
                              NB: Enter mobile number if bank name is Mobile
                              Money
                            </p>
                          </div>
                          <p className="font-medium text-red-700">
                            <ErrorMessage name="accountNumber" />
                          </p>
                        </div>
                      </div>
                    </section>

                    <section className="flex mt-4 justify-between">
                      <Link href={"/views/expense"}>
                        <button
                          type="button"
                          className="text-[#404040] border bg-[#E1E1E1] mt-4 rounded-[4px] w-[84px] border-[#DADADA] focus:outline-none text-sm py-1.5 text-center inline-flex justify-center font-normal items-center ml- mr-2 "
                        >
                          Cancel
                        </button>
                      </Link>
                      <Link href={""}>
                        <button
                          type="button"
                          className="text-[#FFFFFF] border bg-[#007AF5] mt-4 rounded-[4px] w-[84px] border-[#DADADA] focus:outline-none text-sm py-1.5 text-center inline-flex justify-center font-normal items-center ml- mr "
                        >
                          Submit
                        </button>
                      </Link>
                    </section>
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