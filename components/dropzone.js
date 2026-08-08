/* eslint-disable require-jsdoc */
import React, { useMemo } from "react";
import { useDropzone } from "react-dropzone";
import { useTheme } from "next-themes";
import plus from "../assets/dropzoneplus.svg";
import Image from "next/image";

const dropzoneBaseStyle = (resolvedTheme) => ({
  display: "flex",
  width: "188px",
  height: "200px",
  alignItems: "center",
  padding: "50px",
  justifyItems: "center",
  paddingLeft: "73px",
  borderWidth: 1,
  borderRadius: 8,
  borderColor: resolvedTheme === "dark" ? "#5C5C5C" : "#BFBFBF",
  borderStyle: "dashed",
  backgroundColor: resolvedTheme === "dark" ? "#404040" : "#fafafa",
  color: resolvedTheme === "dark" ? "#B0B0B0" : "#bdbdbd",
  outline: "none",
  transition: "border .24s ease-in-out",
});

const focusedStyle = {
  borderColor: "#2196f3",
};

const acceptStyle = {
  borderColor: "#00e676",
};

const rejectStyle = {
  borderColor: "#ff1744",
};

export default function StyledDropzone() {
  const { resolvedTheme } = useTheme();
  const { getRootProps, getInputProps, isFocused, isDragAccept, isDragReject } =
    useDropzone({ accept: { "image/*": [] } });

  const style = useMemo(
    () => ({
      ...dropzoneBaseStyle(resolvedTheme),
      ...(isFocused ? focusedStyle : {}),
      ...(isDragAccept ? acceptStyle : {}),
      ...(isDragReject ? rejectStyle : {}),
    }),
    [isFocused, isDragAccept, isDragReject, resolvedTheme]
  );

  return (
    <div className="container">
      <div className="cursor-pointer grow" {...getRootProps({ style })}>
        <input {...getInputProps()} />
        <Image
          className="w-[2.625rem] h-[2.625rem]"
          src={plus}
          alt={"plus icon"}
        />
      </div>
    </div>
  );
}

function VehicleImagesDropzone() {
  const { resolvedTheme } = useTheme();
  const { getRootProps, getInputProps, isFocused, isDragAccept, isDragReject } =
    useDropzone({ accept: { "image/*": [] } });

  const style = useMemo(
    () => ({
      ...dropzoneBaseStyle(resolvedTheme),
      ...(isFocused ? focusedStyle : {}),
      ...(isDragAccept ? acceptStyle : {}),
      ...(isDragReject ? rejectStyle : {}),
    }),
    [isFocused, isDragAccept, isDragReject, resolvedTheme]
  );

  return (
    <div className="container">
      <div className="cursor-pointer grow" {...getRootProps({ style })}>
        <input {...getInputProps()} />
        <Image
          className="w-[2.625rem] h-[2.625rem]"
          src={plus}
          alt={"plus icon"}
        />
      </div>
    </div>
  );
}

function VehicleImagesDropzone1() {
  const { resolvedTheme } = useTheme();
  const { getRootProps, getInputProps, isFocused, isDragAccept, isDragReject } =
    useDropzone({ accept: { "image/*": [] } });

  const style = useMemo(
    () => ({
      ...dropzoneBaseStyle(resolvedTheme),
      ...(isFocused ? focusedStyle : {}),
      ...(isDragAccept ? acceptStyle : {}),
      ...(isDragReject ? rejectStyle : {}),
    }),
    [isFocused, isDragAccept, isDragReject, resolvedTheme]
  );

  return (
    <div className="container">
      <div className="cursor-pointer grow" {...getRootProps({ style })}>
        <input {...getInputProps()} />
        <Image
          className="w-[2.625rem] h-[2.625rem]"
          src={plus}
          alt={"plus icon"}
        />
      </div>
    </div>
  );
}

export { VehicleImagesDropzone, VehicleImagesDropzone1 };
