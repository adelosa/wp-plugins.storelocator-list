# StoreLocator-List Plugin Architecture

## Overview
This document describes the new architecture implemented in version 0.2.0 of the StoreLocator-List plugin.

## Directory Structure

```
storelocator-list/
├── storelocator-list.php          # Main plugin file
├── includes/                       # Core plugin functionality
│   ├── class-sllist-plugin.php    # Main plugin class
│   ├── core/                      # Core functionality
│   │   ├── class-sllist-shortcodes.php    # Shortcode handling
│   │   └── class-sllist-query-helper.php  # Database queries
│   ├── public/                    # Public-facing functionality
│   │   └── (future self-service components)
│   └── admin/                     # Admin functionality
│       └── (future admin components)
├── templates/                     # Template files
├── assets/                        # Static assets
│   ├── css/
│   │   └── sllist-styles.css
│   └── js/
└── languages/                     # Translation files
```

## Architecture Principles

### 1. Namespace Organization
- All classes use the `StoreLocatorList` namespace
- Subnamespaces organize functionality:
  - `StoreLocatorList\Core` - Core functionality
  - `StoreLocatorList\Public` - Public-facing features
  - `StoreLocatorList\Admin` - Admin interface

### 2. Single Responsibility
- Each class has a specific purpose
- Functionality is split into logical components
- Easy to test and maintain

### 3. WordPress Standards
- Follows WordPress coding standards
- Uses WordPress hooks and filters appropriately
- Proper sanitization and security practices

### 4. Backward Compatibility
- Legacy functions are maintained with deprecation notices
- Existing shortcodes continue to work
- Smooth migration path for existing installations

## Core Classes

### SLList_Plugin
- Main plugin controller
- Handles initialization and dependency loading
- Manages plugin lifecycle (activation/deactivation)

### SLList_Shortcodes
- Handles all shortcode functionality
- Renders store listings
- Manages shortcode attributes and output

### SLList_Query_Helper
- Database query utilities
- Store search functionality
- Metadata handling

## Future Extensions

The architecture is designed to support the upcoming self-service features:

### Public Components (Phase 2-3)
- `SLList_Store_Search` - Store search interface
- `SLList_Store_Update` - Store update functionality
- `SLList_Access_Manager` - Token and authentication management

### Admin Components (Phase 4)
- `SLList_Admin` - Admin interface controller
- `SLList_Token_Manager` - Token management
- `SLList_Settings` - Plugin settings

### Security Components (Phase 1-5)
- `SLList_Security` - Security utilities
- `SLList_Rate_Limiter` - Rate limiting
- `SLList_Email_Manager` - Email handling

## Migration Notes

### From Version 0.1.0 to 0.2.0
- Existing shortcodes continue to work unchanged
- Legacy functions are deprecated but functional
- New namespace-based classes are recommended for new development
- No database changes required

### Deprecation Timeline
- v0.2.0: Legacy functions deprecated with notices
- v0.3.0: Legacy functions will show warnings
- v0.4.0: Legacy functions will be removed

## Development Guidelines

### Adding New Features
1. Create classes in appropriate namespace
2. Follow WordPress coding standards
3. Include proper docblocks
4. Add unit tests where applicable
5. Update this documentation

### Security Considerations
- Sanitize all input
- Escape all output
- Use WordPress nonces for forms
- Validate permissions appropriately
- Follow principle of least privilege

## Version History

### 0.2.0 (Current)
- Restructured plugin architecture
- Added namespace support
- Improved code organization
- Added CSS styling framework
- Maintained backward compatibility

### 0.1.0 (Legacy)
- Initial plugin version
- Basic shortcode functionality
- Single file architecture
