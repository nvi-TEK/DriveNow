/* eslint-disable */

import React, { useMemo } from "react";
import {
  useTable,
  usePagination,
  useSortBy,
  useGlobalFilter,
  useRowSelect,
} from "react-table";
import CUSTOM_PUSH_DATA from "../../components/customPush/CUSTOM_PUSH_DATA.json";
import { CUSTOMPUSHCOLUMNS } from "../../components/customPush/CustomPushColumns";
import sortingarrows from "../../assets/chevrons-up-down.png";
import {
  Bubble,
  AgreedBubble,
  EngineControlBubble,
} from "../payment/PaymentBubbles";
import UnfoldMoreOutlinedIcon from "@mui/icons-material/UnfoldMoreOutlined";
import ArrowBackIosOutlinedIcon from "@mui/icons-material/ArrowBackIosOutlined";
import { CustomPushFilter } from "../../components/customPush/CustomPushFilter";
import ArrowForwardIosOutlinedIcon from "@mui/icons-material/ArrowForwardIosOutlined";
import { Checkbox } from "../Checkbox";

export const CustomPushTable = () => {
  const columns = useMemo(() => CUSTOMPUSHCOLUMNS, []);
  const data = useMemo(() => CUSTOM_PUSH_DATA, []);

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

  const { pageSize } = state;

  const dropdown = [
    <select
      value={pageSize}
      defaultValue={3}
      onChange={(e) => setPageSize(Number(e.target.value))}
      className="border h-[30px] shadow-[0px_1px_2px_0px_#1B283614] text-center border-[#D9D9D9] text-xs text-[#BFBFBF] rounded px-1 py-1 "
      placeholder=""
    >
      {[3, 10, 15, 20].map((pageSize) => (
        <option key={pageSize} value={pageSize}>
          {pageSize}
        </option>
      ))}
    </select>,
  ];

  const dropdown2 = [
    <select
      value={pageSize}
      defaultValue={3}
      onChange={(e) => setPageSize(Number(e.target.value))}
      className="border h-[30px] shadow-[0px_1px_2px_0px_#1B283614] text-center text-xs border-[#D9D9D9] rounded px-1 py-1 "
      aria-placeholder=""
    >
      {[3, 10, 15, 20].map((pageSize) => (
        <option key={pageSize} value={pageSize}>
          {pageSize} Items/Page
        </option>
        
      ))}
    </select>,
  ];

  const { globalFilter } = state;

  return (
    <>
      {/* number of entries dropdown and Search bar */}
      <div className="flex items-center mt-6 ml-[10px]">
        <p className="font-medium text-sm leading-[30px] mr-[33px] text-[#262626] ">
          Show {dropdown} entries
        </p>

        <CustomPushFilter filter={globalFilter} setFilter={setGlobalFilter} />
      </div>

      {/* Table */}
      <table {...getTableProps()} className="mt-7 w-full">
        <thead>
          {headerGroups.map((headerGroup) => (
            <tr {...headerGroup.getHeaderGroupProps()}>
              {headerGroup.headers.map((column) => (
                <th
                  {...column.getHeaderProps(column.getSortByToggleProps())}
                  className="text-left text-xs font-normal leading-[18px] h-[48px] pl-2 bg-[#FAFAFA] "
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
                        className="text-[#595959] pl-2 text-xs font-normal leading-[18px] border-[#E6E6E6] border-y h-[48px] "
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

      <div className="flex mt-5 justify-end gap-x-2">
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

        <p className="font-medium text-sm leading-[30px] text-[#262626] ">
          {dropdown2}
        </p>
      </div>
    </>
  );
};
