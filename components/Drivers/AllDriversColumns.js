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
          className="flex items-center text-[#595959] py-1.5 leading-[9.67px] font-normal text-sm   "
        >
          <Image src={user} className="mr-2 w-5" alt="people icon" />
          Driver Profile
        </MenuItem>
        <MenuItem
          className="flex items-center text-[#595959] py-1.5 leading-[9.67px] font-normal text-sm   "
          onClick={handleClose}
        >
          <Image src={pencil} className="mr-2 w-5 " alt="pencil" />
          Update Agreement
        </MenuItem>
        <MenuItem
          className="flex items-center text-[#595959] py-1.5 leading-[9.67px] font-normal text-sm   "
          onClick={handleClose}
        >
          <Image src={power} className="mr-2 w-7 " alt="power icon" />
          Turn Off Engine Control
        </MenuItem>
        <MenuItem
          className="flex items-center text-[#595959] py-1.5 leading-[9.67px] font-normal text-sm    "
          onClick={handleClose}
        >
          <Image src={recycle} className="mr-2 w-5 " alt="recycle icon" />
          Restore Engine Control
        </MenuItem>
        <MenuItem className="flex py-1.5 items-center" onClick={handleClose}>
          <Image src={bin} className="mr-2 w-5 " alt="bin icon" />
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
          className="text-base max-2xl:text-sm"
          style={{
            color: props.value === "Online" ? "#0EA371" : "#DC4A41",
            backgroundColor: props.value === "Online" ? "#E7F6F1" : "#FBEDEC",
            borderRadius: "2px",
            textAlign: "center",
            paddingLeft: "6px",
            paddingRight: "6px",
            paddingTop: "2px",
            paddingBottom: "2px",
            lineHeight: "16px",
            fontWeight: "500",
            display: "inline-block",
          }}
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
          className="text-base max-2xl:text-sm"
          style={{
            color: props.value === "Yes" ? "#0EA371" : "#DC4A41",
            backgroundColor: props.value === "Yes" ? "#E7F6F1" : "#FBEDEC",
            borderRadius: "2px",
            textAlign: "center",
            paddingTop: "2px",
            paddingBottom: "2px",
            paddingLeft: "6px",
            paddingRight: "6px",
            lineHeight: "16px",
            fontWeight: "500",
            display: "inline-block"
          }}
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
          className="text-sm inline-block max-2xl:text-xs"
          style={{
            color: props.value === "ON" ? "#0EA371" : "#DC4A41",
            backgroundColor: props.value === "ON" ? "#E7F6F1" : "#FBEDEC",
            borderRadius: "2px",
            textAlign: "center",
            paddingTop: "2px",
            paddingBottom: "2px",
            paddingLeft: "6px",
            paddingRight: "6px",
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
