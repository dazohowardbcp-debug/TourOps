# Files & Pages Overview

This document explains the purpose of the main files and directories in the TourOps project, and where pagination or “See more” behaviors are used.

## Quick Links (Routes)
- Public
  - `/` → `index.php`
  - `/packages.php`
  - `/package.php?id=...`
  - `/itinerary.php` (requires login)
  - `/reviews.php`
- Admin (requires admin login)
  - `/admin/index.php`
  - `/admin/packages.php`
  - `/admin/bookings.php`
  - `/admin/users.php`
  - `/admin/feedback.php`

## Public Pages
- `index.php`
  - Entry point. Typically shows hero/spotlight content and can include system stats.
- `packages.php`
  - Package listing UI powered by `assets/js/react-app.js`.
  - Client-friendly UX with grid cards and image wrappers.
- `package.php`
  - Single package details with booking form, average rating summary, and recent reviews.
- `itinerary.php`
  - User’s bookings (“My Bookings”) with actions (leave a review, cancel if allowed).
  - Uses client "See more" for the grid (`data-see-more="15"`).
- `reviews.php`
  - All public reviews with filters and server-side pagination (Prev/Next under the grid).

## Admin Pages
- `admin/index.php`
  - Dashboard: quick stats and links.
- `admin/packages.php`
  - Manage tour packages (create/edit/delete).
  - Server-side pagination + page-size selector (10/15/30/50).
  - Compact tables with sticky headers.
- `admin/bookings.php`
  - Manage bookings: status, payment, admin notes.
  - Server-side pagination + page-size selector.
  - Compact sticky table.
- `admin/users.php`
  - Manage users: role toggle (Make/Remove Admin), delete if no bookings.
  - Server-side pagination + page-size selector.
  - Compact sticky table.
- `admin/feedback.php`
  - Moderate reviews: add admin reply, delete review.
  - Server-side pagination (10 per page), controls placed under the last row (inline in table).
  - Compact sticky table.

## Includes
- `inc/config.php`
  - Site configuration, helpers, URL building, CSRF helpers.
- `inc/db.php`
  - PDO connection and database bootstrap.
- `inc/header.php` / `inc/footer.php`
  - Shared layout wrappers for public pages.
- `admin/header.php` / `admin/footer.php`
  - Shared layout wrappers for admin pages.

## Assets
- `assets/css/styles.css`
  - Theme, utilities, image thumbnail helpers, `.table-compact` (reduced paddings) and `.sticky-head` (sticky table headers and scroll area).
- `assets/js/main.js`
  - UI initializer, toasts, validation helpers, `initSeeMore()` (generic client-side reveal for any element with `data-see-more`), etc.
- `assets/js/react-app.js`
  - React components used on `packages.php` for grid and front-end UX.

## Data Seeders & Tools (Optional)
- `admin/seed_packages.php` – Insert dummy packages for demo.
- `admin/seed_users.php` – Insert dummy users for demo.
- `migrate_database.php` – Database migration helper.

## Pagination vs “See more”
- Server-side pagination (Prev/Next + page-size selector)
  - Admin: `admin/packages.php`, `admin/users.php`, `admin/bookings.php`, `admin/feedback.php` (10 per page, controls inline under last row)
  - Public: `reviews.php` (Prev/Next under the grid)
- Client-side "See more" (generic `data-see-more`)
  - Public: `itinerary.php` (bookings grid)
  - Any container/table can opt-in by adding `data-see-more="<batch>"` and `uiEnhancer.initSeeMore()` in `assets/js/main.js` will inject a button below the element.

## Security & Forms
- CSRF-protected forms on public and admin actions (tokens included and verified).
- Prepared statements for all DB interactions.

## Notes
- Page-size selectors use the `pageSize` query parameter and preserve it in Prev/Next links.
- Sticky headers are enabled by wrapping tables with `.table-responsive.sticky-head`.
