import React from "react";
import Image from "next/image";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import IconButton from "@mui/material/IconButton";
import MoreHorizIcon from "@mui/icons-material/MoreHoriz";
import refresh from "../../../assets/blue_recycle.png";
import fileicon from "../../../assets/fileicon.png";
// import recycle from "../../assets/recycle.png";
// import deleteicon from "../../assets/blockvehicle.png";
import viewmap from "../../../assets/viewmap.png";

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
        disableScrollLock= {true}
        PaperProps={{
          style: {
            maxHeight: ITEM_HEIGHT * 4.5,
            width: "25ch",
            position: "sticky",
            marginRight: "30px"
          },
        }}
      >
        <MenuItem onClick={handleClose} className="flex ">
          <Image src={viewmap} className="mr-2" alt="map" />
          View on map
        </MenuItem>
      </Menu>
    </>
  );
}

export const VEHICLETRACKERCOLUMNS = [
  {
    Header: "ID",
    accessor: "id",
  },
  {
    Header: "Car Registration",
    accessor: "car_registration",
  },
  {
    Header: "Driver Name",
    accessor: "driver_name",
  },
  {
    Header: "Car Speed",
    accessor: "car_speed",
  },
  {
    Header: "Engine Status",
    accessor: "engine_status",
    Cell: (props) => {
      return (
        <div className="flex items-center gap-x-1">
        <div className="">
        <Image src={refresh} alt="refresh icon" />
        </div>
        <div
          style={{
            color: props.value === "Active" ? "#0EA371" : "#DC4A41",
            backgroundColor: props.value === "Active" ? "#E7F6F1" : "#FBEDEC",
            borderRadius: "2px",
            textAlign: "center",
            paddingTop: "1px",
            paddingLeft: "6px",
            paddingRight: "6px",
            paddingBottom: "1px",
            fontSize: "12px",
            lineHeight: "16px",
            fontWeight: "500",
          }}
        >
          {props.value}
        </div></div>
      );
    }
  },
  {
    Header: "Location Updated",
    accessor: "location_updated",
  },
  {
    Header: "Device Status",
    accessor: "device_status",
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
            width: "50px"
          }}
        >
          {props.value}
        </div>
      );
    }
  },
  {
    Header: "Action",
    Cell: ({ original }) => (
      <>
        <LongMenu />
      </>
    ),
  },
];
