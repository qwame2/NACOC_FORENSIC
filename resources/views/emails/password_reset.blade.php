<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FIMS Password Reset Verification Code</title>
</head>
<body style="margin:0;padding:0;background-color:#eef3f2;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#eef3f2;padding:36px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;background-color:#ffffff;border-radius:20px;overflow:hidden;border:1px solid #d5e0dd;box-shadow:0 10px 30px rgba(5,43,41,0.08);">
                    <!-- Brass Accent Bar -->
                    <tr>
                        <td style="height:5px;background:linear-gradient(90deg,#d4a24c 0%,#0f766e 100%);font-size:0;line-height:0;">&nbsp;</td>
                    </tr>

                    <!-- Institutional Header -->
                    <tr>
                        <td style="background-color:#052b29;padding:28px 34px;">
                            <div style="font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#d4a24c;margin-bottom:6px;">
                                Narcotics Control Commission &bull; Ghana
                            </div>
                            <div style="font-size:20px;font-weight:800;color:#ffffff;line-height:1.25;">
                                Forensic Information Management System
                            </div>
                            <div style="font-size:12px;color:#9cc0bb;margin-top:4px;">
                                One-Time Password (OTP) Verification
                            </div>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding:34px 34px 28px;">
                            <p style="margin:0 0 14px;font-size:15px;font-weight:700;color:#052b29;">
                                Hello {{ $officerName }},
                            </p>
                            <p style="margin:0 0 22px;font-size:14px;line-height:1.65;color:#334155;">
                                Enter the 6-digit verification code below on the FIMS Forgot Password screen to verify your identity and set a new password.
                            </p>

                            <!-- Prominent 6-Digit OTP Display Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:26px;">
                                <tr>
                                    <td align="center" style="background-color:#f0fdfa;border:2px dashed #0f766e;border-radius:16px;padding:24px 20px;">
                                        <div style="font-size:10.5px;font-weight:800;letter-spacing:0.16em;text-transform:uppercase;color:#0f766e;margin-bottom:10px;">
                                            Your 6-Digit OTP Code
                                        </div>
                                        <div style="font-family:'Courier New',Courier,monospace;font-size:36px;font-weight:900;letter-spacing:0.28em;color:#052b29;padding-left:0.28em;line-height:1.15;">
                                            {{ $otpCode }}
                                        </div>
                                        <div style="font-size:11.5px;font-weight:600;color:#475569;margin-top:10px;">
                                            Expires in <strong style="color:#052b29;">{{ $expiryMinutes }} minutes</strong> &bull; Single-use code
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Officer Dossier Summary Box -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f6f9f8;border:1px solid #dce6e4;border-radius:14px;margin-bottom:22px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:12px;color:#475569;">
                                            <tr>
                                                <td style="padding:4px 0;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;">Service ID</td>
                                                <td align="right" style="padding:4px 0;font-family:'Courier New',Courier,monospace;font-weight:700;color:#052b29;">{{ $serviceId }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;">Assigned Unit</td>
                                                <td align="right" style="padding:4px 0;font-weight:700;color:#0f172a;">{{ $unitName }}</td>
                                            </tr>
                                            <tr>
                                                <td style="padding:4px 0;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;">Timestamp</td>
                                                <td align="right" style="padding:4px 0;font-family:'Courier New',Courier,monospace;color:#334155;">{{ $requestedAtUtc }}</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0;font-size:12.5px;line-height:1.6;color:#475569;">
                                Never share this verification code with anyone. NACOC IT personnel will never ask for your OTP. If you did not initiate this request (logged from IP <span style="font-family:'Courier New',Courier,monospace;color:#0f172a;">{{ $requestIp }}</span>), you can safely ignore this email and your current password will remain unchanged.
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8faf9;padding:18px 34px;border-top:1px solid #e2e8f0;font-size:11px;color:#64748b;line-height:1.5;">
                            <strong>NACOC Forensics Department, Ghana</strong> &bull; IT Command Helpdesk: Ext. 4022<br>
                            Automated security dispatch from the Forensic Information Management System (FIMS).
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
