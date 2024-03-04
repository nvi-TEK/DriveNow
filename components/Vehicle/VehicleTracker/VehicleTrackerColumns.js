import React from "react";
import Image from "next/image";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import IconButton from "@mui/material/IconButton";
import MoreHorizIcon from "@mui/icons-material/MoreHoriz";
// import pencil from "../../assets/pencil.png";
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
  },
  {
    Header: "Location Updated",
    accessor: "location_updated",
  },
  {
    Header: "Device Status",
    accessor: "device_status",
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
