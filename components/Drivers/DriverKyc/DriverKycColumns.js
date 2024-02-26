import React from "react";
import Image from "next/image";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import IconButton from "@mui/material/IconButton";
import MoreHorizIcon from "@mui/icons-material/MoreHoriz";
import pencil from "../../../assets/pencil.png";
import fileicon from "../../../assets/fileicon.png";
import recycle from "../../../assets/recycle.png";
import deleteicon from "../../../assets/blockvehicle.png";

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
          <Image src={pencil} className="mr-2" alt="people icon" />
          Driver Profile
        </MenuItem>
        <MenuItem className="flex" onClick={handleClose}>
          <Image src={fileicon} className="mr-2 w-5 " alt="pencil" />
          Update Agreement
        </MenuItem>
        <MenuItem className="flex" onClick={handleClose}>
          <Image src={recycle} className="mr-2 w-5 " alt="power icon" />
          Turn Off Engine Control
        </MenuItem>
        <MenuItem className="flex" onClick={handleClose}>
          <Image src={recycle} className="mr-2 w-5 " alt="recycle icon" />
          Restore Engine Control
        </MenuItem>
        <MenuItem className="flex" onClick={handleClose}>
          <Image src={deleteicon} className="mr-2 w-5 " alt="bin icon" />
          <p className="text-[#DC4A41]">Terminate</p>
        </MenuItem>
      </Menu>
    </>
  );
}

export const DRIVERKYCCOLUMNS = [
  {
    Header: "ID",
    accessor: "id",
  },
  {
    Header: "Full Name",
    accessor: "full_name",
  },
  {
    Header: "Mobile Number",
    accessor: "mobile_number",
  },
  {
    Header: "Email",
    accessor: "email",
  },
  {
    Header: "App Version",
    accessor: "app_version",
  },
  {
    Header: "Engine Control",
    accessor: "engine_control",
  },
  {
    Header: "Registration Date",
    accessor: "registration_date",
  },
  {
    Header: "Documents Uploaded",
    accessor: "documents_uploaded",
  },
  {
    Header: "Date Approved",
    accessor: "date_approved",
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
