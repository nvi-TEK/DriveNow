import React, { useState } from 'react'
import SearchOutlinedIcon from '@mui/icons-material/SearchOutlined';

export const ListOfVehiclesFilter = ({ filter, setFilter }) => {
 
  return (
    <span className=''>
      <input
        value={filter || ''}
        onChange={(e) => setFilter(e.target.value)} 
        className='border shadow-[0px_1px_2px_0px_#1B283614] w-[353px] pl-3 py-1 placeholder-[#BFBFBF] border-[#D9D9D9] text-sm rounded text-black'
        placeholder='Search by Name, Email or Phone Number'
      />
    </span>
  )
}