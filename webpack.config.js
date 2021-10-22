var Encore = require('@symfony/webpack-encore');
const WebpackRTLPlugin = require('webpack-rtl-plugin');

function basic(path, Encore){
    return Encore
        .setOutputPath('./src/Resources/public/' + path)
        .setPublicPath('/')
        .setManifestKeyPrefix('bundles/dashboard')
        .cleanupOutputBeforeBuild()
        .enableSassLoader()
        .enableSourceMaps(false)
        .enableVersioning(false)
        .disableSingleRuntimeChunk()
        .autoProvidejQuery()
        .addPlugin(new WebpackRTLPlugin({
            test: '^((?!(app-custom-rtl.css)).)*$',
            diffOnly: true,
        }))
}


/**
 * Config for sb-admin layout
 */
basic('dashboard', Encore)
    .addEntry("app", "./assets/js/app.js")
;
const dashboard = Encore.getWebpackConfig();
Encore.reset();

module.exports = [dashboard]
