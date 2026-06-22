# Configuring Logging

To enable logging, add the following to your `civicrm.settings.php`:

```php
if (!defined('TWINGLE_API_LOGGING')) {
  define('TWINGLE_API_LOGGING', TRUE);
}
```

Then every API call from Twingle will be logged to CiviCRM log.
