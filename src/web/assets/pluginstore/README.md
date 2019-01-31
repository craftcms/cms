# Plugin Store Resources

## Setup

### Dev Server

To get started with local development for the Plugin Store resources, you need to: 

1. Copy all of the environment variables from `/src/web/assets/pluginstore/.env.example` to a `/web/craftnetresources/id/.env` file.
2. In your `.env` file, make sure that the paths to your SSL key and certificate are correct:
 
        DEV_SERVER_SSL_KEY="../../../../../../ssl/pluginstore.dev.key"
        DEV_SERVER_SSL_CERT="../../../../../../ssl/pluginstore.dev.crt"
3. Make sure `devMode` is enabled in `config/general.php`:
        
        <?php

        return [
            'devMode' => true,
        ];
4. Tell the Plugin Store to use the dev server to serve resources in `config/app.php`:
        
        <?php
        
        return [
            'components' => [
                'pluginStore' => [
                    'class' => craft\services\PluginStore::class,
                    'useDevServer' => true,
                ],
            ],
        ];


#### Customizing the dev server

By default, the dev server will be set up to work with `https://localhost:8082/`. If you want to change the host or port, port you need to:
1. Update your `.env` with the new values:

        DEV_PUBLIC_PATH="https://localhost:8089/"
        DEV_SERVER_PORT="8089"
        
2. Tweak the Plugin Store service by adding a `config/app.php` file:

        <?php
        
        return [
            'components' => [
                'pluginStore' => [
                    'class' => craft\services\PluginStore::class,
                    'useDevServer' => true,
                    'devServerManifestPath' => 'https://localhost:8089',
                    'devServerPublicPath' => 'https://localhost:8089',
                ],
            ],
        ];

## Commands

### Install
    npm install
    
### Build for Development
    npm run serve

### Build for Production
    npm run build

### Lint
    npm run lint
