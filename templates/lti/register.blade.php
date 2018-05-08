<html>
<head>
    <title>{{ $title }}</title>
    <style>
        body {
            margin: 1em;
			font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;
            font-style: normal;
            font-variant: normal;
            font-weight: 400;
        }

        p {
            line-height: 1.5;
        }

        h1 {
            font-size: 1.25em;
            font-style: normal;
            font-variant: normal;
            font-weight: 500;
        }

		a {
			margin-right: 0.5em;
			color: #B01109;
		}

		a:hover,
		a:active {
			color: #7F0C07;
		}
    </style>
</head>
<body>
<h1>{{ sprintf(__('Registration request for %s', 'pressbooks-lti-provider'), $title) }}</h1>

<p><a href='{!! $cancel_url !!}'>{{ __('Go back', 'pressbooks-lti-provider') }}</a> <a href='{!! $success_url !!}'>{{ __('Proceed with registration &rarr;', 'pressbooks-lti-provider') }}</a></p>
</body>
</html>
