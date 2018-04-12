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
<h1>Registration Request For: {{ $title }}</h1>

<p>If you can read this then Pressbooks and the LMS have done a cryptographic dance and they are cool with each other.</p>

<p>After the user clicks Register, the LMS <em>(the LTI Consumer)</em> and Pressbooks <em>(the LTI Provider)</em> will be connected.</p>

<p>Accepting any LTI Tool Consumer makes sense for open web content, the same way you could find open web content using Google, but <b><i>it doesn't make sense</i></b> if the
    webbook is private. This is a great place to make some <b>product management decisions</b>. This is simply a web form. This can be anything. Some examples:

<ul>
    <li><b>Password protected?</b> If the password was wrong then clicking Register would loop back and say "Invalid password, try again."</li>
    <li><b>Whitelist?</b> If the request is coming from an unknown domain we display "Unauthorized" instead of this page.</li>
    <li><b>This is fine?</b> We can check the permissions later. "Register" doesn't mean that we have to let users do anything. We can put off this action or event to a later time.</li>
</ul>

<p>There are probably some LTI conventions we don't know about. We should ask someone who knows more about LTI what they think.</p>

<p><a href='{!! $success_url !!}' class='button'>Register</a></p>
</body>
</html>