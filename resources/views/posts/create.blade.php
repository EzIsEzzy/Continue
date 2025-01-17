<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Create Post</title>
</head>
<body>
    <form action="{{url('posts/store')}}" method="post">
        @csrf

        @if(session('success'))
        <p class="alert alert-success">
            {{session('success')}}
        </p>
        @endif
        @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
        @endif

        <textarea name="content" id="" cols="30" rows="10" placeholder="Write soemthing...">

        </textarea>
        <input type="submit" value="post"  name="post">
    </form>
</body>
</html>
