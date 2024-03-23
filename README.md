# storelocator-list 

A Wordpress plugin that provides a shortcode to list store locator entries

## dependencies

### WP Store Locator

This plugin suppliments the [WP Store Locator plugin](https://wordpress.org/plugins/wp-store-locator/) by providing a shortcode that lists the store details.

Manage and maintain your store details using this plugin.

### Font Awesome

The [Font Awesome plugin](https://wordpress.org/plugins/font-awesome/) plugin is used to display some icons in the listing. Will work without this installed.

## Installation

* Add the `storelocator-list` folder to your wp-content\plugins folder.
* Using admin, activate the plugin.

## Usage

Add the following shortcode where you want to display the list

| shortcode                          | Use                        |
|------------------------------------|----------------------------|
| \[sllist]                          | list all stores            |
| \[sllist category_slug={slugname}] | list stores under category |  
| \[sllist state={state}]            | list stores with state     |

## Licence
See [LICENCE](LICENCE)

## Development

### Initial setup

You will need docker and docker-compose installed

Start the environment for first time:

    docker compose up

Wordpress installation will be available at http://localhost:8080

Complete the installation of Wordpress.

Update the permissions for the Wordpress plugins directory so you 
can install/update the plugin as you develop:

    sudo chmod -R +777 wordpress/wp-content/plugins

Install the storelocator-list plugin:

    ./update.sh

Login as admin and install and activate the required plugins:

    * WP Store Locator
    * Font Awesome

Activate storelocator-list plugin.

### Ongoing

Start the dev environment:

    docker compose up

Wordpress will be available at http://localhost:8080

Update plugin for use:

    ./update.sh

Shutdown the dev environment:

    docker compose down