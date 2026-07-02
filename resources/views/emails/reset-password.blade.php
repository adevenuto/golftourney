<x-branded-email
    title="Reset your GolfTourney password"
    heading="Reset your password"
    :cta-url="$url"
    cta-label="Reset password"
    :message="$message ?? null"
>
    <p style="margin:0 0 16px 0;">Hi {{ $firstName }},</p>
    <p style="margin:0 0 16px 0;">We received a request to reset the password for your GolfTourney account. Tap the button below to choose a new one.</p>
    <p style="margin:0;">This link expires in {{ $expireMinutes }} minutes. If you didn't request a reset, you can safely ignore this email — your password won't change.</p>
</x-branded-email>
