/* eslint-disable require-jsdoc */

import React, { useState } from "react";
import { useTheme } from "next-themes";
import Image from "next/image";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import IconButton from "@mui/material/IconButton";
import Link from "next/link";
import Box from "@mui/material/Box";
import Modal from "@mui/material/Modal";
import tableaction from "../../assets/tableaction.png";
import approve from "../../assets/check.svg";
import details from "../../assets/viewdetails.svg";
import decline from "../../assets/declinex.svg";
import MoreHorizIcon from "@mui/icons-material/MoreHoriz";
import MoreDetails from "./MoreDetails";

const ITEM_HEIGHT = 48;

export default function LongMenu() {
  const { resolvedTheme } = useTheme();
  const [anchorEl, setAnchorEl] = React.useState(null);
  const open = Boolean(anchorEl);
  const handleClick = (event) => {
    setAnchorEl(event.currentTarget);
  };
  const handleClose = () => {
    setAnchorEl(null);
  };

  const [modalIsOpen, setModalIsOpen] = React.useState(false);
  const openModal = () => {
    setModalIsOpen(true);
  };
  const closeModal = () => {
    setModalIsOpen(false);
  };

  const style = {
    position: "absolute",
    top: "50%",
    left: "50%",
    transform: "translate(-50%, -50%)",
    // height:605,
    // width: 929,
    borderRadius: "8px",
    bgcolor: resolvedTheme === "dark" ? "#2A2A2A" : "background.paper",
    border: "0px",
    paddingLeft: "24px",
    paddingRight: "24px",
    zIndex: 1,
  };

  return (
    <>
      <IconButton
        aria-label="more"
        id="long-button"
        aria-controls={open ? "long-menu" : undefined}
        aria-expanded={open ? "true" : undefined}
        aria-haspopup="true"
        onClick={handleClick}
      >
        <MoreHorizIcon className="dark:text-white" />
      </IconButton>
      <Menu
        id="long-menu"
        MenuListProps={{
          "aria-labelledby": "long-button",
        }}
        anchorEl={anchorEl}
        open={open}
        onClose={handleClose}
        onClick={handleClose}
        keepMounted
        disableScrollLock={true}
        PaperProps={{
          className: "dark:bg-dm-700",
          style: {
            maxHeight: ITEM_HEIGHT * 4,
            width: "200px",
            position: "sticky",
            marginRight: "76px",
            fontFamily: "Avenir",
          },
        }}
      >
        <Box>
          <MenuItem
            onClick={openModal}
            component={Link}
            href={""}
            className="py-2 flex dark:text-white dark:hover:bg-dm-600"
          >
            <Image src={details} className="mr-2 dark:brightness-0 dark:invert" alt="checkmark" />
            View Details
          </MenuItem>
          <Modal
            open={modalIsOpen}
            onClose={closeModal}
            aria-labelledby="modal-modal-title"
            aria-describedby="modal-modal-description"
            disableScrollLock={true}
          >
            <Box sx={style}>
              <MoreDetails
                expenseType="Part Replacement Expense"
                date="3 February, 2023"
                time="01:09 pm"
                costCenter="Fleet"
                expenseCategory="Fleet Management"
                expenseLine="Part Replacement"
                carRegistrationNumber="GT 9909-22"
                requestedBy="Benjamin@feenix.technology"
                approvedBy="Geoffrey@feenix.technology"
                bankName="GT Bank"
                accountNumber={30305546454509}
                paidTo="Vendor"
                expenseStatus="Done"
                description="For fixing David Mantey’s side mirror covers which were stolen while the vehicle was parked at the office."
                amount={4000.0}
              />
            </Box>
          </Modal>
        </Box>
        <MenuItem onClick={handleClose} className="flex py-2 dark:text-white dark:hover:bg-dm-600">
          <Image src={approve} className="mr-2 dark:brightness-0 dark:invert" alt="checkmark" />
          Approve
        </MenuItem>
        <MenuItem
          className="text-[#DC4A41] text-sm font-normal py-2 leading-[18px] dark:hover:bg-dm-600"
          onClick={handleClose}
        >
          <Image src={decline} className="mr-2 dark:brightness-0 dark:invert" alt="x" />
          <p className="text-[#DC4A41] font-normal">Decline</p>
        </MenuItem>
      </Menu>
    </>
  );
}

export const EXPENSECOLUMNS = [
  {
    Header: "ID",
    accessor: "id",
    width: 60,
  },
  {
    Header: "Category",
    accessor: "category",
    width: 380,
    sortable: true,
  },

  {
    Header: "Paid to",
    accessor: "paid_to",
    sortable: true,
    width: 290,
  },
  {
    Header: "Amount",
    accessor: "amount",
    sortable: true,
    width: 180,
  },
  {
    Header: "Approved By",
    accessor: "approved_by",
    sortable: true,
    width: 340,
  },
  {
    Header: "Requested By",
    accessor: "requested_by",
    width: 350,
    sortable: true,
  },
  {
    Header: "Dated on",
    width: 180,
    accessor: "dated_on",
  },
  {
    Header: "Status",
    accessor: "status",
    sortable: true,
    Cell: (props) => {
      return (
        <div className="flex justify-center ">
          <div
            id="expense-table-status"
            className={`rounded-sm py-[3px] px-1.5 leading-4 font-medium ${
              props.value === "Paid"
                ? "text-[#0EA371] dark:text-[#34D399] bg-[#E7F6F1] dark:bg-[#0EA37133]"
                : props.value === "Declined"
                ? "text-[#DC4A41] dark:text-[#F87171] bg-[#FBEDEC] dark:bg-[#DC4A4133]"
                : "text-[#E8B123] dark:text-[#FBBF24] bg-[#FBF6E9] dark:bg-[#E8B12333]"
            }`}
          >
            <p className="">{props.value}</p>
          </div>
        </div>
      );
    },
  },
  {
    Header: "Description",
    accessor: "description",
    width: 350,
  },
  {
    Header: "Action",
    Cell: ({ original }) => (
      <>
        <LongMenu />{" "}
      </>
    ),
  },
];
