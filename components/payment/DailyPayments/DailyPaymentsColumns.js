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
            fontFamily: "Avenir",
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

export const DAILYPAYMENTSCOLUMNS = [
  {
    Header: "ID",
    accessor: "id",
    width: 259,
    sortable: true,
  },
  {
    Header: "Full Name",
    accessor: "full_name",
    width: 630,
    sortable: true,
  },
  {
    Header: "Deposit Amount",
    accessor: "deposit_amount",
    width: 160,
  },
  {
    Header: "Total Amount Due",
    accessor: "total_amount_due",
    width: 310,
  },
  {
    Header: "Daily Payment",
    accessor: "daily_payment",
    sortable: true,

    width: 260,
  },
  {
    Header: "Vehicle Repayment",
    accessor: "vehicle_repayment",
    width: 160,
  },
  {
    Header: "Engine Control",
    accessor: "engine_control",
    sortable: true,

    width: 390,
    Cell: (props) => {
      return (
        <div
          style={{
            color: props.value === "ON" ? "#0EA371" : "#DC4A41",
            backgroundColor: props.value === "ON" ? "#E7F6F1" : "#FBEDEC",
            borderRadius: "2px",
            textAlign: "center",
            width: "40px",
            paddingTop: "2px",
            paddingBottom: "2px",
            lineHeight: "16px",
            fontWeight: "500",
          }}
        >
          <p className="font-medium leading-4 ">{props.value}</p>
        </div>
      );
    },
  },
  {
    Header: "Engine Status",
    accessor: "engine_status",
    sortable: true,

    width: 290,
    Cell: (props) => {
      return (
        <div className="flex items-center gap-x-1">
          <div className="">
            <Image src={refresh} alt="refresh icon" />
          </div>
          <div
            id="t-engine-status"
            style={{
              color: props.value === "Active" ? "#0EA371" : "#DC4A41",
              backgroundColor: props.value === "Active" ? "#E7F6F1" : "#FBEDEC",
              borderRadius: "2px",
              textAlign: "center",
              paddingTop: "2px",
              paddingBottom: "2px",

              lineHeight: "16px",
              fontWeight: "500",
            }}
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
    width: 570,
  },
  {
    Header: "Completed Weeks (Invoices)",
    accessor: "completed_weeks",
    width: 620,
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
