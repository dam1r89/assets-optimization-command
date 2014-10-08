Optimise assets in the Laravel blade layouts. Concat them and replace with one import.

Example:

        {{ HTML::style('app/lib/bootstrap-datepicker/css/datepicker3.css'); }}
        {{ HTML::style('app/lib/bootstrap/dist/css/bootstrap.css'); }}
        {{ HTML::style('app/lib/font-awesome/css/font-awesome.css'); }}
        {{ HTML::style('assets/css/main.css'); }}

becomes

    	{{ HTML::style('build/main.css') }}

Usage: `php artisan asopt layouts.site`

`layouts.site` view will be replaced with new generated layout so no need to change views to use new layout.
Old layout is saved in the same folder but with `orig-` prefix, so it will be `orig-site.blade.php`.
git
