const Encore = require('@symfony/webpack-encore');

if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    .setOutputPath('src/Resources/public/')
    .setPublicPath('/')
    .addEntry('uploader', './assets/uploader.js')
    .disableSingleRuntimeChunk()
    .cleanupOutputBeforeBuild()
    .enableVersioning(true)
    // .setManifestKeyPrefix('bundles/media')
    .configureBabel((config) => {
        config.plugins.push('@babel/plugin-proposal-class-properties');
    })
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = 3;
    })
;

module.exports = Encore.getWebpackConfig();
