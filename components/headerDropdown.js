import React from "react";
import Image from "next/image";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import Link from "next/link";
import IconButton from "@mui/material/IconButton";
import MoreHorizIcon from "@mui/icons-material/MoreHoriz";
import user from "../assets/user.svg";
import settings from "../assets/settings.svg";
import lock from "../assets/lock.svg";
import fileicon from "../assets/fileicon.png";
import recycle from "../assets/recycle.png";
import deleteicon from "../assets/blockvehicle.png";
import KeyboardArrowDownIcon from "@mui/icons-material/KeyboardArrowDown";

const ITEM_HEIGHT = 80;

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
            width: "201px",
            position: "sticky",
            fontFamily: "Avenir LT Pro",
          },
        }}
      >
        <MenuItem
          onClick={handleClose}
          className="flex text-sm font-normal leading-[18px] text-[#262626] py-2.5 "
        >
          <Image src={user} className="mr-2 w-6" alt="pencil" />
          User Profile
        </MenuItem>
        <MenuItem
          className="flex text-sm font-normal leading-[18px] text-[#262626] py-2.5 "
          onClick={handleClose}
        >
          <Image src={settings} className="mr-2 w-6 " alt="x" />
          Settings
        </MenuItem>
        <MenuItem
          className="flex text-sm font-normal leading-[18px] text-[#262626] py-2.5 "
          onClick={handleClose}
        >
          <Image src={lock} className="mr-2 w-6 " alt="x" />
          Change Password
        </MenuItem>
        <MenuItem
          className="flex text-sm font-normal leading-[18px] text-[#262626] py-2.5 "
          onClick={handleClose}
        >
          System
        </MenuItem>
        <MenuItem
          className="flex text-sm font-normal leading-[18px] text-[#262626] py-2.5 "
          onClick={handleClose}
        >
          Configuration
        </MenuItem>
        <MenuItem
          className="flex text-sm font-normal leading-[18px] text-[#262626] py-2.5  "
          onClick={handleClose}
        >
          Help Documentation
        </MenuItem>

        <MenuItem
          className="flex text-sm font-normal text-[#DC4A41] leading-[18px] py-2.5 "
          component={Link}
          href="/"
          onClick={handleClose}
        >
          Log Out
        </MenuItem>
      </Menu>
    </>
  );
}
