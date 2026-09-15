# Setup Guide

> **This file is read by the automated evaluation pipeline. Be precise and complete.**

## Prerequisites

Before you begin, ensure you have the following installed:

- XAMPP
- Apache
- MySQL
- PHP
- Web browser such as Google Chrome
- Git (optional, for cloning the repository)

## Environment Variables

Copy `.env.example` to `.env` and fill in the values:

```bash
cp .env.example .env
```

| Variable | Description | Required |
|---|---|---|
| `WATSONX_API_KEY` | Your IBM watsonx.ai API key | Yes |
| `WATSONX_PROJECT_ID` | Your watsonx.ai project ID | Yes |
| `DATABASE_URL` | PostgreSQL connection string | Yes |
| `SLACK_WEBHOOK_URL` | Slack webhook for alerts | No |

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/naiyaptl/bob-ai-hackathon-team-elevate.git

# 2. Copy the project into the XAMPP htdocs folder

# Example:
C:\xampp\htdocs\Campus-Canteen-Pre-Order-System

# 3. Start XAMPP

# Start:
Apache
MySQL

# 4. Create the MySQL database using phpMyAdmin

# Open:
http://localhost/phpmyadmin

# 5. Create the required database and import the project SQL/database structure.

# 6. Check the PHP database configuration file and update:
# database name
# username
# password
# host
```

## Running the Application

```bash
1. Open XAMPP Control Panel.
2. Start Apache.
3. Start MySQL.
4. Make sure the project is inside the XAMPP htdocs directory.
5. Open a web browser.
6. Access the application using:
http://localhost/Campus-Canteen-Pre-Order-System/

```

The application will be available at: `http://localhost:[PORT]`

## Running Tests

```bash
he current hackathon prototype does not include an automated testing framework.

The application can be manually tested by checking:

User registration and login
Menu browsing
Adding items to cart
Placing orders
Payment information
Order management
Admin functionality
SuperAdmin functionality
Reports

```

## Quick Demo (Optional)

If you have a demo script or sample data to showcase the project quickly:

```bash
# 1. Start XAMPP
# 2. Start Apache and MySQL
# 3. Open the application in your browser

http://localhost/Campus-Canteen-Pre-Order-System/

```

## Troubleshooting

| Issue | Solution |
|---|---|
| Apache does not start | Check whether another application is using port 80 or 443, then restart Apache. |
| MySQL does not start | Check the MySQL service and make sure its port is not being used by another application. |
| Database connection error | Check the database name, username, password and host in the PHP database configuration. |
