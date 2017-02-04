const { mix } = require('laravel-mix');

mix.setPublicPath('./web');
mix
    .sass('app/Resources/assets/scss/app.scss', 'css/')
    .combine([
        'web/css/app.css',
        'app/Resources/assets/css/font-lato.css',
        'app/Resources/assets/css/highlight-solarized-light.css',
    ], 'css/app.css')

    .js('app/Resources/assets/js/app.js', 'js/')

    .copy('node_modules/font-awesome/fonts', 'web/fonts');
    // .version()
;
