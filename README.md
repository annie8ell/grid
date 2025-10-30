# National Grid: Live

This repository contains the source code for [National Grid: Live](https://grid.iamkate.com/).

## Overview

This project tracks overall energy provision and consumption for the UK, providing real-time and historical data on:

### Electricity Tracking
- **Electricity generation by type**: coal, CCGT (combined cycle gas turbine), OCGT (open cycle gas turbine), nuclear, oil, wind, hydro, pumped storage, biomass, battery, and other sources
- **Embedded generation**: solar and wind power connected to the local distribution network (rather than the national transmission network)
- **Interconnector imports/exports**: IFA, Moyle, BritNed, EWIC, Nemo, IFA2, NSL, ElecLink, Viking, and Greenlink
- **Electricity pricing**: wholesale electricity prices from the APX spot market
- **Carbon emissions**: carbon intensity of electricity generation in grams of CO₂ per kilowatt-hour

### Gas Consumption Tracking
- **Direct gas consumption by sector**: domestic (heating, cooking), industrial, and commercial
- Gas consumption is tracked separately from gas-fired electricity generation (CCGT/OCGT) to avoid double-counting

### Data Updates
The application updates every 5 minutes via a cron job running `update.php`, fetching the latest data from multiple sources and aggregating it across different time periods.

## Development

The development environment uses [Docker](https://www.docker.com/).

Copy `.env.example` to `.env` and edit the values as appropriate. At a minimum, `DATABASE_PASSWORD` must be given a value.

To start the containers:

```
docker compose up --detach
```

Once the containers are running, you can view the site at [http://localhost:9714/](http://localhost:9714/). Port 9714 was chosen due to its slight resemblance to the word ‘grid’.

To run the update script:

```
docker compose exec php php /var/grid/update.php
```

To stop the containers:

```
docker compose down
```

## Production

The production environment does not use Docker, instead running directly on the server. PHP 8.3 and a recent version MariaDB or MySQL are required.

### Files

Copy `.env.example` to `.env` and edit the values as appropriate. At a minimum, `DATABASE_PASSWORD` must be given a value. `DATABASE_HOSTNAME` should be changed to `localhost` if the database is running on the same server.

Upload `.env`, `update.php`, and the `classes` and `public` directories to the server.

### Database

Create a database and a user with `SELECT`, `INSERT`, `UPDATE`, and `DELETE` privileges, and import `grid.sql` into the database.

#### Database Schema

The database stores time-series data in 5 tables with different granularities:
- `past_five_minutes` - 5-minute electricity generation data
- `past_half_hours` - 30-minute data (electricity generation, embedded generation, emissions, pricing, gas consumption)
- `past_days` - Daily aggregated data
- `past_weeks` - Weekly aggregated data
- `past_years` - Yearly aggregated data

Each table includes columns for:
- Electricity generation by type (coal, CCGT, OCGT, nuclear, oil, wind, hydro, pumped storage, biomass, battery, other)
- Embedded generation (solar and wind)
- Interconnector transfers (IFA, Moyle, BritNed, EWIC, Nemo, IFA2, NSL, ElecLink, Viking, Greenlink)
- Gas consumption by sector (domestic, industrial, commercial)
- Pricing and emissions data (where applicable)

### Web server

Configure the server to serve the contents of the `public` directory. Note that this directory contains only static files, so the web server does not need to support PHP.

### Cron

Set up a cron job to execute the `update.php` script (using the [PHP CLI SAPI](https://www.php.net/manual/en/features.commandline.usage.php)) every five minutes. The cron job must run as a user with write access to `public/favicon.svg` and `public/index.html`.

The script outputs details of the update process to standard output, and details of errors to standard error. An error with an individual data source does not abort the rest of the update process.

### Fonts

The CSS refers to `proza-light.woff2` and `proza-regular.woff2`. These are commercial fonts, so are not included in this repository. Licences for [Proza](http://bureauroffa.com/about-proza) can be purchased from [Bureau Roffa](http://bureauroffa.com/). Alternatively, the simplified free version [Proza Libre](http://bureauroffa.com/about-proza-libre) can be used instead.

### Cloudflare

National Grid: Live uses [Cloudflare](https://www.cloudflare.com/)’s content delivery network. Visit counts will be retrieved from Cloudflare if the `CLOUDFLARE_API_TOKEN` and `CLOUDFLARE_ZONE_ID` environment variables are set to non-empty strings. The Cloudflare API token must be configured to provide Analytics Read access for the zone.

## Codebase structure

PHP classes can be found in the `classes` directory. The [Database](classes/Database.php) class directly within this directory is responsible for all database access. The other classes are divided into three namespaces:

The [Data](classes/Data) namespace contains classes for reading data from the various data sources, as documented further below.

The [State](classes/State) namespace contains classes representing the data needed to output the user interface. The [State](classes/State/State.php) class is the overall container; an instance of this class is returned by the `getState()` method of a `Database` instance.

The [UI](classes/UI) namespace contains classes that output the user interface. The [UI](classes/UI/UI.php) class has overall responsibility for outputting the HTML, while the [Favicon](classes/UI/Favicon.php) class outputs the dynamically-updated favicon.

## Data sources

### Electricity Data

#### [Elexon Insights Solution](https://bmrs.elexon.co.uk/)

This API, developed by Elexon, reports power generation connected to the national transmission network, interconnector imports and exports, and pricing.

Data is available in JSON format at 30-minute or 5-minute granularity.

PHP classes: [Generation](classes/Data/Generation.php), [Pricing](classes/Data/Pricing.php)

#### [National Energy System Operator Data Portal](https://www.neso.energy/data-portal)

This API, developed by the National Energy System Operator, estimates power generation from embedded solar and wind (generation connected to the local distribution network rather than the national transmission network).

Data is available in CSV format at 30-minute granularity. Estimates may be retrospectively updated.

PHP class: [Demand](classes/Data/Demand.php)

#### [Carbon Intensity API](https://carbonintensity.org.uk/)

This API, developed by the National Energy System Operator and the University Of Oxford Department Of Computer Science, estimates the carbon intensity of electricity generation in grams of carbon dioxide per kilowatt-hour.

Data is available in JSON format at 30-minute granularity. Estimates may be retrospectively updated.

PHP class: [Emissions](classes/Data/Emissions.php)

### Gas Consumption Data

#### Gas Data Sources (To Be Integrated)

Gas consumption data infrastructure has been added to the database schema and backend, ready for integration with:
- [National Gas](https://www.nationalgas.com/) or [National Gas Data Portal](https://data.nationalgas.com/)
- [NESO Data Portal](https://www.neso.energy/data-portal) (Gas Demand)
- [DESNZ Energy Trends](https://www.gov.uk/government/organisations/department-for-energy-security-and-net-zero)

The database schema now includes columns for `domestic_gas`, `industrial_gas`, and `commercial_gas` across all time-series tables. Gas consumption is tracked separately from gas-fired electricity generation (CCGT/OCGT) to prevent double-counting.

PHP class: [GasConsumption](classes/Data/GasConsumption.php) (placeholder implementation)

## Future plans

Battery storage data isn’t yet shown. The Elexon Insights Solution only reports on discharging of battery storage systems. Without charging being reported, this would lead to double-counting of generation.

I’m not currently planning any other major changes. I believe it’s better for a project like this to have a limited scope and a concise interface serving the general public than to attempt to offer specialised analysis for energy industry experts.
