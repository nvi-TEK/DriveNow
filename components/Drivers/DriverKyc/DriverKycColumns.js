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
          className="flex items-center text-[#595959] py-1.5 leading-[9.67px] font-normal text-sm"
        >
          <Image src={user} className="mr-2 w-5" alt="people icon" />
          Driver Profile
        </MenuItem>
        <MenuItem
          className="flex items-center text-[#595959] py-1.5 leading-[9.67px] font-normal text-sm "
          onClick={handleClose}
        >
          <Image src={pencil} className="mr-2 w-5 " alt="pencil" />
          Update Agreement
        </MenuItem>
        <MenuItem
          className="flex items-center text-[#595959] py-1.5 leading-[9.67px] font-normal text-sm "
          onClick={handleClose}
        >
          <Image src={power} className="mr-2 w-5" alt="power icon" />
          Turn Off Engine Control
        </MenuItem>
        <MenuItem
          className="flex items-center text-[#595959] py-1.5 leading-[9.67px] font-normal text-sm "
          onClick={handleClose}
        >
          <Image src={recycle} className="mr-2 w-5" alt="recycle icon" />
          Restore Engine Control
        </MenuItem>
        <MenuItem
          className="flex items-center py-1.5 leading-[9.67px] font-normal text-sm "
          onClick={handleClose}
        >
          <Image src={bin} className="mr-2 w-5" alt="bin icon" />
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
    width:1270,
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
    width: 300,
    sortable: true,

    Cell: (props) => {
      return (
        <div
          style={{
            color: props.value === "ON" ? "#0EA371" : "#DC4A41",
            backgroundColor: props.value === "ON" ? "#E7F6F1" : "#FBEDEC",
            borderRadius: "2px",
            textAlign: "center",
            width: "50px",
            paddingTop: "2px",
            paddingBottom: "2px",
            lineHeight: "16px",
            fontWeight: "500",
          }}
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
          style={{
            color:
              props.value === "5/5"
                ? "#0EA371"
                : props.value === "0/5"
                ? "#DC4A41"
                : props.value === "1/5"
                ? "#DC4A41"
                : "#E8B123",
            backgroundColor:
              props.value === "5/5"
                ? "#E7F6F1"
                : props.value === "0/5"
                ? "#FBEDEC"
                : props.value === "1/5"
                ? "#FBEDEC"
                : "#FBF6E9",
            borderRadius: "2px",
            textAlign: "center",
            paddingTop: "1px",
            paddingBottom: "1px",

            maxWidth: "45px",
            lineHeight: "16px",
            fontWeight: "500",
          }}
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
