<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Privacy Policy — Acneno HRMS</title>
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
        }
    </style>
</head>
<body>

<header class="page-header">
    <div class="header-inner">
        <a href="<?= base_url('privacy-policy') ?>" class="logo">
            <img src="<?= base_url('assets/img/acneno-logo.png') ?>" alt="Acneno">
            Acneno HRMS
        </a>
        <nav class="header-nav">
            <a href="<?= base_url('support') ?>">Support</a>
        </nav>
    </div>
</header>

<main class="container">
    <div class="card">
        <h1>Privacy Policy</h1>
        <p class="subtitle">Effective Date: February 1, 2026 &middot; Last Updated: <?= date('F d, Y') ?></p>

        <h2>1. Introduction</h2>
        <p>PT Forbes Titik Terang ("we", "us", or "our") operates the <strong>Acneno HRMS</strong> mobile application and web platform (collectively, the "Service"). This Privacy Policy explains how we collect, use, disclose, and safeguard your personal information when you use our Service.</p>
        <p>By accessing or using Acneno HRMS, you acknowledge that you have read, understood, and agree to be bound by this Privacy Policy. If you do not agree, please discontinue use of the Service immediately.</p>

        <hr class="divider">

        <h2>2. Information We Collect</h2>
        <p>We collect the following categories of information:</p>
        <ul>
            <li><strong>Personal Information</strong> — Full name, email address, phone number, employee ID, department, and job title as provided by you or your employer.</li>
            <li><strong>Location Data</strong> — GPS coordinates collected <strong>only</strong> at the moment you perform an attendance check-in or check-out. We do <strong>not</strong> track your location in the background or outside of attendance actions.</li>
            <li><strong>Device Information</strong> — Device model, operating system version, unique device identifiers, and app version for compatibility and troubleshooting.</li>
            <li><strong>Usage Data</strong> — Pages visited, features used, timestamps, and interaction patterns to help us improve the Service.</li>
            <li><strong>Uploaded Content</strong> — Photos or documents you voluntarily upload (e.g., profile photo, supporting documents for leave requests).</li>
        </ul>

        <hr class="divider">

        <h2>3. How We Use Your Information</h2>
        <p>Your information is used strictly for human-resource management purposes:</p>
        <ul>
            <li><strong>Attendance Verification</strong> — GPS coordinates confirm your physical presence at a designated work location during check-in and check-out.</li>
            <li><strong>Leave Management</strong> — Processing, approving, and tracking leave requests and quotas.</li>
            <li><strong>Performance Reviews</strong> — Facilitating self-assessments and supervisor evaluations.</li>
            <li><strong>Overtime Tracking</strong> — Recording and calculating overtime hours for payroll integration.</li>
            <li><strong>Notifications</strong> — Sending you alerts about approvals, reminders, and system updates.</li>
            <li><strong>Analytics &amp; Improvements</strong> — Aggregated, anonymized usage data to improve Service quality and reliability.</li>
            <li><strong>Security</strong> — Detecting unauthorized access and protecting account integrity.</li>
        </ul>

        <hr class="divider">

        <h2>4. Data Sharing &amp; Third Parties</h2>
        <p>We do <strong>not</strong> sell, rent, or trade your personal information to any third party. Your data may be shared only in the following limited circumstances:</p>
        <ul>
            <li><strong>Your Employer's HR Administrators</strong> — Authorized personnel within your organization who manage attendance, leave, and performance data through the Acneno HRMS dashboard.</li>
            <li><strong>Cloud Infrastructure Providers</strong> — We use reputable hosting and cloud-storage providers to store data securely. These providers are contractually obligated to protect your data and may not use it for their own purposes.</li>
            <li><strong>Legal Obligations</strong> — We may disclose information if required by Indonesian law, court order, or governmental regulation.</li>
        </ul>
        <p>We do <strong>not</strong> integrate with advertising networks, and no personal data is shared for marketing or advertising purposes.</p>

        <hr class="divider">

        <h2>5. Data Retention &amp; Deletion</h2>
        <ul>
            <li><strong>Active Employment</strong> — Your data is retained for as long as your employer maintains an active account with Acneno HRMS and you remain an employee.</li>
            <li><strong>Post-Employment</strong> — After your employment ends, data may be retained for up to two (2) years in accordance with Indonesian labor-law record-keeping requirements.</li>
            <li><strong>Deletion Requests</strong> — You may request deletion of your personal data by emailing <a href="mailto:support@acneno.com">support@acneno.com</a>. We will process your request within thirty (30) business days, subject to any legal retention obligations.</li>
        </ul>

        <hr class="divider">

        <h2>6. Your Rights</h2>
        <p>In accordance with Indonesia's Personal Data Protection Law (UU PDP), you have the right to:</p>
        <ul>
            <li><strong>Access</strong> — Request a copy of the personal data we hold about you.</li>
            <li><strong>Correction</strong> — Request correction of inaccurate or incomplete personal data.</li>
            <li><strong>Deletion</strong> — Request deletion of your personal data, subject to legal retention requirements.</li>
            <li><strong>Withdraw Consent</strong> — Withdraw your consent to data processing at any time. Note that withdrawal may affect your ability to use certain features of the Service.</li>
        </ul>
        <p>To exercise any of these rights, contact us at <a href="mailto:support@acneno.com">support@acneno.com</a>.</p>

        <hr class="divider">

        <h2>7. Children's Privacy</h2>
        <p>Acneno HRMS is a workplace application and is not intended for use by individuals under the age of 17. We do not knowingly collect personal data from children. If we discover that we have inadvertently collected information from a child under 17, we will delete it promptly.</p>

        <hr class="divider">

        <h2>8. Security Measures</h2>
        <p>We implement industry-standard security measures to protect your data:</p>
        <ul>
            <li><strong>Encryption in Transit</strong> — All data transmitted between your device and our servers is encrypted using HTTPS/TLS.</li>
            <li><strong>Password Hashing</strong> — Passwords are stored using bcrypt hashing and are never kept in plain text.</li>
            <li><strong>Session Management</strong> — Sessions are secured with server-side tokens and automatic expiration.</li>
            <li><strong>Access Controls</strong> — Role-based access ensures only authorized personnel can view or modify sensitive data.</li>
        </ul>
        <p>While we strive to protect your information, no method of electronic transmission or storage is 100% secure. We encourage you to use strong, unique passwords and keep your credentials confidential.</p>

        <hr class="divider">

        <h2>9. Changes to This Policy</h2>
        <p>We may update this Privacy Policy from time to time. Changes will be reflected in the "Last Updated" date at the top of this page. Continued use of the Service after changes are posted constitutes your acceptance of the revised Privacy Policy.</p>
        <p>For material changes, we may also notify you via in-app notification or email.</p>

        <hr class="divider">

        <h2>10. Contact Us</h2>
        <p>If you have questions, concerns, or requests regarding this Privacy Policy or your personal data, please contact us:</p>
        <ul>
            <li><strong>Company</strong> — PT Forbes Titik Terang</li>
            <li><strong>Email</strong> — <a href="mailto:support@acneno.com">support@acneno.com</a></li>
            <li><strong>Support Page</strong> — <a href="<?= base_url('support') ?>"><?= base_url('support') ?></a></li>
        </ul>
    </div>
</main>

<footer class="page-footer">
    <p>&copy; <?= date('Y') ?> PT Forbes Titik Terang. All rights reserved.</p>
    <p><a href="<?= base_url('privacy-policy') ?>">Privacy Policy</a> &middot; <a href="<?= base_url('support') ?>">Support</a></p>
</footer>

</body>
</html>
