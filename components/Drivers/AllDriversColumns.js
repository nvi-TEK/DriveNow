import React from "react";
import Image from "next/image";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import IconButton from "@mui/material/IconButton";
import MoreHorizIcon from "@mui/icons-material/MoreHoriz";
import pencil from "../../assets/pencil.svg";
import user from "../../assets/user.svg";
import power from "../../assets/power.svg";
import recycle from "../../assets/recycle.svg";
import bin from "../../assets/bin.svg";

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
          className: "dark:bg-dm-700",
          style: {
            maxHeight: ITEM_HEIGHT * 4.5,
            width: "24ch",
            position: "sticky",
            marginRight: "50px",
            fontFamily: "Avenir",
          },
        }}
      >
        <MenuItem
          onClick={handleClose}
          className="flex items-center text-[#595959] dark:text-white dark:hover:bg-dm-600 py-1.5 leading-[9.67px] font-normal text-sm   "
        >
          <Image src={user} className="mr-2 w-5 dark:brightness-0 dark:invert" alt="people icon" />
          Driver Profile
        </MenuItem>
        <MenuItem
          className="flex items-center text-[#595959] dark:text-white dark:hover:bg-dm-600 py-1.5 leading-[9.67px] font-normal text-sm   "
          onClick={handleClose}
        >
          <Image src={pencil} className="mr-2 w-5 dark:brightness-0 dark:invert" alt="pencil" />
          Update Agreement
        </MenuItem>
        <MenuItem
          className="flex items-center text-[#595959] dark:text-white dark:hover:bg-dm-600 py-1.5 leading-[9.67px] font-normal text-sm   "
          onClick={handleClose}
        >
          <Image src={power} className="mr-2 w-7 dark:brightness-0 dark:invert" alt="power icon" />
          Turn Off Engine Control
        </MenuItem>
        <MenuItem
          className="flex items-center text-[#595959] dark:text-white dark:hover:bg-dm-600 py-1.5 leading-[9.67px] font-normal text-sm    "
          onClick={handleClose}
        >
          <Image src={recycle} className="mr-2 w-5 dark:brightness-0 dark:invert" alt="recycle icon" />
          Restore Engine Control
        </MenuItem>
        <MenuItem className="flex py-1.5 items-center dark:hover:bg-dm-600" onClick={handleClose}>
          <Image src={bin} className="mr-2 w-5 dark:brightness-0 dark:invert" alt="bin icon" />
          <p className="text-[#DC4A41] leading-[9.67px] font-normal text-sm ">
            Terminate
          </p>
        </MenuItem>
      </Menu>
    </>
  );
}

export const ALLDRIVERSCOLUMNS = [
  {
    Header: "ID",
    accessor: "id",
    width: 120,
    sortable: true,
  },
  {
    Header: "Full Name",
    accessor: "full_name",
    width: 800,
    sortable: true,
  },
  {
    Header: "Mobile Number",
    accessor: "mobile_number",
    width: 700,
    sortable: true,
  },
  {
    Header: "Status",
    accessor: "status", width:400,
    sortable: true,

    Cell: (props) => {
      return (
        <div
          className={`text-base max-2xl:text-sm rounded-sm text-center px-1.5 py-0.5 leading-4 font-medium inline-block ${
            props.value === "Online"
              ? "text-[#0EA371] dark:text-[#34D399] bg-[#E7F6F1] dark:bg-[#0EA37133]"
              : "text-[#DC4A41] dark:text-[#F87171] bg-[#FBEDEC] dark:bg-[#DC4A4133]"
          }`}
        >
          <p>{props.value}</p>
        </div>
      );
    },
  },
  {
    Header: "Agreed",
    accessor: "agreed",
    width: 300,
    sortable: true,
    Cell: (props) => {
      return (
        <div className="flex justify-center ">
        <div
        id="table40"
          className={`text-base max-2xl:text-sm rounded-sm text-center px-1.5 py-0.5 leading-4 font-medium inline-block ${
            props.value === "Yes"
              ? "text-[#0EA371] dark:text-[#34D399] bg-[#E7F6F1] dark:bg-[#0EA37133]"
              : "text-[#DC4A41] dark:text-[#F87171] bg-[#FBEDEC] dark:bg-[#DC4A4133]"
          }`}
        >
          {props.value}
        </div>
        </div>
      );
    },
  },
  {
    Header: "App Version",
    accessor: "app_version",
    width: 400,
  },
  {
    Header: "Engine Control",
    accessor: "engine_control",
    width: 391,
    sortable: true,
    Cell: (props) => {
      return (
        <div
          className={`text-sm inline-block max-2xl:text-xs rounded-sm text-center px-1.5 py-0.5 leading-4 font-medium ${
            props.value === "ON"
              ? "text-[#0EA371] dark:text-[#34D399] bg-[#E7F6F1] dark:bg-[#0EA37133]"
              : "text-[#DC4A41] dark:text-[#F87171] bg-[#FBEDEC] dark:bg-[#DC4A4133]"
          }`}
        >
          {props.value}
        </div>
      );
    },
  },
  {
    Header: "Location Updated",
    accessor: "location_updated",
    width: 350,
  },
  {
    Header: "Active Hours",
    accessor: "active_hours",
    width: 300,
  },
  {
    Header: "Action",
    width: 100,
    Cell: ({ original }) => (
      <>
        <LongMenu />{" "}
      </>
    ),
  },
];
