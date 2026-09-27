# Financial Transaction Fraud Detection System

A database-driven web application designed to monitor financial transactions and identify potentially fraudulent activities using rule-based fraud detection techniques.

This project is developed as a Database Management System (DBMS) course project.

---

## 📌 Project Overview

Financial fraud is a major concern in modern banking and digital payment systems. This project provides a simplified fraud detection system that monitors financial transactions and identifies suspicious activities based on predefined rules.

The system allows users to manage accounts and transactions, while administrators can monitor suspicious transactions, investigate fraud alerts, and generate reports.

The main focus of this project is the implementation and application of database concepts such as:

- Relational database design
- Primary and foreign keys
- SQL queries
- Joins
- Normalization
- Database constraints
- Fraud detection rules
- Transaction management
- Reporting
- Data integrity

---

## 🎯 Objectives

The main objectives of the project are:

1. Store and manage customer information.
2. Manage customer accounts.
3. Record financial transactions.
4. Detect potentially suspicious transactions.
5. Generate fraud alerts.
6. Allow administrators to investigate suspicious transactions.
7. Maintain transaction and fraud records.
8. Generate useful reports.
9. Demonstrate practical DBMS concepts through a real-world application.

---

## ✨ Main Features

### 👤 Customer Management

- Customer registration
- Customer login
- Customer information management
- Account management

### 💳 Transaction Management

- Create financial transactions
- View transaction history
- Track transaction status
- Store transaction details

### 🚨 Fraud Detection

The system can identify suspicious transactions using predefined rules such as:

- Unusually large transactions
- Multiple transactions within a short period
- Suspicious transaction patterns
- Unusual account activity
- Other configurable fraud detection conditions

### 🔔 Fraud Alerts

- Automatically identify suspicious transactions
- Generate fraud alerts
- Assign risk levels
- Track alert status

### 👨‍💼 Administrator Features

- View customers
- View accounts
- View transactions
- Monitor fraud alerts
- Investigate suspicious transactions
- View reports

### 📊 Reports

The system provides transaction and fraud-related information for monitoring and analysis.

---

## 🏗️ System Architecture

The application follows a simple three-layer architecture:

```text
                User
                 |
                 ↓
          ┌─────────────┐
          │  Frontend   │
          │  index.html │
          └──────┬──────┘
                 |
                 ↓
          ┌─────────────┐
          │   Backend   │
          │ PHP Scripts │
          └──────┬──────┘
                 |
                 ↓
          ┌─────────────┐
          │   MySQL     │
          │  Database   │
          └─────────────┘