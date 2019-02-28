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
    </style>
</head>
<body>
<h1>{{ sprintf(__('Content item selection request for %s', 'pressbooks-lti-provider'), $title) ?></h1>

<form action="{!! $url !!}" method="post">

    <input id="item-0" type="radio" name="section" value="0" checked> <label for="item-0"><?php _e('Cover Page', 'pressbooks-lti-provider') ?></label><br>

    @foreach ($book_structure['front-matter'] as $k => $v)
        <input id="item-{{ $v['ID'] }}" type="radio" name="section" value="{{ $v['ID'] }}"> <label for="item-{{ $v['ID'] }}"><?php _e('Front Matter', 'pressbooks-lti-provider') ?>: {{ $v['post_title']  }}</label><br>
    @endforeach

    @foreach ($book_structure['part'] as $key => $value)
        @foreach ($value['chapters'] as $k => $v)
            <input id="item-{{ $v['ID'] }}" type="radio" name="section" value="{{ $v['ID'] }}"> <label for="item-{{ $v['ID'] }}"><?php _e('Chapter', 'pressbooks-lti-provider') ?>: {{ $v['post_title']  }}</label><br>
        @endforeach
    @endforeach

    @foreach ($book_structure['back-matter'] as $k => $v)
        <input id="item-{{ $v['ID'] }}" type="radio" name="section" value="{{ $v['ID'] }}"> <label for="item-{{ $v['ID'] }}"><?php _e('Back Matter', 'pressbooks-lti-provider') ?>: {{ $v['post_title']  }}</label><br>
    @endforeach

    <p><input type="submit" value="<?php _e('Submit', 'pressbooks-lti-provider') ?>"/></p>
</form>

</body>
</html>
