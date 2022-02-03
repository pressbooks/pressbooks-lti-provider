let path = require( 'path' );
let mix = require( 'laravel-mix' );
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
