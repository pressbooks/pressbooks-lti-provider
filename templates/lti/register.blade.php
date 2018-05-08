<html>
<head>
    <title>{{ $title }}</title>
    <style>
        body {
            margin: 1em;
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 14px;
            font-style: normal;
            font-variant: normal;
            font-weight: 400;

        }

        p {
            line-height: 20px;
        }

        h1 {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            font-size: 24px;
            font-style: normal;
            font-variant: normal;
            font-weight: 500;
            line-height: 26.4px;
        }
    </style>
</head>
<body>
<h1>{{ sprintf(__('Registration request for %s', 'pressbooks-lti-provider'), $title) }}</h1>

<p><a href='{!! $cancel_url !!}'>{{ __('Go back', 'pressbooks-lti-provider') }}</a> <a href='{!! $success_url !!}'>{{ __('Proceed with registration &rarr;', 'pressbooks-lti-provider') }}</a></p>
</body>
</html>
