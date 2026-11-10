# CargoTrace: Code Ownership Interview Preparation Guide

This guide prepares you for the **UAS Presentation & Code Ownership Interview (Slide 19)**. The lecturer may pick random files, functions, or ask you to explain design decisions.

---

## 1. Core Architecture Questions

### Q: Why is there a Service Layer instead of placing code directly in Controllers?
**Answer**:
Controllers should solely handle HTTP concerns (parsing requests, returning responses/views). Business rules such as:
- Calculating delay severities and durations (`EtaService`),
- Enforcing valid shipment state transitions and generating sequential references (`ShipmentService`),
- Fetching external weather telemetry and writing chronological checkpoints (`TrackingService`),
belong in the **Domain Service Layer**. This makes our business logic reusable across both the Blade Web application and the REST API endpoints, while making unit/feature testing straightforward without having to mock HTTP sessions.

### Q: Why does `Shipment` have a `hasMany` relationship with `TrackingEvent` and not overwrite the location?
**Answer**:
In freight logistics visibility, chronological traceability is mandatory. Overwriting a record loses history. By using `hasMany(TrackingEvent::class)`, we preserve an immutable event stream from origin departure to destination discharge, allowing us to reconstruct the exact historical polyline route on the Leaflet map and produce audit trails.

### Q: What prevents invalid status jumps (e.g. going from `CREATED` directly to `DELIVERED`, or changing a `DELIVERED` shipment)?
**Answer**:
In `app/Models/Shipment.php`, we define a state transition map:
```php
public const VALID_TRANSITIONS = [
    self::STATUS_CREATED => [self::STATUS_BOOKED, self::STATUS_CANCELLED],
    self::STATUS_BOOKED => [self::STATUS_PICKED_UP, self::STATUS_CANCELLED],
    self::STATUS_PICKED_UP => [self::STATUS_IN_TRANSIT, self::STATUS_CANCELLED],
    self::STATUS_IN_TRANSIT => [self::STATUS_DELIVERED, self::STATUS_CANCELLED],
    self::STATUS_DELIVERED => [],
    self::STATUS_CANCELLED => [],
];
```
Before transitioning, `ShipmentService::transitionStatus()` invokes `$shipment->canTransitionTo($targetStatus)`. If the transition is illegal, it throws a `DomainException`. Our global exception handler converts this to a clean HTTP 422 JSON response without leaking stack traces.

---

## 2. Business Rules & Logic Questions

### Q: How is delay detected and evaluated?
**Answer**:
Located in `app/Services/EtaService.php`:
1. We compute the difference between the reference arrival (`actual_arrival ?? estimated_arrival ?? expected_arrival`) and the scheduled `expected_arrival`.
2. If reference arrival is greater than expected arrival:
   - Difference $\le 6\text{ hours}$: `LOW` severity.
   - Difference $> 6\text{ hours}$ and $\le 24\text{ hours}$: `MEDIUM` severity.
   - Difference $> 24\text{ hours}$: `HIGH` severity.
3. The group threshold is loaded from `config('cargotrace.delay_threshold_hours')`, which reads from `.env` (`CARGOTRACE_DELAY_THRESHOLD_HOURS=12`).

### Q: How do you handle external API failures (Open-Meteo Weather API)?
**Answer**:
In `app/Services/WeatherService.php`:
- We use Laravel's HTTP Client with a 4-second timeout: `Http::timeout(4)->get(...)`.
- The call is wrapped in a `try/catch (Throwable $e)` block.
- If the external API times out, returns an error status, or if the server is offline/sandboxed, we catch the exception, log a warning, and return a safe cached fallback snapshot without interrupting the database transaction or crashing user actions.

---

## 3. Security & Authorization Questions

### Q: How do you prevent Customer A from viewing or modifying Customer B's shipments?
**Answer**:
We enforce multi-tiered security:
1. **Query Scoping**: In `ShipmentApiController::index()` and `ShipmentController::index()`, if `$user->isCustomer()`, the query is automatically scoped with `->where('customer_id', $user->id)`.
2. **Policy Authorization**: In `app/Policies/ShipmentPolicy.php`:
   ```php
   public function view(User $user, Shipment $shipment): bool
   {
       if ($user->isAdmin() || $user->isOperator()) return true;
       return $shipment->customer_id === $user->id;
   }
   ```
   If a customer attempts to access `/api/shipments/{id}` or `/shipments/{id}` belonging to another client, the policy aborts with HTTP 403 Forbidden.

### Q: How are API tokens managed?
**Answer**:
We use **Laravel Sanctum**. When a user logs in via `POST /api/login`, Sanctum generates a hashed personal access token (`plainTextToken`). Subsequent API requests pass this token in the `Authorization: Bearer <token>` header, verified by the `auth:sanctum` middleware.

---

## 4. Live Code Modification Cheatsheet (If Requested by Lecturer)

### Task 1: "Change the delay threshold for your group scenario"
- **Action**: Open `.env`, change:
  ```env
  CARGOTRACE_DELAY_THRESHOLD_HOURS=18
  ```
  *(or whatever number the lecturer requests)*.

### Task 2: "Add a new cargo type"
- **Action**: Open `config/cargotrace.php`, append:
  ```php
  'AEROSPACE' => 'Aerospace & Aviation Components',
  ```

### Task 3: "Run automated tests"
- **Action**: In terminal run:
  ```bash
  vendor/bin/phpunit
  ```
  All 22 tests will execute and display green checkmarks / OK status.
