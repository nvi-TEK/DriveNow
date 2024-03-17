import React from "react";
import { DatePicker, Space } from "antd";
import dayjs from "dayjs";
import customParseFormat from "dayjs/plugin/customParseFormat";
import moment from "moment";
const { RangePicker } = DatePicker;
const dateFormat = "DD/MM/YYYY";

dayjs.extend(customParseFormat);

function Picker() {
  return (
    <DatePicker
      format={dateFormat}
      placeholder="dd/mm/yyyy"
      className="border-[#D9D9D9] dark:bg-gray-600 dark:border-0 dark:text-white shadow-[0px_1px_2px_0px_#1B283614] text-xs py-[5px] rounded-[4px]  w-full"
    />
  );
}

function AddExpensePicker() {
  return (
    <DatePicker
      format={dateFormat}
      disabled={true}
      defaultValue={moment()}
      placeholder="dd/mm/yyyy"
      className="border-[#D9D9D9] bg-[#F0F0F0] dark:bg-gray-600 dark:border-0 dark:text-white shadow-[0px_1px_2px_0px_#1B283614] text-xs py-[5px] rounded-[4px]  w-full"
    />
  );
}

function TableRange() {
  return (
    <Space direction="vertical" size={12}>
      <RangePicker
        size="small"
        placeholder={["Start", "End"]}
        className="w-[216px] dark:bg-gray-600 dark:border-0 dark:text-white shadow-[0px_1px_2px_0px_#1B283614] rounded-[3px]"
      />
    </Space>
  );
}

export default Picker;
export { TableRange, AddExpensePicker };
