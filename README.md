# A workbench for developing for WordPress
[![Packagist](https://img.shields.io/packagist/v/withfatpanda/workbench-wordpress.svg?style=flat-square)](https://packagist.org/packages/withfatpanda/workbench-wordpress)

At [Fat Panda](https://wordpress.withfatpanda.com), sometimes we develop plugins and themes for WordPress. 

To speed up our prototyping process and reduce time to market, we've created this workbench based on [Bedrock](https://roots.io/bedrock/), and now you can use it too.

## What does it do?

This workbench speeds up developing plugins and themes for WordPress by providing scaffolding for both.

With our workbench you can:

* Start a new [illuminated](https://github.com/withfatpanda/illuminate-wordpress) plugin project: `wp workbench make plugin {plugin_name}`
* Start a new illuminated mu-plugin project: `wp workbench make mu-plugin {plugin_name}`
* Start a new theme based on [underscores](https://underscores.me/): `wp workbench make theme underscores {theme_name}`
* Start a new theme based on [Sage](https://roots.io/sage/): `wp workbench make theme sage {theme_name}`
* [WHIP] any theme into shape: `wp workbench whip theme {theme_name}`

There are other commands and features: see *Commands* below.

## Requirements

* The Mac platform&mdash;other platforms *might* work
* [Homebrew](http://brew.sh/): the missing package manager for Mac
* [Git](https://try.github.io): `brew install git`
* [Composer](https://getcomposer.org/): the PHP package manager; `brew tap homebrew/php && brew install composer`
* [Laravel Valet](https://laravel.com/docs/5.3/valet): a minimalist dev stack; refer to their documentation for installion instructions
* [MariaDB](https://mariadb.org): MySQL, real open source; `brew install mariadb`; any other MySQL database should be fine too
* [WP-CLI](https://wp-cli.org): the command line utility for WordPress; `brew install homebrew/php/wp-cli`
* [WP-CLI Dotenv Command](https://github.com/aaemnnosttv/wp-cli-dotenv-command): a plugin for generating hash salts; `wp package install aaemnnosttv/wp-cli-dotenv-command`

(Eventually we will probably switch to [Trellis](https://roots.io/trellis/) from Laravel Valet, but Valet works great for now.)

## Getting Started

1. Start a new project:

  `composer create-project withfatpanda/workbench-wordpress example`

  Follow the on-screen instructions.

2. Finish setting up WordPress by switching into your workbench path, and running the following WP-CLI commands:

    ```
    cd example
    wp db create
    wp core install --title="Workbench" --url="http://workbench.dev" --admin_user="admin" --admin_password="password" --admin_email="no-reply@workbench.dev"
    ```

3. If using Valet, switch to your new project's root path, and run: 

  `valet link {hostname}`

  Where `{hostname}` is your development domain name without the TLD; so if you are developing a site on *example.dev*, `{hostname}` should be `example`.

**Note:** by default, Valet will try to serve all requests made to `.dev` domain names. You can configure this&mdash;from Valet's [documentation](https://laravel.com/docs/5.3/valet):

> By default, Valet serves your projects using the `.dev` TLD. If you'd like to use another domain, you can do so using the `valet domain tld-name` command.

> For example, if you'd like to use `.app` instead of `.dev`, run `valet domain app` and Valet will start serving your projects at `*.app` automatically.

## Commands

To be documented.

## Based on Bedrock

This workbench wouldn't be possible without Bedrock. For your convenience and excitement, their readme is included below.

# [Bedrock](https://roots.io/bedrock/)
[![Packagist](https://img.shields.io/packagist/v/roots/bedrock.svg?style=flat-square)](https://packagist.org/packages/roots/bedrock)
[![Build Status](https://img.shields.io/travis/roots/bedrock.svg?style=flat-square)](https://travis-ci.org/roots/bedrock)

Bedrock is a modern WordPress stack that helps you get started with the best development tools and project structure.

Much of the philosophy behind Bedrock is inspired by the [Twelve-Factor App](http://12factor.net/) methodology including the [WordPress specific version](https://roots.io/twelve-factor-wordpress/).

## Features

* Better folder structure
* Dependency management with [Composer](http://getcomposer.org)
* Easy WordPress configuration with environment specific files
* Environment variables with [Dotenv](https://github.com/vlucas/phpdotenv)
* Autoloader for mu-plugins (use regular plugins as mu-plugins)
* Enhanced security (separated web root and secure passwords with [wp-password-bcrypt](https://github.com/roots/wp-password-bcrypt))

Use [Trellis](https://github.com/roots/trellis) for additional features:

* Easy development environments with [Vagrant](http://www.vagrantup.com/)
* Easy server provisioning with [Ansible](http://www.ansible.com/) (Ubuntu 14.04, PHP 7, MariaDB)
* One-command deploys

See a complete working example in the [roots-example-project.com repo](https://github.com/roots/roots-example-project.com).

## Requirements

* PHP >= 5.6
* Composer - [Install](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)

## Installation

1. Create a new project in a new folder for your project:

  `composer create-project roots/bedrock your-project-folder-name`

2. Copy `.env.example` to `.env` and update environment variables:
  * `DB_NAME` - Database name
  * `DB_USER` - Database user
  * `DB_PASSWORD` - Database password
  * `DB_HOST` - Database host
  * `WP_ENV` - Set to environment (`development`, `staging`, `production`)
  * `WP_HOME` - Full URL to WordPress home (http://example.com)
  * `WP_SITEURL` - Full URL to WordPress including subdirectory (http://example.com/wp)
  * `AUTH_KEY`, `SECURE_AUTH_KEY`, `LOGGED_IN_KEY`, `NONCE_KEY`, `AUTH_SALT`, `SECURE_AUTH_SALT`, `LOGGED_IN_SALT`, `NONCE_SALT`

  If you want to automatically generate the security keys (assuming you have wp-cli installed locally) you can use the very handy [wp-cli-dotenv-command][wp-cli-dotenv]:

      wp package install aaemnnosttv/wp-cli-dotenv-command

      wp dotenv salts regenerate

  Or, you can cut and paste from the [Roots WordPress Salt Generator][roots-wp-salt].

3. Add theme(s) in `web/app/themes` as you would for a normal WordPress site.

4. Set your site vhost document root to `/path/to/site/web/` (`/path/to/site/current/web/` if using deploys)

5. Access WP admin at `http://example.com/wp/wp-admin`

## Deploys

There are two methods to deploy Bedrock sites out of the box:

* [Trellis](https://github.com/roots/trellis)
* [bedrock-capistrano](https://github.com/roots/bedrock-capistrano)

Any other deployment method can be used as well with one requirement:

`composer install` must be run as part of the deploy process.

## Documentation

Bedrock documentation is available at [https://roots.io/bedrock/docs/](https://roots.io/bedrock/docs/).

## Contributing

Contributions are welcome from everyone. We have [contributing guidelines](https://github.com/roots/guidelines/blob/master/CONTRIBUTING.md) to help you get started.

## Community

Keep track of development and community news.

* Participate on the [Roots Discourse](https://discourse.roots.io/)
* Follow [@rootswp on Twitter](https://twitter.com/rootswp)
* Read and subscribe to the [Roots Blog](https://roots.io/blog/)
* Subscribe to the [Roots Newsletter](https://roots.io/subscribe/)
* Listen to the [Roots Radio podcast](https://roots.io/podcast/)

[roots-wp-salt]:https://roots.io/salts.html
[wp-cli-dotenv]:https://github.com/aaemnnosttv/wp-cli-dotenv-command
