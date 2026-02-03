# Student Management Portal

## Project Overview
The Student Management Portal is a web-based application developed using **PHP** and **MySQL**. It helps students manage academic and personal activities in a single platform. The portal allows students to maintain tasks, expenses, documents, reminders, and emergency contact information.

---

## Features

### User Authentication
- Student registration
- Secure login and logout
- Password encryption

### Dashboard
- Summary of pending tasks
- Expense tracking overview
- Reminder notifications
- Document storage summary
- Emergency contacts display

### Todo Management
- Add tasks
- Set priority level
- Set due dates
- Update task status
- Delete tasks

### Expense Management
- Add expense details
- Track expense category
- Record payment method
- View expense history
- Monthly expense tracking

### Document Management
- Upload important documents
- Store file details
- Download documents
- Delete documents

### Reminder Management
- Create reminders
- Set reminder date
- View upcoming reminders
- Delete reminders

### Emergency Contact Management
- Add emergency contact details
- Store relation and phone number
- Edit and delete contacts

---

## Technology Stack

### Frontend
- HTML  
- CSS  
- Bootstrap  
- JavaScript  

### Backend
- PHP  

### Database
- MySQL  

### Server
- XAMPP / Apache  

---

## Database Structure

### Main Tables

#### Users
Stores student account information.

#### Todos
Stores task details created by students.

#### Expenses
Stores student expense records.

#### Documents
Stores uploaded document information.

#### Reminders
Stores reminder messages and dates.

#### Emergency Contacts
Stores important contact details.

---

## ER Diagram
The database design is represented using an Entity Relationship Diagram which shows relationships between Users, Todos, Expenses, Documents, Reminders, and Emergency Contacts.

---

## Installation Steps

### Step 1: Install XAMPP
Download and install XAMPP from the official website.

### Step 2: Setup Project Folder
1. Open XAMPP installation folder.
2. Go to `htdocs` directory.
3. Copy project folder inside `htdocs`.

### Step 3: Create Database
1. Open phpMyAdmin.
2. Create a new database named:


3. Import SQL file if available.

### Step 4: Configure Database Connection

Update database connection in PHP file:

```php
$conn = mysqli_connect("localhost","root","","student_management");


Step 5: Run Project

Open browser and enter:

http://localhost/student_app

or 

http://127.0.0.1/student_app

// Folder Structure //

student_management/
│
├── index.php
├── dashboard.php
├── readme.md
├── uploads/
├── css/
└── database/
