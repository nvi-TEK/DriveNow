import React from "react";
import Image from "next/image";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import IconButton from "@mui/material/IconButton";
import MoreHorizIcon from "@mui/icons-material/MoreHoriz";
import pencil from "../../assets/pencil.png";
import fileicon from "../../assets/fileicon.png";
import recycle from "../../assets/recycle.png";
import deleteicon from "../../assets/blockvehicle.png";

const ITEM_HEIGHT = 56;

export default function LongMenu() {
  const [anchorEl, setAnchorEl] = React.useState(null);
  const open = Boolean(anchorEl);
  const handleClick = (event) => {
    setAnchorEl(event.currentTarget);
  };
  const handleClose = () => {
    setAnchorEl(null);
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
        PaperProps={{
          style: {
            maxHeight: ITEM_HEIGHT * 4.5,
            width: "20ch",
            position: "absolute",
          },
        }}
      >
        <MenuItem onClick={handleClose} className="flex">
          <Image src={pencil} className="mr-2" alt="checkmark" />
          Edit Vehicle{" "}
        </MenuItem>
        <MenuItem className="flex" onClick={handleClose}>
          <Image src={fileicon} className="mr-2 w-5 " alt="x" />
          Update Sim{" "}
        </MenuItem>
        <MenuItem className="flex" onClick={handleClose}>
          <Image src={recycle} className="mr-2 w-5 " alt="x" />
          Update Status{" "}
        </MenuItem>
        <MenuItem className="flex" onClick={handleClose}>
          <Image src={deleteicon} className="mr-2 w-5 " alt="off icon" />
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
