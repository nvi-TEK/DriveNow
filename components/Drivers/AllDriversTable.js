/* eslint-disable */

import React, { useMemo } from "react";
import {
  useTable,
  usePagination,
  useSortBy,
  useGlobalFilter,
  useRowSelect,
} from "react-table";
import ALL_DRIVERS_DATA from "../../components/Drivers/ALL_DRIVERS_DATA.json";
import { ALLDRIVERSCOLUMNS } from "./AllDriversColumns";
import sortingarrows from "../../assets/chevrons-up-down.png";
import {
  Bubble,
  AgreedBubble,
  EngineControlBubble,
} from "../payment/PaymentBubbles";
import UnfoldMoreOutlinedIcon from "@mui/icons-material/UnfoldMoreOutlined";
import ArrowBackIosOutlinedIcon from "@mui/icons-material/ArrowBackIosOutlined";
import { AllDriversFilter } from "../Drivers/AllDriversFilter";
import ArrowForwardIosOutlinedIcon from "@mui/icons-material/ArrowForwardIosOutlined";
import { Checkbox } from "../../components/Checkbox";

export const AllDriversTable = () => {
  const columns = useMemo(() => ALLDRIVERSCOLUMNS, []);
  const data = useMemo(() => ALL_DRIVERS_DATA, []);

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
      className="border text-[#BFBFBF] rounded px-1 py-1 "
      place
    >
      abc
      {[10, 15, 20].map((pageSize) => (
        <option key={pageSize} value={pageSize}>
          {pageSize}
        </option>
      ))}
    </select>,
  ];

  const dropdown2 = [
    <select
      value={pageSize}
      onChange={(e) => setPageSize(Number(e.target.value))}
      className="border text-[#BFBFBF] rounded px-1 py-1 "
      aria-placeholder=""
    >
      abc
      {[10, 15, 20].map((pageSize) => (
        <option key={pageSize} value={pageSize}>
          {pageSize}
        </option>
      ))}
    </select>,
  ];

  const { globalFilter } = state;

  return (
    <>
      {/* number of entries dropdown and Search bar */}
      <div className="flex items-center mt-6  ml-5">
        <p className="font-medium text-sm leading-[30px] mr-[33px] text-[#262626] ">
          Show {dropdown} entries
        </p>

        <AllDriversFilter filter={globalFilter} setFilter={setGlobalFilter} />
      </div>

      {/* Table */}
      <table {...getTableProps()} className="mt-7 ml-[10px] w-[98%]">
        <thead>
          {headerGroups.map((headerGroup) => (
            <tr {...headerGroup.getHeaderGroupProps()}>
              {headerGroup.headers.map((column) => (
                <th
                  {...column.getHeaderProps(column.getSortByToggleProps())}
                  className="text-left text-sm font-normal text-[#262626] leading-[18px] pl-2 h-[48px] bg-[#FAFAFA] "
                >
                  {column.render("Header")}
                  <span>
                    {column.isSorted ? (
                      column.isSortedDesc ? (
                        <UnfoldMoreOutlinedIcon fontSize="small" />
                      ) : (
                        <UnfoldMoreOutlinedIcon fontSize="small" />
                      )
                    ) : (
                      <UnfoldMoreOutlinedIcon fontSize="small" />
                    )}
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
                        className="text-sm font-normal leading-[18px] text-[#595959] border-y p-2 "
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

      <div className="flex mt-5 pb-4 justify-end pr-5 gap-x-2">
        <button
          onClick={() => previousPage()}
          disabled={!canPreviousPage}
          className="px-2 border rounded-sm "
        >
          <ArrowBackIosOutlinedIcon fontSize="small" />
        </button>

        <button className="border px-3 rounded" onClick={() => gotoPage(0)}>
          {" "}
          1{" "}
        </button>
        <button className="border px-3 rounded" onClick={() => gotoPage(1)}>
          {" "}
          2{" "}
        </button>
        <button className="border px-3 rounded" onClick={() => gotoPage(2)}>
          {" "}
          3{" "}
        </button>
        <button className="border px-3 rounded" onClick={() => gotoPage(3)}>
          {" "}
          4{" "}
        </button>
        <button className="border px-3 rounded" onClick={() => gotoPage(4)}>
          {" "}
          5{" "}
        </button>

        <button
          onClick={() => nextPage()}
          disabled={!canNextPage}
          className="px-2 border rounded-sm "
        >
          <ArrowForwardIosOutlinedIcon fontSize="small" />
        </button>

        <p className="font-medium text-sm leading-[30px] mr-[33px] text-[#262626] ">
          {dropdown2}
        </p>
      </div>
    </>
  );
};
