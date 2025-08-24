# Contributing

## Development setup

This plugin uses docker compose to facilitate a development environment.

### Run

Start the environment for first time:

    docker compose up -d

* Wordpress will be available at http://localhost:8080
* Mailtrap will be available at http://localhost:8025

### Install required plugins

This plugin is an add-on for the existing plugin WP Store Locator.

Login as admin and install and activate the required plugins:

* WP Store Locator
* Font Awesome

Activate these and the storelocator-list plugin which should already be displayed.

### Ongoing

Start the dev environment:

    docker compose up -d

Wordpress will be available at http://localhost:8080

    docker compose down