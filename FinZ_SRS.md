# Software Requirements Specification (SRS)
## Financial Tracker & Smart Receipt Parser (SmartSplit)
**Target Architecture:** Laravel PWA Stack

## 1. Introduction

### 1.1 Purpose
This document specifies the software requirements for a web-based personal finance tracker designed to provide a hybrid approach to financial management. The system integrates high-level manual ledger functionality with automated, itemized receipt parsing. This SRS outlines the expected features, system architecture constraints, and user flows.

### 1.2 Scope
The application will allow users to manage multiple financial accounts, log manual transactions, process cross-account transfers, and extract itemized data from scanned receipts using OCR technology. The application will be a Progressive Web Application (PWA) built on a Laravel backend to ensure rapid deployment, strict database integrity, and a native-like user experience.

## 2. Overall Description

### 2.1 User Environment
The system is designed for mobile-first interaction via modern web browsers (Safari, Chrome). By leveraging PWA standards, users can pin the application to their home screen for full-screen operation, utilizing native HTML5 camera access for receipt scanning.

### 2.2 System Architecture Summary
* **Backend Framework:** Laravel (PHP)
* **Frontend Stack:** Inertia.js with Vue.js or React (Single Page Application architecture)
* **Database:** PostgreSQL or MySQL (strictly normalized relational schema)
* **External Integrations:** Third-party OCR/Document AI API (e.g., Google Document AI, AWS Textract) for receipt parsing.

## 3. Functional Requirements

### 3.1 Account Management & Dashboard

| Req ID | Description |
| :--- | :--- |
| **REQ-1.1** | Users shall be able to create, edit, and delete multiple financial accounts (e.g., Bank Accounts, E-Wallets, Cash). |
| **REQ-1.2** | The system shall calculate and display a live total net worth across all logged accounts on a central dashboard. |
| **REQ-1.3** | The dashboard shall display a summary of recent transactions, categorized spending, and current balances for each individual account. |

### 3.2 Core Financial Ledger

| Req ID | Description |
| :--- | :--- |
| **REQ-2.1** | Users shall be able to manually log an expense, selecting the specific account used, the category (e.g., Food, Groceries), and the amount. |
| **REQ-2.2** | Users shall be able to log transfers between owned accounts. The system must process this via double-entry logic (debiting the origin account and crediting the destination account simultaneously). |
| **REQ-2.3** | The system shall automatically adjust the balance of the linked account(s) immediately upon logging a transaction. |

### 3.3 OCR Receipt Parsing & Bill Splitting

> **Critical Logic Constraint: Tax Calculation**
> The bill splitting feature must not use flat percentage splits for tax and service charges. It must utilize a pro-rata calculation based strictly on the items claimed by the user to ensure mathematical accuracy.

| Req ID | Description |
| :--- | :--- |
| **REQ-3.1** | The application shall invoke the mobile device camera via standard HTML file input to capture receipt images. |
| **REQ-3.2** | The backend shall utilize an OCR pipeline to extract line items, prices, subtotal, and tax/service charges from the uploaded image. |
| **REQ-3.3** | The user interface shall present the extracted line items in a selectable list, allowing the user to claim specific items they consumed. |
| **REQ-3.4** | The system shall calculate the user's specific total by summing the claimed items and applying a proportional share of the overall tax and service charge identified on the receipt. |
| **REQ-3.5** | The system shall prompt the user to log the calculated total as a formal expense, allowing them to select the funding account. |

### 3.4 Analytics and Categorization

| Req ID | Description |
| :--- | :--- |
| **REQ-4.1** | Users shall be able to assign high-level categories (e.g., Groceries, Transport) to any transaction. |
| **REQ-4.2** | The system shall generate visual reports (e.g., pie charts or bar graphs) breaking down expenditure by category over a specified time period (weekly, monthly, yearly). |
| **REQ-4.3** | Users shall be able to filter their transaction history by specific categories, dates, or accounts. |

## 4. Non-Functional Requirements
* **NFR-1 (Performance):** The OCR parsing process, from image upload to displaying selectable items, should conclude within 5 seconds under standard network conditions.
* **NFR-2 (Reliability):** The database must employ ACID properties to guarantee that multi-account transfers never result in orphaned funds or unequal ledger entries.
* **NFR-3 (Usability):** The UI must be fully responsive, prioritizing one-handed usability on mobile viewports for quick, on-the-go logging.
* **NFR-4 (Security):** All user financial data and uploaded receipt images must be transmitted securely over HTTPS and stored with appropriate encryption at rest.
