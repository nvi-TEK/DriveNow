/* eslint-disable require-jsdoc */

import React, { useState } from "react";
import Image from "next/image";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import IconButton from "@mui/material/IconButton";
import Link from "next/link";
import Modal from "react-modal";
import tableaction from "../../assets/tableaction.png";
import approve from "../../assets/check.svg";
import details from "../../assets/viewdetails.svg";
import decline from "../../assets/declinex.svg";
import MoreHorizIcon from "@mui/icons-material/MoreHoriz";

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

  const [modalIsOpen, setModalIsOpen] = useState(false);

  const openModal = () => {
    setModalIsOpen(true);
  };

  const closeModal = () => {
    setModalIsOpen(false);
  };

  const customStyles = {
    content: {
      top: "50%",
      left: "60%",
      right: "20%",
      bottom: "auto",
      marginRight: "-80%",
      transform: "translate(-80%, -55%)",
      padding: "0",
      width: "308px",
      height: "183px",
      border: "0",
      borderRadius: "8px 8px 8px 8px",
      backgroundColor: "",
    },
    overlay: {
      backgroundColor: "#0000008F",
      zIndex: 1000,
    },
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
        <MoreHorizIcon className="dark:text-white" />{" "}
      </IconButton>
      <Menu
        id="long-menu"
        MenuListProps={{
          "aria-labelledby": "long-button",
        }}
        anchorEl={anchorEl}
        open={open}
        onClose={handleClose}
        disableScrollLock={true}
        PaperProps={{
          style: {
            maxHeight: ITEM_HEIGHT * 4.5,
            width: "25ch",
            position: "sticky",
            marginRight: "60px",
          },
        }}
      >
        <Link href={""}>
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
            isOpen={modalIsOpen}
            onRequestClose={closeModal}
            ariaHideApp={false}
            shouldCloseOnOverlayClick={false}
            overlayClassName=""
            style={customStyles}
          >
            <div>abc</div>
          </Modal>
        </Link>
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
            textAlign: "center",
            paddingTop: "2px",
            paddingBottom: "2px",
            lineHeight: "16px",
            fontWeight: "500",
          }}
        >
          {props.value}
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
