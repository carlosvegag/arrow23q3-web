<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0;url={{ $approval_url }}">
    <title>Redirecting...</title>
</head>
<body>
    <form action="{{ $approval_url }}" method="POST" id="paypal-redirect-form">
        @csrf
        <button type="submit">Redirecting...</button>
    </form>
    <script>
        document.getElementById('paypal-redirect-form').submit();
    </script>
</body>
</html>
