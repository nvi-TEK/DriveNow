import React, { useState } from 'react'
import SearchOutlinedIcon from '@mui/icons-material/SearchOutlined';

export const DriverKycFilter = ({ filter, setFilter }) => {
 
  return (
    <span className=''>
      <input
        value={filter || ''}
        onChange={(e) => setFilter(e.target.value)} 
        className='border placeholder-[#BFBFBF] w-[353px] pl-3 py-1 text-sm border-[#D9D9D9] rounded text-black'
        placeholder='Search by Name, Email or Phone Number'
      />
    </span>
  )
}