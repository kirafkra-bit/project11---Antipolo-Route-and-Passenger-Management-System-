# PoloNav System Flowchart

The diagram below describes the current application flow for the PoloNav
Antipolo jeepney route and passenger management system.

```mermaid
flowchart TD
    Start([User opens PoloNav]) --> Landing[Public landing page]
    Landing --> Existing{Already authenticated?}
    Existing -- No --> Choice{Choose an action}
    Existing -- Yes --> RoleRedirect[Read session role]

    Choice -->|Create account| Register[Registration form]
    Choice -->|Sign in| Login[Sign-in form]
    Choice -->|Learn more| Landing

    Register --> AccountType{Account type}
    AccountType -->|Passenger| PassengerForm[Enter passenger details and valid ID]
    AccountType -->|Driver| DriverForm[Enter driver details and verification documents]
    PassengerForm --> RegisterAPI[Submit registration]
    DriverForm --> RegisterAPI
    RegisterAPI --> AccountCreated{Registration successful?}
    AccountCreated -- No --> RegistrationError[Show validation or server error]
    RegistrationError --> Register
    AccountCreated -- Yes --> Login

    Login --> LoginAPI[POST /api/login.php]
    LoginAPI --> Credentials{Credentials valid?}
    Credentials -- No --> LoginError[Show invalid credentials]
    LoginError --> Login
    Credentials -- Yes --> Session[Create secure session and CSRF token]
    Session --> RoleRedirect

    RoleRedirect --> Role{User role}
    Role -->|Passenger| PassengerDashboard[Passenger dashboard]
    Role -->|Driver| DriverDashboard[Driver dashboard]
    Role -->|Admin| AdminDashboard[Admin dashboard]

    PassengerDashboard --> PassengerAction{Passenger action}
    PassengerAction -->|Search route| Search[Enter pickup, destination, vehicle and passenger type]
    Search --> SearchAPI[GET /api/search.php]
    SearchAPI --> RouteResults[Show matching routes, jeepneys, drivers and estimated fare]
    RouteResults --> PassengerDashboard

    PassengerAction -->|Calculate fare| FareInput[Enter vehicle type, passenger type and distance or map points]
    FareInput --> FareAPI[POST /api/fare.php]
    FareAPI --> FareResult[Validate points and calculate fare]
    FareResult --> PassengerDashboard

    PassengerAction -->|Review trips| PassengerTrips[GET /api/trips.php]
    PassengerTrips --> PassengerDashboard

    PassengerAction -->|Submit complaint| ComplaintForm[Enter complaint category, subject and description]
    ComplaintForm --> ComplaintAPI[POST /api/complaints.php]
    ComplaintAPI --> ComplaintSaved[Save complaint as submitted]
    ComplaintSaved --> PassengerDashboard

    DriverDashboard --> DriverAction{Driver action}
    DriverAction -->|View assigned jeepney| Assigned[GET /api/jeepneys.php]
    Assigned --> DriverDashboard
    DriverAction -->|View own trips| DriverTrips[GET /api/trips.php]
    DriverTrips --> DriverDashboard
    DriverAction -->|Record trip| TripForm[Enter pickup, drop-off, passenger, jeepney and count]
    TripForm --> TripAPI[POST /api/trips.php]
    TripAPI --> TripValidation{Passenger, jeepney and assignment valid?}
    TripValidation -- No --> TripError[Show validation error]
    TripError --> TripForm
    TripValidation -- Yes --> TripSaved[Save trip linked to passenger, driver and jeepney]
    TripSaved --> DriverDashboard
    DriverAction -->|Calculate fare| FareInput

    AdminDashboard --> AdminAction{Admin action}
    AdminAction -->|Manage users| Users[Create, update, view or delete users]
    AdminAction -->|Manage passengers| Passengers[View and maintain passenger records]
    AdminAction -->|Manage drivers| Drivers[Create, update status, view or delete drivers]
    AdminAction -->|Manage routes| Routes[Create, update, view or delete routes]
    AdminAction -->|Manage jeepneys| Jeepneys[Assign jeepneys to drivers and routes]
    AdminAction -->|Manage trips| Trips[Create, update, view or delete trip records]
    AdminAction -->|Handle complaints| Complaints[Review complaints and update status or response]

    Users --> AdminAPI[Admin APIs with session role and CSRF validation]
    Passengers --> AdminAPI
    Drivers --> AdminAPI
    Routes --> AdminAPI
    Jeepneys --> AdminAPI
    Trips --> AdminAPI
    Complaints --> AdminAPI
    AdminAPI --> MySQL[(MySQL database)]
    MySQL --> AdminDashboard

    SearchAPI --> MySQL
    FareAPI --> FareCalculator[Fare calculator and configured rates]
    FareCalculator --> FareResult
    PassengerTrips --> MySQL
    ComplaintAPI --> MySQL
    Assigned --> MySQL
    DriverTrips --> MySQL
    TripAPI --> MySQL

    PassengerDashboard --> Logout{Logout?}
    DriverDashboard --> Logout
    AdminDashboard --> Logout
    Logout -- Yes --> LogoutAPI[POST /api/logout.php]
    LogoutAPI --> Landing
    Logout -- No --> Role

    classDef public fill:#e8f1ff,stroke:#2563eb,color:#172554
    classDef decision fill:#fff7ed,stroke:#ea580c,color:#431407
    classDef role fill:#ecfdf5,stroke:#059669,color:#064e3b
    classDef data fill:#f3e8ff,stroke:#9333ea,color:#3b0764
    class Landing,Choice,Register,Login,RegistrationError,LoginError,LogoutAPI public
    class Existing,AccountType,AccountCreated,Credentials,Role,PassengerAction,DriverAction,TripValidation,AdminAction,Logout decision
    class PassengerDashboard,DriverDashboard,AdminDashboard role
    class MySQL,FareCalculator data
```

## Role boundaries

- **Passenger:** can search routes, calculate fares, view personal trips, and
  submit or review personal complaints.
- **Driver:** can view assigned vehicles and record trips for the assigned
  driver identity.
- **Admin:** can manage users, passengers, drivers, routes, jeepneys, trips,
  and complaint resolution.
- **Shared controls:** authenticated API access, role checks, CSRF-protected
  writes, prepared database queries, and session-based redirects.
