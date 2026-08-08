/* eslint-disable require-jsdoc */
import React, {useMemo} from 'react';
import {useDropzone} from 'react-dropzone';
import {useTheme} from 'next-themes';
import plus from "../../assets/blueplus.png"
import Image from 'next/image';

const baseStyle = (resolvedTheme) => ({
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
    backgroundColor: resolvedTheme === 'dark' ? '#2A2A2A' : '#FFFFFF',
    color: resolvedTheme === 'dark' ? '#B0B0B0' : '#bdbdbd',
    cursor: 'pointer',
    outline: 'none',
    transition: 'border .24s ease-in-out'
});

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
  const { resolvedTheme } = useTheme();
  const {
    getRootProps,
    getInputProps,
    isFocused,
    isDragAccept,
    isDragReject,

  } = useDropzone({accept: {'image/*': []}});



  const style = useMemo(() => ({
    ...baseStyle(resolvedTheme),
    ...(isFocused ? focusedStyle : {}),
    ...(isDragAccept ? acceptStyle : {}),
    ...(isDragReject ? rejectStyle : {})
  }), [
    isFocused,
    isDragAccept,
    isDragReject,
    resolvedTheme
  ]);

  return (
    <div className="container">
      <div {...getRootProps({style})}>
        <input {...getInputProps()} />
        <Image className="w-[2.625rem] h-[2.625rem] " src={plus} alt={"drag n drop image"} />
        <p className='font-medium leading-[18px] pt-5 text-2xl dark:text-white'>Choose file or drag them here</p>
        <p className='text-[#8C8C8C] dark:text-dm-300 pt-5 text-xs font-normal '>Maximum 10 photos </p>
      </div>
    </div>
  );
}

