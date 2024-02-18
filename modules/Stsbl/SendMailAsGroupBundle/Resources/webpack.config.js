
let merge = require('webpack-merge');
let path = require('path');
let baseConfig = require(path.join(process.env.WEBPACK_BASE_PATH, 'webpack.config.base.js'));

let webpackConfig = {
    entry: {
        'js/mail-as-group_form': './assets/js/mail-as-group_form.js',
        'js/mail-as-group_scroll': './assets/js/mail-as-group_scroll.js',
        'css/groupmail': './assets/less/groupmail.less',
    },
};

module.exports = merge(baseConfig.get(__dirname), webpackConfig);
