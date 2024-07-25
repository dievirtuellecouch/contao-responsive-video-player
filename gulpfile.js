import {deleteAsync} from 'del';
import {WebpackManifestPlugin} from 'webpack-manifest-plugin';
import autoprefixer from 'autoprefixer';
import cssdeclarationsorter from 'css-declaration-sorter';
import cssnano from 'cssnano';
import dartSass from 'sass';
import gulp from 'gulp';
import gulpRev from "gulp-rev";
import gulpRevManifest from "gulp-revmanifest";
import gulpSass from 'gulp-sass';
import inlineSvg from '@zemax/sass-svg';
import path from 'path';
import postcss from 'gulp-postcss';
import webpackStream from 'webpack-stream';

const sass = gulpSass(dartSass);

const resolve = function (dir) {
    return path.join(path.dirname(''), dir)
}

const paths = {
    'images': {
        'src': 'assets/images/**/*',
        'dest': 'public/',
        'watch': 'src/images/**/*',
    },
    'styles': {
        'base': 'assets/styles/',
        'src': {
            'main': 'assets/styles/video-player.scss',
        },
        'dest': 'public/',
        'watch': 'assets/styles/**/*.scss',
    },
    'webpack': {
        'base': 'assets/scripts/',
        'src': 'assets/scripts/**/*.js',
        'dest': 'public/',
        'entry': {
            'video-player': [
               './assets/scripts/video-player/index.js'
            ],
        },
        'watch': 'assets/scripts/**/*.js',
    },
};

export const clearPublic = async function() {
    return deleteAsync([
        'public/*',
    ]);
};

/**
 * Handles building style files.
 * @param {function} done Callback
 */
export const styles = function (done) {
    const plugins = [
        autoprefixer(),
        cssdeclarationsorter(),
        cssnano(),
    ];

    for (const [name, path] of Object.entries(paths.styles.src)) {
        gulp.src(
            path,
            {
                'base': paths.styles.base,
            }
        )
            .pipe(sass.sync({
                functions: Object.assign( {}, inlineSvg(paths.images.src) ),
            }).on(
                'error',
                sass.logError
            ))
            .pipe(postcss(plugins))
            .pipe(gulpRev())
            .pipe(gulp.dest(paths.styles.dest))
            .pipe(gulpRevManifest({
                path: 'manifest.json',
                merge: true,
                cwd: paths.styles.dest,
            }))
            .pipe(gulp.dest(paths.styles.dest))
        ;
    }

    done();
};

export const webpack = function() {
    return gulp.src(
        paths.webpack.src,
        {
            'base': paths.webpack.base,
        }
    )
        .pipe(webpackStream({
            entry: paths.webpack.entry,
            output: {
                publicPath: 'public',
                filename: '[name]-[chunkhash].js',
            },
            resolve: {
                alias: {
                    '@': resolve('/' + paths.webpack.base)
                }
            },
            watch: false,
            mode: 'production',
            plugins: [
                new WebpackManifestPlugin({
                    publicPath: '',

                }),
            ],
        }))
        .pipe(gulp.dest(paths.webpack.dest))
    ;
};

export const copyImagesToPublic = function() {
    return gulp.src(
        paths.images.src
    )
        .pipe(gulp.dest(paths.images.dest));
};

const build = gulp.series(
    clearPublic,
    webpack,
    styles,
    copyImagesToPublic,
);

export default build;
