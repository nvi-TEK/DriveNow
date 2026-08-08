import React from "react";
import { Select, Space, ConfigProvider, theme as antdTheme } from "antd";
import { useTheme } from "next-themes";
import KeyboardArrowDownIcon from "@mui/icons-material/KeyboardArrowDown";

const handleChange = (value) => {
  console.log(`selected ${value}`);
};

export default function UpdateStatusDropdown() {
  const { resolvedTheme } = useTheme();

  return (
    <ConfigProvider
      theme={{
        algorithm:
          resolvedTheme === "dark"
            ? antdTheme.darkAlgorithm
            : antdTheme.defaultAlgorithm,
        token: {
          colorText: "",
          fontSize: 12,
          lineHeight: 1,
          fontFamily: "Avenir",
        },
        components: {
          Select: {
            optionActiveBg: "#F1F8FF",
            optionSelectedColor: "#007AF5",
            optionPadding: "15px 0px 15px 8px",
          },
        },
      }}
    >
      <Space wrap>
        <div className="relative" id="update"></div>
        <Select
          className="text-[#8C8C8C] cursor-pointer"
          getPopupContainer={() => document.getElementById("update")}
          suffixIcon={<KeyboardArrowDownIcon className="dark:text-white" />}
          popupClassName="p-2"
          placeholder="Select"
          style={{ width: 250, font: "#8C8C8C" }}
          onChange={handleChange}
          options={[
            { value: "Awaiting Allocation", label: "Awaiting Allocation" },
            {
              value: "Driver/ Vehicle Issues",
              label: "Driver/ Vehicle Issues",
            },
            { value: "Driver Allocated", label: "Driver Allocated" },
            { value: "Out of Service", label: "Out of Service" },
          ]}
        />
      </Space>
    </ConfigProvider>
  );
}
