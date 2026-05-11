<?php
$basePath = '/itec_contract_system';
$assetVersion = time();
$pageTitle = '419 Session Expired';
$pageHeading = 'Session Expired';
$pageEyebrow = 'refresh required';
$pageLead = 'Your session is no longer active, so the protected contract action cannot continue safely.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>419 - Session Expired</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 500px;
            width: 90%;
            margin: 20px;
        }
        .error-card {
            background: white;
            border-radius: 16px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .error-code {
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #dc3545;
            background: #ffe9e9;
            display: inline-block;
            padding: 6px 14px;
            border-radius: 30px;
            margin-bottom: 20px;
        }
        .error-card h1 {
            font-size: 28px;
            color: #333;
            margin-bottom: 10px;
        }
        .error-card .lead {
            color: #666;
            font-size: 16px;
            line-height: 1.5;
            margin-bottom: 30px;
        }
        .divider {
            height: 1px;
            background: #e0e0e0;
            margin: 25px 0;
        }
        .error-card p {
            color: #555;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .head-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .button.primary {
            background: #007bff;
            color: white;
            border: none;
        }
        .button.primary:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }
        .button.secondary {
            background: #f5f5f5;
            color: #333;
            border: 1px solid #ddd;
        }
        .button.secondary:hover {
            background: #e9e9e9;
        }
        .info-text {
            font-size: 12px;
            color: #999;
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .icon-circle {
            width: 70px;
            height: 70px;
            margin: 0 auto 20px;
            border-radius: 50%;
            background: #ffe9e9;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .icon-circle i {
            font-size: 32px;
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="error-card">
            <div class="icon-circle">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <div class="error-code">419 Token Expired</div>
            <h1>Token Expired</h1>
            <div class="lead">Your token is no longer active</div>
            <div class="divider"></div>
            <p><i class="fas fa-exclamation-triangle" style="color: #dc3545; margin-right: 8px;"></i> The protected contract action (save, signature, or upload) cannot continue safely because your session has expired.</p>
            <p><i class="fas fa-sync-alt" style="color: #007bff; margin-right: 8px;"></i> Please refresh the page and sign in again to continue.</p>
            <div class="head-actions">
                <a class="button secondary" href="<?= $basePath ?>/">
                    <i class="fas fa-home"></i> Home
                </a>
              
            </div>
            <div class="info-text">
                <i class="fas fa-clock"></i> This usually happens after 30 minutes of inactivity.
            </div>
        </div>
    </div>
</body>
</html>