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

<p>For example, we could build a form with radio buttons. The user could select a section. Once they press submit we would send back a link to that chapter.</p>

<p>For now when you press Submit, we'll returbn link to my wife's web comic, just to see what happenss</p>

<p><a href='{!! $url !!}' class='button'>Submit</a></p>
</body>
</html>