# Web Services Report - Booking Module

## Table of Contents
1. [Overview](#overview)
2. [Service Exposure](#service-exposure)
3. [Service Consumption](#service-consumption)
4. [Interface Agreement (IFA) Documentation](#interface-agreement-ifa-documentation)
5. [Code Implementation](#code-implementation)
6. [Testing Examples](#testing-examples)

---

## Overview

The Booking Module implements web services technology to enable seamless integration with other system modules. The module follows a **RESTful API (JSON-based)** approach, which is the recommended choice for modern, lightweight, and flexible services.

### Web Service Technology Used

1. **REST API (JSON-based)**: All web services use REST architecture with JSON data format
2. **HTTP Protocol**: Communication between modules uses standard HTTP POST requests
3. **Laravel HTTP Client**: Utilizes Laravel's built-in HTTP client (`Illuminate\Support\Facades\Http`) for consuming external services
4. **IFA Standard Compliance**: All services adhere to the Interface Agreement (IFA) standards with mandatory timestamp/requestID fields

### Key Features

- **Service Exposure**: The Booking Module exposes a web service endpoint for other modules to retrieve booking information
- **Service Consumption**: The Booking Module consumes facility information from the Facility Management Module
- **Error Handling**: Implements fallback mechanisms when external services are unavailable
- **Request Tracking**: All requests include timestamp or requestID for proper tracking
- **Standardized Responses**: All responses follow IFA standards with status and timestamp fields

---

## Service Exposure

### Exposed Service: Get Booking Information

The Booking Module exposes a web service that allows other modules to retrieve detailed booking information by booking ID. This service is actively used by the **Feedback Module** to display booking details when users view feedback associated with a booking.

**Purpose**: This service enables other modules to access booking data without direct database access, promoting modularity and separation of concerns.

**Current Usage**:
- **Feedback Module**: Used in `FeedbackController::getBookingDetailsForFeedback()` to retrieve booking information when displaying feedback details that are associated with a booking. This allows users to view comprehensive booking information (facility details, booking date/time, status, etc.) directly from the feedback page.

---

## Service Consumption

### Consumed Service: Get Facility Information

The Booking Module consumes a web service from the Facility Management Module to retrieve facility details during booking creation and availability checks.

**Purpose**: This service ensures that booking operations use the most up-to-date facility information, including capacity, status, and configuration settings.

---

## Interface Agreement (IFA) Documentation

### 1. Service Exposure - Get Booking Information

#### Webservice Mechanism

| Field | Value | Description |
|-------|-------|-------------|
| **Protocol** | RESTFUL | JSON-based REST API |
| **Function Description** | Retrieves detailed booking information by booking ID | Gets comprehensive booking details including user, facility, attendees, and time slots |
| **Source Module** | Booking Management Module | The module that exposes this service |
| **Target Module** | Feedback Module (actively used), Analytics Module, Reporting Module, Customer Service Module | Modules that can consume this service |
| **URL** | `http://[base-url]/api/bookings/service/get-info` | Endpoint URL (replace [base-url] with actual base URL) |
| **Function Name** | `getBookingInfo` | Controller method name |
| **HTTP Method** | POST | Request method |
| **Content-Type** | application/json | Request and response content type |
| **Authentication** | Not required | Inter-module backend communication (no authentication needed) |

#### Web Services Request Parameter

| Field Name | Field Type | Mandatory/Optional | Description | Format |
|------------|------------|-------------------|-------------|--------|
| booking_id | Integer | Mandatory | Unique ID of the booking | Numeric value, must exist in bookings table |
| timestamp | String | Mandatory* | Time when the request was made | YYYY-MM-DD HH:MM:SS |
| requestID | String | Mandatory* | Unique identifier for the request | Alphanumeric string (e.g., "req_1234567890") |

**Note**: 
- `booking_id` is **always required** and must be a valid booking ID that exists in the database.
- Either `timestamp` OR `requestID` must be provided (at least one is mandatory). This is an IFA standard requirement for request tracking.
- Both `timestamp` and `requestID` can be provided together, but at least one is required.

**Recommended Usage**: Use `timestamp` for most cases. This is the standard approach used by the Feedback Module and other consumers.

**Example Request (Recommended - using timestamp)**:
```json
{
    "booking_id": 123,
    "timestamp": "2024-01-15 14:30:00"
}
```

**Example Request (Alternative - using requestID)**:
```json
{
    "booking_id": 123,
    "requestID": "req_20240115143000_abc123"
}
```

**Example Request (Using both - optional)**:
```json
{
    "booking_id": 123,
    "timestamp": "2024-01-15 14:30:00",
    "requestID": "req_20240115143000_abc123"
}
```

**Current Implementation**: The Feedback Module uses the `timestamp` approach as shown in the recommended example above.

#### Web Services Response Parameter

| Field Name | Field Type | Mandatory/Optional | Description | Format |
|------------|------------|-------------------|-------------|--------|
| status | String | Mandatory | Status of the request | S: Success, F: Fail, E: Error |
| message | String | Optional | Human-readable message | String |
| data | Object | Mandatory | Booking information object | Contains booking details |
| data.booking | Object | Mandatory | Detailed booking information | See booking object structure below |
| data.booking.id | Integer | Mandatory | Booking ID | Numeric |
| data.booking.user_id | Integer | Mandatory | User ID who made the booking | Numeric |
| data.booking.user_name | String | Optional | Name of the user | String |
| data.booking.user_email | String | Optional | Email of the user | Valid email format |
| data.booking.facility_id | Integer | Mandatory | Facility ID | Numeric |
| data.booking.facility_name | String | Optional | Name of the facility | String |
| data.booking.facility_code | String | Optional | Code of the facility | String |
| data.booking.booking_date | String | Mandatory | Date of the booking | YYYY-MM-DD |
| data.booking.start_time | String | Mandatory | Start time of booking | YYYY-MM-DD HH:MM:SS |
| data.booking.end_time | String | Mandatory | End time of booking | YYYY-MM-DD HH:MM:SS |
| data.booking.duration_hours | Decimal | Mandatory | Duration in hours | Numeric (e.g., 2.5) |
| data.booking.purpose | String | Mandatory | Purpose of the booking | String |
| data.booking.status | String | Mandatory | Booking status | pending, approved, rejected, cancelled, completed |
| data.booking.expected_attendees | Integer | Optional | Number of expected attendees | Numeric |
| data.booking.approved_by | Integer | Optional | User ID who approved | Numeric (null if not approved) |
| data.booking.approved_at | String | Optional | Approval timestamp | YYYY-MM-DD HH:MM:SS (null if not approved) |
| data.booking.cancelled_at | String | Optional | Cancellation timestamp | YYYY-MM-DD HH:MM:SS (null if not cancelled) |
| data.booking.created_at | String | Mandatory | Creation timestamp | YYYY-MM-DD HH:MM:SS |
| data.attendees_count | Integer | Mandatory | Number of attendees | Numeric |
| data.slots_count | Integer | Mandatory | Number of time slots | Numeric |
| timestamp | String | Mandatory | Time when the response was generated | YYYY-MM-DD HH:MM:SS |

**Example Success Response**:
```json
{
    "status": "S",
    "message": "Booking information retrieved successfully",
    "data": {
        "booking": {
            "id": 123,
            "user_id": 45,
            "user_name": "John Doe",
            "user_email": "john.doe@example.com",
            "facility_id": 10,
            "facility_name": "Basketball Court A",
            "facility_code": "BCA001",
            "booking_date": "2024-01-20",
            "start_time": "2024-01-20 10:00:00",
            "end_time": "2024-01-20 12:00:00",
            "duration_hours": 2.0,
            "purpose": "Basketball practice session",
            "status": "approved",
            "expected_attendees": 10,
            "approved_by": 1,
            "approved_at": "2024-01-15 09:30:00",
            "cancelled_at": null,
            "created_at": "2024-01-15 08:15:00"
        },
        "attendees_count": 8,
        "slots_count": 1
    },
    "timestamp": "2024-01-15 14:30:05"
}
```

**Example Error Response**:
```json
{
    "status": "F",
    "message": "Validation error: timestamp or requestID is mandatory",
    "errors": {
        "timestamp": "Either timestamp or requestID must be provided"
    },
    "timestamp": "2024-01-15 14:30:00"
}
```

---

### 2. Service Consumption - Get Facility Information

#### Webservice Mechanism
- **Protocol**: RESTFUL (JSON-based)
- **Function**: Retrieves facility information by facility ID
- **Source Module**: Booking Management Module
- **Target Module**: Facility Management Module
- **URL**: `http://[base-url]/api/facilities/service/get-info`
- **Function Name**: `getFacilityInfo`
- **HTTP Method**: POST

#### Web Services Request Parameter

| Field Name | Field Type | Mandatory/Optional | Description | Format |
|------------|------------|-------------------|-------------|--------|
| facility_id | Integer | Mandatory | Unique ID of the facility | Numeric value, must exist in facilities table |
| timestamp | String | Mandatory* | Time when the request was made | YYYY-MM-DD HH:MM:SS |
| requestID | String | Mandatory* | Unique identifier for the request | Alphanumeric string |

**Note**: Either `timestamp` OR `requestID` must be provided (at least one is mandatory).

**Example Request**:
```json
{
    "facility_id": 10,
    "timestamp": "2024-01-15 14:30:00"
}
```

#### Web Services Response Parameter

| Field Name | Field Type | Mandatory/Optional | Description | Format |
|------------|------------|-------------------|-------------|--------|
| status | String | Mandatory | Status of the request | S: Success, F: Fail, E: Error |
| message | String | Optional | Human-readable message | String |
| data | Object | Mandatory | Facility information object | Contains facility details |
| data.facility | Object | Mandatory | Detailed facility information | Facility object with all attributes |
| data.capacity | Integer | Mandatory | Maximum capacity of the facility | Numeric |
| data.status | String | Mandatory | Current status of the facility | available, unavailable, maintenance |
| timestamp | String | Mandatory | Time when the response was generated | YYYY-MM-DD HH:MM:SS |

**Example Success Response**:
```json
{
    "status": "S",
    "message": "Facility information retrieved successfully",
    "data": {
        "facility": {
            "id": 10,
            "name": "Basketball Court A",
            "code": "BCA001",
            "type": "sports",
            "location": "Building A, Floor 2",
            "capacity": 20,
            "status": "available",
            "enable_multi_attendees": true,
            "max_attendees": 20
        },
        "capacity": 20,
        "status": "available"
    },
    "timestamp": "2024-01-15 14:30:05"
}
```

---

## Code Implementation

### 1. Service Exposure Implementation

**File**: `app/Http/Controllers/API/BookingController.php`

**Method**: `getBookingInfo()`

```php
/**
 * Web Service API: Get booking information
 * This endpoint is designed for inter-module communication
 * Used by other modules (e.g., Analytics Module, Reporting Module) to query booking information
 * 
 * IFA Standard Compliance:
 * - Request must include timestamp or requestID (mandatory)
 * - Response includes status and timestamp (mandatory)
 */
public function getBookingInfo(Request $request)
{
    // IFA Standard: Validate mandatory fields (timestamp or requestID)
    if (!$request->has('timestamp') && !$request->has('requestID')) {
        return response()->json([
            'status' => 'F',
            'message' => 'Validation error: timestamp or requestID is mandatory',
            'errors' => [
                'timestamp' => 'Either timestamp or requestID must be provided',
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ], 422);
    }

    $request->validate([
        'booking_id' => 'required|exists:bookings,id',
    ]);

    $booking = Booking::with(['user', 'facility', 'attendees', 'slots', 'approver'])
        ->findOrFail($request->booking_id);

    // IFA Standard Response Format
    return response()->json([
        'status' => 'S', // S: Success, F: Fail, E: Error (IFA Standard)
        'message' => 'Booking information retrieved successfully',
        'data' => [
            'booking' => [
                'id' => $booking->id,
                'user_id' => $booking->user_id,
                'user_name' => $booking->user->name ?? null,
                'user_email' => $booking->user->email ?? null,
                'facility_id' => $booking->facility_id,
                'facility_name' => $booking->facility->name ?? null,
                'facility_code' => $booking->facility->code ?? null,
                'booking_date' => $booking->booking_date ? $booking->booking_date->format('Y-m-d') : null,
                'start_time' => $booking->start_time ? $booking->start_time->format('Y-m-d H:i:s') : null,
                'end_time' => $booking->end_time ? $booking->end_time->format('Y-m-d H:i:s') : null,
                'duration_hours' => $booking->duration_hours,
                'purpose' => $booking->purpose,
                'status' => $booking->status,
                'expected_attendees' => $booking->expected_attendees,
                'approved_by' => $booking->approved_by,
                'approved_at' => $booking->approved_at ? $booking->approved_at->format('Y-m-d H:i:s') : null,
                'cancelled_at' => $booking->cancelled_at ? $booking->cancelled_at->format('Y-m-d H:i:s') : null,
                'created_at' => $booking->created_at ? $booking->created_at->format('Y-m-d H:i:s') : null,
            ],
            'attendees_count' => $booking->attendees->count(),
            'slots_count' => $booking->slots->count(),
        ],
        'timestamp' => now()->format('Y-m-d H:i:s'), // IFA Standard: Mandatory timestamp
    ]);
}
```

**Route Registration**: `routes/api.php`

```php
// Inter-module Web Service Routes (for module-to-module communication)
Route::post('/bookings/service/get-info', [BookingController::class, 'getBookingInfo']);
```

**Description**:
- This method validates that the request includes either a `timestamp` or `requestID` field (IFA requirement)
- Validates that the `booking_id` exists in the database
- Retrieves the booking with all related data (user, facility, attendees, slots, approver)
- Returns a standardized JSON response following IFA standards with status and timestamp
- Handles errors gracefully with appropriate status codes

**Real-World Usage Example - Feedback Module**:

The Feedback Module consumes this service in the `getBookingDetailsForFeedback()` method:

```php
// Use Web Service to get booking information from Booking Module
$baseUrl = config('app.url', 'http://localhost:8000');
$apiUrl = rtrim($baseUrl, '/') . '/api/bookings/service/get-info';

$response = Http::timeout(10)->post($apiUrl, [
    'booking_id' => $feedback->booking_id,
    'timestamp' => now()->format('Y-m-d H:i:s'),
]);

if ($response->successful()) {
    $data = $response->json();
    if ($data['status'] === 'S') {
        // Use booking information from $data['data']['booking']
        return response()->json([
            'status' => 'S',
            'data' => [
                'feedback' => [...],
                'booking' => $data['data']['booking'], // Booking info from web service
            ],
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}
// Fallback to direct query if web service fails
```

**Benefits**:
- **Modularity**: Feedback Module doesn't need direct access to Booking database tables
- **Consistency**: Ensures all modules use the same booking data format
- **Maintainability**: Changes to booking data structure only need to be updated in one place
- **Reliability**: Includes fallback mechanism if web service is temporarily unavailable

---

### 2. Service Consumption Implementation

**File**: `app/Http/Controllers/API/BookingController.php`

**Method**: `store()` - Booking Creation

```php
//Service Consumption: Get facility info via HTTP from Facility Management Module
$baseUrl = config('app.url', 'http://localhost:8000');
$apiUrl = rtrim($baseUrl, '/') . '/api/facilities/service/get-info';

$facilityResponse = Http::timeout(10)->post($apiUrl, [
    'facility_id' => $facilityId,
    'timestamp' => now()->format('Y-m-d H:i:s'), 
]);

if (!$facilityResponse->successful()) {
    Log::warning('Failed to get facility from Facility Management Module', [
        'status' => $facilityResponse->status(),
        'response' => $facilityResponse->body(),
    ]);
    // Fallback to direct query
    $facility = Facility::find($facilityId);
} else {
    $facilityData = $facilityResponse->json();
    if ($facilityData['status'] === 'S' && isset($facilityData['data']['facility'])) {
        // Convert array to Facility model instance
        $facility = Facility::find($facilityId);
    } else {
        $facility = Facility::find($facilityId);
    }
}
```

**Description**:
- Uses Laravel's HTTP client to make a POST request to the Facility Management Module
- Includes mandatory `timestamp` field in the request (IFA compliance)
- Sets a 10-second timeout to prevent hanging requests
- Implements fallback mechanism: if the HTTP call fails, falls back to direct database query
- Logs warnings when external service calls fail for monitoring and debugging
- Validates the response status before processing

**Method**: `checkAvailability()` - Availability Check

```php
//Service Consumption Get facility info via HTTP from Facility Management Module
$baseUrl = config('app.url', 'http://localhost:8000');
$apiUrl = rtrim($baseUrl, '/') . '/api/facilities/service/get-info';

$facilityResponse = Http::timeout(10)->post($apiUrl, [
    'facility_id' => $facilityId,
    'timestamp' => now()->format('Y-m-d H:i:s'), 
]);

if (!$facilityResponse->successful()) {
    Log::warning('Failed to get facility from Facility Management Module', [
        'status' => $facilityResponse->status(),
    ]);
    // Fallback to direct query
    $facility = Facility::findOrFail($facilityId);
} else {
    $facilityData = $facilityResponse->json();
    if ($facilityData['status'] === 'S' && isset($facilityData['data']['facility'])) {
        $facility = Facility::findOrFail($facilityId);
    } else {
        $facility = Facility::findOrFail($facilityId);
    }
}
```

**Description**:
- Similar implementation to the `store()` method
- Consumes the same facility service endpoint
- Used during availability checks to ensure accurate facility information
- Maintains consistency in service consumption patterns across the module

---

## Testing Examples

### 1. Testing Service Exposure - Get Booking Information

#### Using cURL:

```bash
curl -X POST http://localhost:8000/api/bookings/service/get-info \
  -H "Content-Type: application/json" \
  -d '{
    "booking_id": 123,
    "timestamp": "2024-01-15 14:30:00"
  }'
```

#### Real-World Usage in Feedback Module:

When a user views feedback details that are associated with a booking, the Feedback Module automatically calls this service:

**Frontend Request** (JavaScript):
```javascript
// In public/js/feedbacks/show.js
const result = await API.get(`/feedbacks/${feedbackId}/booking-details?timestamp=${timestamp}`);
// This internally calls the Booking Web Service
```

**Backend Implementation** (FeedbackController):
```php
// In app/Http/Controllers/API/FeedbackController.php
$response = Http::timeout(10)->post($apiUrl, [
    'booking_id' => $feedback->booking_id,
    'timestamp' => now()->format('Y-m-d H:i:s'),
]);
```

#### Using PHP (Laravel HTTP Client):

```php
use Illuminate\Support\Facades\Http;

$response = Http::post('http://localhost:8000/api/bookings/service/get-info', [
    'booking_id' => 123,
    'timestamp' => now()->format('Y-m-d H:i:s'),
]);

$data = $response->json();
if ($data['status'] === 'S') {
    $booking = $data['data']['booking'];
    echo "Booking ID: " . $booking['id'];
    echo "User: " . $booking['user_name'];
    echo "Facility: " . $booking['facility_name'];
}
```

#### Using JavaScript (Fetch API):

```javascript
fetch('http://localhost:8000/api/bookings/service/get-info', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
    },
    body: JSON.stringify({
        booking_id: 123,
        timestamp: new Date().toISOString().slice(0, 19).replace('T', ' ')
    })
})
.then(response => response.json())
.then(data => {
    if (data.status === 'S') {
        console.log('Booking:', data.data.booking);
    } else {
        console.error('Error:', data.message);
    }
});
```

### 2. Testing Service Consumption - Get Facility Information

#### Internal Usage in BookingController:

The service consumption is automatically triggered when:
1. A user creates a new booking (`store()` method)
2. A user checks facility availability (`checkAvailability()` method)

#### Manual Testing:

```php
// In a controller or service class
use Illuminate\Support\Facades\Http;

$baseUrl = config('app.url', 'http://localhost:8000');
$apiUrl = rtrim($baseUrl, '/') . '/api/facilities/service/get-info';

$response = Http::timeout(10)->post($apiUrl, [
    'facility_id' => 10,
    'timestamp' => now()->format('Y-m-d H:i:s'),
]);

if ($response->successful()) {
    $data = $response->json();
    if ($data['status'] === 'S') {
        $facility = $data['data']['facility'];
        // Use facility information
    }
}
```

---

## Error Handling

### Service Exposure Errors

1. **Missing Timestamp/RequestID**: Returns status 'F' with 422 status code
2. **Invalid Booking ID**: Returns status 'F' with 404 status code (Laravel's findOrFail)
3. **Database Errors**: Returns status 'E' with 500 status code

### Service Consumption Errors

1. **Network Timeout**: Falls back to direct database query, logs warning
2. **Service Unavailable**: Falls back to direct database query, logs warning
3. **Invalid Response**: Falls back to direct database query, logs warning

---

## Security Considerations

1. **No Authentication Required**: Inter-module services are designed for backend-to-backend communication
2. **Input Validation**: All inputs are validated before processing
3. **SQL Injection Protection**: Uses Laravel's Eloquent ORM which provides built-in protection
4. **Error Information**: Error messages are informative but don't expose sensitive system details
5. **Request Tracking**: Timestamp/requestID fields enable request tracking and auditing

---

## Conclusion

The Booking Module successfully implements both service exposure and consumption following IFA standards. The module:

- ✅ Exposes a REST API endpoint for other modules to retrieve booking information
- ✅ Consumes facility information from the Facility Management Module
- ✅ Adheres to IFA standards with mandatory timestamp/requestID fields
- ✅ Implements proper error handling and fallback mechanisms
- ✅ Provides comprehensive documentation and code examples

This implementation promotes modularity, maintainability, and seamless integration between different system components.

---

**Document Version**: 1.0  
**Last Updated**: 2024-01-15  
**Module**: Booking Management Module

