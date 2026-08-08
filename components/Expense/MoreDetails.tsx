/* eslint-disable react/no-unescaped-entities */
/* eslint-disable require-jsdoc */
import React from "react";
import { useEffect, useState } from "react";
import KYC from "@/components/driverKYC";
import Head from "next/head";
import Layout from "../../components/layout";
import info from "../../../assets/info_icon.png";
import yellowinfo from "../../../assets/yellowinfo.png";
import Image from "next/image";
import Link from "next/link";
import { Formik, Form, Field, ErrorMessage } from "formik";
import * as Yup from "yup";
import Modal from "react-modal";
import closebutton from "../../assets/largeclose.svg";
import linksquare from "../../assets/link-square.svg";
import StyledDropzone from "@/components/dropzones/AddExpenseDropzone";
import attach from "../../../assets/attachment_icon.png";
import Header from "@/components/header";
import AddExpensePicker from "@/components/dateRange";

type MoreDetailsProp = {
  costCenter: string;
  date: string;
  time: string;
  expenseType: string;
  expenseCategory: string;
  expenseLine: string;
  carRegistrationNumber: string;
  requestedBy: string;
  approvedBy: string;
  bankName: string;
  accountNumber: number;
  paidTo: string;
  expenseStatus: string;
  description: string;
  amount: number;
};

export default function MoreDetails(prop: MoreDetailsProp) {
  return (
    <div className="p-8">
      <section className="flex justify-between items-center">
        <div>
          <h1 className="text-[#404040] dark:text-white font-medium leading-7 ">
            {prop.expenseType}
          </h1>
          <div className="flex gap-x-1">
            <p className="text-[#8C8C8C]">{prop.date}</p>
            <p className="text-[#8C8C8C]">{prop.time}</p>
          </div>
        </div>
        <Image src={closebutton} alt="close button" />
      </section>
      <section className="py-3 border-y border-[#F2F2F2] dark:border-dm-500 gap-y-3 grid grid-cols-3 ">
        <div>
          <p className="text-[#B3B3B3] dark:text-dm-300 font-normal leading-5">Cost Center</p>
          <p className="text-[#404040] dark:text-white leading-[18px] pt-1 font-normal ">
            {prop.costCenter}
          </p>
        </div>

        <div>
          <p className="text-[#B3B3B3] dark:text-dm-300 font-normal leading-5">
            Expense Catergory
          </p>
          <p className="text-[#404040] dark:text-white pt-1 leading-[18px] font-normal ">
            {prop.expenseCategory}
          </p>
        </div>
        <div>
          <p className="text-[#B3B3B3] dark:text-dm-300 font-normal leading-5">Expense Line</p>
          <p className="text-[#404040] dark:text-white pt-1 leading-[18px] font-normal ">
            {prop.expenseLine}
          </p>
        </div>
        <div>
          <p className="text-[#B3B3B3] dark:text-dm-300 font-normal leading-5">
            Car Registration Number
          </p>
          <p className="text-[#404040] dark:text-white pt-1 leading-[18px] font-normal ">
            {prop.carRegistrationNumber}
          </p>
        </div>
        <div>
          <p className="text-[#B3B3B3] dark:text-dm-300 font-normal leading-5">Requested By</p>
          <p className="text-[#404040] dark:text-white pt-1 leading-[18px] font-normal ">
            {prop.requestedBy}
          </p>
        </div>
        <div>
          <p className="text-[#B3B3B3] dark:text-dm-300 font-normal leading-5">Approved By</p>
          <p className="text-[#404040] dark:text-white pt-1 leading-[18px] font-normal ">
            {prop.approvedBy}
          </p>
        </div>
      </section>

      <section className="mt-3 grid gap-y-3 grid-cols-2 ">
        <div>
          <p className="text-[#B3B3B3] dark:text-dm-300 font-normal leading-5">Bank Name</p>
          <p className="text-[#404040] dark:text-white pt-1 leading-[18px] font-normal ">
            {prop.bankName}
          </p>
        </div>
        <div>
          <p className="text-[#B3B3B3] dark:text-dm-300 font-normal leading-5">
            Account Number (MoMo)
          </p>
          <p className="text-[#404040] dark:text-white pt-1 leading-[18px] font-normal ">
            {prop.accountNumber}
          </p>
        </div>
        <div>
          <p className="text-[#B3B3B3] dark:text-dm-300 font-normal leading-5">Paid To</p>
          <p className="text-[#404040] dark:text-white pt-1 leading-[18px] font-normal">
            {prop.paidTo}
          </p>
        </div>
        <div className="text-left">
          <p className="text-[#B3B3B3] dark:text-dm-300 font-normal leading-5">Expense Status</p>

          <div
            className={`py-1 px-2 mt-1 text-left inline-block rounded-sm ${
              prop.expenseStatus == "Pending"
                ? "bg-[#FBF6E9] dark:bg-[#E8B12333]"
                : "bg-[#E7F6F1] dark:bg-[#0EA37133]"
            }`}
          >
            <p
              className={`leading-[18px] text-left font-normal ${
                prop.expenseStatus == "Pending"
                  ? "text-[#E8B123] dark:text-[#FBBF24]"
                  : "text-[#0EA371] dark:text-[#34D399]"
              }`}
            >
              {prop.expenseStatus}
            </p>
          </div>
        </div>

        <div>
          <p className="text-[#B3B3B3] dark:text-dm-300 font-normal leading-5">Description</p>
          <p className="text-[#404040] dark:text-white pt-1 w-[350px] leading-[18px] font-normal">
            {prop.description}
          </p>
        </div>
        <div>
          <p className="text-[#B3B3B3] dark:text-dm-300 font-normal leading-5">Amount</p>
          <h2 className="text-[#404040] dark:text-white pt-1 leading-[18px] font-normal ">
            GHC {prop.amount}
          </h2>
        </div>
      </section>
      <section className="mt-3">
        <p className="text-[#B3B3B3] dark:text-dm-300 leading-5 font-normal ">Invoices</p>
        <p className="leading-[18px] font-normal text-[#404040] dark:text-white ">
          Click to open attached.
        </p>
        <div className="flex mt-3 gap-x-6">
          <div
            style={{}}
            className="w-[345px] flex items-center justify-center h-[125px] bg-[#0F0F0F8F] rounded-lg"
          >
            <button className="rounded-lg py-1 px-3 text-center inline-flex justify-center items-center bg-white cursor-pointer">
              Click to view
              <Image src={linksquare} className="ml-2" alt="link square" />
            </button>
          </div>
          <div className="w-[345px] flex items-center justify-center h-[125px] rounded-lg bg-[#0F0F0F8F]">
            <button className="rounded-lg py-1 px-3 text-center inline-flex justify-center items-center bg-white cursor-pointer">
              Click to view
              <Image src={linksquare} className="ml-2" alt="link square" />
            </button>
          </div>
        </div>
      </section>
    </div>
  );
}
