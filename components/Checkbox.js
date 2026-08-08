/* eslint-disable */

import React from 'react'

export const Checkbox = React.forwardRef(({ indeterminate, ...rest }, ref) => {
  const defaultRef = React.useRef()
  const resolvedRef = ref || defaultRef

  React.useEffect(() => {
    resolvedRef.current.indeterminate = indeterminate
  }, [resolvedRef, indeterminate])

  return (
    <>
      <input className='border-[#D9D9D9] dark:border-0 dark:bg-dm-600 rounded-[2px] cursor-pointer focus:ring-0 focus:ring-offset-0 focus:outline-none' type='checkbox' ref={resolvedRef} {...rest} />
    </>
  )
})