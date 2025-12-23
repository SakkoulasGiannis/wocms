<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $form->auto_reply_subject ?? 'Thank you for your submission' }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 40px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        .checkmark {
            display: inline-block;
            width: 60px;
            height: 60px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            margin-bottom: 15px;
            position: relative;
        }
        .checkmark::after {
            content: '✓';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 32px;
            color: #ffffff;
        }
        .email-body {
            padding: 40px 30px;
        }
        .message-content {
            font-size: 16px;
            color: #495057;
            margin-bottom: 30px;
            white-space: pre-wrap;
            line-height: 1.8;
        }
        .info-box {
            background-color: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px 20px;
            border-radius: 4px;
            margin-top: 30px;
        }
        .info-box p {
            margin: 0;
            color: #1976D2;
            font-size: 14px;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 25px 20px;
            text-align: center;
            border-top: 1px solid #e9ecef;
        }
        .email-footer p {
            margin: 0;
            font-size: 14px;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <div class="checkmark"></div>
            <h1>{{ $form->auto_reply_subject ?? 'Thank You!' }}</h1>
        </div>

        <div class="email-body">
            @if($form->auto_reply_message)
                <div class="message-content">
                    {{ $form->auto_reply_message }}
                </div>
            @else
                <div class="message-content">
                    Thank you for submitting the <strong>{{ $form->name }}</strong> form. We have received your information and will get back to you as soon as possible.
                </div>
            @endif

            <div class="info-box">
                <p>This is an automated response to confirm we received your submission. Please do not reply to this email.</p>
            </div>
        </div>

        <div class="email-footer">
            <p>Sent from {{ config('app.name', 'Form System') }}</p>
        </div>
    </div>
</body>
</html>
