# Technical Project Summary

## Overview
This project is a WordPress plugin development environment for the `storelocator-list` plugin. It includes a local WordPress installation and uses Docker for containerized development and testing.

The plugin extends another Wordpress plugin called wp store locator.

<https://en-au.wordpress.org/plugins/wp-store-locator/#developers>

The source code for the plugin is here
<https://plugins.svn.wordpress.org/wp-store-locator/>

## Structure
- **storelocator-list/**: Contains the custom WordPress plugin code (`storelocator-list.php`).
- **wordpress/**: Contains a full WordPress installation, including core files and directories (`wp-admin/`, `wp-content/`, `wp-includes/`).
- **docker-compose.yml**: Defines Docker services for running WordPress and its dependencies locally.
- **features/**: Contains documentation and feature specifications.

## Technologies Used
- **WordPress**: PHP-based CMS, used here for plugin development and testing.
- **PHP**: Main language for plugin and WordPress core development.
- **Docker**: Used for local development, providing isolated containers for WordPress, database, and other services.

## Development Workflow
- Plugin code is developed in the `storelocator-list` directory.
- The local WordPress environment is managed via Docker Compose, allowing for easy setup and teardown.
- The `update.sh` script may be used for maintenance or deployment tasks (details not specified).

## Notes
- The project is structured for ease of local development and testing of WordPress plugins.
- All WordPress core files are included for a complete, self-contained environment.
