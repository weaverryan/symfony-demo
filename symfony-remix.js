const ExtractTextPlugin = require("extract-text-webpack-plugin");
const ManifestPlugin = require('webpack-manifest-plugin');
const webpack = require('webpack');
const path = require('path');
const fs = require('fs');

function DeleteUnusedEntriesJSPlugin(entriesToDelete = []) {
    this.entriesToDelete = entriesToDelete;
}
DeleteUnusedEntriesJSPlugin.prototype.apply = function(compiler) {
    compiler.plugin('emit', (compilation, callback) => {

        // loop over output chunks
        compilation.chunks.forEach((chunk) => {
            // see of this chunk is one that needs its .js deleted
            if (this.entriesToDelete.includes(chunk.name)) {
                let fileDeleteCount = 0;

                // loop over the output files and find the 1 that ends in .js
                chunk.files.forEach((filename) => {
                    if (path.extname(filename) == '.js') {
                        fileDeleteCount++;
                        delete compilation.assets[filename];
                    }
                });

                // todo - also make sure all files were deleted
                // sanity check: make sure 1 file was deleted
                // if there's some edge case where multiple .js files
                // or 0 .js files might be deleted, I'd rather error
                if (fileDeleteCount != 1) {
                    throw new Error(`Problem deleting JS entry for ${chunk.name}: ${fileDeleteCount} files were deleted`);
                }
            }
        });

        callback();
    });
};

class Remix {
    constructor(nodeEnv = null) {
        // todo - this will change when the path of this file changes
        this.rootDir = __dirname;
        this.outputPath = null;
        this.publicPath = null;
        this.publicCDNPath = null;
        this.entries = new Map();
        this.styleEntries = new Map();
        this.useVersioning = false;
        this.useSourceMaps = false;
        this.commonsVendorName = null;
        this.providedVariables = {};

        this.nodeEnvironment = nodeEnv !== null ? nodeEnv : process.env.NODE_ENV;
    }

    setOutputPath(outputPath) {
        if(!path.isAbsolute(outputPath)) {
            outputPath = path.resolve(__dirname, outputPath);
        }

        if (!fs.existsSync(outputPath)) {
            fs.mkdirSync(outputPath);
        }

        // todo - some error checking on path exists eventually!
        // todo - convert to absolute path if not absolute!
        this.outputPath = outputPath;

        return this;
    }

    setPublicPath(publicPath) {
        // ugly way to guarantee /path/ format
        publicPath = publicPath.replace(/^\//,"");
        publicPath = publicPath.replace(/\/$/,"");
        publicPath = '/'+publicPath+'/';

        // todo - make sure that a full URL is NOT passed here!
        // this is because (A) it would change the keys in
        // the Manifest file to be the full URLs... probably not
        // what we want

        this.publicPath = publicPath;

        return this;
    }

    setPublicCDNPath(publicCdnPath) {
        // ugly way to guarantee a trailing slash
        publicCdnPath = publicCdnPath.replace(/^\//,"");
        publicCdnPath = publicCdnPath.replace(/\/$/,"");
        publicCdnPath = publicCdnPath+'/';

        this.publicCDNPath = publicCdnPath;

        return this;
    }

    addEntry(name, src) {
        this.entries.set(name, src);

        return this;
    }

    addStylesEntry(name, src) {
        // todo make sure there are no name conflicts with JS entries
        // make sure there are no JS files included in this
        this.styleEntries.set(name, src);

        return this;
    }

    enableVersioning(enabled = true) {
        this.useVersioning = enabled;

        return this;
    }

    enableSourceMaps(enabled = true) {
        this.useSourceMaps = enabled;

        return this;
    }

    /**
     *
     * @param name The chunk name (e.g. vendor)
     * @param files Array of files to put in the vendor entry
     */
    extractVendorEntry(name, files) {
        this.commonsVendorName = name;

        // todo - error if there is already an entry by this name

        this.addEntry(name, files);

        return this;
    }

    /**
     * Magically make some variables available everywhere!
     *
     * Usage:
     *
     *  Remix.autoProvideVariables({
     *      $: 'jquery',
     *      jQuery: 'jquery'
     *  });
     *
     *  Then, whenever $ or jQuery are found in any
     *  modules, webpack will automatically require
     *  the "jquery" module so that the variable is available.
     *
     *  This is useful for older packages, that might
     *  expect jQuery (or something else) to be a global variable.
     *
     * @param variables
     * @returns {Remix}
     */
    autoProvideVariables(variables) {
        // merge new variables into the object
        this.providedVariables = Object.assign(
            variables,
            this.providedVariables
        );

        return this;
    }

    /**
     * Makes jQuery available everywhere. Equivalent to
     *
     *  Remix.autoProvideVariables({
     *      $: 'jquery',
     *      jQuery: 'jquery'
     *  });
     */
    autoProvidejQuery() {
        this.autoProvideVariables({
            $: 'jquery',
            jQuery: 'jquery'
        });

        return this;
    }

    isProduction() {
        return this.nodeEnvironment == 'production';
    }

    getWebpackConfig() {
        const config = {
            entry: this._buildEntryConfig(),
            output: this._buildOutputConfig(),
            module: {
                rules: this._buildRulesConfig(),
            },
            plugins: this._buildPluginsConfig()
        };

        if (this.useSourceMaps) {
            // todo this should be configurable
            config.devtool = '#inline-source-map';
        }

        config.devServer = {
            // todo - make port (other stuff?) configurable
            // todo - bah! I think this should point to web, not web/builds!
            contentBase: __dirname+'/web',
        };

        return config;
    }

    _buildEntryConfig() {
        const entry = {};

        for (const [entryName, entryChunks] of this.entries) {
            // entryFile could be an array, we don't care
            entry[entryName] = entryChunks;
        }

        for (const [entryName, entryChunks] of this.styleEntries) {
            // entryFile could be an array, we don't care
            entry[entryName] = entryChunks;
        }

        return entry;
    }

    _buildOutputConfig() {
        // todo exception if output props aren't set, or aren't
        // set properly

        return {
            path: this.outputPath, // ./web/builds
            // todo - this would need have the hash later
            filename: this.useVersioning ? '[name].[chunkhash].js' : '[name].js',

            // if a CDN is provided, use that for the public path so
            // that split chunks load via the CDN
            // todo should it be ./builds?
            publicPath: this.publicCDNPath ? this.publicCDNPath : this.publicPath
        };
    }

    _getSourceMapOption() {
        return this.useSourceMaps ? '?sourceMap' : '';
    }

    _buildRulesConfig() {
        return [
            // todo .jsx
            // from .jsx loader: 'babel-loader?{"cacheDirectory":true,"presets":[["es2015",{"modules":false}]]}' },
            // what's the modules=false? cacheDirectory?
            {
                test: /\.css$/,
                //                use: ['style-loader', 'css-loader']
                use: ExtractTextPlugin.extract({
                    fallback: 'style-loader'+this._getSourceMapOption(),
                    use: 'css-loader'+this._getSourceMapOption(),
                })
            },
            {
                // and also .sass?
                test: /\.scss$/,
                use: ExtractTextPlugin.extract({
                    fallback: 'style-loader'+this._getSourceMapOption(),
                    use: [
                        // this is what actually extracts the final CSS into a separate file
                        // {
                        //     loader: '/Users/weaverryan/Sites/os/symfony-demo-laravel-mix/node_modules/extract-text-webpack-plugin/loader.js',
                        //     options: {id: 1, omit: 1, remove: true}
                        // },
                        // {loader: 'style-loader'},
                        {
                            loader: 'css-loader'+this._getSourceMapOption(),
                        },
                        /*
                         * couldn't/shouldn't this be done in sass?
                         * regardless, this needs some extra config, doesn't
                         * work currently :)
                         * When we re-add, don't forget ?sourceMap
                         { loader: 'postcss-loader' },
                         */
                        {
                            loader: 'resolve-url-loader'+this._getSourceMapOption(),
                        },
                        {
                            loader: 'sass-loader',
                            options: {
                                precision: 8,
                                outputStyle: 'expanded',
                                // always enabled, needed by resolve-url-loader
                                sourceMap: true
                            }
                        },
                    ]
                })
            },
            {
                // and also .sass?
                test: /\.less/,
                use: ExtractTextPlugin.extract({
                    fallback: 'style-loader'+this._getSourceMapOption(),
                    use: [
                        // this is what actually extracts the final CSS into a separate file
                        // {
                        //     loader: '/Users/weaverryan/Sites/os/symfony-demo-laravel-mix/node_modules/extract-text-webpack-plugin/loader.js',
                        //     options: {id: 1, omit: 1, remove: true}
                        // },
                        // {loader: 'style-loader'},
                        {
                            loader: 'css-loader'+this._getSourceMapOption()
                        },
                        /*
                         * couldn't/shouldn't this be done in sass?
                         * regardless, this needs some extra config, doesn't
                         * work currently :)
                         { loader: 'postcss-loader' },
                         */
                        {
                            loader: 'less-loader'+this._getSourceMapOption()
                        },
                    ]
                })
            },
            {
                test: /\.(png|jpg|gif)$/,
                loader: 'file-loader',
                options: {
                    name: 'images/[name].[ext]?[hash]',
                    publicPath: '/'
                }
            },
            {
                // didn't I have some CORS problem with fonts?
                test: /\.(woff2?|ttf|eot|svg|otf)$/,
                loader: 'file-loader',
                options: {
                    name: 'fonts/[name].[ext]?[hash]',
                    publicPath: '/'
                }
            },
        ];
    }

    _buildPluginsConfig() {
        let plugins = [
            // all CSS/SCSS content (due to the loaders above) will be
            // extracted into an [entrypointname].css file
            // the result is that NO css will be inlined, which
            // may not be ideal - especially from a RAD perspective - in
            // some cases, as you now need to include the JS AND CSS file.
            // But it is at least predictable: in all cases you need to
            // include the JS and CSS file (well, unless there is no CSS,
            // and thus no CSS file is emitted!)
            new ExtractTextPlugin({
                filename: this.useVersioning ? '[name].[contenthash].css' : '[name].css',
                // TODO this will probably be configurable: should webpack
                // crawl through and ALSO include CSS that is included
                // in "code-split" JS files
                allChunks: false
            }),

            // register the pure-style entries that should be deleted
            // should we instantiate this once in construct? And then
            // just add style entries to it along the way?
            new DeleteUnusedEntriesJSPlugin(this.styleEntries.keys()),

            // dumps the manifest.json file
            new ManifestPlugin({
                // prefixes all keys with builds/, which allows us to refer to
                // the paths as builds/main.css in Twig, instead of just main.css
                // strip the opening slash
                basePath: this.publicPath.replace(/^\//,"")
            }),

            /**
             * This section is a bit mysterious. The "minimize"
             * true is read and used to minify the CSS.
             * But as soon as this plugin is included
             * at all, SASS begins to have errors, until the context
             * and output options are specified. At this time, I'm
             * quite unsure what's going on here
             * https://github.com/jtangelder/sass-loader/issues/285
             */
            new webpack.LoaderOptionsPlugin({
                minimize: this.isProduction(),
                debug: !this.isProduction(),
                options: {
                    context: this.rootDir,
                    output: { path: this.outputPath }
                }
            })
        ];

        let moduleNamePlugin;
        if (this.isProduction()) {
            // shorter, and obfuscated module ids
            moduleNamePlugin = new webpack.HashedModuleIdsPlugin();
        } else {
            // human-readable module names, helps debug in HMR
            moduleNamePlugin = new webpack.NamedModulesPlugin();
        }
        plugins = plugins.concat([moduleNamePlugin]);

        if (Object.keys(this.providedVariables).length > 0) {
            plugins = plugins.concat([
                new webpack.ProvidePlugin(this.providedVariables)
            ]);
        }

        // if we're extracting a vendor chunk, set it up!
        if (this.commonsVendorName) {
            plugins = plugins.concat([
                new webpack.optimize.CommonsChunkPlugin({
                    name: [this.commonsVendorName, 'manifest'],
                    minChunks: Infinity,
                }),
            ]);
        }

        if (this.isProduction()) {
            plugins = plugins.concat([
                new webpack.DefinePlugin({
                    'process.env': {
                        NODE_ENV: '"production"'
                    }
                }),

                // todo - options here should be configurable
                new webpack.optimize.UglifyJsPlugin({})
            ]);
        }

        return plugins;
    }
}

// is exporting a new instance ok?
module.exports = new Remix();
