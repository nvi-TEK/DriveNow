/* eslint-disable react/no-unescaped-entities */
/* eslint-disable require-jsdoc */
import React from "react";
import { useEffect, useState } from "react";
import KYC from "@/components/driverKYC";
import Head from "next/head";
import Layout from "../../components/layout";
import Link from "next/link";
import Image from "next/image";
import driver from "../../assets/driver_icon.png";
import vehicle from "../../assets/vehicle_icon.png";
import payment from "../../assets/payments.png";
import declined from "../../assets/declined.png";
import ExpenseTiles from "@/components/Expense/ExpenseTiles";
import ExpenseChart from "@/components/Expense/ExpenseChart";
import { ExpenseTable } from "../../components/Expense/ExpenseTable";
import revenue from "../../assets/revenue_icon.png";
import AccountMenu from "@/components/headerDropdown";
import { Grid } from "@mui/material";
// import { gridSpacing } from "../components/revenueChart/constant";
import BasicStacking from "@/components/stackedChart";

export default function Expense() {
  return (
    <>
      <Layout>
        <Head>
          <title>Expense</title>
          <meta name="description" content="Generated by create next app" />
          <meta name="viewport" content="width=device-width, initial-scale=1" />
        </Head>

        {/* Code goes into the main tag */}
        <main className="bg-[#F2F2F2] w-full xg:min-h-screen">
          {/* Bottom menu */}
          <section className="w-full flex">
            <div className="flex grow gap-x-4 pr-5 ml-5 mt-5">
              <ExpenseTiles
                icon={payment}
                entity1="Total Expenses (Paid)"
                entity1value={1250}
                entity1differential="+15.80%"
                entity2="Approved Expenses"
                entity2value={1180}
              />

              <ExpenseTiles
                icon={declined}
                entity1="Total Expenses (Declined)"
                entity1value={20}
                entity1differential="+5.80%"
                entity2="Pending Expenses"
                entity2value={450}
              />
            </div>
          </section>

          <section className="flex">
            <div className="bg-white ml-5 mt-6 w-[61.03%] h-[41rem] ">
              <ExpenseChart />
            </div>
          </section>

          {/* Table */}
          <div className="bg-white mt-4 rounded-lg mx-5 ">
            <h3 className="text-[22px] font-medium leading-[30px] pl-[10px] pt-4 text-[#262626] ">
              Expense History
            </h3>

            <ExpenseTable />
          </div>
        </main>
      </Layout>
    </>
  );
}
