# WINNER GYM — Nutrition Final v2

This patch upgrades the real nutrition module already present in Winner Gym. It does not invent a separate meal-plan database that the current schema does not have.

## Included

- Professional `/nutrition/appointments` workspace matching Dashboard / Members / Subscriptions / Attendance / Finance / Inventory.
- Standard Winner Gym top bar: page title + breadcrumb, nutrition search, theme, today's nutrition appointment indicator, dynamic logged-in user at the far right.
- Today's appointment KPIs: total, confirmed, unpaid, no-show, unique nutrition clients.
- Search + date + status + nutritionist filters.
- Upcoming appointments panel.
- Selected appointment action panel:
  - confirm appointment
  - receive full payment (cash or transfer + optional proof)
  - complete session
  - mark no-show
  - record measurements
  - reverse a payment when permitted
  - cancel an unpaid appointment with required reason
- Booking modal for gym members or non-member nutrition clients.
- Nutritionist schedule modal and automatic available-slot calculation.
- Conflict prevention and schedule-window validation remain enforced by AppointmentService.
- Non-member nutrition client creation.
- Professional `/nutrition/measurements` page with latest metrics and history.
- Measurement modal dynamically reads active measurement types and automatically calculates BMI from weight + height.
- Adds two missing measurement types used by the approved nutrition reference: `muscle_mass` and `hip`.
- Finance integration correction: nutrition revenue uses appointment payment status `paid` (the actual DB constraint), and 6-month finance flow includes subscription + nutrition + product-sale revenue.

## Install

```powershell
cd "C:\Users\Omer AL-Kaff\Herd\winner-gym-laravel"
Expand-Archive "$env:USERPROFILE\Downloads\winner-gym-nutrition-final-v2.zip" -DestinationPath . -Force
php artisan migrate
php artisan optimize:clear
```

No `npm run build` is required. Do not run `migrate:fresh`.

Then open:

- `http://winner-gym-laravel.test/nutrition/appointments`
- `http://winner-gym-laravel.test/nutrition/measurements`

Use `Ctrl + F5` once after installation.
