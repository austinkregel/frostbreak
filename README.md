# A WinterCMS Marketplace 

Welcome to the source of [frostbreak.market](https://frostbreak.market)! This application aims to provide a modern, community-driven marketplace for the WinterCMS ecosystem, with a primary focus on WinterCMS compatibility.

## Project Goals
- **Marketplace for CMS Ecosystems:** Serve as a self-hostable central hub for discovering, searching, installing, and updating plugins and themes for WinterCMS.
- **Project Linking and Creating:** Easily link your projects to the marketplace for streamlined management.
- **Plugin & Theme Management:** Search, install, and update plugins and themes directly from the marketplace interface.
- **Core Updates:** Experimental support for updating the core WinterCMS version (currently under investigation; use with caution).

## Features
- User registration and authentication
- Project creation and management
- Linking projects to WinterCMS installations
- Searching, installing, and updating plugins and themes
- Experimental support for core WinterCMS updates
- Repackaging plugins and themes
- Easy configuration and setup!
- Self-hostable


## Setup Instructions

<!-- 
Will be writing these later once I finalize a docker container to publish. for an almost "prod ready" version
-->


## Development Setup Instructions

‼️ Keep in mind that this will download all available packages from Packagist to build the index. 
This could take a while and use a lot of disk space. At present the compressed packages take up about 2.1GB. ‼️

It as it completes repackaging it will delete the original code when zipped, so the actual _required_ space is likely about 5GB+. Do not run this on a system with limited disk space, or on a metered connection (unless you're okay with the data usage).

Either download or clone the repository then:

```bash
composer install
npm install
npm run build # Could also be dev if you want to run with hot reloading (Enter the following commands in a new shell)
vendor/bin/sail up -d
vendor/bin/sail artisan migrate
vendor/bin/sail artisan packages:build-index-from-packagist
vendor/bin/sail artisan horizon
```

This will set up the application's database, and queue querying Packagist for all available packages, then start the horizon queue worker to process the repackaging of plugins and themes.

This will take a while to finish downloading all the versions, so be patient (30+ minutes with several queue workers).

When you're ready to access the app visit [localhost](http://localhost:8000) in your browser. There is no default user, so you'll need to register a new account.

### Configuring WinterCMS Installation

To link a WinterCMS installation to the marketplace, you need to add the marketplace URL to the `config/cms.php` file of your WinterCMS installation.

```php
    'updateServer' => env('WINTER_MARKETPLACE_URL', 'http://marketplace.test/place'),
```

In `http://DOMAIN/place`, replace `DOMAIN` with the domain or IP address where your marketplace is hosted `/place` is the endpoint that WinterCMS will use to communicate with the marketplace.

To restore the original WinterCMS update server, comment out the updateServer line or replace it with:

```php
    'updateServer' => env('WINTER_MARKETPLACE_URL', 'https://api.wintercms.com/marketplace'),
```

By default, WinterCMS will use `https://api.wintercms.com/marketplace` and basically act as a proxy to the OctoberCMS Marketplace. 

## Future plans
- [ ] A way to claim ownership of a project or published package to add additional details
- [ ] A way to for creators to upload plugins and themes
- [ ] A way to for creators to manage their plugins and themes
- [ ] Investigate a monetization strategy for creators & the marketplace (ads, sponsorships, donations, paid plugins/themes, etc.)
- [ ] Authentication to private repositories for package indexing

## Contributing

We welcome open source contributions! Whether you're fixing bugs, adding features, improving documentation, or just sharing ideas, your input is valued. Please open an issue or submit a pull request to get started.

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
