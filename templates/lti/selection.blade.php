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
<h1>{{ __('Content item selection request for', 'pressbooks-lti-provider') }}: {{ $title }}</h1>

<form action="{!! $url !!}" method="post">

    <input type="radio" name="section" value="0" checked> {{ __('Cover Page', 'pressbooks-lti-provider') }}<br>

    @foreach ($book_structure['front-matter'] as $k => $v)
        <input type="radio" name="section" value="{{ $v['ID'] }}"> {{ __('Front Matter', 'pressbooks-lti-provider') }}: {{ $v['post_title']  }}<br>
    @endforeach

    @foreach ($book_structure['part'] as $key => $value)
        @foreach ($value['chapters'] as $k => $v)
            <input type="radio" name="section" value="{{ $v['ID'] }}"> {{ __('Chapter', 'pressbooks-lti-provider') }}: {{ $v['post_title']  }}<br>
        @endforeach
    @endforeach

    @foreach ($book_structure['back-matter'] as $k => $v)
        <input type="radio" name="section" value="{{ $v['ID'] }}"> {{ __('Back Matter', 'pressbooks-lti-provider') }}: {{ $v['post_title']  }}<br>
    @endforeach

    <p><input type="submit" value="{{ __('Submit', 'pressbooks-lti-provider') }}"/></p>
</form>

</body>
</html>
