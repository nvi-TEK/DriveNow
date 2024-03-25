import React, { useState } from "react";
import { Select, Space, ConfigProvider } from "antd";
import { BasicTable } from "./payment/BasicTable";
import { PaymentTransactionsTable } from "./payment/PaymentTransactions/PaymentTransactionsTable";
import { DailyPaymentsTable } from "./payment/DailyPayments/DailyPaymentsTable";
import UnfoldMoreIcon from "@mui/icons-material/UnfoldMore";
import KeyboardArrowDownIcon from "@mui/icons-material/KeyboardArrowDown";
import { DrivenowInvoicesTable } from "./DriveNowInvoices/DrivenowInvoices";

export default function PaymentsDropdown() {
  const [selected, setSelected] = useState("Weekly Payments");

  const handleChange = (value) => {
    setSelected(value);
  };
  return (
    <ConfigProvider
      theme={{
        token: {
          colorText: "#FFFFFF",
          fontSize: 12,
          lineHeight: 1,
          lineWidth: 0,
          fontFamily: "Avenir",
        },
        components: {
          Select: {
            optionActiveBg: "#F1F8FF",
            optionSelectedColor: "#007AF5",
            colorText: "black",
            optionPadding: "8px 0px 8px 8px",
            selectorBg: "#007AF5",
          },
        },
      }}
    >
      <Space className="flex" wrap>
        <label className="text-[#262626] dark:text-white">Select:</label>
        <Select
          id="abc"
          className="h-6 ml-2 border-0"
          defaultValue={"weekly Payments"}
          popupClassName=""
          dropdownStyle={{ BackgroundColor: "black" }}
          value={selected}
          suffixIcon={
            <KeyboardArrowDownIcon
              fontSize="small"
              className="dark:text-white text-white"
            />
          }
          style={{ width: 199, font: "#8C8C8C" }}
          onChange={handleChange}
          options={[
            { value: "Weekly Payments", label: "Weekly Payments" },
            { value: "Daily Payments", label: "Daily Payments" },
            { value: "Payment Transactions", label: "Payment Transactions" },
          ]}
        />
        {selected == "Weekly Payments" ? <BasicTable /> : ""}
        {selected == "Daily Payments" ? <DailyPaymentsTable /> : ""}
        {selected == "Payment Transactions" ? <PaymentTransactionsTable /> : ""}
      </Space>
    </ConfigProvider>
  );
}

function DriveNowInvoicesDropdown() {
  const [selected, setSelected] = useState("Weekly Invoices");

  const handleChange = (value) => {
    setSelected(value);
  };
  return (
    <ConfigProvider
      theme={{
        token: {
          colorText: "#FFFFFF",
          fontSize: 12,
          lineHeight: 1,
          lineWidth: 0,
          fontFamily: "Avenir",
        },
        components: {
          Select: {
            optionActiveBg: "#F1F8FF",
            optionSelectedColor: "#007AF5",
            colorText: "black",
            optionPadding: "8px 0px 8px 8px",
            selectorBg: "#007AF5",
          },
        },
      }}
    >
      <Space className="flex" wrap>
        <label className="text-[#262626] dark:text-white">Select:</label>
        <Select
          id="abc"
          className="h-6 ml-2 border-0"
          defaultValue={"Weekly Invoices"}
          popupClassName=""
          dropdownStyle={{ BackgroundColor: "black" }}
          value={selected}
          suffixIcon={
            <KeyboardArrowDownIcon
              fontSize="small"
              className="dark:text-white text-white"
            />
          }
          style={{ width: 199, font: "#8C8C8C" }}
          onChange={handleChange}
          options={[
            { value: "Weekly Invoices", label: "Weekly Invoices" },
            { value: "Daily Invoices", label: "Daily Invoices" },
          ]}
        />
        {selected == "Weekly Invoices" ? <DrivenowInvoicesTable /> : ""}
        {selected == "Daily Invoices" ? <DailyPaymentsTable /> : ""}
      </Space>
    </ConfigProvider>
  );
}

export { DriveNowInvoicesDropdown };
