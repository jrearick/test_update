# test_updates

This module is meant as a development tool for local development only.

Often we need to test and run and re-run updates to get them just right. It is a bit of a pain to do most of the time and can be time-consuming. This module is meant to help developers test their `hook_update`s.

## Getting Started

* Clone this to into your local development site's modules directory and enable.
    * `cd ~/Sites/drupal/web/modules`
    * `git clone git@github.com:jrearick/test_updates.git`
    * `drush en test_updates`

## Usage

Go to the page at the path `test-update`. Eg `local.test/drupal/web/test-update` where `local.test/drupal/web` is the document root of the Drupal installation.

This is a simple page that will run whatever function in a specified module's `.install` file.
Define the module and function like so:

```
/test-updates?fn=my_module_update_8901
```

The parameter **fn** is the hook_update you want to run. in the above example `my_module_update_8901` will run `my_module_update_8901();`.

Here's a recommended testing flow:

* Build things until just before you want to run the update(s)
* Back up the database
* Turn on xDebug and set breakpoints
* Run your update(s) by going to this page with the query parameters
* Evaluate the result
* To run again, restore the database from the backup you took earlier and reload this page


## Disclaimer

This module was written in a hurry and with no regard to security, deprecations or dependency injection. Do not put this in production! Use at your own risk.
