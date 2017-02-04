const { mix } = require('laravel-mix');

mix
    .sass('app/Resources/assets/scss/app.scss', 'web/css/')
    .combine([
        'web/css/app.css',
        'app/Resources/assets/css/font-lato.css',
        'app/Resources/assets/css/highlight-solarized-light.css',
    ], 'web/css/app.css')

    .js('app/Resources/assets/js/app.js', 'web/js/')

    .copy('node_modules/font-awesome/fonts', 'web/fonts');
    // .version()
;
