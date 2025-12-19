<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Certificate of Achievement</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        @page {
            size: A4;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Montserrat', sans-serif;
            background: #f5f7fa;
        }

        .certificate-wrapper {
            width: 100%;
            box-sizing: border-box;
            display: flex;
            justify-content: center;
        }

        .certificate {
            border: 4px solid #a31f37;
            border-radius: 12px;
            background: #ffffff;
            width: 90%;
            max-width: 850px;
            margin: 16px auto;
            padding: 36px 44px;
            box-sizing: border-box;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            page-break-inside: avoid; /* keep whole cert on one page */
        }

        .certificate-inner-border {
            border: 1px solid rgba(163, 31, 55, 0.15);
            border-radius: 8px;
            padding: 28px 36px;
            box-sizing: border-box;
            position: relative;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            padding-bottom: 20px;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 30%;
            width: 40%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #a31f37, transparent);
        }

        .header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 0;
            color: #1a202c;
            font-weight: 700;
            line-height: 1.2;
        }

        .header h2 {
            font-size: 14px;
            margin: 10px 0 0 0;
            color: #4b5563;
            letter-spacing: 1.5px;
            font-weight: 400;
            text-transform: uppercase;
        }

        .body {
            text-align: center;
            margin: 24px 0;
            padding: 0 6px;
        }

        .label {
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #6b7280;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .recipient-name {
            font-family: 'Playfair Display', serif;
            font-size: 28px;
            font-weight: 700;
            color: #1a202c;
            margin: 20px 0;
            padding: 15px 0;
            border-top: 1px solid rgba(163, 31, 55, 0.1);
            border-bottom: 1px solid rgba(163, 31, 55, 0.1);
        }

        .text {
            font-size: 16px;
            color: #374151;
            margin: 25px 0;
            line-height: 1.5;
        }

        .title-container {
            margin: 30px auto;
            display: inline-block;
            padding: 0 20px;
        }

        .title {
            font-size: 20px;
            font-weight: 700;
            color: #a31f37;
            margin: 0;
            padding: 10px 0;
            position: relative;
        }

        .title::before, .title::after {
            content: "—";
            color: #a31f37;
            margin: 0 15px;
        }

        .description-container {
            margin: 25px auto 30px auto;
            max-width: 85%;
            padding: 15px;
            background: #f8fafc;
            border-radius: 6px;
        }

        .description {
            font-size: 14px;
            color: #4b5563;
            margin: 0;
            line-height: 1.6;
            font-style: italic;
        }

        .footer {
            display: flex;
            justify-content: space-between;
            margin-top: 28px;
            padding-top: 18px;
            border-top: 1px solid #e5e7eb;
        }

        .footer .item {
            text-align: left;
            flex: 1;
        }

        .footer .item.right {
            text-align: right;
        }

        .footer .item span.label {
            display: block;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #9ca3af;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .footer-value {
            font-size: 14px;
            color: #1f2937;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .signature-container {
            margin-top: 10px;
        }

        .signature-line {
            margin-top: 5px;
            width: 180px;
            height: 1px;
            background: #4b5563;
            display: inline-block;
        }

        .signature-text {
            font-size: 12px;
            color: #6b7280;
            margin-top: 5px;
        }

        .logo {
            position: absolute;
            top: 40px;
            right: 60px;
            font-size: 22px;
            font-weight: bold;
            color: #a31f37;
            font-family: 'Playfair Display', serif;
            z-index: 2;
        }

        .certificate-info {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .certificate-wrapper {
                height: auto;
                padding: 0;
            }
            
            .certificate {
                box-shadow: none;
                border: 3px solid #a31f37;
                width: 100%;
                max-width: 100%;
                margin: 0;
                padding: 40px 50px;
            }
        }
    </style>
</head>
<body>
<div class="certificate-wrapper">
    <div class="certificate">
        <div class="logo">
            TAR&nbsp;UMT
        </div>
        <div class="certificate-inner-border">
            <div class="header">
                <h1>Certificate of Achievement</h1>
                <h2>TAR UMT Facilities Management System</h2>
            </div>

            <div class="body">
                <div class="label">This is to certify that</div>
                <div class="recipient-name">
                    {{ $user->name }}
                </div>

                <div class="text">
                    has successfully earned the following recognition
                </div>

                <div class="title-container">
                    <div class="title">
                        {{ $certificate->title }}
                    </div>
                </div>

                <div class="description-container">
                    <div class="description">
                        @php
                            $desc = $certificate->description
                                ?? ($reward->description ?? 'For active participation and contribution in using TAR UMT facilities.');
                        @endphp
                        {{ $desc }}
                    </div>
                </div>
            </div>

            <div class="footer">
                <div class="item">
                    <div class="certificate-info">
                        <span class="label">Certificate No.</span>
                        <div class="footer-value">{{ $certificate->certificate_number }}</div>
                        
                        <span class="label">Awarded To</span>
                        <div class="footer-value">{{ $user->email }}</div>
                    </div>
                </div>

                <div class="item right">
                    <div class="certificate-info">
                        <span class="label">Issued Date</span>
                        <div class="footer-value">
                            {{ optional($certificate->issued_date)->format('d M Y') }}
                        </div>
                    </div>
                    
                    <div class="signature-container">
                        <span class="label">Issued By</span>
                        <div class="footer-value">
                            {{ $certificate->issued_by ? 'Admin' : 'TAR UMT FMS' }}
                        </div>
                        <div class="signature-line"></div>
                        <div class="signature-text">Authorized Signature</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>