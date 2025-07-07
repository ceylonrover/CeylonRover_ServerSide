<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New Blog Submission</title>
</head>
<body>
    <h2>New Blog Submitted for Approval</h2>

    <p><strong>Title:</strong> {{ $blog->title }}</p>
    <p><strong>Description:</strong> {{ Str::limit($blog->description, 150) }}</p>
    <p><strong>Submitted by User ID:</strong> {{ $blog->user_id }}</p>
    <p><strong>Status:</strong> {{ $blog->status }}</p>

    <p>Please review and approve it in the admin panel.</p>
</body>
</html>
