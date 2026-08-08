import React from "react";
import { DatePicker, Space, ConfigProvider, theme as antdTheme } from "antd";
import { useTheme } from "next-themes";
import dayjs from "dayjs";
import customParseFormat from "dayjs/plugin/customParseFormat";
import moment from "moment";
const { RangePicker } = DatePicker;
const dateFormat = "DD/MM/YYYY";

dayjs.extend(customParseFormat);

function Picker() {
  const { resolvedTheme } = useTheme();

  return (
    <ConfigProvider
      theme={{
        algorithm:
          resolvedTheme === "dark"
            ? antdTheme.darkAlgorithm
            : antdTheme.defaultAlgorithm,
      }}
    >
      <DatePicker
        format={dateFormat}
        placeholder="dd/mm/yyyy"
        className="border-[#D9D9D9] dark:bg-dm-600 dark:border-0 dark:text-white cursor-pointer shadow-[0px_1px_2px_0px_#1B283614] text-xs py-[5px] rounded-[4px]  w-full"
      />
    </ConfigProvider>
  );
}

function AddExpensePicker() {
  const { resolvedTheme } = useTheme();

  return (
    <ConfigProvider
      theme={{
        algorithm:
          resolvedTheme === "dark"
            ? antdTheme.darkAlgorithm
            : antdTheme.defaultAlgorithm,
      }}
    >
      <DatePicker
        format={dateFormat}
        disabled={true}
        defaultValue={moment()}
        placeholder="dd/mm/yyyy"
        className="border-[#D9D9D9] bg-[#F0F0F0] dark:bg-dm-600 dark:border-0 dark:text-white shadow-[0px_1px_2px_0px_#1B283614] text-xs py-[5px] rounded-[4px]  w-full"
      />
    </ConfigProvider>
  );
}

function TableRange() {
  const { resolvedTheme } = useTheme();

  return (
    <ConfigProvider
      theme={{
        algorithm:
          resolvedTheme === "dark"
            ? antdTheme.darkAlgorithm
            : antdTheme.defaultAlgorithm,
      }}
    >
      <Space direction="vertical" size={12}>
        <RangePicker
          size="small"
          placeholder={["Start", "End"]}
          className="w-[216px] dark:bg-dm-600 dark:border-0 dark:text-white cursor-pointer shadow-[0px_1px_2px_0px_#1B283614] rounded-[3px]"
        />
      </Space>
    </ConfigProvider>
  );
}

export default Picker;
export { TableRange, AddExpensePicker };
