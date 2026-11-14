# Video scripts

Two videos, both recorded from the live site https://continuum-spaceapps.isotherm.app.

- **Local (Santiago form), up to 4:30**, due Sunday 15 November, 3:00 p.m. Spanish narration with English subtitles (subtitles keep it usable for the global judges too).
- **Global demo, up to 30 seconds**, for the NASA project page. English narration and captions.

Both use an AI voice. Say so on screen ("Narración: voz generada por IA / AI-generated voice"), in the YouTube description and in the file metadata. No faces of minors. No NASA logos.

## Local video (4:30, Spanish)

| Time | Screen | Narration (ES) | Subtitle (EN) |
| --- | --- | --- | --- |
| 0:00–0:20 | Home, map of the Dominican Republic's most unusual week | Somos Elminson y Miguel, del equipo Continuum, de Santo Domingo. Elegimos el reto de armonizar los focos de calor de MODIS y VIIRS. Nuestra pregunta es simple: ¿fue inusual este fuego, comparado con qué? | We are Elminson and Miguel, team Continuum, from Santo Domingo. Our challenge: harmonizing MODIS and VIIRS hot spots. Our question is simple: was this fire unusual, compared with what? |
| 0:20–0:55 | Area page Hispaniola, chart "the two sensors" (orange VIIRS dashed line jumping in 2012) | La NASA vigila incendios con MODIS desde el año 2000 y con VIIRS desde 2012. VIIRS tiene píxeles más pequeños y ve mucho más fuego. Si se unen los dos registros tal cual, parece que en 2012 empezó una ola de incendios. No fue así: llegó un satélite nuevo. Y la NASA planea apagar MODIS alrededor de enero de 2027. | NASA has watched fires with MODIS since 2000 and VIIRS since 2012. VIIRS has smaller pixels and sees far more fire. Joined as they are, the record shows a fire boom in 2012. There wasn't one: a new satellite arrived. And NASA plans to switch MODIS off around January 2027. |
| 0:55–1:40 | How it works: pipeline, then the evidence table | Continuum lo resuelve en tres pasos. Uno: cada píxel va a una cuadrícula de un kilómetro, así un frente de fuego cuenta una vez. Dos: unimos todos los satélites de cada sensor. Tres: la diferencia de sensibilidad la medimos con una razón mensual, ajustada en los días en que volaron ambos sensores. Y lo probamos de forma honesta: ajustamos con tres de cada cuatro días y medimos el error en el cuarto. El error baja entre 83 y 90 por ciento frente a la mezcla ingenua, en 1.352 días de prueba por área. | Continuum fixes it in three steps. One: every pixel goes into a 1 km grid, so one fire front counts once. Two: we merge each sensor's satellites. Three: we measure the sensitivity gap with a monthly ratio fitted on the days both sensors flew. And we test it honestly: fit on three of every four days, measure the error on the fourth. Error drops 83 to 90 percent against a naive merge, on 1,352 test days per area. |
| 1:40–2:15 | Area page Hispaniola: fire calendar, then fire season strip | El resultado es un solo calendario de fuego de 26 años. En La Española la temporada va de febrero a abril, con el pico en marzo: concentra más de la mitad del fuego del año. | The result is one 26-year fire calendar. On Hispaniola the season runs February to April, peaking in March: more than half of the year's fire. |
| 2:15–2:55 | Unusual periods, filter Cuba, click week of 17 Aug 2026; map with the satellite image of that day | Cada semana se compara con la misma semana de todos los demás años. La semana del 17 de agosto de 2026 en Cuba fue 24,5 veces lo normal. Aquí está cada celda que ardió, sobre la imagen satelital de la NASA de ese día. Del 10 al 31 de agosto fue el período más alto en 26 años. | Every week is compared with the same week in all other years. The week of 17 August 2026 in Cuba was 24.5 times normal. Here is every cell that burned, over NASA's satellite image of that day. 10 to 31 August was the highest in 26 years. |
| 2:55–3:30 | Explore: pick "Dominican Republic", Analyze, map with fires inside the box | Cualquier persona puede explorar un área: la República Dominicana, Santo Domingo o un recuadro dibujado en el mapa. Continuum ajusta y prueba su propia razón para ese recuadro y construye su calendario al instante. Sin crear cuenta. | Anyone can explore an area: the Dominican Republic, Santo Domingo or a box drawn on the map. Continuum fits and tests its own ratio for that box and builds its calendar instantly. No account needed. |
| 3:30–4:00 | Area page, "When MODIS retires": click 2024 | ¿Y cuando MODIS se apague? Lo simulamos: apagamos MODIS en 2024, reconstruimos solo con VIIRS y comparamos con el registro real. La forma del registro se mantiene, con correlación mensual de hasta 0,99. | And when MODIS switches off? We simulate it: switch MODIS off in 2024, rebuild from VIIRS alone and compare with the real record. The record keeps its shape, with monthly correlation up to 0.99. |
| 4:00–4:25 | Ingestion page, then API page | Todo usa datos abiertos de la NASA, FIRMS y GIBS, actualizados cada hora, con una API pública y descargas en CSV, en español e inglés. Queremos que cualquier agencia del Caribe pueda usarlo, empezando por la nuestra. Pruébenlo en continuum-spaceapps.isotherm.app. | Everything runs on NASA open data, FIRMS and GIBS, updated hourly, with a public API and CSV downloads, in Spanish and English. We want any agency in the Caribbean to use it, starting with ours. Try it at continuum-spaceapps.isotherm.app. |

Closing card (last 5 s): "Equipo Continuum · Elminson y Miguel · Santo Domingo · Datos: NASA FIRMS, NASA GIBS · Narración: voz generada por IA".

## Global demo (30 s, English)

| Time | Screen | Narration |
| --- | --- | --- |
| 0:00–0:06 | Hispaniola chart, VIIRS jumping in 2012 | NASA's two fire sensors disagree: VIIRS sees far more fire than MODIS. |
| 0:06–0:13 | How it works, evidence table | Continuum puts both on one 1 km grid and one scale, cutting the error of a naive merge by 83 to 90 percent. |
| 0:13–0:21 | Cuba, week of 17 August 2026, map with satellite image | Now 26 years compare: Cuba's August 2026 fires were 24.5 times a normal week. |
| 0:21–0:27 | Explore, Dominican Republic | Explore any area, even after MODIS retires. |
| 0:27–0:30 | URL card | continuum-spaceapps.isotherm.app — AI-generated voice. |

## YouTube description (both)

Continuum, NASA Space Apps Challenge 2026, Harmonization of MODIS and VIIRS Hot Spots. Team Continuum (Elminson and Miguel), Santo Domingo, Dominican Republic. Live: https://continuum-spaceapps.isotherm.app · Code: https://github.com/elminson/continuum-spaceapps · Data: NASA FIRMS, NASA GIBS. Narration: AI-generated voice (Microsoft neural text-to-speech). All screens are real recordings of the app.
