/* eslint-disable require-jsdoc */
import React, {useMemo} from 'react';
import {useDropzone} from 'react-dropzone';
import plus from "../assets/dropzone_icon.png"
import Image from 'next/image';

const baseStyle = {
    flex: 1,
    display: 'flex',
    width:'160px',
    alignItems: 'center',
    padding: '50px',
    justifyItems: 'center',
    paddingLeft: '58px',
    borderWidth: 2,
    borderRadius: 2,
    borderColor: '#eeeeee',
    borderStyle: 'dashed',
    backgroundColor: '#fafafa',
    color: '#bdbdbd',
    outline: 'none',
    transition: 'border .24s ease-in-out'
};

const focusedStyle = {
  borderColor: '#2196f3'
};

const acceptStyle = {
  borderColor: '#00e676'
};

const rejectStyle = {
  borderColor: '#ff1744'
};

export default function StyledDropzone() {
  const {
    getRootProps,
    getInputProps,
    isFocused,
    isDragAccept,
    isDragReject,
    
  } = useDropzone({accept: {'image/*': []}});

  

  const style = useMemo(() => ({
    ...baseStyle,
    ...(isFocused ? focusedStyle : {}),
    ...(isDragAccept ? acceptStyle : {}),
    ...(isDragReject ? rejectStyle : {})
  }), [
    isFocused,
    isDragAccept,
    isDragReject
  ]);

  return (
    <div className="container">
      <div {...getRootProps({style})}>
        <input {...getInputProps()} />
        <Image className="w-[2.625rem] h-[2.625rem] " src={plus} alt={"drag n drop image"} />
      </div>
    </div>
  );
}

