import React from "react";
import Image from "next/image";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import IconButton from "@mui/material/IconButton";
import driverprofile from "../../assets/users.png";
import reassign from "../../assets/pencil.png";
import invoice from "../../assets/invoice.png";
import restore from "../../assets/recycle.png";
import off from "../../assets/offbutton.png";
import block from "../../assets/blockvehicle.png";
import MoreHorizIcon from "@mui/icons-material/MoreHoriz";

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
          <Image src={driverprofile} className="mr-2" alt="checkmark" />
          Driver Profile
        </MenuItem>
        <MenuItem
          className="flex"
          onClick={handleClose}
        >
          <Image src={reassign} className="mr-2 w-5 " alt="x" />
          Re-assign Vehicle
        </MenuItem>
        <MenuItem
          className="flex"
          onClick={handleClose}
        >
          <Image src={invoice} className="mr-2 w-5 " alt="x" />
          Invoice History
        </MenuItem>
        <MenuItem
          className="flex"
          onClick={handleClose}
        >
          <Image src={off} className="mr-2 w-5 " alt="off icon" />
          Turn Off Engine Control
        </MenuItem>
        <MenuItem
          className="flex"
          onClick={handleClose}
        >
          <Image src={restore} className="mr-2 w-10 " alt="x" />
          Restore Engine Control
        </MenuItem>
        <MenuItem
          className="text-[#DC4A41] flex text-sm font-normal leading-[18px]"
          onClick={handleClose}
        >
          <Image src={block} className="mr-2 w-5 " alt="x" />
          <p className="text-[#DC4A41] font-normal">Block Vehicle</p>
        </MenuItem>
      </Menu>
    </>
  );
}

export const COLUMNS = [
  {
    Header: "ID",
    accessor: "id",
  },
  {
    Header: "Full Name",
    accessor: "full_name",
  },
  {
    Header: "Deposit Amount",
    accessor: "deposit_amount",
  },
  {
    Header: "Vehicle Repayment",
    accessor: "vehicle_repayment",
  },
  {
    Header: "Additional Charges",
    accessor: "additional_charges",
  },
  {
    Header: "Amount Received",
    accessor: "amount_received",
  },
  {
    Header: "Engine Control",
    accessor: "engine_control",
  },
  {
    Header: "Engine Status",
    accessor: "engine_status",
  },
  {
    Header: "Engine Status Updated",
    accessor: "engine_status_updated",
  },
  {
    Header: "Completed Weeks (Invoices)",
    accessor: "completed_weeks",
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
