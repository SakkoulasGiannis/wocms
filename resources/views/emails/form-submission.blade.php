<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Form Submission - {{ $form->name }}</title>
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
            padding: 30px 20px;
            text-align: center;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 30px 20px;
        }
        .intro-message {
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        .intro-message p {
            margin: 0;
            color: #555;
        }
        .submission-data {
            margin-bottom: 30px;
        }
        .data-row {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }
        .data-row:last-child {
            border-bottom: none;
        }
        .data-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 5px;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .data-value {
            color: #212529;
            font-size: 16px;
            word-wrap: break-word;
        }
        .metadata {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin-top: 20px;
        }
        .metadata-title {
            font-size: 16px;
            font-weight: 600;
            color: #495057;
            margin-bottom: 15px;
        }
        .metadata-item {
            display: flex;
            margin-bottom: 8px;
            font-size: 14px;
        }
        .metadata-item:last-child {
            margin-bottom: 0;
        }
        .metadata-label {
            font-weight: 600;
            color: #6c757d;
            margin-right: 8px;
            min-width: 120px;
        }
        .metadata-value {
            color: #495057;
            word-break: break-all;
        }
        .email-footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            font-size: 14px;
            color: #6c757d;
        }
        a {
            color: #667eea;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-header">
            <h1>New Form Submission</h1>
            <p style="margin: 5px 0 0; font-size: 16px; opacity: 0.9;">{{ $form->name }}</p>
        </div>

        <div class="email-body">
            @if($form->notification_message)
                <div class="intro-message">
                    <p>{{ $form->notification_message }}</p>
                </div>
            @endif

            <div class="submission-data">
                <h2 style="margin-top: 0; margin-bottom: 20px; color: #212529; font-size: 20px;">Submission Details</h2>

                @foreach($formattedData as $data)
                    <div class="data-row">
                        <div class="data-label">{{ $data['label'] }}</div>
                        <div class="data-value">
                            @if($data['is_html'])
                                {!! $data['value'] !!}
                            @else
                                {{ $data['value'] ?: '(Not provided)' }}
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="metadata">
                <div class="metadata-title">Submission Information</div>

                <div class="metadata-item">
                    <span class="metadata-label">Submitted:</span>
                    <span class="metadata-value">{{ $submission ? $submission->created_at->format('F j, Y g:i A') : now()->format('F j, Y g:i A') }}</span>
                </div>

                @if($submission)
                    <div class="metadata-item">
                        <span class="metadata-label">IP Address:</span>
                        <span class="metadata-value">{{ $submission->ip_address }}</span>
                    </div>

                    @if($submission->user_agent)
                        <div class="metadata-item">
                            <span class="metadata-label">Browser:</span>
                            <span class="metadata-value">{{ $submission->user_agent }}</span>
                        </div>
                    @endif

                    @if($submission->referer)
                        <div class="metadata-item">
                            <span class="metadata-label">Referrer:</span>
                            <span class="metadata-value">{{ $submission->referer }}</span>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="email-footer">
            <p style="margin: 0;">This is an automated notification from your form submission system.</p>
        </div>
    </div>
</body>
</html>
