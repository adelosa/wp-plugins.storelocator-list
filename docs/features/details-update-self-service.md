# Feature - store owner self service update

## Overview
Enhance the plugin to allow store owners to update their store details without having a WordPress account. Store owners will request access to edit their store, receive a unique link and password via email, and use these to update their details securely.

## Features & Steps

### 1. Store Manager Access Request Page
- Create a public-facing page (e.g., /store-manager/) where store owners can search for their store (by name, location, or email).
- After locating their store, the owner can request access to edit their details.

### 2. Access Request Workflow
- On request, verify the store exists and retrieve the store's email address from the database.
- Generate a unique, secure token (UUID or similar) and a random password.
- Store the token and a hashed version of the password as post meta for the store.
- Email the store owner a link to a unique update page (e.g., /update-store/?token=xxxx) and the password.

### 3. Update Store Details Page
- Create a public-facing page/template that accepts the token as a URL parameter.
- Display a form for updating store details, requiring the password for authentication.
- On submission, verify the token and password, then update the store details using WordPress functions.

### 4. Security Considerations
- Use long, random tokens and securely hash passwords.
- Do not store plain text passwords.
- Add rate limiting or CAPTCHA to prevent brute-force attacks.
- Expire tokens after use or after a set period.

### 5. Admin Tools
- Add admin UI to view, (re)generate, or revoke tokens/passwords.
- Allow admins to manually trigger access emails if needed.

### 6. Email Template
- Create a customizable email template for sending the access link and password to store owners.

## Implementation Plan

### **Phase 1: Foundation & Database Schema (Week 1)**

#### Step 1.1: Extend Plugin Architecture
- **Objective**: Restructure the plugin for better organization and maintainability
- **Actions**:
  - Create an organized folder structure within the plugin
  - Split functionality into separate classes/files
  - Implement proper WordPress coding standards
  - Add namespace support

#### Step 1.2: Database Schema Design
- **Objective**: Design secure token and authentication storage
- **Actions**:
  - Create custom post meta fields for access tokens:
    - `sllist_access_token` (unique UUID)
    - `sllist_access_password_hash` (bcrypt hashed)
    - `sllist_token_expires` (timestamp)
    - `sllist_token_used` (boolean flag)
    - `sllist_access_requested_date` (timestamp)
  - Document the schema and relationships

#### Step 1.3: Security Foundation
- **Objective**: Implement core security functions
- **Actions**:
  - Create secure token generation function (UUID4)
  - Implement password hashing/verification functions
  - Add rate limiting utilities
  - Create input sanitization helpers

### **Phase 2: Store Search & Access Request (Week 2)**

#### Step 2.1: Public Store Search Page
- **Objective**: Allow store owners to find their store
- **Actions**:
  - Create `/store-manager/` public page template
  - Implement AJAX store search functionality
  - Search criteria: store name, email, address, phone
  - Add fuzzy matching capabilities
  - Include pagination for results

#### Step 2.2: Access Request Workflow
- **Objective**: Generate and send access credentials
- **Actions**:
  - Create access request form with store selection
  - Implement token/password generation
  - Store encrypted credentials in post meta
  - Add email validation for store owner
  - Implement basic CAPTCHA protection

#### Step 2.3: Email Notification System
- **Objective**: Send secure access credentials via email
- **Actions**:
  - Create customizable email templates
  - Implement secure email sending
  - Include access link with token parameter
  - Add email logging for admin tracking
  - Support HTML and plain text formats

### **Phase 3: Secure Update Interface (Week 3)**

#### Step 3.1: Update Page Authentication
- **Objective**: Create secure store update interface
- **Actions**:
  - Create `/update-store/` public page template
  - Implement token validation from URL parameter
  - Add password authentication form
  - Handle token expiration and usage tracking
  - Add security headers and CSRF protection

#### Step 3.2: Store Update Form
- **Objective**: Provide user-friendly update interface
- **Actions**:
  - Design responsive update form
  - Include all editable store fields:
    - Store name (`post_title`)
    - Description (`post_content`)
    - Address fields (`wpsl_address`, `wpsl_address2`, `wpsl_city`, `wpsl_state`, `wpsl_zip`)
    - Contact info (`wpsl_phone`, `wpsl_email`, `wpsl_url`)
  - Add client-side validation
  - Implement progressive enhancement

#### Step 3.3: Update Processing & Validation
- **Objective**: Securely process store updates
- **Actions**:
  - Server-side input validation and sanitization
  - Update WordPress post and meta data
  - Log all changes for audit trail
  - Send confirmation email to store owner
  - Invalidate token after successful update

### **Phase 4: Admin Management Interface (Week 4)**

#### Step 4.1: Admin Dashboard Integration
- **Objective**: Provide admin tools for managing access requests
- **Actions**:
  - Add submenu under WP Store Locator admin
  - Create overview dashboard with statistics
  - List all access requests with status
  - Add bulk actions for token management

#### Step 4.2: Token Management Tools
- **Objective**: Allow admins to manage access tokens
- **Actions**:
  - View active/expired/used tokens
  - Manually generate new access tokens
  - Revoke existing tokens
  - Resend access emails
  - Export access logs

#### Step 4.3: Settings & Configuration
- **Objective**: Provide customizable settings
- **Actions**:
  - Token expiration time settings
  - Email template customization
  - Rate limiting configuration
  - Security settings (CAPTCHA, etc.)
  - Notification preferences

### **Phase 5: Security Hardening & Testing (Week 5)**

#### Step 5.1: Advanced Security Features
- **Objective**: Implement comprehensive security measures
- **Actions**:
  - Add brute force protection
  - Implement IP-based rate limiting
  - Add honeypot fields to forms
  - Enable detailed security logging
  - Add CSRF token validation

#### Step 5.2: Comprehensive Testing
- **Objective**: Ensure reliability and security
- **Actions**:
  - Unit tests for core functions
  - Integration tests for workflows
  - Security penetration testing
  - User acceptance testing
  - Performance testing

#### Step 5.3: Documentation & Deployment
- **Objective**: Prepare for production use
- **Actions**:
  - Write comprehensive documentation
  - Create user guides for store owners
  - Admin documentation for site managers
  - Update plugin version and changelog
  - Prepare deployment package

### **Phase 6: Advanced Features (Week 6)**

#### Step 6.1: Enhanced User Experience
- **Objective**: Improve usability and functionality
- **Actions**:
  - Add store preview functionality
  - Implement change approval workflow (optional)
  - Add store owner dashboard
  - Support for bulk store updates
  - Integration with existing store categories

#### Step 6.2: Monitoring & Analytics
- **Objective**: Provide insights and monitoring
- **Actions**:
  - Usage analytics dashboard
  - Success/failure rate tracking
  - Popular update fields analysis
  - Email delivery monitoring
  - Performance metrics

### **Implementation Strategy:**

1. **Incremental Development**: Each phase builds upon the previous one, allowing for testing and refinement
2. **Security First**: Security considerations are integrated from the beginning, not added as an afterthought
3. **Backward Compatibility**: Ensure existing plugin functionality remains intact
4. **WordPress Standards**: Follow WordPress coding standards and best practices
5. **Extensibility**: Design the architecture to allow for future enhancements

### **Key Technical Considerations:**

- **Database**: Leverage WordPress post meta for token storage, avoiding custom tables
- **Security**: Use WordPress nonces, proper sanitization, and secure password hashing
- **Email**: Integrate with WordPress mail system and support popular SMTP plugins
- **UI/UX**: Follow WordPress admin design patterns and ensure mobile responsiveness
- **Performance**: Optimize database queries and implement caching where appropriate

## Next Steps
- Design database schema for tokens/passwords as post meta.
- Implement the store manager access request page and workflow.
- Build the update form and authentication logic.
- Add security and admin features.
- Test the full workflow end-to-end.
