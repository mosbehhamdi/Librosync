<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>LibroSync Test Email</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .content {
            padding: 20px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.8em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LibroSync Email Test</h1>
    </div>
    
    <div class="content">
        <p>Hello {{ $name }},</p>
        <p>This is a test email confirming that your email configuration is working correctly.</p>
        <p>Sent at: {{ $timestamp }}</p>
    </div>

    <div class="footer">
        <p>This is an automated message from LibroSync</p>
    </div>
</body>
</html>
