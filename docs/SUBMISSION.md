# NASA Space Apps submission

Paste each block into the matching field of the team page (spaceappschallenge.org → My Team → Project → Edit). Everything is in English, as the judges read in English. Numbers below are live from https://continuum-spaceapps.isotherm.app on 15 November 2026.

> **For the browser assistant filling the form:** each `##` heading below is one field of the NASA project page; paste the text under it verbatim into that field. After every field is filled, click **Submit for Judging** only when the team owner confirms.

## Project Name

Continuum

## High-Level Project Summary

Continuum answers one question for fire and land managers: was this fire unusual, compared with what? NASA has watched fires with MODIS since 2000 and with VIIRS since 2012, but VIIRS sees several times more fire, so a naive merge makes 2012 look like the start of a fire boom. Continuum puts every NASA FIRMS detection on one 1 km grid, merges each sensor's satellites, and fits a monthly MODIS/VIIRS ratio on the days both flew. Tested on 1,352 held-out days per area, it cuts the error of a naive merge by 83–90%. One day shows why: on 28 April 2015 MODIS saw 9 burning 1 km cells in the Dominican Republic and VIIRS saw 121. Taking the VIIRS count as if it were MODIS is off by 112; applying the April ratio (0.124) gives 15, off by 6. The −89% is that error averaged over the 1,352 test days (2.4 cells per day instead of 22.6). The result is one 26-year fire calendar on either sensor's scale, built first for the Dominican Republic (error −89%; its most unusual week, 27 April 2015, was 11.5 times normal), with fire seasons, unusual weeks, date comparisons, maps and open data. It flagged Cuba's August 2026 wildfires at 24.5 times a normal week. Because NASA plans to shut MODIS down around January 2027, Continuum also tests the future: rebuilt from VIIRS alone, the record keeps its shape (monthly r 0.93–0.996).

## Link to Project Demo

https://youtu.be/ypfUgrSXI0Y (30 seconds, English)
Explanation in Spanish with English subtitles (4:23): https://youtu.be/2g1FSY0PNK8
Slides (7): https://github.com/elminson/continuum-spaceapps/blob/main/docs/presentation/continuum-slides.pdf

## Link to Final Project

https://continuum-spaceapps.isotherm.app (no login needed; the start page states the question, who uses the tool and the answers to the usual questions; the live map and numbers are at /resume)
For judges, every ask of the challenge and each criterion mapped to its proof: https://continuum-spaceapps.isotherm.app/judges
Code: https://github.com/elminson/continuum-spaceapps

## Detailed Project Description

**What it does.** Continuum is a web app with a 26-year (November 2000 → today) harmonized fire record, focused on the **Dominican Republic**, plus Hispaniola (Dominican Republic and Haiti), Cuba, the Gran Chaco (Argentina, Paraguay, Bolivia) and the Cerrado and Amazon arc of deforestation (Brazil) to show the method holds in very different fire regimes. For each area it shows:

- a month-by-year fire calendar and the two sensors next to the harmonized record, on the MODIS or the VIIRS scale;
- the fire season (start, peak, end, its share of the year, and whether we are in it now);
- any date range compared with the same dates in every other year (value, typical value, times normal, rank);
- unusual weeks: each week against the same week in every other year, with a map of the 1 km cells that burned and NASA's satellite image of that day;
- a short fire bulletin in plain language (English and Spanish), written by an AI model from the computed facts and checked number by number;
- fire alerts by email for any drawn zone: a daily check of the last complete week against the same week in every other year, one email per flagged week, a sample and a test alert on demand (SMS coming soon);
- downloads: every map as a PNG and as a CSV of the burning 1 km cells, every calendar as a PNG;
- a one-week **forecast** per area: ridge regression on this harmonized record and NASA POWER weather (temperature, humidity, rain, wind), validated year by year 2013→2026 on 722 held-out weeks per area: one week ahead, 19–31% less error than the same-week climatology, 6–22% less than persistence, r 0.71–0.89 (two weeks ahead, used when the current week has already begun: 13–19% and r 0.67–0.87); shown with its skill on every area page;
- a test of the end of MODIS (below);
- an **AI analysis** page gathering the situation reading across areas and every area's bulletins, each written by Claude (Anthropic API) or Gemma 4 (self-hosted) from computed facts, number-checked and labelled;
- CSV downloads and a public JSON API;
- a start page that answers the three questions reviewers asked first (how the 89% is achieved, compared with what, which decision it serves and who takes it), a Questions page with a live worked example of one 1 km cell through the four steps, and a judges page that maps every line of the challenge brief and the five criteria to its proof.

The Explore page analyses any box inside the areas instantly (drag to draw), lets anyone request a zone outside them (loaded from the NASA FIRMS archive with `fire:area` and `fire:import`), with ready-made places such as the Dominican Republic, Haiti, Santo Domingo, Santiago and Puerto Rico. Everything works without an account; signing in only saves boxes. The interface is in English and Spanish, and new NASA data arrives every hour.

**Questions it answers** (each shown live on the home page, with a link to the proof):
- Was this week unusual? Dominican Republic, week of 27 April 2015: 11.5 times the same week in a normal year.
- When is the fire season, and are we in it now? Dominican Republic: March to April, peak in March, 44% of a typical year's fire.
- Was this period worse than usual? Cuba, 10–31 August 2026: the highest of 26 years for those dates.
- Where did it burn? Every burning 1 km cell over NASA's satellite image of that day.
- Can I trust the numbers? −89% error vs a naive merge on 1,352 held-out days.
- What happens when MODIS shuts down? Rebuilt from VIIRS alone, the monthly record keeps its shape.
- What does next week look like? A validated one-week outlook per area (×normal with a range and its skill score).
- Can our own system use it? Open JSON API and CSV.

**How it relates to NASA FIRMS.** FIRMS tells you where fire is; Continuum tells you whether it is unusual. It is built on FIRMS: it turns the pixels of two different sensors into one comparable 26-year record, which FIRMS does not provide.

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

The percentage is an average: the mean absolute error per day over those held-out days, harmonized record versus naive merge (Dominican Republic: 2.4 against 22.6 burning cells per day). One day as an example: 28 April 2015, MODIS 9 cells, VIIRS 121; the naive merge is off by 112, the harmonized value (121 × 0.124 = 15) by 6. A sensor's own observations are always used when it flew; converted values are marked as estimated, with their interval. Unusual weeks use the median and median absolute deviation of the same week in every other year, so one extreme year cannot hide another.

**Next week.** For each area a ridge regression predicts the coming week's burning cell-days from the last weeks of fire, the same-week climatology and NASA POWER weather (7- and 28-day temperature, humidity, rain and wind). It is validated the only honest way for a forecast, rolling origin: trained on the years before each year 2013→2026 and tested on that year, against two baselines. Results on 722 held-out weeks per area: Dominican Republic −19% error vs climatology (r 0.71), Hispaniola −20% (r 0.73), Cuba −25% (r 0.89), Gran Chaco −30% (r 0.82), Cerrado −31% (r 0.87); and −6% to −22% vs persistence. The page shows the outlook as "×N of normal" with a range and these scores next to it.

**When MODIS retires.** NASA plans to stop Aqua MODIS around August–September 2026 and Terra MODIS around January 2027. Continuum simulates it: pick the year MODIS "stops", fit the ratio only on earlier days, rebuild every later month from VIIRS alone and compare with the real MODIS record. With MODIS switched off on 1 January 2024: daily error 81–89% below a naive switch, monthly correlation 0.93–0.996, and totals within −0.4% to +25% depending on the area.

**Benefits, and who executes them.** Continuum is built for three immediate decisions, each with its trigger: (1) raise the week's readiness level and move brigades on Monday when the next-week outlook is at ×2 of normal or more; (2) dispatch to the flagged zone within 24 hours of the alert email; (3) suspend burn permits and issue a public warning today when the fire season is on and the current week runs above normal. The priority job is one weekly decision: the forest-fire unit of the Dominican Republic's Ministry of Environment opens the Dominican Republic page on Monday morning, reads the next-week outlook, the bulletin and where last week burned against 26 years of the same week, and sets the readiness level and the placement of brigades with a measured number. Provincial emergency operations (COE) and municipal brigades draw their territory once and receive one email when a week there turns unusual. Planners, journalists and researchers compare any dates with every other year and download the burning cells and the record to document an event with a defensible baseline. Fire managers can compare years across the 2012 sensor change, so a busy season reads as a busy season, not as a new satellite; the week of 17 August 2026 in Cuba was 24.5 times a normal week, and 10–31 August 2026 was the highest of 26 years for those dates. Scientists get validated numbers with uncertainty and open data.

**What we hope to achieve.** A free, open fire calendar that any agency in the Caribbean and Latin America can point at its own territory, starting with the Dominican Republic and Haiti, and that keeps working on VIIRS alone after MODIS ends.

**Tools.** PHP 8.4, Laravel 12, Inertia with Vue 3 and TypeScript, Tailwind CSS, PostgreSQL, Apache ECharts, MapLibre GL JS, PHPUnit (59 automated tests). Hosted on one Linux server; GitHub → Spinnr deploys; the scheduler pulls NASA FIRMS every hour.

**Factors considered.** Accessibility: no login, English and Spanish, works on phones. Cost: one small server and only free, open data. Honesty: every number has its test, its limits are on the Method page, and estimated values are marked.

**How it was built.** All code was written during the event (14–15 November 2026), starting from the Laravel starter kit; the git history shows it. The raw NASA FIRMS detections for the four areas were downloaded before the event, which the Santiago organizers allow.

## Use of Artificial Intelligence (AI)

Yes. Tools and purpose:

- **Claude Code (Anthropic)**, a coding assistant: helped write, test and review the Laravel and Vue code, and draft the README, method text, Spanish translations and this submission. Commits it helped write carry a `Co-Authored-By: Claude` line in the public git history.
- **Microsoft Edge neural text-to-speech voices**: the narration of the demo videos. This is stated on screen, in the video descriptions and in the file metadata.

- **Claude (Anthropic API, model claude-opus-5-5) and Gemma 4 (Google, open model, self-hosted with Ollama)**, inside the app: one of them writes the short plain-language **fire bulletin** on each area page and the **situation reading** on the Unusual periods page (English and Spanish) from facts the code computes (times normal, rank among the same week in other years, where in the area, the fire season); the other is the fallback. Every number in the text is checked against those facts; if any number does not match, the app shows a fixed template instead. Each text is labelled with the model that wrote it. The AI never decides what is unusual: the statistics do. https://www.anthropic.com/api · https://ai.google.dev/gemma · https://ollama.com

No AI was used to generate data, numbers or images. Every chart and map shows NASA data processed by the app's own code. No NASA logos or branding were generated or modified.

## Space Agency Data

- NASA FIRMS (Fire Information for Resource Management System) active fire data, the core of the project: MODIS Collection 6.1 (Terra, Aqua, 1 km) and VIIRS 375 m (Suomi NPP, NOAA-20, NOAA-21), standard and near-real-time. https://firms.modaps.eosdis.nasa.gov
- NASA FIRMS near-real-time global 24-hour CSV feeds, pulled every hour. https://firms.modaps.eosdis.nasa.gov/active_fire/
- NASA GIBS (Global Imagery Browse Services): Blue Marble shaded relief and MODIS Terra corrected reflectance (true colour) under the fire maps. https://gibs.earthdata.nasa.gov
- NASA POWER (Prediction Of Worldwide Energy Resources): daily temperature, relative humidity, precipitation and wind at each area's centre since 2000, the drivers of the one-week forecast. https://power.larc.nasa.gov
- NASA Earthdata, MODIS to VIIRS transition notice (MODIS shutdown dates). https://www.earthdata.nasa.gov/data/alerts-outages/transition-from-modis-viirs

## References

- Claude Code (Anthropic): https://www.anthropic.com/claude-code
- Claude API (Anthropic): https://www.anthropic.com/api
- Gemma 4 (Google, open model): https://ai.google.dev/gemma · Ollama: https://ollama.com
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
