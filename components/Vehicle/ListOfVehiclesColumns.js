/* eslint-disable react/no-unescaped-entities */
/* eslint-disable require-jsdoc */
import React, { useState } from "react";
import { useTheme } from "next-themes";
import Image from "next/image";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import IconButton from "@mui/material/IconButton";
import MoreHorizIcon from "@mui/icons-material/MoreHoriz";
import pencil from "../../assets/pencil.svg";
import file from "../../assets/file.svg";
import recycle from "../../assets/recycle.svg";
import modalclose from "../../assets/x.svg";
import vehicle from "../../assets/menuvehicle.svg";
import Box from "@mui/material/Box";
import Modal from "@mui/material/Modal";
import UpdateStatusDropdown from "../Dropdown";

const ITEM_HEIGHT = 96;

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

  const [modalIsOpen1, setModalIsOpen1] = React.useState(false);
  const openModal1 = () => {
    setModalIsOpen1(true);
  };
  const closeModal1 = () => {
    setModalIsOpen1(false);
  };

  const style = {
    position: "absolute",
    top: "50%",
    left: "50%",
    transform: "translate(-50%, -50%)",
    width: 306,
    height: 181,
    borderRadius: "8px",
    bgcolor: resolvedTheme === "dark" ? "#2A2A2A" : "background.paper",
    border: "0px",
    paddingLeft: "24px",
    paddingRight: "24px",
    zIndex: 1,
  };

  const style1 = {
    position: "absolute",
    top: "50%",
    left: "50%",
    transform: "translate(-50%, -50%)",
    width: 306,
    height: 181,
    borderRadius: "8px",
    bgcolor: resolvedTheme === "dark" ? "#2A2A2A" : "background.paper",
    border: "0px",
    paddingLeft: "24px",
    paddingRight: "24px",
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
        <MenuItem
          onClick={handleClose}
          className="flex font-normal text-[#595959] dark:text-white dark:hover:bg-dm-600 text-sm leading-[18px] py-2"
        >
          <Image src={pencil} className="mr-2 w-6 dark:brightness-0 dark:invert" alt="checkmark" />
          Edit Vehicle{" "}
        </MenuItem>

        {/* update Sim */}
        <Box>
          <MenuItem
            onClick={openModal}
            className="flex font-normal text-[#595959] dark:text-white dark:hover:bg-dm-600 text-sm leading-[18px] py-2"
          >
            <Image src={file} className="mr-2 w-6 dark:brightness-0 dark:invert" alt="x" />
            Update Sim
          </MenuItem>
          <Modal
            open={modalIsOpen}
            onClose={closeModal}
            aria-labelledby="modal-modal-title"
            aria-describedby="modal-modal-description"
            disableScrollLock={true}
          >
            <Box sx={style}>
              <div className="flex mt-[18px] items-center justify-between">
                <p className="pl- dark:text-white">Update Sim for GT 9202-22</p>
                <Image
                  src={modalclose}
                  alt="close button"
                  className="cursor-pointer "
                  onClick={closeModal}
                />
              </div>
              <div className="mt-5">
                <label
                  htmlFor="simNumber"
                  className="block mb-1 text-sm font-normal text-[#262626] dark:text-white"
                >
                  <span className="text-[#DC4A41] ">*</span> Sim Number:
                </label>
                <input
                  type="text"
                  id="amount"
                  className="border border-[#D9D9D9] dark:border-0 dark:bg-dm-600 placeholder-[#BFBFBF] dark:placeholder-dm-300 shadow-[0px_1px_2px_0px_#1B283614] text-gray-900 dark:text-white text-sm rounded block w-full h-[32px]"
                  placeholder="Enter"
                />
              </div>
              <div className="flex justify-end mt-4 gap-x-4 items-center ">
                <button
                  onClick={closeModal}
                  className="h-[28px] px-3 text-[#595959] dark:text-white dark:hover:bg-dm-600 text-sm leading-[18px] font-normal border-0 cursor-pointer"
                >
                  Cancel
                </button>
                <button className="h-[28px] px-3 bg-[#007AF5] text-sm text-[#FFFFFF] rounded-[4px] cursor-pointer">
                  Update
                </button>
              </div>
            </Box>
          </Modal>
        </Box>

        {/* update status */}
        <div>
          <MenuItem
            className="flex font-normal text-[#595959] dark:text-white dark:hover:bg-dm-600 text-sm leading-[18px] py-2"
            onClick={openModal1}
          >
            <Image src={recycle} className="mr-2 w-6 dark:brightness-0 dark:invert" alt="x" />
            Update Status{" "}
          </MenuItem>
          <Modal
            open={modalIsOpen1}
            onClose={closeModal1}
            aria-labelledby="modal-modal-title"
            aria-describedby="modal-modal-description"
            disableScrollLock={true}
          >
            <Box sx={style1}>
              <div className="flex mt-[18px] items-center justify-between">
                <p className="pl- dark:text-white">Update Status of GT 9202-22</p>
                <Image
                  src={modalclose}
                  alt="close button"
                  className="cursor-pointer "
                  onClick={closeModal1}
                />
              </div>
              <div className="mt-5">
                <label
                  htmlFor="simNumber"
                  className="block mb-1 text-sm font-normal text-[#262626] dark:text-white"
                >
                  <span className="text-[#DC4A41] ">*</span> Status of Vehicle
                </label>
                <UpdateStatusDropdown />
              </div>
              <div className="flex justify-end mt-4 gap-x-4 items-center ">
                <button
                  onClick={closeModal1}
                  className="h-[28px] px-3 text-[#595959] dark:text-white dark:hover:bg-dm-600 text-sm leading-[18px] font-normal border-0 cursor-pointer"
                >
                  Cancel
                </button>
                <button className="h-[28px] px-3 bg-[#007AF5] text-sm text-[#FFFFFF] rounded-[4px] cursor-pointer">
                  Update
                </button>
              </div>
            </Box>
          </Modal>
        </div>
        <MenuItem
          className="flex font-normal items-center text-[#595959] dark:text-white dark:hover:bg-dm-600 text-sm leading-[18px] py-2"
          onClick={handleClose}
        >
          <Image src={vehicle} className="mr-2 w-6 dark:brightness-0 dark:invert" alt="off icon" />
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
