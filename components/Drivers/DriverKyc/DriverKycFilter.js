import React, { useState } from 'react'
import SearchOutlinedIcon from '@mui/icons-material/SearchOutlined';

export const DriverKycFilter = ({ filter, setFilter }) => {
 
  return (
    <span className='flex items-center'>
    <p className='text-[#262626] leading-[30px] font-medium'>Search:</p>
      <input
        value={filter || ''}
        onChange={(e) => setFilter(e.target.value)} 
        className='border shadow-[0px_1px_2px_0px_#1B283614] placeholder-[#BFBFBF] dark:text-white dark:border-0 ml-2 dark:bg-dm-600 w-[353px] pl-3 py-1 text-sm border-[#D9D9D9] rounded text-black'
        placeholder='Search by Name, Email or Phone Number'
      />
    </span>
  )
}