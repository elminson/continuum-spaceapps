# NASA Space Apps submission

Paste each block into the matching field of the team page (spaceappschallenge.org → My Team → Project → Edit). Everything is in English, as the judges read in English. Numbers below are live from https://continuum-spaceapps.isotherm.app on 14 November 2026.

## Project Name

Continuum

## High-Level Project Summary

Continuum answers one question for fire and land managers: was this fire unusual, compared with what? NASA has watched fires with MODIS since 2000 and with VIIRS since 2012, but VIIRS sees several times more fire, so a naive merge makes 2012 look like the start of a fire boom. Continuum puts every NASA FIRMS detection on one 1 km grid, merges each sensor's satellites, and fits a monthly MODIS/VIIRS ratio on the days both flew. Tested on 1,352 held-out days per area, it cuts the error of a naive merge by 83–90%. The result is one 26-year fire calendar on either sensor's scale, built first for the Dominican Republic (error −89%; its most unusual week, 27 April 2015, was 11.5 times normal), with fire seasons, unusual weeks, date comparisons, maps and open data. It flagged Cuba's August 2026 wildfires at 24.5 times a normal week. Because NASA plans to shut MODIS down around January 2027, Continuum also tests the future: rebuilt from VIIRS alone, the record keeps its shape (monthly r 0.93–0.996).

## Link to Project Demo

`<YouTube link to the 30-second video>`
Slides (7): https://github.com/elminson/continuum-spaceapps/blob/main/docs/presentation/continuum-slides.pdf

## Link to Final Project

https://continuum-spaceapps.isotherm.app (no login needed)
Code: https://github.com/elminson/continuum-spaceapps

## Detailed Project Description

**What it does.** Continuum is a web app with a 26-year (November 2000 → today) harmonized fire record, focused on the **Dominican Republic**, plus Hispaniola (Dominican Republic and Haiti), Cuba, the Gran Chaco (Argentina, Paraguay, Bolivia) and the Cerrado and Amazon arc of deforestation (Brazil) to show the method holds in very different fire regimes. For each area it shows:

- a month-by-year fire calendar and the two sensors next to the harmonized record, on the MODIS or the VIIRS scale;
- the fire season (start, peak, end, its share of the year, and whether we are in it now);
- any date range compared with the same dates in every other year (value, typical value, times normal, rank);
- unusual weeks: each week against the same week in every other year, with a map of the 1 km cells that burned and NASA's satellite image of that day;
- a test of the end of MODIS (below);
- CSV downloads and a public JSON API.

The Explore page analyses any box inside the four areas instantly, with ready-made places such as the Dominican Republic, Haiti, Santo Domingo, Santiago and Puerto Rico. Everything works without an account; signing in only saves boxes. The interface is in English and Spanish, and new NASA data arrives every hour.

**How it works.** The disagreement between MODIS and VIIRS is three problems, each handled separately:

1. Pixel size: a MODIS pixel is about 1 km, a VIIRS pixel 375 m, so one fire front is one MODIS pixel but several VIIRS pixels. Continuum counts 1 km grid cells with fire per day (cell-days) instead of pixels. VIIRS averages 2.4 pixels per cell; MODIS 1.1.
2. Number of satellites: Terra and Aqua for MODIS; Suomi NPP, NOAA-20 and NOAA-21 for VIIRS. Each sensor's satellites are merged before counting.
3. Sensitivity: VIIRS still sees smaller, cooler fires. On the days both sensors flew, Continuum fits MODIS ÷ VIIRS cell-days per calendar month, with a 95% bootstrap interval.

The ratio is fitted on three of every four overlap days and tested on the fourth, against a naive merge that just switches sensors:

| Area | MODIS ÷ VIIRS | Held-out days | Error vs naive merge | Correlation |
| --- | --- | --- | --- | --- |
| **Dominican Republic** | 0.135 | 1,352 | −89% | r 0.726 |
| Hispaniola | 0.129 | 1,352 | −90% | r 0.727 |
| Cuba | 0.232 | 1,352 | −85% | r 0.853 |
| Gran Chaco | 0.345 | 1,352 | −86% | r 0.942 |
| Cerrado & Amazon arc | 0.347 | 1,352 | −83% | r 0.919 |

A sensor's own observations are always used when it flew; converted values are marked as estimated, with their interval. Unusual weeks use the median and median absolute deviation of the same week in every other year, so one extreme year cannot hide another.

**When MODIS retires.** NASA plans to stop Aqua MODIS around August–September 2026 and Terra MODIS around January 2027. Continuum simulates it: pick the year MODIS "stops", fit the ratio only on earlier days, rebuild every later month from VIIRS alone and compare with the real MODIS record. With MODIS switched off on 1 January 2024: daily error 81–89% below a naive switch, monthly correlation 0.93–0.996, and totals within −0.4% to +25% depending on the area.

**Benefits.** Fire managers can compare years across the 2012 sensor change, so a busy season reads as a busy season, not as a new satellite. Responders get early warning: the week of 17 August 2026 in Cuba was 24.5 times a normal week, and 10–31 August 2026 was the highest of 26 years for those dates. Land managers get each area's fire season (Dominican Republic: March–April, peak in March), and scientists get validated numbers with uncertainty and open data.

**What we hope to achieve.** A free, open fire calendar that any agency in the Caribbean and Latin America can point at its own territory, starting with the Dominican Republic and Haiti, and that keeps working on VIIRS alone after MODIS ends.

**Tools.** PHP 8.4, Laravel 12, Inertia with Vue 3 and TypeScript, Tailwind CSS, PostgreSQL, Apache ECharts, MapLibre GL JS, PHPUnit (38 automated tests). Hosted on one Linux server; GitHub → Spinnr deploys; the scheduler pulls NASA FIRMS every hour.

**Factors considered.** Accessibility: no login, English and Spanish, works on phones. Cost: one small server and only free, open data. Honesty: every number has its test, its limits are on the Method page, and estimated values are marked.

**How it was built.** All code was written during the event (14–15 November 2026), starting from the Laravel starter kit; the git history shows it. The raw NASA FIRMS detections for the four areas were downloaded before the event, which the Santiago organizers allow. An earlier prototype exists (github.com/elminson/continuum, September 2026); no code was copied from it, and the logo is reused from it.

## Use of Artificial Intelligence (AI)

Yes. Tools and purpose:

- **Claude Code (Anthropic)**, a coding assistant: helped write, test and review the Laravel and Vue code, and draft the README, method text, Spanish translations and this submission. Commits it helped write carry a `Co-Authored-By: Claude` line in the public git history.
- **Microsoft Edge neural text-to-speech voices**: the narration of the demo videos. This is stated on screen, in the video descriptions and in the file metadata.

No AI was used to generate data, numbers or images. Every chart and map shows NASA data processed by the app's own code. No NASA logos or branding were generated or modified.

## Space Agency Data

- NASA FIRMS (Fire Information for Resource Management System) active fire data, the core of the project: MODIS Collection 6.1 (Terra, Aqua, 1 km) and VIIRS 375 m (Suomi NPP, NOAA-20, NOAA-21), standard and near-real-time. https://firms.modaps.eosdis.nasa.gov
- NASA FIRMS near-real-time global 24-hour CSV feeds, pulled every hour. https://firms.modaps.eosdis.nasa.gov/active_fire/
- NASA GIBS (Global Imagery Browse Services): Blue Marble shaded relief and MODIS Terra corrected reflectance (true colour) under the fire maps. https://gibs.earthdata.nasa.gov
- NASA Earthdata, MODIS to VIIRS transition notice (MODIS shutdown dates). https://www.earthdata.nasa.gov/data/alerts-outages/transition-from-modis-viirs

## References

- Claude Code (Anthropic): https://www.anthropic.com/claude-code
- Microsoft Edge text-to-speech voices: https://learn.microsoft.com/azure/ai-services/speech-service/language-support
- Laravel (MIT): https://laravel.com
- Inertia.js (MIT): https://inertiajs.com
- Vue (MIT): https://vuejs.org
- Tailwind CSS (MIT): https://tailwindcss.com
- Apache ECharts (Apache-2.0): https://echarts.apache.org
- vue-echarts (MIT): https://github.com/ecomfe/vue-echarts
- MapLibre GL JS (BSD-3-Clause): https://maplibre.org
- PostgreSQL (PostgreSQL License): https://www.postgresql.org
- Copernicus CAMS, "Drifting away from MODIS": https://atmosphere.copernicus.eu/drifting-away-modis-cams-global-fire-assimilation-system
- Full list with licenses: https://github.com/elminson/continuum-spaceapps/blob/main/CREDITS.md

## Tags

wildfire, fire, MODIS, VIIRS, FIRMS, harmonization, time series, Caribbean, Dominican Republic, early warning, open data
