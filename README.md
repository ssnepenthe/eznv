# eznv

Easy WordPress development environments with WP-CLI server and SQLite.

It is a work in progress with a lot of rough edges.

Works on linux, might work on macos, probably doesn't work on windows...

## Usage

Make sure composer and WP-CLI are installed and available in your PATH.

Add the bin dir to your path:

```
export PATH=/path/to/eznv/bin:$PATH
```

Change into your package directory:

```
cd ~/plugins/my-plugin
```

eznv initializes environments using composer with your package as a (symlinked) composer dependency. If your package hasn't been initialized as a composer package already, do so now:

```
composer init
```

Make sure you set the composer type field to "wordpress-plugin", "wordpress-muplugin", or "wordpress-theme" as appropriate.

Initialize an eznv environment:

```
eznv init
```

This creates a WordPress environment in $XDG_DATA_HOME/eznv (or falls back to $HOME/.eznv).

The default username is "admin" with a password of "password".

Run the WP-CLI server:

```
eznv serve
```

Optionally set the number of server workers:

```
PHP_CLI_SERVER_WORKERS=4 eznv serve
```

Tail debug.log:

```
eznv logs
```

You can use eznv to run composer commands within your environment:

```
eznv composer require wpackagist-plugin/query-monitor
```

As well as WP-CLI commands:

```
eznv wp user list
```

Alternatively you might want to create a wp-cli.yml file with a "path" that points to your environment. Get the wordpress path using:

```
eznv info
```

You can start an interactive login shell at your environment root:

```
eznv shell
```

And you can destroy the environment:

```
eznv destroy
```

You can also use the `for` command to run any command in a specified project directory. It utilizes your package name as defined in composer.json:

```
eznv for ssnepenthe/eznv serve
```

But can also be used with a package id:

```
eznv for 4be5d8a926c9 info
```

Or package directory:

```
eznv for /path/to/eznv shell
```

You can list all valid identifiers for all initialized environments:

```
eznv env-list
```
