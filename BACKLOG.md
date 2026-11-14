# Backlog

Left out of the first build to fit the 14–15 November 2026 window. Add in this order if time allows.

## Done after the first build
- Map of an unusual week: the 1 km cells that burned, over NASA GIBS Blue Marble imagery.
- Explore any box inside the areas; ready-made Caribbean places; optional sign-in to save boxes.
- Fire season per area (start, peak, end, in season now).
- Compare any dates against the same dates in every other year.
- NASA GIBS daily true-colour imagery under the fire map.

## Features
1. **Draw your own area:** pick a box on a map; fetch its full history from the FIRMS Area API and process it.
2. **FRP threshold transfer:** a second transfer that keeps only VIIRS cells a MODIS pixel could have seen.
3. **Raw hotspot CSV:** download the NASA detections behind any period.
4. **Daily and weekly charts:** today only monthly.
5. **More areas:** Colombia, Northern Australia, Borneo–Sumatra.
6. **AI reading of an alert:** an advisory explanation of an unusual week.

## Method
- Track the MODIS/VIIRS ratio over time and per VIIRS satellite (the retirement test shows ±20% drift after 2–3 years).
- Per-cell cloud masks instead of a day-level coverage proxy.
- Cross-check against burned-area products.

## Engineering
- Static analysis (PHPStan) and CI on GitHub Actions.
- JSON API documentation page.
- Queue worker for long jobs instead of running them in commands.
