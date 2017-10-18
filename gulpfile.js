const gulp = require('gulp');
const $ = require('gulp-load-plugins')();

const autoprefixer = require('autoprefixer');
const cssnano = require('cssnano');

gulp.task('build', ['css', 'lib']);

gulp.task('watch', ['css'], () => {
    gulp.watch('./web/scss/*.scss', ['css'])
});

gulp.task('css', () => gulp.src('./web/scss/**/**/*.scss', { base: './web/scss' })
    .pipe($.sourcemaps.init())
    .pipe($.sass())
    .pipe($.postcss([
        autoprefixer({browsers: ['last 2 versions']}),
        cssnano(),
    ]))
    .pipe($.sourcemaps.write('.'))
    .pipe(gulp.dest('./web/css'))
);

const libs = {
    './node_modules/jquery/dist': 'jquery',
    './node_modules/popper.js/dist': 'popper.js',
    './node_modules/bootstrap/dist': 'bootstrap',
    './node_modules/font-awesome': 'font-awesome',
    './node_modules/select2/dist': 'select2',
    './node_modules/select2-bootstrap-theme/dist': 'select2-bootstrap-theme',
};

gulp.task('lib', () => {
    Object.keys(libs).forEach((k) => {
        gulp.src(k).pipe($.symlink('./web/lib/' + libs[k], {force: true}));
    });
});
