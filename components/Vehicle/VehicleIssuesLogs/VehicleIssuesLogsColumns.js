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
            width: "28ch",
            position: "sticky",
            marginRight: "30px"
          },
        }}
      >
        <MenuItem onClick={handleClose} className="flex">
          <Image src={fileicon} className="mr-2" alt="checkmark" />
          Raise an Expense
        </MenuItem>
      </Menu>
    </>
  );
}

export const VEHICLEISSUESLOGSCOLUMNS = [
  {
    Header: "ID",
    accessor: "id",
  },
  {
    Header: "Car Registration",
    accessor: "car_registration",
  },
  {
    Header: "Category",
    accessor: "category",
  },
  {
    Header: "Amount",
    accessor: "amount",
  },
  {
    Header: "Created By",
    accessor: "created_by",
  },
  {
    Header: "Status",
    accessor: "status",
  },
  {
    Header: "Description",
    accessor: "description",
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
