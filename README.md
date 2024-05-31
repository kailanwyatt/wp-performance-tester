# WP Performance Tester

WP Performance Tester is a WordPress plugin that allows you to test the performance impact of your plugins on your site. It measures the time it takes to load your site with each plugin deactivated one by one.

## Installation

1. Download the `wp-performance-tester` folder.
2. Upload the `wp-performance-tester` folder to your `mu-plugins` directory in the `wp-content` directory.
3. Activate the plugin through the 'Must Use Plugins' menu in WordPress.

## Usage

### Using WP CLI

To run a performance test for all plugins, use the following command:

```
wp performance_tester
```

To run a performance test for a specific plugin, use the following command:

```
wp wp-performance-tester --plugin=plugin-folder/plugin-file.php
```

Replace plugin-folder/plugin-file.php with the folder and file name of the plugin you want to test.

## Flags
### use_logging
The use_logging flag allows you to log the performance test results to the error log.

To use logging, add the --use_logging flag to your command:

```
wp wp-performance-tester --use_logging
```

When this flag is used, the results will be logged with the timestamp, plugin name, and elapsed time in seconds and milliseconds.

### minimum_required_plugins
The minimum_required_plugins flag allows you to specify plugins that should never be deactivated during the test. This is useful for plugins that are essential for your site to function properly.

To specify minimum required plugins, use the --minimum_required_plugins flag with a comma-separated list of plugins:

```
wp wp-performance-tester --minimum_required_plugins=plugin-folder/plugin-file.php,another-plugin-folder/another-plugin-file.php
```

Replace the values with the folder and file names of the plugins you want to include as minimum required plugins.

## Contributing
Contributions are welcome! If you have any suggestions, bug reports, or improvements, please open an issue or submit a pull request.

## License
This plugin is licensed under the GPL-2.0 License. See the LICENSE file for details.
