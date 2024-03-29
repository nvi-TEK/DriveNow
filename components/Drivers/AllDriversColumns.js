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
        disableScrollLock={true}
        PaperProps={{
          style: {
            maxHeight: ITEM_HEIGHT * 4.5,
            width: "24ch",
            position: "sticky",
            marginRight: "50px",
          },
        }}
      >
        <MenuItem
          onClick={handleClose}
          className="flex items-center text-[#595959] py-2 leading-[9.67px] font-normal text-sm   "
        >
          <Image src={user} className="mr-2 w-5" alt="people icon" />
          Driver Profile
        </MenuItem>
        <MenuItem
          className="flex items-center text-[#595959] py-2 leading-[9.67px] font-normal text-sm   "
          onClick={handleClose}
        >
          <Image src={pencil} className="mr-2 w-5 " alt="pencil" />
          Update Agreement
        </MenuItem>
        <MenuItem
          className="flex items-center text-[#595959] py-2 leading-[9.67px] font-normal text-sm   "
          onClick={handleClose}
        >
          <Image src={power} className="mr-2 w-5 " alt="power icon" />
          Turn Off Engine Control
        </MenuItem>
        <MenuItem
          className="flex items-center text-[#595959] py-2 leading-[9.67px] font-normal text-sm    "
          onClick={handleClose}
        >
          <Image src={recycle} className="mr-2 w-5 " alt="recycle icon" />
          Restore Engine Control
        </MenuItem>
        <MenuItem className="flex py-2 items-center" onClick={handleClose}>
          <Image src={bin} className="mr-2 w-5 " alt="bin icon" />
          <p className="text-[#DC4A41]  leading-[9.67px] font-normal text-sm ">
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
    Header: "Status",
    accessor: "status",
    Cell: (props) => {
      return (
        <div
          style={{
            color: props.value === "Online" ? "#0EA371" : "#DC4A41",
            backgroundColor: props.value === "Online" ? "#E7F6F1" : "#FBEDEC",
            borderRadius: "2px",
            textAlign: "center",
            paddingLeft: "2px",
            paddingRight: "2px",
            paddingTop: "2px",
            paddingBottom: "2px",
            fontSize: "12px",
            lineHeight: "16px",
            fontWeight: "500",
          }}
        >
          {props.value}
        </div>
      );
    }
  },
  {
    Header: "Agreed",
    accessor: "agreed",
    Cell: (props) => {
      return (
        <div
          style={{
            color: props.value === "Yes" ? "#0EA371" : "#DC4A41",
            backgroundColor: props.value === "Yes" ? "#E7F6F1" : "#FBEDEC",
            borderRadius: "2px",
            textAlign: "center",
            width: "50px",
            paddingTop: "2px",
            paddingBottom: "2px",
            fontSize: "12px",
            lineHeight: "16px",
            fontWeight: "500",
          }}
        >
          {props.value}
        </div>
      );
    }
  },
  {
    Header: "App Version",
    accessor: "app_version",
  },
  {
    Header: "Engine Control",
    accessor: "engine_control",
    Cell: (props) => {
      return (
        <div
          style={{
            color: props.value === "ON" ? "#0EA371" : "#DC4A41",
            backgroundColor: props.value === "ON" ? "#E7F6F1" : "#FBEDEC",
            borderRadius: "2px",
            textAlign: "center",
            width: "50px",
            paddingTop: "2px",
            paddingBottom: "2px",
            fontSize: "12px",
            lineHeight: "16px",
            fontWeight: "500",
          }}
        >
          {props.value}
        </div>
      );
    }
  },
  {
    Header: "Location Updated",
    accessor: "location_updated",
  },
  {
    Header: "Active Hours",
    accessor: "active_hours",
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
