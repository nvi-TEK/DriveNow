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
      <input className='border-[#D9D9D9]' type='checkbox' ref={resolvedRef} {...rest} />
    </>
  )
})