import React from "react";
import Image from "next/image";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import Link from "next/link";
import IconButton from "@mui/material/IconButton";
import MoreHorizIcon from "@mui/icons-material/MoreHoriz";
import pencil from "../assets/pencil.png";
import fileicon from "../assets/fileicon.png";
import recycle from "../assets/recycle.png";
import deleteicon from "../assets/blockvehicle.png";
import KeyboardArrowDownIcon from "@mui/icons-material/KeyboardArrowDown";

const ITEM_HEIGHT = 60;

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
        <KeyboardArrowDownIcon />
      </IconButton>
      <Menu
      className="rounded-lg mr-2 mt-9"
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
          },
        }}
      >
        <MenuItem onClick={handleClose} className="flex">
          <Image src={pencil} className="mr-2" alt="pencil" />
          User Profile
        </MenuItem>
        <MenuItem className="flex" onClick={handleClose}>
          <Image src={fileicon} className="mr-2 w-5 " alt="x" />
          Settings
        </MenuItem>
        <MenuItem className="flex" onClick={handleClose}>
          <Image src={recycle} className="mr-2 w-5 " alt="x" />
          Change Password
        </MenuItem>
        <MenuItem className="flex" onClick={handleClose}>
          System
        </MenuItem>
        <MenuItem className="flex" onClick={handleClose}>
          Configuration
        </MenuItem>
        <MenuItem className="flex" onClick={handleClose}>
          Help Documentation
        </MenuItem>

        <MenuItem
          className="flex"
          component={Link}
          href="/"
          onClick={handleClose}
        >
          <p className="text-[#DC4A41]">Log Out</p>
        </MenuItem>
      </Menu>
    </>
  );
}
