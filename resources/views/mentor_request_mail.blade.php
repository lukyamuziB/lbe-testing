<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" style="width: 100%; height: 100%;">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width">
    <!-- This will be the subject of the mail -->
    <title>New Mentee Request</title>
</head>
<body style="font-size: 16px; font-family: 'Avenir', 'Avenir Next', Helvetica; padding: 0; margin: 0; color: #666; width: 100%; height: 100%;">
<!-- Main table wrapper -->
<table cellpadding="0" cellspacing="0" style="width: 100%; height: 100%; border-collapse: collapse; background-color: #f3f3f3;" valign="top">
    <tbody>
    <tr>
        <td align="center" valign="top">
            <center>
                <!-- Container -->
                <table width="680" style="padding: 20px;">
                    <tbody>
                    <tr>
                        <td style="background-color: #fff; display: block; padding: 25px;">
                            <!-- Header -->
                            <table align="center" valign="top" style="border-collapse: collapse; margin-bottom: 15px;">
                                <tbody>
                                <tr>
                                    <th bgcolor="#225be2">
                                        <table>
                                            <tbody>
                                            <tr>
                                                <!-- Header content -->
                                                <th width="274" style="padding-right: 16px; padding-left: 16px;">
                                                    <table>
                                                        <tbody>
                                                        <tr>
                                                            <th width="300">
                                                                <h3 style="color: #fff; font-weight: 50; text-align: left; font-size: 15px">
                                                                    New Mentee Request
                                                                </h3>
                                                            </th>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </th>
                                                <!-- Header content -->

                                                <!-- Logo -->
                                                <th width="274" align=right style="padding-right: 16px; padding-left: 16px;">
                                                    <table>
                                                        <tbody>
                                                        <tr>
                                                            <th>
                                                                <a href="http://andela.com" target="_blank">
                                                                    <img src='https://lv.andela.com/images/logo-white.png' height="50px"/>
                                                                </a>
                                                            </th>
                                                        </tr>
                                                        </tbody>
                                                    </table>
                                                </th>
                                                <!-- Logo -->
                                            </tr>
                                            </tbody>
                                        </table>
                                    </th>
                                </tr>
                                </tbody>
                            </table>
                            <!-- Header -->

                            <!-- Body -->
                            <p> {{ $payload["currentUser"] }} has created a request to be mentored in {{ $payload["title"] }} . Click <a href="http://{{ env("ACCEPT_REJECT_MAIL_LINK") }}">here</a> to indicate interest in this request.</p>
                            <!-- Body -->

                            <!-- Footer section -->
                            <table align="center">
                                <tbody>
                                <tr>
                                    <th style="padding-top: 25px;">
                                       <p style="font-size: 12px; color: #b3b3b3; font-weight: 100; text-align: center;">
                                            This email was sent by the Andela Lenken Development Team. <br/><br/>
                                            Click <a href="http://{{ env("UNSUBSCRIBE_FROM_MAIL_LINK") }}"  style="font-size: 12px; color: #b3b3b3; font-weight: 100;">here</a> to unsubscribe from this notification
                                        </p> 
                                    </th>
                                </tr>
                                </tbody>
                            </table>
                            <!-- Footer section -->
                        </td>
                    </tr>
                    </tbody>
                </table>
                <!-- Container -->
            </center>
        </td>
    </tr>
    </tbody>
</table>
<!-- Main table wrapper -->
</body>
</html>