# CivicTrack — Statewide Civic Issue Reporting & Resolution Management System

> Student: KONTHAM JAGADEESH  
> Enrollment No.: 25CS003552  
> Branch: CSE (CYBER SECURITY - L&T)  
> Semester: III

## 1. Project title and introduction
CivicTrack is a web-based prototype that allows residents to report local civic problems and track their progress. Authorized staff can review reports, assign work and update issue status. The prototype uses sample administrative data and is not an official government complaint service.

## 2. Problem statement
People often face civic problems such as potholes, garbage, blocked drains, water-supply issues and broken streetlights. It can be difficult to report these problems and know whether they are being fixed. CivicTrack provides one place to report issues and check their status, helping staff organize and manage them.

## 3. Objectives
- Provide a simple form for residents to report civic issues with category, description, location and optional photo.
- Organize administrative data by state, district, municipality, ward and department.
- Generate a unique complaint ID and show status updates.
- Allow authorized staff to review, assign and update reports.
- Provide summary statistics for administrators.
- Use secure coding and access controls to protect accounts, reports and uploaded files.
- Demonstrate a prototype that could support real municipal participation after authorization.

## 4. Scope and requirements
### Intended users
- Citizen: registers, submits and tracks own reports.
- Staff: reviews reports assigned to an authorized municipality, adds notes and updates status.
- Administrator: oversees reports and manages user roles and sample administrative data.

### Functional requirements
Document the features that you have actually implemented and tested. Add any limitations.

### Non-functional requirements
Responsive interface, input validation, secure password storage, access control, maintainable PHP code and relational MySQL storage.

## 5. System design and architecture
Browser (HTML/CSS/JavaScript) → PHP application → MySQL database. PHP validates requests, checks sessions and roles, performs prepared SQL operations and renders pages.

## 6. Database design / ER diagram
Core entities: users, districts, municipalities, wards, departments, staff_memberships, categories, issues, issue_assignments, issue_updates, issue_images and audit_logs.

Suggested relationships:
- One user can submit many issues.
- One category can classify many issues.
- One district contains many municipalities.
- One municipality contains many wards and departments.
- Staff memberships connect users to municipalities.
- One issue has many status updates and images.
- Audit logs record key actions.

Insert an ER diagram image or diagram source here.

## 7. Implementation details and technologies
- Frontend: HTML5, CSS3, JavaScript
- Backend: PHP
- Database: MySQL
- Local environment: XAMPP and phpMyAdmin
- Version control: Git/GitHub

Describe each module and add screenshots from your running local website.

## 8. Testing and results
Add a test-case table with test ID, test description, input, expected result, actual result and pass/fail. Include screenshots of successful and unsuccessful tests. Do not mark tests as passed until you have run them.

## 9. Conclusion and future enhancements
Summarize what works in your tested build. Possible enhancements include municipality/ward dependent selectors, full reference-data management, email notifications, password reset, richer analytics, map coordinates, deployment and authorized municipal integration.

## 10. References
- Minor Project Guidelines, Web Development, October 2026.
- CivicTrack Mini Project Proposal supplied for this project.
- PHP documentation: https://www.php.net/docs.php
- MySQL documentation: https://dev.mysql.com/doc/
- MDN Web Docs: https://developer.mozilla.org/
- Git documentation: https://git-scm.com/doc
