<p>
      Dear Andelan,
      the following requests are still unmatched on Lenken
</p>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en" style="width: 100%; height: 100%;">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width">
    <!-- This will be the subject of the mail -->
    <title>Unmatched Mentorship Requests</title>
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
                                                Unmatched Requests
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
                      <table cellspacing="5px" style="border-bottom: 1px solid #eee; padding: 5px; padding-bottom: 35px; vertical-align:top; width: 100%;">
                        <div style="padding-bottom: 15px;">
                          <thead style="font-weight: 50; font-size: 13px; text-align: left;">
                            <th>Avatar</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Request</th>
                            <th>Request Skills</th>
                            <th>Client</th>
                          </thead>
                        </div>
                        <tbody>
                          @foreach($unmatched_requests as $request)
                            <tr>
                              <!-- Avatar -->
                              <td style="vertical-align: top; padding-right: 8px; width: 40px; align: center;"><img src={{ $request["avatar"] }} height="30px" style="border-radius: 50%;"/> </td>
                              <!--  Name of Mentee -->
                              <td style="text-align:left;font-size: 12px;font-weight: 40;"> {{ $request["name"] }} </td>
                              <td style="text-align:left;font-size: 12px;font-weight: 40;"> {{ $request["email"] }} </td>
                              <td style="text-align:left;font-size: 12px;font-weight: 40;"> {{ $request["request_url"] }} </td>
                              <td style="text-align:left;font-size: 12px;font-weight: 40;"> {{ $request["skills"] }} </td>
                              <td style="text-align:left;font-size: 12px;font-weight: 40;"> {{ $request["client"] }} </td>
                            </tr>
                          @endforeach
                        </tbody>
                      </table>
                      <!-- Body -->

                      <!-- Footer section -->
                      <table align="center">
                        <tbody>
                          <tr>
                            <th style="padding-top: 25px;">
                              <p style="font-size: 12px; color: #b3b3b3; font-weight: 100; text-align: center;">
                                This email was sent by the Andela Lenken Development Team.
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
