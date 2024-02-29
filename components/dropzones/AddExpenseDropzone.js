/* eslint-disable require-jsdoc */
import React, {useMemo} from 'react';
import {useDropzone} from 'react-dropzone';
import plus from "../../assets/blueplus.png"
import Image from 'next/image';

const baseStyle = {
    flex: 1,
    display: 'flex',
    flexDirection: 'column',
    width:'100%',
    height: '240px',
    alignItems: 'center',
    padding: '50px',
    justifyItems: 'center',
    paddingLeft: '58px',
    borderWidth: 1,
    borderRadius: 16,
    borderColor: '#007AF5',
    borderStyle: 'dashed',
    backgroundColor: '#FFFFFF',
    color: '#bdbdbd',
    cursor: 'pointer',
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
        <p className='font-medium leading-[18px] pt-5 text-2xl'>Choose file or drag them here</p>
        <p className='text-[#8C8C8C] pt-5 text-xs font-normal '>Maximum 10 photos </p>
      </div>
    </div>
  );
}

