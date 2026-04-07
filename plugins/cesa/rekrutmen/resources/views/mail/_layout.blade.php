<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
  </head>
  <body style="margin:0; padding:0; font-family: Arial, sans-serif; color:#333;">
    <table cellpadding="0" cellspacing="0" width="100%">
      <tr>
        <td width="100%" style="background:#f2f2f2; padding:24px;">
          <table cellpadding="0" cellspacing="0" width="100%" style="background:#fff;">
            <tr>
              <td style="background:#1D4ED8; color:#fff; text-align:center; padding:18px 24px;">
                <h1 style="margin:0; font-size:20px; color:#fff;">{{ $heading }}</h1>
              </td>
            </tr>
            <tr>
              <td style="padding:24px;">
                <p style="margin-top:0;">{{ $greeting }}</p>
                <p>{{ $body }}</p>

                <p style="margin:16px 0 8px; font-weight:bold;">{{ $summaryHeading }}</p>
                <table style="width:100%; border-collapse:collapse; margin:12px 0;">
                  @foreach ($summary as $item)
                    <tr>
                      <td style="padding:6px 10px; border:1px solid #ddd; width:35%; font-weight:bold;">
                        {{ $item['label'] }}
                      </td>
                      <td style="padding:6px 10px; border:1px solid #ddd;">
                        {{ $item['value'] ?? '-' }}
                      </td>
                    </tr>
                  @endforeach
                </table>

                @if (filled($progressUrl ?? null) && filled($actionLabel ?? null))
                  <table style="width:100%; margin:16px 0;" cellpadding="0" cellspacing="0">
                    <tr>
                      <td style="text-align:left;">
                        <a
                          href="{{ $progressUrl }}"
                          target="_blank"
                          style="display:inline-block; padding:12px 20px; background:#1D4ED8; color:#fff; text-decoration:none;"
                        >
                          {{ $actionLabel }}
                        </a>
                      </td>
                    </tr>
                  </table>
                @endif

                @if (filled($footerNote ?? null))
                  <p style="margin:20px 0 0; font-size:12px; line-height:1.6; color:#6b7280;">
                    {{ $footerNote }}
                  </p>
                @endif
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
