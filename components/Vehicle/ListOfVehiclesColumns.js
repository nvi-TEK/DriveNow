/* eslint-disable react/no-unescaped-entities */
/* eslint-disable require-jsdoc */
import React, { useState } from "react";
import Image from "next/image";
import Link from "next/link";
import Menu from "@mui/material/Menu";
import Modal from "react-modal";
import MenuItem from "@mui/material/MenuItem";
import IconButton from "@mui/material/IconButton";
import MoreHorizIcon from "@mui/icons-material/MoreHoriz";
import pencil from "../../assets/pencil.svg";
import file from "../../assets/file.svg";
import recycle from "../../assets/recycle.svg";
import modalclose from "../../assets/modalclose.png";
import vehicle from "../../assets/menuvehicle.svg";

const ITEM_HEIGHT = 96;

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
        <MenuItem
          onClick={handleClose}
          className="flex font-normal text-[#595959] text-sm leading-[18px] py-2"
        >
          <Image src={pencil} className="mr-2 w-6" alt="checkmark" />
          Edit Vehicle{" "}
        </MenuItem>

        <MenuItem
          onClick={openModal}
          component={Link}
          href={""}
          className="flex font-normal text-[#595959] text-sm leading-[18px] py-2"
        >
          <Image src={file} className="mr-2 w-6 " alt="x" />
          Update Sim
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

        <MenuItem
          className="flex font-normal text-[#595959] text-sm leading-[18px] py-2"
          onClick={handleClose}
        >
          <Image src={recycle} className="mr-2 w-6 " alt="x" />
          Update Status{" "}
        </MenuItem>
        <MenuItem
          className="flex font-normal items-center text-[#595959] text-sm leading-[18px] py-2"
          onClick={handleClose}
        >
          <Image src={vehicle} className="mr-2 w-6 " alt="off icon" />
          <p className="text-[#DC4A41] text-sm">Delete</p>{" "}
        </MenuItem>
      </Menu>
    </>
  );
}

export const LISTOFVEHICLESCOLUMNS = [
  {
    Header: "ID",
    accessor: "id",
    sortable: true,
  },
  {
    Header: "Car Registration",
    accessor: "car_registration",
    sortable: true,
  },
  {
    Header: "Car Make Model",
    accessor: "car_make_model",
  },
  {
    Header: "Supplier",
    accessor: "supplier",
  },
  {
    Header: "Driver",
    accessor: "driver",
  },
  {
    Header: "Status",
    accessor: "status",
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
