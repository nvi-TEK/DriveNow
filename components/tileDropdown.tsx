import React, { useState } from "react";
import { Select, Space, ConfigProvider } from "antd";
import { BasicTable } from "./payment/BasicTable";
import { PaymentTransactionsTable } from "./payment/PaymentTransactions/PaymentTransactionsTable";
import { DailyPaymentsTable } from "./payment/DailyPayments/DailyPaymentsTable";
import UnfoldMoreIcon from "@mui/icons-material/UnfoldMore";

const handleChange = (value: string) => {
  console.log(`selected ${value}`);
};

export default function TileDropdown() {
  return (
    <ConfigProvider
      theme={{
        token: {
          colorText: "#8C8C8C",
          fontSize: 12,
          lineHeight: 1,
          fontFamily: "Avenir",
        },
        components: {
          Select: {
            optionActiveBg: "#F1F8FF",
            optionSelectedColor: "#007AF5",
            optionPadding: "11px 50px 8px 8px",
          },
        },
      }}
    >
      <Space wrap>
        <Select
          className="text-[#8C8C8C]"
          defaultValue="This Week"
          suffixIcon={<UnfoldMoreIcon className="dark:text-white" />}
          popupMatchSelectWidth={false}
          variant="borderless"
          style={{ width: 102 }}
          onChange={handleChange}
          options={[
            { value: "Today", label: "Today" },
            { value: "This Week", label: "This Week" },
            { value: "This Month", label: "This Month" },
            { value: "This Year", label: "This Year" },
          ]}
        />
      </Space>
    </ConfigProvider>
  );
}

function TileDropdown1() {
  return (
    <ConfigProvider
      theme={{
        token: {
          colorText: "#8C8C8C",
          fontSize: 12,
          lineHeight: 1,
          fontFamily: "Avenir",
        },
        components: {
          Select: {
            optionActiveBg: "#F1F8FF",
            optionSelectedColor: "#007AF5",
            optionPadding: "10px 0px 8px 8px",
          },
        },
      }}
    >
      <Space wrap>
        <Select
          className="text-[#8C8C8C]"
          defaultValue="This Week"
          suffixIcon={<UnfoldMoreIcon className="dark:text-white" />}
          variant="borderless"
          style={{ width: 106, font: "#8C8C8C" }}
          onChange={handleChange}
          options={[
            { value: "This Week", label: "This Week" },
            { value: "This Month", label: "This Month" },
            { value: "This Year", label: "This Year" },
          ]}
        />
      </Space>
    </ConfigProvider>
  );
}

export { TileDropdown1 };
