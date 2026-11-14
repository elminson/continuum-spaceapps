# Backlog

Left out of the first build to fit the 14–15 November 2026 window. Add in this order if time allows.

## Done after the first build
- Map of an unusual week: the 1 km cells that burned, over NASA GIBS Blue Marble imagery.

## Features
1. **Draw your own area:** pick a box on a map; fetch its full history from the FIRMS Area API and process it.
2. **Date-range picker:** any from/to dates with the median for the same dates in other years.
3. **Fire season:** onset, peak and end per area, and whether today is inside the season.
4. **FRP threshold transfer:** a second transfer that keeps only VIIRS cells a MODIS pixel could have seen.
5. **Raw hotspot CSV:** download the NASA detections behind any period.
6. **Daily and weekly charts:** today only monthly.
7. **More areas:** Colombia, Northern Australia, Borneo–Sumatra.
8. **AI reading of an alert:** an advisory explanation of an unusual week.
9. **Accounts:** save areas per user.

## Method
- Track the MODIS/VIIRS ratio over time and per VIIRS satellite (the retirement test shows ±20% drift after 2–3 years).
- Per-cell cloud masks instead of a day-level coverage proxy.
- Cross-check against burned-area products.

## Engineering
- Static analysis (PHPStan) and CI on GitHub Actions.
- JSON API documentation page.
- Queue worker for long jobs instead of running them in commands.
