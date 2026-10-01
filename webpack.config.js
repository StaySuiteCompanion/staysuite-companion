/**
 * WordPress Scripts configuration.
 *
 * Builds ./src/index.js into ./assets/build so the plugin ships
 * compiled frontend code without committing node_modules.
 *
 * The only adjustment is to the clean step: @wordpress/scripts wipes its
 * output directory after every build so a stale bundle can never ship, and the
 * Sass build (`npm run build:css`) writes the compiled stylesheets into that
 * same directory. Without exempting css/ here, any JS build — including every
 * rebuild of `npm start` — silently deletes the stylesheet the plugin
 * enqueues. fonts/ and images/ are already exempt upstream; css/ joins them.
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

const KEEP_IN_BUILD = '!css/**';

const plugins = defaultConfig.plugins.map( ( plugin ) => {
    // Matched by name so this does not depend on clean-webpack-plugin being
    // resolvable from the project root (it is only a transitive dependency).
    if ( ! plugin || plugin.constructor.name !== 'CleanWebpackPlugin' ) {
        return plugin;
    }

    if ( Array.isArray( plugin.cleanAfterEveryBuildPatterns ) ) {
        plugin.cleanAfterEveryBuildPatterns = [
            ...plugin.cleanAfterEveryBuildPatterns,
            KEEP_IN_BUILD,
        ];
    }

    if ( Array.isArray( plugin.cleanOnceBeforeBuildPatterns ) ) {
        plugin.cleanOnceBeforeBuildPatterns = [
            ...plugin.cleanOnceBeforeBuildPatterns,
            KEEP_IN_BUILD,
        ];
    }

    return plugin;
} );

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
    plugins,
};
