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

const ITEM_HEIGHT = 86;


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
        <MoreHorizIcon />
      </IconButton>
      <Menu
        id="long-menu"
        MenuListProps={{
          "aria-labelledby": "long-button",
        }}
        anchorEl={anchorEl}
        open={open}
        onClose={handleClose}
        disableScrollLock= {true}
        PaperProps={{
          style: {
            maxHeight: ITEM_HEIGHT * 4,
            width: "25ch",
            position: "sticky",
            marginRight: "100px"
          },
        }}
      >
        <MenuItem onClick={handleClose} className="flex py-2.5">
          <Image src={pencil} className="mr-2 w-6" alt="checkmark" />
          Edit Vehicle{" "}
        </MenuItem>
        <Link href={""}>
          <MenuItem onClick={openModal} component={Link}
          href={""} className="flex py-2.5" >
          
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
            <div className="w-[306px] h-[181px] rounded-lg bg-white">
              <section className="flex items-center h-[50px] rounded-t-lg">
                <p className="mr-auto text-black font-bold ml-[20px]">
                  Update Sim for GT 9999
                </p>
                <div
                  onClick={closeModal}
                  className="flex cursor-pointer rounded-full w-[20px] h-[20px] mr-4"
                >
                  <Image
                    className=" self-center ml-[4.5px]"
                    src={modalclose}
                    width={10}
                    alt={"close button"}
                  />
                </div>
              </section>

              <section>
                <div className="mt-2 px-6  ">
                  <label
                    htmlFor="simNumber"
                    className="block mb-1 font-normal leading-[18px] text-[#262626] text-sm"
                  >
                    <span className="text-[#DC4A41] ">*</span>Sim Number:
                  </label>
                  <input
                    type="text"
                    id="simNumber"
                    className="border border-gray-300 text-gray-900 text-sm rounded block w-full p-2"
                    placeholder="Enter"
                    // value={values.simNumber}
                    // onChange={handleChange}
                  />
                  
                </div>
              </section>
              <section className="flex justify-end mt-4 pr-6" >
                <Link href={""}>
                  <button className="rounded-[4px] px-4 py-1.5 text-[14px] text-[#595959] font-normal leading-[18px] bg-[#FAFAFA] ">
                    Cancel
                  </button>
                </Link>
                <Link href={""}>
                  <button className="rounded-[4px] ml-4 px-4 py-1.5 text-[14px] text-white font-normal bg-[#007AF5] leading-[18px]">
                    Update
                  </button>
                </Link>
              </section>
            </div>
          </Modal>
        </Link>

        <MenuItem className="flex py-2.5" onClick={handleClose}>
          <Image src={recycle} className="mr-2 w-6 " alt="x" />
          Update Status{" "}
        </MenuItem>
        <MenuItem className="flex py-2.5" onClick={handleClose}>
          <Image src={vehicle} className="mr-2 w-6 " alt="off icon" />
          <p className="text-[#DC4A41]">Delete</p>{" "}
        </MenuItem>
      </Menu>
    </>
  );
}

export const LISTOFVEHICLESCOLUMNS = [
  {
    Header: "ID",
    accessor: "id",
  },
  {
    Header: "Car Registration",
    accessor: "car_registration",
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
