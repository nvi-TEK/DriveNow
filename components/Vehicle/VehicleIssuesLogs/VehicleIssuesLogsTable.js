/* eslint-disable */

import React, { useMemo } from "react";
import {
  useTable,
  usePagination,
  useSortBy,
  useGlobalFilter,
  useRowSelect,
} from "react-table";
import VEHICLE_ISSUES_LOGS_DATA from "../VehicleIssuesLogs/VEHICLE_ISSUES_LOGS_DATA.json";
import { VEHICLEISSUESLOGSCOLUMNS } from "../VehicleIssuesLogs/VehicleIssuesLogsColumns";
import sortingarrows from "../../../assets/chevrons-up-down.png";
import {
  Bubble,
  AgreedBubble,
  EngineControlBubble,
} from "../../payment/PaymentBubbles";
import UnfoldMoreOutlinedIcon from "@mui/icons-material/UnfoldMoreOutlined";
import ArrowBackIosOutlinedIcon from "@mui/icons-material/ArrowBackIosOutlined";
import { VehicleIssuesLogsFilter } from "../VehicleIssuesLogs/VehicleIssuesLogsFilter";
import ArrowForwardIosOutlinedIcon from "@mui/icons-material/ArrowForwardIosOutlined";
import { Checkbox } from "../../Checkbox";

export const VehicleIssuesLogsTable = () => {
  const columns = useMemo(() => VEHICLEISSUESLOGSCOLUMNS, []);
  const data = useMemo(() => VEHICLE_ISSUES_LOGS_DATA, []);

  const {
    getTableProps,
    getTableBodyProps,
    headerGroups,
    page,
    nextPage,
    previousPage,
    canNextPage,
    canPreviousPage,
    gotoPage,
    pageCount,
    pageOptions,
    state,
    setGlobalFilter,
    setPageSize,
    prepareRow,
    selectedFlatRows,
  } = useTable(
    {
      columns,
      data,
    },
    useGlobalFilter,
    useSortBy,
    usePagination,
    useRowSelect,
    (hooks) => {
      hooks.visibleColumns.push((columns) => {
        return [
          {
            id: "selection",
            Header: ({ getToggleAllRowsSelectedProps }) => (
              <Checkbox {...getToggleAllRowsSelectedProps()} />
            ),
            Cell: ({ row }) => (
              <Checkbox {...row.getToggleRowSelectedProps()} />
            ),
          },
          ...columns,
        ];
      });
    }
  );

  const { pageIndex, pageSize } = state;

  const dropdown = [
    <select
      value={pageSize}
      onChange={(e) => setPageSize(Number(e.target.value))}
      className="border cursor-pointer shadow-[0px_1px_2px_0px_#1B283614] dark:border-0 dark:bg-dm-600 dark:text-white h-[30px] text-center border-[#D9D9D9] text-[#BFBFBF] rounded px-1 py-1 "
      place
    >
      abc
      {[10, 15, 20].map((pageSize) => (
        <option key={pageSize} value={pageSize} className="dark:text-white dark:bg-dm-600">
          {pageSize}
        </option>
      ))}
    </select>,
  ];

  const dropdown2 = [
    <select
      value={pageSize}
      onChange={(e) => setPageSize(Number(e.target.value))}
      className="border cursor-pointer shadow-[0px_1px_2px_0px_#1B283614] dark:border-0 dark:bg-dm-600 dark:text-white h-[30px] text-center border-[#D9D9D9] text-xs rounded px-1 py-1 "
      aria-placeholder=""
    >
      abc
      {[10, 15, 20].map((pageSize) => (
        <option key={pageSize} value={pageSize} className="dark:text-white dark:bg-dm-600">
          {pageSize} Items/Page
        </option>
      ))}
    </select>,
  ];

  const { globalFilter } = state;

  return (
    <>
      {/* number of entries dropdown and Search bar */}
      <div className="flex items-center mt-6  ml-3">
        <p className="font-medium dark:text-white leading-[30px] mr-[33px] text-[#262626] ">
          Show {dropdown} entries
        </p>

        <VehicleIssuesLogsFilter
          filter={globalFilter}
          setFilter={setGlobalFilter}
        />
      </div>

      {/* Table */}
      <table {...getTableProps()} className="mt-7 w-full">
        <thead>
          {headerGroups.map((headerGroup) => (
            <tr {...headerGroup.getHeaderGroupProps()}>
              {headerGroup.headers.map((column) => (
                <th
                  {...(column.sortable
                    ? {
                        ...column.getHeaderProps(column.getSortByToggleProps()),
                      }
                    : { ...column.getHeaderProps() })}
                  className="text-left font-normal leading-[18px] h-[48px] text-[#262626] dark:text-white dark:bg-dm-600 pl-2 bg-[#FAFAFA] "
                >
                  {column.render("Header")}
                  <span>
                  {column.sortable
                      ? {
                          ...(column.isSorted ? (
                            column.isSortedDesc ? (
                              <UnfoldMoreOutlinedIcon fontSize="small" />
                            ) : (
                              <UnfoldMoreOutlinedIcon fontSize="small" />
                            )
                          ) : (
                            <UnfoldMoreOutlinedIcon fontSize="small" />
                          )),
                        }
                      : ""}
                  </span>
                </th>
              ))}
            </tr>
          ))}
        </thead>
        <tbody {...getTableBodyProps()}>
          {page.map((row) => {
            prepareRow(row);
            return (
              <tr {...row.getRowProps()}>
                {row.cells.map((cell) => {
                  return (
                    <>
                      <td
                        {...cell.getCellProps()}
                        className="text-[#595959] pl-2 font-normal dark:text-white dark:border-dm-500 leading-[18px] border-y h-[48px]"
                      >
                        {cell.render("Cell")}
                      </td>
                    </>
                  );
                })}
              </tr>
            );
          })}
        </tbody>
      </table>

      <div className="flex mt-5 pb-4 justify-end gap-x-2">
        <button
          onClick={() => previousPage()}
          disabled={!canPreviousPage}
          className="px-2 border dark:bg-dm-600 dark:border-0 text-[#595959] dark:text-white rounded-sm  cursor-pointer disabled:cursor-not-allowed"
        >
          <ArrowBackIosOutlinedIcon fontSize="small" />
        </button>

        <button
          className="border dark:bg-dm-600 dark:border-0 text-[#595959] dark:text-white px-3 rounded cursor-pointer"
          onClick={() => gotoPage(0)}
        >
          {" "}
          1{" "}
        </button>
        <button
          className="border dark:bg-dm-600 dark:border-0 text-[#595959] dark:text-white px-3 rounded cursor-pointer"
          onClick={() => gotoPage(1)}
        >
          {" "}
          2{" "}
        </button>
        <button
          className="border dark:bg-dm-600 dark:border-0 text-[#595959] dark:text-white px-3 rounded cursor-pointer"
          onClick={() => gotoPage(2)}
        >
          {" "}
          3{" "}
        </button>
        <button
          className="border dark:bg-dm-600 dark:border-0 text-[#595959] dark:text-white px-3 rounded cursor-pointer"
          onClick={() => gotoPage(3)}
        >
          {" "}
          4{" "}
        </button>
        <button
          className="border dark:bg-dm-600 dark:border-0 text-[#595959] dark:text-white px-3 rounded cursor-pointer"
          onClick={() => gotoPage(4)}
        >
          {" "}
          5{" "}
        </button>

        <button
          onClick={() => nextPage()}
          disabled={!canNextPage}
          className="px-2 border dark:bg-dm-600 dark:border-0 text-[#595959] dark:text-white rounded-sm  cursor-pointer disabled:cursor-not-allowed"
        >
          <ArrowForwardIosOutlinedIcon fontSize="small" />
        </button>

        <p className="font-medium text-sm leading-[30px] text-[#262626] ">
          {dropdown2}
        </p>
      </div>
    </>
  );
};
