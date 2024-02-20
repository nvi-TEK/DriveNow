/* eslint-disable react/no-unescaped-entities */
/* eslint-disable require-jsdoc */
"use client";

import React from "react";
import { Sidebar } from "flowbite-react";
import driver from "../assets/driver_icon.png";

export default function Sidebar1() {
  return (
    <Sidebar aria-label="Sidebar with multi-level dropdown example">
      <Sidebar.Items>
        <Sidebar.ItemGroup>
          <Sidebar.Item href="#" icon={driver}>
            Dashboard
          </Sidebar.Item>
          <Sidebar.Collapse label="E-commerce">
            <Sidebar.Item href="#">Products</Sidebar.Item>
            <Sidebar.Item href="#">Sales</Sidebar.Item>
            <Sidebar.Item href="#">Refunds</Sidebar.Item>
            <Sidebar.Item href="#">Shipping</Sidebar.Item>
          </Sidebar.Collapse>
          <Sidebar.Item href="#" icon={driver}>
            Inbox
          </Sidebar.Item>
          <Sidebar.Item href="#" icon={driver}>
            Users
          </Sidebar.Item>
          <Sidebar.Item href="#" icon={driver}>
            Products
          </Sidebar.Item>
          <Sidebar.Item href="#" icon={driver}>
            Sign In
          </Sidebar.Item>
          <Sidebar.Item href="#" icon={driver}>
            Sign Up
          </Sidebar.Item>
        </Sidebar.ItemGroup>
      </Sidebar.Items>
    </Sidebar>
  );
}

