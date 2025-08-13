# Database Schema Documentation

## Overview
This document describes the database schema used for the store owner self-service update feature. The schema uses WordPress post meta fields to store access tokens and authentication data, avoiding the need for custom database tables.

## Post Meta Fields

All fields are stored as post meta for the `wpsl_stores` post type:

### Core Access Fields

#### `sllist_access_token`
- **Type**: String (VARCHAR)
- **Format**: UUID-style (e.g., `a1b2c3d4-e5f6-7890-1234-567890abcdef`)
- **Purpose**: Unique access token for store update URL
- **Security**: 32-character secure random string formatted as UUID
- **Uniqueness**: Guaranteed unique across all stores
- **Lifecycle**: Created on access request, deleted when revoked/expired

#### `sllist_access_password_hash`
- **Type**: String (VARCHAR)
- **Format**: WordPress password hash (bcrypt)
- **Purpose**: Hashed version of temporary access password
- **Security**: Uses WordPress `wp_hash_password()` function
- **Storage**: Never store plain text passwords
- **Lifecycle**: Created with token, deleted when revoked/expired

#### `sllist_token_expires`
- **Type**: Integer (BIGINT)
- **Format**: Unix timestamp
- **Purpose**: Token expiration time
- **Default**: 24 hours from creation (configurable)
- **Validation**: Checked on every access attempt
- **Cleanup**: Expired tokens are automatically cleaned up

#### `sllist_token_used`
- **Type**: Boolean (stored as string: '1' or '0')
- **Purpose**: Mark token as used (one-time use)
- **Default**: false (0)
- **Behavior**: Set to true after successful store update
- **Security**: Prevents token reuse

#### `sllist_access_requested_date`
- **Type**: String (DATETIME)
- **Format**: MySQL datetime format (`Y-m-d H:i:s`)
- **Purpose**: Timestamp when access was first requested
- **Timezone**: Uses WordPress site timezone
- **Audit**: Used for tracking and reporting

### Security & Rate Limiting Fields

#### `sllist_last_access_attempt`
- **Type**: String (DATETIME)
- **Purpose**: Timestamp of last access attempt (successful or failed)
- **Usage**: Rate limiting and security monitoring
- **Reset**: Updated on every access attempt

#### `sllist_access_attempts`
- **Type**: Integer
- **Purpose**: Count of failed access attempts
- **Limit**: 5 attempts maximum before lockout
- **Reset**: Reset to 0 on successful authentication
- **Security**: Prevents brute force attacks

## Database Relationships

### Primary Relationship
```
wp_posts (wpsl_stores) 
    ├── wp_postmeta (sllist_access_token)
    ├── wp_postmeta (sllist_access_password_hash)
    ├── wp_postmeta (sllist_token_expires)
    ├── wp_postmeta (sllist_token_used)
    ├── wp_postmeta (sllist_access_requested_date)
    ├── wp_postmeta (sllist_last_access_attempt)
    └── wp_postmeta (sllist_access_attempts)
```

### Store Identification
Stores are identified by:
- **Primary**: `sllist_access_token` (for URL-based access)
- **Validation**: `post_type = 'wpsl_stores'` AND `post_status IN ('publish', 'pending')`

## Security Schema

### Token Generation
```php
// Token format: 32 characters as UUID-style
$token = sprintf(
    '%s-%s-%s-%s-%s',
    substr($unique_hash, 0, 8),
    substr($unique_hash, 8, 4),
    substr($unique_hash, 12, 4),
    substr($unique_hash, 16, 4),
    substr($unique_hash, 20, 12)
);
```

### Password Security
```php
// Hashing (creation)
$hash = wp_hash_password($plain_password);

// Verification (login)
$is_valid = wp_check_password($plain_password, $stored_hash);
```

## Data Lifecycle

### 1. Access Request
```
POST /store-manager/ (search & request)
    ↓
Generate: token + password + expiry
    ↓
Store: All meta fields created
    ↓
Email: Send credentials to store owner
```

### 2. Access Validation
```
GET /update-store/?token=xxx
    ↓
Validate: token exists & not expired & not used
    ↓
POST credentials (token + password)
    ↓
Verify: password hash matches
    ↓
Grant: access to update form
```

### 3. Store Update
```
POST /update-store/ (form submission)
    ↓
Validate: token + password again
    ↓
Update: store post & meta data
    ↓
Mark: token as used (sllist_token_used = true)
    ↓
Email: confirmation to store owner
```

### 4. Cleanup Process
```
Cron Job (daily)
    ↓
Find: expired tokens (sllist_token_expires < current_time)
    ↓
Delete: All related meta fields
    ↓
Log: cleanup statistics
```

## Queries & Performance

### Frequently Used Queries

#### Find Store by Token
```sql
SELECT p.* FROM wp_posts p
INNER JOIN wp_postmeta pm ON p.ID = pm.post_id
WHERE p.post_type = 'wpsl_stores'
AND pm.meta_key = 'sllist_access_token'
AND pm.meta_value = 'token_here'
LIMIT 1;
```

#### Find Expired Tokens
```sql
SELECT post_id FROM wp_postmeta
WHERE meta_key = 'sllist_token_expires'
AND meta_value < UNIX_TIMESTAMP()
```

#### Count Active Tokens
```sql
SELECT COUNT(*) FROM wp_postmeta pm1
INNER JOIN wp_postmeta pm2 ON pm1.post_id = pm2.post_id
WHERE pm1.meta_key = 'sllist_access_token'
AND pm2.meta_key = 'sllist_token_used'
AND pm2.meta_value = '0'
```

### Performance Considerations

1. **Indexing**: WordPress automatically indexes `meta_key` and `post_id`
2. **Query Optimization**: Use specific meta_key queries rather than broad searches
3. **Cleanup**: Regular cleanup prevents database bloat
4. **Caching**: Use WordPress transients for rate limiting

## Migration & Backup

### Backup Considerations
- All access data is stored in standard WordPress tables
- Standard WordPress backup procedures capture all data
- No special backup requirements

### Migration Process
- Export: Standard WordPress post and meta export
- Import: Standard WordPress import process
- Validation: Check token expiry dates after migration

## Security Compliance

### Data Protection
- **Passwords**: Never stored in plain text
- **Tokens**: Cryptographically secure random generation
- **Expiry**: Automatic cleanup of expired data
- **Access Control**: Rate limiting and attempt counting

### Audit Trail
- **Creation**: `sllist_access_requested_date`
- **Usage**: `sllist_last_access_attempt`
- **Security**: `sllist_access_attempts`
- **Lifecycle**: Token used/expired status

## Configuration

### Configurable Values
```php
const TOKEN_EXPIRY_HOURS = 24;        // Token lifetime
const MAX_ACCESS_ATTEMPTS = 5;        // Failed attempt limit
const RATE_LIMIT_WINDOW = 3600;       // Rate limit window (seconds)
const TOKEN_LENGTH = 32;              // Token character length
const PASSWORD_LENGTH = 12;           // Generated password length
```

### WordPress Integration
- Uses WordPress user capabilities for admin functions
- Integrates with WordPress mail system
- Follows WordPress coding standards
- Compatible with WordPress multisite
