# CivicTrack — Statewide Civic Issue Reporting & Resolution Management System

**Academic Minor Project | PHP | MySQL | HTML5 | CSS3 | JavaScript**

CivicTrack is a web-based civic issue reporting and tracking system that helps citizens report local civic problems and monitor their resolution online.

This project is developed as an academic prototype to demonstrate web development, database management, authentication, role-based access control and application security.

## 1. Project Objectives

- Provide an online platform for reporting civic issues.
- Allow citizens to track complaint progress.
- Help administrators manage complaints.
- Allow staff to handle complaints assigned to their municipalities.
- Maintain complaint status history and audit records.
- Demonstrate secure web application development.

## 2. Technologies Used

| Technology | Purpose |
|---|---|
| HTML5 | Page structure |
| CSS3 | Styling and responsive layouts |
| JavaScript | Client-side interactions |
| PHP | Backend development |
| MySQL | Database management |
| PDO | Secure database connectivity |
| XAMPP | Local Apache/PHP environment |
| MySQL Workbench | Database administration |

## 3. Main Features

### Citizen Module
- Citizen registration and login.
- Submit civic complaints.
- Select complaint category and district.
- Provide ward/area and location details.
- Upload supporting images.
- Receive a unique complaint tracking code.
- View personal complaints and their status.
- Track complaint progress.

### Administrator Module
- View and manage complaints.
- Update complaint status.
- Assign complaints to staff.
- Manage user roles and account activation.
- View complaint information and progress history.
- Maintain administrative audit records.

### Staff Module
- Access municipality-specific complaints.
- View assigned work within permitted municipality memberships.
- Update complaint progress and status.
- Restricted access to complaints outside authorised municipalities.

## 4. Security Features

- Password hashing using `password_hash()`.
- Password verification using `password_verify()`.
- PDO prepared statements.
- HTML output escaping.
- CSRF protection on state-changing forms.
- Session ID regeneration after login.
- Role-based access control.
- Login rate limiting.
- Image MIME-type and size validation.
- Random filenames for uploaded images.
- Audit logging for important actions.
- Municipality-based staff access restrictions.

## 5. Requirements

- Windows computer.
- XAMPP with Apache and PHP 8.1 or later.
- MySQL Server.
- MySQL Workbench.
- Modern web browser.
- Git (for development and version control).

## 6. Project Installation

1. Download or clone the CivicTrack repository.
2. Place the `CivicTrack` folder inside:

   `C:\xampp\htdocs\`

3. Start Apache using XAMPP Control Panel.
4. Start your MySQL Server.
5. Open MySQL Workbench and connect to your MySQL Server.
6. Create a database named `civictrack`.
7. Import the provided database structure file:

   `database/civictrack_structure.sql`

8. Copy `config.example.php` as `config.php` inside the `config` folder.
9. Update the database host, database name, username and password in `config.php`.
10. Ensure the required reference data and initial administrator account are provisioned before running the application.

**Important:** The included `civictrack_structure.sql` is a structure-only export. It does not contain existing users, complaints or reference-data records. A fresh installation requires appropriate database initialization and administrator provisioning.

## 7. Running the Application

Start Apache and MySQL Server, then open:

`http://localhost/CivicTrack/`

Log in using an authorised account.

## 8. Database Structure

The database contains tables for:

- Users
- Issues
- Issue updates
- Issue assignments
- Districts
- Municipalities
- Wards
- Staff memberships
- Departments
- Audit logs
- Supporting application data

The database structure is provided in:

`database/civictrack_structure.sql`

## 9. Project Scope

CivicTrack is an academic prototype using sample administrative data.

It is not an official government complaint service.

Integration with real municipal authorities requires permission and cooperation from the relevant authorities.

The current project focuses on Telangana urban local bodies, including districts, municipalities, municipal corporations and wards.

## 10. Future Enhancements

- Integration with authorised municipal systems.
- Email notifications for complaint updates.
- Interactive maps and location-based reporting.
- Improved municipality and ward selection.
- Advanced analytics and administrative reports.
- Public deployment with HTTPS.
- Additional security testing and monitoring.

## 11. Testing

The following workflows have been tested during development:

- Citizen registration and login.
- Duplicate email prevention.
- Complaint submission.
- Complaint tracking using unique complaint codes.
- Administrator complaint management.
- Staff assignment.
- Staff municipality-based access restrictions.
- Complaint status updates.
- Citizen tracking of status changes.
- Complaint resolution workflow.

Additional testing should be performed after every major change.

## 12. Academic Purpose

This project is developed as a Web Development Minor Project to demonstrate practical knowledge of frontend development, backend programming, database management, authentication, application security and software testing.

---

**Project Name:** CivicTrack  
**Project Type:** Web Development Minor Project  
**Backend:** PHP  
**Database:** MySQL  
**Environment:** XAMPP and MySQL Server