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
import viewmap from "../../../assets/viewmap.svg";

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
            width: "25ch",
            position: "sticky",
            marginRight: "30px",
            fontFamily: "Avenir",
          },
        }}
      >
        <MenuItem onClick={handleClose} className="flex items-center text-sm dark:text-white dark:hover:bg-dm-600">
          <Image src={viewmap} className="mr-2 w-6 dark:brightness-0 dark:invert" alt="map" />
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
    sortable: true,
  },
  {
    Header: "Car Registration",
    accessor: "car_registration",
  },
  {
    Header: "Driver Name",
    accessor: "driver_name",
    sortable: true,
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
            className={`text-base max-2xl:text-sm rounded-sm text-center px-1.5 py-px leading-4 font-medium ${
              props.value === "Active"
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
    Header: "Location Updated",
    accessor: "location_updated",
  },
  {
    Header: "Device Status",
    accessor: "device_status",
    Cell: (props) => {
      return (
        <div className="">
          <div
            className={`text-base max-2xl:text-sm rounded-sm text-center px-1.5 py-0.5 leading-4 font-medium inline-block ${
              props.value === "Online"
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
    Header: "Action",
    Cell: ({ original }) => (
      <>
        <LongMenu />
      </>
    ),
  },
];
