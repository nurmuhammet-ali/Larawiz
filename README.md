![Artem Maltsev - Unsplash #3n7DdlkMfEg](https://images.unsplash.com/photo-1551269901-5c5e14c25df7?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1280&h=400&q=80)

[![Latest Version on Packagist](https://img.shields.io/packagist/v/darkghosthunter/larawiz.svg?style=flat-square)](https://packagist.org/packages/larawiz/larawiz) [![License](https://poser.pugx.org/darkghosthunter/larawiz/license)](https://packagist.org/packages/darkghosthunter/larawiz)
![](https://img.shields.io/packagist/php-v/larawiz/larawiz.svg)

# Larawiz  

The Laravel Wizard scaffolder you wanted but never got, until now!

Larawiz reads one YAML file to create multiple files for your database, without touching more than one Artisan command: models, migrations, factories, pivot tables, etc.

## Requirements:

* Laravel 7

## Install:

Install this package using Composer directly inside your development packages.

```bash
composer require larawiz/larawiz:dev-master --dev
```

## Usage

First, publish the sample YAML files into `larawiz/`.

    php artisan larawiz:sample

Once you edit your YAML files, kick off the assistant with this artisan command.

    php artisan larawiz:scaffold

Larawiz will automatically create the files needed to set up your project like it was magic.

### Safety first

Larawiz will automatically copy your `app`, `routes` and some of your `database` directories as backups every time you scaffold. It only deletes the `migrations` folder.

You can find it in your application default storage path under the `storage/larawiz/backups` directory, and copy them over your project file. 

## Generating your app

[The whole documentation is online](https://darkghosthunter.gitbook.io/larawiz/), but you should get the gist with this:

```yaml
models:
  Author:
    name: string
    email: string
    password: string
    publications: hasMany

  Publication:
    title: string
    body: longText
    author: belongsTo
```

You shouldn't need to read the documentation, but if you're unsure or you want something more, go ahead, it explains everything.

## How this works

Larawiz goes around many loops that parses, prepares, associates and writes files.

1. Reads your YAML files and parses them into data.
2. Prepares the data ands fills the gaps.
3. Associates the data between it.
4. Writes the files. 

This is powered by multiple pipelines, allowing this package to be easily maintained by adding features or fixing the existing ones.

## Roadmap

The intention of this package is to give developers an easy way to make the skeleton of their application with some pseudo-code instead of full fledged logic, which slows your development.
 
While these features are "soft-documented" in some parts, these still needs to be implemented.

### Version 2.0:

* [HTTP](wiki/HTTP.md): Controllers, middleware, routes, and internal logic (queries, events, jobs, notifications, validation requests, etc.)

### Version 3.0:

* [Authorization](wiki/AUTH.md): Gates, policies, Form Requests with validation and authorization inside the HTTP.

### Version 4.0:

* Views: Basic views for controllers, Bootstrap 4 based.

### Version 5.0:

* Testing: Making automatic tests for the HTTP layer (controllers and middleware).

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).

Laravel is a Trademark of Taylor Otwell. Copyright © 2011-2020 Laravel LLC.
