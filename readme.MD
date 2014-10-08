Optimise assets in the Laravel blade layouts. Concat them and replace with one import.

##What it does

Example:

    {{ HTML::style('app/lib/bootstrap-datepicker/css/datepicker3.css') }}
    {{ HTML::style('app/lib/bootstrap/dist/css/bootstrap.css') }}
    {{ HTML::style('app/lib/font-awesome/css/font-awesome.css') }}
    {{ HTML::style('assets/css/main.css') }}

becomes

    {{ HTML::style('build/main.css') }}

##Usage

    php artisan asopt layouts.site

Optional JavaScript minify flag `-m`

    php artisan asopt layouts.site -m

`layouts.site` view will be replaced with new generated layout so no need to change views to use new layout.
Old layout is saved in the same folder but with `orig-` prefix, it will be `orig-site.blade.php`.
After that, do not change `layout.site` file, but only file with `orig-` prefix.

The main problem with this approach is that you have to rerun command after each change to the orig layout file.

##For production

You can add to `.gitignore` generated files and keep `orig-site.blade.php` file. Then to compile you should call the command like this:

    php artisan asopt layouts.orig-site

##Installation

Add to the composer.json

    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/dam1r89/assets-optimization-command"
        }
    ],
    "require-dev": {
        "dam1r89/asset-optimization": "dev-master"
    }


