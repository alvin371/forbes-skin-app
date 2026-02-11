<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Support — Acneno HRMS</title>
    <link rel="icon" type="image/png" href="<?= base_url('assets/img/fav.png') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.7;
            color: #333;
            background: #f4f5f7;
            -webkit-text-size-adjust: 100%;
        }
        .page-header {
            background: #fff;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 20px;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        .header-inner {
            max-width: 720px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-inner .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #0d1458;
            font-weight: 700;
            font-size: 16px;
        }
        .header-inner .logo img { height: 32px; width: auto; }
        .header-nav a {
            color: #4f46e5;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .header-nav a:hover { text-decoration: underline; }
        .container {
            max-width: 720px;
            margin: 32px auto;
            padding: 0 20px;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            padding: 40px 36px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            margin-bottom: 24px;
        }
        h1 {
            color: #0d1458;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 6px;
        }
        .subtitle {
            color: #828ea5;
            font-size: 14px;
            margin-bottom: 32px;
        }
        h2 {
            color: #0d1458;
            font-size: 20px;
            font-weight: 600;
            margin-top: 36px;
            margin-bottom: 12px;
        }
        h2:first-of-type { margin-top: 0; }
        p { margin-bottom: 14px; }
        ul, ol {
            margin-bottom: 14px;
            padding-left: 24px;
        }
        li { margin-bottom: 6px; }
        a { color: #4f46e5; }
        strong { font-weight: 600; }
        .divider {
            border: none;
            border-top: 1px solid #e5e7eb;
            margin: 32px 0;
        }

        /* Steps */
        .steps {
            list-style: none;
            padding-left: 0;
            counter-reset: step;
        }
        .steps li {
            counter-increment: step;
            padding-left: 36px;
            position: relative;
            margin-bottom: 12px;
        }
        .steps li::before {
            content: counter(step);
            position: absolute;
            left: 0;
            top: 1px;
            width: 24px;
            height: 24px;
            background: #4f46e5;
            color: #fff;
            border-radius: 50%;
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* FAQ Accordion */
        details {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 10px;
            overflow: hidden;
        }
        details + details { margin-top: 0; }
        summary {
            padding: 14px 18px;
            font-weight: 500;
            cursor: pointer;
            list-style: none;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            user-select: none;
            -webkit-user-select: none;
        }
        summary::-webkit-details-marker { display: none; }
        summary::after {
            content: '+';
            font-size: 20px;
            font-weight: 300;
            color: #828ea5;
            flex-shrink: 0;
            transition: transform 0.2s ease;
        }
        details[open] summary::after {
            content: '\2212';
        }
        details[open] summary {
            border-bottom: 1px solid #e5e7eb;
        }
        .faq-body {
            padding: 16px 18px;
            font-size: 14px;
            line-height: 1.7;
        }
        .faq-body p { margin-bottom: 10px; }
        .faq-body p:last-child { margin-bottom: 0; }
        .faq-body ol, .faq-body ul { margin-bottom: 10px; }

        /* Info box */
        .info-box {
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 8px;
            padding: 20px 24px;
            margin-bottom: 14px;
        }
        .info-box h3 {
            color: #0d1458;
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .info-box p { font-size: 14px; margin-bottom: 8px; }
        .info-box p:last-child { margin-bottom: 0; }

        /* Contact card */
        .contact-card {
            background: #f8f9fb;
            border-radius: 8px;
            padding: 24px;
        }
        .contact-card p { margin-bottom: 10px; }
        .contact-card p:last-child { margin-bottom: 0; }

        .page-footer {
            max-width: 720px;
            margin: 0 auto 40px;
            padding: 0 20px;
            text-align: center;
            font-size: 13px;
            color: #828ea5;
        }
        .page-footer a { color: #4f46e5; text-decoration: none; }
        .page-footer a:hover { text-decoration: underline; }
        @media (max-width: 480px) {
            .card { padding: 28px 20px; }
            h1 { font-size: 24px; }
            h2 { font-size: 18px; }
            summary { padding: 12px 14px; font-size: 14px; }
            .faq-body { padding: 14px; }
        }
    </style>
</head>
<body>

<header class="page-header">
    <div class="header-inner">
        <a href="<?= base_url('support') ?>" class="logo">
            <img src="<?= base_url('assets/img/acneno-logo.png') ?>" alt="Acneno">
            Acneno HRMS
        </a>
        <nav class="header-nav">
            <a href="<?= base_url('privacy-policy') ?>">Privacy Policy</a>
        </nav>
    </div>
</header>

<main class="container">

    <!-- Getting Started -->
    <div class="card">
        <h1>Support</h1>
        <p class="subtitle">Everything you need to get started and get help with Acneno HRMS.</p>

        <h2>Getting Started</h2>
        <p>Acneno HRMS is a mobile application for managing attendance, leave requests, performance reviews, and overtime — all from your phone. Follow these steps to get set up:</p>
        <ol class="steps">
            <li><strong>Download the app</strong> — Install Acneno HRMS from the App Store on your iPhone.</li>
            <li><strong>Log in</strong> — Use the credentials provided by your HR administrator (email and temporary password).</li>
            <li><strong>Set your PIN</strong> — Create a 6-digit PIN for quick and secure access.</li>
            <li><strong>Allow location access</strong> — When prompted, select "Allow While Using App" so attendance check-in can verify your location.</li>
            <li><strong>You're ready</strong> — Start checking in, submitting leave requests, and more.</li>
        </ol>
    </div>

    <!-- FAQ: Attendance -->
    <div class="card">
        <h2>Attendance</h2>

        <details>
            <summary>How do I check in and check out?</summary>
            <div class="faq-body">
                <p>Open the app and tap the <strong>Check In</strong> button on the home screen. The app will record your current GPS location and timestamp. When your work day is done, tap <strong>Check Out</strong> the same way.</p>
            </div>
        </details>

        <details>
            <summary>Why does the app need my GPS location?</summary>
            <div class="faq-body">
                <p>GPS is used to verify that you are at or near your designated work location when checking in or out. Your location is captured <strong>only at the moment of check-in/check-out</strong> — the app does not track your location in the background.</p>
            </div>
        </details>

        <details>
            <summary>GPS is not working or shows inaccurate location</summary>
            <div class="faq-body">
                <p>Try the following steps:</p>
                <ol>
                    <li>Make sure Location Services are enabled: go to <strong>Settings &gt; Privacy &amp; Security &gt; Location Services</strong> and ensure Acneno HRMS is set to "While Using the App".</li>
                    <li>Move to an open area with clear sky view for better GPS signal.</li>
                    <li>Restart the app and try again.</li>
                    <li>If the issue persists, restart your phone and try once more.</li>
                </ol>
            </div>
        </details>

        <details>
            <summary>I forgot to check in or check out. What should I do?</summary>
            <div class="faq-body">
                <p>Contact your HR administrator or direct supervisor to manually adjust your attendance record. Please provide the date, expected check-in/check-out time, and reason for the missing entry.</p>
            </div>
        </details>
    </div>

    <!-- FAQ: Leave Management -->
    <div class="card">
        <h2>Leave Management</h2>

        <details>
            <summary>How do I submit a leave request?</summary>
            <div class="faq-body">
                <p>Navigate to the <strong>Leave</strong> section in the app. Tap <strong>New Request</strong>, select the leave type (annual, sick, etc.), pick your dates, add an optional note, and submit. Your supervisor will be notified for approval.</p>
            </div>
        </details>

        <details>
            <summary>How can I check my remaining leave quota?</summary>
            <div class="faq-body">
                <p>Go to the <strong>Leave</strong> section. Your current quota for each leave type is displayed at the top of the screen, showing used and remaining days.</p>
            </div>
        </details>

        <details>
            <summary>Can I cancel a leave request?</summary>
            <div class="faq-body">
                <p>Yes, you can cancel a pending leave request before it has been approved. Open the request from your leave history and tap <strong>Cancel Request</strong>. Once a request has been approved, contact your HR administrator to process the cancellation.</p>
            </div>
        </details>

        <details>
            <summary>Why was my leave request rejected?</summary>
            <div class="faq-body">
                <p>Common reasons include insufficient leave quota, overlapping dates with other team members, or incomplete supporting documents (e.g., medical certificate for sick leave). Check the rejection note from your supervisor for details.</p>
            </div>
        </details>
    </div>

    <!-- FAQ: Performance Review -->
    <div class="card">
        <h2>Performance Review</h2>

        <details>
            <summary>How do I submit my self-assessment?</summary>
            <div class="faq-body">
                <p>When a review period is open, you will see a notification in the app. Go to the <strong>Performance</strong> section, fill in your self-assessment form, and tap <strong>Submit</strong>. Make sure to complete it before the deadline.</p>
            </div>
        </details>

        <details>
            <summary>Can I edit my self-assessment after submitting?</summary>
            <div class="faq-body">
                <p>Once submitted, your self-assessment is locked for review by your supervisor. If you need to make changes, contact your HR administrator before the review period closes.</p>
            </div>
        </details>
    </div>

    <!-- FAQ: Overtime -->
    <div class="card">
        <h2>Overtime</h2>

        <details>
            <summary>How do I request overtime?</summary>
            <div class="faq-body">
                <p>Go to the <strong>Overtime</strong> section, tap <strong>New Request</strong>, enter the date, expected hours, and reason for the overtime. Submit it for supervisor approval. Overtime should be requested <strong>before</strong> or on the same day the work is performed.</p>
            </div>
        </details>

        <details>
            <summary>Where can I view my overtime summary?</summary>
            <div class="faq-body">
                <p>The <strong>Overtime</strong> section shows your monthly overtime summary, including total approved hours and status of each request.</p>
            </div>
        </details>
    </div>

    <!-- FAQ: Account & Security -->
    <div class="card">
        <h2>Account &amp; Security</h2>

        <details>
            <summary>I forgot my PIN. How do I reset it?</summary>
            <div class="faq-body">
                <p>On the PIN entry screen, tap <strong>Forgot PIN</strong>. You will be asked to verify your identity using your email and password. Once verified, you can set a new 6-digit PIN.</p>
            </div>
        </details>

        <details>
            <summary>My account is locked. What should I do?</summary>
            <div class="faq-body">
                <p>Accounts are temporarily locked after multiple failed login attempts for security purposes. Wait 15 minutes and try again, or contact your HR administrator to unlock your account immediately.</p>
            </div>
        </details>

        <details>
            <summary>How do I update my profile information?</summary>
            <div class="faq-body">
                <p>Go to <strong>Profile</strong> in the app to update your phone number, profile photo, and other editable fields. Some fields (name, employee ID, department) are managed by your HR administrator and cannot be changed directly.</p>
            </div>
        </details>
    </div>

    <!-- Troubleshooting -->
    <div class="card">
        <h2>Troubleshooting</h2>

        <div class="info-box">
            <h3>App not loading or showing a blank screen</h3>
            <p>Force-close the app and reopen it. If the issue continues, check your internet connection and ensure you are running the latest version of the app from the App Store.</p>
        </div>

        <div class="info-box">
            <h3>Not receiving notifications</h3>
            <p>Go to <strong>Settings &gt; Notifications &gt; Acneno HRMS</strong> on your iPhone and make sure notifications are enabled. Also check that Do Not Disturb or Focus mode is not blocking alerts.</p>
        </div>

        <div class="info-box">
            <h3>App crashes unexpectedly</h3>
            <p>Update to the latest version of Acneno HRMS. If crashes persist, try deleting and reinstalling the app (your data is stored on the server and will not be lost). If the problem continues, contact support with your device model and iOS version.</p>
        </div>

        <div class="info-box">
            <h3>Location permission was denied</h3>
            <p>Go to <strong>Settings &gt; Privacy &amp; Security &gt; Location Services &gt; Acneno HRMS</strong> and select <strong>While Using the App</strong>. The app requires location access for attendance check-in only.</p>
        </div>
    </div>

    <!-- Contact Us -->
    <div class="card">
        <h2>Contact Us</h2>
        <p>If you cannot find the answer to your question above, our support team is here to help.</p>

        <div class="contact-card">
            <p><strong>Email:</strong> <a href="mailto:support@acneno.com">support@acneno.com</a></p>
            <p><strong>Response Time:</strong> Within 1 business day (Monday – Friday, 09:00 – 17:00 WIB)</p>
            <p><strong>When contacting support, please include:</strong></p>
            <ul>
                <li>Your full name and employee ID</li>
                <li>Device model and iOS version</li>
                <li>A clear description of the issue</li>
                <li>Screenshots, if applicable</li>
            </ul>
        </div>
    </div>

</main>

<footer class="page-footer">
    <p>&copy; <?= date('Y') ?> PT Forbes Titik Terang. All rights reserved.</p>
    <p><a href="<?= base_url('privacy-policy') ?>">Privacy Policy</a> &middot; <a href="<?= base_url('support') ?>">Support</a></p>
</footer>

</body>
</html>
