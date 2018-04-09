let mix = require( 'laravel-mix' );

mix
	.setPublicPath( 'dist' )
	.js( 'assets/scripts/pressbooks-lti-provider.js', 'dist/scripts/' )
	.sass( 'assets/styles/pressbooks-lti-provider.scss', 'dist/styles/' )
	.copyDirectory( 'assets/images', 'dist/images' )
	.version();
