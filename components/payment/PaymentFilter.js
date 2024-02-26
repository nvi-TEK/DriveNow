import React, { useState } from 'react'
import SearchOutlinedIcon from '@mui/icons-material/SearchOutlined';

export const GlobalFilter = ({ filter, setFilter }) => {
 
  return (
    <span className=''>
      <input
        value={filter || ''}
        onChange={(e) => setFilter(e.target.value)} 
        className='border w-[353px] text-sm pl-3 py-1 border-[#D9D9D9] rounded text-black'
        placeholder='Search by Name, Email or Phone Number'
      />
    </span>
  )
}