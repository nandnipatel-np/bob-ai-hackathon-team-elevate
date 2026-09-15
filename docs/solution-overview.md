# Solution Overview

## What We Built

We built a web-based Campus Canteen Pre-Order System that allows students to view the canteen menu, select food items, add them to a cart, and place orders before reaching the canteen. The system also provides Admin and SuperAdmin functionality for managing menu items, users, orders, payments, and reports.

The system reduces waiting time and crowding during peak hours by moving the ordering process from the physical counter to a digital platform.
## How It Works

[Explain the core mechanism step by step. A numbered list or simple flow works well here.]

The user opens the Campus Canteen Pre-Order System through a web browser.
The user logs in according to their role and receives access to the appropriate features.
Students browse the available canteen menu and select required food items.
Selected items are added to the cart, where the user can review the order.
The student places the pre-order and provides the required payment information or proof.
The PHP backend processes the request and stores the order information in the MySQL database.
Canteen administrators can view and manage incoming orders through the dashboard.
Admin and SuperAdmin users can manage menu items, users, payments, and reports.
The system displays the updated order and status information to the user.

## Architecture Diagram

> See [`architecture.md`](architecture.md) for the detailed diagram.

[Optionally include a simple ASCII or Mermaid diagram here for quick reference.]

```
[User] → [Frontend: React] → [API: FastAPI] → [watsonx.ai] → [Dashboard]
                                    ↓
                             [PostgreSQL DB]
```

## Key Design Decisions

| Decision | Rationale |
|---|---|
| Used PHP for the backend | PHP provides a straightforward way to implement the web application's business logic and server-side processing. |
| Used MySQL for data storage | MySQL provides structured storage for users, menu items, orders, payments, and related information.|
| Used HTML, CSS and JavaScript for the frontend | These technologies provide the required web interface for students, Admin, and SuperAdmin users. |

## IBM Technologies Used

IBM Bob: Used as an AI-assisted development tool during the project workflow to help the team with development, documentation, problem-solving, and project-related tasks.

Important: If your team did not actually use IBM Bob, don't claim it here. In that case, write:

IBM Technologies: None directly integrated into the CCP application. The application was developed using PHP, HTML, CSS, JavaScript, MySQL, Apache/XAMPP, and PHPMailer.

- **[IBM Tech 1, e.g., watsonx.ai]:** [How it was used — e.g., "Used the `ibm/granite-13b-instruct-v2` model via the Python SDK to classify anomaly types from log text."]
- **[IBM Tech 2]:** [How it was used]
