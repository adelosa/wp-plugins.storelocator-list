# storelocator-list 

A Wordpress plugin that provides a shortcode to list store locator entries

## dependencies

### WP Store Locator

This plugin is an add-on for the [WP Store Locator plugin](https://wordpress.org/plugins/wp-store-locator/).

### Font Awesome

The [Font Awesome plugin](https://wordpress.org/plugins/font-awesome/) is 
used to display some icons in the listing. Will work without this installed.

## Installation

* Add the `storelocator-list` folder to your wp-content\plugins folder.
* Using admin, activate the plugin.

## Usage

### __sllist__ shortcode

Add the following shortcode where you want to display the list of stores.

| shortcode                          | Use                        |
|------------------------------------|----------------------------|
| \[sllist]                          | list all stores            |
| \[sllist category_slug={slugname}] | list stores under category |  
| \[sllist state={state}]            | list stores with state     |

### store listing updater

This plugin provides a function to allow store owners to update their
own listings.

The store owner locates their listing and requests access to update
using the store manager page (via /store-manager)

An email is sent to the store listing nominated email address
containing a web link and a password.

The store manager follows the link in the email, enters the password
and then a page is provided to update the listing.

### Store export/import

In the Wordpress admin menu, a new item will be provided to import 
and export store listings to and from Excel.

## Licence

See [LICENCE](LICENCE)
