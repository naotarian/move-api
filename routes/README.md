# Routes Structure

This directory contains all the route definitions for the application.

## File Structure

```
routes/
├── api.php           # Main API routes (estimates, admin, store, etc.)
├── organization.php  # Organization-specific API routes
├── web.php          # Web routes (if any)
└── console.php      # Console/Artisan command routes
```

## Organization Routes

The organization routes are separated into their own file for better maintainability.

**File:** `routes/organization.php`  
**URL Prefix:** `/api/organization`  
**Middleware:** `api`

### Available Endpoints

| Method | Endpoint                   | Description                         | Auth Required |
| ------ | -------------------------- | ----------------------------------- | ------------- |
| POST   | `/api/organization/login`  | Organization login                  | No            |
| POST   | `/api/organization/logout` | Organization logout                 | Yes           |
| GET    | `/api/organization/me`     | Get authenticated organization info | Yes           |
| GET    | `/api/organization/stores` | Get organization's store list       | Yes           |

### Configuration

The organization routes are registered in `bootstrap/app.php`:

```php
->withRouting(
    // ... other routes ...
    then: function () {
        Route::prefix('api/organization')
            ->middleware('api')
            ->group(base_path('routes/organization.php'));
    },
)
```

## Testing Routes

You can test the organization authentication endpoints using curl or any API testing tool:

```bash
# Login
curl -X POST http://localhost/api/organization/login \
  -H "Content-Type: application/json" \
  -d '{"email":"org1@moving-auction.local","password":"org123"}'

# Get authenticated organization info
curl -X GET http://localhost/api/organization/me \
  -H "Authorization: Bearer YOUR_TOKEN"

# Logout
curl -X POST http://localhost/api/organization/logout \
  -H "Authorization: Bearer YOUR_TOKEN"
```

## Adding New Organization Routes

1. Open `routes/organization.php`
2. Add your route within the appropriate middleware group:
    - **No auth required:** Add directly to the file
    - **Auth required:** Add inside the `auth:organization` middleware group

Example:

```php
// No auth required
Route::post('/register', [OrganizationAuthController::class, 'register']);

// Auth required
Route::middleware('auth:organization')->group(function () {
    Route::get('/dashboard', [OrganizationDashboardController::class, 'index']);
    Route::get('/stores', [OrganizationStoreController::class, 'index']);
});
```
