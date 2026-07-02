<x-branded-email
    title="Set up your GolfTourney login"
    heading="You're invited to {{ $leagueName }}"
    :cta-url="$url"
    cta-label="Set up your account"
    :message="$message ?? null"
>
    <p style="margin:0 0 16px 0;">Hi {{ $firstName }},</p>
    <p style="margin:0;">You've been invited to join <strong style="color:#14432f;">{{ $leagueName }}</strong> and manage your handicap on GolfTourney — track your rounds and keep your Handicap Index up to date.</p>
</x-branded-email>
