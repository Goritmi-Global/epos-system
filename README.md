# 🧾 EPOS System – Restaurant & Cafe (Multi-Platform)

A scalable, multi-user Electronic Point of Sale (EPOS) system built for restaurants and cafes, supporting web, mobile, and desktop environments with real-time synchronization and role-based access.

---

## 🔧 Technology Stack

- **Backend:** Laravel 12 (REST API + Web)
- **Frontend:** Vue 3 with Inertia.js (Laravel Breeze-based)
- **Mobile/Desktop Apps:** Flutter (Android, iOS, Windows)
- **Database:** MySQL
- **Authentication:** Laravel Sanctum (API), Laravel Breeze (Web)
- **Authorization:** Spatie Laravel Permission

---

## 🔐 Authentication & Access Control

### Multi-User Authentication:
- Username + password login
- Quick access via PIN code
- Sanctum token-based login for Flutter
- Breeze session-based login for Web

### Role & Permission Management:
- Spatie Laravel Permission
- Roles include: Admin, Cashier, Chef, Waiter, Manager, Inventory Staff
- Fine-grained permission control (e.g., `order:create`, `report:view`, `inventory:update`)

---

## 📲 Cross-Platform Integration

Real-time data sync across:
- Web Dashboard
- Flutter Mobile App (Waiters, Managers)
- Flutter Desktop App (POS Terminal)
- Kitchen Display System (KDS)

Communication via secure Sanctum-authenticated APIs.

---

## 📦 Core Features

- **Inventory & Stock Management**
  - Batches, expiry alerts, supplier tracking
- **Menu Management**
  - Item categories with estimated prep times
- **Order Management**
  - Dine-in, takeaway, and delivery flows
  - Real-time updates to kitchen & cashier
- **Table Reservation System**
  - Online interface for managing bookings
- **Notification System**
  - Stock alerts, order updates, and table notifications

---

## 🎨 UI & Styling

- Custom admin dashboard template
- No external frameworks like Bootstrap
- Uses built-in styling library for clean, responsive UI

---

## 🏢 Developed By

**Goritmi**  
Full-stack digital solutions for modern businesses.  
📧 [info@goritmi.co.uk]  
🌐 [www.goritmi.co.uk](http://www.goritmi.co.uk)

---

## 🚀 Setup & Installation

> This project requires PHP 8.2+, Node.js 18+, MySQL, and Flutter SDK.

### 1. Clone the Repository:
```bash
git clone https://github.com/your-repo/epos-system.git
cd epos-system
