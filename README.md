# Continuum

**One fire calendar from 26 years of NASA satellites.** NASA Space Apps Challenge 2026 · *Harmonization of MODIS and VIIRS Hot Spots* · Team Continuum (Elminson & Miguel, Santo Domingo, Dominican Republic).

Live: https://continuum-spaceapps.isotherm.app (start page: the question, who it is for, the answers; live overview at /resume) · Code: https://github.com/elminson/continuum-spaceapps · For judges: https://continuum-spaceapps.isotherm.app/judges

Videos (YouTube): [30-second demo, English](https://youtu.be/LJ6uETwGb_0) · [2:50 explanation, Spanish with English subtitles](https://youtu.be/Sbg-dRMqPS8) · Slides: [7-slide deck (PDF)](docs/presentation/continuum-slides.pdf). Narration is an AI-generated voice.

## The problem

NASA has watched fires from space with MODIS since 2000 (1 km pixels) and with VIIRS since 2012 (375 m pixels). VIIRS sees many more small fires, so simply joining the two records makes 2012 look like the start of a fire boom. NASA plans to shut MODIS down around January 2027, so the record has to continue on VIIRS alone.

## What Continuum does

- **One grid.** Every NASA FIRMS pixel goes into a 0.01° cell (about 1 km). A fire front that VIIRS reports with several pixels counts once, as MODIS reports it.
- **One count per sensor.** All satellites of a sensor are merged (Terra + Aqua; Suomi NPP + NOAA-20 + NOAA-21).
- **One ratio for the physics.** On the days both sensors flew, MODIS ÷ VIIRS cell-days is fitted per calendar month with a 95% bootstrap interval.
- **An honest test.** Fitted on three of every four overlap days, tested on the fourth, against a naive merge.
- **One scale.** Read the whole record on the MODIS or the VIIRS scale; converted values are marked as estimated.
- **Unusual weeks.** Each week against the same week in every other year (median and MAD).
- **When MODIS retires.** Pick a year: the ratio is fitted on earlier days only, later months are rebuilt from VIIRS alone and checked against the real MODIS record.
- **Next week outlook.** A ridge regression per area on this record and NASA POWER weather (temperature, humidity, rain, wind), validated year by year 2013→2026 on 722 held-out weeks per area (one week ahead: 19–31% less error than climatology, r 0.71–0.89; two weeks ahead, when the current week has begun: 13–19%, r 0.67–0.87); shown with its skill, never without it.
- **Fire alerts by email.** Draw a zone; every morning its last complete week is scored against the same week in every other year, and one email goes out per flagged week (sample and test alerts on demand; SMS coming soon).
- **AI that explains, never decides.** A bulletin per area and a situation reading across areas, written by Claude (Anthropic API) or Gemma 4 (self-hosted) from the computed facts only; every number is checked against the facts, or a fixed template is shown. All of it on one page: `/ai`.
- English and Spanish, CSV and PNG downloads, public JSON API. New NASA FIRMS data every hour; any new zone can be loaded from the FIRMS archive (`fire:area`, `fire:import`).

Areas: **Dominican Republic** (the focus), Hispaniola (Dominican Republic and Haiti), Cuba, Gran Chaco, Cerrado & Amazon arc.

## How this repo was built

- **All code in this repo was written during the event (14–15 November 2026),** starting from the Laravel Vue starter kit (first commit).
- **An earlier prototype exists** at https://github.com/elminson/continuum (September 2026). This repo is a new build made during Space Apps; no code was copied from it.
- **Data:** the raw NASA FIRMS detections for the four areas (21.2 million) were downloaded before the event, when we chose the challenge. All processing of that data is done by the code in this repo; new data arrives every hour from the FIRMS feeds.
- **AI use:** written with Claude Code (Anthropic) as a coding assistant; commits carry a `Co-Authored-By: Claude` line. Inside the app, Claude (Anthropic API) and Gemma 4 (self-hosted) write the bulletins from checked numbers; see `/ai`.

Submission text: [docs/SUBMISSION.md](docs/SUBMISSION.md). See [CREDITS.md](CREDITS.md) for every dataset, library and AI tool used, and [BACKLOG.md](BACKLOG.md) for what was left out.

## Run it

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate   # set the PostgreSQL connection
php artisan migrate --seed
php artisan fire:ingest          # last 24 h of NASA FIRMS for the areas
php artisan fire:process         # grid, area totals, fit, unusual weeks
npm run build && php artisan serve
```

The scheduler runs `fire:ingest` every hour (`php artisan schedule:run` from cron). Tests: `php artisan test` (PostgreSQL).

## Add a zone

The database holds the NASA FIRMS archive for the areas listed on the site. Any other zone takes three commands and one download:

```bash
php artisan fire:area jamaica "Jamaica" -78.4 17.7 -76.2 18.6 --name-es=Jamaica
# request the archive for that box at https://firms.modaps.eosdis.nasa.gov/download/
# (MODIS C6.1 from 2000-11-01; VIIRS S-NPP, NOAA-20 and NOAA-21 from 2012-01-20); NASA emails the CSV files
php artisan fire:import ~/Downloads/fire_archive_*.csv --area=jamaica   # streams the files, then builds and warms the area
```

NASA prepares the archive in minutes to an hour; importing and harmonizing a country-sized zone takes from 10 minutes to a few hours here. Visitors who draw outside the loaded areas can request a zone from the Explore page; `zone_requests` lists them.

## API

| Endpoint | Returns |
| --- | --- |
| `GET /api/areas` | The areas and their bounding boxes |
| `GET /api/areas/{slug}/monthly?scale=modis\|viirs` | Harmonized monthly cell-days, both native sensors, the fit and its validation |
| `GET /api/areas/{slug}/monthly.csv?scale=…` | The same as CSV |
| `GET /api/areas/{slug}/alerts` | Unusual weeks |
| `GET /api/areas/{slug}/retirement?year=2024` | The MODIS retirement test |

## Deployment

GitHub `main` → Spinnr (spinnr.dev) → the server pulls `main`; then `composer install --no-dev`, `npm run build`, `php artisan optimize`. Nginx + PHP-FPM + PostgreSQL on one Linux server; cron runs the Laravel scheduler.

## References

- NASA FIRMS active fire data: MODIS Collection 6.1 (Terra, Aqua) and VIIRS 375 m (Suomi NPP, NOAA-20, NOAA-21). https://firms.modaps.eosdis.nasa.gov
- NASA MODIS → VIIRS transition: https://www.earthdata.nasa.gov/data/alerts-outages/transition-from-modis-viirs
- Full list with licenses: [CREDITS.md](CREDITS.md).

## License

MIT
