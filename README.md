# 🎫 EventRes — Event Reservation Web Platform

A full-stack web application developed with **Symfony 7**, featuring modern authentication mechanisms including **JWT tokens** and **Passkeys (WebAuthn/FIDO2)**.

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | Symfony 7, PHP 8.2 |
| Database | PostgreSQL 15 |
| Authentication | JWT (LexikJWTBundle) + Passkeys (WebAuthn) |
| Frontend | Twig, Bootstrap 5, Vanilla JS |
| Containerization | Docker, Docker Compose |
| Version Control | Git, GitHub |

---

## ⚙️ Application Features

### For Users
- Account creation and login via password or Passkey (biometric/PIN)
- Browse and explore upcoming events
- View detailed information per event
- Book a spot at any available event
- Receive a confirmation screen upon successful reservation

### For Administrators
- Dedicated secure admin login
- Overview dashboard with key statistics
- Full event management: create, read, update, delete (with image upload support)
- Access and review all reservations linked to each event
- Secure session logout

### Security Highlights
- Stateless API authentication via JWT tokens
- Passwordless login support through Passkeys (WebAuthn/FIDO2)
- Refresh tokens with a 30-day validity window
- Role-based access control (`ROLE_USER`, `ROLE_ADMIN`)
- API-level CORS configuration

---

## 🚀 Setup & Installation

### Requirements
- Docker
- Docker Compose
- Git

### Step-by-Step Guide

#### 1. Clone the project
```bash
git clone https://github.com/SarraBenHamad/MiniProjet2A-EventReservation-SarraBenHamad.git
cd MiniProjet2A-EventReservation-SarraBenHamad
```

#### 2. Set up your environment file
```bash
cp .env .env.local
```

Update `.env.local` with your configuration:
```bash
DATABASE_URL="postgresql://appuser:apppassword@db:5432/event_reservation?serverVersion=15"
JWT_PASSPHRASE=your_secret_passphrase
APP_DOMAIN=localhost
WEBAUTHN_RP_NAME="Event Reservation App"
```

#### 3. Build and start the containers
```bash
docker compose up -d --build
```

#### 4. Install PHP dependencies
```bash
docker exec -it symfony_php composer install
```

#### 5. Generate JWT key pair
```bash
docker exec -it symfony_php bash
mkdir -p config/jwt
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
chmod 600 config/jwt/*.pem
exit
```

#### 6. Apply database migrations
```bash
docker exec -it symfony_php php bin/console doctrine:migrations:migrate --no-interaction
```

#### 7. Seed the database with fixtures
```bash
docker exec -it symfony_php php bin/console doctrine:fixtures:load --no-interaction
```

#### 8. Access the application
```
http://localhost:8080
```

---

## 🔑 Default Login Credentials

| Role | Username | Password |
|---|---|---|
| Admin | admin | admin123 |

---

## 🧪 Running the Test Suite
```bash
docker exec -it symfony_php php bin/phpunit --testdox
```

---

## 📂 Codebase Structure
```
src/
├── Controller/
│   ├── Admin/          # Admin-side controllers
│   ├── Api/            # JWT & Passkey API endpoints
│   └── User/           # User-facing controllers
├── Entity/             # Doctrine ORM entities
├── Repository/         # Custom database query classes
├── Service/            # PasskeyAuthService logic
└── DataFixtures/       # Sample data for development
templates/
├── admin/              # Admin panel views
└── user/               # User-facing views
public/js/
└── auth.js             # WebAuthn client-side logic
```
