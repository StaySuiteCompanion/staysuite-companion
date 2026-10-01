/**
 * WordPress Scripts configuration.
 *
 * Builds ./src/index.js into ./assets/build so the plugin ships
 * compiled frontend code without committing node_modules.
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
    ...defaultConfig,
    entry: {
        index: './src/index.js',
        editor: './src/editor.js',
        admin: './src/admin.js',
    },
    output: {
        ...defaultConfig.output,
        path: __dirname + '/assets/build',
    },
};
