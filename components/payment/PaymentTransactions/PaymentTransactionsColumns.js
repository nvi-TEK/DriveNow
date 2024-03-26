import React from "react";
import Image from "next/image";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import IconButton from "@mui/material/IconButton";
import user from "../../../assets/user.svg";
import pencil from "../../../assets/pencil.svg";
import power from "../../../assets/power.svg";
import recycle from "../../../assets/recycle.svg";
import vehicle from "../../../assets/menuvehicle.svg";
import fileicon from "../../../assets/file.svg";
import refresh from "../../../assets/bluerecycle.svg";
import MoreHorizIcon from "@mui/icons-material/MoreHoriz";

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
            width: "195px",
            position: "sticky",
            marginRight: "55px",
          },
        }}
      >
        <MenuItem
          onClick={handleClose}
          className="flex text-[#595959] py-1 font-normal leading-3 text-xs"
        >
          <Image src={user} className="mr-2 w-5" alt="checkmark" />
          Driver Profile
        </MenuItem>
        <MenuItem
          className="flex text-[#595959] py-1 font-normal leading-3 text-xs"
          onClick={handleClose}
        >
          <Image src={pencil} className="mr-2 w-5 " alt="x" />
          Re-assign Vehicle
        </MenuItem>
        <MenuItem
          className="flex text-[#595959] py-1 font-normal leading-3 text-xs"
          onClick={handleClose}
        >
          <Image src={fileicon} className="mr-2 w-5 " alt="x" />
          Invoice History
        </MenuItem>
        <MenuItem
          className="flex text-[#595959] py-1 font-normal leading-3 text-xs"
          onClick={handleClose}
        >
          <Image src={power} className="mr-2 w-5 " alt="off icon" />
          Turn Off Engine Control
        </MenuItem>
        <MenuItem
          className="flex text-[#595959] py-1 font-normal leading-3 text-xs"
          onClick={handleClose}
        >
          <Image src={recycle} className="mr-2 w-5 " alt="x" />
          Restore Engine Control
        </MenuItem>
        <MenuItem
          className="text-[#DC4A41] flex py-1 font-normal leading-3 text-xs"
          onClick={handleClose}
        >
          <Image src={vehicle} className="mr-2 w-5 " alt="x" />
          <p className="text-[#DC4A41] text-xs font-normal">Block Vehicle</p>
        </MenuItem>
      </Menu>
    </>
  );
}

export const PAYMENTTRANSACTIONSCOLUMNS = [
  {
    Header: "ID",
    accessor: "id",
    width: 100,
  },
  {
    Header: "Transaction ID",
    accessor: "transaction_id",
    width: 200,
  },
  {
    Header: "Full Name",
    accessor: "full_name",
    sortable: true,

    width: 480,
  },
  {
    Header: "Amount Paid",
    accessor: "amount_paid",
    sortable: true,

    width: 260,
  },
  {
    Header: "Previous Payment",
    accessor: "previous_payment",
    sortable: true,

    width: 380,
  },
  {
    Header: "Next Due Payment",
    accessor: "next_due_payment",
    sortable: true,

    width: 220,
  },
  {
    Header: "Total Additional Charges",
    accessor: "total_additional_charges",
    width: 230,
  },
  {
    Header: "Date",
    accessor: "date",
    sortable: true,

    width: 230,
  },
  {
    Header: "Action",
    Cell: ({}) => (
      <>
        <LongMenu />
      </>
    ),
  },
];
