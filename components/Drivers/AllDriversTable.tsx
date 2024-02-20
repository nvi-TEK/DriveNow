/* eslint-disable require-jsdoc */

import React, { useState } from "react";
import Image from "next/image";
// import Modal from "react-modal";
import Link from "next/link";
import Bubble from "./DriversBubbles";
import { AgreedBubble, EngineControlBubble } from "./DriversBubbles";
import IconButton from "@mui/material/IconButton";
import Menu from "@mui/material/Menu";
import MenuItem from "@mui/material/MenuItem";
import MoreVertIcon from "@mui/icons-material/MoreVert";

type tableProp = {
  ID?: number;
  fullName?: string;
  mobileNumber?: string;
  Status?: string;
  Agreed?: string;
  appVersion?: string;
  EngineControl?: string;
  locationUpdate: string;
  activeHours: string;
};

const allDriversOptions = [
  "Edit",
  "Duplicate",
  "Archive",
  "Attach file",
  "Delete",
];

const ITEM_HEIGHT = 48;

export default function TableData(props: tableProp) {
  const [anchorEl, setAnchorEl] = React.useState<null | HTMLElement>(null);
  const open = Boolean(anchorEl);
  const handleClick = (event: React.MouseEvent<HTMLElement>) => {
    setAnchorEl(event.currentTarget);
  };
  const handleClose = () => {
    setAnchorEl(null);
  };

  const [modalIsOpen, setModalIsOpen] = useState(false);

  const openModal = () => {
    setModalIsOpen(true);
  };

  const closeModal = () => {
    setModalIsOpen(false);
  };

  const customStyles = {
    content: {
      top: "50%",
      left: "50%",
      right: "auto",
      bottom: "auto",
      marginRight: "-50%",
      transform: "translate(-50%, -50%)",
      padding: "0",
      width: "903px",
      height: "800px",
      borderRadius: "8px 8px 0 0",
      backgroundColor: "white",
    },
  };

  return (
    <>
      <td className="w-4 p-4">
        <div className="flex items-center">
          <input
            id="checkbox-table-1"
            type="checkbox"
            className="h-4 w-4 rounded  bg-gray-100 text-[#FA790F]"
          />
          <label htmlFor="checkbox-table-1" className="sr-only">
            checkbox
          </label>
        </div>
      </td>
      <th
        scope="row"
        className="justify-between mt-4 whitespace-nowrap py-4 font-medium text-gray-900"
      >
        <p>{props.ID}</p>
      </th>
      <td>
        <p className="text-xs">{props.fullName}</p>
      </td>
      <td className="px-6 py-6">
        <p>{props.mobileNumber}</p>
      </td>
      <td className="px-6 py-4">
        <Bubble status={props.Status} />
      </td>

      <td className="pl-6 py-4">
        <p className="text-[#828187]">
          <AgreedBubble agreed={props.Agreed} />{" "}
        </p>
      </td>

      <td className="pl-6 py-4">
        <p>{props.appVersion}</p>
      </td>
      <td className="pl-6 py-4">
        <p>
          <EngineControlBubble engineControl={props.EngineControl} />
        </p>
      </td>
      <td className="pl-6 py-4">
        <p>{props.locationUpdate}</p>
      </td>
      <td className="pl-6 py-4">
        <p>{props.activeHours}</p>
      </td>
      <td className="pl-6 py-4">
        <div>
          <IconButton
            aria-label="more"
            id="long-button"
            aria-controls={open ? "long-menu" : undefined}
            aria-expanded={open ? "true" : undefined}
            aria-haspopup="true"
            onClick={handleClick}
          >
            <MoreVertIcon />
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
                width: "20ch",
                position: "absolute",
              },
            }}
          >
            {allDriversOptions.map((option) => (
              <MenuItem
                key={option}
                selected={option === "Pyxis"}
                onClick={handleClose}
              >
                {option}
              </MenuItem>
            ))}
          </Menu>
        </div>
      </td>

      <td className="pl-6 py-4"></td>
    </>
  );
}
