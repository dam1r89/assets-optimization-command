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

    php artisan asopt <layout-file> <dest/folder/file>
    php artisan asopt layouts.site build/main

Destination shouldn't have an extension (.js or .css).

Optional JavaScript minify flag `-m`

    php artisan asopt layouts.site build/main -m

`layouts.site` view will be replaced with new generated layout so no need to change views to use new layout.
Old layout is saved in the same folder but with `orig-` prefix, it will be `orig-site.blade.php`.
After that, do not change `layout.site` file, only a file with `orig-` prefix.

Output is saved under the public path and specified folder.

The main problem with this approach is that you have to rerun command after each change to the orig layout file.

##For production

Add to `.gitignore` generated files (ones without `orig-` prefix, these used to be original files, but now they are replaced with the generated one) and keep `orig-site.blade.php` file.
To compile it call the command as usual. Command will find that `orig-site.blade.php` exists and will build the layout from that file.

    php artisan asopt layouts.site build/main

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


