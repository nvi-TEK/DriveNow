import React from "react";
import Image from "next/image";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import IconButton from "@mui/material/IconButton";
import user from "../../assets/user.svg";
import pencil from "../../assets/pencil.svg";
import reassign from "../../assets/pencil.png";
import power from "../../assets/power.svg";
import recycle from "../../assets/payment-recycle.svg";
import vehicle from "../../assets/menuvehicle.svg";
import fileicon from "../../assets/file.svg";
import refresh from "../../assets/bluerecycle.svg";
import MoreHorizIcon from "@mui/icons-material/MoreHoriz";

const ITEM_HEIGHT = 96;

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
            width: "195px",
            position: "sticky",
            marginRight: "55px",
            fontFamily: "Avenir",
          },
        }}
      >
        <MenuItem
          onClick={handleClose}
          className="flex text-[#595959] dark:text-white dark:hover:bg-dm-600 py-1 font-normal leading-3 text-xs"
        >
          <Image src={user} className="mr-2 w-5 dark:brightness-0 dark:invert" alt="checkmark" />
          Driver Profile
        </MenuItem>
        <MenuItem
          className="flex text-[#595959] dark:text-white dark:hover:bg-dm-600 py-1 font-normal leading-3 text-xs"
          onClick={handleClose}
        >
          <Image src={pencil} className="mr-2 w-5 dark:brightness-0 dark:invert" alt="x" />
          Re-assign Vehicle
        </MenuItem>
        <MenuItem
          className="flex text-[#595959] dark:text-white dark:hover:bg-dm-600 py-1 font-normal leading-3 text-xs"
          onClick={handleClose}
        >
          <Image src={fileicon} className="mr-2 w-5 dark:brightness-0 dark:invert" alt="x" />
          Invoice History
        </MenuItem>
        <MenuItem
          className="flex text-[#595959] dark:text-white dark:hover:bg-dm-600 py-1 font-normal leading-3 text-xs"
          onClick={handleClose}
        >
          <Image src={power} className="mr-2 w-5 dark:brightness-0 dark:invert" alt="off icon" />
          Turn Off Engine Control
        </MenuItem>
        <MenuItem
          className="flex text-[#595959] dark:text-white dark:hover:bg-dm-600 py-1 font-normal leading-3 text-xs"
          onClick={handleClose}
        >
          <Image src={recycle} className="mr-2 w-5 dark:brightness-0 dark:invert" alt="x" />
          Restore Engine Control
        </MenuItem>
        <MenuItem
          className="text-[#DC4A41] flex items-center py-1 font-normal leading-3 text-xs dark:hover:bg-dm-600"
          onClick={handleClose}
        >
          <Image src={vehicle} className="mr-2 w-5 dark:brightness-0 dark:invert" alt="x" />
          <p className="text-[#DC4A41] text-xs font-normal">Block Vehicle</p>
        </MenuItem>
      </Menu>
    </>
  );
}

export const COLUMNS = [
  {
    Header: "ID",
    accessor: "id",
    width: 127,
    sortable: true,
  },
  {
    Header: "Full Name",
    accessor: "full_name",
    width: 530,
    sortable: true,
  },
  {
    Header: "Deposit Amount",
    accessor: "deposit_amount",
    width: 160,
  },
  {
    Header: "Vehicle Repayment",
    accessor: "vehicle_repayment",
    width: 140,
  },
  {
    Header: "Additional Charges",
    accessor: "additional_charges",
    width: 170,
    sortable: true,
  },
  {
    Header: "Amount Received",
    accessor: "amount_received",
    width: 170,
  },
  {
    Header: "Engine Control",
    accessor: "engine_control",
    width: 190,
    sortable: true,

    Cell: (props) => {
      return (
        <div
          className={`text-sm max-2xl:text-xs rounded-sm text-center pt-0.5 px-2 inline-block leading-4 font-medium ${
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
    Header: "Engine Status",
    accessor: "engine_status",

    sortable: true,
    width: 220,
    Cell: (props) => {
      return (
        <div className="flex items-center gap-x-1">
          <div className="">
            <Image src={refresh} alt="refresh icon" />
          </div>
          <div
            className={`text-sm font-medi max-2xl:text-xs rounded-sm pt-0.5 pb-px px-2 leading-4 font-medium ${
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
    Header: "Engine Status Updated",
    accessor: "engine_status_updated",
    width: 290,
  },
  {
    Header: "Completed Weeks (Invoices)",
    accessor: "completed_weeks",
    width: 270,
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
