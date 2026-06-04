@php
    $_siteName = \App\Models\AppSetting::get('site_name', config('app.name'));
    $_logo     = \App\Models\AppSetting::get('logo1', '');
    $_theme    = \App\Models\AppSetting::get('theme_color', '#22c55e');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('subject', $_siteName)</title>
</head>
<body style="margin:0;padding:0;background-color:#f2f3f8;font-family:Arial,Helvetica,sans-serif;">

<table cellspacing="0" border="0" cellpadding="0" width="100%" bgcolor="#f2f3f8">
<tbody>
<tr><td>
<table style="background-color:#f2f3f8;max-width:670px;margin:0 auto" width="100%" border="0" align="center" cellpadding="0" cellspacing="0">
<tbody>

  {{-- Top spacing --}}
  <tr><td style="height:80px">&nbsp;</td></tr>

  {{-- Logo --}}
  <tr>
    <td style="text-align:center">
      @if($_logo)
        <img src="{{ url(\Illuminate\Support\Facades\Storage::url($_logo)) }}"
             alt="{{ $_siteName }}" width="180"
             style="max-width:180px;height:auto;display:inline-block">
      @else
        <span style="font-size:26px;font-weight:700;color:#1e1e2d;letter-spacing:-0.5px">{{ $_siteName }}</span>
      @endif
    </td>
  </tr>

  {{-- Spacing --}}
  <tr><td style="height:20px">&nbsp;</td></tr>

  {{-- White card --}}
  <tr>
    <td>
      <table width="95%" border="0" align="center" cellpadding="0" cellspacing="0"
             style="max-width:670px;background:#ffffff;border-radius:3px;text-align:center;
                    -webkit-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);
                    -moz-box-shadow:0 6px 18px 0 rgba(0,0,0,.06);
                    box-shadow:0 6px 18px 0 rgba(0,0,0,.06)">
      <tbody>
        <tr><td style="height:40px">&nbsp;</td></tr>
        <tr>
          <td style="padding:0 35px">

            {{-- Title --}}
            <h1 style="color:#1e1e2d;font-weight:500;margin:0;font-size:32px;font-family:Arial,Helvetica,sans-serif">
              @yield('title')
            </h1>

            {{-- Divider --}}
            <span style="display:inline-block;vertical-align:middle;margin:29px 0 26px;border-bottom:1px solid #cecece;width:100px"></span>

            {{-- Body content --}}
            @yield('body')

          </td>
        </tr>
        <tr><td style="height:40px">&nbsp;</td></tr>
      </tbody>
      </table>
    </td>
  </tr>

  {{-- Spacing --}}
  <tr><td style="height:20px">&nbsp;</td></tr>

  {{-- Footer --}}
  <tr>
    <td style="text-align:center">
      <p style="font-size:14px;color:rgba(69,80,86,0.74);line-height:18px;margin:0">
        &copy; {{ date('Y') }} <strong>{{ $_siteName }}</strong>
      </p>
    </td>
  </tr>

  {{-- Bottom spacing --}}
  <tr><td style="height:80px">&nbsp;</td></tr>

</tbody>
</table>
</td></tr>
</tbody>
</table>

</body>
</html>
