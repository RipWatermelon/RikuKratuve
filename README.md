# Rīku Krātuve

A PHP + SQLite school lending platform for borrowing equipment, books, and learning materials.

## Features
- User registration for students and teachers
- Inventory browsing with search and category filters
- Borrow request flow with auto-approval for school-owned items
- User profile page with password updates, notifications, and ratings
- Rating threshold that removes access below 3 stars
- Simple SQLite database split into `users`, `items`, and `current_lends`

## Run locally
1. Open a terminal in the project folder.
2. Start the PHP built-in server:
   ```bash
   php -S localhost:8000
   ```
3. Open http://localhost:8000

## Default admin account
- Username: admin
- Password: admin123

## Database files
The app stores data in `data/`:
- `users.sqlite`
- `items.sqlite`
- `current_lends.sqlite`
