# storelocator-list 

A Wordpress plugin that provides a shortcode to list store locator entries

## dependencies

### WP Store Locator

This plugin is an  the [WP Store Locator plugin](https://wordpress.org/plugins/wp-store-locator/) by providing a shortcode that lists the store details.

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
