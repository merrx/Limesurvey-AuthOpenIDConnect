# AuthOpenIDConnect - LimeSurvey Auth Plugin

## Disclaimer
Original plugin by https://github.com/janmenzel

updated by olicha@worteks, Philipp Seßner, Merrx

## Updates
* updated the compatibility in the config.xml file
* add ?noAuthOpenIDConnect=true to bypass SSO and get authDb

## Install

1. Download the plugin.

2. Install necessary dependencies via composer.
```
composer install
```

3. Zip the plugin with all dependencies installed.
```
zip -r AuthOpenIDConnect AuthOpenIDConnect/*
```

4. Install the plugin in LimeSurvey and fill in the necessary settings in order to connect to your ID Provider.

## Credits
Thanks to Michael Jett for providing the [OpenID Connect Client](https://github.com/jumbojett/OpenID-Connect-PHP)!
