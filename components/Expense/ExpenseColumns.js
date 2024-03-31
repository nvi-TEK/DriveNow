/* eslint-disable require-jsdoc */

import React, { useState } from "react";
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
    bgcolor: "background.paper",
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
            className="py-2 flex"
          >
            <Image src={details} className="mr-2" alt="checkmark" />
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
        <MenuItem onClick={handleClose} className="flex py-2">
          <Image src={approve} className="mr-2" alt="checkmark" />
          Approve
        </MenuItem>
        <MenuItem
          className="text-[#DC4A41] text-sm font-normal py-2 leading-[18px]"
          onClick={handleClose}
        >
          <Image src={decline} className="mr-2" alt="x" />
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
            style={{
              color:
                props.value === "Paid"
                  ? "#0EA371"
                  : props.value === "Declined"
                  ? "#DC4A41"
                  : "#E8B123",
              backgroundColor:
                props.value === "Paid"
                  ? "#E7F6F1"
                  : props.value === "Declined"
                  ? "#FBEDEC"
                  : "#FBF6E9",
              borderRadius: "2px",
              paddingTop: "3px",
              paddingBottom: "3px",
              paddingLeft: "6px",
              paddingRight: "6px",
              lineHeight: "16px",
              fontWeight: "500",
            }}
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
