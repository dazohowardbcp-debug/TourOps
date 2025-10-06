# Demo Script (10–12 minutes)

This guide outlines a smooth flow to demo TourOps to your instructor. Adjust timings as needed.

## 0) Prep (30–60s)
- Log in as Admin: `admin@tourops.local / admin123`
- Ensure demo data:
  - Visit `/admin/seed_packages.php?count=36`
  - Visit `/admin/seed_users.php?count=36`

## 1) Landing and Packages (2 min)
- Open `packages.php` (React grid).
  - Point out consistent thumbnails and card layout.
  - Mention client-friendly UX.
- Open one `package.php?id=...`.
  - Show average rating + review count.
  - Scroll to recent reviews section.

## 2) Booking flow (2–3 min)
- From `package.php`, demonstrate a booking:
  - Fill the form (date, pax, contact).
  - Submit and show success.
- Open `itinerary.php` (user panel) to show the booking card.
  - Note client “See more” on this grid if many bookings.

## 3) Reviews (1–2 min)
- In `itinerary.php`, for a completed booking, click “Leave Review”.
  - Submit a rating + optional review.
- Open `reviews.php` (public reviews):
  - Show rating/package filters.
  - Show server-side pagination (Prev/Next under the grid).

## 4) Admin Panel (3–4 min)
- `admin/index.php`: stats snapshot.
- `admin/packages.php`:
  - Compact sticky table + page-size selector (10/15/30/50).
  - Prev/Next with current page indicator.
- `admin/bookings.php`:
  - Update booking status and payment. Show notes.
  - Pagination + selector.
- `admin/users.php`:
  - Toggle Make/Remove Admin, delete only if no bookings.
  - Pagination + selector.
- `admin/feedback.php`:
  - Moderate reviews (Add Reply / Delete).
  - Pagination row placed directly under the last review.

## 5) Security (30–45s)
- CSRF tokens on forms (`inc/config.php`: `csrf_token()`, `verify_csrf_token()`).
- Prepared statements across DB access (`inc/db.php` usage).
- Session checks on admin routes.

## 6) Code & Docs tour (30–45s)
- `README.md`: demo credentials, quick start, required vs optional files.
- `DOCS/FILES_AND_PAGES_OVERVIEW.md`: purpose of pages and where pagination/see-more appear.

## Q&A (time left)
- Be ready to explain:
  - Why server pagination vs client “See more”.
  - How CSRF token is generated/verified.
  - How to change page size defaults.

## Useful Links
- Packages: `/packages.php`
- Package: `/package.php?id=1`
- Itinerary: `/itinerary.php`
- Reviews: `/reviews.php`
- Admin: `/admin/index.php`
- Seed packages: `/admin/seed_packages.php?count=36`
- Seed users: `/admin/seed_users.php?count=36`
