let mix = require( 'laravel-mix' );

mix
	.setPublicPath( 'assets/dist' )
	.js( 'assets/src/scripts/pressbooks-lti-provider.js', 'assets/dist/scripts/' )
	.sass( 'assets/src/styles/pressbooks-lti-provider.scss', 'assets/dist/styles/' )
	.copyDirectory( 'assets/src/images', 'assets/dist/images' )
	.version();
