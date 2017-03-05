const gulp = require('gulp');
const run = require('gulp-run');
const notify = require('gulp-notify');
const phpspec = require('gulp-phpspec');

gulp.task('test', function(){
    gulp.src('tests/**/*.php')
        .pipe(run('clear'))
        .pipe(phpspec('', { notify: true }))
        .on('error', notify.onError({
            title: 'Crap',
            message: 'Your tests failed!'
        }))
        .pipe(notify({
            title: 'Success',
            message: 'All tests have returned green!'
        }));
});

gulp.task('watch', function(){
    gulp.watch(['app/**/*.php', 'tests/**/*.php'], ['test']);
});

gulp.task('default', ['test', 'watch']);