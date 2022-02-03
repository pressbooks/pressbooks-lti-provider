let path = require( 'path' );
let mix = require( 'laravel-mix' );

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

mix
	.version()
	.options( { processCssUrls: false } )
	.setPublicPath( path.join( 'assets', 'dist' ) )
	.js( 'assets/src/scripts/pressbooks-lti-provider.js', 'assets/dist/scripts/' )
	.sass( 'assets/src/styles/pressbooks-cc-exports.scss', 'assets/dist/styles/' )
	.sass( 'assets/src/styles/pressbooks-lti-consumers.scss', 'assets/dist/styles/' )
	.copyDirectory( 'assets/src/images', 'assets/dist/images' )
