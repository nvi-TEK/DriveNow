import React from "react";
import Image from "next/image";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import IconButton from "@mui/material/IconButton";
import MoreHorizIcon from "@mui/icons-material/MoreHoriz";
import pencil from "../../../assets/pencil.svg";
import user from "../../../assets/user.svg";
import recycle from "../../../assets/recycle.svg";
import bin from "../../../assets/bin.svg";
import power from "../../../assets/power.svg";

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

  const DocsUploaded = {
    Doc1: "green",
    Doc2: "red",
    Doc3: "yellow",
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
            width: "225px",
            position: "sticky",
            marginRight: "50px",
            fontFamily: "Avenir",
            paddingTop: "",
          },
        }}
      >
        <MenuItem
          onClick={handleClose}
          className="flex items-center text-[#595959] dark:text-white dark:hover:bg-dm-600 py-1.5 leading-[9.67px] font-normal text-sm"
        >
          <Image src={user} className="mr-2 w-5 dark:brightness-0 dark:invert" alt="people icon" />
          Driver Profile
        </MenuItem>
        <MenuItem
          className="flex items-center text-[#595959] dark:text-white dark:hover:bg-dm-600 py-1.5 leading-[9.67px] font-normal text-sm "
          onClick={handleClose}
        >
          <Image src={pencil} className="mr-2 w-5 dark:brightness-0 dark:invert" alt="pencil" />
          Update Agreement
        </MenuItem>
        <MenuItem
          className="flex items-center text-[#595959] dark:text-white dark:hover:bg-dm-600 py-1.5 leading-[9.67px] font-normal text-sm "
          onClick={handleClose}
        >
          <Image src={power} className="mr-2 w-5 dark:brightness-0 dark:invert" alt="power icon" />
          Turn Off Engine Control
        </MenuItem>
        <MenuItem
          className="flex items-center text-[#595959] dark:text-white dark:hover:bg-dm-600 py-1.5 leading-[9.67px] font-normal text-sm "
          onClick={handleClose}
        >
          <Image src={recycle} className="mr-2 w-5 dark:brightness-0 dark:invert" alt="recycle icon" />
          Restore Engine Control
        </MenuItem>
        <MenuItem
          className="flex items-center py-1.5 leading-[9.67px] font-normal text-sm dark:hover:bg-dm-600"
          onClick={handleClose}
        >
          <Image src={bin} className="mr-2 w-5 dark:brightness-0 dark:invert" alt="bin icon" />
          <p className="text-[#DC4A41] leading-[9.67px] text-sm">Terminate</p>
        </MenuItem>
      </Menu>
    </>
  );
}

export const DRIVERKYCCOLUMNS = [
  {
    Header: "ID",
    accessor: "id",
    width: 294,
    sortable: true,
  },
  {
    Header: "Full Name",
    accessor: "full_name",
    width: 1270,
    sortable: true,
  },
  {
    Header: "Mobile Number",
    accessor: "mobile_number",
    width: 1220,
    sortable: true,
  },
  {
    Header: "Email",
    accessor: "email",
    sortable: false,
    width: 20,
  },
  {
    Header: "App Version",
    accessor: "app_version",
    width: 20,
  },
  {
    Header: "Engine Control",
    accessor: "engine_control",
    width: 500,
    sortable: true,

    Cell: (props) => {
      return (
        <div
          className={`rounded-sm text-center px-2 py-0.5 leading-4 font-medium inline-block ${
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
    Header: "Registration Date",
    accessor: "registration_date",
    width: 150,
  },
  {
    Header: "Documents Uploaded",
    accessor: "documents_uploaded",
    width: 200,
    Cell: (props) => {
      return (
        <div
          className={`rounded-sm text-center py-px px-2 inline-block leading-4 font-medium ${
            props.value === "5/5"
              ? "text-[#0EA371] dark:text-[#34D399] bg-[#E7F6F1] dark:bg-[#0EA37133]"
              : props.value === "0/5" || props.value === "1/5"
              ? "text-[#DC4A41] dark:text-[#F87171] bg-[#FBEDEC] dark:bg-[#DC4A4133]"
              : "text-[#E8B123] dark:text-[#FBBF24] bg-[#FBF6E9] dark:bg-[#E8B12333]"
          }`}
        >
          {props.value}
        </div>
      );
    },
  },
  {
    Header: "Date Approved",
    accessor: "date_approved",
    width: 90,
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
