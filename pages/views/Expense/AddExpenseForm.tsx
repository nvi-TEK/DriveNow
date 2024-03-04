/* eslint-disable react/no-unescaped-entities */
/* eslint-disable require-jsdoc */
import React from "react";
import { useEffect, useState } from "react";
import KYC from "@/components/driverKYC";
import Head from "next/head";
import Layout from "../../../components/layout";
import info from "../../../assets/info_icon.png";
import yellowinfo from "../../../assets/yellowinfo.png";
import Image from "next/image";
import Link from "next/link";
import { Formik, Form, Field, ErrorMessage } from "formik";
import * as Yup from "yup";
import Modal from "react-modal";
import closebutton from "../../../assets/darkclose.png";
import StyledDropzone from "@/components/dropzones/AddExpenseDropzone";
import attach from "../../../assets/attachment_icon.png";
import Header from "@/components/header";

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
      width: "645px",
      height: "347px",
      borderRadius: "8px",
      backgroundColor: "white",
    },
    overlay: {
      backgroundColor: "#0000008C",
    },
  };
  return (
    <>
      <Header name="Add Expense" />

      <Layout>
        <Head>
          <title>Add Expense</title>
          <meta name="description" content="Generated by create next app" />
          <meta name="viewport" content="width=device-width, initial-scale=1" />
        </Head>

        {/* Code goes into the main tag */}
        <main className="bg-[#F2F2F2] w-full xg:min-h-screen">
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
                  expenseLine: Yup.string().required("Expense Line required"),
                  expenseCategory: Yup.string().required(
                    "Expense Category required"
                  ),
                  bank: Yup.string().required("Bank Name required"),
                  amount: Yup.number().required("Amount rquired"),
                  payTo: Yup.string().required("Required"),
                  accountNumber: Yup.number().required(
                    "Account Number rquired"
                  ),
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

                    <div className="flex py-3 gap-x-4">
                      <div className="w-[50%]">
                        <label
                          htmlFor="costCenter"
                          className="block mb-2 text-sm font-normal text-[#000000]"
                        >
                          Cost Center
                        </label>
                        <Field
                          type="text"
                          id="costCenter"
                          className="border border-gray-300 text-gray-900 text-sm rounded w-[100%]  p-1.5"
                          placeholder=""
                          value={values.amount}
                          onChange={handleChange}
                          disabled
                        />
                      </div>
                      <div className="w-[50%]">
                        
                      </div>
                    </div>

                    <div className="flex mt-5 py-3 gap-x-4 items-center">
                      <div className="w-[50%]">
                        <label
                          htmlFor="expenseCategory"
                          className="block mb-2 text-sm font-normal text-[#404040]"
                        >
                          Expense Category
                        </label>
                        <Field
                          id="expenseCategory"
                          as="select"
                          className="bg-[#FFFFFF] border border-[#D9D9D9] text-gray-900  text-sm rounded-[4px] block w-full p-1.5 "
                          value={values.expenseCategory}
                          onChange={handleChange}
                        >
                          <option
                            disabled={true}
                            value={""}
                            className="text-[#BFBFBF]"
                          >
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

                      <div className="w-[50%] ">
                        <label
                          htmlFor="createdBy"
                          className="block mb-2 text-sm font-normal text-[#404040]"
                        >
                          Created By
                        </label>
                        <Field
                          type="text"
                          id="createdBy"
                          className="border border-gray-300 text-gray-900 text-sm rounded  w-full p-1.5"
                          placeholder=""
                          value={values.amount}
                          onChange={handleChange}
                          disabled
                        />
                      </div>
                    </div>

                    <section className="mt-3">
                      <h3 className="font-medium text-lg text-[#404040] leading-[18px] ">
                        Expense Line
                      </h3>
                      <p className="pt-1 text-[#6F6F6F] text-sm leading-[18px] font-normal ">
                        Add Expense information to the expense line.
                      </p>

                      <section className="flex gap-x-4 mt-2">
                        <div className="mt-5 w-[50%]">
                          <label
                            htmlFor="expenseLine"
                            className="block mb-2 text-sm font-medium text-gray-900"
                          >
                            Expense Line
                          </label>
                          <Field
                            id="expenseLine"
                            as="select"
                            className="bg-[#FFFFFF] border border-[#D9D9D9] text-gray-900 text-sm rounded-[4px] block w-full p-1.5 "
                            value={values.expenseLine}
                            onChange={handleChange}
                          >
                            <option disabled={true} value={""}>
                              Choose an expense line
                            </option>
                            <option>Car Insurance</option>
                            <option>Part Replacement</option>
                            <option>Vehicle Maintenance</option>
                            <option>Driver Support</option>
                          </Field>
                          <p className="font-medium text-xs text-red-700">
                            <ErrorMessage name="expenseLine" />
                          </p>
                        </div>

                        <div className="w-[50%] mt-5">
                          <label
                            htmlFor="attachment"
                            className="block mb-2 text-sm font-medium text-[#404040]"
                          >
                            Upload Invoice (.png, .jpeg, .pdf)
                          </label>
                          <Link href={""}>
                            <div
                              onClick={openModal}
                              className="flex items-center cursor-pointer pl-2 border w-full border-dashed h-[34px] rounded-lg   border-[#BFBFBF]"
                            >
                              <Image src={attach} alt="attachment icon" />
                              <p className="text-[#BFBFBF] font-medium text-sm pl-1 leading-[18px]">
                                Attachment
                              </p>
                            </div>
                            <Modal
                              isOpen={modalIsOpen}
                              onRequestClose={closeModal}
                              ariaHideApp={false}
                              shouldCloseOnOverlayClick={false}
                              overlayClassName=""
                              style={customStyles}
                            >
                              <div className="w-[643px] h-[345px] border rounded-lg px-[32px] bg-white">
                                <section className="flex justify-between mb-[40] items-center h-[50px] rounded-t-lg">
                                  <p className="mr-auto text-[#404040] text-[22px] leading-[18px] font-bold">
                                    Upload Invoice
                                  </p>
                                  <div
                                    onClick={closeModal}
                                    className="flex cursor-pointer rounded-full w-[20px] h-[20px] mr-4"
                                  >
                                    <Image
                                      className="self-center ml-[4.5px]"
                                      src={closebutton}
                                      alt={"close button"}
                                    />
                                  </div>
                                </section>
                                <StyledDropzone />

                                <div className="flex mt-3 justify-between">
                                  <p className="font-medium text-xs leading-[18px] text-[#8C8C8C] ">
                                    Supported formats: png, jpeg, pdf
                                  </p>
                                  <p className="font-medium text-xs leading-[18px] text-[#8C8C8C] ">
                                    Maximum file size: 20 MB
                                  </p>
                                </div>
                              </div>
                            </Modal>
                          </Link>
                        </div>
                      </section>

                      <div className="flex mt-5 gap-x-5 border-b pb-7 ">
                        <div className="w-[50%]">
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
                            className="block p-2.5 w-full h-[100px] text-sm text-gray-900 bg-[#FFFFFF] rounded-[4px] border border-gray-300"
                            placeholder="Enter Description"
                            value={values.description}
                            onChange={handleChange}
                          />
                          <p className="font-medium text-xs  text-red-700">
                            <ErrorMessage name="description" />
                          </p>
                        </div>

                        <div className="w-[50%]">
                          <label
                            htmlFor="amount"
                            className="block mb-2 text-sm font-normal  text-[#000000]"
                          >
                            Amount (GH₵)
                          </label>
                          <Field
                            type="text"
                            id="amount"
                            className="border border-gray-300 text-gray-900 text-sm rounded block w-full p-1.5"
                            placeholder="Amount"
                            value={values.amount}
                            onChange={handleChange}
                          />
                          <p className="font-medium text-xs text-red-700">
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

                      <div className="mt-5">
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

                      <div className="flex gap-x-4 mt-5">
                        <div className="w-[50%] ">
                          <label
                            htmlFor="bank"
                            className="block mb-2 text-sm font-medium text-gray-900"
                          >
                            Bank Name
                          </label>
                          <Field
                            id="bank"
                            as="select"
                            className="bg-[#FFFFFF] border border-[#D9D9D9] text-gray-900 text-sm rounded-[4px] block w-full p-1.5 "
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

                        <div className="w-[50%]">
                          <label
                            htmlFor="accountNumber"
                            className="block mb-2 text-sm font-normal  text-[#000000]"
                          >
                            Account Number
                          </label>
                          <Field
                            type="text"
                            id="accountNumber"
                            className="border border-gray-300 text-gray-900 text-sm rounded block w-full p-1.5"
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
                          <p className="font-medium text-xs text-red-700">
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
