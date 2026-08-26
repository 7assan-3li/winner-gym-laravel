WINNER GYM — Login RTL / Artwork Blend Final Fix

What this patch fixes:
- Locks the desktop layout to: artwork on the LEFT, Arabic login form on the RIGHT.
- Keeps the form RTL internally.
- Blends the left artwork into the dark UI using edge gradients and a subtle vignette.
- Removes the visible "pasted image/card" feeling by hiding the artwork's own edge/border.
- Preserves the logo and motivational copy without stretching the image.
- Keeps the previous Chrome/Edge autofill white-glow fix and password visibility button.
- No database migration and no npm build required.

Install from project root:
Expand-Archive "$env:USERPROFILE\Downloads\winner-gym-login-rtl-blended-final.zip" -DestinationPath . -Force
php artisan optimize:clear

Then open /login and press Ctrl + F5 once.
