# WP Performance Tester

WP Performance Tester is a WordPress plugin that allows you to test the performance impact of your plugins on your site. It measures the time it takes to load your site with each plugin deactivated one by one.

## Installation

1. Download the `wp-performance-tester` folder.
2. Upload the `wp-performance-tester.php` file to your `mu-plugins` directory in the `wp-content` directory.

## Usage

### Using WP CLI

To run a performance test for all plugins, use the following command:

```
wp performance_tester
```

To run a performance test for a specific plugin, use the following command:

```
wp performance_tester --plugin=plugin-folder/plugin-file.php
```

Replace plugin-folder/plugin-file.php with the folder and file name of the plugin you want to test.

## Flags
### use_logging
The use_logging flag allows you to log the performance test results to the error log.

To use logging, add the `--use_logging` flag to your command:

```
wp performance_tester --use_logging
```

When this flag is used, the results will be logged with the timestamp, plugin name, and elapsed time in seconds and milliseconds.

### minimum_required_plugins
The minimum_required_plugins flag allows you to specify plugins that should never be deactivated during the test. This is useful for plugins that are essential for your site to function properly.

To specify minimum required plugins, use the --minimum_required_plugins flag with a comma-separated list of plugins:

```
wp performance_tester --minimum_required_plugins=plugin-folder/plugin-file.php,another-plugin-folder/another-plugin-file.php
```

Replace the values with the folder and file names of the plugins you want to include as minimum required plugins.

## Result

The response will look something like this

```
query-monitor/query-monitor.php - 0.185s/185ms
akismet/akismet.php - 0.125s/125ms
edd-software-licensing/edd-software-licenses.php - 0.128s/128ms
edd-stripe/edd-stripe.php - 0.128s/128ms
kw-portfolio/kw-portfolio.php - 0.130s/130ms
plugin-check/plugin.php - 0.125s/125ms
user-switching/user-switching.php - 0.127s/127ms
wc-product-info/wc-product-info.php - 0.096s/96ms
woocollections-for-woocommerce/woocollections-for-woocommerce.php - 0.127s/127ms
woocommerce/woocommerce.php - 0.046s/46ms
wordpress-beta-tester/wp-beta-tester.php - 0.175s/175ms
wp-job-manager/wp-job-manager.php - 0.128s/128ms
wp-media-stories/wp-media-stories.php - 0.136s/136ms

Top offending plugin: woocommerce/woocommerce.php with time: 0.046
```

The output will idenitify the plugin that has the lowest load time when it is deactivated. This won't automatically tell you what might be causing a bloat in the plugin but it gives you a start by narrowing it down to a plugin.

## Contributing
Contributions are welcome! If you have any suggestions, bug reports, or improvements, please open an issue or submit a pull request.

## License
This plugin is licensed under the GPL-2.0 License. See the LICENSE file for details.
