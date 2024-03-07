import React from "react";
import { Select, Space, ConfigProvider } from "antd";
import UnfoldMoreIcon from "@mui/icons-material/UnfoldMore";
const handleChange = (value: string) => {
  console.log(`selected ${value}`);
};

export default function ChartDropdown() {
  return (
    <ConfigProvider
      theme={{
        token: {
          colorText: "#8C8C8C",
          fontSize: 12,
          lineHeight: 1,
        },
        components: {
          Select: {
            optionActiveBg: "#F1F8FF",
            optionSelectedColor: "#007AF5",
            optionPadding: "6px 20px 3px 6px",
          },
        },
      }}
    >
      <Space wrap>
        <Select
          className="text-[#8C8C8C]"
          defaultValue="This Year"
          suffixIcon={<UnfoldMoreIcon />}
          variant="borderless"
          style={{ width: 100 }}
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
  return (
    <ConfigProvider
      theme={{
        token: {
          colorText: "#8C8C8C",
          fontSize: 12,
          lineHeight: 1,
        },
        components: {
          Select: {
            optionActiveBg: "#F1F8FF",
            optionSelectedColor: "#007AF5",
            optionPadding: "6px 20px 3px 6px",
          },
        },
      }}
    >
      <Space wrap>
        <Select
          className="text-[#8C8C8C]"
          defaultValue="2024"
          suffixIcon={<UnfoldMoreIcon />}
          variant="borderless"
          style={{ width: 71 }}
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
  return (
    <ConfigProvider
      theme={{
        token: {
          colorText: "#8C8C8C",
          fontSize: 12,
          lineHeight: 1,
        },
        components: {
          Select: {
            optionActiveBg: "#F1F8FF",
            optionSelectedColor: "#007AF5",
          },
        },
      }}
    >
      <Space wrap>
        <Select
          className="text-[#8C8C8C]"
          defaultValue="FLEET"
          suffixIcon={<UnfoldMoreIcon />}
          variant="borderless"
          style={{ width: 76 }}
          onChange={handleChange}
          options={[{ value: "FLEET", label: "FLEET" }]}
        />
      </Space>
    </ConfigProvider>
  );
}

function MonthDropdown() {
  return (
    <ConfigProvider
      theme={{
        token: {
          colorText: "#8C8C8C",
          fontSize: 12,
          lineHeight: 1,
        },
        components: {
          Select: {
            optionActiveBg: "#F1F8FF",
            optionSelectedColor: "#007AF5",
            optionPadding: "6px 20px 3px 6px",
          },
        },
      }}
    >
      <Space wrap>
        <Select
          className="text-[#8C8C8C]"
          defaultValue="JAN"
          suffixIcon={<UnfoldMoreIcon />}
          variant="borderless"
          style={{ width: 66 }}
          onChange={handleChange}
          options={[{ value: "JAN", label: "JAN" }]}
        />
      </Space>
    </ConfigProvider>
  );
}

export { YearDropdown, MonthDropdown, CategoryDropdown };
