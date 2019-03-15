let mix = require( 'laravel-mix' );
let path = require( 'path' );
let normalizeNewline = require( 'normalize-newline' );
let fs = require( 'fs' );

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application, as well as bundling up your JS files.
 |
 */

// Normalize Newlines
const normalizeNewlines = ( dir ) => {
	fs.readdirSync( dir ).forEach( function( file ) {
		file = path.join( dir, file );
		fs.readFile( file, 'utf8', function( err, buffer ) {
			if ( err ) return console.log( err );
			buffer = normalizeNewline( buffer );
			fs.writeFile( file, buffer, 'utf8', function( err ) {
				if ( err ) return console.log( err );
			} );
		} );
	} );
};

mix
	.version()
	.options( { processCssUrls: false } )
	.setPublicPath( path.join( 'assets', 'dist' ) )
	.js( 'assets/src/scripts/pressbooks-lti-provider.js', 'assets/dist/scripts/' )
	.sass( 'assets/src/styles/pressbooks-cc-exports.scss', 'assets/dist/styles/' )
	.sass( 'assets/src/styles/pressbooks-lti-consumers.scss', 'assets/dist/styles/' )
	.copyDirectory( 'assets/src/images', 'assets/dist/images' )
	.then( () => {
		normalizeNewlines( 'assets/dist/scripts/' );
		normalizeNewlines( 'assets/dist/styles/' );
	} );

// Full API
// mix.js(src, output); <-- compile (ES2015 syntax, modules, ...) !and! minify
// mix.scripts(src, output); <-- just minify
// mix.react(src, output); <-- Identical to mix.js(), but registers React Babel compilation.
// mix.extract(vendorLibs);
// mix.sass(src, output);
// mix.standaloneSass('src', output); <-- Faster, but isolated from Webpack.
// mix.fastSass('src', output); <-- Alias for mix.standaloneSass().
// mix.less(src, output);
// mix.stylus(src, output);
// mix.browserSync( 'my-site.dev' );
// mix.combine(files, destination);
// mix.babel(files, destination); <-- Identical to mix.combine(), but also includes Babel compilation.
// mix.copy(from, to);
// mix.copyDirectory(fromDir, toDir);
// mix.minify(file);
// mix.sourceMaps(); // Enable sourcemaps
// mix.version(); // Enable versioning.
// mix.disableNotifications();
// mix.setPublicPath( 'path/to/public' );
// mix.setResourceRoot( 'prefix/for/resource/locators' );
// mix.autoload({}); <-- Will be passed to Webpack's ProvidePlugin.
// mix.webpackConfig({}); <-- Override webpack.config.js, without editing the file directly.
// mix.then(function () {}) <-- Will be triggered each time Webpack finishes building.
// mix.options({
//   extractVueStyles: false, // Extract .vue component styling to file, rather than inline.
//   processCssUrls: true, // Process/optimize relative stylesheet url()'s. Set to false, if you don't want them touched.
//   purifyCss: false, // Remove unused CSS selectors.
//   uglify: {}, // Uglify-specific options. https://webpack.github.io/docs/list-of-plugins.html#uglifyjsplugin
//   postCss: [] // Post-CSS options: https://github.com/postcss/postcss/blob/master/docs/plugins.md
// });
