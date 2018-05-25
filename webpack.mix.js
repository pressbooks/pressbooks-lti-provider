let mix = require( 'laravel-mix' );
let path = require( 'path' );

mix
	.setPublicPath( path.join( 'assets', 'dist' ) )
	.sass( 'assets/src/styles/pressbooks-cc-exports.scss', 'assets/dist/styles/' )
	.sass(
		'assets/src/styles/pressbooks-lti-consumers.scss',
		'assets/dist/styles/'
	)
	.copyDirectory( 'assets/src/images', 'assets/dist/images' )
	.version()
	.options( { processCssUrls: false } );
