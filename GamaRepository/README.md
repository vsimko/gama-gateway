## PHP Installation
 - Recommended PHP version `5.2.0+`
 - Required PHP extensions
   - php5-soap
   - php5-pdo
   - php5-mysql
   - php5-json
   - php5-xmlreader
   - php5-xmlwriter
   - php5-json

### Configuring the `php.ini` directives

You can either change the php.ini file:
```ini
output_buffering = Off
safe_mode = Off
memory_limit = 556M
```

Or you can update httpd configuration:
```apacheconf
php_admin_flag safe_mode Off
php_admin_value memory_limit 556M
php_admin_value output_buffering Off
```

## Configuration and setup

Copy `config.php.dist` to `config.php` and adjust to your needs.

Copy `endpoint/config-endpoint.php.dist` to `endpoint/config-endpoint.php` and adjust to your needs.

Add `.htaccess` file to the endpoint dir to prevent unauthorised execution of
- `backup.php`
- `cleanup.php`
- `insert.php`
- `siminsert.php`

Install `dot` tool from the `graphviz` package (required by the `visualize.php` script)

Make sure that the HTTP server has write access to the following files/dirs:
```bash
chmod g+w GamaSync/Update
chmod g+w GamaSync/Backup
chmod g+w .config-idx-engine.php
```
