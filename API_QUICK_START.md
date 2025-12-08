# TARUMT Facilities Management System - Quick Start Guide

## Prerequisites
- PHP 8.1 or higher
- MySQL/MariaDB
- Composer
- Laravel 10.x

## Setup Instructions

### 1. Install Dependencies
```bash
composer install
```

### 2. Environment Configuration
Copy `.env.example` to `.env` and configure:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=student_system
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Generate Application Key
```bash
php artisan key:generate
```

### 4. Run Migrations
```bash
php artisan migrate
```

### 5. Start Development Server
```bash
php artisan serve
```

The API will be available at: `http://localhost:8000/api`

## Testing the API

### 1. Register a User
```bash
POST http://localhost:8000/api/register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password123",
    "password_confirmation": "password123"
}
```

### 2. Login
```bash
POST http://localhost:8000/api/login
Content-Type: application/json

{
    "email": "john@example.com",
    "password": "password123"
}
```

Response will include a `token`. Use this token for authenticated requests.

### 3. Get Authenticated User
```bash
GET http://localhost:8000/api/me
Authorization: Bearer {your_token_here}
```

### 4. Create a Facility (Admin)
```bash
POST http://localhost:8000/api/facilities
Authorization: Bearer {your_token_here}
Content-Type: application/json

{
    "name": "Computer Lab 1",
    "code": "LAB-001",
    "type": "laboratory",
    "location": "Block A, Level 2",
    "capacity": 30,
    "status": "available"
}
```

### 5. Create a Booking
```bash
POST http://localhost:8000/api/bookings
Authorization: Bearer {your_token_here}
Content-Type: application/json

{
    "facility_id": 1,
    "booking_date": "2025-12-15",
    "start_time": "2025-12-15 09:00:00",
    "end_time": "2025-12-15 11:00:00",
    "purpose": "Programming workshop",
    "expected_attendees": 25
}
```

## API Endpoints Overview

### Authentication
- `POST /api/register` - Register new user
- `POST /api/login` - Login
- `POST /api/logout` - Logout
- `GET /api/me` - Get current user

### Users
- `GET /api/users` - List users
- `POST /api/users` - Create user
- `GET /api/users/{id}` - Get user
- `PUT /api/users/{id}` - Update user
- `DELETE /api/users/{id}` - Delete user
- `POST /api/users/upload-csv` - Bulk upload

### Notifications
- `GET /api/notifications` - List notifications
- `POST /api/notifications` - Create notification
- `POST /api/notifications/{id}/send` - Send notification
- `GET /api/notifications/user/my-notifications` - My notifications

### Facilities
- `GET /api/facilities` - List facilities
- `POST /api/facilities` - Create facility
- `GET /api/facilities/{id}/availability` - Check availability
- `GET /api/facilities/{id}/utilization` - Utilization stats

### Bookings
- `GET /api/bookings` - List bookings
- `POST /api/bookings` - Create booking
- `PUT /api/bookings/{id}/approve` - Approve booking
- `GET /api/bookings/user/my-bookings` - My bookings

### Loyalty
- `GET /api/loyalty/points` - Get points
- `POST /api/loyalty/rewards/redeem` - Redeem reward

### Feedback
- `POST /api/feedbacks` - Submit feedback
- `GET /api/feedbacks` - List feedbacks

## Using Postman

1. Import the collection (if available)
2. Set environment variables:
   - `base_url`: `http://localhost:8000/api`
   - `token`: (from login response)
3. Use the `token` variable in Authorization header:
   ```
   Bearer {{token}}
   ```

## Common Response Codes

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

## Documentation

For detailed documentation, see:
- `TARUMT_FACILITIES_MANAGEMENT_SYSTEM_GUIDE.md` - Comprehensive guide with theory and implementation details

## Support

For issues or questions, refer to the main guide or Laravel documentation.

