const Remix = require('@weaverryan/webpack-remix');

Remix
    .setContext(__dirname)
    .setOutputPath('web/build')
    .setPublicPath('/build')

    //.setPublicPath('http://localhost:9007/builds/')

    .useWebpackDevServer(!Remix.isProduction())

    .enableVersioning(Remix.isProduction())
    .addEntry('app', './app/Resources/assets/js/app.js')
    .addEntry('other', './app/Resources/assets/js/split-chunk2')
    .createSharedEntry('vendor', ['jquery', 'moment'])
    .addStyleEntry('styles', [
        './app/Resources/assets/scss/app.scss',
        './app/Resources/assets/css/font-lato.css',
        './app/Resources/assets/css/highlight-solarized-light.css'
    ])
    //.enablePostCss()
    .autoProvidejQuery()
    .cleanupOutputBeforeBuild()
;

Remix.enableSourceMaps(!Remix.isProduction());

module.exports = Remix.getWebpackConfig();
