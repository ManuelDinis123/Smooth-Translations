<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Testing</title>
</head>
<body>
    
    <h1>{{$translator->translate("translated string")}}</h1>
    <h1>{{$translator->translate("hello world")}}</h1>
    <h1>{{$translator->translate("new table")}}</h1>

    {{$translator->get_languages(null, true)}}
    <hr>
    {{$translator->get_texts(null, true)}}
    <hr>
    {{$translator->get_translations(null, true)}}

</body>
</html>