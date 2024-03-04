/* eslint-disable require-jsdoc */

import React from "react";
import Image from "next/image";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import IconButton from "@mui/material/IconButton";
import tableaction from "../../assets/tableaction.png";
import approve from "../../assets/approvecheck.png";
import details from "../../assets/viewdetails.png";
import decline from "../../assets/x.png";
import MoreHorizIcon from '@mui/icons-material/MoreHoriz';



const ITEM_HEIGHT = 48;

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
<MoreHorizIcon />      </IconButton>
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
            width: "25ch",
            position: "sticky",
            marginRight: "60px"
          },
        }}
      >
      <MenuItem onClick={handleClose} className="py-2 flex">
          <Image src={details} className="mr-2" alt="checkmark" />
          View Details
        </MenuItem>
        <MenuItem onClick={handleClose} className="flex py-2">
          <Image src={approve} className="mr-2" alt="checkmark" />
          Approve
        </MenuItem>
        <MenuItem
          className="text-[#DC4A41] text-sm font-normal py-2 leading-[18px]"
          onClick={handleClose}
        >
          <Image src={decline} className="mr-2" alt="x" />
          <p className="text-[#DC4A41] font-normal">Decline</p>
        </MenuItem>
      </Menu>
    </>
  );
}

export const EXPENSECOLUMNS = [
  {
    Header: "ID",
    accessor: "id",
  },
  {
    Header: "Category",
    accessor: "category",
  },
  {
    Header: "Car Reg",
    accessor: "car_reg",
  },

  {
    Header: "Paid to",
    accessor: "paid_to",
  },
  {
    Header: "Amount",
    accessor: "amount",
  },
  {
    Header: "Approved By",
    accessor: "approved_by",
  },
  {
    Header: "Requested By",
    accessor: "requested_by",
  },
  {
    Header: "Dated on",
    accessor: "dated_on",
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
        <LongMenu />{" "}
      </>
    ),
  },
];
