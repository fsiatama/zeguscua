var gulp       = require('gulp');
var stylus     = require('gulp-stylus')
var uglify     = require('gulp-uglify')

var jade       = require('gulp-jade')
var rename     = require('gulp-rename')
var concat     = require('gulp-concat-css')
var nib        = require('nib')
var minify     = require('gulp-minify-css')

var newer      = require('gulp-newer')
var imagemin   = require('gulp-imagemin')


/********************************** bundle images ***************************************/

var imgSrc = './frontEndLib/img/**';
var imgDest = './public_html/img';

// Minify any new images 
gulp.task('images', function() {
 
  // Add the newer pipe to pass through newer images only 
  return gulp.src(imgSrc)
      .pipe(newer(imgDest))
      .pipe(imagemin({ optimizationLevel: 3, progressive: true, interlaced: true, use: [] }))
      .pipe(gulp.dest(imgDest));
 
});

gulp.task('images:watch', function() {

  return gulp.watch(imgSrc, ['images'])

})



/********************************** bundle js ***************************************/

gulp.task('jsApp', function() {
  return jsApp()
})

gulp.task('jsApp:watch', function() {
  
  return gulp.watch( [ 'frontEndLib/js/**/*.js' ], ['jsApp'] )
})

/********************************** bundle stylus ***************************************/
gulp.task('stylApp', function() {
  return stylApp();
})
gulp.task('stylApp:livereload', function() {
  return stylApp().pipe( livereload( { start: true } ) )
})
gulp.task('stylApp:watch', function() {
  return gulp.watch( [ 'frontEndLib/styles/**/*.styl' ], ['stylApp'] )
})

/********************************** bundle jade ***************************************/
gulp.task('htmlTplApp', function() {
  return htmlTplApp();
})
gulp.task('htmlTplApp:watch', function() {
  return gulp.watch( [ 'frontEndLib/templates/**/*.jade' ], ['htmlTplApp'] )
})

gulp.task( 'watch', ['stylApp:watch', 'htmlTplApp:watch', 'jsApp:watch', 'images:watch'])


function jsApp (b) {
  return gulp.src('frontEndLib/js/**/*.js')
  .pipe(uglify())
  .pipe(gulp.dest('./public_html/js/'))
}

function htmlTplApp () {
  return gulp.src('frontEndLib/templates/*.jade')
  .pipe(jade())
  .pipe(rename({
    extname: ".tpl.php"
  }))
  .pipe(gulp.dest('app/views/'))
}

function stylApp () {
  return gulp.src('frontEndLib/styles/main.styl')
  .pipe(stylus({ use: nib() }))
  .pipe(concat('main.css'))
  .pipe(minify())
  .pipe(gulp.dest('public_html/css/'))
}