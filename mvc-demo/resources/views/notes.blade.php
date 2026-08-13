<!DOCTYPE html>
<html>
<head>
    <title>My Notes</title>
    <style>
        body{
            font-family: Arial, sans-serif;
            background:#f5f7fb;
            padding:40px;
        }

        .container{
            max-width:600px;
            margin:auto;
            background:white;
            padding:25px;
            border-radius:12px;
            box-shadow:0 5px 20px rgba(0,0,0,.1);
        }

        h1{
            text-align:center;
        }

        input{
            width:100%;
            padding:12px;
            border:1px solid #ccc;
            border-radius:8px;
            margin-bottom:12px;
            box-sizing:border-box;
        }

        button{
            width:100%;
            padding:12px;
            border:none;
            background:#2563eb;
            color:white;
            border-radius:8px;
            cursor:pointer;
            font-size:16px;
        }

        button:hover{
            background:#1d4ed8;
        }

        .success{
            background:#dcfce7;
            color:#166534;
            padding:10px;
            border-radius:8px;
            margin-bottom:15px;
        }

        li{
            background:#eef4ff;
            padding:10px;
            margin:8px 0;
            border-radius:8px;
            list-style:none;
        }

        li small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 13px;
        }

        ul{
            padding:0;
        }
    </style>
</head>

<body>

<div class="container">

<h1>My Notes</h1>

@if(session('success'))
    <div class="success">
        {{ session('success') }}
    </div>
@endif

<form method="POST" action="/notes">

    @csrf

    <input
        type="text"
        name="title"
        placeholder="Write a note..."
        required>

    <button type="submit">
        Add Note
    </button>

</form>

<hr>

<ul>

    @foreach($notes as $note)

        <li>
            <strong>{{ $note->title }}</strong>

            <small>
                {{ $note->created_at->format('d M Y, h:i A') }}
            </small>
        </li>

    @endforeach
</ul>

</div>

</body>
</html>