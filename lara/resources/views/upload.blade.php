<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload to Google Drive</title>
</head>
<body>
    <h1>Upload to Google Drive</h1>
    <form action="{{ route('google.drive.upload') }}" method="post" enctype="multipart/form-data">
        @csrf
        <label for="imageFile">Choose Images:</label>
        <input type="file" name="imageFile[]" id="imageFile" multiple>
        <button type="submit">Upload</button>
    </form>
    @if(isset($upload_data))
        <h2>Uploaded Files:</h2>
        <ul>
            @foreach ($upload_data as $file)
                <li><a href="{{ $file['link'] }}" target="_blank">{{ $file['filename'] }}</a></li>
            @endforeach
        </ul>
    @endif
</body>
</html>
