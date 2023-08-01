// phpcs:ignoreFile
/**
 * Custom configuration for Storybook.
 */

// Using Production version of the asset building Webpack configuration to
// unify the building pipeline.
const path = require('path');
const { merge } = require('webpack-merge');
const webpack = require('webpack');
const custom = require('../webpack/webpack.prod');
const scssVariables = require('./importer.scss_variables');
const iconUtils = require('../components/00-base/icon/icon.utils');
const backgroundUtils = require('../components/00-base/background/background.utils');
const logoUtils = require('../components/02-molecules/logo/logo.utils');
const addonConfig = require('./addon-config').default();

const customPlugin = new webpack.DefinePlugin({
  BACKGROUNDS: JSON.stringify(backgroundUtils.getBackgrounds()),
  ICONS: JSON.stringify(iconUtils.getIcons()),
  LOGOS: JSON.stringify(logoUtils.getLogos()),
  SCSS_VARIABLES: JSON.stringify(scssVariables.getVariables()),
});

module.exports = {
  stories: [
    '../components/**/*.stories.js',
  ],
  addons: addonConfig,
  webpackFinal: async (config) => {
    // Replace normal CSS import with stories CSS import, which already includes
    // normal CSS import. This is to allow to resolve variables and mixins in
    // stories CSS.
    custom.entry = custom.entry.main.map((value) => (value.indexOf('css.js') > -1 ? path.resolve(__dirname, 'css.stories.js') : value));

    // Modify common configs to let Storybook take over.
    delete custom.output;
    custom.plugins = [
      customPlugin,
    ];
    // Special case: override whatever loader is used to load styles with a
    // style-loader in order to have styles injected during the runtime.
    custom.module.rules[1].use[0] = 'style-loader';

    return merge(config, custom);
  },
};
