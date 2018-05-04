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

        .button {
            font-family: "Helvetica Neue", Helvetica, Arial, sans-serif;
            text-decoration: none;
            background-color: #EEEEEE;
            color: #333333;
            padding: 2px 6px 2px 6px;
            border: 1px solid #CCCCCC;
            border-right-color: #333333;
            border-bottom-color: #333333;
        }
    </style>
</head>
<body>
<h1>{{ __('Registration request for', 'pressbooks-lti-provider') }}: {{ $title }}</h1>

<p><a href='{!! $success_url !!}' class='button'>{{ __('Register', 'pressbooks-lti-provider') }}</a> <a href='{!! $cancel_url !!}' class='button'>{{ __('Cancel', 'pressbooks-lti-provider') }}</a></p>
</body>
</html>