# 🚀 TourOps - Tour Operations Management System

## 🎟️ Demo Credentials
- **Admin Email**: `admin@tourops.local`
- **Admin Password**: `admin123`
- After logging in, you can create/upgrade additional admins in `admin/users.php`.


A comprehensive web-based tour operations management system built with **PHP**, **MySQL**, **React**, and **Bootstrap 5**. Features a modern React frontend with AJAX/JSON API, complete CRUD operations, and a beautiful responsive interface for managing tour packages, bookings, users, and itineraries.

## ✨ Features

### 🎯 **Core Functionality**
- **User Dashboard** - Personalized dashboard with welcome message, quick links, and recent bookings
- **Tour Package Management** - Create, edit, and manage tour packages with rich descriptions
- **Advanced Booking System** - Complete booking workflow with React forms and AJAX
- **User Management** - Comprehensive user registration, login, and role-based access
- **Itinerary Management** - View and manage travel itineraries with status tracking
- **Admin Panel** - Full-featured admin dashboard with statistics and management tools

### 🔐 **Enhanced Booking System**
- **Travel Date Selection** - Required future date validation with Flatpickr calendar
- **Special Requests** - Capture customer special requirements and notes
- **Payment Status Tracking** - Pending, Partial, Paid, Cancelled with color coding
- **Admin Notes** - Add internal notes and updates to bookings
- **Booking Status Management** - Pending, Confirmed, Cancelled, Completed
- **Duplicate Prevention** - Smart duplicate booking detection with admin override
- **Pax Management** - 1-50 passengers with admin override capabilities

### 🎨 **Modern UI/UX with React**
- **React Components** - Modern React frontend for packages and booking forms
- **AJAX/JSON API** - Smooth, asynchronous data loading and form submissions
- **Responsive Design** - Works perfectly on all devices with Bootstrap 5
- **Interactive Elements** - Hover effects, animations, and smooth transitions
- **Professional Admin Interface** - Sidebar navigation with real-time statistics
- **Search Functionality** - Real-time search across all admin tables
- **Dark Mode Support** - Toggle between light and dark themes
- **Loading States** - Spinners and progress indicators for better UX

### 🛡️ **Security Features**
- **Password Hashing** - Secure bcrypt password storage
- **SQL Injection Protection** - Prepared statements throughout
- **Session Management** - Secure user authentication with auto-logout
- **Input Validation** - Client and server-side validation
- **XSS Protection** - HTML escaping for all output
- **CSRF Protection** - Token-based CSRF protection for forms
- **File Upload Security** - Secure profile picture uploads with validation

### 📱 **Advanced Features**
- **Profile Management** - Complete user profile with avatar upload
- **Login History** - Track user login activity and IP addresses
- **Notification System** - Toast notifications for user feedback
- **Performance Optimizations** - Lazy loading, content visibility, and asset optimization
- **Database Migration Tool** - Safe migration script for schema updates

## 🚀 Quick Start

### 1. **Prerequisites**
- PHP 7.4+ with PDO MySQL extension
- MySQL 5.7+ or MariaDB 10.2+
- Web server (Apache/Nginx) or XAMPP/WAMP
- Modern web browser with JavaScript enabled

### 2. **Installation**

#### **Option A: Fresh Installation**
1. **Clone/Download** the project to your web server directory
2. **Create Database**: Run `sql/tourops.sql` in your MySQL server
3. **Configure Database**: Update `inc/config.php` with your database credentials
4. **Access**: Navigate to your project URL in a web browser

#### **Option B: Update Existing Database**
1. **Run Migration Tool**: Visit `migrate_database.php` in your browser
2. **Click "Run Database Migration"** to update your existing database
3. **Verify**: Check that all new columns were added successfully

### 3. **Database Configuration**
Edit `inc/config.php`:
```php
// Database configuration
define('DB_HOST', 'localhost');     // Your database host
define('DB_NAME', 'tourops');       // Your database name
define('DB_USER', 'root');          // Your database username
define('DB_PASS', '');              // Your database password
```

### 4. **Default Admin Account**
- **Email**: admin@tourops.local
- **Password**: admin123
- **⚠️ Important**: Change this password immediately after first login!

## 🧪 Demo & Presentation

### Seed Demo Data (optional)
- Packages: visit `/admin/seed_packages.php?count=36`
- Users: visit `/admin/seed_users.php?count=36`

These admin-only seeders quickly populate data so pagination and lists are easy to demo.

### Suggested Demo Flow
1. Browse packages (`/packages.php`) and open a package details page.
2. Make a booking, then view it in the user panel (`/itinerary.php`).
3. Leave a review from the user panel; view all reviews at `/reviews.php` with filters + pagination.
4. Open Admin → Packages/Users/Bookings/Feedback to show server-side pagination and compact sticky tables.
5. In Admin → Feedback, demonstrate replying to a review; pagination appears under the last row.

### Page Size Selector (Admin)
Admin lists support changing page size (10/15/30/50). Use the selector above each table; pagination links preserve your selection.

## 📦 Required vs Optional Files

### Required (Core runtime)
- `index.php`, `packages.php`, `package.php`, `itinerary.php`, `reviews.php`
- `inc/config.php`, `inc/db.php`, `inc/header.php`, `inc/footer.php`
- `assets/css/styles.css`, `assets/js/main.js`
- Database schema (`sql/tourops.sql`)

### Admin (core features)
- `admin/index.php`, `admin/packages.php`, `admin/bookings.php`, `admin/users.php`, `admin/feedback.php`
- `admin/header.php`, `admin/footer.php`

### Optional (helpers and seeders)
- Seeders: `admin/seed_packages.php`, `admin/seed_users.php`
- Migration: `migrate_database.php`
- Docs: `README.md`, additional `*.md` guides

### Creating a Demo Admin (no seeder)
- Option A (SQL): after registering a user, mark as admin:
  ```sql
  UPDATE users SET is_admin = 1 WHERE email = 'your.email@example.com';
  ```
- Option B (existing admin): log in as any admin and toggle another user to Admin in `admin/users.php`.

## 📁 Project Structure

```
tourops/
├── admin/                  # Admin panel files
│   ├── header.php         # Admin layout header
│   ├── footer.php         # Admin layout footer
│   ├── index.php          # Admin dashboard
│   ├── bookings.php       # Booking management with status updates
│   ├── packages.php       # Package management with CRUD
│   └── users.php          # User management with role control
├── api/                    # AJAX API endpoints
│   ├── book.php           # Booking API with validation
│   └── packages.php       # Packages API with search/pagination
├── assets/                 # Static assets
├── dashboard.php           # User dashboard with recent bookings and quick links
│   ├── css/
│   │   └── styles.css     # Enhanced custom styles with dark mode
│   ├── js/
│   │   ├── main.js        # Enhanced JavaScript utilities
│   │   └── react-app.js   # React components for frontend
│   ├── uploads/           # User uploads directory
│   └── favicon.ico        # Site favicon
├── inc/                    # Include files
│   ├── config.php         # Centralized configuration
│   ├── db.php             # Database connection
│   ├── header.php         # Main site header with navigation
│   └── footer.php         # Main site footer
├── sql/                    # Database files
│   └── tourops.sql        # Complete database schema with seeds
├── index.php               # Homepage with carousel
├── packages.php            # Package listing with React
├── package.php             # Individual package view with booking form
├── itinerary.php           # Itinerary management
├── login.php               # User login with validation
├── register.php            # User registration with comprehensive fields
├── profile.php             # User profile management
├── logout.php              # User logout
├── migrate_database.php    # Database migration tool
└── README.md               # This documentation
```

## 🎮 Usage Guide

### **For Tourists/Users**
1. **Browse Packages** - View available tour packages with React-powered interface
2. **Book Tours** - Use React booking form with real-time validation
3. **View Itinerary** - Check booking status and travel details
4. **Manage Profile** - Update personal information and avatar
5. **View History** - Access booking history and login activity

### **For Administrators**
1. **Dashboard** - Overview of system statistics and recent activity
2. **Manage Bookings** - Update status, payment info, pax, and add notes
3. **Manage Packages** - Create, edit, and delete tour packages with rich content
4. **Manage Users** - User administration, role management, and profile viewing
5. **System Monitoring** - Track user activity and system performance

## 🔧 Customization

### **Adding New Fields**
1. **Database**: Add columns to relevant tables
2. **API**: Update API endpoints in `api/` directory
3. **React Components**: Modify React components in `assets/js/react-app.js`
4. **Forms**: Update forms and validation
5. **Display**: Update display components and admin interfaces

### **Styling**
- **CSS Variables**: Modify colors and themes in `assets/css/styles.css`
- **Dark Mode**: Customize dark mode colors and transitions
- **Bootstrap**: Override Bootstrap classes as needed
- **Responsive**: Mobile-first design approach

### **React Components**
- **Package Cards**: Customizable package display components
- **Booking Forms**: React-powered forms with validation
- **Search**: Real-time search with debouncing

### **API Endpoints**
- **RESTful Design**: Clean API endpoints for data operations
- **JSON Responses**: Consistent JSON response format
- **Error Handling**: Comprehensive error handling and validation
- **CSRF Protection**: Secure API endpoints with CSRF tokens

## 🚨 Security Considerations

### **Production Deployment**
1. **Change Default Passwords** - Update admin and database passwords
2. **HTTPS** - Enable SSL/TLS encryption
3. **Database Security** - Use dedicated database user with minimal privileges
4. **File Permissions** - Restrict access to sensitive files
5. **Regular Updates** - Keep PHP and dependencies updated
6. **CSRF Protection** - All forms protected with CSRF tokens
7. **Input Validation** - Comprehensive validation on all inputs

### **Data Protection**
- **Input Sanitization** - All user inputs are validated and sanitized
- **Output Escaping** - HTML entities for XSS protection
- **Session Security** - Secure session handling with auto-logout
- **SQL Injection Prevention** - Prepared statements throughout
- **File Upload Security** - Secure file uploads with type validation

## 🐛 Troubleshooting

### **Common Issues**

#### **Database Connection Error**
- Verify database credentials in `inc/config.php`
- Ensure MySQL service is running
- Check database exists and is accessible

#### **Migration Errors**
- Run `migrate_database.php` for existing databases
- Check MySQL version compatibility
- Verify database user has ALTER privileges

#### **React Components Not Loading**
- Check browser console for JavaScript errors
- Verify React and ReactDOM are loaded
- Check API endpoints are accessible

#### **CSS/JS Not Loading**
- Check file paths in header/footer files
- Verify file permissions
- Clear browser cache

#### **Admin Access Issues**
- Ensure user has `is_admin = 1` in database
- Check session configuration
- Verify admin file permissions

### **Debug Mode**
Enable debug mode in `inc/config.php`:
```php
define('DEBUG_MODE', true);
```

## 📊 Database Schema

### **Users Table**
- `id`, `name`, `email`, `password`, `is_admin`, `created_at`
- `fullname`, `dob`, `gender`, `nationality`, `mobile`, `address`, `username`
- `emergency_name`, `emergency_relation`, `emergency_phone`
- `profile_image`, `two_factor_enabled`, `notify_email`, `notify_sms`, `newsletter`

### **Packages Table**
- `id`, `title`, `description`, `days`, `duration`, `price`, `highlights`, `image`
- `image_url`, `location`, `group_size`, `created_at`

### **Bookings Table**
- `id`, `user_id`, `guest_name`, `guest_email`, `package_id`, `pax`, `total`
- `travel_date`, `special_requests`, `payment_status`, `status`, `notes`, `created_at`

### **User Logins Table**
- `id`, `user_id`, `ip`, `user_agent`, `created_at`

## 🔄 Updates & Maintenance

### **Regular Tasks**
1. **Backup Database** - Regular database backups
2. **Monitor Logs** - Check error logs for issues
3. **Update Dependencies** - Keep React, Bootstrap and other libraries updated
4. **Security Audits** - Regular security reviews
5. **Performance Monitoring** - Monitor API response times and user experience

### **Adding Features**
1. **Plan Database Changes** - Design new tables/columns
2. **Update API** - Add new API endpoints for functionality
3. **Update React Components** - Add new React components for UI
4. **Update Backend** - Modify PHP files for new functionality
5. **Test Thoroughly** - Test all new features before deployment

## 📞 Support

### **Getting Help**
1. **Check Documentation** - Review this README thoroughly
2. **Review Code** - Examine similar functionality in existing files
3. **Database Queries** - Use `DESCRIBE table_name` to understand structure
4. **Error Logs** - Check web server and PHP error logs
5. **Browser Console** - Check for JavaScript errors in browser console

### **Contributing**
1. **Fork the Project** - Create your own copy
2. **Make Changes** - Implement improvements
3. **Test Thoroughly** - Ensure everything works
4. **Submit Changes** - Share your improvements

## 🆕 Recent Updates

### **Version 2.0 - Major Improvements**
- ✅ **React Integration** - Modern React frontend for packages and booking
- ✅ **AJAX/JSON API** - RESTful API endpoints for smooth interactions
- ✅ **Enhanced Database Schema** - Comprehensive user profiles and package details
- ✅ **Advanced Admin Panel** - Improved admin interface with better UX
- ✅ **Security Enhancements** - CSRF protection, improved validation
- ✅ **Performance Optimizations** - Lazy loading, content visibility
- ✅ **Dark Mode Support** - Toggle between light and dark themes
- ✅ **Migration Tool** - Safe database migration for existing installations
- ✅ **Profile Management** - Complete user profile with avatar upload
- ✅ **Login History** - Track user login activity

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 🙏 Acknowledgments

- **React** - For the modern frontend framework
- **Bootstrap** - For the responsive UI framework
- **PHP Community** - For excellent documentation and examples
- **MySQL** - For reliable database management
- **Open Source Community** - For inspiration and best practices

---

**🎉 Happy Tour Operations Management! 🎉**

Built with ❤️ for the travel industry using modern web technologies.

