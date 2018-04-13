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
<h1>Content Item Selection Request For: {{ $title }}</h1>

<p>Pressbooks has received a ContentItemSelection request for a single LtiLinkItem</p>

<p>This is a great place to make some <b>product management decisions</b>. This is simply a web form. What do we want to do here? </p>

<p>For example, we could build a form with radio buttons. The user could select a section. Once they press submit we would send back a link.</p>

<form action="{!! $url !!}" method="post">

    <input type="radio" name="section" value="0" checked> Home Page<br>

    @foreach ($book_structure['front-matter'] as $k => $v)
        <input type="radio" name="section" value="{{ $v['ID'] }}"> Front-matter: {{ $v['post_title']  }}<br>
    @endforeach

    @foreach ($book_structure['part'] as $key => $value)
        @foreach ($value['chapters'] as $k => $v)
            <input type="radio" name="section" value="{{ $v['ID'] }}"> Chapter: {{ $v['post_title']  }}<br>
        @endforeach
    @endforeach

    @foreach ($book_structure['back-matter'] as $k => $v)
        <input type="radio" name="section" value="{{ $v['ID'] }}"> Back-matter: {{ $v['post_title']  }}<br>
    @endforeach

    <p><input type="submit" value="Submit"/></p>
</form>

</body>
</html>