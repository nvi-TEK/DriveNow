import React, { useState } from "react";
import { Select, Space, ConfigProvider } from "antd";
import { BasicTable } from "./payment/BasicTable";
import { PaymentTransactionsTable } from "./payment/PaymentTransactions/PaymentTransactionsTable";
import { DailyPaymentsTable } from "./payment/DailyPayments/DailyPaymentsTable";
import UnfoldMoreIcon from "@mui/icons-material/UnfoldMore";
import KeyboardArrowDownIcon from "@mui/icons-material/KeyboardArrowDown";

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
          popupClassName="p-2"
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
          popupMatchSelectWidth={false}
          popupClassName="p-2"
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

// function UpdateStatus() {
//   return (
//     <ConfigProvider
//       theme={{
//         token: {
//           colorText: "#8C8C8C",
//           fontSize: 12,
//           lineHeight: 1,
//           fontFamily: "Avenir",
//         },
//         components: {
//           Select: {
//             optionActiveBg: "#F1F8FF",
//             optionSelectedColor: "#007AF5",
//             optionPadding: "10px 0px 8px 8px",
//           },
//         },
//       }}
//     >
//       <Space wrap>
//         <div className="relative" id="update" >
//         <Select
//           className="text-[#8C8C8C] z-1000"
//           getPopupContainer={}
//           suffixIcon={<KeyboardArrowDownIcon className="dark:text-white" />}
//           popupMatchSelectWidth={false}
//           popupClassName="p-2"
//           style={{ width: 255, font: "#8C8C8C" }}
//           onChange={handleChange}
//           options={[
//             { value: "Awaiting Allocation", label: "Awaiting Allocation" },
//             {
//               value: "Driver/ Vehicle Issues",
//               label: "Driver/ Vehicle Issues",
//             },
//             { value: "Driver Allocated", label: "Driver Allocated" },
//             { value: "Out of Service", label: "Out of Service" },
//           ]}
//         />
//         </div>
//       </Space>
//     </ConfigProvider>
//   );
// }

export { TileDropdown1 };
