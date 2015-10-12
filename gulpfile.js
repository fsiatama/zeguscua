var gulp       = require('gulp');
var stylus     = require('gulp-stylus')
var uglify     = require('gulp-uglify')

var jade       = require('gulp-jade')
var rename     = require('gulp-rename')
var concat     = require('gulp-concat')
var nib        = require('nib')
var minify     = require('gulp-minify-css')

var newer      = require('gulp-newer')
var imagemin   = require('gulp-imagemin')


//var imageResize = require('gulp-image-resize')
var gm = require('gulp-gm')


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

gulp.task('thumbnails', function () {
  gulp.src('frontEndLib/img/gallery/*.jpg')
    .pipe(gm(function (gmfile) {
     
      return gmfile.resize(200, 200);
 
    }))
    //.pipe(imageResize({ width : 100, imageMagick: true })).on('error', swallowError)
    //.pipe(rename(function (path) { 
      //console.log(path.basename);
      //path.basename += "-thumbnail"; 
    //}))
    .pipe(gulp.dest('frontEndLib/img/gallery/thumbnail/'));
});


/********************************** bundle js ***************************************/

gulp.task('jsPlugins', function () {
  return jsPlugins();
})
gulp.task('jsApp', ['jsPlugins'], function() {
  return jsApp()
})

gulp.task('jsApp:watch', function() {
  
  return gulp.watch( [ 'frontEndLib/js/*.js' ], ['jsApp'] )
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

function jsPlugins () {
  return gulp.src('frontEndLib/js/plugins/*.js')
  .pipe(concat('plugins.js'))
  .pipe(uglify())
  .pipe(gulp.dest('./public_html/js/'))
}

function jsApp () {
  return gulp.src('frontEndLib/js/*.js')
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
  .pipe(stylus({ use: nib(), import: ['nib'] }))
  .pipe(concat('main.css'))
  .pipe(minify())
  .pipe(gulp.dest('public_html/css/'))
}






function swallowError (error) {

  // If you want details of the error in the console
  console.log(error.toString());

  this.emit('end');
}