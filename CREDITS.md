# Credits

Everything this project uses that we did not write, with its license and a public link.

## NASA data and resources

| Resource | Use | Terms | Link |
| --- | --- | --- | --- |
| NASA FIRMS active fire data: MODIS Collection 6.1 (Terra, Aqua), 1 km | Core input: every detection since November 2000 | NASA open data policy; cited here and in the app | https://firms.modaps.eosdis.nasa.gov |
| NASA FIRMS active fire data: VIIRS 375 m (Suomi NPP, NOAA-20, NOAA-21) | Core input: every detection since January 2012 | NASA open data policy | https://firms.modaps.eosdis.nasa.gov |
| NASA FIRMS near-real-time global CSV feeds (24 h) | Hourly updates | NASA open data policy | https://firms.modaps.eosdis.nasa.gov/active_fire/ |
| NASA GIBS, Blue Marble shaded relief and bathymetry | Base map under the fire maps | NASA open data policy | https://gibs.earthdata.nasa.gov |
| NASA Earthdata: MODIS to VIIRS transition notice | Shutdown dates (Aqua ~Aug–Sep 2026, Terra ~Jan 2027) | Public | https://www.earthdata.nasa.gov/data/alerts-outages/transition-from-modis-viirs |

The raw FIRMS detections for the four areas (21.2 million rows) were downloaded before the event, which the Santiago organizers allow. All processing happens in this repo's code.

## Software

| Library | License | Link |
| --- | --- | --- |
| Laravel (framework and Vue starter kit) | MIT | https://laravel.com |
| Inertia.js | MIT | https://inertiajs.com |
| Vue | MIT | https://vuejs.org |
| Tailwind CSS | MIT | https://tailwindcss.com |
| Apache ECharts | Apache-2.0 | https://echarts.apache.org |
| vue-echarts | MIT | https://github.com/ecomfe/vue-echarts |
| MapLibre GL JS | BSD-3-Clause | https://maplibre.org |
| Radix Vue, Lucide icons (from the starter kit) | MIT, ISC | https://www.radix-vue.com, https://lucide.dev |
| PostgreSQL | PostgreSQL License | https://www.postgresql.org |
| PHPUnit | BSD-3-Clause | https://phpunit.de |

## Our earlier work

- **Logo** (`public/favicon.svg`, `favicon.ico`, `apple-touch-icon.png`): our Continuum brand mark, drawn for our earlier prototype (github.com/elminson/continuum, September 2026) and reused here. No code was copied from that prototype.

## AI ("Super Intelligence") use

- **Code:** written with Claude Code (Anthropic, https://www.anthropic.com/claude-code) as a coding assistant. Commits it helped write end with `Co-Authored-By: Claude`.
- **Text:** README, method text, translations and submission text drafted with Claude and edited by the team.
- **Video narration (when added):** AI text-to-speech voice, stated on screen, in the video description and in the file metadata.
- No NASA logos or branding are used or modified in anything generated.
