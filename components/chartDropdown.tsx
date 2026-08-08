import React from "react";
import { Select, Space, ConfigProvider, theme as antdTheme } from "antd";
import { useTheme } from "next-themes";
import UnfoldMoreIcon from "@mui/icons-material/UnfoldMore";
const handleChange = (value: string) => {
  console.log(`selected ${value}`);
};

export default function ChartDropdown() {
  const { resolvedTheme } = useTheme();

  return (
    <ConfigProvider
      theme={{
        algorithm:
          resolvedTheme === "dark"
            ? antdTheme.darkAlgorithm
            : antdTheme.defaultAlgorithm,
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
            optionPadding: "12px 0px 8px 8px",
          },
        },
      }}
    >
      <Space wrap>
        <Select
          className="text-[#8C8C8C] cursor-pointer"
          defaultValue="This Year"
          suffixIcon={<UnfoldMoreIcon className="dark:text-white" />}
          popupMatchSelectWidth={false}
          popupClassName="p-2"
          variant="borderless"
          style={{ width: 102 }}
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

function YearDropdown() {
  const { resolvedTheme } = useTheme();

  return (
    <ConfigProvider
      theme={{
        algorithm:
          resolvedTheme === "dark"
            ? antdTheme.darkAlgorithm
            : antdTheme.defaultAlgorithm,
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
            optionPadding: "16px 70px 12px 8px",
            optionHeight: 34,
          },
        },
      }}
    >
      <Space wrap>
        <Select
        id="yearDropdown"
          className="text-[#8C8C8C] cursor-pointer"
          defaultValue="2024"
          suffixIcon={<UnfoldMoreIcon className="dark:text-white" />}
          popupMatchSelectWidth={false}
          popupClassName="p-2"
          dropdownStyle={{gap: "15px"}}
          placement="bottomRight"
          variant="borderless"
          style={{ width: 68 }}
          onChange={handleChange}
          options={[
            { value: "2024", label: "2024" },
            { value: "2023", label: "2023" },
            { value: "2022", label: "2022" },
          ]}
        />
      </Space>
    </ConfigProvider>
  );
}

function CategoryDropdown() {
  const { resolvedTheme } = useTheme();

  return (
    <ConfigProvider
      theme={{
        algorithm:
          resolvedTheme === "dark"
            ? antdTheme.darkAlgorithm
            : antdTheme.defaultAlgorithm,
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
            optionPadding: "12px 0px 8px 8px",
          },
        },
      }}
    >
      <Space wrap>
        <Select
          className="text-[#8C8C8C] cursor-pointer"
          defaultValue="FLEET"
          suffixIcon={<UnfoldMoreIcon className="dark:text-white" />}
          popupMatchSelectWidth={false}
          popupClassName="p-2"
          variant="borderless"
          style={{ width: 79 }}
          onChange={handleChange}
          options={[{ value: "FLEET", label: "FLEET" }]}
        />
      </Space>
    </ConfigProvider>
  );
}

function MonthDropdown() {
  const { resolvedTheme } = useTheme();

  return (
    <ConfigProvider
      theme={{
        algorithm:
          resolvedTheme === "dark"
            ? antdTheme.darkAlgorithm
            : antdTheme.defaultAlgorithm,
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
            optionPadding: "12px 0px 8px 8px",
          },
        },
      }}
    >
      <Space wrap>
        <Select
          className="text-[#8C8C8C] cursor-pointer"
          defaultValue="JAN"
          suffixIcon={<UnfoldMoreIcon className="dark:text-white" />}
          popupMatchSelectWidth={false}
          popupClassName="p-2"
          variant="borderless"
          style={{ width: 68 }}
          onChange={handleChange}
          options={[
            { value: "JAN", label: "JAN" },
            { value: "FEB", label: "FEB" },
            { value: "MAR", label: "MAR" },
            { value: "APR", label: "APR" },
          ]}
        />
      </Space>
    </ConfigProvider>
  );
}

export { YearDropdown, MonthDropdown, CategoryDropdown };
